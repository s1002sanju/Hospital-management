<?php 
require __DIR__ . '/../../includes/bootstrap.php'; 
require_admin(); 
$title = 'Hospital Administration Dashboard'; 
$pdo = db();

// Real Database Metrics
$tot = $pdo->query("SELECT COUNT(*) total, 
                           COUNT(CASE WHEN status='booked' THEN 1 END) booked, 
                           COUNT(CASE WHEN status='cancelled' THEN 1 END) cancelled, 
                           COUNT(CASE WHEN status='completed' THEN 1 END) completed 
                    FROM appointments")->fetch();

$today = (int)$pdo->query("SELECT COUNT(*) FROM appointments a 
                          JOIN slots s ON s.id=a.slot_id 
                          WHERE s.slot_date=" . sql_today() . " AND a.status='booked'")->fetchColumn();

$patients = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='patient'")->fetchColumn();

$byDept = $pdo->query("SELECT dp.name, COUNT(a.id) c 
                       FROM departments dp 
                       LEFT JOIN doctors d ON d.department_id=dp.id 
                       LEFT JOIN slots s ON s.doctor_id=d.id 
                       LEFT JOIN appointments a ON a.slot_id=s.id AND a.status<>'cancelled' 
                       GROUP BY dp.id, dp.name 
                       ORDER BY dp.name")->fetchAll();

// Recent real appointment activity
$recentAppts = $pdo->query("SELECT a.id, a.status, u.name AS patient, s.slot_date, s.start_time, dr.name AS doctor 
                            FROM appointments a 
                            JOIN users u ON u.id=a.user_id 
                            JOIN slots s ON s.id=a.slot_id 
                            JOIN doctors dr ON dr.id=s.doctor_id 
                            ORDER BY a.id DESC LIMIT 5")->fetchAll();

require __DIR__ . '/../../includes/header.php'; 
?>

<!-- Admin Dashboard Title & Quick Actions -->
<div class="page-header">
  <div>
    <h3 class="page-title">Admin Dashboard</h3>
    <p class="page-subtitle mb-0">Overview of patient registrations, specialist consultations, and appointment analytics.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="/admin/appointments.php" class="btn btn-outline-primary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      Manage Appointments
    </a>
    <a href="/admin/slots.php" class="btn btn-primary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Generate Slots
    </a>
  </div>
</div>

<!-- Accurate Key Metrics Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-primary" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
    </div>
    <span class="stat-value"><?= $patients ?></span>
    <span class="stat-label">Registered Patients</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-success" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
    </div>
    <span class="stat-value"><?= $today ?></span>
    <span class="stat-label">Today's Bookings</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-info" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
    </div>
    <span class="stat-value"><?= (int)$tot['booked'] ?></span>
    <span class="stat-label">Active Booked Visits</span>
  </div>

  <div class="stat-card">
    <div class="stat-card-icon stat-card-icon-secondary" aria-hidden="true">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
    </div>
    <span class="stat-value"><?= (int)$tot['cancelled'] ?></span>
    <span class="stat-label">Cancelled Visits</span>
  </div>
</div>

<!-- Interactive Analytics Charts -->
<div class="row g-4 mb-5">
  <div class="col-lg-7">
    <div class="chart-card h-100">
      <h6>Active Appointments by Department</h6>
      <div style="position: relative; min-height: 280px;">
        <canvas id="deptChart"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="chart-card h-100">
      <h6>Appointment Status Distribution</h6>
      <div style="position: relative; min-height: 280px; display: flex; align-items: center; justify-content: center;">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Recent Appointment Activity -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="fw-bold mb-1">Recent Booking Activity</h5>
        <div class="small text-muted">Latest patient appointments registered in the system.</div>
      </div>
      <a href="/admin/appointments.php" class="btn btn-sm btn-outline-primary">
        View All Appointments
      </a>
    </div>

    <?php if (!$recentAppts): ?>
      <div class="empty-state py-4">
        <p class="text-muted mb-0">No appointment records found in database.</p>
      </div>
    <?php else: ?>
      <div class="table-wrapper">
        <table class="table" aria-label="Recent Appointments">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Patient</th>
              <th scope="col">Doctor</th>
              <th scope="col">Schedule</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentAppts as $ra): ?>
              <tr>
                <td class="fw-semibold text-muted">#<?= (int)$ra['id'] ?></td>
                <td class="fw-medium"><?= e($ra['patient']) ?></td>
                <td><?= e($ra['doctor']) ?></td>
                <td>
                  <div><?= date('M j, Y', strtotime($ra['slot_date'])) ?></div>
                  <div class="small text-muted"><?= e(substr($ra['start_time'], 0, 5)) ?></div>
                </td>
                <td>
                  <?php
                    $statusClass = match($ra['status']) {
                      'booked' => 'badge-booked',
                      'completed' => 'badge-completed',
                      'cancelled' => 'badge-cancelled',
                      default => 'bg-secondary'
                    };
                  ?>
                  <span class="badge badge-status <?= $statusClass ?>">
                    <?= e(ucfirst($ra['status'])) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Brand color palette constants
  const colors = {
    primary: '#12304A',
    secondary: '#0F8B8D',
    success: '#16865B',
    warning: '#D98A18',
    muted: '#667789',
    lightBorder: '#E3EAF0',
  };

  // 1. Department Bar Chart
  const deptLabels = <?= json_encode(array_column($byDept, 'name')) ?>;
  const deptData = <?= json_encode(array_map('intval', array_column($byDept, 'c'))) ?>;
  
  new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
      labels: deptLabels,
      datasets: [{
        label: 'Active Appointments',
        data: deptData,
        backgroundColor: '#0F8B8D',
        borderRadius: 6,
        hoverBackgroundColor: '#0b6d6f'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1, color: colors.muted },
          grid: { color: colors.lightBorder }
        },
        x: {
          ticks: { color: colors.muted },
          grid: { display: false }
        }
      }
    }
  });

  // 2. Status Doughnut Chart
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Booked', 'Completed', 'Cancelled'],
      datasets: [{
        data: [
          <?= (int)$tot['booked'] ?>, 
          <?= (int)$tot['completed'] ?>, 
          <?= (int)$tot['cancelled'] ?>
        ],
        backgroundColor: ['#16865B', '#2B7CD4', '#8E9BAA'],
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { boxWidth: 12, padding: 15, color: colors.muted }
        }
      },
      cutout: '70%'
    }
  });
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
