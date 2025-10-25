<?php
// admission.php — RECEIVE frontend POST, SAVE to DB, optionally POST to Google Sheet
// Replace your existing file with this one. Keep config.php (DB info) next to it.
// Top of admission.php — ensure nothing leaks before JSON output
ini_set('display_errors', 0);          // never show warnings to user
ini_set('log_errors', 1);              // still log errors
error_reporting(E_ALL);

ob_start();
// ---------- CONFIG ----------
$DEBUG_MODE = false; // set true for local debugging (shows PHP errors) — set false in production

// Google Sheet WebApp (kept here per your request)
$GSHEET_WEBAPP_URL    = 'https://script.google.com/macros/s/AKfycbxNayFaYXwjmrIue1T8uJfmDsrNz-OaVJMGNw-gOfujxg8L-X-KBeU1NqYLe0IPaTbBMQ/exec';
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';

// ---------- debug / error handling ----------
if ($DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}

header('Content-Type: application/json; charset=utf-8');

// debug log file
$DEBUG_LOG = __DIR__ . '/admission_debug.log';
function dbg($msg) {
    global $DEBUG_LOG;
    $line = "[".date('Y-m-d H:i:s')."] $msg\n";
    @file_put_contents($DEBUG_LOG, $line, FILE_APPEND | LOCK_EX);
}

// ---------- load config.php (must provide DB credentials) ----------
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    dbg("Missing config.php at $configPath");
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server configuration missing']);
    exit;
}
require_once $configPath;

// Support both DB_* constants and legacy vars ($servername etc.)
if (!defined('DB_HOST') && isset($servername)) define('DB_HOST', $servername);
if (!defined('DB_USER') && isset($username)) define('DB_USER', $username);
if (!defined('DB_PASS') && isset($password)) define('DB_PASS', $password);
if (!defined('DB_NAME') && isset($database)) define('DB_NAME', $database);

// ---------- only POST ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    dbg("Wrong method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success'=>false,'message'=>'Only POST allowed']);
    exit;
}

// ---------- read input (form-data or JSON) ----------
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = array_merge($input, $json);
}

// ---------- sanitizers & validators ----------
function safe_trim($v){ return trim((string)($v ?? '')); }
function valid_email($e){ return filter_var($e, FILTER_VALIDATE_EMAIL); }
function clean_phone($p){ return preg_replace('/\D+/', '', (string)$p); }

$name  = safe_trim($input['name'] ?? '');
$email = safe_trim($input['email'] ?? '');
$phone = safe_trim($input['phone'] ?? '');
$grade = safe_trim($input['grade'] ?? '');
$source = safe_trim($input['source'] ?? '');

dbg("Received POST: name=" . substr($name,0,80) . " email=" . substr($email,0,80) . " phone=" . substr($phone,0,40) . " grade=" . substr($grade,0,40) . " source=" . substr($source,0,80));

// validation
$errors = [];
if ($name === '') $errors[] = 'Name is required';
if ($email === '' || !valid_email($email)) $errors[] = 'Valid email is required';
$phone_clean = clean_phone($phone);
if ($phone_clean === '' || strlen($phone_clean) < 7) $errors[] = 'Valid phone is required';
if ($grade === '' || $grade === 'Select Your Option') $errors[] = 'Please choose grade';
if ($source === '' || $source === 'Select Your Option') $errors[] = 'Please choose source';

if (!empty($errors)) {
    dbg('Validation failed: ' . implode('; ', $errors) . ' | input: ' . json_encode($input));
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>implode('; ', $errors)]);
    exit;
}

// ---------- DB config presence ----------
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
    dbg('DB config missing in config.php');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server DB configuration missing']);
    exit;
}

// ---------- connect to DB ----------
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    dbg("DB connect error: " . $mysqli->connect_error);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// ---------- ensure table exists ----------
$create_sql = "CREATE TABLE IF NOT EXISTS admissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  grade VARCHAR(50) NOT NULL,
  source VARCHAR(100) NOT NULL,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (!$mysqli->query($create_sql)) {
    dbg("Create table error: " . $mysqli->error);
    // continue — insert will likely fail if table truly missing
}

// ---------- insert record ----------
$stmt = $mysqli->prepare("INSERT INTO admissions (name, email, phone, grade, source) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    dbg("Prepare failed: " . $mysqli->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database error']);
    $mysqli->close();
    exit;
}
$name_db = mb_substr($name, 0, 150);
$email_db = mb_substr($email, 0, 150);
$phone_db = mb_substr($phone_clean, 0, 50);
$grade_db = mb_substr($grade, 0, 50);
$source_db = mb_substr($source, 0, 100);
$stmt->bind_param('sssss', $name_db, $email_db, $phone_db, $grade_db, $source_db);

if (!$stmt->execute()) {
    dbg("Insert failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Failed to save record']);
    $stmt->close();
    $mysqli->close();
    exit;
}

$insert_id = $stmt->insert_id;
$stmt->close();
$mysqli->close();

dbg("Saved admission id=$insert_id name={$name_db} email={$email_db}");

// ---------- post to Google Apps Script WebApp (JSON) ----------
$sheet_ok = false;
if (!empty($GSHEET_WEBAPP_URL) && !empty($GSHEET_SHARED_SECRET)) {
    $payload = [
        'secret' => $GSHEET_SHARED_SECRET,
        'id' => $insert_id,
        'name' => $name_db,
        'email' => $email_db,
        'phone' => $phone_db,
        'grade' => $grade_db,
        'source' => $source_db,
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    $ch = curl_init($GSHEET_WEBAPP_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $resp = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);
    dbg("GSheet post http={$http_status} curl_err={$curl_err} resp=" . substr((string)$resp,0,300));
    if ($resp !== false && $http_status >= 200 && $http_status < 300) {
        $dec = json_decode($resp, true);
        if (is_array($dec) && isset($dec['success']) && $dec['success'] === true) {
            $sheet_ok = true;
            dbg("Posted to Google Sheet OK for id={$insert_id}");
        } else {
            dbg("GSheet returned non-success: " . $resp);
        }
    }
} else {
    dbg("GSheet URL or secret not set in admission.php; skipping push.");
}

// ---------- respond ----------
echo json_encode([
    'success' => true,
    'message' => 'Admission enquiry saved.',
    'id' => intval($insert_id),
    'sent_to_sheet' => $sheet_ok
]);
exit;
