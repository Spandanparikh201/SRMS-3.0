<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Fetch existing schools with detailed data
$schools = $conn->query("SELECT * FROM school ORDER BY school_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage School Data - SRMS</title>
    <link rel="stylesheet" href="assets/css/iris-design-system.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 SRMS</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_school.php">Manage Schools</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Manage Schools</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showSection('add')">Add New School</div>
            <div class="tab" onclick="showSection('manage')">Manage Existing Schools</div>
        </div>
        
        <div id="message"></div>
        
        <div id="addSection" class="section active">
            <div class="add-form">
                <h2>Add New School</h2>
                <form id="addSchoolForm">
                    <div class="form-group">
                        <label>School Name</label>
                        <input type="text" name="school_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>School Address</label>
                        <textarea name="school_address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Principal Name</label>
                        <input type="text" name="principal_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Principal Username</label>
                        <input type="text" name="principal_username" class="form-control">
                    </div>
                    <button type="submit" class="btn">Add School</button>
                </form>
            </div>
        </div>
        
        <div id="manageSection" class="section">
            <h2>Existing Schools</h2>
            <div class="school-grid">
                <?php while($school = $schools->fetch_assoc()): ?>
                <div class="school-card" id="school-<?= $school['school_id'] ?>">
                    <div class="school-header">
                        <h2 class="school-title"><?= htmlspecialchars($school['school_name']) ?></h2>
                        <div>
                            <button class="btn" onclick="editSchool(<?= $school['school_id'] ?>)">Edit</button>
                            <?php if(($school['status'] ?? 'active') == 'active'): ?>
                            <button class="btn btn-danger" onclick="deactivateSchool(<?= $school['school_id'] ?>)">Deactivate</button>
                            <?php else: ?>
                            <button class="btn" onclick="activateSchool(<?= $school['school_id'] ?>)">Activate</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="school-info">
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= htmlspecialchars($school['school_address']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Principal</div>
                            <div class="info-value"><?= htmlspecialchars($school['principal_name'] ?? 'Not specified') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Principal Username</div>
                            <div class="info-value"><?= htmlspecialchars($school['principal_username'] ?? 'Not set') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">School ID</div>
                            <div class="info-value"><?= $school['school_id'] ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><?= ucfirst($school['status'] ?? 'active') ?></div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit School Data</h2>
            <form id="editForm">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label>School Name</label>
                    <input type="text" id="editName" name="school_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>School Address</label>
                    <textarea id="editAddress" name="school_address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Principal Name</label>
                    <input type="text" id="editPrincipal" name="principal_name" class="form-control">
                </div>
                <div class="form-group">
                    <label>Principal Username</label>
                    <input type="text" id="editUsername" name="principal_username" class="form-control">
                </div>
                <button type="submit" class="btn">Update School</button>
            </form>
        </div>
    </div>



    <script>
        function editSchool(id) {
            fetch(`school_data.php?action=get&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const school = data.school;
                    document.getElementById('editId').value = school.school_id;
                    document.getElementById('editName').value = school.school_name;
                    document.getElementById('editAddress').value = school.school_address;
                    document.getElementById('editPrincipal').value = school.principal_name || '';
                    document.getElementById('editUsername').value = school.principal_username || '';
                    document.getElementById('editModal').style.display = 'block';
                } else {
                    showMessage(data.error || 'Failed to load school data', 'error');
                }
            })
            .catch(error => {
                showMessage('Network error occurred', 'error');
            });
        }



        function showSection(section) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(section + 'Section').classList.add('active');
        }
        
        document.getElementById('addSchoolForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch('school_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = data.success;
                    if (data.principal_credentials) {
                        message += '<br><strong>Principal Login Credentials:</strong><br>';
                        message += 'Username: ' + data.principal_credentials.username + '<br>';
                        message += 'Password: ' + data.principal_credentials.password;
                    }
                    showMessage(message, 'success');
                    this.reset();
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error occurred', 'error');
            });
        });
        
        function deactivateSchool(id) {
            if (confirm('Are you sure you want to deactivate this school? All accounts will be suspended.')) {
                const formData = new FormData();
                formData.append('action', 'deactivate');
                formData.append('id', id);
                
                fetch('school_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.success, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error occurred', 'error');
                });
            }
        }
        
        function activateSchool(id) {
            if (confirm('Are you sure you want to activate this school?')) {
                const formData = new FormData();
                formData.append('action', 'activate');
                formData.append('id', id);
                
                fetch('school_data.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.success, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error occurred', 'error');
                });
            }
        }
        
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('school_data.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let message = data.success;
                    if (data.new_credentials) {
                        message += '<br><strong>New Principal Login Credentials:</strong><br>';
                        message += 'Username: ' + data.new_credentials.username + '<br>';
                        message += 'Password: ' + data.new_credentials.password;
                    }
                    showMessage(message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error occurred', 'error');
            });
        });
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="${type}">${message}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 5000);
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>