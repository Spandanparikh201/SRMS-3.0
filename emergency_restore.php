<?php
// Emergency Database Restore Utility
// Minimal interface for quick database restoration

require_once 'db_connect.php';
require_once 'backup_settings.php';

$backupSettings = new BackupSettings();

if (!$backupSettings->isEmergencyRestoreEnabled()) {
    die('<h1>🚫 Emergency Restore Disabled</h1><p>Emergency restore has been disabled by the administrator.</p><a href="admin_dashboard.php">← Back to Dashboard</a>');
}

class EmergencyRestore {
    private $conn;
    private $backupDir = 'backups/';
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function getLatestCheckpoint() {
        $files = glob($this->backupDir . 'srms_checkpoint_*.sql');
        if (empty($files)) return null;
        
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return [
            'filename' => basename($files[0]),
            'filepath' => $files[0],
            'date' => date('Y-m-d H:i:s', filemtime($files[0])),
            'size' => filesize($files[0])
        ];
    }
    
    public function getLatestBackup() {
        $files = glob($this->backupDir . 'srms_full_backup_*.sql');
        if (empty($files)) return null;
        
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return [
            'filename' => basename($files[0]),
            'filepath' => $files[0],
            'date' => date('Y-m-d H:i:s', filemtime($files[0])),
            'size' => filesize($files[0])
        ];
    }
    
    public function quickRestore($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }
        
        $content = file_get_contents($filepath);
        if (!$content) {
            return ['success' => false, 'error' => 'Cannot read backup file'];
        }
        
        // Start transaction
        $this->conn->autocommit(false);
        
        try {
            // Disable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
            $this->conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
            
            // Execute SQL statements
            $statements = $this->splitSQL($content);
            $executed = 0;
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && substr($statement, 0, 2) !== '--' && substr($statement, 0, 2) !== '/*') {
                    if ($this->conn->query($statement)) {
                        $executed++;
                    } else {
                        throw new Exception("SQL Error: " . $this->conn->error);
                    }
                }
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Commit transaction
            $this->conn->commit();
            $this->conn->autocommit(true);
            
            return [
                'success' => true, 
                'message' => "Database restored successfully. Executed $executed SQL statements.",
                'statements_executed' => $executed
            ];
            
        } catch (Exception $e) {
            // Rollback on error
            $this->conn->rollback();
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $this->conn->autocommit(true);
            
            return ['success' => false, 'error' => 'Restore failed: ' . $e->getMessage()];
        }
    }
    
    private function splitSQL($sql) {
        // Simple SQL statement splitter
        $statements = [];
        $lines = explode("\n", $sql);
        $current = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 2) === '/*') {
                continue;
            }
            
            $current .= $line . "\n";
            
            // Check if statement ends with semicolon
            if (substr($line, -1) === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }
        
        // Add remaining statement if any
        if (trim($current)) {
            $statements[] = trim($current);
        }
        
        return $statements;
    }
    
    public function testConnection() {
        try {
            $result = $this->conn->query("SELECT 1");
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Handle requests
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$restore = new EmergencyRestore($conn);

if ($action === 'restore_latest_checkpoint') {
    $checkpoint = $restore->getLatestCheckpoint();
    if ($checkpoint) {
        $result = $restore->quickRestore($checkpoint['filepath']);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'No checkpoint found']);
    }
    exit;
}

if ($action === 'restore_latest_backup') {
    $backup = $restore->getLatestBackup();
    if ($backup) {
        $result = $restore->quickRestore($backup['filepath']);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'No backup found']);
    }
    exit;
}

if ($action === 'get_status') {
    $checkpoint = $restore->getLatestCheckpoint();
    $backup = $restore->getLatestBackup();
    $dbStatus = $restore->testConnection();
    
    echo json_encode([
        'database_connected' => $dbStatus,
        'latest_checkpoint' => $checkpoint,
        'latest_backup' => $backup
    ]);
    exit;
}

