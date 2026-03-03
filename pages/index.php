<?php
session_start();
require_once '../db/config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "cig_system");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get organization count
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM organizations WHERE status = 'active'");
$org_count = mysqli_fetch_assoc($result);

// Get submission count
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM submissions");
$submission_count = mysqli_fetch_assoc($result);

// Get approval statistics
$result = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
    FROM submissions
");
$stats = mysqli_fetch_assoc($result);

$approval_rate = $stats['total'] > 0 
    ? round(($stats['approved'] / $stats['total']) * 100) 
    : 0;

// Fetch latest active announcement from DB
$result = mysqli_query($conn, "
    SELECT title, content, created_at 
    FROM announcements 
    WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 1
");
$announcement = mysqli_fetch_assoc($result);

// Fallback if none in DB
if (!$announcement) {
    $announcement = [
        'title'      => 'Welcome Announcement',
        'content'    => 'Welcome to the Admin Dashboard!',
        'created_at' => date('Y-m-d H:i:s')
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php $current_page = 'home'; ?>
<?php include 'navbar.php'; ?>

<div id="page-content" class="page-background">

  <!-- WELCOME SECTION -->
  <div class="welcome-section">
    <div class="welcome-content">
      <h1>Welcome to CIG</h1>
      <p class="welcome-subtitle">Council of Internal Governance</p>
      <p class="welcome-description">Manage submissions, reviews, and organizational governance with ease. Stay updated with the latest announcements and maintain transparency across all departments.</p>
    </div>
    <div class="welcome-stats">
      <div class="stat-item">
        <span class="stat-number"><?php echo $org_count['count']; ?></span>
        <span class="stat-label">Organizations</span>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?php echo number_format($submission_count['count']); ?></span>
        <span class="stat-label">Submissions</span>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?php echo $approval_rate; ?>%</span>
        <span class="stat-label">Approval Rate</span>
      </div>
    </div>
  </div>

  <!-- ANNOUNCEMENT BOARD -->
  <div class="announcement-board">
    <div class="announcement-board-inner">
      <div class="announcement-header">
        <div class="announcement-header-left">
          <div class="announcement-icon">
            <i class="fas fa-bell"></i>
          </div>
          <div class="announcement-header-text">
            <h3>Latest Announcements</h3>
            <span class="announcement-subtitle">Important updates and notices</span>
          </div>
        </div>
        <button class="edit-btn" onclick="editAnnouncement()">
          <i class="fas fa-edit"></i>
          <span>Edit</span>
        </button>
      </div>
      <div class="announcement-content" id="announcementContent">
        <h4 id="announcementTitle"><?php echo htmlspecialchars($announcement['title']); ?></h4>
        <p id="announcementText"><?php echo htmlspecialchars($announcement['content']); ?></p>
        <small id="announcementDate" style="color:#888;">
          Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
        </small>
      </div>
    </div>
  </div>

  <!-- ORGANIZATION VALUES SECTION -->
  <div class="values-section">
    <div class="values-container">
      <div class="value-card-new mission">
        <div class="card-image-header" style="background: linear-gradient(135deg, #1e90ff 0%, #00bfff 100%); position: relative; overflow: hidden;">
          <div class="hexagon-icon">
            <i class="fas fa-rocket" style="font-size: 48px; color: #1e90ff;"></i>
          </div>
        </div>
        <div class="card-title-section"><h3>MISSION</h3></div>
        <div class="card-description">
          <p>To strengthen the capability of organization through collaboration and active participation in school governance.</p>
        </div>
      </div>

      <div class="value-card-new vision">
        <div class="card-image-header" style="background: linear-gradient(135deg, #ff6b6b 0%, #ff1744 100%); position: relative; overflow: hidden;">
          <div class="hexagon-icon">
            <i class="fas fa-eye" style="font-size: 48px; color: #ff6b6b;"></i>
          </div>
        </div>
        <div class="card-title-section"><h3>VISION</h3></div>
        <div class="card-description">
          <p>A highly trusted organization committed to capacitating progressive communities.</p>
        </div>
      </div>

      <div class="value-card-new values">
        <div class="card-image-header" style="background: linear-gradient(135deg, #ff9500 0%, #ff6f00 100%); position: relative; overflow: hidden;">
          <div class="hexagon-icon">
            <i class="fas fa-heart" style="font-size: 48px; color: #ff9500;"></i>
          </div>
        </div>
        <div class="card-title-section"><h3>VALUES</h3></div>
        <div class="card-description">
          <ul style="list-style: none; padding: 0; text-align: left;">
            <li style="padding: 6px 0;"><strong>SERVICE</strong> - Dedicated to serving our communities</li>
            <li style="padding: 6px 0;"><strong>VOLUNTEERISM</strong> - Active participation and commitment</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <style>
    .values-section { margin: 60px 0; }
    .values-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; margin-bottom: 40px; }
    .value-card-new { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.12); transition: all 0.3s ease; display: flex; flex-direction: column; }
    .value-card-new:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    .card-image-header { height: 220px; display: flex; align-items: center; justify-content: center; }
    .hexagon-icon { width: 140px; height: 140px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .card-title-section { padding: 25px; text-align: center; border-bottom: 2px solid #f0f0f0; }
    .card-title-section h3 { font-size: 1.8em; font-weight: 800; margin: 0; color: #1a202c; letter-spacing: 1px; }
    .card-description { padding: 25px; flex-grow: 1; display: flex; align-items: center; }
    .card-description p { margin: 0; font-size: 0.95em; line-height: 1.7; color: #4a5568; text-align: center; }
    .card-description ul { font-size: 0.95em; line-height: 1.7; color: #4a5568; }
    @media (max-width: 768px) {
      .values-container { grid-template-columns: 1fr; gap: 30px; }
      .card-image-header { height: 180px; }
      .hexagon-icon { width: 110px; height: 110px; }
    }
  </style>

</div>

<!-- ANNOUNCEMENT MODAL -->
<div id="announcementModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h3>Edit Announcement</h3>
    <div id="saveSuccess" style="display:none; background:#d4edda; color:#155724; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
      <i class="fas fa-check-circle"></i> Announcement saved successfully!
    </div>
    <div id="saveError" style="display:none; background:#f8d7da; color:#721c24; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
      <i class="fas fa-exclamation-circle"></i> <span id="saveErrorMsg">Failed to save.</span>
    </div>
    <form id="announcementForm">
      <label style="display:block; margin-bottom:6px; font-weight:600;">Title</label>
      <input 
        type="text" 
        id="announcementTitleInput" 
        name="title"
        placeholder="Announcement title..."
        value="<?php echo htmlspecialchars($announcement['title']); ?>"
        style="width:100%; padding:10px; margin-bottom:14px; border:1px solid #ddd; border-radius:6px; font-size:14px; box-sizing:border-box;"
        required
      >
      <label style="display:block; margin-bottom:6px; font-weight:600;">Content</label>
      <textarea 
        id="announcementTextInput" 
        name="content"
        placeholder="Enter announcement text..."
        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:14px; min-height:120px; resize:vertical; box-sizing:border-box;"
        required
      ><?php echo htmlspecialchars($announcement['content']); ?></textarea>
      <div class="modal-buttons" style="margin-top:16px; display:flex; gap:10px;">
        <button type="submit" class="save-btn" id="saveAnnouncementBtn">
          <i class="fas fa-save"></i> Save
        </button>
        <button type="button" class="cancel-btn" onclick="closeAnnouncementModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
function editAnnouncement() {
  document.getElementById('announcementTitleInput').value = document.getElementById('announcementTitle').innerText;
  document.getElementById('announcementTextInput').value  = document.getElementById('announcementText').innerText;
  document.getElementById('saveSuccess').style.display    = 'none';
  document.getElementById('saveError').style.display      = 'none';
  document.getElementById('announcementModal').style.display = 'flex';
}

function closeAnnouncementModal() {
  document.getElementById('announcementModal').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('announcementModal');
  if (event.target === modal) modal.style.display = 'none';
}

document.getElementById('announcementForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const btn     = document.getElementById('saveAnnouncementBtn');
  const title   = document.getElementById('announcementTitleInput').value.trim();
  const content = document.getElementById('announcementTextInput').value.trim();

  if (!title || !content) return;

  btn.disabled  = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  const formData = new FormData();
  formData.append('title',   title);
  formData.append('content', content);

  fetch('../api/save_announcement.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      document.getElementById('announcementTitle').innerText = data.title;
      document.getElementById('announcementText').innerText  = data.content;
      document.getElementById('announcementDate').innerText  = 'Posted: ' + data.created_at;
      document.getElementById('saveSuccess').style.display   = 'block';
      document.getElementById('saveError').style.display     = 'none';
      setTimeout(closeAnnouncementModal, 1200);
    } else {
      document.getElementById('saveError').style.display  = 'block';
      document.getElementById('saveErrorMsg').innerText   = data.message || 'Failed to save.';
      document.getElementById('saveSuccess').style.display = 'none';
    }
  })
  .catch(() => {
    document.getElementById('saveError').style.display  = 'block';
    document.getElementById('saveErrorMsg').innerText   = 'Network error. Please try again.';
  })
  .finally(() => {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save';
  });
});
</script>

<!-- index.js removed — it conflicts with editAnnouncement() above -->
<script src="../js/navbar.js"></script>

</body>
</html>