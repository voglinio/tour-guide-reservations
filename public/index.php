
<?php
require __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user'])) { header('Location: /login.php'); exit; }
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tour Guide Reservations</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css" rel="stylesheet">
  <style>
    body { padding: 1rem; }
    .fc-toolbar-title { font-size: 1.2rem; }
    dialog form { display: grid; gap: .5rem; }
    .header { display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem; }
  </style>
</head>
<body>
<header class="header">
  <strong>Logged in as: <?= htmlspecialchars($user['display_name']) ?> (<?= htmlspecialchars($user['role']) ?>)</strong>
  <nav>
    <?php if ($user['role']==='admin'): ?>
      <a href="/users.php">Users</a>
    <?php endif; ?>
    <a href="/logout.php" role="button" class="secondary">Logout</a>
  </nav>
</header>

<main class="container">
  <div class="grid">
    <div><button id="newEventBtn">+ New Reservation</button></div>
    <div>
      <select id="viewSelect">
        <option value="dayGridMonth">Month</option>
        <option value="timeGridWeek" selected>Week</option>
        <option value="timeGridDay">Day</option>
        <option value="listWeek">List</option>
      </select>
    </div>
  </div>
  <div id="calendar"></div>
</main>

<dialog id="eventDialog">
  <article>
    <header><strong id="dlgTitle">New Reservation</strong></header>
    <form id="eventForm" method="dialog">
      <?php if ($user['role'] === 'admin'): ?>
      <label>Guide
        <select name="guide_id" id="guideSelect"></select>
      </label>
      <?php else: ?>
      <input type="hidden" name="guide_id" value="<?= (int)$user['id'] ?>">
      <?php endif; ?>
      <label>Tour group name
        <input name="group_name" required>
      </label>
      <label>Start
        <input type="datetime-local" name="start" required>
      </label>
      <label>End
        <input type="datetime-local" name="end" required>
      </label>
      <label>Notes
        <textarea name="notes" rows="3"></textarea>
      </label>
      <input type="hidden" name="id" value="">
      <footer>
        <button id="deleteBtn" class="secondary" type="button" style="display:none">Delete</button>
        <button type="submit" id="saveBtn">Save</button>
        <button class="secondary" type="button" onclick="document.getElementById('eventDialog').close()">Cancel</button>
      </footer>
    </form>
  </article>
</dialog>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>
<script>
const currentUser = <?= json_encode($user) ?>;
const calendarEl = document.getElementById('calendar');
const viewSelect = document.getElementById('viewSelect');
const eventDialog = document.getElementById('eventDialog');
const eventForm = document.getElementById('eventForm');
const deleteBtn = document.getElementById('deleteBtn');
const newEventBtn = document.getElementById('newEventBtn');
const dlgTitle = document.getElementById('dlgTitle');

const calendar = new FullCalendar.Calendar(calendarEl, {
  initialView: 'timeGridWeek',
  height: 'auto',
  headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
  slotDuration: '01:00:00',
  selectable: true,
  selectMirror: true,
  nowIndicator: true,
  events: { url: '/api/events.php', failure: () => alert('Failed loading events') },
  select: info => {
    openDialog({ id:'', group_name:'', start: info.startStr.substring(0,16), end: info.endStr.substring(0,16), notes:'' });
  },
  eventClick: info => {
    const e = info.event;
    openDialog({
      id: e.id,
      group_name: e.extendedProps.group_name,
      start: e.start.toISOString().slice(0,16),
      end: e.end ? e.end.toISOString().slice(0,16) : e.start.toISOString().slice(0,16),
      notes: e.extendedProps.notes || ''
    }, true);
  }
});
async function populateGuides(){
  try{
    const res = await fetch('/api/users.php');
    const users = await res.json();
    const sel = document.getElementById('guideSelect');
    if (!sel) return;
    sel.innerHTML = '';
    users.forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = `${u.display_name} (${u.username})`;
      sel.appendChild(opt);
    });
  }catch(e){ console.warn('Failed to load users', e); }
}
calendar.render();

viewSelect.addEventListener('change', () => calendar.changeView(viewSelect.value));
newEventBtn.addEventListener('click', () => {
  const now = new Date(); const end = new Date(now.getTime() + 60*60*1000);
  openDialog({ id:'', group_name:'', start: now.toISOString().slice(0,16), end: end.toISOString().slice(0,16), notes:'' });
});

function openDialog(data, isEdit=false) {
  eventForm.reset();
  dlgTitle.textContent = isEdit ? 'Edit Reservation' : 'New Reservation';
  for (const [k,v] of Object.entries(data)) {
    const el = eventForm.elements.namedItem(k);
    if (el) el.value = v;
  }
  deleteBtn.style.display = isEdit ? '' : 'none';
  populateGuides();
  eventDialog.showModal();
}

eventForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(eventForm);
  const id = formData.get('id');
  const payload = Object.fromEntries(formData.entries());
  try {
    if (id) {
      const params = new URLSearchParams(payload);
      const res = await fetch('/api/events.php', { method:'PUT', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() });
      const out = await res.json(); if (!res.ok) throw new Error(out.error || 'Update failed');
    } else {
      const res = await fetch('/api/events.php', { method:'POST', body: formData });
      const out = await res.json(); if (!res.ok) throw new Error(out.error || 'Create failed');
    }
    eventDialog.close(); calendar.refetchEvents();
  } catch (err) { alert(err.message); }
});

deleteBtn.addEventListener('click', async () => {
  const id = eventForm.elements.namedItem('id').value; if (!id) return;
  if (!confirm('Delete this reservation?')) return;
  const params = new URLSearchParams({id});
  const res = await fetch('/api/events.php', { method:'DELETE', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() });
  const out = await res.json(); if (!res.ok) { alert(out.error || 'Delete failed'); return; }
  eventDialog.close(); calendar.refetchEvents();
});
</script>
</body>
</html>
