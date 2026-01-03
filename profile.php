<?php
session_start();
require_once 'config/db.php';

// Check if user is authenticated
if (!isset($_SESSION['user'])) {
    header('Location: signin.php');
    exit;
}

$user = $_SESSION['user'];
$employee_id = $user['employee_id'] ?? null;
$company_id = $user['company_id'] ?? null;
$role_id = $user['role_id'] ?? null;

if (!$employee_id) {
    header('Location: dashboard.php?error=' . urlencode('Employee profile not found'));
    exit;
}

$pdo = getPDO();

// Get user role
$roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE role_id = ?");
$roleStmt->execute([$role_id]);
$userRole = $roleStmt->fetchColumn();
$isAdmin = ($userRole === 'ADMIN');

// Get employee details
$empStmt = $pdo->prepare("
    SELECT e.*, c.company_name, u.login_id
    FROM employees e
    LEFT JOIN companies c ON c.company_id = e.company_id
    LEFT JOIN users u ON u.employee_id = e.employee_id
    WHERE e.employee_id = ?
");
$empStmt->execute([$employee_id]);
$employee = $empStmt->fetch(PDO::FETCH_ASSOC);

// Get profile data
$profileStmt = $pdo->prepare("SELECT * FROM employee_profiles WHERE employee_id = ?");
$profileStmt->execute([$employee_id]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) {
    $profile = []; // Initialize empty array if profile doesn't exist
}

// Get manager name if exists
$managerName = null;
if ($profile && $profile['manager_id']) {
    $mgrStmt = $pdo->prepare("SELECT first_name, last_name FROM employees WHERE employee_id = ?");
    $mgrStmt->execute([$profile['manager_id']]);
    $manager = $mgrStmt->fetch(PDO::FETCH_ASSOC);
    if ($manager) {
        $managerName = $manager['first_name'] . ' ' . $manager['last_name'];
    }
}

// Get skills
$skillsStmt = $pdo->prepare("SELECT skill_name FROM employee_skills WHERE employee_id = ? ORDER BY created_at");
$skillsStmt->execute([$employee_id]);
$skills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get certifications
$certsStmt = $pdo->prepare("SELECT * FROM employee_certifications WHERE employee_id = ? ORDER BY issue_date DESC");
$certsStmt->execute([$employee_id]);
$certifications = $certsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get salary info (visible to all, but editable only by admin)
$salary = null;
$salaryStmt = $pdo->prepare("SELECT * FROM employee_salary WHERE employee_id = ?");
$salaryStmt->execute([$employee_id]);
$salary = $salaryStmt->fetch(PDO::FETCH_ASSOC);

// Get all employees for manager dropdown
$allEmployeesStmt = $pdo->prepare("
    SELECT employee_id, first_name, last_name 
    FROM employees 
    WHERE company_id = ? AND employee_id != ?
    ORDER BY first_name, last_name
");
$allEmployeesStmt->execute([$company_id, $employee_id]);
$allEmployees = $allEmployeesStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Profile — HRMS</title>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
  <header class="topbar">
    <div class="container topbar-inner">
      <div class="left">
        <div class="logo">AuroraHQ</div>
        <nav class="main-nav">
          <a href="dashboard.php">Employees</a>
          <a href="attendance.php">Attendance</a>
          <a href="timeoff.php">Time Off</a>
          <?php if (in_array($userRole, ['ADMIN', 'HR'])): ?>
          <a href="employees.php">Manage</a>
          <?php endif; ?>
          <a href="profile.php" class="active">My Profile</a>
        </nav>
      </div>
      <div class="right">
        <div class="avatar-wrapper" id="avatarWrapper">
          <img id="userAvatar" class="avatar" src="<?php echo htmlspecialchars($user['logo'] ?? 'https://i.pravatar.cc/40?u=me'); ?>" alt="User">
          <div class="avatar-menu" id="avatarMenu" role="menu" aria-hidden="true">
            <a href="profile.php" class="menu-item">My Profile</a>
            <a href="auth/logout.php" class="menu-item">Log Out</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="container profile-container">
    <div class="profile-header-section">
      <h1 class="profile-title">My Profile</h1>
      
      <div class="profile-header-card">
        <div class="profile-picture-section">
          <div class="profile-picture-wrapper">
            <img id="profilePicture" src="<?php echo htmlspecialchars($profile['profile_picture'] ?? 'https://i.pravatar.cc/150?u=' . urlencode($employee['email'])); ?>" alt="Profile Picture">
            <button class="edit-picture-btn" onclick="document.getElementById('profilePictureInput').click()">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <input type="file" id="profilePictureInput" accept="image/*" style="display:none" onchange="uploadProfilePicture(this)">
          </div>
        </div>
        
        <div class="profile-info-grid">
          <div class="profile-info-left">
            <div class="info-field">
              <label>My Name</label>
              <div class="info-value"><?php echo htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')); ?></div>
            </div>
            <div class="info-field">
              <label>Login ID</label>
              <div class="info-value"><?php echo htmlspecialchars($employee['login_id'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-field">
              <label>Email</label>
              <div class="info-value"><?php echo htmlspecialchars($employee['email'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-field">
              <label>Mobile</label>
              <div class="info-value"><?php echo htmlspecialchars($employee['phone'] ?? 'N/A'); ?></div>
            </div>
          </div>
          
          <div class="profile-info-right">
            <div class="info-field">
              <label>Company</label>
              <div class="info-value"><?php echo htmlspecialchars($employee['company_name'] ?? 'N/A'); ?></div>
            </div>
            <div class="info-field">
              <label>Department</label>
              <div class="info-value editable" data-field="department">
                <span class="value-text"><?php echo htmlspecialchars($profile['department'] ?? 'Not set'); ?></span>
                <input type="text" class="value-input" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>" style="display:none">
              </div>
            </div>
            <div class="info-field">
              <label>Manager</label>
              <div class="info-value editable" data-field="manager_id">
                <span class="value-text"><?php echo htmlspecialchars($managerName ?? 'Not set'); ?></span>
                <select class="value-input" style="display:none">
                  <option value="">Select Manager</option>
                  <?php foreach($allEmployees as $emp): ?>
                    <option value="<?php echo $emp['employee_id']; ?>" <?php echo ($profile['manager_id'] ?? null) == $emp['employee_id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="info-field">
              <label>Location</label>
              <div class="info-value editable" data-field="location">
                <span class="value-text"><?php echo htmlspecialchars($profile['location'] ?? 'Not set'); ?></span>
                <input type="text" class="value-input" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" style="display:none">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="profile-tabs">
      <button class="tab-btn active" data-tab="resume">Resume</button>
      <button class="tab-btn" data-tab="private">Private Info</button>
      <button class="tab-btn" data-tab="salary">Salary Info</button>
    </div>

    <div class="profile-content">
      <!-- Resume Tab -->
      <div class="tab-content active" id="resume-tab">
        <div class="section-card">
          <div class="section-header">
            <h3>About</h3>
            <button class="edit-btn" onclick="editSection('about')">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
          </div>
          <div class="section-content">
            <div class="read-mode" id="about-read">
              <p><?php echo nl2br(htmlspecialchars($profile['about'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.')); ?></p>
            </div>
            <div class="edit-mode" id="about-edit" style="display:none">
              <textarea class="section-textarea" data-field="about"><?php echo htmlspecialchars($profile['about'] ?? ''); ?></textarea>
              <div class="edit-actions">
                <button class="btn primary" onclick="saveSection('about')">Save</button>
                <button class="btn" onclick="cancelEdit('about')">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-header">
            <h3>What I love about my job</h3>
            <button class="edit-btn" onclick="editSection('job_love')">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
          </div>
          <div class="section-content">
            <div class="read-mode" id="job_love-read">
              <p><?php echo nl2br(htmlspecialchars($profile['job_love'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.')); ?></p>
            </div>
            <div class="edit-mode" id="job_love-edit" style="display:none">
              <textarea class="section-textarea" data-field="job_love"><?php echo htmlspecialchars($profile['job_love'] ?? ''); ?></textarea>
              <div class="edit-actions">
                <button class="btn primary" onclick="saveSection('job_love')">Save</button>
                <button class="btn" onclick="cancelEdit('job_love')">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-header">
            <h3>My interests and hobbies</h3>
            <button class="edit-btn" onclick="editSection('interests_hobbies')">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
          </div>
          <div class="section-content">
            <div class="read-mode" id="interests_hobbies-read">
              <p><?php echo nl2br(htmlspecialchars($profile['interests_hobbies'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.')); ?></p>
            </div>
            <div class="edit-mode" id="interests_hobbies-edit" style="display:none">
              <textarea class="section-textarea" data-field="interests_hobbies"><?php echo htmlspecialchars($profile['interests_hobbies'] ?? ''); ?></textarea>
              <div class="edit-actions">
                <button class="btn primary" onclick="saveSection('interests_hobbies')">Save</button>
                <button class="btn" onclick="cancelEdit('interests_hobbies')">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-header">
            <h3>Skills</h3>
          </div>
          <div class="section-content">
            <div id="skills-list" class="skills-list">
              <?php if (empty($skills)): ?>
                <p class="empty-state">No skills added yet.</p>
              <?php else: ?>
                <?php foreach($skills as $skill): ?>
                  <span class="skill-tag">
                    <?php echo htmlspecialchars($skill); ?>
                    <button class="skill-remove" onclick="removeSkill('<?php echo htmlspecialchars($skill); ?>')">×</button>
                  </span>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button class="add-btn" onclick="showAddSkill()">+ Add Skills</button>
            <div id="add-skill-form" style="display:none; margin-top: 10px;">
              <input type="text" id="new-skill-input" placeholder="Enter skill name" class="skill-input">
              <div style="margin-top: 8px;">
                <button class="btn primary" onclick="addSkill()">Add</button>
                <button class="btn" onclick="hideAddSkill()">Cancel</button>
              </div>
            </div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-header">
            <h3>Certification</h3>
          </div>
          <div class="section-content">
            <div id="certifications-list" class="certifications-list">
              <?php if (empty($certifications)): ?>
                <p class="empty-state">No certifications added yet.</p>
              <?php else: ?>
                <?php foreach($certifications as $cert): ?>
                  <div class="certification-item">
                    <div class="cert-info">
                      <strong><?php echo htmlspecialchars($cert['certification_name']); ?></strong>
                      <?php if ($cert['issuing_organization']): ?>
                        <span class="cert-org"><?php echo htmlspecialchars($cert['issuing_organization']); ?></span>
                      <?php endif; ?>
                      <?php if ($cert['issue_date']): ?>
                        <span class="cert-date">Issued: <?php echo date('M Y', strtotime($cert['issue_date'])); ?></span>
                      <?php endif; ?>
                    </div>
                    <button class="cert-remove" onclick="removeCertification(<?php echo $cert['certification_id']; ?>)">×</button>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <button class="add-btn" onclick="showAddCertification()">+ Add Certification</button>
            <div id="add-certification-form" style="display:none; margin-top: 10px;">
              <div class="form-group">
                <input type="text" id="cert-name" placeholder="Certification Name" class="skill-input" required>
              </div>
              <div class="form-group">
                <input type="text" id="cert-org" placeholder="Issuing Organization" class="skill-input">
              </div>
              <div class="form-group">
                <input type="date" id="cert-issue-date" placeholder="Issue Date" class="skill-input">
              </div>
              <div style="margin-top: 8px;">
                <button class="btn primary" onclick="addCertification()">Add</button>
                <button class="btn" onclick="hideAddCertification()">Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Private Info Tab -->
      <div class="tab-content" id="private-tab">
        <div class="section-card">
          <h3>Private Information</h3>
          <p>This section is for private information that only you can see.</p>
          <p style="color: var(--muted); font-size: 14px;">More private information fields can be added here in the future.</p>
        </div>
      </div>

      <!-- Salary Info Tab -->
      <div class="tab-content" id="salary-tab">
        <?php if (!$isAdmin): ?>
        <div class="section-card" style="background: #fef3c7; border: 1px solid #fbbf24; margin-bottom: 20px;">
          <p style="margin: 0; color: #92400e;"><strong>Note:</strong> Salary information is view-only. Only administrators can edit salary details.</p>
        </div>
        <?php endif; ?>
        <div class="section-card">
          <h3>Salary Overview</h3>
          <div class="salary-overview-grid">
            <div class="salary-field">
              <label>Month Wage</label>
              <div class="salary-value editable-salary" data-field="monthly_wage" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                <span class="value-text">₹<?php echo number_format($salary['monthly_wage'] ?? 50000, 2); ?></span>
                <input type="number" class="value-input" value="<?php echo $salary['monthly_wage'] ?? 50000; ?>" step="0.01" style="display:none">
                <span class="salary-unit">/ Month</span>
              </div>
            </div>
            <div class="salary-field">
              <label>Yearly wage</label>
              <div class="salary-value editable-salary" data-field="yearly_wage" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                <span class="value-text">₹<?php echo number_format($salary['yearly_wage'] ?? 600000, 2); ?></span>
                <input type="number" class="value-input" value="<?php echo $salary['yearly_wage'] ?? 600000; ?>" step="0.01" style="display:none">
                <span class="salary-unit">/ Yearly</span>
              </div>
            </div>
            <div class="salary-field">
              <label>No of working days in a week:</label>
              <div class="salary-value editable-salary" data-field="working_days_per_week" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                <span class="value-text"><?php echo $salary['working_days_per_week'] ?? '5'; ?></span>
                <input type="number" class="value-input" value="<?php echo $salary['working_days_per_week'] ?? 5; ?>" step="0.1" style="display:none">
                <span class="salary-unit">/hrs</span>
              </div>
            </div>
            <div class="salary-field">
              <label>Break Time:</label>
              <div class="salary-value editable-salary" data-field="break_time_hours" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                <span class="value-text"><?php echo $salary['break_time_hours'] ?? '1'; ?></span>
                <input type="number" class="value-input" value="<?php echo $salary['break_time_hours'] ?? 1; ?>" step="0.01" style="display:none">
                <span class="salary-unit">hours</span>
              </div>
            </div>
          </div>
        </div>

        <div class="section-card">
          <h3>Salary Components</h3>
          <div class="salary-components">
            <?php
            $components = [
              ['field' => 'basic_salary', 'label' => 'Basic Salary', 'percent' => 'basic_salary_percent', 'default_percent' => 50.00, 'desc' => 'Define Basic salary from company cost compute it based on monthly Wages.'],
              ['field' => 'hra', 'label' => 'House Rent Allowance (HRA)', 'percent' => 'hra_percent', 'default_percent' => 50.00, 'desc' => 'HRA provided to employees 50% of the basic salary.'],
              ['field' => 'standard_allowance', 'label' => 'Standard Allowance', 'percent' => 'standard_allowance_percent', 'default_percent' => 16.67, 'desc' => 'A standard allowance is a predetermined, fixed amount provided to employee as part of their salary.'],
              ['field' => 'performance_bonus', 'label' => 'Performance Bonus', 'percent' => 'performance_bonus_percent', 'default_percent' => 8.33, 'desc' => 'Variable amount paid during payroll. The value defined by the company and calculated as a % of the basic salary.'],
              ['field' => 'lta', 'label' => 'Leave Travel Allowance (LTA)', 'percent' => 'lta_percent', 'default_percent' => 8.33, 'desc' => 'LTA is paid by the company to employees to cover their travel expenses. and calculated as a % of the basic salary.'],
              ['field' => 'fixed_allowance', 'label' => 'Fixed Allowance', 'percent' => null, 'default_percent' => null, 'desc' => 'fixed allowance portion of wages is determined after calculating all salary components.']
            ];
            foreach($components as $comp):
              $value = $salary[$comp['field']] ?? 0;
              $percent = $comp['percent'] ? ($salary[$comp['percent']] ?? $comp['default_percent']) : null;
            ?>
            <div class="salary-component">
              <div class="component-header">
                <strong><?php echo $comp['label']; ?></strong>
                <div class="component-values">
                  <span class="component-amount editable-salary" data-field="<?php echo $comp['field']; ?>" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    <span class="value-text">₹<?php echo number_format($value, 2); ?></span>
                    <input type="number" class="value-input" value="<?php echo $value; ?>" step="0.01" style="display:none">
                    <span class="salary-unit">/month</span>
                  </span>
                  <?php if ($percent !== null): ?>
                  <span class="component-percent editable-salary" data-field="<?php echo $comp['percent']; ?>" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    (<span class="value-text"><?php echo number_format($percent, 2); ?></span>%
                    <input type="number" class="value-input" value="<?php echo $percent; ?>" step="0.01" style="display:none">)
                  </span>
                  <?php endif; ?>
                </div>
              </div>
              <p class="component-desc"><?php echo $comp['desc']; ?></p>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="section-card">
          <h3>Provident Fund (PF) Contribution</h3>
          <div class="salary-components">
            <div class="salary-component">
              <div class="component-header">
                <strong>Employee</strong>
                <div class="component-values">
                  <span class="component-amount editable-salary" data-field="pf_employee" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    <span class="value-text">₹<?php echo number_format($salary['pf_employee'] ?? 3000, 2); ?></span>
                    <input type="number" class="value-input" value="<?php echo $salary['pf_employee'] ?? 3000; ?>" step="0.01" style="display:none">
                    <span class="salary-unit">/month</span>
                  </span>
                  <span class="component-percent editable-salary" data-field="pf_employee_percent" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    (<span class="value-text"><?php echo number_format($salary['pf_employee_percent'] ?? 12, 2); ?></span>%
                    <input type="number" class="value-input" value="<?php echo $salary['pf_employee_percent'] ?? 12; ?>" step="0.01" style="display:none">)
                  </span>
                </div>
              </div>
              <p class="component-desc">PF is calculated based on the basic salary.</p>
            </div>
            <div class="salary-component">
              <div class="component-header">
                <strong>Employer</strong>
                <div class="component-values">
                  <span class="component-amount editable-salary" data-field="pf_employer" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    <span class="value-text">₹<?php echo number_format($salary['pf_employer'] ?? 3000, 2); ?></span>
                    <input type="number" class="value-input" value="<?php echo $salary['pf_employer'] ?? 3000; ?>" step="0.01" style="display:none">
                    <span class="salary-unit">/month</span>
                  </span>
                  <span class="component-percent editable-salary" data-field="pf_employer_percent" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    (<span class="value-text"><?php echo number_format($salary['pf_employer_percent'] ?? 12, 2); ?></span>%
                    <input type="number" class="value-input" value="<?php echo $salary['pf_employer_percent'] ?? 12; ?>" step="0.01" style="display:none">)
                  </span>
                </div>
              </div>
              <p class="component-desc">PF is calculated based on the basic salary.</p>
            </div>
          </div>
        </div>

        <div class="section-card">
          <h3>Tax Deductions</h3>
          <div class="salary-components">
            <div class="salary-component">
              <div class="component-header">
                <strong>Professional Tax</strong>
                <div class="component-values">
                  <span class="component-amount editable-salary" data-field="professional_tax" <?php echo $isAdmin ? 'data-editable="true"' : ''; ?>>
                    <span class="value-text">₹<?php echo number_format($salary['professional_tax'] ?? 200, 2); ?></span>
                    <input type="number" class="value-input" value="<?php echo $salary['professional_tax'] ?? 200; ?>" step="0.01" style="display:none">
                    <span class="salary-unit">/month</span>
                  </span>
                </div>
              </div>
              <p class="component-desc">Professional Tax deducted from the Gross salary.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="assets/js/profile.js"></script>
  <script>
    const employeeId = <?php echo $employee_id; ?>;
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
  </script>
</body>
</html>

