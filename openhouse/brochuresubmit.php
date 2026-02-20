<?php
// brochure_submit.php
// Inserts lead into DB and pushes data to Google Sheet via Apps Script Web App

// --- CONFIG ---
$GSHEET_WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbxjk6bu1yfKJWuOr4NhBPwUPLhuT3j0DDeOA7kKyV5conRdkCdcYnWm8RP4PjsC8OvY/exec';
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';
$DEBUG_LOG = __DIR__ . '/brochure_debug.log';

// --- LOG HELPER ---
function dbg($msg) {
    global $DEBUG_LOG;
    $line = "[" . date('Y-m-d H:i:s') . "] $msg\n";
    @file_put_contents($DEBUG_LOG, $line, FILE_APPEND | LOCK_EX);
}

// --- BASIC CHECKS ---
header('Content-Type: application/json; charset=utf-8');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

// --- INCLUDE CONFIG ---
$configPath = dirname(__DIR__) . '/../config.php';
if (!file_exists($configPath)) {
    dbg("Missing config.php");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server config missing']);
    exit;
}
require_once $configPath;

// --- DB CONNECTION ---
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($servername, $username, $password, $database)) {
        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            dbg('DB connect error: ' . $conn->connect_error);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
    } else {
        dbg('No DB connection info found');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
        exit;
    }
}
$conn->set_charset('utf8mb4');

// --- READ INPUT ---
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = array_merge($input, $json);
}

// --- SANITIZE ---
function safe_trim($v) { return trim((string)($v ?? '')); }
function valid_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }
function clean_phone($p) { return preg_replace('/\D+/', '', (string)$p); }

// --- VALIDATE ---
$name  = safe_trim($input['name'] ?? '');
$email = safe_trim($input['email'] ?? '');
$phone = safe_trim($input['phone'] ?? '');
$source = safe_trim($input['source'] ?? $input['heardAbout'] ?? 'brochure-modal');
$download_url = safe_trim($input['download_url'] ?? $input['downloadUrl'] ?? '');

$errors = [];
if ($name === '') $errors[] = 'Name is required';
if ($email === '' || !valid_email($email)) $errors[] = 'Valid email is required';
$phone_clean = clean_phone($phone);
if ($phone_clean === '' || strlen($phone_clean) < 7) $errors[] = 'Valid phone required';

if (!empty($errors)) {
    dbg('Validation failed: ' . implode('; ', $errors));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
    exit;
}

// --- ENSURE TABLE ---
$table = 'brochure_leads';
$conn->query("
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
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
");

// --- INSERT LEAD ---
$stmt = $conn->prepare("INSERT INTO `{$table}` (`name`,`email`,`phone`,`download_url`,`source`,`ip_address`,`user_agent`)
VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    dbg("Prepare failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB prepare failed']);
    exit;
}

$ip_db = $_SERVER['REMOTE_ADDR'] ?? '';
$ua_db = $_SERVER['HTTP_USER_AGENT'] ?? '';
$stmt->bind_param('sssssss', $name, $email, $phone_clean, $download_url, $source, $ip_db, $ua_db);
$stmt->execute();
$inserted_id = $stmt->insert_id;
$stmt->close();

dbg("Inserted lead ID: {$inserted_id} | name={$name} email={$email} phone={$phone_clean}");

// --- SEND TO GOOGLE SHEET ---
$gs_payload = [
    'sheet' => 'brochureenquiry',
    'registration_id' => $inserted_id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone_clean,
    'source' => $source,
    'download_url' => $download_url,
    'ip' => $ip_db,
    'user_agent' => $ua_db,
    'received_at' => date('Y-m-d H:i:s'),
    'secret' => $GSHEET_SHARED_SECRET
];

$gs_ok = false;

if (!empty($GSHEET_WEBAPP_URL)) {
    $ch = curl_init($GSHEET_WEBAPP_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gs_payload));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // handle redirects
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BrochureSubmit/1.0');

    $resp = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    dbg("Google Sheet POST HTTP Status: $http_status");
    dbg("Google Sheet Response: " . substr((string)$resp, 0, 4000));
    dbg("Google Sheet CURL Error: " . $curl_err);

    if ($resp !== false && $http_status >= 200 && $http_status < 300) {
        if (strpos($resp, 'Script function not found') !== false) {
            dbg("ERROR: Apps Script missing doPost() in deployed version!");
        } else {
            $decoded = json_decode($resp, true);
            if (is_array($decoded) && isset($decoded['success']) && $decoded['success'] === true) {
                $gs_ok = true;
                dbg("✅ Sent successfully to Google Sheet for ID $inserted_id");
            } else {
                dbg("Apps Script returned non-success: $resp");
            }
        }
    } else {
        dbg("HTTP Failure: $http_status | CURL: $curl_err");
    }
} else {
    dbg("GSHEET_WEBAPP_URL not set — skipping Google Sheet push");
}

// --- RESPONSE ---
$response = [
    'success' => true,
    'id' => intval($inserted_id),
    'sent_to_sheet' => $gs_ok,
    'message' => 'Thank you — brochure request recorded.'
];

dbg("Response to client: " . json_encode($response));
echo json_encode($response);
exit;
