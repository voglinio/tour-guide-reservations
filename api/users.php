
<?php
// declare(strict_types=1);
require __DIR__ . '/helpers.php';
require_auth();

$user = current_user();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && isset($_POST['_method'])) { $method = strtoupper($_POST['_method']); }
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) { $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']); }

switch ($method) {
  case 'GET':
    if ($user['role'] === 'admin') {
      $st = $pdo->query("SELECT id, username, display_name, role, created_at FROM users ORDER BY display_name");
      $rows = $st->fetchAll();
    } else {
      $st = $pdo->prepare("SELECT id, username, display_name, role, created_at FROM users WHERE id=?");
      $st->execute([$user['id']]);
      $rows = $st->fetchAll();
    }
    json_out($rows);
    break;

  case 'POST':
    if ($user['role'] !== 'admin') { json_out(['error'=>'Forbidden'],403); }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $display  = trim($_POST['display_name'] ?? '');
    $role     = $_POST['role'] ?? 'guide';
    if (!$username || !$password || !$display) { json_out(['error'=>'Missing fields'],422); }
    if (!in_array($role, ['admin','guide'], true)) $role = 'guide';
    $st = $pdo->prepare("SELECT COUNT(*) AS c FROM users WHERE username=?"); $st->execute([$username]);
    $row = $st->fetch(); if ($row && intval($row['c'])>0) { json_out(['error'=>'Username already exists'],422); }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = $pdo->prepare("INSERT INTO users (username, password_hash, role, display_name) VALUES (?,?,?,?)");
    $st->execute([$username, $hash, $role, $display]);
    json_out(['ok'=>true,'id'=>$pdo->lastInsertId()],201);
    break;

  default: json_out(['error'=>'Method not allowed'],405);
}
