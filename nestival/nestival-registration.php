<?php
/**
 * NESTival Registration — Standalone PHP (NO SMTP, with logging)
 * - Saves to MySQL
 * - Sends confirmation email via sendmail (fallback to mail())
 * - Pushes to Google Sheets (with short timeouts)
 * - Writes detailed logs to /nestival/mail_debug.log
 */

/* ---------------- DEBUG LOGGING (enable) ---------------- */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/mail_debug.log');

function log_msg(string $msg): void {
  error_log('[NESTival] ' . $msg);
}

/* ---------------- CONFIG ---------------- */
$DB_HOST = "localhost";
$DB_NAME = "theneo1n_testnestdb";
$DB_USER = "theneo1n_smmuser";
$DB_PASS = "Nest@2025";
$DB_REG_TABLE  = "nestival_registrations";
$DB_PART_TABLE = "nestival_participants";

$GOOGLE_SHEET_URL = "https://script.google.com/macros/s/AKfycbwJmrOrZHIhucsP5vEhBydLgVgsV-mdSeSNgrARKNGRyheRd4ZgC9T3JJ4HfeMLi51s/exec";

$FROM_EMAIL  = "events@thenest.school";
$FROM_NAME   = "NESTival Team";
$CC_EMAIL    = "smm@msec.edu.in"; // optional CC

/* ---------------- HELPERS ---------------- */
function respond($data, $code = 200) {
  http_response_code($code);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function clean($v) { return htmlspecialchars(trim((string)$v)); }

/** Try to detect a sendmail binary path (common on cPanel) */
function detect_sendmail_path(): ?string {
  $candidates = [
    '/usr/sbin/sendmail -t -i',
    '/usr/lib/sendmail -t -i',
    '/usr/sbin/exim -t -i',
  ];
  foreach ($candidates as $cmd) {
    $bin = strtok($cmd, ' ');
    if (@is_executable($bin)) return $cmd;
  }
  return null;
}

/* ---------------- MAIN ---------------- */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  respond(["success" => false, "message" => "Invalid method"], 405);
}

/* Parse JSON or form data */
$raw = file_get_contents("php://input");
$data = [];
if (stripos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") !== false) {
  $data = json_decode($raw, true) ?: [];
} else {
  $data = $_POST;
}

/* ---------- Setup DB ---------- */
try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER, $DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Throwable $e) {
  log_msg("DB connection failed: " . $e->getMessage());
  respond(["success" => false, "message" => "DB connection failed"], 500);
}

/* ---------- Extract Fields ---------- */
$regType = strtolower($data["registrationType"] ?? "school");

$school = [
  "schoolName"  => clean($data["schoolName"] ?? ""),
  "faculty"     => clean($data["faculty"] ?? ""),
  "phone"       => clean($data["phone"] ?? ""),
  "schoolEmail" => clean($data["schoolEmail"] ?? ""),
];

$individual = [
  "participantName" => clean($data["participantName"] ?? ""),
  "parentName"      => clean($data["parentName"] ?? ""),
  "parentPhone"     => clean($data["parentPhone"] ?? ""),
  "parentEmail"     => clean($data["parentEmail"] ?? ""),
  // Accept both keys from the frontend
  "schoolName"      => clean($data["individual_schoolName"] ?? $data["schoolName"] ?? ""),
];

$howHeard = clean($data["howDidYouHear"] ?? $data["how_heard"] ?? "");

/* competitions / students */
$competitions = [];
if (isset($data["competitions"])) {
  $competitions = is_string($data["competitions"])
    ? (json_decode($data["competitions"], true) ?: [])
    : $data["competitions"];
}
$students = [];
if (isset($data["students"])) {
  $students = is_string($data["students"])
    ? (json_decode($data["students"], true) ?: [])
    : $data["students"];
}

/* ---------- Validation ---------- */
if ($regType === "school") {
  if (!$school["schoolName"] || !$school["faculty"] || !$school["phone"] || !$school["schoolEmail"]) {
    respond(["success" => false, "message" => "Missing school fields"], 400);
  }
} else {
  if (!$individual["parentName"] || !$individual["parentPhone"] || !$individual["parentEmail"]) {
    respond(["success" => false, "message" => "Missing parent fields"], 400);
  }
}

