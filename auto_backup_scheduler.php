<?php
// Auto Backup Scheduler for SRMS
// This script can be run via cron job or Windows Task Scheduler

require_once 'db_connect.php';
require_once 'enhanced_backup_system.php';
require_once 'backup_settings.php';

$backupSettings = new BackupSettings();

class AutoBackupScheduler {
    private $backup;
    private $logFile = 'backups/backup_log.txt';
    
    public function __construct($connection) {
        $this->backup = new DatabaseBackup($connection);
    }
    
    public function runScheduledBackup() {
        global $backupSettings;
        
        if (!$backupSettings->isAutoBackupEnabled()) {
            $this->log("⏸️ Auto backup is disabled - skipping checkpoint");
            return false;
        }
        
        $this->log("Starting scheduled backup at " . date('Y-m-d H:i:s'));
        
        try {
            // Create checkpoint with timestamp
            $description = "Auto-checkpoint " . date('Y-m-d H:i:s');
            $result = $this->backup->createCheckpoint($description);
            
            if ($result['success']) {
                $this->log("✅ Checkpoint created successfully: " . $result['filename']);
                
                // Clean old backups (keep last 10)
                $this->cleanOldBackups();
                
                return true;
            } else {
                $this->log("❌ Failed to create checkpoint: " . $result['error']);
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("❌ Exception during backup: " . $e->getMessage());
            return false;
        }
    }
    
    public function createWeeklyBackup() {
        global $backupSettings;
        
        if (!$backupSettings->isAutoBackupEnabled()) {
            $this->log("⏸️ Auto backup is disabled - skipping weekly backup");
            return false;
        }
        
        $this->log("Creating weekly full backup at " . date('Y-m-d H:i:s'));
        
        try {
            $result = $this->backup->createFullBackup(true);
            
            if ($result['success']) {
                $this->log("✅ Weekly backup created: " . $result['filename']);
                return true;
            } else {
                $this->log("❌ Failed to create weekly backup: " . $result['error']);
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("❌ Exception during weekly backup: " . $e->getMessage());
            return false;
        }
    }
    
    private function cleanOldBackups() {
        $backups = $this->backup->getBackupList();
        $checkpoints = array_filter($backups, function($b) { return $b['type'] === 'checkpoint'; });
        
        if (count($checkpoints) > 10) {
            // Sort by date and remove oldest
            usort($checkpoints, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });
            
            $toDelete = array_slice($checkpoints, 0, count($checkpoints) - 10);
            
            foreach ($toDelete as $backup) {
                if (unlink($backup['filepath'])) {
                    $this->log("🗑️ Deleted old checkpoint: " . $backup['filename']);
                    
                    // Also delete metadata file if exists
                    $metaFile = str_replace('.sql', '.json', $backup['filepath']);
                    if (file_exists($metaFile)) {
                        unlink($metaFile);
                    }
                }
            }
        }
    }
    
    private function log($message) {
        $logEntry = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry; // Also output to console
    }
    
    public function getBackupLog() {
        if (file_exists($this->logFile)) {
            return file_get_contents($this->logFile);
        }
        return "No log file found.";
    }
}

// Check if running from command line or web
if (php_sapi_name() === 'cli') {
    // Command line execution
    $scheduler = new AutoBackupScheduler($conn);
    
    $action = $argv[1] ?? 'checkpoint';
    
    switch ($action) {
        case 'checkpoint':
            $scheduler->runScheduledBackup();
            break;
        case 'weekly':
            $scheduler->createWeeklyBackup();
            break;
        default:
            echo "Usage: php auto_backup_scheduler.php [checkpoint|weekly]\n";
    }
} else {
    // Web interface for manual execution and log viewing
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Unauthorized access');
    }
    
    $scheduler = new AutoBackupScheduler($conn);
    $action = $_GET['action'] ?? '';
    
    if ($action === 'run_checkpoint') {
        header('Content-Type: application/json');
        $result = $scheduler->runScheduledBackup();
        echo json_encode(['success' => $result]);
        exit;
    }
    
    if ($action === 'run_weekly') {
        header('Content-Type: application/json');
        $result = $scheduler->createWeeklyBackup();
        echo json_encode(['success' => $result]);
        exit;
    }
    
    if ($action === 'get_log') {
        header('Content-Type: text/plain');
        echo $scheduler->getBackupLog();
        exit;
    }
    
    // Show web interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Auto Backup Scheduler - SRMS</title>
        <link rel="stylesheet" href="assets/css/iris-design-system.css">
        <style>
            .log-container { 
                background: #f8f9fa; 
                padding: 1rem; 
                border-radius: 8px; 
                font-family: monospace; 
                white-space: pre-wrap; 
                max-height: 400px; 
                overflow-y: auto; 
            }
            .schedule-info {
                background: linear-gradient(135deg, #e3f2fd, #bbdefb);
                padding: 1.5rem;
                border-radius: 12px;
                margin: 1rem 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="welcome-banner">
                <h1>⏰ Auto Backup Scheduler</h1>
                <p>Automated backup scheduling and monitoring</p>
                <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>

            <div class="card">
                <h2>📋 Backup Schedule Setup</h2>
                <div class="schedule-info">
                    <h3>🔄 Recommended Schedule:</h3>
                    <ul>
                        <li><strong>Checkpoints:</strong> Every 4 hours during business hours</li>
                        <li><strong>Full Backups:</strong> Weekly (Sunday nights)</li>
                        <li><strong>Retention:</strong> Keep last 10 checkpoints, all weekly backups</li>
                    </ul>
                    
                    <h3>⚙️ Windows Task Scheduler Setup:</h3>
                    <p><strong>For Checkpoints (every 4 hours):</strong></p>
                    <code>php "<?php echo realpath(__FILE__); ?>" checkpoint</code>
                    
                    <p><strong>For Weekly Backups (Sundays):</strong></p>
                    <code>php "<?php echo realpath(__FILE__); ?>" weekly</code>
                </div>
            </div>

            <div class="card">
                <h2>🎮 Manual Controls</h2>
                <div class="action-buttons">
                    <button onclick="runCheckpoint()" class="btn">📍 Run Checkpoint Now</button>
                    <button onclick="runWeekly()" class="btn btn-secondary">💾 Run Weekly Backup</button>
                    <button onclick="refreshLog()" class="btn btn-info">🔄 Refresh Log</button>
                    <a href="backup_settings.php" class="btn btn-warning">⚙️ Backup Settings</a>
                </div>
            </div>

            <div class="card">
                <h2>📊 Backup Log</h2>
                <div id="logContainer" class="log-container">Loading log...</div>
            </div>
        </div>

        <script>
            function runCheckpoint() {
                fetch('auto_backup_scheduler.php?action=run_checkpoint')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Checkpoint created successfully!');
                        refreshLog();
                    } else {
                        alert('❌ Failed to create checkpoint');
                    }
                });
            }

            function runWeekly() {
                fetch('auto_backup_scheduler.php?action=run_weekly')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Weekly backup created successfully!');
                        refreshLog();
                    } else {
                        alert('❌ Failed to create weekly backup');
                    }
                });
            }

            function refreshLog() {
                fetch('auto_backup_scheduler.php?action=get_log')
                .then(response => response.text())
                .then(log => {
                    document.getElementById('logContainer').textContent = log;
                    // Scroll to bottom
                    const container = document.getElementById('logContainer');
                    container.scrollTop = container.scrollHeight;
                });
            }

            // Load log on page load and refresh every 30 seconds
            refreshLog();
            setInterval(refreshLog, 30000);
        </script>
    </body>
    </html>
    <?php
}
?>