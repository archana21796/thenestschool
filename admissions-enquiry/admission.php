<?php
// admission.php — RECEIVE frontend POST, SAVE to DB, optionally POST to Google Sheet
// This version ADDS sending a confirmation email to the user and returns "email_sent" in JSON.
// Replace your existing file with this one. Keep config.php (DB info) next to it.
// Top of admission.php — ensure nothing leaks before JSON output
ini_set('display_errors', 0);          // never show warnings to user
ini_set('log_errors', 1);              // still log errors
error_reporting(E_ALL);

ob_start();
// ---------- CONFIG ----------
$DEBUG_MODE = false; // set true for local debugging (shows PHP errors) — set false in production

// Google Sheet WebApp (kept here per your request)
$GSHEET_WEBAPP_URL    = 'https://script.google.com/macros/s/AKfycby9F6uBDycYQDlCY7v0HbXnqiS15HekXM0rR5nttyXNL2tbnGKPwtDs8kJ7VvAnDgFLAw/exec';
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';

// Email "From" address (set to a domain-verified email on your server)
$EMAIL_FROM_ADDRESS = 'socialmedia@msec.edu.in';
$EMAIL_FROM_NAME    = 'The Nest School';

// PHPMailer manual includes (files must exist in /phpmailer/)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

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
$page_url = safe_trim($input['page_url'] ?? '');
$area   = safe_trim($input['area'] ?? '');
$school = safe_trim($input['current_school'] ?? '');
$reason = safe_trim($input['reason_for_change'] ?? '');

// NEW: UTM / campaign values (do NOT store these in DB per request)
$utm_campaign_raw = safe_trim($input['utm_campaign_raw'] ?? $input['utm_campaign'] ?? '');
$campaign_name_in = safe_trim($input['campaign_name'] ?? '');

// Compose campaign value for sheet: prefer friendly campaign name, then raw utm, else filler
$campaign_for_sheet = $campaign_name_in ?: ($utm_campaign_raw ?: 'campaign not found');

dbg("Received POST: name=" . substr($name,0,80) . " email=" . substr($email,0,80) . " phone=" . substr($phone,0,40) . " grade=" . substr($grade,0,40) . " source=" . substr($source,0,80) . " campaign=" . substr($campaign_for_sheet,0,80) . " utm_raw=" . substr($utm_campaign_raw,0,80) . " page_url=" . substr($page_url,0,250));

// validation
$errors = [];
if ($name === '') $errors[] = 'Name is required';
if ($email === '' || !valid_email($email)) $errors[] = 'Valid email is required';
$phone_clean = clean_phone($phone);
if ($phone_clean === '' || strlen($phone_clean) < 7) $errors[] = 'Valid phone is required';
if ($grade === '' || $grade === 'Select Your Option') $errors[] = 'Please choose grade';
if ($source === '' || $source === 'Select Your Option') $errors[] = 'Please choose source';
if ($area === '') $errors[] = 'Area & City is required';

$gradeNumber = intval(preg_replace('/\D+/', '', $grade));
if ($gradeNumber >= 1) {
    if ($school === '') $errors[] = 'Current school is required';
    if ($reason === '') $errors[] = 'Reason for changing school is required';
}

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
  area VARCHAR(150) NOT NULL,
  grade VARCHAR(50) NOT NULL,
  source VARCHAR(100) NOT NULL,
  current_school VARCHAR(150),
  reason_for_change TEXT,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (!$mysqli->query($create_sql)) {
    dbg("Create table error: " . $mysqli->error);
    // continue — insert will likely fail if table truly missing
}