/* ---------- Insert Registration ---------- */
try {
  $stmt = $pdo->prepare("INSERT INTO $DB_REG_TABLE
    (school_name, faculty_incharge, phone, school_email, extra, how_heard, created_at)
    VALUES (:sn, :fac, :ph, :em, :ex, :how, NOW())");

  $extra = json_encode([
    "students"      => $students,
    "competitions"  => $competitions,
    "how_heard"     => $howHeard
  ], JSON_UNESCAPED_UNICODE);

  $stmt->execute([
    ":sn"  => $regType === "individual" ? $individual["schoolName"]  : $school["schoolName"],
    ":fac" => $regType === "individual" ? $individual["parentName"]  : $school["faculty"],
    ":ph"  => $regType === "individual" ? $individual["parentPhone"] : $school["phone"],
    ":em"  => $regType === "individual" ? $individual["parentEmail"] : $school["schoolEmail"],
    ":ex"  => $extra,
    ":how" => $howHeard
  ]);
  $regId = (int)$pdo->lastInsertId();
  log_msg("Registration inserted id=$regId");
} catch (Throwable $e) {
  log_msg("DB insert failed: " . $e->getMessage());
  respond(["success" => false, "message" => "DB insert failed"], 500);
}

/* ---------- Insert Participants ---------- */
try {
  $stmt = $pdo->prepare("INSERT INTO $DB_PART_TABLE
    (registration_id, competition_id, competition_title, mode, participant_index, student_name, grade, email, created_at)
    VALUES (:rid,:cid,:title,:mode,:idx,:name,:grade,:email,NOW())");

  foreach ($competitions as $comp) {
    $cid    = clean($comp["id"] ?? "");
    $ctitle = clean($comp["title"] ?? "");
    $cmode  = clean($comp["mode"] ?? "");
    $i = 1;
    foreach (($comp["participants"] ?? []) as $p) {
      $stmt->execute([
        ":rid"   => $regId,
        ":cid"   => $cid,
        ":title" => $ctitle,
        ":mode"  => $cmode,
        ":idx"   => $i++,
        ":name"  => clean($p["name"] ?? ""),
        ":grade" => clean($p["grade"] ?? ""),
        ":email" => clean($p["email"] ?? "")
      ]);
    }
  }
  log_msg("Participants inserted for reg id=$regId");
} catch (Throwable $e) {
  log_msg("Participants insert failed: " . $e->getMessage());
}

/* ---------- Confirmation Email (Sendmail → mail() with debug) ---------- */
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$to = ($regType === "individual") ? $individual["parentEmail"] : $school["schoolEmail"];
if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
  $subject = "NESTival Registration Confirmation — ID #{$regId}";

  $body  = "<h3>Thank you for registering for NESTival!</h3>";
  $body .= "<p><strong>Registration ID:</strong> {$regId}</p>";
  $body .= "<p><strong>School/Contact:</strong> " . ($school["schoolName"] ?: $individual["schoolName"]) . "</p>";
  $body .= "<p><strong>How did you hear about us:</strong> " . ($howHeard ?: "Not specified") . "</p>";
  $body .= "<hr><h4>Registered Events</h4>";

  foreach ($competitions as $comp) {
    $title = clean($comp["title"] ?? "Untitled Event");
    $mode  = clean($comp["mode"] ?? "");
    $body .= "<p><strong>{$title}</strong>" . ($mode ? " ({$mode})" : "") . "<br>";
    $parts = [];
    foreach (($comp["participants"] ?? []) as $p) {
      $nm = clean($p["name"] ?? "");
      $gr = clean($p["grade"] ?? "");
      if ($nm !== "") $parts[] = $gr ? "{$nm} (Grade {$gr})" : $nm;
    }
    $body .= htmlspecialchars(implode(", ", $parts)) . "</p>";
  }
  $body .= "<hr><p>For queries, reply to this email.</p><p>– NESTival Team</p>";

  $sent = false;

  // 1) Try local sendmail first
  try {
    $m1 = new PHPMailer(true);
    $m1->isSendmail();

    // ✅ UTF-8 + AltBody for clean rendering and deliverability
    $m1->CharSet  = 'UTF-8';
    $m1->Encoding = 'base64';
    $m1->AltBody  = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $body));

    // Log sendmail debug
    $m1->SMTPDebug = 3; // 1=errors, 2=info, 3=full
    $m1->Debugoutput = function($str, $level) {
      error_log("PHPMailer Sendmail Debug: [$level] $str");
    };

    // Auto-detect sendmail path (cPanel)
    $detected = detect_sendmail_path();
    if ($detected) {
      $m1->Sendmail = $detected;
      log_msg("Using sendmail path: $detected");
    } else {
      log_msg("Sendmail path not auto-detected; PHPMailer default will be used.");
    }

    $m1->setFrom($FROM_EMAIL, $FROM_NAME);
    $m1->addReplyTo($FROM_EMAIL, $FROM_NAME);
    $m1->addAddress($to);
    if (!empty($CC_EMAIL)) $m1->addCC($CC_EMAIL);

    $m1->isHTML(true);
    $m1->Subject = $subject;
    $m1->Body    = $body;

    $m1->Timeout = 8; // safety
    $m1->send();
    $sent = true;
    log_msg("Sendmail: sent to $to (reg #$regId)");
  } catch (Exception $e) {
    log_msg('Sendmail PHPMailer error: ' . $e->getMessage());
  }

  // 2) Fallback: PHP mail()
  if (!$sent) {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
    if (!empty($CC_EMAIL)) $headers .= "Cc: {$CC_EMAIL}\r\n";

    $ok = @mail($to, $subject, $body, $headers, "-f{$FROM_EMAIL}");
    if ($ok) {
      log_msg("PHP mail(): sent to $to (reg #$regId)");
    } else {
      log_msg("PHP mail(): FAILED to send to $to (reg #$regId)");
    }
  }
} else {
  log_msg("Invalid recipient email, skipping send. to=" . ($to ?? 'NULL'));
}