// Show emergency interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Restore - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        body { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .emergency-container { max-width: 800px; margin: 2rem auto; }
        .status-indicator { 
            display: inline-block; 
            width: 12px; 
            height: 12px; 
            border-radius: 50%; 
            margin-right: 8px; 
        }
        .status-online { background: #2ecc71; }
        .status-offline { background: #e74c3c; }
        .emergency-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        .restore-option {
            background: rgba(255,255,255,0.95);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1rem 0;
            border-left: 4px solid #3498db;
        }
        .restore-option.checkpoint { border-left-color: #f39c12; }
        .restore-option.backup { border-left-color: #2ecc71; }
    </style>
</head>
<body>
    <div class="emergency-container">
        <div class="emergency-warning">
            <h1>🚨 EMERGENCY DATABASE RESTORE</h1>
            <p>Use this interface only in case of database failure or corruption</p>
        </div>

        <div class="card">
            <h2>📊 System Status</h2>
            <div id="systemStatus">
                <p><span class="status-indicator status-offline"></span>Checking database connection...</p>
            </div>
        </div>

        <div class="card">
            <h2>⚡ Quick Restore Options</h2>
            <p><strong>Warning:</strong> These actions will replace ALL current data!</p>
            
            <div id="restoreOptions">
                <div class="restore-option checkpoint">
                    <h3>📍 Restore Latest Checkpoint</h3>
                    <p>Restore to the most recent automatic checkpoint</p>
                    <div id="checkpointInfo">Loading...</div>
                    <button onclick="restoreCheckpoint()" class="btn btn-warning" id="checkpointBtn" disabled>
                        🔄 Restore Latest Checkpoint
                    </button>
                </div>

                <div class="restore-option backup">
                    <h3>💾 Restore Latest Full Backup</h3>
                    <p>Restore to the most recent complete backup</p>
                    <div id="backupInfo">Loading...</div>
                    <button onclick="restoreBackup()" class="btn btn-success" id="backupBtn" disabled>
                        🔄 Restore Latest Backup
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>🔗 Other Options</h2>
            <div class="action-buttons">
                <a href="enhanced_backup_system.php" class="btn btn-secondary">📋 Full Backup System</a>
                <a href="admin_dashboard.php" class="btn btn-info">🏠 Admin Dashboard</a>
            </div>
        </div>

        <div id="progressModal" class="modal" style="display: none;">
            <div class="modal-content">
                <h2>🔄 Restoring Database...</h2>
                <p>Please wait while the database is being restored. Do not close this window.</p>
                <div style="text-align: center; padding: 2rem;">
                    <div style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        function loadStatus() {
            fetch('emergency_restore.php?action=get_status')
            .then(response => response.json())
            .then(data => {
                // Update database status
                const statusEl = document.getElementById('systemStatus');
                const dbStatus = data.database_connected ? 
                    '<span class="status-indicator status-online"></span>Database: Connected' :
                    '<span class="status-indicator status-offline"></span>Database: Disconnected';
                
                statusEl.innerHTML = `<p>${dbStatus}</p>`;
                
                // Update checkpoint info
                const checkpointEl = document.getElementById('checkpointInfo');
                const checkpointBtn = document.getElementById('checkpointBtn');
                
                if (data.latest_checkpoint) {
                    const cp = data.latest_checkpoint;
                    checkpointEl.innerHTML = `
                        <strong>File:</strong> ${cp.filename}<br>
                        <strong>Date:</strong> ${cp.date}<br>
                        <strong>Size:</strong> ${(cp.size/1024).toFixed(1)} KB
                    `;
                    checkpointBtn.disabled = false;
                } else {
                    checkpointEl.innerHTML = '<em>No checkpoint available</em>';
                    checkpointBtn.disabled = true;
                }
                
                // Update backup info
                const backupEl = document.getElementById('backupInfo');
                const backupBtn = document.getElementById('backupBtn');
                
                if (data.latest_backup) {
                    const bk = data.latest_backup;
                    backupEl.innerHTML = `
                        <strong>File:</strong> ${bk.filename}<br>
                        <strong>Date:</strong> ${bk.date}<br>
                        <strong>Size:</strong> ${(bk.size/1024).toFixed(1)} KB
                    `;
                    backupBtn.disabled = false;
                } else {
                    backupEl.innerHTML = '<em>No backup available</em>';
                    backupBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error loading status:', error);
                document.getElementById('systemStatus').innerHTML = 
                    '<p><span class="status-indicator status-offline"></span>Error checking status</p>';
            });
        }

        function restoreCheckpoint() {
            if (!confirm('🚨 EMERGENCY RESTORE\n\nThis will restore the database to the latest checkpoint and REPLACE ALL CURRENT DATA.\n\nAre you absolutely sure?')) {
                return;
            }
            
            showProgress();
            
            fetch('emergency_restore.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restore_latest_checkpoint'
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                if (data.success) {
                    alert('✅ Database restored successfully!\n\n' + data.message);
                    location.reload();
                } else {
                    alert('❌ Restore failed:\n\n' + data.error);
                }
            })
            .catch(error => {
                hideProgress();
                alert('❌ Network error during restore: ' + error.message);
            });
        }

        function restoreBackup() {
            if (!confirm('🚨 EMERGENCY RESTORE\n\nThis will restore the database to the latest full backup and REPLACE ALL CURRENT DATA.\n\nAre you absolutely sure?')) {
                return;
            }
            
            showProgress();
            
            fetch('emergency_restore.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restore_latest_backup'
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                if (data.success) {
                    alert('✅ Database restored successfully!\n\n' + data.message);
                    location.reload();
                } else {
                    alert('❌ Restore failed:\n\n' + data.error);
                }
            })
            .catch(error => {
                hideProgress();
                alert('❌ Network error during restore: ' + error.message);
            });
        }

        function showProgress() {
            document.getElementById('progressModal').style.display = 'block';
        }

        function hideProgress() {
            document.getElementById('progressModal').style.display = 'none';
        }

        // Load status on page load
        loadStatus();
        
        // Refresh status every 10 seconds
        setInterval(loadStatus, 10000);
    </script>
</body>
</html>