<?php
require_once 'db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get system statistics
$stats = [];

// Database size
$result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size FROM information_schema.tables WHERE table_schema = 'srms_db1'");
$stats['db_size'] = $result->fetch_assoc()['db_size'] ?? 0;

// Recent activity
$result = $conn->query("SELECT COUNT(*) as count FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stats['recent_activity'] = $result->fetch_assoc()['count'];

// Active users
$result = $conn->query("SELECT COUNT(*) as count FROM user WHERE status = 'active'");
$stats['active_users'] = $result->fetch_assoc()['count'];

// System errors (last 24 hours)
$stats['system_errors'] = 0; // Would need error logging system

// Get recent audit logs
$auditQuery = "SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10";
$auditResult = $conn->query($auditQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Dashboard - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="system_dashboard.php">System Status</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>System Status Dashboard</h1>
            <p>Monitor system health and performance</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['db_size']; ?> MB</div>
                <div class="stat-label">Database Size</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['recent_activity']; ?></div>
                <div class="stat-label">Recent Activity (24h)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['system_errors']; ?></div>
                <div class="stat-label">System Errors</div>
            </div>
        </div>

        <div class="card">
            <h2>Recent Activity Log</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $auditResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                        <td><?php echo $log['user_role']; ?> (ID: <?php echo $log['user_id']; ?>)</td>
                        <td><?php echo $log['action']; ?></td>
                        <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td><?php echo $log['ip_address']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="action-buttons">
            <button onclick="window.location.href='optimize_database.php'" class="btn">🔧 Optimize Database</button>
            <button onclick="window.location.href='bulk_operations.php'" class="btn">📊 Bulk Operations</button>
            <button onclick="clearAuditLog()" class="btn btn-danger">🗑️ Clear Old Logs</button>
        </div>
    </div>

    <script>
        function clearAuditLog() {
            if (confirm('Clear audit logs older than 30 days?')) {
                fetch('clear_logs.php', {method: 'POST'})
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>