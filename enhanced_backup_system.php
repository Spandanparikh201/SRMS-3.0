<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

class DatabaseBackup {
    private $conn;
    private $backupDir = 'backups/';
    
    public function __construct($connection) {
        $this->conn = $connection;
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function createFullBackup($includeData = true) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "srms_full_backup_{$timestamp}.sql";
        $filepath = $this->backupDir . $filename;
        
        $backup = $this->generateBackupSQL($includeData);
        
        if (file_put_contents($filepath, $backup)) {
            return ['success' => true, 'filename' => $filename, 'path' => $filepath];
        }
        return ['success' => false, 'error' => 'Failed to create backup file'];
    }
    
    public function createCheckpoint($description = '') {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "srms_checkpoint_{$timestamp}.sql";
        $filepath = $this->backupDir . $filename;
        
        $backup = $this->generateBackupSQL(true, $description);
        
        if (file_put_contents($filepath, $backup)) {
            // Also create a metadata file
            $metadata = [
                'type' => 'checkpoint',
                'created' => date('Y-m-d H:i:s'),
                'description' => $description,
                'tables_count' => $this->getTablesCount(),
                'total_records' => $this->getTotalRecords()
            ];
            file_put_contents($this->backupDir . "checkpoint_{$timestamp}.json", json_encode($metadata, JSON_PRETTY_PRINT));
            
            return ['success' => true, 'filename' => $filename, 'path' => $filepath];
        }
        return ['success' => false, 'error' => 'Failed to create checkpoint'];
    }
    
    private function generateBackupSQL($includeData = true, $description = '') {
        $backup = "-- =====================================================\n";
        $backup .= "-- SRMS Database Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $backup .= "-- Database: srms_db\n";
        if ($description) {
            $backup .= "-- Description: $description\n";
        }
        $backup .= "-- =====================================================\n\n";
        
        $backup .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $backup .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $backup .= "SET AUTOCOMMIT = 0;\n";
        $backup .= "START TRANSACTION;\n\n";
        
        // Get all tables in correct order (respecting foreign keys)
        $tables = ['school', 'user', 'class', 'subject', 'teacher', 'student', 'teacher_class_subject', 'exam', 'examresult'];
        
        foreach ($tables as $table) {
            $backup .= $this->backupTable($table, $includeData);
        }
        
        $backup .= "COMMIT;\n";
        $backup .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $backup .= "-- Backup completed successfully\n";
        
        return $backup;
    }
    
    private function backupTable($table, $includeData = true) {
        $backup = "\n-- =====================================================\n";
        $backup .= "-- Table structure for `$table`\n";
        $backup .= "-- =====================================================\n\n";
        
        // Drop table if exists
        $backup .= "DROP TABLE IF EXISTS `$table`;\n\n";
        
        // Get table structure
        $result = $this->conn->query("SHOW CREATE TABLE `$table`");
        if ($result && $row = $result->fetch_assoc()) {
            $backup .= $row['Create Table'] . ";\n\n";
        }
        
        if ($includeData) {
            $backup .= "-- =====================================================\n";
            $backup .= "-- Dumping data for table `$table`\n";
            $backup .= "-- =====================================================\n\n";
            
            $result = $this->conn->query("SELECT * FROM `$table`");
            if ($result && $result->num_rows > 0) {
                $backup .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                
                while ($row = $result->fetch_assoc()) {
                    $values = array_map(function($value) {
                        if ($value === null) return 'NULL';
                        return "'" . $this->conn->real_escape_string($value) . "'";
                    }, array_values($row));
                    
                    $rows[] = "(" . implode(', ', $values) . ")";
                }
                
                $backup .= implode(",\n", $rows) . ";\n\n";
            } else {
                $backup .= "-- No data found for table `$table`\n\n";
            }
        }
        
        return $backup;
    }
    
    public function restoreFromFile($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }
        
