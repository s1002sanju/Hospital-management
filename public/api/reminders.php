<?php // Daily reminders. Protected by the shared secret REMINDER_KEY (header X-Reminder-Key).
//   POST /api/reminders.php?action=send   -> app emails tomorrow's patients itself   (used by .github/workflows/reminders.yml)
//   GET  /api/reminders.php               -> list tomorrow's appointments            (legacy: external sender, e.g. AWS Lambda)
//   POST /api/reminders.php {"sent_ids":[..]} -> mark those as reminded               (legacy)
require __DIR__ . '/../../includes/bootstrap.php'; header('Content-Type: application/json');
if (REMINDER_KEY === '' || !hash_equals(REMINDER_KEY, $_SERVER['HTTP_X_REMINDER_KEY'] ?? '')) { http_response_code(401); echo json_encode(['error' => 'unauthorized']); exit; }
$pdo = db();

function tomorrows_appointments(PDO $pdo): array {
    return $pdo->query("SELECT a.id, u.name, u.email, s.slot_date, s.start_time, dr.name AS doctor FROM appointments a
      JOIN users u ON u.id=a.user_id JOIN slots s ON s.id=a.slot_id JOIN doctors dr ON dr.id=s.doctor_id
      WHERE a.status='booked' AND a.reminder_sent=false AND s.slot_date = " . sql_tomorrow())->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_GET['action'] ?? '') === 'send') {
        $rows = tomorrows_appointments($pdo); $sent = 0;
        foreach ($rows as $r) {
            $ok = send_email($r['email'], 'Appointment reminder for tomorrow',
                "Hello {$r['name']},\nReminder: you have an appointment with {$r['doctor']} on {$r['slot_date']} at " . substr($r['start_time'], 0, 5) . '.');
            if ($ok) { $pdo->prepare("UPDATE appointments SET reminder_sent=true WHERE id=?")->execute([$r['id']]); $sent++; }
        }
        echo json_encode(['sent' => $sent, 'total' => count($rows)]); exit;
    }
    $ids = array_map('intval', json_decode(file_get_contents('php://input'), true)['sent_ids'] ?? []);
    $up = $pdo->prepare("UPDATE appointments SET reminder_sent=true WHERE id=?");
    foreach ($ids as $id) $up->execute([$id]);
    echo json_encode(['updated' => count($ids)]); exit;
}
echo json_encode(['appointments' => tomorrows_appointments($pdo)]);
