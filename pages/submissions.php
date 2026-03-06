<?php
/**
 * CIG Admin Dashboard - Submissions Page
 */

session_start();
require_once '../db/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$db   = new Database();
$user = ['full_name' => $_SESSION['admin_email'] ?? 'Admin'];

$search_query = $_GET['search'] ?? '';

$query  = "
    SELECT s.*, u.full_name as submitted_by_name,
           COALESCE(u.org_name, o.org_name) as org_name
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.user_id
    LEFT JOIN users o ON s.org_id = o.user_id
    WHERE s.status IN ('pending', 'in_review')
";
$params = [];

if ($search_query) {
    $query   .= " AND (s.title LIKE ? OR u.org_name LIKE ? OR o.org_name LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}
$query .= " ORDER BY s.submitted_at DESC";

try {
    $submissions = $db->fetchAll($query, $params);
} catch (Exception $e) {
    error_log('Submissions Error: ' . $e->getMessage());
    $submissions = [];
}


// Helper: safely build the openPreviewModal() onclick attribute
function previewOnclick($s) {
    $id     = (int) $s['submission_id'];
    $ext    = strtolower(pathinfo($s['file_name'] ?? '', PATHINFO_EXTENSION));
    $title  = addslashes(strip_tags($s['title']));
    $status = $s['status'];
    return "openPreviewModal({$id},'{$ext}','{$title}','{$status}')";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submissions - Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/components.css">
<link rel="stylesheet" href="../css/submissions.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php
$current_page = 'submissions';
$user_name    = $user['full_name'] ?? '';
?>
<?php include 'navbar.php'; ?>

  <!-- SUBMISSIONS PAGE -->
  <div class="page active">
    <div class="page-header">
      <h2><i class="fas fa-file-alt"></i> Submissions</h2>
    </div>

    <div class="search-filter-container">
      <form method="GET" class="search-filter-form">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input type="text" name="search" placeholder="Search submissions..."
                 value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
        </div>
      </form>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> Ref No</th>
            <th><i class="fas fa-building"></i> Organization</th>
            <th><i class="fas fa-file-alt"></i> Title & Type</th>
            <th><i class="fas fa-tag"></i> Status</th>
            <th><i class="fas fa-user"></i> Submitted By</th>
            <th><i class="fas fa-calendar"></i> Date & Time</th>
            <th><i class="fas fa-cog"></i> Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($submissions)): ?>
            <?php foreach ($submissions as $index => $sub): ?>
              <tr>
                <td class="ref-number">#<?php echo str_pad($index + 1, 3, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo htmlspecialchars($sub['org_name'] ?? 'N/A'); ?></td>
                <td class="title-cell">
                  <?php
                    $ext = strtolower(pathinfo($sub['file_name'] ?? '', PATHINFO_EXTENSION));
                    $badgeMap = [
                      'pdf'  => ['PDF',  '#e74c3c'],
                      'docx' => ['DOCX', '#2980b9'],
                      'doc'  => ['DOC',  '#2980b9'],
                      'xlsx' => ['XLSX', '#27ae60'],
                      'xls'  => ['XLS',  '#27ae60'],
                    ];
                    $iconMap = [
                      'pdf'  => 'fa-file-pdf',
                      'docx' => 'fa-file-word',
                      'doc'  => 'fa-file-word',
                      'xlsx' => 'fa-file-excel',
                      'xls'  => 'fa-file-excel',
                    ];
                    [$badgeLabel, $badgeColor] = $badgeMap[$ext] ?? [strtoupper($ext) ?: '—', '#7f8c8d'];
                    $iconClass = $iconMap[$ext] ?? 'fa-file-alt';
                  ?>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <i class="fas <?php echo $iconClass; ?>"
                       style="font-size:1.3rem;color:<?php echo $badgeColor; ?>;flex-shrink:0;"></i>
                    <div>
                      <strong><?php echo htmlspecialchars($sub['title']); ?></strong>
                      <div style="margin-top:3px;">
                        <span style="background:<?php echo $badgeColor; ?>;color:#fff;
                                     font-size:.68rem;font-weight:700;padding:2px 7px;
                                     border-radius:4px;letter-spacing:.04em;">
                          <?php echo $badgeLabel; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="status-badge <?php echo strtolower($sub['status']); ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars($sub['submitted_by_name'] ?? 'N/A'); ?></td>
                <td>
                  <div><?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?></div>
                  <small style="color:#888;font-size:.78rem;">
                    <i class="far fa-clock"></i>
                    <?php echo date('h:i A', strtotime($sub['submitted_at'])); ?>
                  </small>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-action btn-view"
                            onclick="<?php echo previewOnclick($sub); ?>">
                      <i class="fas fa-eye"></i> Preview
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="empty-row">No submissions found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include 'footer.php'; ?>
</div><!-- /.wrapper -->


<!-- ══════════════════════════════════════════════════════════
     DOCUMENT PREVIEW MODAL
     ══════════════════════════════════════════════════════════ -->
<div id="previewModal"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(0,0,0,.6);align-items:center;justify-content:center;">

  <div style="background:#fff;border-radius:10px;width:92vw;max-width:1060px;
              height:90vh;display:flex;flex-direction:column;overflow:hidden;
              box-shadow:0 8px 40px rgba(0,0,0,.4);">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:13px 18px;background:#047857;color:#fff;flex-shrink:0;gap:12px;">

      <!-- File icon + title -->
      <div style="display:flex;align-items:center;gap:9px;min-width:0;flex:1;">
        <i id="previewFileIcon" class="fas fa-file-alt" style="font-size:1.1rem;flex-shrink:0;"></i>
        <span id="previewTitle"
              style="font-size:.95rem;font-weight:600;overflow:hidden;
                     text-overflow:ellipsis;white-space:nowrap;"></span>
      </div>

      <!-- Approve / Reject -->
      <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
        <button id="modalApproveBtn" class="modal-action-btn">
          <i class="fas fa-check"></i> Approve
        </button>
        <button id="modalRejectBtn" class="modal-action-btn">
          <i class="fas fa-times"></i> Reject
        </button>
      </div>

      <!-- Close -->
      <button onclick="closePreviewModal()"
              style="background:none;border:none;color:#fff;font-size:1.5rem;
                     cursor:pointer;line-height:1;flex-shrink:0;">&times;</button>
    </div>

    <!-- Loading spinner -->
    <div id="previewLoading"
         style="display:flex;flex-direction:column;align-items:center;
                justify-content:center;flex:1;gap:12px;color:#666;">
      <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#047857;"></i>
      <span id="previewLoadingMsg" style="font-size:.9rem;">Loading document&hellip;</span>
    </div>

    <!-- Error state -->
    <div id="previewError"
         style="display:none;flex-direction:column;align-items:center;
                justify-content:center;flex:1;gap:10px;color:#c0392b;">
      <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
      <span id="previewErrorMsg"
            style="font-size:.9rem;text-align:center;max-width:400px;"></span>
    </div>

    <!-- PDF iframe -->
    <iframe id="previewPdfFrame"
            style="display:none;flex:1;border:none;width:100%;"></iframe>

    <!-- DOCX / XLSX rendered output -->
    <div id="previewDocxWrap"
         style="display:none;flex:1;overflow:auto;"></div>

  </div>
</div><!-- /#previewModal -->


<script src="../js/navbar.js"></script>
<script src="../js/submissions.js"></script>

<script>
function toggleNotificationPanel() {
    var panel = document.getElementById('notificationPanel');
    if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>