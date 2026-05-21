<?php
require_once __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: application/json');

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if (!$id && $action !== '') {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

switch ($action) {

    case 'update_status':
        $allowed = ['new', 'contacted', 'in_progress', 'closed', 'spam'];
        $status  = $_POST['status'] ?? '';
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']); exit;
        }
        $stmt = $db->prepare("UPDATE leads SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'update_notes':
        $notes = $_POST['notes'] ?? '';
        $stmt  = $db->prepare("UPDATE leads SET notes=? WHERE id=?");
        $stmt->bind_param('si', $notes, $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    case 'delete':
        $stmt = $db->prepare("DELETE FROM leads WHERE id=?");
        $stmt->bind_param('i', $id);
        echo json_encode(['success' => $stmt->execute()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>
