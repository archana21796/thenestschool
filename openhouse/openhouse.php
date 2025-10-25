<?php
/**
 * Open Day Registration – Standalone (register-only)
 * POST /openday.php?action=register
 *
 * - Saves to MySQL (same table as WP if you set DB_TABLE = 'wp_openday_enquiries')
 * - Posts the same JSON payload to the same Google Apps Script webhook
 */

// DB connection (use the DB that contains your new table)
const DB_HOST  = 'localhost';
const DB_NAME  = 'theneo1n_testnestdb';   // <-- change to the DB shown in phpMyAdmin
const DB_USER  = 'YOUR_DB_USER';          // <-- user with INSERT privileges on that DB
const DB_PASS  = 'YOUR_DB_PASSWORD';

// Use your new table (no wp_ prefix)
const DB_TABLE = 'openday_enquiries';     // <-- EXACT table name you created


// SAME Google Apps Script Web App URL (from your plugin):
const GSHEET_WEBHOOK = 'https://script.google.com/macros/s/AKfycbxEQ9LjNgLpv8INcu_23aFedFkgqFxRGfbX6oYZGUOx3_MX3YAvOf4PVez_C8tph21X/exec';
/* ================================================== */

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');

$action = $_GET['action'] ?? 'register';

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

function json_out($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function clean_str(?string $s): string { return trim((string)($s ?? '')); }
function get_body_params(): array {
    $ctype = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($ctype, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST; // form post
}

if ($action === 'register') {
    // CORS for front-ends; restrict to your domain if needed
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['success' => false, 'message' => 'Use POST'], 405);
    }

    $p = get_body_params();

    $parent_name   = clean_str($p['parent_name'] ?? '');
    $parent_email  = filter_var(($p['parent_email'] ?? ''), FILTER_VALIDATE_EMAIL) ? $p['parent_email'] : '';
    $parent_phone  = preg_replace('/\D+/', '', ($p['parent_phone'] ?? '')); // keep digits
    $student_name  = clean_str($p['student_name'] ?? '');
    $grade         = clean_str($p['grade'] ?? '');
    $willing       = clean_str($p['willing_to_attend'] ?? ''); // Yes/No/Maybe
    $attendees     = (int)($p['attendees_count'] ?? 1);
    $page_url      = clean_str($p['pageUrl'] ?? '');
    $submitted_at  = date('Y-m-d H:i:s');

    // Validation (same logic as plugin)
    $errors = [];
    if ($parent_name === '') $errors['parent_name'] = 'Parent name is required.';
    if ($parent_email === '') $errors['parent_email'] = 'Valid parent email is required.';
    if ($parent_phone === '' || !preg_match('/^[0-9]{10}$/', $parent_phone)) $errors['parent_phone'] = 'Phone must be exactly 10 digits.';
    if ($student_name === '') $errors['student_name'] = 'Student name is required.';
    if ($grade === '') $errors['grade'] = 'Grade is required.';
    if ($willing === '' || !in_array(strtolower($willing), ['yes','no','maybe'], true)) $errors['willing_to_attend'] = 'Allowed: Yes / No / Maybe.';
    if ($attendees < 1) $errors['attendees_count'] = 'Attendees must be at least 1.';

    if ($errors) json_out(['success' => false, 'errors' => $errors], 422);

    // Insert row
    $sql = "INSERT INTO ".DB_TABLE."
            (parent_name, parent_email, parent_phone, student_name, grade,
             willing_to_attend, attendees_count, page_url, submitted_at)
            VALUES (:parent_name,:parent_email,:parent_phone,:student_name,:grade,
                    :willing,:attendees,:page_url,:submitted_at)";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':parent_name'  => $parent_name,
        ':parent_email' => $parent_email,
        ':parent_phone' => $parent_phone,
        ':student_name' => $student_name,
        ':grade'        => $grade,
        ':willing'      => ucfirst(strtolower($willing)),
        ':attendees'    => $attendees,
        ':page_url'     => $page_url ?: null,
        ':submitted_at' => $submitted_at,
    ]);
    if (!$ok) json_out(['success' => false, 'message' => 'Could not save'], 500);

    $id = (int)$pdo->lastInsertId();

    // Post to the SAME Google Apps Script webhook with the SAME payload
    if (GSHEET_WEBHOOK) {
        $payload = [
            'id'                => $id,
            'parent_name'       => $parent_name,
            'parent_email'      => $parent_email,
            'parent_phone'      => $parent_phone,
            'student_name'      => $student_name,
            'grade'             => $grade,
            'willing_to_attend' => ucfirst(strtolower($willing)),
            'attendees_count'   => $attendees,
            'pageUrl'           => $page_url,
            'submitted_at'      => $submitted_at,
        ];
        $ch = curl_init(GSHEET_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        // Optionally log $resp or curl_error($ch)
        curl_close($ch);
    }

    json_out(['success' => true, 'message' => 'Registration saved', 'id' => $id]);
}

// Anything else → 404-ish JSON
json_out(['success' => false, 'message' => 'Unknown action'], 400);
