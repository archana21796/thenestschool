<?php
/**
 * NESTival Registration — Standalone PHP
 * ---------------------------------------
 * Accepts registration payloads (JSON or form POST),
 * saves to MySQL, pushes to Google Sheets,
 * sends confirmation email (HTML with participants per event).
 *
 * Author: Archana / Adapted by ChatGPT
 * Version: 1.10 (Standalone)
 */

// ======================= CONFIG =======================
$DB_HOST = "localhost";
$DB_NAME = "YOUR_DB_NAME";
$DB_USER = "YOUR_DB_USER";
$DB_PASS = "YOUR_DB_PASS";
$DB_REG_TABLE = "nestival_registrations";
$DB_PART_TABLE = "nestival_participants";

$GOOGLE_SHEET_URL = "https://script.google.com/macros/s/AKfycbwJmrOrZHIhucsP5vEhBydLgVgsV-mdSeSNgrARKNGRyheRd4ZgC9T3JJ4HfeMLi51s/exec";

$ADMIN_EMAIL = "info@rankraze.com";
$FROM_EMAIL = "no-reply@thenest.school";
$FROM_NAME = "NESTival Team";

// ======================= HELPERS =======================
function respond($data, $code = 200) {
    http_response_code($code);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
    exit;
}

function clean($v) { return htmlspecialchars(trim((string)$v)); }

// ======================= MAIN =======================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(["success" => false, "message" => "Invalid method"], 405);
}

// Parse JSON or form data
$raw = file_get_contents("php://input");
$data = [];
if (stripos($_SERVER["CONTENT_TYPE"] ?? "", "application/json") !== false) {
    $data = json_decode($raw, true) ?: [];
} else {
    $data = $_POST;
}

// ---------- Setup DB ----------
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    respond(["success" => false, "message" => "DB connection failed", "error" => $e->getMessage()], 500);
}

// ---------- Extract Fields ----------
$regType = strtolower($data["registrationType"] ?? "school");
$school = [
    "schoolName" => clean($data["schoolName"] ?? ""),
    "faculty" => clean($data["faculty"] ?? ""),
    "phone" => clean($data["phone"] ?? ""),
    "schoolEmail" => clean($data["schoolEmail"] ?? ""),
];
$individual = [
    "participantName" => clean($data["participantName"] ?? ""),
    "parentName" => clean($data["parentName"] ?? ""),
    "parentPhone" => clean($data["parentPhone"] ?? ""),
    "parentEmail" => clean($data["parentEmail"] ?? ""),
    "schoolName" => clean($data["individual_schoolName"] ?? ""),
];
$howHeard = clean($data["howDidYouHear"] ?? $data["how_heard"] ?? "");

$competitions = [];
if (isset($data["competitions"])) {
    $competitions = is_string($data["competitions"]) ? json_decode($data["competitions"], true) : $data["competitions"];
}
$students = [];
if (isset($data["students"])) {
    $students = is_string($data["students"]) ? json_decode($data["students"], true) : $data["students"];
}

// ---------- Validation ----------
if ($regType === "school") {
    if (!$school["schoolName"] || !$school["faculty"] || !$school["phone"] || !$school["schoolEmail"]) {
        respond(["success" => false, "message" => "Missing school fields"], 400);
    }
} else {
    if (!$individual["parentName"] || !$individual["parentPhone"] || !$individual["parentEmail"]) {
        respond(["success" => false, "message" => "Missing parent fields"], 400);
    }
}

// ---------- Insert Registration ----------
try {
    $stmt = $pdo->prepare("INSERT INTO $DB_REG_TABLE
        (school_name, faculty_incharge, phone, school_email, extra, how_heard, created_at)
        VALUES (:sn, :fac, :ph, :em, :ex, :how, NOW())");
    $extra = json_encode(["students" => $students, "competitions" => $competitions, "how_heard" => $howHeard]);
    $stmt->execute([
        ":sn" => $regType === "individual" ? $individual["schoolName"] : $school["schoolName"],
        ":fac" => $regType === "individual" ? $individual["parentName"] : $school["faculty"],
        ":ph" => $regType === "individual" ? $individual["parentPhone"] : $school["phone"],
        ":em" => $regType === "individual" ? $individual["parentEmail"] : $school["schoolEmail"],
        ":ex" => $extra,
        ":how" => $howHeard
    ]);
    $regId = $pdo->lastInsertId();
} catch (Exception $e) {
    respond(["success" => false, "message" => "DB insert failed", "error" => $e->getMessage()], 500);
}

// ---------- Insert Participants ----------
try {
    $stmt = $pdo->prepare("INSERT INTO $DB_PART_TABLE
        (registration_id, competition_id, competition_title, mode, participant_index, student_name, grade, email, created_at)
        VALUES (:rid,:cid,:title,:mode,:idx,:name,:grade,:email,NOW())");

    foreach ($competitions as $comp) {
        $cid = clean($comp["id"] ?? "");
        $ctitle = clean($comp["title"] ?? "");
        $cmode = clean($comp["mode"] ?? "");
        $i = 1;
        foreach ($comp["participants"] ?? [] as $p) {
            $stmt->execute([
                ":rid" => $regId,
                ":cid" => $cid,
                ":title" => $ctitle,
                ":mode" => $cmode,
                ":idx" => $i++,
                ":name" => clean($p["name"] ?? ""),
                ":grade" => clean($p["grade"] ?? ""),
                ":email" => clean($p["email"] ?? "")
            ]);
        }
    }
} catch (Exception $e) {
    error_log("Nestival participants insert failed: " . $e->getMessage());
}

// ---------- Confirmation Email ----------
$to = $regType === "individual" ? $individual["parentEmail"] : $school["schoolEmail"];
if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $subject = "NESTival Registration Confirmation — ID #$regId";
    $body = "<h3>Thank you for registering for NESTival!</h3>
    <p><strong>Registration ID:</strong> $regId</p>
    <p><strong>School/Contact:</strong> " . ($school["schoolName"] ?: $individual["schoolName"]) . "</p>
    <p><strong>How did you hear about us:</strong> " . ($howHeard ?: "Not specified") . "</p>
    <hr><h4>Registered Events</h4>";

    foreach ($competitions as $comp) {
        $body .= "<p><strong>" . clean($comp["title"] ?? "Untitled Event") . "</strong> (" . clean($comp["mode"] ?? "") . ")<br>";
        $parts = [];
        foreach ($comp["participants"] ?? [] as $p) {
            $parts[] = clean($p["name"]) . " (Grade " . clean($p["grade"]) . ")";
        }
        $body .= implode(", ", $parts) . "</p>";
    }

    $body .= "<hr><p>For queries, reply to this email.</p><p>– NESTival Team</p>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $FROM_NAME <$FROM_EMAIL>\r\n";

    @mail($to, $subject, $body, $headers);
}

// ---------- Push to Google Sheets ----------
try {
    $payload = [
        "registration_id" => $regId,
        "registration_type" => $regType,
        "school" => $school,
        "individual" => $individual,
        "students" => $students,
        "competitions" => $competitions,
        "how_heard" => $howHeard,
        "received_at" => date("Y-m-d H:i:s")
    ];

    $ch = curl_init($GOOGLE_SHEET_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http < 200 || $http >= 300) {
        error_log("Nestival: Google Sheet failed HTTP $http: $resp");
    }
    curl_close($ch);
} catch (Exception $e) {
    error_log("Nestival: Google Sheet exception: " . $e->getMessage());
}

// ---------- Final Response ----------
respond(["success" => true, "registration_id" => $regId]);
