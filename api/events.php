
<?php
// declare(strict_types=1);
require __DIR__ . '/helpers.php';
require_auth();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) { $method = strtoupper($_POST['_method']); }
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) { $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']); }

switch ($method) {
  case 'GET':
    $start = $_GET['start'] ?? null;
    $end   = $_GET['end'] ?? null;
    $user = current_user();
    if ($user['role']==='admin') {
      $sql = "SELECT e.*, u.display_name AS guide_name FROM events e JOIN users u ON u.id=e.guide_id
              WHERE (:start IS NULL OR e.start_datetime >= :start) AND (:end IS NULL OR e.end_datetime <= :end)";
      $st = $pdo->prepare($sql); $st->execute([':start'=>$start, ':end'=>$end]);
    } else {
      $sql = "SELECT e.*, u.display_name AS guide_name FROM events e JOIN users u ON u.id=e.guide_id
              WHERE e.guide_id=:gid AND (:start IS NULL OR e.start_datetime >= :start) AND (:end IS NULL OR e.end_datetime <= :end)";
      $st = $pdo->prepare($sql); $st->execute([':gid'=>$user['id'], ':start'=>$start, ':end'=>$end]);
    }
    $rows = $st->fetchAll();
    $events = array_map(function($r){
      return [
        'id'=>$r['id'],
        'title'=>$r['group_name'].' — '.$r['guide_name'],
        'start'=>$r['start_datetime'],
        'end'=>$r['end_datetime'],
        'extendedProps'=>[
          'guide_id'=>(int)$r['guide_id'],
          'group_name'=>$r['group_name'],
          'notes'=>$r['notes'] ?? '',
        ]
      ];
    }, $rows);
    json_out($events);
    break;

  case 'POST':
    $user = current_user();
    $guide_id = intval($_POST['guide_id'] ?? $user['id']);
    if ($user['role']!=='admin' && $guide_id!==intval($user['id'])) { json_out(['error'=>'Cannot create for another guide'],403); }
    $group_name = trim($_POST['group_name'] ?? '');
    $start = $_POST['start'] ?? '';
    $end = $_POST['end'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    if (!$group_name || !$start || !$end) { json_out(['error'=>'Missing fields'],422); }
    if (has_overlap($pdo, $guide_id, $start, $end, null)) { json_out(['error'=>'Overlapping reservation for this guide'],422); }
    $st = $pdo->prepare("INSERT INTO events (guide_id, group_name, start_datetime, end_datetime, notes) VALUES (?,?,?,?,?)");
    $st->execute([$guide_id, $group_name, $start, $end, $notes]);
    json_out(['ok'=>true, 'id'=>$pdo->lastInsertId()], 201);
    break;

  case 'PUT':
    parse_str(file_get_contents('php://input'), $body);
    $id = intval($body['id'] ?? 0); if(!$id) json_out(['error'=>'Missing id'],422);
    $st = $pdo->prepare("SELECT * FROM events WHERE id=?"); $st->execute([$id]); $event = $st->fetch();
    if(!$event) json_out(['error'=>'Not found'],404); if(!can_manage_event($event)) json_out(['error'=>'Forbidden'],403);
    $group_name = trim($body['group_name'] ?? $event['group_name']);
    $start = $body['start'] ?? $event['start_datetime'];
    $end = $body['end'] ?? $event['end_datetime'];
    $notes = trim($body['notes'] ?? ($event['notes'] ?? ''));
    if (has_overlap($pdo, intval($event['guide_id']), $start, $end, $event['id'])) { json_out(['error'=>'Overlapping reservation for this guide'],422); }
    $st = $pdo->prepare("UPDATE events SET group_name=?, start_datetime=?, end_datetime=?, notes=? WHERE id=?");
    $st->execute([$group_name, $start, $end, $notes, $id]);
    json_out(['ok'=>true]);
    break;

  case 'DELETE':
    parse_str(file_get_contents('php://input'), $body);
    $id = intval($body['id'] ?? 0); if(!$id) json_out(['error'=>'Missing id'],422);
    $st = $pdo->prepare("SELECT * FROM events WHERE id=?"); $st->execute([$id]); $event = $st->fetch();
    if(!$event) json_out(['error'=>'Not found'],404); if(!can_manage_event($event)) json_out(['error'=>'Forbidden'],403);
    $st = $pdo->prepare("DELETE FROM events WHERE id=?"); $st->execute([$id]);
    json_out(['ok'=>true]);
    break;

  default: json_out(['error'=>'Method not allowed'],405);
}
