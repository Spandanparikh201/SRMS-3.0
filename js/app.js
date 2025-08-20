// AJAX Operations and Form Handling

// Generic AJAX function
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(null, xhr.responseText);
                }
            } else {
                callback('Error: ' + xhr.status, null);
            }
        }
    };
    
    xhr.send(data);
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            isValid = false;
        } else {
            input.style.borderColor = '#d1d5db';
        }
    });
    
    return isValid;
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 1rem;
        border-radius: 8px;
        z-index: 10000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    `;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 5000);
}

// Modal functions
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Load students by class
function loadStudents(classId, selectId) {
    ajaxRequest('get_students.php?class_id=' + classId, 'GET', null, function(error, data) {
        if (error) {
            showNotification('Error loading students', 'error');
            return;
        }
        
        const select = document.getElementById(selectId);
        select.innerHTML = '<option value="">Select Student</option>';
        
        data.forEach(student => {
            select.innerHTML += `<option value="${student.student_id}">${student.fullname} (${student.roll_number})</option>`;
        });
    });
}

// Export data to CSV
function exportToCSV(data, filename) {
    const csv = data.map(row => Object.values(row).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Export functions
function exportResults(format, classId = '', subjectId = '', term = '') {
    const params = new URLSearchParams({
        type: 'results',
        format: format
    });
    
    if (classId) params.append('class_id', classId);
    if (subjectId) params.append('subject_id', subjectId);
    if (term) params.append('term', term);
    
    window.open('export.php?' + params.toString());
}

function exportStudents(format) {
    window.open('export.php?type=students&format=' + format);
}