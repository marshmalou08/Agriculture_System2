<?php
// Shared by add.php and edit.php (not a page on its own).
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (empty($formEntry)) {
    redirect('crops/index.php');
}
$editing = $id !== null;
$crop = $editing ? Crop::find($id) : new Crop();
if (!$crop) {
    flash('danger', Crop::notFoundMessage());
    redirect('crops/index.php');
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $crop->fillFromForm($_POST);
    $errors = $crop->validate();          // the class checks its own fields
    if (!$errors) {
        try {
            $crop->save();                // INSERT or UPDATE, audit fields and audit log
            flash('success', $crop->savedMessage(!$editing));
            redirect('crops/index.php');
        } catch (PDOException $e) {
            $errors['general'] = friendly_db_error($e);
        }
    }
}
$data = $crop->toArray();
$categories = Lookup::options('crop_category');
$seasons = Lookup::options('season');

$pageTitle = $editing ? 'Edit Crop' : 'Add Crop';
$active = 'crops';
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
          <label for="crop_name" class="form-label required">Crop Name</label>
          <input type="text" class="form-control <?= isset($errors['crop_name']) ? 'is-invalid' : '' ?>" id="crop_name"
                 name="crop_name" maxlength="80" required value="<?= e($data['crop_name']) ?>">
          <div class="invalid-feedback">Crop name is required.</div><?= field_error($errors, 'crop_name') ?>
        </div>
        <div class="col-md-6">
          <label for="variety" class="form-label required">Variety</label>
          <input type="text" class="form-control <?= isset($errors['variety']) ? 'is-invalid' : '' ?>" id="variety"
                 name="variety" maxlength="80" required value="<?= e($data['variety']) ?>">
          <div class="invalid-feedback">Variety is required.</div><?= field_error($errors, 'variety') ?>
        </div>
        <div class="col-md-4">
          <label for="category_id" class="form-label required">Crop Category</label>
          <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" required>
            <?= options($categories, $data['category_id'], '-- Select category --') ?>
          </select>
          <div class="invalid-feedback">Select a category.</div><?= field_error($errors, 'category_id') ?>
        </div>
        <div class="col-md-4">
          <label for="growing_period_days" class="form-label required">Growing Period (days)</label>
          <input type="number" step="1" min="1" max="1500" class="form-control <?= isset($errors['growing_period_days']) ? 'is-invalid' : '' ?>"
                 id="growing_period_days" name="growing_period_days" required value="<?= e($data['growing_period_days']) ?>">
          <div class="invalid-feedback">Enter 1 to 1,500 days.</div><?= field_error($errors, 'growing_period_days') ?>
        </div>
        <div class="col-md-4">
          <label for="season_id" class="form-label required">Recommended Season</label>
          <select class="form-select <?= isset($errors['season_id']) ? 'is-invalid' : '' ?>" id="season_id" name="season_id" required>
            <?= options($seasons, $data['season_id'], '-- Select season --') ?>
          </select>
          <div class="invalid-feedback">Select a season.</div><?= field_error($errors, 'season_id') ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Crop' ?></button>
        <a href="<?= e(url('crops/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
