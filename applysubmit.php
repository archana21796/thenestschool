<?php
// applysubmit.php
// Accepts POST multipart/form-data from the apply modal, saves file, stores DB record, posts to Google Sheets

// --- Basic config defaults (can be overridden by config.php) ---
$GSHEET_WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbzNKkchMOoBZb1xzQp3Uh8YlFCk_FQ_PpcEg9cUGW-TJAb6MIMAgDVqv7qUrnPfZb3BQw/exec';
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';
$DEBUG_LOG = __DIR__ . '/brochure_debug.log';

// LOG helper (guarded)
if (!function_exists('dbg')) {
    function dbg($msg) {
        global $DEBUG_LOG;
        $line = "[" . date('Y-m-d H:i:s') . "] $msg\n";
        @file_put_contents($DEBUG_LOG, $line, FILE_APPEND | LOCK_EX);
    }
}

header('Content-Type: application/json; charset=utf-8');

// reflect origin for CORS (only for debugging; lock to your domain in production)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed', 'saved_to_db' => false]);
    exit;
}

// include config.php (should define DB_* constants or $servername style variables)
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    dbg("Missing config.php");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server config missing', 'saved_to_db' => false]);
    exit;
}
require_once $configPath;

// Allow config.php to override defaults
$GSHEET_WEBAPP_URL = $GSHEET_WEBAPP_URL ?? ($GLOBALS['GSHEET_WEBAPP_URL'] ?? '');
$GSHEET_SHARED_SECRET = $GSHEET_SHARED_SECRET ?? ($GLOBALS['GSHEET_SHARED_SECRET'] ?? '');
$DEBUG_LOG = $DEBUG_LOG ?? ($GLOBALS['DEBUG_LOG'] ?? (__DIR__ . '/brochure_debug.log'));

// validators
function isEmail($v) { return filter_var(trim($v), FILTER_VALIDATE_EMAIL); }
function isPhone($v) { return strlen(preg_replace('/\D/', '', (string)$v)) >= 7; }
function safe_trim($v) { return trim((string)($v ?? '')); }

try {
    // Read & sanitize input
    $name = safe_trim($_POST['name'] ?? '');
    $email = safe_trim($_POST['email'] ?? '');
    $phone = safe_trim($_POST['phone'] ?? '');
    $positions = $_POST['positions'] ?? []; // may be array or comma string
    $source = safe_trim($_POST['source'] ?? 'apply-modal');

    dbg("Request received: name=" . substr($name,0,80) . " email=" . substr($email,0,80) . " phone=" . substr($phone,0,30));

    // Normalize positions: allow array or comma-separated string
    if (!empty($positions) && !is_array($positions)) {
        $positions = array_map('trim', explode(',', (string)$positions));
    }
    if (!is_array($positions)) $positions = [];
    $positions = array_values(array_filter(array_map('trim', $positions)));
    $positions_str = implode(', ', $positions);

    // Basic validation
    if ($name === '' || !isEmail($email) || !isPhone($phone) || empty($positions)) {
        dbg("Validation failed: name/ email / phone / positions check");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid form data.', 'saved_to_db' => false]);
        exit;
    }

    // --- Handle resume upload (if any) ---
    $resumePath = null;
    if (!empty($_FILES['resume']['name']) && isset($_FILES['resume']['tmp_name'])) {
        $uploadDir = __DIR__ . './assets/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $originalName = basename($_FILES['resume']['name']);
        $safeBase = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $safeName = time() . '_' . $safeBase;
        $targetPath = $uploadDir . $safeName;

        if (is_uploaded_file($_FILES['resume']['tmp_name'])) {
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                // if uploads folder is web-served, this is the relative path
                $resumePath = 'uploads/' . $safeName;
                dbg("Resume uploaded: $resumePath");
            } else {
                dbg("move_uploaded_file failed for " . $_FILES['resume']['name']);
                $resumePath = null;
            }
        } else {
            dbg("No uploaded file or invalid upload: " . $_FILES['resume']['name']);
        }
    }

    // --- Insert into MySQL DB ---
    $savedToDb = false;
    // check config: support constants (preferred) or older variable names
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
        // try legacy vars
        if (isset($servername, $username, $password, $database)) {
            define('DB_HOST', $servername);
            define('DB_USER', $username);
            define('DB_PASS', $password);
            define('DB_NAME', $database);
        }
    }

    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
        dbg("DB config missing in config.php");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server DB configuration missing', 'saved_to_db' => false]);
        exit;
    }

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        dbg("DB connect error: " . $mysqli->connect_error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed', 'saved_to_db' => false]);
        exit;
    }
    $mysqli->set_charset('utf8mb4');

    // ensure table exists
    $create_sql = "CREATE TABLE IF NOT EXISTS applications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      email VARCHAR(150) NOT NULL,
      phone VARCHAR(50) NOT NULL,
      positions TEXT NOT NULL,
      resume VARCHAR(255) DEFAULT NULL,
      source VARCHAR(50) DEFAULT 'apply-modal',
      submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$mysqli->query($create_sql)) {
        dbg("Create table error: " . $mysqli->error);
        // continue - insert may still fail - will be handled
    }

    $stmt = $mysqli->prepare("INSERT INTO applications (name, email, phone, positions, resume, source) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        dbg("Prepare failed: " . $mysqli->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error', 'saved_to_db' => false]);
        exit;
    }
    $stmt->bind_param('ssssss', $name, $email, $phone, $positions_str, $resumePath, $source);

    if (!$stmt->execute()) {
        dbg("Insert failed: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save application.', 'saved_to_db' => false]);
        $stmt->close();
        $mysqli->close();
        exit;
    }

    $insertedId = $stmt->insert_id;
    $savedToDb = true;
    $stmt->close();
    $mysqli->close();

    dbg("Saved application id=$insertedId");

    // --- Optional: Post to Google Sheets Webapp (non-blocking best-effort) ---
    if (!empty($GSHEET_WEBAPP_URL) && !empty($GSHEET_SHARED_SECRET)) {
        $payload = [
            'secret' => $GSHEET_SHARED_SECRET,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'positions' => $positions_str,
            'resume' => $resumePath,
            'source' => $source,
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        $ch = curl_init($GSHEET_WEBAPP_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        dbg("GSheet post http=$httpCode curlErr=$curlErr resp=" . substr((string)$resp, 0, 400));
        // do not fail the request if sheet push fails
    }

    // success response
    echo json_encode([
      'success' => true,
      'message' => 'Application received successfully.',
      'saved_to_db' => $savedToDb,
      'insert_id' => $insertedId
    ]);
    exit;

} catch (Exception $e) {
    dbg("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.', 'saved_to_db' => false]);
    exit;
}
