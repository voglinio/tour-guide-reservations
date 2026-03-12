
<?php
require __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') { http_response_code(403); echo "Forbidden"; exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Users</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <style> body{padding:1rem;} table{width:100%;} </style>
</head>
<body>
<header class="container">
  <nav>
    <ul><li><strong>User Management</strong></li></ul>
    <ul><li><a href="/">Calendar</a></li><li><a href="/logout.php" class="secondary">Logout</a></li></ul>
  </nav>
</header>

<main class="container">
  <article>
    <h3>Add New User</h3>
    <form id="addForm">
      <div class="grid">
        <label>Display name <input name="display_name" required></label>
        <label>Username <input name="username" required></label>
      </div>
      <div class="grid">
        <label>Password <input type="password" name="password" required></label>
        <label>Role
          <select name="role"><option value="guide" selected>guide</option><option value="admin">admin</option></select>
        </label>
      </div>
      <button type="submit">Create</button>
    </form>
  </article>

  <article>
    <h3>Existing Users</h3>
    <table id="usersTable">
      <thead><tr><th>ID</th><th>Display name</th><th>Username</th><th>Role</th><th>Created</th></tr></thead>
      <tbody></tbody>
    </table>
  </article>
</main>

<script>
async function fetchUsers(){
  const res = await fetch('/api/users.php');
  const rows = await res.json();
  const tbody = document.querySelector('#usersTable tbody');
  tbody.innerHTML = '';
  rows.forEach(r => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${r.id}</td><td>${r.display_name}</td><td>${r.username}</td><td>${r.role}</td><td>${r.created_at}</td>`;
    tbody.appendChild(tr);
  });
}
document.getElementById('addForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const res = await fetch('/api/users.php', { method:'POST', body: fd });
  const out = await res.json();
  if(!res.ok){ alert(out.error || 'Failed'); return; }
  e.target.reset(); fetchUsers();
});
fetchUsers();
</script>
</body>
</html>
