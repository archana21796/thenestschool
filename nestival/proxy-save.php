<?php
// proxy-save.php
// Accepts application/x-www-form-urlencoded POST and forwards JSON to the WP REST endpoint.
// Put this file in public_html/nestival/proxy-save.php

// === CONFIG ===
// The secret is added server-side so clients don't need it (but this is still for testing).
$NESTIVAL_SHARED_SECRET = 'b7fN$8vTq9!sX3uRz6YwL#1pKdV2mH0gFQe4CjZr5UaBtP';

// REST endpoint to forward to
$rest_url = 'https://thenest.school/wp-json/nestival/v1/register';

// optional logging (will append; ensure nestival folder writable)
$log_file = __DIR__ . '/proxy-save.log';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read form fields from $_POST
// Expected simple fields:
// schoolName, faculty, phone, schoolEmail
// competitionId, competitionTitle, mode
// participant1, participant2, ... where value format is "Name|Grade|Email"
// Alternatively you can send multiple competition blocks by repeating keys with suffixes.
$schoolName  = trim($_POST['schoolName'] ?? '');
$faculty     = trim($_POST['faculty'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$schoolEmail = trim($_POST['schoolEmail'] ?? '');

// Build competitions array: detect competition keys; if you supplied single competition use competitionId/title/mode
$competitions = [];

// If competitionId exists, make one competition
if (!empty($_POST['competitionId']) || !empty($_POST['competitionTitle'])) {
    $comp = [
        'id' => $_POST['competitionId'] ?? 'unknown_comp',
        'title' => $_POST['competitionTitle'] ?? 'Untitled Competition',
        'mode' => $_POST['mode'] ?? 'solo',
        'participants' => []
    ];

    // Find participantN keys (participant1, participant2, ...)
    foreach ($_POST as $k => $v) {
        if (preg_match('/^participant(\d+)$/', $k)) {
            $parts = explode('|', $v);
            $name = trim($parts[0] ?? '');
            $grade = trim($parts[1] ?? '');
            $email = trim($parts[2] ?? '');
            if ($name !== '' && $grade !== '') {
                $comp['participants'][] = ['name' => $name, 'grade' => $grade, 'email' => $email];
            }
        }
    }

    $competitions[] = $comp;
} else {
    // No single competition keys provided: try to detect multiple competition blocks like comp1_id, comp1_title, comp1_mode, comp1_participant1...
    // Collect comp indices by scanning keys like comp{N}_id
    $comp_indices = [];
    foreach ($_POST as $k => $v) {
        if (preg_match('/^comp(\d+)_id$/', $k, $m)) {
            $comp_indices[$m[1]] = true;
        }
    }
    ksort($comp_indices);
    foreach (array_keys($comp_indices) as $idx) {
        $cid = $_POST["comp{$idx}_id"] ?? '';
        $ctitle = $_POST["comp{$idx}_title"] ?? '';
        $cmode = $_POST["comp{$idx}_mode"] ?? 'solo';
        $c = ['id'=>$cid, 'title'=>$ctitle, 'mode'=>$cmode, 'participants'=>[]];
        // find comp{idx}_participant{n}
        foreach ($_POST as $k => $v) {
            if (preg_match('/^comp' . $idx . '_participant(\d+)$/', $k, $m)) {
                $parts = explode('|', $v);
                $name = trim($parts[0] ?? '');
                $grade = trim($parts[1] ?? '');
                $email = trim($parts[2] ?? '');
                if ($name !== '' && $grade !== '') {
                    $c['participants'][] = ['name'=>$name, 'grade'=>$grade, 'email'=>$email];
                }
            }
        }
        $competitions[] = $c;
    }
}

// Build final payload
$payload = [
    'shared_secret' => $NESTIVAL_SHARED_SECRET,
    'school' => [
        'schoolName' => $schoolName,
        'faculty' => $faculty,
        'phone' => $phone,
        'schoolEmail' => $schoolEmail
    ],
    'competitions' => $competitions
];

// Simple logging for debug (append)
$log_entry = date('c') . " REQUEST FROM " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$log_entry .= "POST: " . json_encode($_POST) . "\n";
$log_entry .= "FORWARD: " . json_encode($payload) . "\n\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// Forward via cURL to internal REST endpoint
$ch = curl_init($rest_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
// set a simple user agent - sometimes helps with WAF
curl_setopt($ch, CURLOPT_USERAGENT, 'Nestival-Proxy/1.0 (+thenest.school)');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Upstream request failed','error'=>$err]);
    exit;
}
curl_close($ch);

// Return upstream response exactly
http_response_code($httpcode);
header('Content-Type: application/json');
echo $response;
exit;
