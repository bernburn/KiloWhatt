<?php
require_once '../api/session_check.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Presets | KiloWhatt Admin</title>
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
        tr:hover td { background: rgba(255,255,255,0.02); }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(4px); }
        .modal-content { background: #1e293b; margin: 10% auto; padding: 40px; border-radius: 24px; width: 480px; border: 1px solid rgba(255,255,255,0.1); color: white; }
        .modal-content input { background: #0f172a; border-color: rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body>
    <div class="admin-shell">
        <aside class="sidebar">
            <a href="../index.php" class="brand"><img src="../assets/LOGO.png" width="32"> KiloWhatt</a>
            <nav class="nav-list">
                <a href="dashboard.php" class="nav-item"><i data-lucide="layout-grid"></i> Overview</a>
                <a href="appliances.php" class="nav-item active"><i data-lucide="database"></i> Appliance Presets</a>
                <a href="users.php" class="nav-item"><i data-lucide="users"></i> User Accounts</a>
                <hr style="opacity: 0.05; margin: 16px 0;">
                <a href="../dashboard.php" class="nav-item"><i data-lucide="external-link"></i> Live App</a>
                <a href="../api/logout.php" class="nav-item" style="color: #f87171;"><i data-lucide="log-out"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
                <h1>Appliance Database</h1>
                <button class="btn btn-success" onclick="openModal()"><i data-lucide="plus"></i> Add Preset</button>
            </header>

            <div class="data-card">
                <table>
                    <thead>
                        <tr>
                            <th>Appliance</th>
                            <th>Category</th>
                            <th>Default Power</th>
                            <th>Behavior</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="presets-tbody"></tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="presetModal" class="modal">
        <div class="modal-content animate-fade">
            <h2 id="modalTitle" style="margin-bottom: 24px;">New Appliance Preset</h2>
            <form id="preset-form">
                <input type="hidden" id="preset-id">
                <div class="form-group">
                    <label style="color: #94a3b8;">Appliance Name</label>
                    <input type="text" id="p-name" placeholder="e.g. Premium Aircon" required>
                </div>
                <div class="form-group">
                    <label style="color: #94a3b8;">Category</label>
                    <input type="text" id="p-category" placeholder="e.g. Cooling" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label style="color: #94a3b8;">Watts</label>
                        <input type="number" id="p-watts" placeholder="1200" required>
                    </div>
                    <div class="form-group">
                        <label style="color: #94a3b8;">Usage %</label>
                        <input type="number" id="p-behavior" value="100" required>
                    </div>
                </div>
                <div style="display: flex; gap: 12px; margin-top: 32px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Save Preset</button>
                    <button type="button" class="btn btn-ghost" style="flex: 1;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        async function fetchPresets() {
            const res = await fetch('../api/get_presets.php');
            const presets = await res.json();
            const tbody = document.getElementById('presets-tbody');
            tbody.innerHTML = '';
            
            presets.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="font-weight: 600;">${p.name}</td>
                    <td><span class="badge" style="background: rgba(255,255,255,0.05); color: #94a3b8;">${p.category}</span></td>
                    <td>${p.default_watts}W</td>
                    <td>${p.default_usage_behavior}%</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-ghost" style="padding: 6px;" onclick='editPreset(${JSON.stringify(p)})'><i data-lucide="edit-2" size="16"></i></button>
                            <button class="btn btn-danger" style="padding: 6px; background: rgba(239, 68, 68, 0.1);" onclick="deletePreset(${p.id})"><i data-lucide="trash-2" size="16"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            lucide.createIcons();
        }

        const modal = document.getElementById('presetModal');
        function openModal() { modal.style.display = 'block'; }
        function closeModal() { modal.style.display = 'none'; document.getElementById('preset-form').reset(); document.getElementById('preset-id').value = ''; }

        async function editPreset(p) {
            document.getElementById('modalTitle').textContent = "Update Preset";
            document.getElementById('preset-id').value = p.id;
            document.getElementById('p-name').value = p.name;
            document.getElementById('p-category').value = p.category;
            document.getElementById('p-watts').value = p.default_watts;
            document.getElementById('p-behavior').value = p.default_usage_behavior;
            openModal();
        }

        document.getElementById('preset-form').onsubmit = async (e) => {
            e.preventDefault();
            const id = document.getElementById('preset-id').value;
            const data = {
                id: id,
                name: document.getElementById('p-name').value,
                category: document.getElementById('p-category').value,
                watts: document.getElementById('p-watts').value,
                behavior: document.getElementById('p-behavior').value
            };

            const endpoint = id ? '../api/admin/update_preset.php' : '../api/admin/add_preset.php';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                Swal.fire({ icon: 'success', title: 'Saved!', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                closeModal();
                fetchPresets();
            }
        };

        async function deletePreset(id) {
            const result = await Swal.fire({
                title: 'Delete Preset?',
                text: "Users won't see this in autocomplete anymore.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#fff'
            });

            if (result.isConfirmed) {
                const res = await fetch('../api/admin/delete_preset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                if (res.ok) fetchPresets();
            }
        }

        fetchPresets();
    </script>
</body>
</html>
