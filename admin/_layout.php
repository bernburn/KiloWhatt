<?php

if (!function_exists('adminRenderLayoutStart')) {
    function adminRenderLayoutStart(string $title, string $activePage, string $pageTitle, string $pageSubtitle = '', string $headerActions = ''): void
    {
        $navItems = [
            'dashboard' => ['label' => 'Overview', 'icon' => 'layout-grid', 'href' => 'dashboard.php'],
            'appliances' => ['label' => 'Appliance Presets', 'icon' => 'database', 'href' => 'appliances.php'],
            'users' => ['label' => 'User Accounts', 'icon' => 'users', 'href' => 'users.php'],
            'reports' => ['label' => 'Audit Logs', 'icon' => 'file-text', 'href' => 'reports.php'],
        ];

        $userNameRaw = (string) ($_SESSION['user_name'] ?? 'Admin');
        $userName = htmlspecialchars($userNameRaw);
        $titleEscaped = htmlspecialchars($title);
        $pageTitleEscaped = htmlspecialchars($pageTitle);
        $pageSubtitleEscaped = htmlspecialchars($pageSubtitle);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titleEscaped; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="./admin.css">
    <link rel="icon" href="../assets/LOGO.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar">
            <div>
                <a href="../index.php" class="admin-brand">
                    <img src="../assets/LOGO.png" width="34" height="34" alt="KiloWhatt logo">
                    <span>KiloWhatt</span>
                </a>

                <div class="admin-sidebar-section">
                    <div class="admin-sidebar-label">Command Center</div>
                    <nav class="admin-nav">
                        <?php foreach ($navItems as $key => $item): ?>
                            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="admin-nav-item <?php echo $activePage === $key ? 'is-active' : ''; ?>">
                                <i data-lucide="<?php echo htmlspecialchars($item['icon']); ?>" size="18"></i>
                                <span><?php echo htmlspecialchars($item['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>

            <div class="admin-sidebar-footer">
                <a href="../dashboard.php" class="admin-nav-item">
                    <i data-lucide="external-link" size="18"></i>
                    <span>Live App</span>
                </a>
                <a href="../api/logout.php" class="admin-nav-item admin-nav-item-danger">
                    <i data-lucide="log-out" size="18"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </aside>

        <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

        <main class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-btn" id="adminSidebarToggle" aria-label="Toggle admin navigation">
                    <i data-lucide="menu" size="20"></i>
                </button>

                <div class="admin-topbar-copy">
                    <p class="admin-eyebrow">Admin Panel</p>
                    <h1><?php echo $pageTitleEscaped; ?></h1>
                    <?php if ($pageSubtitleEscaped !== ''): ?>
                        <p class="muted"><?php echo $pageSubtitleEscaped; ?></p>
                    <?php endif; ?>
                </div>

                <div class="admin-topbar-meta">
                    <?php if ($headerActions !== ''): ?>
                        <div class="admin-topbar-actions"><?php echo $headerActions; ?></div>
                    <?php endif; ?>
                    <div class="admin-user-chip">
                        <span class="admin-user-avatar"><?php echo htmlspecialchars(strtoupper(substr($userNameRaw, 0, 1))); ?></span>
                        <div>
                            <p class="admin-user-label">Signed in</p>
                            <p class="admin-user-name"><?php echo $userName; ?></p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-content">
<?php
    }
}

if (!function_exists('adminRenderLayoutEnd')) {
    function adminRenderLayoutEnd(string $pageScript = ''): void
    {
        ?>
            </div>
        </main>
    </div>

    <script>
        const adminSidebar = document.getElementById('adminSidebar');
        const adminSidebarToggle = document.getElementById('adminSidebarToggle');
        const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');

        function toggleAdminSidebar(forceOpen) {
            if (!adminSidebar) {
                return;
            }

            const shouldOpen = typeof forceOpen === 'boolean'
                ? forceOpen
                : !adminSidebar.classList.contains('is-open');

            adminSidebar.classList.toggle('is-open', shouldOpen);
            if (adminSidebarBackdrop) {
                adminSidebarBackdrop.classList.toggle('is-visible', shouldOpen);
            }
        }

        if (adminSidebarToggle) {
            adminSidebarToggle.addEventListener('click', () => toggleAdminSidebar());
        }

        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.addEventListener('click', () => toggleAdminSidebar(false));
        }

        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
    <?php echo $pageScript; ?>
</body>
</html>
<?php
    }
}
