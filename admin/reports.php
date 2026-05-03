<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';

try {
    $stmt = $pdo->query("
        SELECT r.id, r.gemini_output, r.created_at, u.name as user_name 
        FROM analysis_reports r
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | KiloWhatt Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --sidebar-width: 280px; }
        body { background: #0f172a; color: #f8fafc; margin: 0; font-family: 'Inter', sans-serif; }
        .admin-shell { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: #1e293b; position: fixed; height: 100vh; padding: 32px; border-right: 1px solid rgba(255,255,255,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; font-family: 'Lexend', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--accent); margin-bottom: 48px; text-decoration: none; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { color: var(--accent); }
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; }
        
        .data-card { background: #1e293b; border-radius: 24px; padding: 32px; border: 1px solid rgba(255,255,255,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid rgba(255,255,255,0.05); }
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
                <a href="users.php" class="nav-item"><i data-lucide="users"></i> User Accounts</a>
                <a href="reports.php" class="nav-item active"><i data-lucide="file-text"></i> Audit Logs</a>
                <hr style="opacity: 0.05; margin: 16px 0;">
                <a href="../dashboard.php" class="nav-item"><i data-lucide="external-link"></i> Live App</a>
                <a href="../api/logout.php" class="nav-item" style="color: #f87171;"><i data-lucide="log-out"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="main-content">
            <header style="margin-bottom: 40px;">
                <h1>Audit Logs</h1>
                <p class="muted">Review system-generated energy analysis reports.</p>
            </header>

            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($report['user_name']); ?></td>
                                <td class="muted"><?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-ghost" style="color: white; border: 1px solid rgba(255,255,255,0.1);" onclick='showReport(<?php echo json_encode(str_replace("'", "\'", $report['gemini_output'])); ?>)'>
                                        <i data-lucide="eye" size="16"></i>
                                    </button>
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
        function showReport(html) {
            Swal.fire({
                title: 'Audit Report',
                html: `<div style="text-align: left; padding: 2rem; color: #1e293b; background: white; max-height: 70vh; overflow-y: auto;">${html}</div>`,
                width: '800px',
                background: '#fff',
                confirmButtonColor: '#0f172a',
                confirmButtonText: 'Close'
            });
        }
    </script>
</body>
</html>
