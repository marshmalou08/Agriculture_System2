<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$id = (int) ($_GET['id'] ?? 0);

$r = Planting::detail($id);
if (!$r) {
    flash('danger', Planting::notFoundMessage());
    redirect('planting/index.php');
}
$harvests = Harvest::byPlanting($id);

$pageTitle = 'Planting Details';
$active = 'planting';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Planting Details</h1>
  <div class="d-flex gap-2">
    <a href="<?= e(url('planting/edit.php?id=' . $id)) ?>" class="btn btn-primary">Edit</a>
    <a href="<?= e(url('planting/index.php')) ?>" class="btn btn-outline-secondary">Back to list</a>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header">Planting #<?= (int) $r['planting_id'] ?> — <?= e($r['crop_name']) ?> at <?= e($r['farm_name']) ?></div>
  <div class="card-body">
    <dl class="row detail-list mb-0">
      <dt class="col-sm-3">Farm</dt><dd class="col-sm-9"><a href="<?= e(url('farms/view.php?id=' . $r['farm_id'])) ?>"><?= e($r['farm_name']) ?></a> (<?= e($r['first_name'] . ' ' . $r['last_name']) ?>)</dd>
      <dt class="col-sm-3">Crop</dt><dd class="col-sm-9"><a href="<?= e(url('crops/view.php?id=' . $r['crop_id'])) ?>"><?= e($r['crop_name']) ?></a> (<?= e($r['variety']) ?>)</dd>
      <dt class="col-sm-3">Planting Date</dt><dd class="col-sm-9"><?= e(fmt_date($r['planting_date'])) ?></dd>
      <dt class="col-sm-3">Expected Harvest</dt><dd class="col-sm-9"><?= e(fmt_date($r['expected_harvest_date'])) ?></dd>
      <dt class="col-sm-3">Area Planted</dt><dd class="col-sm-9"><?= number_format((float) $r['area_planted'], 2) ?> hectares</dd>
      <dt class="col-sm-3">Seed Quantity</dt><dd class="col-sm-9"><?= number_format((float) $r['seed_quantity'], 2) ?></dd>
      <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge <?= (int) $r['status_id'] === 2 ? 'badge-status-harvested' : 'badge-status-growing' ?>"><?= e($r['status_description']) ?></span></dd>
      <dt class="col-sm-3">Created</dt><dd class="col-sm-9"><?= e(fmt_datetime($r['created_at'])) ?> by <?= e($r['created_name'] ?? 'N/A') ?></dd>
      <dt class="col-sm-3">Last Updated</dt><dd class="col-sm-9"><?= e(fmt_datetime($r['updated_at'])) ?> by <?= e($r['updated_name'] ?? 'N/A') ?></dd>
    </dl>
  </div>
</div>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    Harvests from this Planting (<?= count($harvests) ?>)
    <a href="<?= e(url('harvest/add.php?planting_id=' . $id)) ?>" class="btn btn-sm btn-accent">+ Add Harvest</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Date</th><th class="text-end">Qty (kg)</th><th class="text-end">Price/kg</th><th class="text-end">Est. Value</th><th>Grade</th><th>Remarks</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($harvests as $h): ?>
        <tr>
          <td><?= e(fmt_date($h['harvest_date'])) ?></td>
          <td class="text-end"><?= number_format((float) $h['quantity_kg'], 2) ?></td>
          <td class="text-end"><?= e(money($h['selling_price_per_kg'])) ?></td>
          <td class="text-end"><?= e(money($h['est_value'])) ?></td>
          <td><?= e($h['grade_description']) ?></td><td><?= e($h['remark_description']) ?></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('harvest/view.php?id=' . $h['harvest_id'])) ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$harvests): ?><tr><td colspan="7" class="text-center text-muted py-3">No harvest recorded for this planting yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
