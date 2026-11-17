<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Harvest Records';
$active = 'harvest';

$q = get('q');
$grade = get('grade');
$remark = get('remark');
$from = get('date_from');
$to = get('date_to');
$filters = ['q' => $q, 'grade' => $grade, 'remark' => $remark, 'date_from' => $from, 'date_to' => $to];
[$sortKey, $sortDir, $orderBy] = sort_clause(Harvest::sortColumns(), 'date', 'desc');
[$page, $pages, $total, $offset] = paginate_total(Harvest::countMatching($filters));
$rows = Harvest::search($filters, $orderBy, PER_PAGE, $offset);
$totals = Harvest::totals($filters);   // totals for everything that matches the filter, not only this page
$grades = Lookup::options('quality_grade');
$remarks = Lookup::options('harvest_remark');

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Harvest Records</h1>
  <a href="<?= e(url('harvest/add.php')) ?>" class="btn btn-primary">+ Add Harvest Record</a>
</div>

<form method="get" class="filter-bar row g-2 mb-3">
  <div class="col-md-4">
    <label for="q" class="form-label mb-1">Search</label>
    <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Farm, crop or variety">
  </div>
  <div class="col-md-4">
    <label for="grade" class="form-label mb-1">Quality Grade</label>
    <select class="form-select" id="grade" name="grade"><?= options($grades, $grade, 'All grades') ?></select>
  </div>
  <div class="col-md-4">
    <label for="remark" class="form-label mb-1">Remarks</label>
    <select class="form-select" id="remark" name="remark"><?= options($remarks, $remark, 'All remarks') ?></select>
  </div>
  <div class="col-md-3">
    <label for="date_from" class="form-label mb-1">Harvested from</label>
    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e($from) ?>">
  </div>
  <div class="col-md-3">
    <label for="date_to" class="form-label mb-1">Harvested to</label>
    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($to) ?>">
  </div>
  <div class="col-md-3 d-flex align-items-end gap-2">
    <button class="btn btn-primary" type="submit">Filter</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('harvest/index.php')) ?>">Reset</a>
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
          <?= sort_th('Harvest Date', 'date', $sortKey, $sortDir) ?>
          <?= sort_th('Qty (kg)', 'qty', $sortKey, $sortDir) ?>
          <?= sort_th('Price/kg', 'price', $sortKey, $sortDir) ?>
          <?= sort_th('Est. Value', 'value', $sortKey, $sortDir) ?>
          <?= sort_th('Grade', 'grade', $sortKey, $sortDir) ?>
          <?= sort_th('Remarks', 'remark', $sortKey, $sortDir) ?>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['harvest_id'] ?></td>
          <td><?= e($r['farm_name']) ?></td>
          <td><?= e($r['crop_name']) ?> <small class="text-muted">(<?= e($r['variety']) ?>)</small></td>
          <td><?= e(fmt_date($r['harvest_date'])) ?></td>
          <td class="text-end"><?= number_format((float) $r['quantity_kg'], 2) ?></td>
          <td class="text-end"><?= e(money($r['selling_price_per_kg'])) ?></td>
          <td class="text-end"><?= e(money($r['est_value'])) ?></td>
          <td><span class="badge badge-grade"><?= e($r['grade_description']) ?></span></td>
          <td><?= e($r['remark_description']) ?></td>
          <td class="text-end"><?= row_actions('harvest', (int) $r['harvest_id'], 'harvest record #' . $r['harvest_id'] . ' (' . $r['crop_name'] . ')') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No harvest records found.</td></tr>
      <?php endif; ?>
      </tbody>
      <?php if ($rows): ?>
      <tfoot>
        <tr>
          <th colspan="4" class="text-end">Totals for all matching records:</th>
          <th class="text-end"><?= number_format((float) $totals['qty'], 2) ?></th>
          <th></th>
          <th class="text-end"><?= e(money($totals['val'])) ?></th>
          <th colspan="3"></th>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?= pagination_html($page, $pages, $total) ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
