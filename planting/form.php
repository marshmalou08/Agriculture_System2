<?php
// Shared by add.php and edit.php (not a page on its own).
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (empty($formEntry)) {
    redirect('planting/index.php');
}
$editing = $id !== null;
$planting = $editing ? Planting::find($id) : new Planting();
if (!$planting) {
    flash('danger', Planting::notFoundMessage());
    redirect('planting/index.php');
}
$errors = [];
if (!$editing) {
    foreach (['farm_id', 'crop_id'] as $k) {   // preselect when coming from another page
        if (ctype_digit(get($k))) {
            $planting->$k = get($k);
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $planting->fillFromForm($_POST);
    $errors = $planting->validate();          // the class checks its own fields
    if (!$errors) {
        try {
            $planting->save();                // INSERT or UPDATE, audit fields and audit log
            flash('success', $planting->savedMessage(!$editing));
            redirect('planting/index.php');
        } catch (PDOException $e) {
            $errors['general'] = friendly_db_error($e);
        }
    }
}
$data = $planting->toArray();
$farms = Farm::options();
$crops = Crop::options();
$statuses = Lookup::options('planting_status');

$pageTitle = $editing ? 'Edit Planting Record' : 'Add Planting Record';
$active = 'planting';
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
        <div class="col-md-6">
          <label for="farm_id" class="form-label required">Farm</label>
          <select class="form-select <?= isset($errors['farm_id']) ? 'is-invalid' : '' ?>" id="farm_id" name="farm_id" required>
            <?= options($farms, $data['farm_id'], '-- Select farm --') ?>
          </select>
          <div class="invalid-feedback">Select a farm.</div><?= field_error($errors, 'farm_id') ?>
        </div>
        <div class="col-md-6">
          <label for="crop_id" class="form-label required">Crop</label>
          <select class="form-select <?= isset($errors['crop_id']) ? 'is-invalid' : '' ?>" id="crop_id" name="crop_id" required>
            <?= options($crops, $data['crop_id'], '-- Select crop --') ?>
          </select>
          <div class="invalid-feedback">Select a crop.</div><?= field_error($errors, 'crop_id') ?>
        </div>
        <div class="col-md-4">
          <label for="planting_date" class="form-label required">Planting Date</label>
          <input type="date" class="form-control <?= isset($errors['planting_date']) ? 'is-invalid' : '' ?>" id="planting_date"
                 name="planting_date" required value="<?= e($data['planting_date']) ?>">
          <div class="invalid-feedback">Enter a valid date.</div><?= field_error($errors, 'planting_date') ?>
        </div>
        <div class="col-md-4">
          <label for="expected_harvest_date" class="form-label required">Expected Harvest Date</label>
          <input type="date" class="form-control <?= isset($errors['expected_harvest_date']) ? 'is-invalid' : '' ?>" id="expected_harvest_date"
                 name="expected_harvest_date" required value="<?= e($data['expected_harvest_date']) ?>">
          <div class="invalid-feedback">Enter a date after the planting date.</div><?= field_error($errors, 'expected_harvest_date') ?>
        </div>
        <div class="col-md-4">
          <label for="status_id" class="form-label required">Status</label>
          <select class="form-select <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>" id="status_id" name="status_id" required>
            <?= options($statuses, $data['status_id'], '-- Select status --') ?>
          </select>
          <div class="invalid-feedback">Select a status.</div><?= field_error($errors, 'status_id') ?>
        </div>
        <div class="col-md-6">
          <label for="area_planted" class="form-label required">Area Planted (hectares)</label>
          <input type="number" step="0.01" min="0.01" max="10000" class="form-control <?= isset($errors['area_planted']) ? 'is-invalid' : '' ?>"
                 id="area_planted" name="area_planted" required value="<?= e($data['area_planted']) ?>">
          <div class="invalid-feedback">Enter an area greater than 0.</div><?= field_error($errors, 'area_planted') ?>
        </div>
        <div class="col-md-6">
          <label for="seed_quantity" class="form-label required">Seed Quantity</label>
          <input type="number" step="0.01" min="0.01" max="999999.99" class="form-control <?= isset($errors['seed_quantity']) ? 'is-invalid' : '' ?>"
                 id="seed_quantity" name="seed_quantity" required value="<?= e($data['seed_quantity']) ?>">
          <div class="invalid-feedback">Enter a quantity greater than 0.</div><?= field_error($errors, 'seed_quantity') ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Planting Record' ?></button>
        <a href="<?= e(url('planting/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
