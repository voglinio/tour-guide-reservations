
<?php
// declare(strict_types=1); // remove or keep depending on forwarders
require __DIR__ . '/../config/db.php';

function current_user(){ return $_SESSION['user'] ?? null; }
function json_out($data, int $code=200){ http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }
function require_auth(){ if(!current_user()){ json_out(['error'=>'Unauthorized'],401);} }
function can_manage_event(array $event): bool {
  $user = current_user(); if(!$user) return false;
  if($user['role']==='admin') return true;
  return intval($event['guide_id']) === intval($user['id']);
}
function has_overlap(PDO $pdo, int $guide_id, string $start, string $end, ?int $ignore_id=null): bool {
  $sql = "SELECT COUNT(*) AS c FROM events
          WHERE guide_id=:gid AND NOT (end_datetime <= :start OR start_datetime >= :end)";
  $params = [':gid'=>$guide_id, ':start'=>$start, ':end'=>$end];
  if($ignore_id!==null){ $sql .= " AND id <> :iid"; $params[':iid']=$ignore_id; }
  $st = $pdo->prepare($sql); $st->execute($params); $row=$st->fetch();
  return ($row && intval($row['c'])>0);
}
