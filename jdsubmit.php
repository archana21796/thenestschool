<?php
// jd_submit.php
// Save next to config.php (adjust require path if needed)

// CONFIG: set Apps Script web app URL and shared secret (must match Apps Script)
$GSHEET_WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbxjk6bu1yfKJWuOr4NhBPwUPLhuT3j0DDeOA7kKyV5conRdkCdcYnWm8RP4PjsC8OvY/exec'; // <-- set your webapp URL
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';                           // <-- must match Apps Script SHARED_SECRET

// debug log file (next to this script)
$DEBUG_LOG = __DIR__ . '/jd_debug.log';

function dbg($msg) {
    global $DEBUG_LOG;
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] $msg\n";
    try {
        file_put_contents($DEBUG_LOG, $line, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("jd_submit fallback log: " . $line);
    }
}

header('Content-Type: application/json; charset=utf-8');

// CORS (adjust to production domain)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    dbg('Wrong method: ' . $_SERVER['REQUEST_METHOD']);
    exit;
}

// load config (expects $conn or DB vars)
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server configuration error (config.php missing)']);
    dbg("Config file missing: $configPath");
    exit;
}
require_once $configPath;

// build $conn if not already present
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($servername, $username, $password, $database)) {
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            dbg('JD DB connect error: ' . $conn->connect_error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        dbg('Database connection not available (no $conn and no config vars).');
        exit;
    }
}
$conn->set_charset('utf8mb4');

// read incoming data (JSON or form)
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = array_merge($input, $json);
}

// sanitizers
function safe_trim($v) { return trim((string)($v ?? '')); }
function valid_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }
function clean_phone($p) { return preg_replace('/\D+/', '', (string)$p); }

// fields
$name  = safe_trim($input['name'] ?? '');
$email = safe_trim($input['email'] ?? '');
$phone = safe_trim($input['phone'] ?? '');
$source = safe_trim($input['source'] ?? 'jd-download');
$download_url = safe_trim($input['download_url'] ?? $input['downloadUrl'] ?? '');

// validation
$errors = [];
if ($name === '') $errors[] = 'Name is required';
if ($email === '' || !valid_email($email)) $errors[] = 'Valid email is required';
$phone_clean = clean_phone($phone);
if ($phone_clean === '' || strlen($phone_clean) < 7) $errors[] = 'Valid phone required';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
    dbg('Validation failed: ' . implode('; ', $errors) . " | input: " . json_encode($input));
    exit;
}

// ensure DB table exists
$table = 'jd_leads';
$create_sql = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `download_url` VARCHAR(512) DEFAULT NULL,
  `source` VARCHAR(128) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(1024) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`email`),
  INDEX (`phone`)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if (! $conn->query($create_sql) ) {
    dbg("JD submit: create table failed: " . $conn->error);
}

// insert
$stmt = $conn->prepare("INSERT INTO `{$table}` (`name`,`email`,`phone`,`download_url`,`source`,`ip_address`,`user_agent`) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    dbg("JD submit prepare failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

$name_db = mb_substr($name, 0, 190);
$email_db = mb_substr($email, 0, 190);
$phone_db = mb_substr($phone_clean, 0, 31);
$download_db = mb_substr($download_url, 0, 500);
$source_db = mb_substr($source, 0, 120);
$ip_db = $_SERVER['REMOTE_ADDR'] ?? '';
$ip_db = mb_substr($ip_db, 0, 44);
$ua_db = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1023);

$stmt->bind_param('sssssss', $name_db, $email_db, $phone_db, $download_db, $source_db, $ip_db, $ua_db);
$ok = $stmt->execute();

if (!$ok) {
    dbg("JD submit insert failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error (insert failed)']);
    exit;
}

$inserted_id = $stmt->insert_id;
$stmt->close();
dbg("Inserted JD lead ID: {$inserted_id} | name={$name_db} email={$email_db} phone={$phone_db}");

// ---------- Send to Google Apps Script WebApp (sheet) ----------
$gs_payload = array(
    'sheet' => 'enquirejd',   // target sheet/tab name (second tab)
    'registration_id' => $inserted_id,
    'name' => $name_db,
    'email' => $email_db,
    'phone' => $phone_db,
    'source' => $source_db,
    'download_url' => $download_db,
    'ip' => $ip_db,
    'user_agent' => $ua_db,
    'received_at' => date('Y-m-d H:i:s'),
    'secret' => $GSHEET_SHARED_SECRET
);

$gs_ok = false;
if (!empty($GSHEET_WEBAPP_URL)) {
    $ch = curl_init($GSHEET_WEBAPP_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gs_payload));
    $resp = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    dbg("Google Sheet POST HTTP Status: " . $http_status);
    dbg("Google Sheet Response: " . var_export($resp, true));
    dbg("Google Sheet CURL Error: " . $curl_err);

    if ($resp !== false && $http_status >= 200 && $http_status < 300) {
        $decoded = json_decode($resp, true);
        if (is_array($decoded) && isset($decoded['success']) && $decoded['success'] === true) {
            $gs_ok = true;
            dbg("Google Sheet push succeeded for jd id={$inserted_id}");
        } else {
            dbg("JD submit: Apps Script returned non-success: " . $resp);
        }
    } else {
        dbg("JD submit: Apps Script post failed (status {$http_status}) curl_err: {$curl_err} resp: {$resp}");
    }
} else {
    dbg("JD submit: GSHEET_WEBAPP_URL not configured, skipping sheet push.");
}

$response = [
    'success' => true,
    'id' => intval($inserted_id),
    'sent_to_sheet' => $gs_ok,
    'message' => 'Thank you — JD request recorded.'
];

dbg("Response to client: " . json_encode($response));
echo json_encode($response);
exit;
