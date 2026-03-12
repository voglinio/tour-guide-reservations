
<?php
require __DIR__ . '/../config/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'role' => $user['role'],
        ];
        header("Location: /");
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Tour Guide Reservations</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
  <main class="container">
    <article>
      <h1>Login</h1>
      <?php if ($error): ?><mark><?= htmlspecialchars($error) ?></mark><?php endif; ?>
      <form method="post">
        <label>Username <input name="username" required></label>
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Sign in</button>
      </form>
    </article>
  </main>
</body>
</html>