// ---------- insert record (DB does NOT store page_url / campaign) ----------
$stmt = $mysqli->prepare("
  INSERT INTO admissions
  (name, email, phone, area, grade, source, current_school, reason_for_change)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

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
$area_db   = mb_substr($area, 0, 150);
$school_db = mb_substr($school, 0, 150);
$reason_db = mb_substr($reason, 0, 1000);

$stmt->bind_param(
    'ssssssss',
    $name_db,
    $email_db,
    $phone_db,
    $area_db,
    $grade_db,
    $source_db,
    $school_db,
    $reason_db
);


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
// NOTE: we add page_url, utm_campaign_raw and campaign (friendly/raw or 'campaign not found')
// per your request, these values are sent to the sheet but NOT stored in DB.
$sheet_ok = false;
if (!empty($GSHEET_WEBAPP_URL) && !empty($GSHEET_SHARED_SECRET)) {
    $payload = [
        'secret' => $GSHEET_SHARED_SECRET,
        'id' => $insert_id,
        'name' => $name_db,
        'area' => $area_db,
        'email' => $email_db,
        'phone' => $phone_db,
        'grade' => $grade_db,
        'current_school' => $school_db,
        'reason_for_change' => $reason_db,
        'source' => $source_db,
        'page_url' => $page_url,
        'utm_campaign_raw' => $utm_campaign_raw,
        'campaign' => $campaign_for_sheet,
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

// ---------- SEND CONFIRMATION EMAIL TO USER (PHPMailer via Google Workspace SMTP) ----------
$email_sent = false;
$email_error = null;
if (!empty($email_db)) {
    $to = $email_db;
    $subject = "Admission Enquiry Received — Thank you, " . $name_db;

    // build a simple HTML email body
    $body = "<html><body>";
    $body .= "<p>Dear " . htmlspecialchars($name_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ",</p>";
    $body .= "<p>Thank you for your admission enquiry. We have received the following details:</p>";
    $body .= "<ul>";
    $body .= "<li><strong>Name:</strong> " . htmlspecialchars($name_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    $body .= "<li><strong>Grade of interest:</strong> " . htmlspecialchars($grade_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    $body .= "<li><strong>Area:</strong> " . htmlspecialchars($area_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    $body .= "<li><strong>Phone:</strong> " . htmlspecialchars($phone_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    $body .= "<li><strong>Source:</strong> " . htmlspecialchars($source_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    
    if (!empty($school_db)) {
        $body .= "<li><strong>Current School:</strong> " . htmlspecialchars($school_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    }
    if (!empty($reason_db)) {
        $body .= "<li><strong>Reason for Change:</strong> " . htmlspecialchars($reason_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    }
    
    $body .= "</ul>";
    $body .= "<p>Our admissions team will contact you shortly to discuss the next steps. If you need immediate assistance, please call us.</p>";
    $body .= "<p>Warm regards,<br>" . htmlspecialchars($EMAIL_FROM_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
    $body .= "</body></html>";
    if (!empty($school_db)) {
    $body .= "<li><strong>Current School:</strong> " . htmlspecialchars($school_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    }
    if (!empty($reason_db)) {
        $body .= "<li><strong>Reason for Change:</strong> " . htmlspecialchars($reason_db, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>";
    }

    // Attempt PHPMailer SMTP send
    try {
        $mail = new PHPMailer(true);

        // SMTP credentials — prefer constants from config.php
        // Add these lines in config.php:
        // define('SMTP_USER', 'socialmedia@msec.edu.in');
        // define('SMTP_PASS', 'paste-your-google-app-password-here');
        $smtp_user = defined('SMTP_USER') ? SMTP_USER : $EMAIL_FROM_ADDRESS;
        $smtp_pass = defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: '');

        // If SMTP_PASS not set, dbg and fall back to mail() as last resort (but warn)
        if (empty($smtp_pass)) {
            dbg("SMTP_PASS not set — PHPMailer will attempt without password (likely to fail). Please set SMTP_PASS in config.php.");
        }

        // SMTP server configuration (Google Workspace)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;   // SMTP username (full email)
        $mail->Password   = $smtp_pass;   // Google App Password (recommended) or SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 'tls'
        $mail->Port       = 587;

        // Optional: tighten TLS settings (useful on some hosts)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];

        // Message headers & body
        $mail->setFrom($EMAIL_FROM_ADDRESS, $EMAIL_FROM_NAME);
        $mail->addReplyTo($EMAIL_FROM_ADDRESS, $EMAIL_FROM_NAME);
        $mail->addAddress($email_db, $name_db);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>','<br/>','</p>','</li>'], "\n", $body));

        dbg("Attempting PHPMailer SMTP to $email_db via {$mail->Host} as {$mail->Username}");
        if ($mail->send()) {
            $email_sent = true;
            dbg("Confirmation email (SMTP) sent to $email_db for id={$insert_id}");
        } else {
            $email_sent = false;
            $email_error = 'PHPMailer send returned false';
            dbg("PHPMailer send returned false for $email_db id={$insert_id}");
        }
    } catch (Exception $e) {
        $email_sent = false;
        $email_error = 'PHPMailer Exception: ' . $mail->ErrorInfo;
        dbg("PHPMailer Exception for $email_db id={$insert_id} — " . $mail->ErrorInfo . " — " . $e->getMessage());
    }

    // If PHPMailer failed and there is no SMTP_PASS, you may still try PHP mail() as a fallback (optional).
    if (!$email_sent && empty($smtp_pass)) {
        // headers for mail()
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $from_header = sprintf('%s <%s>', $EMAIL_FROM_NAME, $EMAIL_FROM_ADDRESS);
        $headers[] = 'From: ' . $from_header;
        $headers[] = 'Reply-To: ' . $EMAIL_FROM_ADDRESS;

        dbg("Attempting fallback mail() to $to subject='$subject'");
        $mail_result = @mail($to, $subject, $body, implode("\r\n", $headers));
        if ($mail_result) {
            $email_sent = true;
            $email_error = 'PHPMailer failed but mail() succeeded (fallback)';
            dbg("Fallback mail() succeeded to $to for id={$insert_id}");
        } else {
            if ($email_error === null) $email_error = 'PHPMailer failed and mail() returned false';
            dbg("Fallback mail() FAILED to $to for id={$insert_id}");
        }
    }
}

// ---------- respond ----------
echo json_encode([
    'success' => true,
    'message' => 'Admission enquiry saved.',
    'id' => intval($insert_id),
    'sent_to_sheet' => $sheet_ok,
    'email_sent' => $email_sent,
    'email_error' => $email_error
]);
exit;
