<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Planting Records';
$active = 'planting';

$q = get('q');
$status = get('status');
$farm = get('farm');
$crop = get('crop');
$from = get('date_from');
$to = get('date_to');
$filters = ['q' => $q, 'status' => $status, 'farm' => $farm, 'crop' => $crop, 'date_from' => $from, 'date_to' => $to];
[$sortKey, $sortDir, $orderBy] = sort_clause(Planting::sortColumns(), 'date', 'desc');
[$page, $pages, $total, $offset] = paginate_total(Planting::countMatching($filters));
$rows = Planting::search($filters, $orderBy, PER_PAGE, $offset);
$statuses = Lookup::options('planting_status');
$farms = Farm::nameOptions();
$crops = Crop::options();

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Planting Records</h1>
  <a href="<?= e(url('planting/add.php')) ?>" class="btn btn-primary">+ Add Planting Record</a>
</div>

<form method="get" class="filter-bar row g-2 mb-3">
  <div class="col-md-4">
    <label for="q" class="form-label mb-1">Search</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Farm, crop, variety or farmer">
  </div>
  <div class="col-md-2">
    <label for="status" class="form-label mb-1">Status</label>
    <select class="form-select" id="status" name="status"><?= options($statuses, $status, 'All') ?></select>
  </div>
  <div class="col-md-3">
    <label for="farm" class="form-label mb-1">Farm</label>
    <select class="form-select" id="farm" name="farm"><?= options($farms, $farm, 'All farms') ?></select>
  </div>
  <div class="col-md-3">
    <label for="crop" class="form-label mb-1">Crop</label>
    <select class="form-select" id="crop" name="crop"><?= options($crops, $crop, 'All crops') ?></select>
  </div>
  <div class="col-md-3">
    <label for="date_from" class="form-label mb-1">Planted from</label>
    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e($from) ?>">
  </div>
  <div class="col-md-3">
    <label for="date_to" class="form-label mb-1">Planted to</label>
    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($to) ?>">
  </div>
  <div class="col-md-3 d-flex align-items-end gap-2">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('planting/index.php')) ?>">Reset</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <?= sort_th('ID', 'id', $sortKey, $sortDir) ?>
          <?= sort_th('Farm', 'farm', $sortKey, $sortDir) ?>
          <?= sort_th('Crop', 'crop', $sortKey, $sortDir) ?>
          <?= sort_th('Planting Date', 'date', $sortKey, $sortDir) ?>
          <?= sort_th('Area (ha)', 'area', $sortKey, $sortDir) ?>
          <?= sort_th('Seed Qty', 'seed', $sortKey, $sortDir) ?>
          <?= sort_th('Expected Harvest', 'expected', $sortKey, $sortDir) ?>
          <?= sort_th('Status', 'status', $sortKey, $sortDir) ?>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['planting_id'] ?></td>
          <td><?= e($r['farm_name']) ?></td>
          <td><?= e($r['crop_name']) ?> <small class="text-muted">(<?= e($r['variety']) ?>)</small></td>
          <td><?= e(fmt_date($r['planting_date'])) ?></td>
          <td class="text-end"><?= number_format((float) $r['area_planted'], 2) ?></td>
          <td class="text-end"><?= number_format((float) $r['seed_quantity'], 2) ?></td>
          <td><?= e(fmt_date($r['expected_harvest_date'])) ?></td>
          <td><span class="badge <?= (int) $r['status_id'] === 2 ? 'badge-status-harvested' : 'badge-status-growing' ?>"><?= e($r['status_description']) ?></span></td>
          <td class="text-end"><?= row_actions('planting', (int) $r['planting_id'], 'planting record #' . $r['planting_id'] . ' (' . $r['crop_name'] . ' at ' . $r['farm_name'] . ')') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No planting records found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_html($page, $pages, $total) ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
