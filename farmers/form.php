<?php
// Shared by add.php and edit.php (not a page on its own).
require_once __DIR__ . '/../includes/functions.php';
require_login();
if (empty($formEntry)) {
    redirect('farmers/index.php');
}
$editing = $id !== null;
$farmer = $editing ? Farmer::find($id) : new Farmer();
if (!$farmer) {
    flash('danger', Farmer::notFoundMessage());
    redirect('farmers/index.php');
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $farmer->fillFromForm($_POST);
    $errors = $farmer->validate();          // the class checks its own fields
    if (!$errors) {
        try {
            $farmer->save();                // INSERT or UPDATE, audit fields and audit log
            flash('success', $farmer->savedMessage(!$editing));
            redirect('farmers/index.php');
        } catch (PDOException $e) {
            $errors['general'] = friendly_db_error($e);
        }
    }
}
$data = $farmer->toArray();

$pageTitle = $editing ? 'Edit Farmer' : 'Add Farmer';
$active = 'farmers';
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
          <label for="first_name" class="form-label required">First Name</label>
          <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" id="first_name"
                 name="first_name" maxlength="50" required value="<?= e($data['first_name']) ?>">
          <div class="invalid-feedback">First name is required.</div><?= field_error($errors, 'first_name') ?>
        </div>
        <div class="col-md-6">
          <label for="last_name" class="form-label required">Last Name</label>
          <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" id="last_name"
                 name="last_name" maxlength="50" required value="<?= e($data['last_name']) ?>">
          <div class="invalid-feedback">Last name is required.</div><?= field_error($errors, 'last_name') ?>
        </div>
        <div class="col-md-6">
          <label for="contact_number" class="form-label required">Contact Number</label>
          <input type="text" class="form-control <?= isset($errors['contact_number']) ? 'is-invalid' : '' ?>" id="contact_number"
                 name="contact_number" maxlength="20" required pattern="[0-9+() \-]{7,20}" placeholder="09171234567"
                 value="<?= e($data['contact_number']) ?>">
          <div class="invalid-feedback">Enter a valid contact number.</div><?= field_error($errors, 'contact_number') ?>
        </div>
        <div class="col-md-6">
          <label for="date_registered" class="form-label required">Date Registered</label>
          <input type="date" class="form-control <?= isset($errors['date_registered']) ? 'is-invalid' : '' ?>" id="date_registered"
                 name="date_registered" max="<?= e(date('Y-m-d')) ?>" required value="<?= e($data['date_registered']) ?>">
          <div class="invalid-feedback">Enter a valid date (not in the future).</div><?= field_error($errors, 'date_registered') ?>
        </div>
        <div class="col-12">
          <label for="address" class="form-label required">Address</label>
          <input type="text" class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address"
                 name="address" maxlength="150" required value="<?= e($data['address']) ?>">
          <div class="invalid-feedback">Address is required.</div><?= field_error($errors, 'address') ?>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Add Farmer' ?></button>
        <a href="<?= e(url('farmers/index.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
