<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Enterprise User Accounts & RBAC Management
 * Modern White Enterprise Design System
 */
$pageTitle = 'User Accounts & Roles';
$pageSubtitle = 'Role-Based Access Control (RBAC) • Administrator, Teknisi & Viewer Management';

// Handle API / POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $resp = ['success' => false, 'message' => 'Invalid action'];

    if ($action === 'save_user') {
        $username = $_POST['username'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $role = $_POST['role'] ?? 'viewer';
        $password = $_POST['password'] ?? null;
        if (empty($password)) $password = null;

        $resp = save_user($username, $fullname, $role, $password);
    } elseif ($action === 'delete_user') {
        $username = $_POST['username'] ?? '';
        $resp = delete_user($username);
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_GET['json'])) {
        header('Content-Type: application/json');
        echo json_encode($resp);
        exit;
    }

    if ($resp['success']) {
        $msgType = 'success';
        $msgText = $resp['message'];
    } else {
        $msgType = 'danger';
        $msgText = $resp['message'];
    }
}

$userConfig = get_users_config();
$users = $userConfig['users'] ?? [];
$roles = $userConfig['roles'] ?? [];
?>

        <section id="tab-users" class="tab-pane active">
          <?php if (!empty($msgText)): ?>
            <div class="alert alert-<?= $msgType ?>" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; border-radius: 8px;">
              <?= htmlspecialchars($msgText) ?>
            </div>
          <?php endif; ?>

          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Active Accounts</span>
                <span class="badge badge-success"><?= count($users) ?> ACTIVE</span>
              </div>
              <div class="metric-value"><?= count($users) ?> <span class="unit">Users</span></div>
              <p class="metric-sub">Role-Based Access Control Active</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Security Roles</span>
                <span class="badge badge-indigo">3 TIERS</span>
              </div>
              <div class="metric-value">Tiered <span class="unit">RBAC</span></div>
              <p class="metric-sub">Administrator • Teknisi • Viewer</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Session Hardening</span>
                <span class="badge badge-emerald">SECURE</span>
              </div>
              <div class="metric-value">BCrypt <span class="unit">Cost 10</span></div>
              <p class="metric-sub">HttpOnly • SameSite Lax • Secure Cookies</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-emerald); width: 100%;"></div></div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Active Session</span>
                <span class="badge badge-cyan"><?= strtoupper(htmlspecialchars(get_current_user_role())) ?></span>
              </div>
              <div class="metric-value"><?= htmlspecialchars($_SESSION['mitranet_user'] ?? 'admin') ?></div>
              <p class="metric-sub"><?= htmlspecialchars($_SESSION['mitranet_fullname'] ?? 'System User') ?></p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
          </div>

          <!-- Roles Information Card -->
          <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
              <h3>Role Privilege Definitions</h3>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <div style="padding: 1rem; border: 1px solid #c7d2fe; background: #f5f3ff; border-radius: 8px;">
                    <span class="badge badge-indigo" style="margin-bottom: 0.5rem;">ADMINISTRATOR</span>
                    <h5 style="margin-bottom: 0.5rem; font-size: 1rem; font-weight: 700;">Full System Control</h5>
                    <p style="font-size: 0.8125rem; color: #475569; margin: 0;">Full read/write privileges: network interfaces, firewall, QoS, VPN, routing, terminal shell, and user management.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div style="padding: 1rem; border: 1px solid #bae6fd; background: #f0f9ff; border-radius: 8px;">
                    <span class="badge badge-cyan" style="margin-bottom: 0.5rem;">TEKNISI</span>
                    <h5 style="margin-bottom: 0.5rem; font-size: 1rem; font-weight: 700;">Field Network Engineer</h5>
                    <p style="font-size: 0.8125rem; color: #475569; margin: 0;">Operational privileges: configure network, firewall, QoS, VPN, routing, and run diagnostics. Terminal shell &amp; User management restricted.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div style="padding: 1rem; border: 1px solid #bbf7d0; background: #f0fdf4; border-radius: 8px;">
                    <span class="badge badge-emerald" style="margin-bottom: 0.5rem;">VIEWER</span>
                    <h5 style="margin-bottom: 0.5rem; font-size: 1rem; font-weight: 700;">Read-Only Monitoring</h5>
                    <p style="font-size: 0.8125rem; color: #475569; margin: 0;">Monitoring privileges: inspect dashboard, interface traffic, firewall rules, and telemetry. Configuration mutations are blocked.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- User Management Table -->
          <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin-bottom: 0.25rem;">Configured User Accounts</h3>
                <span class="badge badge-cyan"><?= count($users) ?> Accounts Registered</span>
              </div>
              <button class="btn btn-primary" onclick="openAddUserModal()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.35rem;">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add New User
              </button>
            </div>
            <div class="card-body">
              <div class="data-table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Username</th>
                      <th>Full Name</th>
                      <th>Assigned Role</th>
                      <th>Created Date</th>
                      <th>Last Login</th>
                      <th style="text-align: right;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $u): ?>
                      <?php
                        $roleClass = 'badge-emerald';
                        if ($u['role'] === 'administrator') $roleClass = 'badge-indigo';
                        elseif ($u['role'] === 'teknisi') $roleClass = 'badge-cyan';
                      ?>
                      <tr>
                        <td class="mono font-bold text-cyan"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['fullname'] ?? '-') ?></td>
                        <td><span class="badge <?= $roleClass ?>"><?= strtoupper(htmlspecialchars($u['role'])) ?></span></td>
                        <td class="mono" style="font-size: 0.8125rem;"><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
                        <td class="mono" style="font-size: 0.8125rem;"><?= htmlspecialchars($u['last_login'] ?? 'Never') ?></td>
                        <td style="text-align: right;">
                          <button class="btn btn-sm btn-secondary" onclick='openEditUserModal(<?= json_encode($u) ?>)' style="margin-right: 0.35rem;">Edit / Password</button>
                          <?php if ($u['username'] !== ($_SESSION['mitranet_user'] ?? '')): ?>
                            <button class="btn btn-sm btn-outline text-danger" onclick="confirmDeleteUser('<?= htmlspecialchars($u['username']) ?>')">Delete</button>
                          <?php else: ?>
                            <span class="badge badge-secondary" style="font-size: 0.75rem;">Current Session</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- Add/Edit User Modal -->
        <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-lg);">
              <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding: 1.25rem 1.5rem;">
                <h5 class="modal-title font-bold" id="userModalTitle">Add New System User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <form method="POST" action="users.php">
                <input type="hidden" name="action" value="save_user">
                <div class="modal-body" style="padding: 1.5rem;">
                  <div class="mb-3">
                    <label class="form-label font-bold" style="font-size: 0.8125rem;">Username</label>
                    <input type="text" name="username" id="modal-username" class="form-control" required placeholder="e.g. johan, teknisi2" pattern="[a-zA-Z0-9_\-\.]{3,32}">
                    <div class="form-text" style="font-size: 0.75rem;">Alphanumeric 3-32 characters (lowercase recommended).</div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label font-bold" style="font-size: 0.8125rem;">Full Name</label>
                    <input type="text" name="fullname" id="modal-fullname" class="form-control" required placeholder="e.g. Johan Network Engineer">
                  </div>

                  <div class="mb-3">
                    <label class="form-label font-bold" style="font-size: 0.8125rem;">Security Role Level</label>
                    <select name="role" id="modal-role" class="form-select" required>
                      <option value="administrator">Administrator (Full System &amp; Terminal Access)</option>
                      <option value="teknisi" selected>Teknisi (Network, Firewall &amp; VPN Engineer)</option>
                      <option value="viewer">Viewer (Read-Only NOC Observer)</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label font-bold" style="font-size: 0.8125rem;" id="modal-pass-label">Password</label>
                    <input type="password" name="password" id="modal-password" class="form-control" placeholder="Minimum 6 characters" minlength="6">
                    <div class="form-text" id="modal-pass-help" style="font-size: 0.75rem;">Enter a strong password.</div>
                  </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color); padding: 1rem 1.5rem;">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-primary" id="modal-submit-btn">Save User Account</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Form -->
        <form id="deleteUserForm" method="POST" action="users.php" style="display: none;">
          <input type="hidden" name="action" value="delete_user">
          <input type="hidden" name="username" id="delete-username">
        </form>

