/* Employee Dashboard JS
   - Handles avatar dropdown menu
   - Renders employee cards from data array (backend-ready)
   - Opens view-only modal on card click
   - Attendance Check In / Out UI logic (frontend only; ready for API hooks)
*/

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
    const currentUser = typeof currentUser !== 'undefined' ? currentUser : {};

    // --- Functions ---

    /**
     * Renders employee cards in the grid.
     * @param {Array} list - Array of employee objects.
     */
    function renderCards(list) {
        employeeGrid.innerHTML = '';
        list.forEach(emp => {
            const card = document.createElement('div');
            card.className = 'employee-card';
            card.setAttribute('data-id', emp.id);

            const dot = document.createElement('span');
            dot.className = 'status-dot ' + (emp.status || 'unknown');
            dot.title = emp.status ? emp.status.charAt(0).toUpperCase() + emp.status.slice(1) : 'Unknown';

            const img = document.createElement('img');
            img.src = `https://i.pravatar.cc/120?u=${emp.email}`; // Use email for unique avatar
            img.alt = `${emp.first_name} ${emp.last_name}`;

            const name = document.createElement('div');
            name.className = 'name';
            name.textContent = `${emp.first_name} ${emp.last_name}`;

            const muted = document.createElement('div');
            muted.className = 'muted';
            muted.textContent = emp.title || '';

            card.appendChild(dot);
            card.appendChild(img);
            card.appendChild(name);
            card.appendChild(muted);

            card.addEventListener('click', () => openEmployeeModal(emp));
            employeeGrid.appendChild(card);
        });
    }

    /**
     * Opens a modal with view-only employee details.
     * @param {object} emp - Employee object.
     */
    function openEmployeeModal(emp) {
        modalContent.innerHTML = `
            <div style="display:flex;gap:20px;align-items:center">
                <img src="https://i.pravatar.cc/160?u=${emp.email}" style="width:120px;height:120px;border-radius:10px;object-fit:cover">
                <div>
                    <h2 style="margin:0">${emp.first_name} ${emp.last_name}</h2>
                    <div class="muted">${emp.title || ''}</div>
                    <div style="margin-top:8px">${emp.email || ''}</div>
                </div>
            </div>
        `;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    }

    /**
     * Closes the employee modal.
     */
    function closeEmployeeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }
    
    /**
     * Toggles the avatar dropdown menu.
     */
    function toggleAvatarMenu() {
        avatarMenu.classList.toggle('active');
        avatarMenu.setAttribute('aria-hidden', !avatarMenu.classList.contains('active'));
    }

    /**
     * Sets the UI to a "Checked In" state.
     * @param {string} timeStr - The time string to display.
     */
    function setCheckedIn(timeStr) {
        headerStatus.classList.add('present');
        statusText.textContent = 'Checked in';
        if(timeStr) sinceText.textContent = 'Since ' + timeStr;
        checkInBtn.disabled = true;
        checkOutBtn.disabled = false;
    }

    /**
     * Sets the UI to a "Checked Out" state.
     */
    function setCheckedOut() {
        headerStatus.classList.remove('present');
        statusText.textContent = 'Not checked in';
        sinceText.textContent = '';
        checkInBtn.disabled = false;
        checkOutBtn.disabled = true;
    }

    // --- Event Listeners ---

    // Avatar dropdown
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
    
    // Modal close events
    closeModalBtn.addEventListener('click', closeEmployeeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeEmployeeModal();
        }
    });
    
    // Search functionality
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase().trim();
        const filtered = employees.filter(e => 
            `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) || 
            (e.email || '').toLowerCase().includes(q)
        );
        renderCards(filtered);
    });

    // Attendance buttons
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
                // Optionally, refresh employee list to show status change
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
            }
        })