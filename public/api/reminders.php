<?php // Called daily by the AWS Lambda reminder function (EventBridge schedule). Protected by a shared secret.
require __DIR__ . '/../../includes/bootstrap.php'; header('Content-Type: application/json');
if (!hash_equals(REMINDER_KEY, $_SERVER['HTTP_X_REMINDER_KEY'] ?? '')) { http_response_code(401); echo json_encode(['error' => 'unauthorized']); exit; }
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {            // Lambda confirms which ones it emailed
    $ids = array_map('intval', json_decode(file_get_contents('php://input'), true)['sent_ids'] ?? []);
    foreach ($ids as $id) $pdo->prepare("UPDATE appointments SET reminder_sent=1 WHERE id=?")->execute([$id]);
    echo json_encode(['updated' => count($ids)]); exit;
}
$st = $pdo->query("SELECT a.id, u.name, u.email, s.slot_date, s.start_time, dr.name AS doctor FROM appointments a
  JOIN users u ON u.id=a.user_id JOIN slots s ON s.id=a.slot_id JOIN doctors dr ON dr.id=s.doctor_id
  WHERE a.status='booked' AND a.reminder_sent=0 AND s.slot_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
echo json_encode(['appointments' => $st->fetchAll()]);
