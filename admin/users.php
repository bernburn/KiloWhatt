<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';
require_once './_layout.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin users query failed: ' . $e->getMessage());
    $users = [];
}

$headerActions = '
    <span class="admin-badge admin-badge-accent">
        <i data-lucide="shield" size="14"></i>
        <span>Role management enabled</span>
    </span>
';

adminRenderLayoutStart(
    'User Management | KiloWhatt Admin',
    'users',
    'User Accounts',
    'Manage access, filter roles quickly, and keep the account table readable at scale.',
    $headerActions
);
?>

<section class="admin-panel">
    <div class="admin-panel-body">
        <div class="admin-toolbar">
            <div>
                <h2 class="admin-section-title">Platform Users</h2>
                <p class="admin-muted">Search by name or email, then promote or demote roles safely.</p>
            </div>
            <div class="admin-toolbar-group">
                <input type="search" id="userSearch" class="admin-search" placeholder="Search name or email">
                <select id="roleFilter" class="admin-select">
                    <option value="all">All roles</option>
                    <option value="admin">Admins</option>
                    <option value="user">Users</option>
                </select>
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="admin-empty">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr
                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                data-search="<?php echo htmlspecialchars(strtolower($user['name'] . ' ' . $user['email'])); ?>"
                            >
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="admin-badge <?php echo $user['role'] === 'admin' ? 'admin-badge-accent' : ''; ?>">
                                        <?php echo strtoupper(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td class="admin-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <button
                                            class="admin-btn"
                                            type="button"
                                            onclick="manageRole(<?php echo (int) $user['id']; ?>, '<?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>')"
                                        >
                                            <i data-lucide="shield-check" size="16"></i>
                                            <span>Change Role</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="admin-mini-note">Current session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
$usersScript = <<<'SCRIPT'
<script>
    const userSearch = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');

    function applyUserFilters() {
        const term = (userSearch.value || '').trim().toLowerCase();
        const role = roleFilter.value;

        document.querySelectorAll('#usersTableBody tr[data-search]').forEach((row) => {
            const matchesSearch = (row.dataset.search || '').includes(term);
            const matchesRole = role === 'all' || row.dataset.role === role;
            row.style.display = matchesSearch && matchesRole ? '' : 'none';
        });
    }

    userSearch.addEventListener('input', applyUserFilters);
    roleFilter.addEventListener('change', applyUserFilters);

    async function manageRole(userId, currentRole, userName) {
        const newRole = currentRole === 'admin' ? 'user' : 'admin';
        const actionText = currentRole === 'admin' ? 'Demote to User' : 'Promote to Admin';

        const result = await Swal.fire({
            title: `${actionText}?`,
            text: `Change ${userName}'s role to ${newRole.toUpperCase()}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: currentRole === 'admin' ? '#ef4444' : '#f6c21f',
            background: '#0d182a',
            color: '#f8fafc',
            confirmButtonText: 'Yes, update role'
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            const res = await fetch('../api/admin/update_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, role: newRole })
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Unable to update role.');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Role updated',
                text: data.message,
                background: '#0d182a',
                color: '#f8fafc',
                confirmButtonColor: '#f6c21f'
            });

            window.location.reload();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Update failed',
                text: error.message,
                background: '#0d182a',
                color: '#f8fafc',
                confirmButtonColor: '#f87171'
            });
        }
    }
</script>
SCRIPT;

adminRenderLayoutEnd($usersScript);
