<?php
require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (loginAdmin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Rod Wealth Construction</title>
<link href="../assets/img/RodWealth-Favicon.png" rel="icon">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    font-family: 'Segoe UI', system-ui, sans-serif;
  }

  .login-wrapper {
    width: 100%;
    max-width: 420px;
    padding: 16px;
  }

  .logo {
    text-align: center;
    margin-bottom: 28px;
  }

  .logo img {
    max-height: 60px;
    width: auto;
    object-fit: contain;
    display: block;
    margin: 0 auto 10px;
  }

  .logo p {
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.3px;
  }

  .card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 36px 32px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
  }

  .card h2 {
    color: #0f172a;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 24px;
  }

  .form-group {
    margin-bottom: 18px;
  }

  label {
    display: block;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
  }

  input {
    width: 100%;
    padding: 11px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    color: #0f172a;
    font-size: 15px;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
  }

  input::placeholder { color: #cbd5e1; }

  input:focus {
    border-color: #14529d;
    box-shadow: 0 0 0 3px rgba(20, 82, 157, 0.1);
    background: #fff;
  }

  .error-msg {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #be123c;
    padding: 11px 14px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 18px;
  }

  .btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #14529d, #1a65c0);
    color: #fff;
    border: none;
    border-radius: 9px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
    margin-top: 6px;
    box-shadow: 0 3px 10px rgba(20, 82, 157, 0.3);
    letter-spacing: 0.2px;
  }

  .btn:hover { opacity: 0.88; }

  .back-link {
    text-align: center;
    margin-top: 20px;
  }

  .back-link a {
    color: #94a3b8;
    font-size: 13px;
    text-decoration: none;
    transition: color 0.15s;
  }

  .back-link a:hover { color: #64748b; }
</style>
</head>
<body>

<div class="login-wrapper">
  <div class="logo">
    <img src="../assets/img/RodWealth-NavLogo.png" alt="Rod Wealth Construction">
    <p>Admin Dashboard</p>
  </div>

  <div class="card">
    <h2>Sign In</h2>

    <?php if ($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn">Sign In →</button>
    </form>
  </div>

  <div class="back-link">
    <a href="../index.html">← Back to website</a>
  </div>
</div>

</body>
</html>