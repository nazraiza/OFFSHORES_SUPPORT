<?php
require __DIR__ . '/auth_check.php';
require __DIR__ . '/config/db.php';

// Simple search & pagination
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = '1=1';
$params = [];
if ($q !== '') {
  $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ? OR status LIKE ?)';
  $like = '%' . $q . '%';
  $params = [$like, $like, $like, $like, $like];
}

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leads WHERE $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Fetch rows
$stmt = $pdo->prepare("
  SELECT l.id, l.name, l.email, l.phone, s.name AS service_name, l.consent,
         l.status, l.created_at
  FROM leads l
  LEFT JOIN services s ON s.id = l.service_id
  WHERE $where
  ORDER BY l.id DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$totalPages = max(1, (int)ceil($total / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Admin Dashboard — Leads</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{color-scheme:dark}
    body{font-family:system-ui,Arial,sans-serif;background:#0b1220;color:#eaf1ff;margin:0;padding:24px}
    a{color:#9ec1ff;text-decoration:none}
    .wrap{max-width:1100px;margin:0 auto}
    .top{display:flex;gap:12px;align-items:center;justify-content:space-between;margin-bottom:16px}
    .card{background:#121a2b;padding:18px;border-radius:12px}
    form.search{display:flex;gap:8px}
    input[type="text"]{padding:10px 12px;border:1px solid #2b3550;background:#0f1628;color:#eaf1ff;border-radius:8px;min-width:260px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #1e2940}
    th{text-align:left;color:#a9c4ff}
    .pill{padding:4px 8px;border-radius:999px;background:#0f1628;border:1px solid #2b3550;color:#9ec1ff;font-size:12px}
    .muted{color:#a6b0c3}
    .pagination{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
    .btn{display:inline-block;padding:8px 10px;border-radius:8px;background:#1a2a4a;border:1px solid #2b3550;color:#eaf1ff}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h2>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></h2>
      <div>
        <a class="btn" href="logout.php">Logout</a>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px">
      <form class="search" method="GET">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, email, phone, message, status">
        <button class="btn" type="submit">Search</button>
        <?php if ($q !== ''): ?><a class="btn" href="admin_dashboard.php">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top:0">Recent Leads (<?= $total ?>)</h3>
      <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Service</th>
            <th>Consent</th>
            <th>Status</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['phone'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['service_name'] ?? '') ?></td>
              <td><span class="pill"><?= ($r['consent'] ? 'yes' : 'no') ?></span></td>
              <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
              <td class="muted"><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="8" class="muted">No leads yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <div class="pagination">
        <?php if ($page > 1): ?>
          <a class="btn" href="?<?= http_build_query(['q'=>$q,'page'=>$page-1]) ?>">&laquo; Prev</a>
        <?php endif; ?>
        <span class="muted pill">Page <?= $page ?> / <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
          <a class="btn" href="?<?= http_build_query(['q'=>$q,'page'=>$page+1]) ?>">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