<script>
let userModalInstance = null;

function openAddUserModal() {
  document.getElementById('userModalTitle').textContent = 'Add New System User';
  const uInput = document.getElementById('modal-username');
  uInput.value = '';
  uInput.readOnly = false;
  document.getElementById('modal-fullname').value = '';
  document.getElementById('modal-role').value = 'teknisi';
  const pInput = document.getElementById('modal-password');
  pInput.value = '';
  pInput.required = true;
  document.getElementById('modal-pass-label').textContent = 'Password';
  document.getElementById('modal-pass-help').textContent = 'Required (minimum 6 characters).';
  document.getElementById('modal-submit-btn').textContent = 'Create User Account';

  if (!userModalInstance) {
    userModalInstance = new bootstrap.Modal(document.getElementById('userModal'));
  }
  userModalInstance.show();
}

function openEditUserModal(user) {
  document.getElementById('userModalTitle').textContent = 'Edit User: ' + user.username;
  const uInput = document.getElementById('modal-username');
  uInput.value = user.username;
  uInput.readOnly = true;
  document.getElementById('modal-fullname').value = user.fullname || '';
  document.getElementById('modal-role').value = user.role;
  const pInput = document.getElementById('modal-password');
  pInput.value = '';
  pInput.required = false;
  document.getElementById('modal-pass-label').textContent = 'New Password (Optional)';
  document.getElementById('modal-pass-help').textContent = 'Leave blank to keep existing password.';
  document.getElementById('modal-submit-btn').textContent = 'Update User';

  if (!userModalInstance) {
    userModalInstance = new bootstrap.Modal(document.getElementById('userModal'));
  }
  userModalInstance.show();
}

function confirmDeleteUser(username) {
  if (confirm('Are you sure you want to permanently delete user "' + username + '"?')) {
    document.getElementById('delete-username').value = username;
    document.getElementById('deleteUserForm').submit();
  }
}
</script>
