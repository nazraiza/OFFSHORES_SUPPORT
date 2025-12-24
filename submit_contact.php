<?php
require __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo 'Method Not Allowed'; exit;
}

function clean($s){ return trim((string)$s); }

$name       = clean($_POST['name'] ?? '');
$email      = clean($_POST['email'] ?? '');
$phone      = clean($_POST['phone'] ?? '');
$service    = clean($_POST['service'] ?? '');
$message    = clean($_POST['message'] ?? '');
$consent    = isset($_POST['consent']) ? 1 : 0;
$sourcePage = clean($_POST['source_page'] ?? '');

// Basic validation
if ($name === '' || ($email === '' && $phone === '')) {
  http_response_code(422); echo 'Please provide name and email or phone.'; exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422); echo 'Invalid email.'; exit;
}
if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{6,}$/', $phone)) {
  http_response_code(422); echo 'Invalid phone.'; exit;
}

// Map service slug -> id
$service_id = null;
if ($service !== '') {
  $stmt = $pdo->prepare('SELECT id FROM services WHERE slug = ? LIMIT 1');
  $stmt->execute([$service]);
  $row = $stmt->fetch();
  if ($row) $service_id = (int)$row['id'];
}

$ref = $_SERVER['HTTP_REFERER'] ?? '';
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip  = $_SERVER['REMOTE_ADDR'] ?? '';

// Simple rate-limit: max 5 leads per IP per hour
$rate = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE ip_address=? AND created_at > (NOW() - INTERVAL 1 HOUR)');
$rate->execute([$ip]);
if ((int)$rate->fetchColumn() >= 5) {
  http_response_code(429); echo 'Too many submissions. Please try again later.'; exit;
}

$stmt = $pdo->prepare(
  'INSERT INTO leads (name,email,phone,service_id,message,consent,source_page,referrer_url,user_agent,ip_address,status)
   VALUES (?,?,?,?,?,?,?,?,?,?, "new")'
);
$stmt->execute([$name,$email,$phone,$service_id,$message,$consent,$sourcePage,$ref,$ua,$ip]);


header('Content-Type: application/json');
echo json_encode(['ok'=>true]);
