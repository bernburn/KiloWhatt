<?php
require_once 'api/session_check.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard | KiloWhatt</title>
    <link rel="stylesheet" href="dist/output.css" />
    <link rel="icon" href="./assets/LOGO.png" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js"></script>
    <style>
        .sidebar { position: fixed; top: 0; left: 0; transform: translateX(-100%); transition: 0.3s ease; }
        .sidebar.open { transform: translateX(0); }
        @media (min-width: 1024px) {
            .sidebar { transform: translateX(0); position: sticky; }
            .mobile-toggle { display: none; }
        }
        .dashboard-shell { position: relative; }
        .dashboard-shell::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 280px;
            background: radial-gradient(circle at top right, rgba(246,194,31,0.14), transparent 45%);
            pointer-events: none;
        }
        .nav-link.active { background: rgba(255,255,255,0.08); color: var(--accent); }
        .hero-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0b1220 0%, #111827 58%, #172033 100%);
            color: white;
            border-radius: 32px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 34px 70px -42px rgba(2, 6, 23, 0.8);
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            width: 220px;
            height: 220px;
            right: -80px;
            top: -80px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(246,194,31,0.26), transparent 68%);
        }
        .overview-stat-card {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(148,163,184,0.16);
            box-shadow: 0 16px 38px -30px rgba(2, 6, 23, 0.45);
        }
        .surface-panel {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(148,163,184,0.14);
            box-shadow: 0 18px 48px -34px rgba(2, 6, 23, 0.4);
        }
        .appliance-item {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.95));
            border-radius: 24px;
            padding: 1.35rem;
            border: 1px solid rgba(148, 163, 184, 0.18);
            gap: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 18px 42px -30px rgba(2, 6, 23, 0.35);
        }
        .autocomplete-list .auto-item { padding: 10px 16px; cursor: pointer; transition: background 0.2s; }
        .autocomplete-list .auto-item:hover { background: var(--bg); }
        .preview-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 420px;
        }
        .preview-table th {
            position: sticky;
            top: 0;
            background: #0f172a;
            color: #cbd5e1;
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            padding: 0.95rem 1rem;
        }
        .preview-table td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid rgba(226,232,240,0.9);
        }
        .preview-table tbody tr:nth-child(even) td { background: rgba(248,250,252,0.9); }
        .report-shell { display: grid; gap: 1.25rem; }
        .report-header {
            background: linear-gradient(135deg, #0b1220, #172033);
            color: white;
            border-radius: 28px;
            padding: 1.75rem;
            position: relative;
            overflow: hidden;
        }
        .report-header::after {
            content: "";
            position: absolute;
            inset: auto -70px -70px auto;
            width: 180px;
            height: 180px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(246,194,31,0.22), transparent 70%);
        }
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        .report-kpi {
            border-radius: 22px;
            background: white;
            border: 1px solid rgba(226,232,240,0.9);
            padding: 1rem 1.1rem;
        }
        .report-kpi-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }
        .report-kpi-value {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: #0f172a;
        }
        .report-section {
            background: rgba(255,255,255,0.96);
            border-radius: 24px;
            border: 1px solid rgba(226,232,240,0.92);
            padding: 1.5rem;
            box-shadow: 0 16px 44px -34px rgba(2, 6, 23, 0.32);
        }
        .report-section h3 {
            font-size: 1.15rem;
            margin-bottom: 1rem;
            color: #0f172a;
        }
        .report-html {
            color: #1e293b;
            line-height: 1.7;
            word-break: break-word;
        }
        .report-html table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .report-html th,
        .report-html td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            text-align: left;
            vertical-align: top;
        }
        .report-html th { background: #f8fafc; }
        .report-recommendations { display: grid; gap: 0.85rem; }
        .report-recommendation {
            padding: 1rem 1.1rem;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(246,194,31,0.12), rgba(255,255,255,0.8));
            border: 1px solid rgba(246,194,31,0.22);
            color: #1e293b;
        }
        .report-recommendation strong {
            display: block;
            margin-bottom: 0.3rem;
            color: #0f172a;
        }
        .bill-submit-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .bill-submit-btn svg {
            display: block;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="min-h-screen bg-slate-50 flex dashboard-shell">
        <aside class="fixed lg:sticky top-0 left-0 w-72 h-screen bg-ink text-white p-8 flex flex-col z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
            <a href="index.php" class="flex items-center gap-3 text-accent font-extrabold text-xl mb-12">
                <img src="./assets/LOGO.png" width="32" alt="Logo"> KiloWhatt
            </a>
            <nav class="flex flex-col gap-2">
                <div class="nav-link cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10 active" onclick="showSection('overview', this)"><i data-lucide="layout-dashboard" size="20"></i> Dashboard</div>
                <div class="nav-link cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('appliances', this)"><i data-lucide="plug" size="20"></i> My Appliances</div>
                <div class="nav-link cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('bill', this)"><i data-lucide="receipt" size="20"></i> Bill Calibration</div>
                <div class="nav-link cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('recommendations', this)"><i data-lucide="sparkles" size="20"></i> AI Insights</div>
                <?php if (isAdmin()): ?>
                    <div class="my-4 border-t border-white/10"></div>
                    <a href="admin/dashboard.php" class="nav-link flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-accent hover:bg-white/10"><i data-lucide="shield-check" size="20"></i> Admin Panel</a>
                <?php endif; ?>
            </nav>
            <a href="api/logout.php" class="mt-auto flex items-center gap-2 text-red-400 font-semibold p-4">
                <i data-lucide="log-out" size="20"></i> Logout
            </a>
        </aside>

        <div class="flex-1 w-full">
            <header class="bg-white/90 backdrop-blur border-b border-slate-200 px-8 py-4 flex justify-between items-center sticky top-0 z-40">
                <button class="lg:hidden p-2 bg-slate-100 rounded-lg" onclick="toggleSidebar()"><i data-lucide="menu"></i></button>
                <div class="flex items-center gap-4 ml-auto">
                    <span class="text-slate-600">Welcome, <strong class="text-slate-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center font-bold text-ink">
                        <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                    </div>
                </div>
            </header>

            <div class="p-4 lg:p-10 max-w-7xl mx-auto">
                <div id="section-overview" class="dashboard-section animate-fade">
                    <div class="hero-panel mb-8">
                        <div class="relative z-10 max-w-3xl">
                            <p class="text-accent uppercase tracking-[0.24em] text-xs font-bold mb-3">Energy Intelligence</p>
                            <h2 class="text-3xl lg:text-4xl font-bold mb-4">Track appliances, calibrate costs, and turn Gemini insights into cleaner monthly decisions.</h2>
                            <p class="text-slate-300 max-w-2xl">Your KiloWhatt workspace now gives you a clearer live estimate, cleaner report presentation, and smoother paths into the AI audit flow.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="overview-stat-card p-6 rounded-3xl flex items-center gap-4">
                            <div class="bg-amber-50 text-amber-600 p-3 rounded-xl"><i data-lucide="wallet"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Monthly Cost</p><h3 class="text-xl font-bold" id="stat-total-cost">PHP 0.00</h3></div>
                        </div>
                        <div class="overview-stat-card p-6 rounded-3xl flex items-center gap-4">
                            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl"><i data-lucide="zap"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Consumption</p><h3 class="text-xl font-bold" id="stat-total-kwh">0.00 kWh</h3></div>
                        </div>
                        <div class="overview-stat-card p-6 rounded-3xl flex items-center gap-4">
                            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl"><i data-lucide="box"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Appliances</p><h3 class="text-xl font-bold" id="stat-active-appliances">0</h3></div>
                        </div>
                    </div>
                    <div class="surface-panel rounded-3xl p-12 text-center">
                        <i data-lucide="bar-chart-2" size="48" class="text-slate-300 mx-auto mb-4"></i>
                        <p class="text-slate-500">Add appliances to begin tracking your trends.</p>
                    </div>
                </div>

                <div id="section-appliances" class="dashboard-section animate-fade" style="display: none;">
                    <div class="flex justify-between items-center mb-8">
                        <div><h2 class="text-2xl font-bold">My Appliances</h2><p class="text-slate-500">Manage your household energy usage.</p></div>
                        <div class="flex gap-3">
                            <button id="resetRows" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-semibold transition">Clear All</button>
                            <button id="addRow" class="px-4 py-2 bg-ink text-white hover:bg-slate-800 rounded-lg text-sm font-semibold transition flex items-center gap-2"><i data-lucide="plus" size="16"></i> Add New</button>
                        </div>
                    </div>
                    <div class="surface-panel p-6 rounded-3xl">
                        <input type="hidden" id="global-rate" value="13.4702">
                        <div id="appliance-list"></div>
                        <div class="mt-8 pt-6 border-t border-slate-100 text-right">
                            <button id="generateButton" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold shadow-lg hover:bg-emerald-700 transition flex items-center gap-2 ml-auto">
                                <i data-lucide="brain-circuit" size="18"></i> Generate AI Audit
                            </button>
                        </div>
                    </div>
                    <div class="surface-panel p-6 rounded-3xl mt-6">
                        <h3 class="text-lg font-bold">Real-time Estimate</h3>
                        <div id="analysisArea" class="mt-4 preview-table" style="overflow-x: auto;"></div>
                    </div>
                </div>

                <div id="section-bill" class="dashboard-section animate-fade" style="display: none;">
                    <div class="surface-panel p-12 rounded-3xl max-w-xl mx-auto text-center">
                        <div class="bg-accent text-primary w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="landmark" size="32"></i>
                        </div>
                        <h2 class="text-2xl font-bold">Bill Calibration</h2>
                        <p class="text-slate-500 mb-8">Achieve 90%+ accuracy by using your real utility data.</p>
                        <form id="bill-form" class="text-left space-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Total kWh Consumed</label>
                                <input type="number" id="bill-kwh" step="0.01" placeholder="Found on your bill" required class="w-full p-3 rounded-xl border border-slate-200">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Total Amount (PHP)</label>
                                <input type="number" id="bill-amount" step="0.01" placeholder="Total due" required class="w-full p-3 rounded-xl border border-slate-200">
                            </div>
                            <div id="calibration-result" style="display: none;" class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-900">
                                <p class="text-xs font-bold uppercase tracking-wider mb-1 text-emerald-600">Calibrated Rate</p>
                                <h3 id="computed-rate-display" class="text-lg font-bold">PHP 0.00 / kWh</h3>
                            </div>
                            <button type="submit" class="bill-submit-btn w-full py-3 bg-ink text-white rounded-xl font-bold hover:bg-slate-800 transition">Apply Calibration <i data-lucide="refresh-cw" size="16"></i></button>
                        </form>
                    </div>
                </div>

                <div id="section-recommendations" class="dashboard-section animate-fade" style="display: none;">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-bold">Expert Audit</h2>
                        <button id="downloadGeminiOutput" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-semibold transition flex items-center gap-2"><i data-lucide="file-down" size="16"></i> Export PDF</button>
                    </div>
                    <div id="geminiContent">
                        <div class="surface-panel p-12 rounded-3xl text-center">
                            <i data-lucide="sparkles" size="48" class="text-accent mx-auto mb-4"></i>
                            <h3 class="text-lg font-bold">AI Auditor is ready</h3>
                            <p class="text-slate-500">Add appliances and click generate to receive your personalized audit.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        function showSection(sectionId, el) {
            document.querySelectorAll('.dashboard-section').forEach(s => s.style.display = 'none');
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.getElementById('section-' + sectionId).style.display = 'block';
            if (el) {
                el.classList.add('active');
            }
            if(window.innerWidth < 1024) toggleSidebar();
        }
    </script>
    <script type="module" src="index.js"></script>
</body>
</html>
