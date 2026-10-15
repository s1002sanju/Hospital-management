<?php // JSON: GET /api/slots.php?doctor_id=1&date=2026-11-02  (used by load tests & future mobile app)
require __DIR__ . '/../../includes/bootstrap.php'; header('Content-Type: application/json');
$doctor = (int)($_GET['doctor_id'] ?? 0); $date = $_GET['date'] ?? '';
if (!$doctor || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { http_response_code(400); echo json_encode(['error' => 'doctor_id and date (YYYY-MM-DD) required']); exit; }
$st = db()->prepare("SELECT id, start_time, end_time FROM slots WHERE doctor_id=? AND slot_date=? AND is_booked=0 ORDER BY start_time");
$st->execute([$doctor, $date]); echo json_encode(['slots' => $st->fetchAll()]);
