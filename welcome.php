<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: signin.php?error=' . urlencode('Please sign in first.'));
  exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Welcome</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <main class="auth-container">
    <section class="card profile-card">
      <div class="profile-header">
        <?php if (!empty($user['logo'])): ?>
          <img src="<?php echo htmlspecialchars($user['logo']); ?>" alt="Company logo" class="company-logo">
        <?php endif; ?>
        <div>
          <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
          <p class="muted"><?php echo htmlspecialchars($user['company']); ?></p>
        </div>
      </div>
      <div class="profile-body">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><a href="auth/logout.php" class="btn-link">Logout</a></p>
      </div>
    </section>
  </main>
</body>
</html>