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
        .appliance-item {
            background: white; border-radius: var(--radius); padding: 1.5rem; border: 1px solid var(--border);
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto;
            gap: 1.5rem; align-items: end; margin-bottom: 1rem; box-shadow: var(--shadow-sm);
        }
        .autocomplete-list .auto-item { padding: 10px 16px; cursor: pointer; transition: background 0.2s; }
        .autocomplete-list .auto-item:hover { background: var(--bg); }
    </style>
</head>
<body>
    <div class="min-h-screen bg-slate-50 flex">
        <aside class="fixed lg:sticky top-0 left-0 w-72 h-screen bg-ink text-white p-8 flex flex-col z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300" id="sidebar">
            <a href="index.php" class="flex items-center gap-3 text-accent font-extrabold text-xl mb-12">
                <img src="./assets/LOGO.png" width="32" alt="Logo"> KiloWhatt
            </a>
            <nav class="flex flex-col gap-2">
                <div class="cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10 active" onclick="showSection('overview', this)"><i data-lucide="layout-dashboard" size="20"></i> Dashboard</div>
                <div class="cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('appliances', this)"><i data-lucide="plug" size="20"></i> My Appliances</div>
                <div class="cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('bill', this)"><i data-lucide="receipt" size="20"></i> Bill Calibration</div>
                <div class="cursor-pointer flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition hover:bg-white/10" onclick="showSection('recommendations', this)"><i data-lucide="sparkles" size="20"></i> AI Insights</div>
                <?php if (isAdmin()): ?>
                    <div class="my-4 border-t border-white/10"></div>
                    <a href="admin/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-accent hover:bg-white/10"><i data-lucide="shield-check" size="20"></i> Admin Panel</a>
                <?php endif; ?>
            </nav>
            <a href="api/logout.php" class="mt-auto flex items-center gap-2 text-red-400 font-semibold p-4">
                <i data-lucide="log-out" size="20"></i> Logout
            </a>
        </aside>

        <div class="flex-1 w-full">
            <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center sticky top-0 z-40">
                <button class="lg:hidden p-2 bg-slate-100 rounded-lg" onclick="toggleSidebar()"><i data-lucide="menu"></i></button>
                <div class="flex items-center gap-4 ml-auto">
                    <span class="text-slate-600">Welcome, <strong class="text-slate-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <div class="w-10 h-10 bg-accent rounded-xl flex items-center justify-center font-bold text-ink">
                        <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                    </div>
                </div>
            </header>

            <div class="p-4 lg:p-10 max-w-7xl mx-auto">
                <!-- Overview -->
                <div id="section-overview" class="dashboard-section animate-fade">
                    <h2 class="text-2xl font-bold mb-8">Platform Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-4">
                            <div class="bg-amber-50 text-amber-600 p-3 rounded-xl"><i data-lucide="wallet"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Monthly Cost</p><h3 class="text-xl font-bold" id="stat-total-cost">₱0.00</h3></div>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-4">
                            <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl"><i data-lucide="zap"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Consumption</p><h3 class="text-xl font-bold" id="stat-total-kwh">0.00 kWh</h3></div>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-4">
                            <div class="bg-blue-50 text-blue-600 p-3 rounded-xl"><i data-lucide="box"></i></div>
                            <div><p class="text-xs text-slate-500 uppercase font-semibold">Appliances</p><h3 class="text-xl font-bold" id="stat-active-appliances">0</h3></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-3xl border border-slate-100 p-12 text-center shadow-sm">
                        <i data-lucide="bar-chart-2" size="48" class="text-slate-300 mx-auto mb-4"></i>
                        <p class="text-slate-500">Add appliances to begin tracking your trends.</p>
                    </div>
                </div>

                <!-- Appliances -->
                <div id="section-appliances" class="dashboard-section animate-fade" style="display: none;">
                    <div class="flex justify-between items-center mb-8">
                        <div><h2 class="text-2xl font-bold">My Appliances</h2><p class="text-slate-500">Manage your household energy usage.</p></div>
                        <div class="flex gap-3">
                            <button id="resetRows" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-semibold transition">Clear All</button>
                            <button id="addRow" class="px-4 py-2 bg-ink text-white hover:bg-slate-800 rounded-lg text-sm font-semibold transition flex items-center gap-2"><i data-lucide="plus" size="16"></i> Add New</button>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                        <input type="hidden" id="global-rate" value="13.4702">
                        <div id="appliance-list"></div>
                        <div class="mt-8 pt-6 border-t border-slate-100 text-right">
                            <button id="generateButton" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold shadow-lg hover:bg-emerald-700 transition flex items-center gap-2 ml-auto">
                                <i data-lucide="brain-circuit" size="18"></i> Generate AI Audit
                            </button>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm mt-6">
                        <h3 class="text-lg font-bold">Real-time Estimate</h3>
                        <div id="analysisArea" class="mt-4" style="overflow-x: auto;"></div>
                    </div>
                </div>

                <!-- Bill Calibration -->
                <div id="section-bill" class="dashboard-section animate-fade" style="display: none;">
                    <div class="bg-white p-12 rounded-3xl border border-slate-100 shadow-sm max-w-xl mx-auto text-center">
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
                                <h3 id="computed-rate-display" class="text-lg font-bold">₱0.00 / kWh</h3>
                            </div>
                            <button type="submit" class="w-full py-3 bg-ink text-white rounded-xl font-bold hover:bg-slate-800 transition">Apply Calibration <i data-lucide="refresh-cw" size="16"></i></button>
                        </form>
                    </div>
                </div>

                <!-- Recommendations -->
                <div id="section-recommendations" class="dashboard-section animate-fade" style="display: none;">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-bold">Expert Audit</h2>
                        <button id="downloadGeminiOutput" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-semibold transition flex items-center gap-2"><i data-lucide="file-down" size="16"></i> Export PDF</button>
                    </div>
                    <div id="geminiContent">
                        <div class="bg-white p-12 rounded-3xl border border-slate-100 shadow-sm text-center">
                            <i data-lucide="sparkles" size="48" class="text-accent mx-auto mb-4"></i>
                            <h3 class="text-lg font-bold">AI Auditor is ready</h3>
                            <p class="text-slate-500">Add appliances and click generate to receive your personalized audit.</p>
                        </div>
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
            el.classList.add('active');
            if(window.innerWidth < 1024) toggleSidebar();
        }
    </script>
    <script type="module" src="index.js"></script>
</body>
</html>
