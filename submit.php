<?php
// submit.php — place this in your ROOT project folder (same level as index.html)
// Both forms POST to this single file. It auto-detects which form was submitted.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Lock to your domain on production

require_once __DIR__ . '/admin/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')));
}

// ── Shared fields (both forms) ────────────────────────────
$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$phone   = clean($_POST['phone']   ?? '');
$message = clean($_POST['message'] ?? '');

// ── Source detection ──────────────────────────────────────
// JS will append a hidden field: <input type="hidden" name="source" value="homepage|contact">
$source  = clean($_POST['source']  ?? 'homepage');
if (!in_array($source, ['homepage', 'contact'])) $source = 'homepage';

// ── Project type ──────────────────────────────────────────
// Homepage form uses name="type"
// Contact page form uses name="project_type"
// We store both in the same DB column
$project_type = clean($_POST['project_type'] ?? $_POST['type'] ?? '');

// ── Contact page extra fields ─────────────────────────────
$project_address = clean($_POST['project_address'] ?? '');
$budget          = clean($_POST['budget']           ?? '');
$timeline        = clean($_POST['timeline']         ?? '');

// ── Validation ────────────────────────────────────────────
if (!$name || !$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Name, email and phone are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// ── Insert ────────────────────────────────────────────────
$db   = getDB();
$stmt = $db->prepare("
    INSERT INTO leads
        (name, email, phone, message, source, project_type, project_address, budget, timeline, ip_address)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    'ssssssssss',
    $name, $email, $phone, $message,
    $source, $project_type,
    $project_address, $budget, $timeline,
    $ip
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you! We will be in touch soon.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
?>
