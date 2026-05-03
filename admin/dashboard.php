<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';

try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalAppliances = $pdo->query("SELECT COUNT(*) FROM user_appliances")->fetchColumn();
    $totalReports = $pdo->query("SELECT COUNT(*) FROM analysis_reports")->fetchColumn();
    $avgRate = $pdo->query("SELECT AVG(computed_rate) FROM user_bills")->fetchColumn() ?: 13.47;
} catch (PDOException $e) {
    $totalUsers = $totalAppliances = $totalReports = 0;
    $avgRate = 13.47;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | KiloWhatt</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --sidebar-width: 280px; }
        body { background: #0f172a; color: #f8fafc; }
        .admin-shell { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: #1e293b; position: fixed; height: 100vh; padding: 32px; border-right: 1px solid rgba(255,255,255,0.05); }
        .brand { display: flex; align-items: center; gap: 12px; font-family: 'Lexend', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--accent); margin-bottom: 48px; text-decoration: none; }
        .nav-list { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { color: var(--accent); }
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 40px; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: #1e293b; padding: 32px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); }
        .stat-label { font-size: 0.875rem; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 2.5rem; font-weight: 800; color: white; font-family: 'Space Grotesk', sans-serif; }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a href="../index.php" class="brand"><img src="../assets/LOGO.png" width="32"> KiloWhatt</a>
            <nav class="nav-list">
                <a href="dashboard.php" class="nav-item active"><i data-lucide="layout-grid"></i> Overview</a>
                <a href="appliances.php" class="nav-item"><i data-lucide="database"></i> Appliance Presets</a>
                <a href="users.php" class="nav-item"><i data-lucide="users"></i> User Accounts</a>
                <a href="reports.php" class="nav-item"><i data-lucide="file-text"></i> Audit Logs</a>
                <hr style="opacity: 0.05; margin: 16px 0;">
                <a href="../dashboard.php" class="nav-item"><i data-lucide="external-link"></i> Live App</a>
                <a href="../api/logout.php" class="nav-item" style="color: #f87171;"><i data-lucide="log-out"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="main-content">
            <header style="margin-bottom: 48px;">
                <h1>Command Center</h1>
                <p class="muted">Real-time system overview.</p>
            </header>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Platform Users</div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Appliances</div>
                    <div class="stat-value"><?php echo $totalAppliances; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">AI Analyses</div>
                    <div class="stat-value"><?php echo $totalReports; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Avg. Rate (PHP)</div>
                    <div class="stat-value">₱<?php echo number_format($avgRate, 2); ?></div>
                </div>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
