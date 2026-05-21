<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();

// Respect same filters as dashboard
$status_filter = $_GET['status'] ?? '';
$source_filter = $_GET['source'] ?? '';
$search        = $_GET['search']  ?? '';

$where  = ['1=1'];
$params = [];
$types  = '';

if ($status_filter) { $where[] = 'status = ?'; $params[] = $status_filter; $types .= 's'; }
if ($source_filter) { $where[] = 'source = ?'; $params[] = $source_filter; $types .= 's'; }
if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

$stmt = $db->prepare("SELECT * FROM leads WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$filename = 'leads_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Project Type', 'Project Address', 'Budget', 'Timeline', 'Message', 'Source', 'Status', 'Notes', 'Date']);

foreach ($leads as $lead) {
    fputcsv($out, [
        $lead['id'],
        $lead['name'],
        $lead['email'],
        $lead['phone'],
        $lead['project_type'],
        $lead['project_address'],
        $lead['budget'],
        $lead['timeline'],
        $lead['message'],
        $lead['source'],
        $lead['status'],
        $lead['notes'],
        $lead['created_at'],
    ]);
}

fclose($out);
exit;
?>
