<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Crops';
$active = 'crops';

$q = get('q');
$category = get('category');
$filters = ['q' => $q, 'category' => $category];
[$sortKey, $sortDir, $orderBy] = sort_clause(Crop::sortColumns(), 'id');
[$page, $pages, $total, $offset] = paginate_total(Crop::countMatching($filters));
$rows = Crop::search($filters, $orderBy, PER_PAGE, $offset);
$categories = Lookup::options('crop_category');

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Crops</h1>
  <a href="<?= e(url('crops/add.php')) ?>" class="btn btn-primary">+ Add Crop</a>
</div>

<form method="get" class="filter-bar row g-2 mb-3">
  <div class="col-md-5">
    <label for="q" class="form-label mb-1">Search</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Crop name or variety">
  </div>
  <div class="col-md-4">
    <label for="category" class="form-label mb-1">Crop Category</label>
    <select class="form-select" id="category" name="category"><?= options($categories, $category, 'All categories') ?></select>
  </div>
  <div class="col-md-3 d-flex align-items-end gap-2">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('crops/index.php')) ?>">Reset</a>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <?= sort_th('ID', 'id', $sortKey, $sortDir) ?>
          <?= sort_th('Crop Name', 'name', $sortKey, $sortDir) ?>
          <?= sort_th('Variety', 'variety', $sortKey, $sortDir) ?>
          <?= sort_th('Category', 'category', $sortKey, $sortDir) ?>
          <?= sort_th('Growing Period (days)', 'period', $sortKey, $sortDir) ?>
          <?= sort_th('Recommended Season', 'season', $sortKey, $sortDir) ?>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['crop_id'] ?></td>
          <td><?= e($r['crop_name']) ?></td>
          <td><?= e($r['variety']) ?></td>
          <td><?= e($r['category_description']) ?></td>
          <td class="text-end"><?= (int) $r['growing_period_days'] ?></td>
          <td><?= e($r['season_description']) ?></td>
          <td class="text-end"><?= row_actions('crops', (int) $r['crop_id'], $r['crop_name'] . ' (' . $r['variety'] . ')') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No crops found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?= pagination_html($page, $pages, $total) ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
