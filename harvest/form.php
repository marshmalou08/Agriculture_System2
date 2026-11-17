<?php
// Shared by add.php and edit.php (not a page on its own).
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (empty($formEntry)) {
    redirect('harvest/index.php');
}
$editing = $id !== null;
$harvest = $editing ? Harvest::find($id) : new Harvest();
if (!$harvest) {
    flash('danger', Harvest::notFoundMessage());
    redirect('harvest/index.php');
}
$errors = [];
if (!$editing && ctype_digit(get('planting_id'))) {
    $harvest->planting_id = get('planting_id'); // preselect when coming from a planting page
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $harvest->fillFromForm($_POST);
    $errors = $harvest->validate();          // the class checks its own fields
    if (!$errors) {
        try {
            $harvest->save();                // INSERT or UPDATE, audit fields and audit log
            flash('success', $harvest->savedMessage(!$editing));
            redirect('harvest/index.php');
        } catch (PDOException $e) {
            $errors['general'] = friendly_db_error($e);
        }
    }
}
$data = $harvest->toArray();
$plantings = Planting::options();
$grades = Lookup::options('quality_grade');
$remarks = Lookup::options('harvest_remark');

$pageTitle = $editing ? 'Edit Harvest Record' : 'Add Harvest Record';
$active = 'harvest';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>
<?php if (isset($errors['general'])): ?><div class="alert alert-danger"><?= e($errors['general']) ?></div><?php endif; ?>
<?php if ($errors && !isset($errors['general'])): ?><div class="alert alert-danger">Please correct the highlighted fields.</div><?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" class="needs-validation" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label for="planting_id" class="form-label required">Planting Record</label>
          <select class="form-select <?= isset($errors['planting_id']) ? 'is-invalid' : '' ?>" id="planting_id" name="planting_id" required>
            <?= options($plantings, $data['planting_id'], '-- Select planting record --') ?>
          </select>
          <div class="invalid-feedback">Select a planting record.</div><?= field_error($errors, 'planting_id') ?>
        </div>
        <div class="col-md-4">
          <label for="harvest_date" class="form-label required">Harvest Date</label>
          <input type="date" class="form-control <?= isset($errors['harvest_date']) ? 'is-invalid' : '' ?>" id="harvest_date"
                 name="harvest_date" required value="<?= e($data['harvest_date']) ?>">
          <div class="invalid-feedback">Enter a valid date.</div><?= field_error($errors, 'harvest_date') ?>
        </div>
        <div class="col-md-4">
          <label for="quantity_kg" class="form-label required">Quantity Harvested (kg)</label>
          <input type="number" step="0.01" min="0.01" max="99999999.99" class="form-control <?= isset($errors['quantity_kg']) ? 'is-invalid' : '' ?>"
                 id="quantity_kg" name="quantity_kg" required value="<?= e($data['quantity_kg']) ?>">
          <div class="invalid-feedback">Enter a quantity greater than 0.</div><?= field_error($errors, 'quantity_kg') ?>
        </div>
        <div class="col-md-4">
          <label for="selling_price_per_kg" class="form-label required">Selling Price per kg (₱)</label>
          <input type="number" step="0.01" min="0.01" max="100000" class="form-control <?= isset($errors['selling_price_per_kg']) ? 'is-invalid' : '' ?>"
                 id="selling_price_per_kg" name="selling_price_per_kg" required value="<?= e($data['selling_price_per_kg']) ?>">
          <div class="invalid-feedback">Enter a price greater than 0.</div><?= field_error($errors, 'selling_price_per_kg') ?>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="est_value">Estimated Harvest Value</label>
          <input type="text" class="form-control" id="est_value" value="" readonly tabindex="-1" aria-describedby="est_help">
          <div id="est_help" class="form-text">Quantity × Selling Price per kg</div>
        </div>
        <div class="col-md-6">
          <label for="grade_id" class="form-label required">Quality Grade</label>
          <select class="form-select <?= isset($errors['grade_id']) ? 'is-invalid' : '' ?>" id="grade_id" name="grade_id" required>
            <?= options($grades, $data['grade_id'], '-- Select grade --') ?>
          </select>
          <div class="invalid-feedback">Select a quality grade.</div><?= field_error($errors, 'grade_id') ?>
        </div>
        <div class="col-md-6">
          <label for="remark_id" class="form-label required">Remarks</label>
          <select class="form-select <?= isset($errors['remark_id']) ? 'is-invalid' : '' ?>" id="remark_id" name="remark_id" required>
            <?= options($remarks, $data['remark_id'], '-- Select remark --') ?>
          </select>
          <div class="invalid-feedback">Select a remark.</div><?= field_error($errors, 'remark_id') ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Harvest Record' ?></button>
        <a href="<?= e(url('harvest/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
