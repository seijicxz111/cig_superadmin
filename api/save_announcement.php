<?php
session_start();
header('Content-Type: application/json');

// Session guard — admin only
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// db config is at cig_superadmin/db/config.php
require_once __DIR__ . '/../db/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$title   = trim($_POST['title']   ?? '');
$content = trim($_POST['content'] ?? '');

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and content are required']);
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? 1;

// Deactivate all existing announcements
mysqli_query($conn, "UPDATE announcements SET is_active = 0");

// Insert the new one
$stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, content, created_by, is_active) VALUES (?, ?, ?, 1)");
mysqli_stmt_bind_param($stmt, 'ssi', $title, $content, $admin_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success'    => true,
        'message'    => 'Announcement saved successfully',
        'title'      => htmlspecialchars($title),
        'content'    => htmlspecialchars($content),
        'created_at' => date('M d, Y')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>