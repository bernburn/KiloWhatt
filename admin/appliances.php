<?php
require_once '../api/session_check.php';
requireAdmin();
require_once './_layout.php';

$headerActions = '
    <button class="admin-btn admin-btn-primary" type="button" onclick="openModal()">
        <i data-lucide="plus" size="16"></i>
        <span>Add Preset</span>
    </button>
';

adminRenderLayoutStart(
    'Manage Presets | KiloWhatt Admin',
    'appliances',
    'Appliance Database',
    'Curate default appliance presets with a cleaner table and faster editing flow.',
    $headerActions
);
?>

<section class="admin-panel">
    <div class="admin-panel-body">
        <div class="admin-toolbar">
            <div>
                <h2 class="admin-section-title">Preset Library</h2>
                <p class="admin-muted">Search, review, and maintain the presets users see in autocomplete.</p>
            </div>
            <div class="admin-toolbar-group">
                <input type="search" id="presetSearch" class="admin-search" placeholder="Search name or category">
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
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
    </div>
</section>

<div id="presetModal" class="modal">
    <div class="admin-modal-card animate-fade">
        <h2 id="modalTitle" style="margin-bottom: 20px;">New Appliance Preset</h2>
        <form id="preset-form" class="admin-form-grid">
            <input type="hidden" id="preset-id">

            <div class="admin-field">
                <label for="p-name">Appliance Name</label>
                <input type="text" id="p-name" class="admin-input" placeholder="e.g. Premium Aircon" required>
            </div>

            <div class="admin-field">
                <label for="p-category">Category</label>
                <input type="text" id="p-category" class="admin-input" placeholder="e.g. Cooling" required>
            </div>

            <div class="admin-form-grid admin-form-grid-2">
                <div class="admin-field">
                    <label for="p-watts">Watts</label>
                    <input type="number" id="p-watts" class="admin-input" placeholder="1200" required>
                </div>

                <div class="admin-field">
                    <label for="p-behavior">Usage %</label>
                    <input type="number" id="p-behavior" class="admin-input" value="100" required>
                </div>
            </div>

            <div class="admin-toolbar-group">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i data-lucide="save" size="16"></i>
                    <span>Save Preset</span>
                </button>
                <button type="button" class="admin-btn" onclick="closeModal()">
                    <i data-lucide="x" size="16"></i>
                    <span>Cancel</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$appliancesScript = <<<'SCRIPT'
<script>
    const modal = document.getElementById('presetModal');
    const presetSearch = document.getElementById('presetSearch');
    const presetsTbody = document.getElementById('presets-tbody');
    let presetRows = [];

    window.openModal = function openModal() {
        modal.style.display = 'block';
    };

    window.closeModal = function closeModal() {
        modal.style.display = 'none';
        document.getElementById('preset-form').reset();
        document.getElementById('preset-id').value = '';
        document.getElementById('modalTitle').textContent = 'New Appliance Preset';
    };

    function applyPresetFilter() {
        const term = (presetSearch.value || '').trim().toLowerCase();
        presetRows.forEach((row) => {
            row.style.display = (row.dataset.search || '').includes(term) ? '' : 'none';
        });
    }

    async function fetchPresets() {
        const res = await fetch('../api/get_presets.php');
        const presets = await res.json();
        presetsTbody.innerHTML = '';

        presets.forEach((preset) => {
            const tr = document.createElement('tr');
            tr.dataset.search = `${preset.name} ${preset.category}`.toLowerCase();
            tr.innerHTML = `
                <td><strong>${preset.name}</strong></td>
                <td><span class="admin-badge">${preset.category || 'Uncategorized'}</span></td>
                <td>${preset.default_watts}W</td>
                <td>${preset.default_usage_behavior}%</td>
                <td>
                    <div class="admin-actions-inline">
                        <button class="admin-btn" type="button" onclick='editPreset(${JSON.stringify(preset)})'>
                            <i data-lucide="pencil" size="16"></i>
                            <span>Edit</span>
                        </button>
                        <button class="admin-btn admin-btn-danger" type="button" onclick="deletePreset(${preset.id})">
                            <i data-lucide="trash-2" size="16"></i>
                            <span>Delete</span>
                        </button>
                    </div>
                </td>
            `;
            presetsTbody.appendChild(tr);
        });

        presetRows = Array.from(presetsTbody.querySelectorAll('tr'));
        applyPresetFilter();
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    window.editPreset = function editPreset(preset) {
        document.getElementById('modalTitle').textContent = 'Update Preset';
        document.getElementById('preset-id').value = preset.id;
        document.getElementById('p-name').value = preset.name;
        document.getElementById('p-category').value = preset.category;
        document.getElementById('p-watts').value = preset.default_watts;
        document.getElementById('p-behavior').value = preset.default_usage_behavior;
        openModal();
    };

    document.getElementById('preset-form').addEventListener('submit', async (event) => {
        event.preventDefault();

        const id = document.getElementById('preset-id').value;
        const data = {
            id,
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

        if (!res.ok) {
            Swal.fire({
                icon: 'error',
                title: 'Save failed',
                text: 'The preset could not be saved.',
                background: '#0d182a',
                color: '#f8fafc',
                confirmButtonColor: '#f87171'
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Preset saved',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2200
        });
        closeModal();
        fetchPresets();
    });

    window.deletePreset = async function deletePreset(id) {
        const result = await Swal.fire({
            title: 'Delete preset?',
            text: "Users will no longer see it in autocomplete suggestions.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            background: '#0d182a',
            color: '#f8fafc'
        });

        if (!result.isConfirmed) {
            return;
        }

        const res = await fetch('../api/admin/delete_preset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        if (res.ok) {
            fetchPresets();
        }
    };

    presetSearch.addEventListener('input', applyPresetFilter);
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    fetchPresets();
</script>
SCRIPT;

adminRenderLayoutEnd($appliancesScript);
