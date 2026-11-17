<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Farms';
$active = 'farms';

$q = get('q');
$irrigation = get('irrigation');
$soil = get('soil');
$filters = ['q' => $q, 'irrigation' => $irrigation, 'soil' => $soil];
[$sortKey, $sortDir, $orderBy] = sort_clause(Farm::sortColumns(), 'id');
[$page, $pages, $total, $offset] = paginate_total(Farm::countMatching($filters));
$rows = Farm::search($filters, $orderBy, PER_PAGE, $offset);
$irrOptions = Lookup::options('irrigation_type');
$soilOptions = Lookup::options('soil_type');

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Farms</h1>
  <a href="<?= e(url('farms/add.php')) ?>" class="btn btn-primary">+ Add Farm</a>
</div>

<form method="get" class="filter-bar row g-2 mb-3">
  <div class="col-md-4">
    <label for="q" class="form-label mb-1">Search</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Farm, location or farmer">
  </div>
  <div class="col-md-3">
    <label for="irrigation" class="form-label mb-1">Irrigation Type</label>
    <select class="form-select" id="irrigation" name="irrigation"><?= options($irrOptions, $irrigation, 'All irrigation types') ?></select>
  </div>
  <div class="col-md-3">
    <label for="soil" class="form-label mb-1">Soil Type</label>
    <select class="form-select" id="soil" name="soil"><?= options($soilOptions, $soil, 'All soil types') ?></select>
  </div>
  <div class="col-md-2 d-flex align-items-end gap-2">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('farms/index.php')) ?>">Reset</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <?= sort_th('ID', 'id', $sortKey, $sortDir) ?>
          <?= sort_th('Farm Name', 'farm', $sortKey, $sortDir) ?>
          <?= sort_th('Farmer', 'farmer', $sortKey, $sortDir) ?>
          <?= sort_th('Location', 'location', $sortKey, $sortDir) ?>
          <?= sort_th('Area (ha)', 'area', $sortKey, $sortDir) ?>
          <?= sort_th('Soil Type', 'soil', $sortKey, $sortDir) ?>
          <?= sort_th('Irrigation', 'irrigation', $sortKey, $sortDir) ?>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['farm_id'] ?></td>
          <td><?= e($r['farm_name']) ?></td>
          <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
          <td><?= e($r['location']) ?></td>
          <td class="text-end"><?= number_format((float) $r['area_hectares'], 2) ?></td>
          <td><?= e($r['soil_type_description']) ?></td>
          <td><?= e($r['irrigation_type_description']) ?></td>
          <td class="text-end"><?= row_actions('farms', (int) $r['farm_id'], $r['farm_name']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No farms found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_html($page, $pages, $total) ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
