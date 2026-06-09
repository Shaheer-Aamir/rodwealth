<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();

// --- Stats ---
$stats = [];
$r = $db->query("SELECT COUNT(*) AS total FROM leads"); $stats['total']       = $r->fetch_assoc()['total'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE status='new'"); $stats['new']          = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE status='contacted'"); $stats['contacted']    = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE status='closed'"); $stats['closed']       = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE source='homepage'"); $stats['homepage']     = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE source='contact'"); $stats['contact_page'] = $r->fetch_assoc()['c'];
$r = $db->query("SELECT COUNT(*) AS c FROM leads WHERE DATE(created_at)=CURDATE()"); $stats['today'] = $r->fetch_assoc()['c'];

// --- Filters ---
$status_filter = $_GET['status'] ?? '';
$source_filter = $_GET['source'] ?? '';
$search        = $_GET['search'] ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 10;

$where = ['1=1'];
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

$where_sql = implode(' AND ', $where);

// Total count for pagination
$count_stmt = $db->prepare("SELECT COUNT(*) AS c FROM leads WHERE $where_sql");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_leads = $count_stmt->get_result()->fetch_assoc()['c'];
$total_pages  = ceil($total_leads / $per_page);
$offset       = ($page - 1) * $per_page;

// Fetch leads
$stmt = $db->prepare("SELECT * FROM leads WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params[] = $per_page; $params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status_labels = [
    'new'         => ['label' => 'New',         'color' => '#16a34a'],
    'contacted'   => ['label' => 'Contacted',   'color' => '#14529d'],
    'in_progress' => ['label' => 'In Progress', 'color' => '#d97706'],
    'closed'      => ['label' => 'Closed',      'color' => '#7c3aed'],
    'spam'        => ['label' => 'Spam',        'color' => '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Rod Wealth Construction</title>
<link href="../assets/img/RodWealth-Favicon.png" rel="icon">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: #f1f5f9;
    color: #1e293b;
    font-family: 'Segoe UI', system-ui, sans-serif;
    font-size: 14px;
    min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: 230px;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    display: flex; flex-direction: column;
    padding: 24px 0;
    z-index: 100;
    box-shadow: 2px 0 8px rgba(0,0,0,0.05);
  }

  .sidebar-logo {
    padding: 0 20px 24px;
    border-bottom: 1px solid #e2e8f0;
  }

  .sidebar-logo img {
    max-width: 100%;
    height: auto;
    max-height: 52px;
    object-fit: contain;
    display: block;
  }

  .sidebar-logo p {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
    font-weight: 500;
    letter-spacing: 0.4px;
  }
  .nav {
    padding: 16px 12px;
    flex: 1;
  }

  .nav a {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    color: #64748b;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.15s;
    margin-bottom: 2px;
  }

  .nav a:hover {
    background: #eff6ff;
    color: #14529d;
  }

  .nav a.active {
    background: #dbeafe;
    color: #14529d;
    font-weight: 600;
  }

  .nav a .icon { font-size: 16px; }

  .sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid #e2e8f0;
  }

  .sidebar-footer a {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    color: #ef4444;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.15s;
  }

  .sidebar-footer a:hover { background: #fff1f2; }

  /* ── Main ── */
  .main {
    margin-left: 230px;
    padding: 28px 32px;
    min-height: 100vh;
  }

  .page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px;
  }

  .page-header h1 {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
  }

  .page-header p { color: #64748b; font-size: 13px; margin-top: 3px; }

  .btn-export {
    padding: 9px 18px;
    background: linear-gradient(135deg, #14529d, #1a65c0);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
    box-shadow: 0 2px 6px rgba(20,82,157,0.25);
    transition: opacity 0.15s;
  }

  .btn-export:hover { 
    
  opacity: 0.88; 
  color: #14529d !important

}

  /* ── Stats ── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
  }

  .stat-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  }

  .stat-card .label {
    color: #94a3b8;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 8px;
    font-weight: 600;
  }

  .stat-card .value {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
  }

  .stat-card .sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 5px;
  }

  /* ── Filters ── */
  .filters {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  }

  .filters input, .filters select {
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #1e293b;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s;
  }

  .filters input { width: 220px; }
  .filters input:focus, .filters select:focus { border-color: #14529d; box-shadow: 0 0 0 3px rgba(20,82,157,0.1); }

  .filters button {
    padding: 8px 16px;
    background: #14529d;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
  }

  .filters button:hover { opacity: 0.88; }

  .filters a.clear {
    color: #94a3b8;
    font-size: 13px;
    text-decoration: none;
  }

  .filters a.clear:hover { color: #64748b; }

  /* ── Table ── */
  .table-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  }

  .table-info {
    padding: 14px 20px;
    border-bottom: 1px solid #e2e8f0;
    color: #94a3b8;
    font-size: 13px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  thead th {
    background: #f8fafc;
    padding: 11px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 1px solid #e2e8f0;
  }

  tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.12s;
  }

  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #f8fafc; }

  tbody td {
    padding: 13px 16px;
    vertical-align: top;
  }

  .lead-name { font-weight: 600; color: #0f172a; }
  .lead-contact { font-size: 12px; color: #64748b; margin-top: 2px; }

  .badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
  }

  .source-badge {
    background: #dbeafe;
    color: #14529d;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
  }

  .msg-preview {
    color: #94a3b8;
    font-size: 12px;
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .date-col { color: #94a3b8; font-size: 12px; }

  .actions { display: flex; gap: 6px; }

  .btn-action {
    padding: 5px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
  }

  .btn-action:hover { border-color: #14529d; color: #14529d; background: #eff6ff; }
  .btn-action.danger:hover { border-color: #ef4444; color: #ef4444; background: #fff1f2; }

  /* ── Status select ── */
  select.status-select {
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #1e293b;
    font-size: 12px;
    cursor: pointer;
    font-weight: 500;
  }

  select.status-select:focus { outline: none; border-color: #14529d; }

  /* ── Pagination ── */
  .pagination {
    display: flex; gap: 6px; align-items: center;
    padding: 16px 20px;
    border-top: 1px solid #e2e8f0;
  }

  .pagination a, .pagination span {
    padding: 6px 12px;
    border-radius: 7px;
    font-size: 13px;
    text-decoration: none;
  }

  .pagination a { border: 1px solid #e2e8f0; color: #64748b; background: #fff; }
  .pagination a:hover { border-color: #14529d; color: #14529d; background: #eff6ff; }
  .pagination span.current { background: #14529d; color: #fff; font-weight: 600; }
  .pagination .dots { color: #94a3b8; border: none; }

  /* ── Modal ── */
  .modal-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,0.4);
    z-index: 200;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
  }

  .modal-overlay.open { display: flex; }

  .modal {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    padding: 28px;
    position: relative;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  }

  .modal h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }

  .modal-close {
    position: absolute; top: 16px; right: 18px;
    background: none; border: none; color: #94a3b8;
    font-size: 20px; cursor: pointer;
  }

  .modal-close:hover { color: #1e293b; }

  .detail-row { margin-bottom: 14px; }
  .detail-row .dl { font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
  .detail-row .dv { color: #1e293b; }

  .notes-textarea {
    width: 100%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    color: #1e293b;
    padding: 10px 12px;
    font-size: 13px;
    resize: vertical;
    min-height: 80px;
    outline: none;
    font-family: inherit;
    transition: border-color 0.2s;
  }

  .notes-textarea:focus { border-color: #14529d; box-shadow: 0 0 0 3px rgba(20,82,157,0.1); }

  .modal-actions { display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end; }

  .btn-save {
    padding: 9px 20px;
    background: #14529d;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
    box-shadow: 0 2px 6px rgba(20,82,157,0.25);
  }

  .btn-save:hover { opacity: 0.88; }

  .btn-cancel {
    padding: 9px 16px;
    background: none;
    border: 1px solid #e2e8f0;
    color: #64748b;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s;
  }

  .btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
  }

  .empty-state .icon { font-size: 36px; margin-bottom: 12px; }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../assets/img/RodWealth-NavLogo.png" alt="Rod Wealth Construction">
    <p>Admin Dashboard</p>
  </div>
  <nav class="nav">
    <a href="index.php" class="active"><span class="icon"><i class="bi bi-people-fill"></i></span> All Leads</a>
    <a href="index.php?status=new"><span class="icon"><i class="bi bi-person-plus-fill"></i></span> New Leads
      <?php if ($stats['new'] > 0): ?>
        <span style="margin-left:auto;background:#14529d;color:#fff;border-radius:20px;padding:1px 7px;font-size:11px"><?= $stats['new'] ?></span>
      <?php endif; ?>
    </a>
    <a href="index.php?source=homepage"><span class="icon"><i class="bi bi-house-door-fill"></i></span> Homepage Form</a>
    <a href="index.php?source=contact"><span class="icon"><i class="bi bi-envelope-fill"></i></span> Contact Form</a>
    <a href="export.php?<?= http_build_query(array_filter(['status'=>$status_filter,'source'=>$source_filter,'search'=>$search])) ?>" class="btn-export" style="margin-top:12px;border-radius:8px;font-size:13px;color:#fff;">⬇ Export CSV</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php">🚪 Sign Out</a>
  </div>
</aside>

<!-- Main Content -->
<main class="main">

  <div class="page-header">
    <div>
      <h1>Lead Dashboard</h1>
      <p><?= $stats['today'] ?> new lead<?= $stats['today'] !== 1 ? 's' : '' ?> today &nbsp;·&nbsp; <?= $stats['total'] ?> total</p>
    </div>
    <a href="export.php" class="btn-export">⬇ Export CSV</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="label">Total Leads</div>
      <div class="value"><?= $stats['total'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">New</div>
      <div class="value" style="color:#16a34a"><?= $stats['new'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Contacted</div>
      <div class="value" style="color:#14529d"><?= $stats['contacted'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Closed</div>
      <div class="value" style="color:#7c3aed"><?= $stats['closed'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">From Homepage</div>
      <div class="value"><?= $stats['homepage'] ?></div>
      <div class="sub">Contact page: <?= $stats['contact_page'] ?></div>
    </div>
    <div class="stat-card">
      <div class="label">Today</div>
      <div class="value" style="color:#14529d"><?= $stats['today'] ?></div>
    </div>
  </div>

  <!-- Filters -->
  <form class="filters" method="GET" action="index.php">
    <input type="text" name="search" placeholder="🔍  Search name, email, phone..." value="<?= htmlspecialchars($search) ?>">
    <select name="status">
      <option value="">All Statuses</option>
      <?php foreach ($status_labels as $k => $v): ?>
        <option value="<?= $k ?>" <?= $status_filter === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
      <?php endforeach; ?>
    </select>
    <select name="source">
      <option value="">All Sources</option>
      <option value="homepage" <?= $source_filter === 'homepage' ? 'selected' : '' ?>>Homepage</option>
      <option value="contact"  <?= $source_filter === 'contact'  ? 'selected' : '' ?>>Contact Page</option>
    </select>
    <button type="submit">Filter</button>
    <?php if ($search || $status_filter || $source_filter): ?>
      <a href="index.php" class="clear">✕ Clear</a>
    <?php endif; ?>
  </form>

  <!-- Table -->
  <div class="table-card">
    <div class="table-info">
      Showing <?= count($leads) ?> of <?= $total_leads ?> leads
    </div>

    <?php if (empty($leads)): ?>
      <div class="empty-state">
        <div class="icon">📭</div>
        <p>No leads found.</p>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Lead Info</th>
          <th>Source</th>
          <th>Message</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $lead): ?>
        <tr>
          <td style="color:#94a3b8"><?= $lead['id'] ?></td>
          <td>
            <div class="lead-name"><?= htmlspecialchars($lead['name']) ?></div>
            <div class="lead-contact"><?= htmlspecialchars($lead['email']) ?></div>
            <?php if ($lead['phone']): ?>
            <div class="lead-contact">📞 <?= htmlspecialchars($lead['phone']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="source-badge"><?= ucfirst($lead['source']) ?></span>
          </td>
          <td>
            <div class="msg-preview" title="<?= htmlspecialchars($lead['message']) ?>">
              <?= htmlspecialchars($lead['message'] ?? '—') ?>
            </div>
          </td>
          <td>
            <select class="status-select" data-id="<?= $lead['id'] ?>" onchange="updateStatus(this)">
              <?php foreach ($status_labels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>
                  style="color:<?= $v['color'] ?>"><?= $v['label'] ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="date-col"><?= date('M j, Y', strtotime($lead['created_at'])) ?><br><?= date('g:i a', strtotime($lead['created_at'])) ?></td>
          <td>
            <div class="actions">
              <button class="btn-action" onclick="openModal(<?= htmlspecialchars(json_encode($lead)) ?>)">View</button>
              <button class="btn-action danger" onclick="deleteLead(<?= $lead['id'] ?>)">Del</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php
        $qs = array_filter(['status'=>$status_filter,'source'=>$source_filter,'search'=>$search]);
        for ($i = 1; $i <= $total_pages; $i++):
          if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="?<?= http_build_query(array_merge($qs,['page'=>$i])) ?>"><?= $i ?></a>
          <?php endif;
        endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

</main>

<!-- View/Edit Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h3>Lead Details</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px">
      <div class="detail-row">
        <div class="dl">Name</div>
        <div class="dv" id="m-name"></div>
      </div>
      <div class="detail-row">
        <div class="dl">Source</div>
        <div class="dv" id="m-source"></div>
      </div>
      <div class="detail-row">
        <div class="dl">Email</div>
        <div class="dv" id="m-email"></div>
      </div>
      <div class="detail-row">
        <div class="dl">Phone</div>
        <div class="dv" id="m-phone"></div>
      </div>
      <div class="detail-row">
        <div class="dl">Project Type</div>
        <div class="dv" id="m-project-type"></div>
      </div>
      <div class="detail-row">
        <div class="dl">Budget</div>
        <div class="dv" id="m-budget"></div>
      </div>
      <div class="detail-row" id="m-address-row">
        <div class="dl">Project Address</div>
        <div class="dv" id="m-address"></div>
      </div>
      <div class="detail-row" id="m-timeline-row">
        <div class="dl">Timeline</div>
        <div class="dv" id="m-timeline"></div>
      </div>
    </div>

    <div class="detail-row">
      <div class="dl">Message / Project Details</div>
      <div class="dv" id="m-message" style="line-height:1.6;color:#64748b"></div>
    </div>

    <div class="detail-row">
      <div class="dl">Internal Notes</div>
      <textarea class="notes-textarea" id="m-notes" placeholder="Add private notes about this lead..."></textarea>
    </div>

    <div class="detail-row">
      <div class="dl">Date Received</div>
      <div class="dv" id="m-date" style="color:#94a3b8"></div>
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="btn-save" onclick="saveNotes()">Save Notes</button>
    </div>
  </div>
</div>

<script>
let currentLeadId = null;

function openModal(lead) {
  currentLeadId = lead.id;
  document.getElementById('m-name').textContent         = lead.name;
  document.getElementById('m-email').textContent        = lead.email;
  document.getElementById('m-phone').textContent        = lead.phone || '—';
  document.getElementById('m-source').textContent       = lead.source === 'homepage' ? '🏠 Homepage' : '📬 Contact Page';
  document.getElementById('m-project-type').textContent = lead.project_type || '—';
  document.getElementById('m-budget').textContent       = lead.budget || '—';
  document.getElementById('m-address').textContent      = lead.project_address || '—';
  document.getElementById('m-timeline').textContent     = lead.timeline || '—';
  document.getElementById('m-message').textContent      = lead.message || '—';
  document.getElementById('m-notes').value              = lead.notes || '';
  document.getElementById('m-date').textContent         = lead.created_at;
  document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  currentLeadId = null;
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

async function saveNotes() {
  const notes = document.getElementById('m-notes').value;
  const res = await fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_notes&id=${currentLeadId}&notes=${encodeURIComponent(notes)}`
  });
  const data = await res.json();
  if (data.success) { alert('Notes saved!'); closeModal(); } else { alert('Error saving notes.'); }
}

async function updateStatus(sel) {
  const id = sel.dataset.id;
  const status = sel.value;
  const res = await fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_status&id=${id}&status=${status}`
  });
  const data = await res.json();
  if (!data.success) { alert('Error updating status.'); location.reload(); }
}

async function deleteLead(id) {
  if (!confirm('Delete this lead permanently?')) return;
  const res = await fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=delete&id=${id}`
  });
  const data = await res.json();
  if (data.success) { location.reload(); } else { alert('Error deleting lead.'); }
}
</script>

</body>
</html>