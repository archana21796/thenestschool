<?php
// === DEBUG SETTINGS ===
ini_set('log_errors', 1); // enable logging
ini_set('error_log', __DIR__ . '/openhouse-error.log'); // error log file
error_reporting(E_ALL);

// Custom debug logger
function debug_log($msg) {
    error_log('[OPENHOUSE] ' . date('Y-m-d H:i:s') . ' - ' . $msg);
}

/**
 * Open Day Registration – Standalone
 * POST /openhouse/openhouse.php?action=register
 */

$DB_HOST = "localhost";
$DB_NAME = "theneo1n_testnestdb";
$DB_USER = "theneo1n_smmuser";
$DB_PASS = "Nest@2025";

const DB_TABLE = 'openday_enquiries';

const GSHEET_WEBHOOK = 'https://script.google.com/macros/s/AKfycbzrJck9iXKMlBwP5OPDl47Xokc5kgiFre6NhL0vt4xHRH0yFPJ6pPUlNeRjxnynN3Ki/exec';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');

$action = $_GET['action'] ?? 'register';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
    );
    debug_log("Database connected successfully");
} catch (Throwable $e) {
    debug_log("DB connection FAILED: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

function json_out($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_str($s) { return trim($s ?? ''); }

function get_body_params() {
    $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ctype, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    return $_POST;
}

/**
 * Try to extract utm_campaign value from a URL string.
 * Handles normal ?utm_campaign=, broken ?/utm_campaign=, &/utm_campaign=, #utm_campaign= etc.
 */
function extract_utm_from_url($url) {
    $url = (string)$url;
    // Look for ?utm_campaign= or ?/utm_campaign= or &utm_campaign= etc.
    if (preg_match('/[?&#]\/?utm_campaign=([^&#]+)/i', $url, $m)) {
        return urldecode($m[1]);
    }
    // fallback to utm= or campaign=
    if (preg_match('/[?&#]\/?(?:utm|campaign)=([^&#]+)/i', $url, $m2)) {
        return urldecode($m2[1]);
    }
    return '';
}

if ($action === 'register') {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['success' => false, 'message' => 'Use POST'], 405);
    }

    $p = get_body_params();

    $parent_name     = clean_str($p['parent_name'] ?? '');
    $parent_email    = filter_var(($p['parent_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? $p['parent_email'] : '';
    $parent_phone    = preg_replace('/\D+/', '', ($p['parent_phone'] ?? ''));
    $student_name    = clean_str($p['student_name'] ?? '');
    $grade           = clean_str($p['grade'] ?? '');
    $preferred_date  = clean_str($p['preferred_date'] ?? '');
    $page_url        = clean_str($p['pageUrl'] ?? $p['page_url'] ?? '');
    $submitted_at    = date('Y-m-d H:i:s');

    // ---------- UTM / Campaign handling (DO NOT STORE IN DB) ----------
    // Accept common incoming keys: utm_campaign_raw, utm_campaign, utm, campaign_name, campaign
    $utm_campaign_raw_in = clean_str($p['utm_campaign_raw'] ?? $p['utm_campaign'] ?? $p['utm'] ?? '');
    $campaign_name_in    = clean_str($p['campaign_name'] ?? $p['campaign'] ?? $p['campaignName'] ?? '');

    // If both are empty, try to parse from page_url (handles broken ?/utm_campaign=)
    if ($utm_campaign_raw_in === '' && $campaign_name_in === '' && $page_url !== '') {
        $extracted = extract_utm_from_url($page_url);
        if ($extracted !== '') {
            $utm_campaign_raw_in = $extracted;
            debug_log("Extracted utm_campaign from page_url: $extracted");
        }
    }

    // Final campaign value for sheet: prefer friendly campaign_name, else raw utm, else fallback text
    $campaign_for_sheet = $campaign_name_in ?: ($utm_campaign_raw_in ?: 'campaign not found');

    // Validation
    $errors = [];
    if ($parent_name === '') $errors['parent_name'] = 'Parent name is required.';
    if ($parent_email === '') $errors['parent_email'] = 'Valid email is required.';
    if (!preg_match('/^[0-9]{10}$/', $parent_phone)) $errors['parent_phone'] = 'Phone must be 10 digits.';
    if ($student_name === '') $errors['student_name'] = 'Student name is required.';
    if ($grade === '') $errors['grade'] = 'Grade is required.';
    if ($preferred_date === '') $errors['preferred_date'] = 'Preferred date is required.';

    if ($errors) {
        debug_log("Validation FAILED: " . json_encode($errors));
        json_out(['success' => false, 'errors' => $errors], 422);
    }

    // DB insert (unchanged — DO NOT add campaign/page_url here per request)
    $sql = "INSERT INTO ".DB_TABLE."
            (parent_name, parent_email, parent_phone, student_name, grade,
             preferred_date, page_url, submitted_at)
            VALUES (:parent_name,:parent_email,:parent_phone,:student_name,:grade,
                    :preferred_date,:page_url,:submitted_at)";

    $stmt = $pdo->prepare($sql);

    $ok = $stmt->execute([
        ':parent_name'     => $parent_name,
        ':parent_email'    => $parent_email,
        ':parent_phone'    => $parent_phone,
        ':student_name'    => $student_name,
        ':grade'           => $grade,
        ':preferred_date'  => $preferred_date,
        ':page_url'        => $page_url,
        ':submitted_at'    => $submitted_at,
    ]);

    if (!$ok) {
        debug_log("DB INSERT FAILED for $parent_email");
        json_out(['success' => false, 'message' => 'Database insert failed'], 500);
    }

    $id = (int)$pdo->lastInsertId();
    debug_log("DB insert OK. ID=$id, Email=$parent_email");

    // ========== GOOGLE SHEET WEBHOOK ==========
    // Include utm_campaign_raw and campaign_for_sheet in the webhook payload
    $payload = [
        'id'                => $id,
        'parent_name'       => $parent_name,
        'parent_email'      => $parent_email,
        'parent_phone'      => $parent_phone,
        'student_name'      => $student_name,
        'grade'             => $grade,
        'preferred_date'    => $preferred_date,
        'pageUrl'           => $page_url,
        'utm_campaign_raw'  => $utm_campaign_raw_in,
        'campaign'          => $campaign_for_sheet,
        'submitted_at'      => $submitted_at,
    ];

    debug_log("Sending to Google Sheet: " . json_encode($payload));

    $ch = curl_init(GSHEET_WEBHOOK);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10
    ]);
    $gsheetResponse = curl_exec($ch);

    if ($gsheetResponse === false) {
        debug_log("Google Sheet FAILED: " . curl_error($ch));
    } else {
        debug_log("Google Sheet RESPONSE: $gsheetResponse");
    }

    curl_close($ch);

    // ========== EMAIL SEND ==========
    $subject = "Open Day Registration Confirmation - The Nest School";
    $message = "
Hello $parent_name,

Thank you for registering for Open Day at The Nest School.

Here are your details:
---------------------------------------
Parent Name: $parent_name
Student Name: $student_name
Grade: $grade
Preferred Date: $preferred_date
---------------------------------------

Warm regards,
The Nest School";

    $headers = "From: The Nest School <socialmedia@msec.edu.in>\r\n";

    debug_log("Attempting mail() to $parent_email");

    $emailSent = @mail($parent_email, $subject, $message, $headers);

    if (!$emailSent) {
        $phpError = error_get_last();
        debug_log("MAIL FAILED for $parent_email — PHP: " . ($phpError['message'] ?? 'unknown'));

        json_out([
            'success'    => false,
            'message'    => 'Email sending failed',
            'mail_error' => $phpError['message'] ?? null
        ], 500);
    }

    debug_log("MAIL SUCCESS: $parent_email (ID=$id)");

    json_out(['success' => true, 'message' => 'Registration completed successfully', 'id' => $id]);
}

// fallback
debug_log("Unknown action called");
json_out(['success' => false, 'message' => 'Unknown action'], 400);
