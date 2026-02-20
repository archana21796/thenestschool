<?php
// submit-form.php — Save to DB + Send to Google Sheet + Send Routed Emails
declare(strict_types=1);

// ================= CONFIG =================
$servername = "localhost";
$username   = "theneo1n_smmuser";
$password   = "Nest@2025";
$database   = "theneo1n_testnestdb";

$GSHEET_WEBAPP_URL    = 'https://script.google.com/macros/s/AKfycbzazpm3_hj6LFcKpx8ZLiQC9NkQ51DumQtCgLAKmlETCJhBR8HDmfMcTETI6MC-Syiv/exec';
$GSHEET_SHARED_SECRET = 'MyVerySecureSecret2025!';
$DEBUG_LOG            = __DIR__ . '/contact_debug.log';

function dbg($msg) {
  global $DEBUG_LOG;
  @file_put_contents($DEBUG_LOG, '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND | LOCK_EX);
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
  exit;
}

// ================= READ INPUT =================
$input = $_POST;
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $json = json_decode($raw, true);
  if (is_array($json)) $input = array_merge($input, $json);
}

$clean = fn($v) => trim(filter_var((string)($v ?? ''), FILTER_UNSAFE_RAW));

$name    = $clean($input['name'] ?? '');
$email   = $clean($input['email'] ?? '');
$phone   = $clean($input['phone'] ?? '');
$query   = $clean($input['query'] ?? '');
$message = $clean($input['message'] ?? '');
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';

// ================= VALIDATION =================
$errors = [];
if ($name === '') $errors[] = 'Name required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
if ($phone === '') $errors[] = 'Phone required';

if ($errors) {
  http_response_code(422);
  echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
  exit;
}

// ================= MAIL ROUTING =================
$deptEmail = 'smm@msec.edu.in';
$deptName  = 'Support Team';

switch (strtolower($query)) {
  case 'visit campus':
  case 'admissions':
    $deptEmail = 'admissions@thenest.school';
    $deptName  = 'Admissions Team';
    break;

  case 'careers':
    $deptEmail = 'careers@thenest.school';
    $deptName  = 'Careers Team';
    break;
}

// ================= DB CONNECTION =================
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
  dbg("DB error: " . $conn->connect_error);
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'DB connection failed']);
  exit;
}
$conn->set_charset('utf8mb4');

// ================= ENSURE TABLE =================
$conn->query("
CREATE TABLE IF NOT EXISTS contact_enquiries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(50),
  query_type VARCHAR(100),
  message TEXT,
  ip_address VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ================= INSERT =================
$stmt = $conn->prepare("
INSERT INTO contact_enquiries (name,email,phone,query_type,message,ip_address)
VALUES (?,?,?,?,?,?)
");
$stmt->bind_param("ssssss", $name, $email, $phone, $query, $message, $ip);

$ok = $stmt->execute();
$inserted_id = $conn->insert_id;
$stmt->close();
$conn->close();

if (!$ok) {
  dbg("Insert failed");
  echo json_encode(['success' => false, 'message' => 'Could not save to DB']);
  exit;
}

// ================= SEND DEPARTMENT MAIL =================
$subjectDept = "New Enquiry – {$query}";
$bodyDept = "
New enquiry received:

Name: {$name}
Email: {$email}
Phone: {$phone}
Query Type: {$query}
Message: {$message}
";

@mail(
  $deptEmail,
  $subjectDept,
  $bodyDept,
  "From: Website Enquiry <socialmedia@msec.edu.in>\r\nReply-To: {$email}"
);

// ================= SEND USER CONFIRMATION =================
switch (strtolower($query)) {
  case 'visit campus':
    $userText = "Thank you for registering to visit our campus. Our Admissions team will contact you shortly.";
    break;
  case 'admissions':
    $userText = "Thank you for your admission enquiry. Our Admissions team will reach out soon.";
    break;
  case 'careers':
    $userText = "Thank you for your interest in careers with us. Our HR team will contact you.";
    break;
  default:
    $userText = "Thank you for contacting us. Our team will get back to you shortly.";
}

@mail(
  $email,
  "We received your enquiry",
  "Dear {$name},\n\n{$userText}\n\nRegards,\n{$deptName}",
  "From: The NEST School <admissions@thenest.school>"
);

// ================= GOOGLE SHEET PUSH =================
$payload = json_encode([
  'sheet' => 'contact_enquiries',
  'registration_id' => $inserted_id,
  'name' => $name,
  'email' => $email,
  'phone' => $phone,
  'query' => $query,
  'message' => $message,
  'ip' => $ip,
  'received_at' => date('Y-m-d H:i:s'),
  'secret' => $GSHEET_SHARED_SECRET
]);

$ch = curl_init($GSHEET_WEBAPP_URL);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_TIMEOUT => 30
]);
curl_exec($ch);
curl_close($ch);

// ================= RESPONSE =================
echo json_encode([
  'success' => true,
  'message' => 'Form submitted successfully'
]);
