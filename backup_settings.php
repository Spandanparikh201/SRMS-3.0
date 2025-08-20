<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

class BackupSettings {
    private $settingsFile = 'backups/backup_settings.json';
    
    public function __construct() {
        if (!is_dir('backups/')) {
            mkdir('backups/', 0755, true);
        }
    }
    
    public function getSettings() {
        if (file_exists($this->settingsFile)) {
            return json_decode(file_get_contents($this->settingsFile), true);
        }
        
        // Default settings
        return [
            'auto_backup_enabled' => true,
            'emergency_restore_enabled' => true,
            'checkpoint_interval' => 4, // hours
            'max_checkpoints' => 10,
            'weekly_backup_enabled' => true,
            'backup_day' => 'SUN',
            'backup_time' => '02:00'
        ];
    }
    
    public function saveSettings($settings) {
        return file_put_contents($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }
    
    public function isAutoBackupEnabled() {
        $settings = $this->getSettings();
        return $settings['auto_backup_enabled'] ?? true;
    }
    
    public function isEmergencyRestoreEnabled() {
        $settings = $this->getSettings();
        return $settings['emergency_restore_enabled'] ?? true;
    }
}

$settings = new BackupSettings();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save') {
    $newSettings = [
        'auto_backup_enabled' => isset($_POST['auto_backup_enabled']),
        'emergency_restore_enabled' => isset($_POST['emergency_restore_enabled']),
        'checkpoint_interval' => (int)($_POST['checkpoint_interval'] ?? 4),
        'max_checkpoints' => (int)($_POST['max_checkpoints'] ?? 10),
        'weekly_backup_enabled' => isset($_POST['weekly_backup_enabled']),
        'backup_day' => $_POST['backup_day'] ?? 'SUN',
        'backup_time' => $_POST['backup_time'] ?? '02:00'
    ];
    
    if ($settings->saveSettings($newSettings)) {
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save settings']);
    }
    exit;
}

if ($action === 'get') {
    echo json_encode($settings->getSettings());
    exit;
}

$currentSettings = $settings->getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Settings - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
    <style>
        .setting-group { margin: 1.5rem 0; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2196F3; }
        input:checked + .slider:before { transform: translateX(26px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-banner">
            <h1>⚙️ Backup System Settings</h1>
            <p>Configure automatic backup and emergency restore options</p>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <form id="settingsForm">
            <div class="card">
                <h2>🔄 Auto Backup Settings</h2>
                
                <div class="setting-group">
                    <label style="display: flex; align-items: center; gap: 1rem;">
                        <span style="flex: 1;">Enable Automatic Backups</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_backup_enabled" <?php echo $currentSettings['auto_backup_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </label>
                    <small>When enabled, automatic checkpoints and weekly backups will run</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Checkpoint Interval (hours)</label>
                        <select name="checkpoint_interval" class="form-control">
                            <option value="2" <?php echo $currentSettings['checkpoint_interval'] == 2 ? 'selected' : ''; ?>>Every 2 hours</option>
                            <option value="4" <?php echo $currentSettings['checkpoint_interval'] == 4 ? 'selected' : ''; ?>>Every 4 hours</option>
                            <option value="6" <?php echo $currentSettings['checkpoint_interval'] == 6 ? 'selected' : ''; ?>>Every 6 hours</option>
                            <option value="8" <?php echo $currentSettings['checkpoint_interval'] == 8 ? 'selected' : ''; ?>>Every 8 hours</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Max Checkpoints to Keep</label>
                        <select name="max_checkpoints" class="form-control">
                            <option value="5" <?php echo $currentSettings['max_checkpoints'] == 5 ? 'selected' : ''; ?>>5 checkpoints</option>
                            <option value="10" <?php echo $currentSettings['max_checkpoints'] == 10 ? 'selected' : ''; ?>>10 checkpoints</option>
                            <option value="15" <?php echo $currentSettings['max_checkpoints'] == 15 ? 'selected' : ''; ?>>15 checkpoints</option>
                            <option value="20" <?php echo $currentSettings['max_checkpoints'] == 20 ? 'selected' : ''; ?>>20 checkpoints</option>
                        </select>
                    </div>
                </div>

                <div class="setting-group">
                    <label style="display: flex; align-items: center; gap: 1rem;">
                        <span style="flex: 1;">Enable Weekly Full Backups</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="weekly_backup_enabled" <?php echo $currentSettings['weekly_backup_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </label>
                    
                    <div class="form-row" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label>Backup Day</label>
                            <select name="backup_day" class="form-control">
                                <option value="SUN" <?php echo $currentSettings['backup_day'] == 'SUN' ? 'selected' : ''; ?>>Sunday</option>
                                <option value="MON" <?php echo $currentSettings['backup_day'] == 'MON' ? 'selected' : ''; ?>>Monday</option>
                                <option value="SAT" <?php echo $currentSettings['backup_day'] == 'SAT' ? 'selected' : ''; ?>>Saturday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Backup Time</label>
                            <input type="time" name="backup_time" class="form-control" value="<?php echo $currentSettings['backup_time']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>🚨 Emergency Restore Settings</h2>
                
                <div class="setting-group">
                    <label style="display: flex; align-items: center; gap: 1rem;">
                        <span style="flex: 1;">Enable Emergency Restore Access</span>
                        <label class="toggle-switch">
                            <input type="checkbox" name="emergency_restore_enabled" <?php echo $currentSettings['emergency_restore_enabled'] ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </label>
                    <small>When disabled, emergency restore interface will be inaccessible</small>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn">💾 Save Settings</button>
                <button type="button" onclick="resetDefaults()" class="btn btn-secondary">🔄 Reset to Defaults</button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'save');
            
            fetch('backup_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Settings saved successfully!');
                } else {
                    alert('❌ Error: ' + data.error);
                }
            });
        });

        function resetDefaults() {
            if (confirm('Reset all settings to defaults?')) {
                document.querySelector('[name="auto_backup_enabled"]').checked = true;
                document.querySelector('[name="emergency_restore_enabled"]').checked = true;
                document.querySelector('[name="checkpoint_interval"]').value = '4';
                document.querySelector('[name="max_checkpoints"]').value = '10';
                document.querySelector('[name="weekly_backup_enabled"]').checked = true;
                document.querySelector('[name="backup_day"]').value = 'SUN';
                document.querySelector('[name="backup_time"]').value = '02:00';
            }
        }
    </script>
</body>
</html>