<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';
require_once './_layout.php';

try {
    $stmt = $pdo->query("
        SELECT r.id, r.gemini_output, r.created_at, u.name AS user_name
        FROM analysis_reports r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Admin reports query failed: ' . $e->getMessage());
    $reports = [];
}

$headerActions = '
    <a href="dashboard.php" class="admin-btn">
        <i data-lucide="layout-grid" size="16"></i>
        <span>Back to Overview</span>
    </a>
';

adminRenderLayoutStart(
    'Audit Logs | KiloWhatt Admin',
    'reports',
    'Audit Logs',
    'Review Gemini-generated analyses with cleaner search and preview controls.',
    $headerActions
);
?>

<section class="admin-panel">
    <div class="admin-panel-body">
        <div class="admin-toolbar">
            <div>
                <h2 class="admin-section-title">Saved Reports</h2>
                <p class="admin-muted">Search by user or date to inspect generated energy audits.</p>
            </div>
            <div class="admin-toolbar-group">
                <input type="search" id="reportSearch" class="admin-search" placeholder="Search user or report date">
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Created</th>
                        <th>Preview</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="3" class="admin-empty">No reports have been generated yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <?php
                            $preview = trim(preg_replace('/\s+/', ' ', strip_tags((string) $report['gemini_output'])));
                            $preview = $preview === '' ? 'Gemini analysis report.' : substr($preview, 0, 130) . (strlen($preview) > 130 ? '...' : '');
                            $encodedReport = htmlspecialchars(base64_encode((string) $report['gemini_output']), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr data-search="<?php echo htmlspecialchars(strtolower($report['user_name'] . ' ' . date('M d, Y g:i A', strtotime($report['created_at'])) . ' ' . $preview)); ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($report['user_name']); ?></strong>
                                </td>
                                <td class="admin-muted"><?php echo date('M d, Y g:i A', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <div class="admin-actions-inline">
                                        <span class="admin-mini-note"><?php echo htmlspecialchars($preview); ?></span>
                                        <button
                                            type="button"
                                            class="admin-btn"
                                            data-report="<?php echo $encodedReport; ?>"
                                            data-user="<?php echo htmlspecialchars($report['user_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-created="<?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($report['created_at'])), ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <i data-lucide="eye" size="16"></i>
                                            <span>Open</span>
                                        </button>
                                    </div>
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
$reportsScript = <<<'SCRIPT'
<script>
    const reportSearch = document.getElementById('reportSearch');
    const reportTableBody = document.getElementById('reportTableBody');

    function filterRows(input, rowSelector) {
        const term = input.value.trim().toLowerCase();
        document.querySelectorAll(rowSelector).forEach((row) => {
            const haystack = row.dataset.search || '';
            row.style.display = haystack.includes(term) ? '' : 'none';
        });
    }

    if (reportSearch) {
        reportSearch.addEventListener('input', () => filterRows(reportSearch, '#reportTableBody tr[data-search]'));
    }

    if (reportTableBody) {
        reportTableBody.addEventListener('click', (event) => {
            const trigger = event.target.closest('button[data-report]');
            if (!trigger) {
                return;
            }

            const reportHtml = atob(trigger.dataset.report);
            const userName = trigger.dataset.user || 'User';
            const createdAt = trigger.dataset.created || '';

            Swal.fire({
                title: `${userName} Report`,
                html: `
                    <div style="text-align:left;">
                        <p style="margin-bottom:16px;color:#64748b;font-size:0.9rem;">Generated ${createdAt}</p>
                        <div style="background:#fff;color:#0f172a;border-radius:20px;padding:24px;max-height:70vh;overflow:auto;">
                            ${reportHtml}
                        </div>
                    </div>
                `,
                width: '900px',
                background: '#0d182a',
                color: '#f8fafc',
                confirmButtonColor: '#f6c21f',
                confirmButtonText: 'Close'
            });
        });
    }
</script>
SCRIPT;

adminRenderLayoutEnd($reportsScript);
