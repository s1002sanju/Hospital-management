<?php 
require __DIR__ . '/../../includes/bootstrap.php'; 
require_admin(); 
$title = 'Doctors & Departments'; 
$pdo = db();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { 
    csrf_check();
    $a = $_POST['action'] ?? '';

    if ($a === 'add_dept' && trim($_POST['name'] ?? '') !== '') {
        $sql = is_pgsql() 
            ? "INSERT INTO departments (name) VALUES (?) ON CONFLICT (name) DO NOTHING" 
            : "INSERT IGNORE INTO departments (name) VALUES (?)";
        $pdo->prepare($sql)->execute([trim($_POST['name'])]);
        flash('success', 'Department added successfully.');
    }

    if ($a === 'add_doc' && trim($_POST['name'] ?? '') !== '') { 
        $pdo->prepare("INSERT INTO doctors (name, department_id, qualification, fee) VALUES (?,?,?,?)")
            ->execute([
                trim($_POST['name']), 
                (int)$_POST['department_id'], 
                trim($_POST['qualification'] ?? ''), 
                (float)($_POST['fee'] ?? 0)
            ]); 
        flash('success', 'Doctor added successfully.');
    }

    if ($a === 'toggle') { 
        $pdo->prepare("UPDATE doctors SET is_active = NOT is_active WHERE id=?")->execute([(int)$_POST['id']]); 
        flash('success', 'Doctor status updated.');
    }

    redirect('/admin/doctors.php'); 
}

$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$docs = $pdo->query("SELECT d.*, dp.name AS dept FROM doctors d JOIN departments dp ON dp.id=d.department_id ORDER BY d.name")->fetchAll();

require __DIR__ . '/../../includes/header.php'; 
?>

<div class="page-header">
  <div>
    <h3 class="page-title">Doctors & Departments</h3>
    <p class="page-subtitle mb-0">Manage hospital clinical departments, active physicians, and consultation fees.</p>
  </div>
  <div>
    <a href="/admin/slots.php" class="btn btn-primary btn-sm">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
      Generate Slots
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column: Add Department & Add Doctor Forms -->
  <div class="col-lg-4">
    <!-- Add Department Form -->
    <div class="admin-form-card mb-4">
      <h6>Add New Department</h6>
      <form method="post" aria-label="Add Department Form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_dept">
        
        <div class="form-group mb-3">
          <label for="dept-name" class="form-label">Department Name</label>
          <input 
            type="text" 
            id="dept-name" 
            name="name" 
            class="form-control" 
            placeholder="e.g. Neurology, Oncology" 
            required 
            maxlength="100"
          >
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add Department
        </button>
      </form>
    </div>

    <!-- Add Doctor Form -->
    <div class="admin-form-card">
      <h6>Add New Doctor</h6>
      <form method="post" aria-label="Add Doctor Form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_doc">

        <div class="form-group mb-2">
          <label for="doc-name" class="form-label">Doctor Name</label>
          <input 
            type="text" 
            id="doc-name" 
            name="name" 
            class="form-control" 
            placeholder="e.g. Dr. Jane Smith" 
            required 
            maxlength="100"
          >
        </div>

        <div class="form-group mb-2">
          <label for="doc-dept" class="form-label">Department</label>
          <select id="doc-dept" name="department_id" class="form-select" required>
            <option value="">Select department...</option>
            <?php foreach ($depts as $d): ?>
              <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mb-2">
          <label for="doc-qual" class="form-label">Qualifications</label>
          <input 
            type="text" 
            id="doc-qual" 
            name="qualification" 
            class="form-control" 
            placeholder="e.g. MBBS, MD, FRCS" 
            maxlength="150"
          >
        </div>

        <div class="form-group mb-3">
          <label for="doc-fee" class="form-label">Consultation Fee (₹)</label>
          <input 
            type="number" 
            id="doc-fee" 
            name="fee" 
            step="0.01" 
            min="0" 
            class="form-control" 
            placeholder="e.g. 500.00"
          >
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add Doctor
        </button>
      </form>
    </div>
  </div>

  <!-- Right Column: Doctors List Table -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body p-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Registered Doctors (<?= count($docs) ?>)</h5>
          <span class="badge bg-primary"><?= count($depts) ?> Departments</span>
        </div>

        <?php if (!$docs): ?>
          <div class="empty-state py-5">
            <p class="text-muted mb-0">No doctors added yet. Use the form on the left to add your first physician.</p>
          </div>
        <?php else: ?>
          <div class="table-wrapper border-0 shadow-none">
            <table class="table mb-0" aria-label="Doctors Directory">
              <thead>
                <tr>
                  <th scope="col">Doctor</th>
                  <th scope="col">Department</th>
                  <th scope="col">Qualification</th>
                  <th scope="col">Fee</th>
                  <th scope="col" class="text-end">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($docs as $d): ?>
                  <tr>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="doctor-avatar mb-0" style="width: 36px; height: 36px; font-size: 0.85rem;" aria-hidden="true">
                          <?= strtoupper(substr(trim((string)$d['name']), 0, 1)) ?>
                        </div>
                        <div class="fw-bold text-primary"><?= e($d['name']) ?></div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-secondary"><?= e($d['dept']) ?></span>
                    </td>
                    <td>
                      <span class="small text-muted"><?= e($d['qualification'] ?: '—') ?></span>
                    </td>
                    <td>
                      <span class="fw-semibold">₹<?= number_format((float)$d['fee'], 2) ?></span>
                    </td>
                    <td class="text-end">
                      <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                        <button 
                          type="submit" 
                          class="btn btn-sm <?= $d['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                          title="Click to toggle active status"
                          data-no-loader="true"
                        >
                          <?php if ($d['is_active']): ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Active
                          <?php else: ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                            Inactive
                          <?php endif; ?>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