        $content = file_get_contents($filepath);
        if (!$content) {
            return ['success' => false, 'error' => 'Failed to read backup file'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Disable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Split SQL into statements
            $statements = $this->splitSQL($content);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && substr($statement, 0, 2) !== '--') {
                    if (!$this->conn->query($statement)) {
                        throw new Exception("SQL Error: " . $this->conn->error . " in statement: " . substr($statement, 0, 100));
                    }
                }
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Database restored successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            return ['success' => false, 'error' => 'Restore failed: ' . $e->getMessage()];
        }
    }
    
    private function splitSQL($sql) {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && $sql[$i-1] !== '\\') {
                $inString = false;
            }
            
            if (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (trim($current)) {
            $statements[] = $current;
        }
        
        return $statements;
    }
    
    public function getBackupList() {
        $backups = [];
        
        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filepath = $this->backupDir . $file;
                    $metadata = null;
                    
                    // Check for metadata file
                    $metaFile = str_replace('.sql', '.json', $filepath);
                    if (file_exists($metaFile)) {
                        $metadata = json_decode(file_get_contents($metaFile), true);
                    }
                    
                    $backups[] = [
                        'filename' => $file,
                        'filepath' => $filepath,
                        'size' => filesize($filepath),
                        'date' => date('Y-m-d H:i:s', filemtime($filepath)),
                        'type' => strpos($file, 'checkpoint') !== false ? 'checkpoint' : 'backup',
                        'metadata' => $metadata
                    ];
                }
            }
            
            // Sort by date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
        
        return $backups;
    }
    
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        return true;
    }
    
    private function getTablesCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'srms_db'");
        return $result ? $result->fetch_assoc()['count'] : 0;
    }
    
    private function getTotalRecords() {
        $tables = ['school', 'user', 'class', 'subject', 'teacher', 'student', 'teacher_class_subject', 'exam', 'examresult'];
        $total = 0;
        
        foreach ($tables as $table) {
            $result = $this->conn->query("SELECT COUNT(*) as count FROM `$table`");
            if ($result) {
                $total += $result->fetch_assoc()['count'];
            }
        }
        
        return $total;
    }
}

// Handle AJAX requests
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$backup = new DatabaseBackup($conn);

switch ($action) {
    case 'create_backup':
        $result = $backup->createFullBackup(true);
        echo json_encode($result);
        break;
        
    case 'create_checkpoint':
        $description = $_POST['description'] ?? 'Manual checkpoint';
        $result = $backup->createCheckpoint($description);
        echo json_encode($result);
        break;
        
    case 'restore':
        if (isset($_FILES['backup_file'])) {
            $uploadedFile = $_FILES['backup_file']['tmp_name'];
            $result = $backup->restoreFromFile($uploadedFile);
        } else {
            $result = ['success' => false, 'error' => 'No backup file provided'];
        }
        echo json_encode($result);
        break;
        
    case 'restore_from_server':
        $filename = $_POST['filename'] ?? '';
        $filepath = 'backups/' . $filename;
        $result = $backup->restoreFromFile($filepath);
        echo json_encode($result);
        break;
        
    case 'list':
        echo json_encode($backup->getBackupList());
        break;
        
    case 'download':
        $filename = $_GET['filename'] ?? '';
        if (!$backup->downloadBackup($filename)) {
            echo "File not found";
        }
        break;
        
    default:
        showInterface();
}

