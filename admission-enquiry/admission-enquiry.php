<?php
/* =========================================
   Admission Enquiry - Plain PHP Endpoint
   =========================================
   - POST fields: name, email, phone, grade, source, pageUrl (optional)
   - Saves to DB (see SQL below)
   - Posts to Google Sheets (Apps Script Web App)
   - Sends admin email
   - Returns JSON

   Place this file in your public web root (e.g., /admission_enquiry.php)
   Update DB credentials & admin email below.
*/

// ---------- CONFIG ----------
$DB_HOST = "localhost";
$DB_NAME = "theneo1n_testnestdb";
$DB_USER = "theneo1n_smmuser";
$DB_PASS = "Nest@2025";
$DB_TABLE = 'admission_enquiries'; // new table

// Your Apps Script Web App URL (same as in plugin)
$GSHEET_WEBHOOK = 'https://script.google.com/macros/s/AKfycbx-fAGE3IXGOTXKCAscPb3VXWEY1rDvfolgboLNfjfky38DMstrd_wc4UVLcPYJOOFkxg/exec';

// Admin email
$ADMIN_TO = 'info@rankraze.com';
$ADMIN_CC = 'smm@msec.edu.in';
$FROM_NAME = 'The NEST School';  // for email From:
$FROM_EMAIL = 'no-reply@thenest.school'; // must be allowed by server

// ---------- Helpers ----------
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

function require_post() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

// ---------- Main ----------
require_post();

// Collect input (supports JSON or form POST)
$raw = file_get_contents('php://input');
$params = [];
if (!empty($raw) && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $params = json_decode($raw, true) ?: [];
} else {
    $params = $_POST;
}

// Sanitize
$name   = isset($params['name'])  ? trim($params['name'])  : '';
$email  = isset($params['email']) ? trim($params['email']) : '';
$phone  = isset($params['phone']) ? preg_replace('/\D+/', '', $params['phone']) : '';
$grade  = isset($params['grade']) ? trim($params['grade']) : '';
$source = isset($params['source']) ? trim($params['source']) : '';
$pageUrl = '';
if (isset($params['pageUrl']))  $pageUrl = filter_var($params['pageUrl'], FILTER_SANITIZE_URL);
if (isset($params['page_url'])) $pageUrl = filter_var($params['page_url'], FILTER_SANITIZE_URL);
$submittedAt = date('Y-m-d H:i:s');

// Validate
$errors = [];
if ($name === '')   $errors['name']  = 'Name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
if ($phone === '')  $errors['phone'] = 'Phone is required.';
elseif (!preg_match('/^[0-9]{10}$/', $phone)) $errors['phone'] = 'Phone must be exactly 10 digits.';
if ($grade === '')  $errors['grade'] = 'Grade is required.';
if ($source === '') $errors['source'] = 'Source is required.';

if (!empty($errors)) {
    json_response(['success' => false, 'message' => 'Validation errors', 'errors' => $errors], 422);
}

// Save to DB
try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $sql = "INSERT INTO {$DB_TABLE}
            (name, email, phone, grade, source, page_url, submitted_at, sheet_status, sheet_response, mail_status)
            VALUES (:name, :email, :phone, :grade, :source, :page_url, :submitted_at, NULL, NULL, NULL)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'         => $name,
        ':email'        => $email,
        ':phone'        => $phone,
        ':grade'        => $grade,
        ':source'       => $source,
        ':page_url'     => $pageUrl ?: null,
        ':submitted_at' => $submittedAt,
    ]);
    $insertId = (int)$pdo->lastInsertId();

} catch (Throwable $e) {
    error_log('Admission Enquiry DB error: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Database error'], 500);
}

// Post to Google Sheets
$sheetStatus = 'not_sent';
$sheetResponse = '';
if (!empty($GSHEET_WEBHOOK)) {
    $payload = [
        'id'           => $insertId,
        'name'         => $name,
        'email'        => $email,
        'phone'        => $phone,
        'grade'        => $grade,
        'source'       => $source,
        'pageUrl'      => $pageUrl,
        'submitted_at' => $submittedAt,
    ];

    try {
        $ch = curl_init($GSHEET_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $respBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($respBody === false) {
            $sheetStatus = 'error';
            $sheetResponse = 'curl_error: ' . curl_error($ch);
        } else {
            if ($httpCode >= 200 && $httpCode < 300) {
                $sheetStatus = 'ok';
                $sheetResponse = 'HTTP ' . $httpCode;
            } else {
                $sheetStatus = 'http_' . $httpCode;
                $sheetResponse = substr((string)$respBody, 0, 1000);
            }
        }
        curl_close($ch);
    } catch (Throwable $e) {
        $sheetStatus = 'error';
        $sheetResponse = 'exception: ' . $e->getMessage();
    }

    // Update row with sheet status
    try {
        $stmt = $pdo->prepare("UPDATE {$DB_TABLE} SET sheet_status = :st, sheet_response = :sr WHERE id = :id");
        $stmt->execute([':st' => $sheetStatus, ':sr' => $sheetResponse, ':id' => $insertId]);
    } catch (Throwable $e) {
        error_log('Admission Enquiry update sheet status error: ' . $e->getMessage());
    }
}

// Send Admin Email
$mailStatus = 'failed';
$subject = sprintf('New Admission Enquiry — %s (ID: %d)', $name ?: 'Unknown', $insertId);

$body = '<html><body>';
$body .= '<h2>New Admission Enquiry</h2>';
$body .= '<table cellpadding="6" cellspacing="0" border="0">';
$body .= '<tr><td style="font-weight:600;">ID</td><td>' . htmlspecialchars($insertId) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Name</td><td>' . htmlspecialchars($name) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Email</td><td>' . htmlspecialchars($email) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Phone</td><td>' . htmlspecialchars($phone) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Grade</td><td>' . htmlspecialchars($grade) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Source</td><td>' . htmlspecialchars($source) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Page URL</td><td>' . htmlspecialchars($pageUrl) . '</td></tr>';
$body .= '<tr><td style="font-weight:600;">Submitted At</td><td>' . htmlspecialchars($submittedAt) . '</td></tr>';
$body .= '</table>';
$body .= '<p style="color:#666;font-size:13px;">This email was generated by the Admission Enquiry endpoint.</p>';
$body .= '</body></html>';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
if (!empty($ADMIN_CC)) {
    $headers .= "Cc: {$ADMIN_CC}\r\n";
}

if (@mail($ADMIN_TO, $subject, $body, $headers)) {
    $mailStatus = 'sent';
} else {
    $mailStatus = 'failed';
}

// Update mail status
try {
    $stmt = $pdo->prepare("UPDATE {$DB_TABLE} SET mail_status = :ms WHERE id = :id");
    $stmt->execute([':ms' => $mailStatus, ':id' => $insertId]);
} catch (Throwable $e) {
    error_log('Admission Enquiry update mail status error: ' . $e->getMessage());
}

// Final JSON
json_response([
    'success'      => true,
    'message'      => 'Enquiry saved successfully',
    'id'           => $insertId,
    'mail_sent'    => ($mailStatus === 'sent'),
    'sheet_status' => $sheetStatus,
], 200);
