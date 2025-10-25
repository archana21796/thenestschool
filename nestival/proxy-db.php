<?php
// proxy-db.php
// Direct DB insert proxy for testing only.
// Place in public_html/nestival/proxy-db.php
// REMOVE this file when done testing or protect it with authentication.

header('Content-Type: application/json; charset=utf-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Boot WP to use $wpdb safely
$maybe = __DIR__ . '/../wp-load.php';
if (!file_exists($maybe)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'wp-load.php not found: ' . $maybe]);
    exit;
}
require_once $maybe;
if (!isset($wpdb)) {
    global $wpdb;
}

// Simple log
$logfile = __DIR__ . '/proxy-db.log';
function llog($s) {
    global $logfile;
    @file_put_contents($logfile, date('c') . " - " . $s . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Read input from form-data / x-www-form-urlencoded
$schoolName  = trim($_POST['schoolName'] ?? '');
$faculty     = trim($_POST['faculty'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$schoolEmail = trim($_POST['schoolEmail'] ?? '');

// Build competitions (simple single competition support)
$competitions = [];

if (!empty($_POST['competitionId']) || !empty($_POST['competitionTitle'])) {
    $comp = [
        'id' => sanitize_text_field($_POST['competitionId'] ?? 'ai_comp'),
        'title' => sanitize_text_field($_POST['competitionTitle'] ?? 'Competition'),
        'mode' => sanitize_text_field($_POST['mode'] ?? 'solo'),
        'participants' => []
    ];
    // participantN keys: "participant1", "participant2", ...
    foreach ($_POST as $k => $v) {
        if (preg_match('/^participant(\d+)$/', $k)) {
            $parts = explode('|', $v);
            $name  = sanitize_text_field($parts[0] ?? '');
            $grade = sanitize_text_field($parts[1] ?? '');
            $email = sanitize_email($parts[2] ?? '');
            if ($name && $grade) {
                $comp['participants'][] = ['name'=>$name,'grade'=>$grade,'email'=>$email];
            }
        }
    }
    $competitions[] = $comp;
} else {
    // detect compN blocks: comp1_id, comp1_title, comp1_mode, comp1_participant1 ...
    $comp_idxs = [];
    foreach ($_POST as $k => $v) {
        if (preg_match('/^comp(\d+)_id$/', $k, $m)) $comp_idxs[$m[1]] = true;
    }
    ksort($comp_idxs);
    foreach (array_keys($comp_idxs) as $idx) {
        $cid = sanitize_text_field($_POST["comp{$idx}_id"] ?? '');
        $ctitle = sanitize_text_field($_POST["comp{$idx}_title"] ?? '');
        $cmode  = sanitize_text_field($_POST["comp{$idx}_mode"] ?? 'group');
        $c = ['id'=>$cid,'title'=>$ctitle,'mode'=>$cmode,'participants'=>[]];
        foreach ($_POST as $k => $v) {
            if (preg_match('/^comp' . $idx . '_participant(\d+)$/', $k)) {
                $parts = explode('|', $v);
                $name  = sanitize_text_field($parts[0] ?? '');
                $grade = sanitize_text_field($parts[1] ?? '');
                $email = sanitize_email($parts[2] ?? '');
                if ($name && $grade) $c['participants'][] = ['name'=>$name,'grade'=>$grade,'email'=>$email];
            }
        }
        $competitions[] = $c;
    }
}

// Basic validation
if (!$schoolName || !$faculty || !$phone || !$schoolEmail) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Missing required school fields']);
    exit;
}

// Insert into registrations table
$tbl_reg = $wpdb->prefix . 'nestival_registrations';
$tbl_part = $wpdb->prefix . 'nestival_participants';

// Prepare extra payload for record
$extra = ['posted'=>$_POST, 'remote'=>$_SERVER['REMOTE_ADDR'] ?? ''];

// Insert registration
$ok = $wpdb->insert(
    $tbl_reg,
    [
        'school_name' => $schoolName,
        'faculty_incharge' => $faculty,
        'phone' => $phone,
        'school_email' => $schoolEmail,
        'extra' => maybe_serialize($extra)
    ],
    ['%s','%s','%s','%s','%s']
);

if ($ok === false) {
    llog("Registration insert failed: " . print_r($wpdb->last_error, true));
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB insert failed (registration)']);
    exit;
}

$registration_id = $wpdb->insert_id;

// Insert participants
foreach ($competitions as $comp) {
    $comp_id = $comp['id'] ?? '';
    $comp_title = $comp['title'] ?? '';
    $mode = $comp['mode'] ?? '';
    $index = 1;
    foreach ($comp['participants'] as $p) {
        $pname = sanitize_text_field($p['name'] ?? '');
        $pgrade = sanitize_text_field($p['grade'] ?? '');
        $pemail = sanitize_email($p['email'] ?? '');
        if (!$pname || !$pgrade) continue;
        $ok2 = $wpdb->insert(
            $tbl_part,
            [
                'registration_id' => $registration_id,
                'competition_id' => $comp_id,
                'competition_title' => $comp_title,
                'mode' => $mode,
                'participant_index' => intval($index),
                'student_name' => $pname,
                'grade' => $pgrade,
                'email' => $pemail
            ],
            ['%d','%s','%s','%s','%d','%s','%s','%s']
        );
        if ($ok2 === false) llog("Participant insert failed: " . print_r($wpdb->last_error, true));
        $index++;
    }
}

// Success
http_response_code(201);
echo json_encode(['success'=>true,'registration_id'=>$registration_id]);
exit;
