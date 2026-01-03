document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements ---
    const employeeGrid = document.getElementById('employeeGrid');
    const searchInput = document.getElementById('globalSearch');
    const avatar = document.getElementById('userAvatar');
    const avatarMenu = document.getElementById('avatarMenu');
    
    const modal = document.getElementById('employeeModal');
    const modalContent = document.getElementById('employeeContent');
    const closeModalBtn = document.getElementById('closeModal');

    const checkInBtn = document.getElementById('checkInBtn');
    const checkOutBtn = document.getElementById('checkOutBtn');
    const headerStatus = document.getElementById('headerStatus');
    const statusText = document.getElementById('statusText');
    const sinceText = document.getElementById('sinceText');

    // --- Data from PHP ---
    const employees = typeof employeeData !== 'undefined' ? employeeData : [];
    const currentUserData = typeof currentUser !== 'undefined' ? currentUser : {};

    // --- Functions ---

    /**
     * Renders employee cards in the grid.
     * @param {Array} list - Array of employee objects.
     */
    function renderCards(list) {
        if (!employeeGrid) {
            console.error('Employee grid element not found');
            return;
        }
        
        employeeGrid.innerHTML = '';
        
        if (!list || list.length === 0) {
            employeeGrid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--muted);">No employees found for this company.</p>';
            return;
        }
        
        list.forEach(emp => {
            const card = document.createElement('div');
            card.className = 'employee-card';
            card.setAttribute('data-id', emp.id);

            const dot = document.createElement('span');
            dot.className = 'status-dot ' + (emp.status || 'unknown');
            dot.title = emp.status ? emp.status.charAt(0).toUpperCase() + emp.status.slice(1) : 'Unknown';

            const img = document.createElement('img');
            img.src = `https://i.pravatar.cc/80?u=${emp.email}`;
            img.alt = `${emp.first_name} ${emp.last_name}`;
            
            const details = document.createElement('div');
            details.className = 'details';
            details.style.flex = '1';

            const name = document.createElement('div');
            name.className = 'name';
            name.textContent = `${emp.first_name} ${emp.last_name}`;
            name.style.fontSize = '16px';
            name.style.marginBottom = '4px';

            const email = document.createElement('div');
            email.className = 'muted';
            email.textContent = emp.email || 'No email';
            email.style.fontSize = '13px';
            email.style.marginBottom = '4px';

            const phone = document.createElement('div');
            phone.className = 'muted';
            phone.textContent = emp.phone || 'No phone';
            phone.style.fontSize = '12px';
            phone.style.marginBottom = '4px';

            const role = document.createElement('div');
            role.className = 'muted';
            role.textContent = emp.title || 'Employee';
            role.style.fontSize = '12px';
            role.style.color = 'var(--accent)';
            role.style.fontWeight = '600';

            details.appendChild(name);
            details.appendChild(email);
            details.appendChild(phone);
            details.appendChild(role);

            card.appendChild(dot);
            card.appendChild(img);
            card.appendChild(details);

            card.addEventListener('click', () => openEmployeeModal(emp));
            employeeGrid.appendChild(card);
        });
    }

    /**
     * Opens a modal with view-only employee details.
     * @param {object} emp - Employee object.
     */
    function openEmployeeModal(emp) {
        const joinDate = emp.date_of_joining ? new Date(emp.date_of_joining).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
        const statusText = emp.status ? emp.status.charAt(0).toUpperCase() + emp.status.slice(1) : 'Unknown';
        
        modalContent.innerHTML = `
            <div class="modal-header">
                <img src="https://i.pravatar.cc/100?u=${emp.email}" alt="${emp.first_name}">
                <h2>${emp.first_name} ${emp.last_name}</h2>
                <div class="muted" style="margin-top: 5px;">${emp.title || 'Employee'}</div>
            </div>
            <div class="modal-body">
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Email Address</strong>
                        <span>${emp.email || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <strong>Phone Number</strong>
                        <span>${emp.phone || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <strong>Date of Joining</strong>
                        <span>${joinDate}</span>
                    </div>
                    <div class="info-item">
                        <strong>Role</strong>
                        <span>${emp.title || 'Employee'}</span>
                    </div>
                    <div class="info-item">
                        <strong>Employee ID</strong>
                        <span>${emp.id || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <strong>Attendance Status</strong>
                        <span>
                            <span class="status-dot ${emp.status || 'unknown'}" style="display: inline-block; margin-right: 5px;"></span>
                            ${statusText}
                        </span>
                    </div>
                </div>
            </div>
        `;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeEmployeeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }
    
    function toggleAvatarMenu() {
        avatarMenu.classList.toggle('active');
        avatarMenu.setAttribute('aria-hidden', !avatarMenu.classList.contains('active'));
    }

    function setCheckedIn(timeStr) {
        headerStatus.classList.add('present');
        statusText.textContent = 'Checked in';
        if(timeStr) sinceText.textContent = 'Since ' + timeStr;
        checkInBtn.disabled = true;
        checkOutBtn.disabled = false;
    }

    function setCheckedOut() {
        headerStatus.classList.remove('present');
        statusText.textContent = 'Not checked in';
        sinceText.textContent = '';
        checkInBtn.disabled = false;
        checkOutBtn.disabled = true;
    }

    // --- Event Listeners ---

    avatar.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleAvatarMenu();
    });

    document.addEventListener('click', (e) => {
        if (!avatar.contains(e.target)) {
            avatarMenu.classList.remove('active');
            avatarMenu.setAttribute('aria-hidden', 'true');
        }
    });
    
    closeModalBtn.addEventListener('click', closeEmployeeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeEmployeeModal();
        }
    });
    
    // Only enable search if search input exists (hidden for employees)
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase().trim();
            let dataToSearch = employees;
            
            // Security: Employees can only search their own data
            if (typeof currentUserRole !== 'undefined' && currentUserRole === 'EMPLOYEE' && currentUserData && currentUserData.employee_id) {
                dataToSearch = employees.filter(emp => emp.id === currentUserData.employee_id);
            }
            
            const filtered = dataToSearch.filter(e => 
                `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) || 
                (e.email || '').toLowerCase().includes(q) ||
                (e.phone || '').toLowerCase().includes(q)
            );
            renderCards(filtered);
        });
    }

    checkInBtn.addEventListener('click', () => {
        fetch('api/check_attendance.php', { 
            method:'POST', 
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'type=IN'
        })
        .then(response => response.json())
        .then(data => {
            if(data.ok) {
                const now = new Date();
                const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                setCheckedIn(timeStr);
                // Refresh page to update status dots on cards
                location.reload();
            } else {
                alert(data.error || 'Failed to check in.');
            }
        })
        .catch(() => alert('Network error.'));
    });

    checkOutBtn.addEventListener('click', () => {
        fetch('api/check_attendance.php', { 
            method:'POST', 
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'type=OUT'
        })
        .then(response => response.json())
        .then(data => {
            if(data.ok) {
                setCheckedOut();
                location.reload();
            } else {
                alert(data.error || 'Failed to check out.');
            }
        })
        .catch(() => alert('Network error.'));
    });

    document.getElementById('logoutBtn').addEventListener('click', () => {
        window.location.href = 'auth/logout.php';
    });
    
    document.getElementById('viewProfileBtn').addEventListener('click', () => {
        openEmployeeModal({
            first_name: currentUserData.name.split(' ')[0],
            last_name: currentUserData.name.split(' ')[1] || '',
            title: 'Current User',
            email: currentUserData.email,
            phone: 'N/A',
            date_of_joining: 'N/A',
            status: 'present'
        });
    });

    // --- Initial Setup ---
    // Security: If user is EMPLOYEE, filter to only show their own data
    let employeesToShow = employees;
    if (typeof currentUserRole !== 'undefined' && currentUserRole === 'EMPLOYEE' && currentUserData && currentUserData.employee_id) {
        employeesToShow = employees.filter(emp => emp.id === currentUserData.employee_id);
        if (employeesToShow.length !== employees.length) {
            console.log('Security: Filtered employee data - showing only current user');
        }
    }
    
    // Render employee cards
    console.log('Employee data:', employeesToShow);
    console.log('Employee grid element:', employeeGrid);
    
    if (employeesToShow && employeesToShow.length > 0) {
        console.log('Rendering', employeesToShow.length, 'employee cards');
        renderCards(employeesToShow);
    } else {
        console.log('No employees found');
        if (employeeGrid) {
            const message = (typeof currentUserRole !== 'undefined' && currentUserRole === 'EMPLOYEE') 
                ? 'No employee information found.' 
                : 'No employees found for this company.';
            employeeGrid.innerHTML = `<p style="text-align: center; padding: 40px; color: var(--muted);">${message}</p>`;
        }
    }
    
    // Check current user's latest attendance status from PHP
    if (typeof currentUserAttendance !== 'undefined' && currentUserAttendance.status === 'IN') {
        const checkInTime = currentUserAttendance.checkInTime;
        if (checkInTime) {
            const date = new Date(checkInTime);
            const timeStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            setCheckedIn(timeStr);
        } else {
            setCheckedIn("");
        }
    } else {
        setCheckedOut();
    }
});