function showInterface() {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Backup System - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        .backup-item { border: 1px solid #ddd; padding: 1rem; margin: 0.5rem 0; border-radius: 8px; }
        .checkpoint { border-left: 4px solid #f39c12; }
        .backup { border-left: 4px solid #2ecc71; }
        .backup-actions { margin-top: 0.5rem; }
        .backup-meta { font-size: 0.9rem; color: #666; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-banner">
            <h1>🔄 Enhanced Backup & Restore System</h1>
            <p>Create checkpoints, full backups, and restore your database safely</p>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="card">
            <h2>🎯 Create Checkpoint</h2>
            <p>Create a named checkpoint that you can restore to later</p>
            <div class="form-group">
                <input type="text" id="checkpointDesc" placeholder="Checkpoint description (optional)" class="form-control">
            </div>
            <button onclick="createCheckpoint()" class="btn">📍 Create Checkpoint</button>
        </div>

        <div class="card">
            <h2>💾 Full Database Backup</h2>
            <p>Create a complete backup of all data and structure</p>
            <button onclick="createBackup()" class="btn btn-secondary">💾 Create Full Backup</button>
        </div>

        <div class="card">
            <h2>📤 Restore Database</h2>
            <div class="tabs">
                <div class="tab active" onclick="showRestoreTab('upload')">Upload File</div>
                <div class="tab" onclick="showRestoreTab('server')">From Server</div>
            </div>
            
            <div id="uploadRestore" class="section active">
                <form id="restoreForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="file" name="backup_file" accept=".sql" required class="form-control">
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ This will replace ALL current data! Continue?')">
                        ⚠️ Restore from Upload
                    </button>
                </form>
            </div>
            
            <div id="serverRestore" class="section">
                <p>Select a backup from the server to restore:</p>
                <div id="serverBackupList">Loading...</div>
            </div>
        </div>

        <div class="card">
            <h2>📋 Available Backups</h2>
            <div id="backupList">Loading...</div>
        </div>
    </div>

    <script>
        function createCheckpoint() {
            const description = document.getElementById('checkpointDesc').value;
            
            fetch('enhanced_backup_system.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=create_checkpoint&description=' + encodeURIComponent(description)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Checkpoint created: ' + data.filename);
                    document.getElementById('checkpointDesc').value = '';
                    loadBackups();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            });
        }

        function createBackup() {
            fetch('enhanced_backup_system.php?action=create_backup')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Backup created: ' + data.filename);
                    loadBackups();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            });
        }

        function showRestoreTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab + 'Restore').classList.add('active');
            
            if (tab === 'server') {
                loadServerBackups();
            }
        }

        function loadBackups() {
            fetch('enhanced_backup_system.php?action=list')
            .then(response => response.json())
            .then(backups => {
                const list = document.getElementById('backupList');
                if (backups.length === 0) {
                    list.innerHTML = '<p>No backups found.</p>';
                } else {
                    list.innerHTML = backups.map(backup => `
                        <div class="backup-item ${backup.type}">
                            <strong>${backup.filename}</strong>
                            <span class="backup-meta">
                                📅 ${backup.date} | 📦 ${(backup.size/1024).toFixed(1)} KB | 
                                ${backup.type === 'checkpoint' ? '📍 Checkpoint' : '💾 Full Backup'}
                            </span>
                            ${backup.metadata && backup.metadata.description ? 
                                `<div class="backup-meta">📝 ${backup.metadata.description}</div>` : ''}
                            <div class="backup-actions">
                                <button onclick="downloadBackup('${backup.filename}')" class="btn btn-secondary">⬇️ Download</button>
                                <button onclick="restoreFromServer('${backup.filename}')" class="btn btn-danger" 
                                        onclick="return confirm('⚠️ Restore from ${backup.filename}? This will replace all data!')">
                                    🔄 Restore
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            });
        }

        function loadServerBackups() {
            fetch('enhanced_backup_system.php?action=list')
            .then(response => response.json())
            .then(backups => {
                const list = document.getElementById('serverBackupList');
                if (backups.length === 0) {
                    list.innerHTML = '<p>No server backups found.</p>';
                } else {
                    list.innerHTML = backups.map(backup => `
                        <div class="backup-item ${backup.type}">
                            <label>
                                <input type="radio" name="serverBackup" value="${backup.filename}">
                                <strong>${backup.filename}</strong>
                                <span class="backup-meta">📅 ${backup.date} | ${backup.type}</span>
                            </label>
                        </div>
                    `).join('') + 
                    '<button onclick="restoreSelected()" class="btn btn-danger" style="margin-top: 1rem;">🔄 Restore Selected</button>';
                }
            });
        }

        function downloadBackup(filename) {
            window.open('enhanced_backup_system.php?action=download&filename=' + filename);
        }

        function restoreFromServer(filename) {
            if (!confirm(`⚠️ Restore from ${filename}? This will replace ALL current data!`)) return;
            
            fetch('enhanced_backup_system.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=restore_from_server&filename=' + encodeURIComponent(filename)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.error);
                }
            });
        }

        function restoreSelected() {
            const selected = document.querySelector('input[name="serverBackup"]:checked');
            if (!selected) {
                alert('Please select a backup to restore');
                return;
            }
            restoreFromServer(selected.value);
        }

        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'restore');
            
            fetch('enhanced_backup_system.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.error);
                }
            });
        });

        // Load backups on page load
        loadBackups();
    </script>
</body>
</html>
<?php
}
?>