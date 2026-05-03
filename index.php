<?php
require_once 'api/session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KiloWhatt | Smart Energy Management for the Philippines</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="./assets/LOGO.png" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .nav-glass {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            box-shadow: var(--shadow-sm);
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
        }
        .hero-section {
            padding: 160px 0 100px;
            background:
                radial-gradient(circle at top right, rgba(246, 194, 31, 0.18), transparent 45%),
                radial-gradient(circle at bottom left, rgba(14, 165, 233, 0.12), transparent 45%);
            text-align: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .hero-title {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--muted);
            max-width: 650px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: -60px;
        }
        .feature-card {
            background: var(--surface);
            padding: 40px;
            border-radius: 24px;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: left;
            box-shadow: var(--shadow);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }
        .icon-box {
            width: 56px;
            height: 56px;
            background: #fff6d6;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            margin-bottom: 24px;
            transition: all 0.3s;
        }
        .feature-card:hover .icon-box {
            background: var(--accent);
            color: var(--primary);
        }
        .cta-banner {
            background: linear-gradient(135deg, #0b1220 0%, #111827 60%, #0f172a 100%);
            border-radius: 32px;
            padding: 80px 40px;
            color: white;
            text-align: center;
            margin: 100px 0;
            position: relative;
            overflow: hidden;
        }
        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: var(--accent);
            filter: blur(120px);
            opacity: 0.15;
        }

        /* --- Auth Modal Styles --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            position: relative;
            animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .close-modal {
            position: absolute;
            top: 24px;
            right: 24px;
            background: var(--bg);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--muted);
            transition: all 0.2s;
        }
        .close-modal:hover { background: #fee2e2; color: var(--danger); }
        
        .auth-tabs {
            display: flex;
            background: var(--bg);
            padding: 4px;
            border-radius: 12px;
            margin-bottom: 32px;
        }
        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .auth-tab.active { background: white; color: var(--primary); box-shadow: var(--shadow-sm); }
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 0.9rem; margin-bottom: 20px; display: none; align-items: center; gap: 10px; }
        .alert-danger { background: #fef2f2; color: var(--danger); border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: var(--success); border: 1px solid #dcfce7; }

        @media (max-width: 760px) {
            .nav-content { flex-direction: column; gap: 12px; }
            .nav-actions { width: 100%; display: flex; justify-content: center; flex-wrap: wrap; gap: 12px; }
            .hero-section { padding: 140px 0 80px; }
            .feature-grid { margin-top: 0; }
            .cta-banner { padding: 56px 24px; margin: 72px 0; }
        }
    </style>
</head>
<body>
    <nav class="nav-glass">
        <div class="container nav-content">
            <a href="index.php" class="nav-logo">
                <img src="./assets/LOGO.png" width="36" alt="KiloWhatt">
                KiloWhatt
            </a>
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard <i data-lucide="arrow-right"></i></a>
                <?php else: ?>
                    <button onclick="openAuth('login')" class="btn btn-ghost" style="margin-right: 12px;">Login</button>
                    <button onclick="openAuth('register')" class="btn btn-primary">Get Started</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container animate-fade">
            <div class="hero-badge">
                <span class="badge" style="background: var(--accent); color: var(--primary);">New</span>
                Gemini 3 Flash Preview Integrated
            </div>
            <h1 class="hero-title text-gradient">Smart Energy for Smart Homes</h1>
            <p class="hero-subtitle">Stop guessing your electricity bill. Get high-accuracy analysis and personalized AI savings advice designed for Philippine households.</p>
            <div style="display: flex; gap: 16px; justify-content: center;">
                <button onclick="openAuth('register')" class="btn btn-success btn-large" style="padding: 16px 32px; border-radius: 100px;">
                    Start Analyzing Now <i data-lucide="sparkles"></i>
                </button>
                <a href="#features" class="btn btn-ghost btn-large" style="padding: 16px 32px; border-radius: 100px;">
                    How it works
                </a>
            </div>
        </div>
    </header>

    <section id="features" class="container">
        <div class="feature-grid">
            <div class="feature-card animate-fade" style="animation-delay: 0.1s;">
                <div class="icon-box"><i data-lucide="activity"></i></div>
                <h3>90%+ Precision</h3>
                <p class="muted">Calibrate calculations using your actual bill data for industry-leading local accuracy.</p>
            </div>
            <div class="feature-card animate-fade" style="animation-delay: 0.2s;">
                <div class="icon-box"><i data-lucide="cpu"></i></div>
                <h3>AI "Lektric" Consultant</h3>
                <p class="muted">Advanced reasoning with Gemini 3 Flash to find hidden "Energy Hogs" in your home.</p>
            </div>
            <div class="feature-card animate-fade" style="animation-delay: 0.3s;">
                <div class="icon-box"><i data-lucide="bar-chart-3"></i></div>
                <h3>Visual Analytics</h3>
                <p class="muted">Beautiful, interactive reports that show exactly where every Peso of your bill goes.</p>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="cta-banner">
            <h2 style="color: white; font-size: 2.5rem; margin-bottom: 16px;">Lower your bill by up to 30%</h2>
            <p style="opacity: 0.8; font-size: 1.125rem; margin-bottom: 32px; max-width: 500px; margin-inline: auto;">
                Join thousands of Filipinos using AI to optimize their home energy efficiency.
            </p>
            <button onclick="openAuth('register')" class="btn btn-success btn-large" style="padding: 18px 48px; border-radius: 100px; font-size: 1.1rem;">
                Create Your Free Account
            </button>
        </div>
    </section>

    <footer style="padding: 60px 0; border-top: 1px solid var(--border); background: white;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
            <div class="nav-logo">
                <img src="./assets/LOGO.png" width="30" alt="KiloWhatt">
                KiloWhatt
            </div>
            <p class="muted" style="font-size: 0.875rem;">&copy; 2026 KiloWhatt Energy. Built with ⚡ in the Philippines.</p>
            <div style="display: flex; gap: 24px;">
                <a href="#" class="muted" style="text-decoration: none;">Privacy</a>
                <a href="#" class="muted" style="text-decoration: none;">Terms</a>
            </div>
        </div>
    </footer>

    <!-- --- Auth Modal --- -->
    <div id="authModal" class="modal-overlay" onclick="closeAuthOnOverlay(event)">
        <div class="auth-card">
            <button class="close-modal" onclick="closeAuth()"><i data-lucide="x" size="20"></i></button>
            
            <div style="text-align: center; margin-bottom: 24px;">
                <img src="./assets/LOGO.png" width="48" alt="Logo" style="margin-bottom: 12px;">
                <h3 id="modalTitle">Welcome to KiloWhatt</h3>
            </div>

            <div class="auth-tabs">
                <div id="login-tab" class="auth-tab" onclick="switchAuth('login')">Login</div>
                <div id="register-tab" class="auth-tab" onclick="switchAuth('register')">Register</div>
                <div id="admin-tab" class="auth-tab" onclick="switchAuth('admin')">Admin</div>
            </div>

            <div id="alert-error" class="alert alert-danger"><i data-lucide="alert-circle" size="18"></i><span id="error-text"></span></div>
            <div id="alert-success" class="alert alert-success"><i data-lucide="check-circle" size="18"></i><span id="success-text"></span></div>

            <!-- Forms Container -->
            <div id="auth-forms">
                <!-- Shared Login/Admin -->
                <form id="login-form">
                    <div class="form-group">
                        <label id="email-label">Email Address</label>
                        <input type="email" id="login-email" placeholder="name@email.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="login-password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="login-btn" style="width: 100%; padding: 14px; margin-top: 10px;">
                        Sign In <i data-lucide="log-in" size="18"></i>
                    </button>
                </form>

                <!-- Register -->
                <form id="register-form" style="display: none;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="reg-name" placeholder="Juan Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="reg-email" placeholder="name@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password (min 8 chars)</label>
                        <input type="password" id="reg-password" placeholder="••••••••" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; margin-top: 10px;">
                        Create Account <i data-lucide="user-plus" size="18"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Auto-open modal if redirected from a protected page
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auth') === 'required') {
            openAuth('login');
            Swal.fire({
                icon: 'info',
                title: 'Login Required',
                text: 'Please sign in to access the dashboard.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
        }

        const authModal = document.getElementById('authModal');
        const loginForm = document.getElementById('login-form');
        const regForm = document.getElementById('register-form');
        const errorAlert = document.getElementById('alert-error');
        const successAlert = document.getElementById('alert-success');

        function openAuth(type) {
            authModal.classList.add('open');
            document.body.style.overflow = 'hidden';
            switchAuth(type);
        }

        function closeAuth() {
            authModal.classList.remove('open');
            document.body.style.overflow = '';
        }

        function closeAuthOnOverlay(e) {
            if (e.target === authModal) closeAuth();
        }

        function switchAuth(type) {
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(type + '-tab').classList.add('active');

            if (type === 'register') {
                loginForm.style.display = 'none';
                regForm.style.display = 'block';
                document.getElementById('modalTitle').textContent = 'Create an account';
            } else {
                loginForm.style.display = 'block';
                regForm.style.display = 'none';
                const isAdmin = type === 'admin';
                document.getElementById('modalTitle').textContent = isAdmin ? 'Admin Access' : 'Welcome back';
                document.getElementById('login-btn').innerHTML = isAdmin ? 'Admin Login <i data-lucide="shield-check" size="18"></i>' : 'Sign In <i data-lucide="log-in" size="18"></i>';
            }
            lucide.createIcons();
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorAlert.style.display = 'none';
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            const res = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await res.json();
            if (res.ok) {
                window.location.href = data.user.role === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
            } else {
                document.getElementById('error-text').textContent = data.error;
                errorAlert.style.display = 'flex';
            }
        });

        regForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorAlert.style.display = 'none';
            successAlert.style.display = 'none';
            
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;

            const res = await fetch('api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });

            const data = await res.json();
            if (res.ok) {
                document.getElementById('success-text').textContent = data.message;
                successAlert.style.display = 'flex';
                regForm.reset();
                setTimeout(() => switchAuth('login'), 2000);
            } else {
                document.getElementById('error-text').textContent = data.error;
                errorAlert.style.display = 'flex';
            }
        });
    </script>
</body>
</html>