/* ---------- Push to Google Sheets (short timeout + follow 302) ---------- */
try {
  // ✅ FLATTEN fields so the Apps Script can write common columns
  if ($regType === 'individual') {
    $flatSchoolName = $individual['schoolName'];
    $flatFaculty    = $individual['parentName'];
    $flatPhone      = $individual['parentPhone'];
    $flatEmail      = $individual['parentEmail'];
  } else {
    $flatSchoolName = $school['schoolName'];
    $flatFaculty    = $school['faculty'];
    $flatPhone      = $school['phone'];
    $flatEmail      = $school['schoolEmail'];
  }

  $payload = [
    "registration_id"   => $regId,
    "registration_type" => $regType,

    // keep nested objects if you need them in the sheet
    "school"            => $school,
    "individual"        => $individual,
    "students"          => $students,
    "competitions"      => $competitions,
    "how_heard"         => $howHeard,
    "received_at"       => date("Y-m-d H:i:s"),

    // ✅ Flat columns (for your GAS to map directly)
    "schoolName"        => $flatSchoolName,
    "faculty"           => $flatFaculty,
    "phone"             => $flatPhone,
    "schoolEmail"       => $flatEmail,

    // Also include explicit parent fields, if you want to display them in the sheet
    "parentName"        => $individual['parentName'] ?? '',
    "parentPhone"       => $individual['parentPhone'] ?? '',
    "parentEmail"       => $individual['parentEmail'] ?? '',
  ];

  $ch = curl_init($GOOGLE_SHEET_URL);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true, // follow 302 to googleusercontent.com
  ]);
  $resp = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($http < 200 || $http >= 300) {
    log_msg("Google Sheet push HTTP $http, body: " . substr((string)$resp,0,500));
  } else {
    log_msg("Google Sheet push OK: HTTP $http");
  }
  curl_close($ch);
} catch (Throwable $e) {
  log_msg("Google Sheet push exception: " . $e->getMessage());
}

/* ---------- Final Response ---------- */
respond(["success" => true, "registration_id" => $regId]);
