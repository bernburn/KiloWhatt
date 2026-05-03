<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | KiloWhatt Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sidebar-width: 280px; }
        body {
            background:
                radial-gradient(700px 420px at 10% 0%, rgba(20, 184, 166, 0.12), transparent 55%),
                radial-gradient(800px 480px at 90% -10%, rgba(246, 194, 31, 0.12), transparent 60%),
                #0f172a;
            color: #f8fafc;
        }
        .admin-shell { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: #1e293b; position: fixed; height: 100vh; padding: 32px; border-right: 1px solid rgba(255,255,255,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; font-family: 'Space Grotesk', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--accent); margin-bottom: 48px; text-decoration: none; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { color: var(--accent); }
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; }
        
        .data-card { background: #1e293b; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.05); background: transparent; }
        td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a href="../index.php" class="brand"><img src="../assets/LOGO.png" width="32"> KiloWhatt</a>
            <nav class="nav-list">
                <a href="dashboard.php" class="nav-item"><i data-lucide="layout-grid"></i> Overview</a>
                <a href="appliances.php" class="nav-item"><i data-lucide="database"></i> Appliance Presets</a>
                <a href="users.php" class="nav-item active"><i data-lucide="users"></i> User Accounts</a>
                <hr style="opacity: 0.05; margin: 16px 0;">
                <a href="../dashboard.php" class="nav-item"><i data-lucide="external-link"></i> Live App</a>
                <a href="../api/logout.php" class="nav-item" style="color: #f87171;"><i data-lucide="log-out"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="main-content">
            <header style="margin-bottom: 40px;">
                <h1>User Accounts</h1>
                <p class="muted">Manage platform access and assign roles.</p>
            </header>

            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $user['role'] === 'admin' ? 'rgba(246, 202, 6, 0.1)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $user['role'] === 'admin' ? 'var(--accent)' : '#94a3b8'; ?>;">
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </td>
                                <td class="muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <button class="btn btn-ghost" style="padding: 6px;" 
                                                onclick="manageRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>', '<?php echo htmlspecialchars($user['name']); ?>')">
                                            <i data-lucide="shield-check" size="16"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="muted" style="font-size: 0.75rem;">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        async function manageRole(userId, currentRole, userName) {
            const newRole = currentRole === 'admin' ? 'user' : 'admin';
            const actionText = currentRole === 'admin' ? 'Demote to User' : 'Promote to Admin';
            const iconType = currentRole === 'admin' ? 'warning' : 'info';

            const result = await Swal.fire({
                title: `${actionText}?`,
                text: `Are you sure you want to change ${userName}'s role to ${newRole.toUpperCase()}?`,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: currentRole === 'admin' ? '#ef4444' : '#f6ca06',
                confirmButtonText: 'Yes, change role',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                try {
                    const res = await fetch('../api/admin/update_role.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId, role: newRole })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: data.message,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Could not reach the server.', 'error');
                }
            }
        }
    </script>
</body>
</html>
