<?php
session_start();
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign Up</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <main class="auth-container">
    <section class="card card-wide">
      <h2 class="form-title">Sign Up</h2>
      <div class="logo"><span class="app-name" aria-hidden="true"><span class="app-name-main">Aurora</span><span class="app-name-accent">HQ</span></span></div>

      <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>
      <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <form action="auth/register.php" method="post" enctype="multipart/form-data" novalidate>
        <div class="form-grid">
          <label class="field">
            <span class="label">Company Name <span class="required">*</span></span>
            <input type="text" name="company" required placeholder="Your company">
          </label>

          <label class="field upload-field">
            <span class="label">Logo <span class="required">*</span></span>
            <div class="drop-zone" id="logoDropZone" tabindex="0" aria-label="Upload company logo">
              <div class="upload-preview" id="logoPreview"></div>
              <div class="drop-info" id="logoDropInfo"><strong>Drag & drop</strong><span class="muted"> or click to upload a logo</span></div>
              <input type="file" name="logo" id="logoInput" accept="image/*" class="hidden-file" required>
            </div>
          </label>

          <label class="field">
            <span class="label">Name <span class="required">*</span></span>
            <input type="text" name="name" required placeholder="Full name">
          </label>

          <label class="field">
            <span class="label">Email <span class="required">*</span></span>
            <input type="email" name="email" required placeholder="you@example.com">
          </label>

          <label class="field">
            <span class="label">Phone <span class="required">*</span></span>
            <input type="tel" name="phone" required placeholder="Phone number">
          </label>

          <label class="field">
            <span class="label">Password <span class="required">*</span></span>
            <div class="password-wrap">
              <input type="password" name="password" required data-toggle-password placeholder="Create a password">
              <button type="button" class="btn-icon toggle-pass" aria-label="Show password">👁️</button>
            </div>
          </label>

          <label class="field full">
            <span class="label">Confirm Password <span class="required">*</span></span>
            <div class="password-wrap">
              <input type="password" name="confirm_password" required data-toggle-password placeholder="Confirm password">
              <button type="button" class="btn-icon toggle-pass" aria-label="Show password">👁️</button>
            </div>
          </label>
        </div>

        <button type="submit" class="btn-primary">Sign Up</button>
      </form>

      <p class="muted left-note">Already have an account? <a href="signin.php" class="link-cta">Sign In</a></p>
    </section>
  </main>
  <script src="assets/js/main.js"></script>
</body>
</html>