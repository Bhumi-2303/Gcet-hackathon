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
  <title>Sign In</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <main class="auth-container">
    <section class="card">
      <h2 class="form-title">Sign In</h2>
      <div class="logo"><span class="app-name" aria-hidden="true"><span class="app-name-main">Aurora</span><span class="app-name-accent">HQ</span></span></div>

      <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
      <?php endif; ?>
      <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <form action="auth/login.php" method="post" novalidate>
        <label class="field">
          <span class="label">Login Id / Email</span>
          <input type="email" name="email" required placeholder="you@example.com">
        </label>

        <label class="field">
          <span class="label">Password</span>
          <div class="password-wrap">
            <input type="password" name="password" required data-toggle-password placeholder="Enter password">
            <button type="button" class="btn-icon toggle-pass" aria-label="Show password">👁️</button>
          </div>
        </label>

        <button type="submit" class="btn-primary">SIGN IN</button>
      </form>

      <p class="muted left-note">Don't have an Account? <a href="signup.php" class="link-cta">Sign Up</a></p>
    </section>
  </main>
  <script src="assets/js/main.js"></script>
</body>
</html>