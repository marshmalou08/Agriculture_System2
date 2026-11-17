<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);

$r = Farm::detail($id);
if (!$r) {
    flash('danger', Farm::notFoundMessage());
    redirect('farms/index.php');
}
$plantings = Planting::byFarm($id);

$pageTitle = 'Farm Details';
$active = 'farms';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Farm Details</h1>
  <div class="d-flex gap-2">
    <a href="<?= e(url('farms/edit.php?id=' . $id)) ?>" class="btn btn-primary">Edit</a>
    <a href="<?= e(url('farms/index.php')) ?>" class="btn btn-outline-secondary">Back to list</a>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header"><?= e($r['farm_name']) ?></div>
  <div class="card-body">
    <dl class="row detail-list mb-0">
      <dt class="col-sm-3">Farm ID</dt><dd class="col-sm-9"><?= (int) $r['farm_id'] ?></dd>
      <dt class="col-sm-3">Farmer (Owner)</dt>
      <dd class="col-sm-9"><a href="<?= e(url('farmers/view.php?id=' . $r['farmer_id'])) ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></a> (<?= e($r['contact_number']) ?>)</dd>
      <dt class="col-sm-3">Location</dt><dd class="col-sm-9"><?= e($r['location']) ?></dd>
      <dt class="col-sm-3">Area</dt><dd class="col-sm-9"><?= number_format((float) $r['area_hectares'], 2) ?> hectares</dd>
      <dt class="col-sm-3">Soil Type</dt><dd class="col-sm-9"><?= e($r['soil_type_description']) ?></dd>
      <dt class="col-sm-3">Irrigation Type</dt><dd class="col-sm-9"><?= e($r['irrigation_type_description']) ?></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_datetime($r['created_at'])) ?> by <?= e($r['created_name'] ?? 'N/A') ?></dd>
      <dt class="col-sm-3">Last Updated</dt><dd class="col-sm-9"><?= e(fmt_datetime($r['updated_at'])) ?> by <?= e($r['updated_name'] ?? 'N/A') ?></dd>
    </dl>
  </div>
</div>
<div class="card">
  <div class="card-header">Planting Records on this Farm (<?= count($plantings) ?>)</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Crop</th><th>Planting Date</th><th class="text-end">Area (ha)</th><th>Expected Harvest</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($plantings as $p): ?>
        <tr>
          <td><?= e($p['crop_name']) ?> <small class="text-muted">(<?= e($p['variety']) ?>)</small></td>
          <td><?= e(fmt_date($p['planting_date'])) ?></td>
          <td class="text-end"><?= number_format((float) $p['area_planted'], 2) ?></td>
          <td><?= e(fmt_date($p['expected_harvest_date'])) ?></td>
          <td><span class="badge <?= (int) $p['status_id'] === 2 ? 'badge-status-harvested' : 'badge-status-growing' ?>"><?= e($p['status_description']) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('planting/view.php?id=' . $p['planting_id'])) ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$plantings): ?><tr><td colspan="6" class="text-center text-muted py-3">No planting records for this farm.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
