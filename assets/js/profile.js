// Profile page JavaScript

// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tabName = btn.dataset.tab;
        
        // Update active tab button
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Update active tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName + '-tab').classList.add('active');
    });
});

// Avatar menu toggle
const avatarWrapper = document.getElementById('avatarWrapper');
const avatarMenu = document.getElementById('avatarMenu');

if (avatarWrapper && avatarMenu) {
    avatarWrapper.addEventListener('click', (e) => {
        e.stopPropagation();
        avatarMenu.classList.toggle('active');
    });
    
    document.addEventListener('click', () => {
        avatarMenu.classList.remove('active');
    });
}

// Edit section functions
function editSection(fieldName) {
    const readMode = document.getElementById(fieldName + '-read');
    const editMode = document.getElementById(fieldName + '-edit');
    
    if (readMode && editMode) {
        readMode.style.display = 'none';
        editMode.style.display = 'block';
    }
}

function cancelEdit(fieldName) {
    const readMode = document.getElementById(fieldName + '-read');
    const editMode = document.getElementById(fieldName + '-edit');
    
    if (readMode && editMode) {
        readMode.style.display = 'block';
        editMode.style.display = 'none';
        
        // Reset textarea value
        const textarea = editMode.querySelector('.section-textarea');
        if (textarea) {
            const originalValue = textarea.dataset.originalValue || '';
            textarea.value = originalValue;
        }
    }
}

function saveSection(fieldName) {
    const editMode = document.getElementById(fieldName + '-edit');
    const textarea = editMode.querySelector('.section-textarea');
    const value = textarea.value.trim();
    
    fetch('api/update_profile.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `field=${encodeURIComponent(fieldName)}&value=${encodeURIComponent(value)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            const readMode = document.getElementById(fieldName + '-read');
            readMode.innerHTML = '<p>' + value.replace(/\n/g, '<br>') + '</p>';
            cancelEdit(fieldName);
            showToast('Saved successfully!', 'success');
        } else {
            alert(data.error || 'Failed to save');
        }
    })
    .catch(() => alert('Network error'));
}

// Editable fields
document.querySelectorAll('.editable').forEach(el => {
    el.addEventListener('click', function() {
        const valueText = this.querySelector('.value-text');
        const valueInput = this.querySelector('.value-input');
        
        if (valueText && valueInput) {
            valueText.style.display = 'none';
            valueInput.style.display = 'inline-block';
            valueInput.focus();
            
            const saveValue = () => {
                const field = this.dataset.field;
                const value = valueInput.value;
                
                fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        valueText.textContent = value || 'Not set';
                        valueText.style.display = 'inline';
                        valueInput.style.display = 'none';
                        showToast('Saved successfully!', 'success');
                    } else {
                        alert(data.error || 'Failed to save');
                    }
                })
                .catch(() => alert('Network error'));
            };
            
            valueInput.addEventListener('blur', saveValue);
            valueInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    saveValue();
                }
            });
        }
    });
});

// Skills management
function showAddSkill() {
    document.getElementById('add-skill-form').style.display = 'block';
    document.getElementById('new-skill-input').focus();
}

function hideAddSkill() {
    document.getElementById('add-skill-form').style.display = 'none';
    document.getElementById('new-skill-input').value = '';
}

function addSkill() {
    const skillName = document.getElementById('new-skill-input').value.trim();
    if (!skillName) {
        alert('Please enter a skill name');
        return;
    }
    
    fetch('api/add_skill.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `skill_name=${encodeURIComponent(skillName)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert(data.error || 'Failed to add skill');
        }
    })
    .catch(() => alert('Network error'));
}

function removeSkill(skillName) {
    if (!confirm('Remove this skill?')) return;
    
    fetch('api/remove_skill.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `skill_name=${encodeURIComponent(skillName)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert(data.error || 'Failed to remove skill');
        }
    })
    .catch(() => alert('Network error'));
}

// Certification management
function showAddCertification() {
    document.getElementById('add-certification-form').style.display = 'block';
    document.getElementById('cert-name').focus();
}

function hideAddCertification() {
    document.getElementById('add-certification-form').style.display = 'none';
    document.getElementById('cert-name').value = '';
    document.getElementById('cert-org').value = '';
    document.getElementById('cert-issue-date').value = '';
}

function addCertification() {
    const name = document.getElementById('cert-name').value.trim();
    const org = document.getElementById('cert-org').value.trim();
    const issueDate = document.getElementById('cert-issue-date').value;
    
    if (!name) {
        alert('Please enter certification name');
        return;
    }
    
    fetch('api/add_certification.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `certification_name=${encodeURIComponent(name)}&issuing_organization=${encodeURIComponent(org)}&issue_date=${encodeURIComponent(issueDate)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert(data.error || 'Failed to add certification');
        }
    })
    .catch(() => alert('Network error'));
}

function removeCertification(certId) {
    if (!confirm('Remove this certification?')) return;
    
    fetch('api/remove_certification.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `certification_id=${certId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            location.reload();
        } else {
            alert(data.error || 'Failed to remove certification');
        }
    })
    .catch(() => alert('Network error'));
}

// Profile picture upload
function uploadProfilePicture(input) {
    if (!input.files || !input.files[0]) return;
    
    const formData = new FormData();
    formData.append('profile_picture', input.files[0]);
    
    fetch('api/upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            document.getElementById('profilePicture').src = data.url + '?t=' + Date.now();
            showToast('Profile picture updated!', 'success');
        } else {
            alert(data.error || 'Failed to upload picture');
        }
    })
    .catch(() => alert('Network error'));
}

// Salary field editing (only for admins)
document.querySelectorAll('.editable-salary').forEach(el => {
    // Only make editable if user is admin and element has data-editable="true"
    const isEditable = el.getAttribute('data-editable') === 'true';
    if (typeof isAdmin !== 'undefined' && isAdmin && isEditable) {
        el.style.cursor = 'pointer';
        el.addEventListener('click', function() {
            const valueText = this.querySelector('.value-text');
            const valueInput = this.querySelector('.value-input');
            
            if (valueText && valueInput) {
                valueText.style.display = 'none';
                valueInput.style.display = 'inline-block';
                valueInput.focus();
                
                const saveValue = () => {
                    const field = this.dataset.field;
                    const value = valueInput.value;
                    
                    fetch('api/update_salary.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            valueText.textContent = parseFloat(value).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            valueText.style.display = 'inline';
                            valueInput.style.display = 'none';
                            showToast('Saved successfully!', 'success');
                        } else {
                            alert(data.error || 'Failed to save');
                        }
                    })
                    .catch(() => alert('Network error'));
                };
                
                valueInput.addEventListener('blur', saveValue);
                valueInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        saveValue();
                    }
                });
            }
        });
    } else {
        // For non-admins, make it read-only (no cursor pointer, no hover effect)
        el.style.cursor = 'default';
    }
});

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

