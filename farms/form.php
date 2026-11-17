<?php
// Shared by add.php and edit.php (not a page on its own).
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (empty($formEntry)) {
    redirect('farms/index.php');
}
$editing = $id !== null;
$farm = $editing ? Farm::find($id) : new Farm();
if (!$farm) {
    flash('danger', Farm::notFoundMessage());
    redirect('farms/index.php');
}
$errors = [];
if (!$editing && ctype_digit(get('farmer_id'))) {
    $farm->farmer_id = get('farmer_id'); // preselect when coming from a farmer page
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $farm->fillFromForm($_POST);
    $errors = $farm->validate();          // the class checks its own fields
    if (!$errors) {
        try {
            $farm->save();                // INSERT or UPDATE, audit fields and audit log
            flash('success', $farm->savedMessage(!$editing));
            redirect('farms/index.php');
        } catch (PDOException $e) {
            $errors['general'] = friendly_db_error($e);
        }
    }
}
$data = $farm->toArray();
$farmers = Farmer::options();
$soils = Lookup::options('soil_type');
$irrs = Lookup::options('irrigation_type');

$pageTitle = $editing ? 'Edit Farm' : 'Add Farm';
$active = 'farms';
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
          <label for="farmer_id" class="form-label required">Farmer (Owner)</label>
          <select class="form-select <?= isset($errors['farmer_id']) ? 'is-invalid' : '' ?>" id="farmer_id" name="farmer_id" required>
            <?= options($farmers, $data['farmer_id'], '-- Select farmer --') ?>
          </select>
          <div class="invalid-feedback">Select a farmer.</div><?= field_error($errors, 'farmer_id') ?>
        </div>
        <div class="col-md-6">
          <label for="farm_name" class="form-label required">Farm Name</label>
          <input type="text" class="form-control <?= isset($errors['farm_name']) ? 'is-invalid' : '' ?>" id="farm_name"
                 name="farm_name" maxlength="100" required value="<?= e($data['farm_name']) ?>">
          <div class="invalid-feedback">Farm name is required.</div><?= field_error($errors, 'farm_name') ?>
        </div>
        <div class="col-md-8">
          <label for="location" class="form-label required">Location</label>
          <input type="text" class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>" id="location"
                 name="location" maxlength="150" required value="<?= e($data['location']) ?>">
          <div class="invalid-feedback">Location is required.</div><?= field_error($errors, 'location') ?>
        </div>
        <div class="col-md-4">
          <label for="area_hectares" class="form-label required">Area (hectares)</label>
          <input type="number" step="0.01" min="0.01" max="10000" class="form-control <?= isset($errors['area_hectares']) ? 'is-invalid' : '' ?>"
                 id="area_hectares" name="area_hectares" required value="<?= e($data['area_hectares']) ?>">
          <div class="invalid-feedback">Enter an area between 0.01 and 10,000.</div><?= field_error($errors, 'area_hectares') ?>
        </div>
        <div class="col-md-6">
          <label for="soil_type_id" class="form-label required">Soil Type</label>
          <select class="form-select <?= isset($errors['soil_type_id']) ? 'is-invalid' : '' ?>" id="soil_type_id" name="soil_type_id" required>
            <?= options($soils, $data['soil_type_id'], '-- Select soil type --') ?>
          </select>
          <div class="invalid-feedback">Select a soil type.</div><?= field_error($errors, 'soil_type_id') ?>
        </div>
        <div class="col-md-6">
          <label for="irrigation_type_id" class="form-label required">Irrigation Type</label>
          <select class="form-select <?= isset($errors['irrigation_type_id']) ? 'is-invalid' : '' ?>" id="irrigation_type_id" name="irrigation_type_id" required>
            <?= options($irrs, $data['irrigation_type_id'], '-- Select irrigation type --') ?>
          </select>
          <div class="invalid-feedback">Select an irrigation type.</div><?= field_error($errors, 'irrigation_type_id') ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Farm' ?></button>
        <a href="<?= e(url('farms/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
