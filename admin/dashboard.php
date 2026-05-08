<?php
require_once '../api/session_check.php';
requireAdmin();
require_once '../api/db.php';
require_once './_layout.php';

function formatCurrency(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

$totalUsers = 0;
$totalAppliances = 0;
$totalReports = 0;
$averageRate = 13.47;
$activeUsers = 0;
$estimatedMonthlyKwh = 0.0;
$averageMonthlyCost = 0.0;
$topAppliances = [];
$topConsumption = [];
$recentReports = [];
$reportTrendLabels = [];
$reportTrendValues = [];

try {
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalAppliances = (int) $pdo->query("SELECT COUNT(*) FROM user_appliances")->fetchColumn();
    $totalReports = (int) $pdo->query("SELECT COUNT(*) FROM analysis_reports")->fetchColumn();
    $averageRate = (float) ($pdo->query("SELECT COALESCE(AVG(computed_rate), 13.47) FROM user_bills")->fetchColumn() ?: 13.47);

    $activeUsers = (int) $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT DISTINCT user_id FROM analysis_reports WHERE created_at >= NOW() - INTERVAL '30 days'
            UNION
            SELECT DISTINCT user_id FROM user_appliances WHERE created_at >= NOW() - INTERVAL '30 days'
        ) active_users
    ")->fetchColumn();

    $estimatedMonthlyKwh = (float) ($pdo->query("
        SELECT COALESCE(SUM((watts * (usage_behavior_percent / 100.0) * hours_per_day * 30) / 1000.0), 0)
        FROM user_appliances
    ")->fetchColumn() ?: 0);

    $averageMonthlyCost = (float) ($pdo->query("
        SELECT COALESCE(AVG(user_totals.total_cost), 0)
        FROM (
            SELECT
                ua.user_id,
                SUM(
                    ((ua.watts * (ua.usage_behavior_percent / 100.0) * ua.hours_per_day * 30) / 1000.0)
                    * COALESCE(latest_bill.computed_rate, 13.47)
                ) AS total_cost
            FROM user_appliances ua
            LEFT JOIN (
                SELECT DISTINCT ON (user_id) user_id, computed_rate
                FROM user_bills
                ORDER BY user_id, created_at DESC
            ) latest_bill ON latest_bill.user_id = ua.user_id
            GROUP BY ua.user_id
        ) user_totals
    ")->fetchColumn() ?: 0);

    $topAppliances = $pdo->query("
        SELECT
            COALESCE(NULLIF(TRIM(custom_name), ''), 'Unnamed Appliance') AS appliance_name,
            COUNT(*) AS total_entries
        FROM user_appliances
        GROUP BY COALESCE(NULLIF(TRIM(custom_name), ''), 'Unnamed Appliance')
        ORDER BY total_entries DESC, appliance_name ASC
        LIMIT 5
    ")->fetchAll();

    $topConsumption = $pdo->query("
        SELECT
            COALESCE(NULLIF(TRIM(custom_name), ''), 'Unnamed Appliance') AS appliance_name,
            SUM((watts * (usage_behavior_percent / 100.0) * hours_per_day * 30) / 1000.0) AS total_kwh
        FROM user_appliances
        GROUP BY COALESCE(NULLIF(TRIM(custom_name), ''), 'Unnamed Appliance')
        ORDER BY total_kwh DESC, appliance_name ASC
        LIMIT 5
    ")->fetchAll();

    $recentReports = $pdo->query("
        SELECT r.created_at, u.name AS user_name, r.gemini_output
        FROM analysis_reports r
        JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
        LIMIT 6
    ")->fetchAll();

    $trendRows = $pdo->query("
        SELECT
            TO_CHAR(day_range.day_value, 'Mon DD') AS label,
            COALESCE(day_totals.total_reports, 0) AS total_reports
        FROM generate_series(
            CURRENT_DATE - INTERVAL '6 days',
            CURRENT_DATE,
            INTERVAL '1 day'
        ) AS day_range(day_value)
        LEFT JOIN (
            SELECT DATE(created_at) AS report_day, COUNT(*) AS total_reports
            FROM analysis_reports
            WHERE created_at >= CURRENT_DATE - INTERVAL '6 days'
            GROUP BY DATE(created_at)
        ) AS day_totals
            ON day_totals.report_day = DATE(day_range.day_value)
        ORDER BY day_range.day_value ASC
    ")->fetchAll();

    foreach ($trendRows as $row) {
        $reportTrendLabels[] = $row['label'];
        $reportTrendValues[] = (int) $row['total_reports'];
    }
} catch (PDOException $e) {
    error_log('Admin dashboard query failed: ' . $e->getMessage());
}

$headerActions = '
    <a href="reports.php" class="admin-btn">
        <i data-lucide="history" size="16"></i>
        <span>View Logs</span>
    </a>
    <a href="appliances.php" class="admin-btn admin-btn-primary">
        <i data-lucide="plus" size="16"></i>
        <span>Manage Presets</span>
    </a>
';

adminRenderLayoutStart(
    'Admin Command Center | KiloWhatt',
    'dashboard',
    'Command Center',
    'A cleaner snapshot of usage, activity, and AI report trends across the platform.',
    $headerActions
);
?>

<section class="admin-grid admin-grid-stats">
    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Generated Reports</p>
                <div class="admin-stat-value"><?php echo number_format($totalReports); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="file-bar-chart-2" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Recent AI analyses stored across the platform.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Appliance Entries</p>
                <div class="admin-stat-value"><?php echo number_format($totalAppliances); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="plug-zap" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Tracked appliances currently contributing to estimates.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Avg. Monthly Cost</p>
                <div class="admin-stat-value"><?php echo htmlspecialchars(formatCurrency($averageMonthlyCost)); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="wallet" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Average estimated household cost using the latest saved bill rate.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Estimated Monthly kWh</p>
                <div class="admin-stat-value"><?php echo number_format($estimatedMonthlyKwh, 1); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="activity" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Combined monthly consumption from all saved appliance entries.</p>
    </article>
</section>

<section class="admin-grid admin-grid-stats" style="margin-top: 18px;">
    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Platform Users</p>
                <div class="admin-stat-value"><?php echo number_format($totalUsers); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="users" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Total registered accounts in the system.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Active Users (30d)</p>
                <div class="admin-stat-value"><?php echo number_format($activeUsers); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="user-check" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Users who saved appliances or generated reports in the last 30 days.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Average Saved Rate</p>
                <div class="admin-stat-value"><?php echo htmlspecialchars(formatCurrency($averageRate)); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="badge-cent" size="20"></i></span>
        </div>
        <p class="admin-stat-note">Mean calibrated electricity rate from saved utility bills.</p>
    </article>

    <article class="admin-stat-card">
        <div class="admin-stat-top">
            <div>
                <p class="admin-stat-label">Top Appliance Count</p>
                <div class="admin-stat-value"><?php echo number_format($topAppliances[0]['total_entries'] ?? 0); ?></div>
            </div>
            <span class="admin-icon-pill"><i data-lucide="flame" size="20"></i></span>
        </div>
        <p class="admin-stat-note">
            <?php echo htmlspecialchars($topAppliances[0]['appliance_name'] ?? 'No appliance data yet'); ?>
        </p>
    </article>
</section>

<section class="admin-grid admin-grid-halves" style="margin-top: 18px;">
    <article class="admin-panel admin-chart-card">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Report Activity Trend</h2>
                <p class="admin-muted">Daily AI report volume over the last 7 days.</p>
            </div>
            <span class="admin-badge admin-badge-success">Live report activity</span>
        </div>
        <div class="admin-panel-body">
            <div class="admin-chart-wrap">
                <canvas id="reportsTrendChart"></canvas>
            </div>
        </div>
    </article>

    <article class="admin-panel admin-chart-card">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Most Common Appliances</h2>
                <p class="admin-muted">Most frequently saved appliance names across all users.</p>
            </div>
            <span class="admin-badge admin-badge-accent">Top 5 volume</span>
        </div>
        <div class="admin-panel-body">
            <div class="admin-chart-wrap">
                <canvas id="commonAppliancesChart"></canvas>
            </div>
        </div>
    </article>
</section>

<section class="admin-grid admin-grid-halves" style="margin-top: 18px;">
    <article class="admin-panel admin-chart-card">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Highest Energy Consumers</h2>
                <p class="admin-muted">Appliances contributing the largest monthly kWh totals.</p>
            </div>
            <span class="admin-badge">kWh by appliance</span>
        </div>
        <div class="admin-panel-body">
            <div class="admin-chart-wrap">
                <canvas id="consumptionChart"></canvas>
            </div>
        </div>
    </article>

    <article class="admin-panel admin-panel-soft">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Recent Activity</h2>
                <p class="admin-muted">Latest Gemini reports created in the system.</p>
            </div>
        </div>
        <div class="admin-panel-body">
            <?php if (empty($recentReports)): ?>
                <div class="admin-empty">No report activity has been recorded yet.</div>
            <?php else: ?>
                <div class="admin-list">
                    <?php foreach ($recentReports as $report): ?>
                        <?php
                        $preview = trim(preg_replace('/\s+/', ' ', strip_tags((string) $report['gemini_output'])));
                        $preview = $preview === '' ? 'Gemini report saved.' : substr($preview, 0, 110) . (strlen($preview) > 110 ? '...' : '');
                        ?>
                        <div class="admin-list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($report['user_name']); ?></strong>
                                <p class="admin-mini-note"><?php echo htmlspecialchars($preview); ?></p>
                            </div>
                            <div class="admin-mini-note"><?php echo date('M d, Y g:i A', strtotime($report['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="admin-grid admin-grid-halves" style="margin-top: 18px;">
    <article class="admin-panel">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Appliance Hotspots</h2>
                <p class="admin-muted">Quick ranked view of the appliances appearing most often.</p>
            </div>
        </div>
        <div class="admin-panel-body">
            <?php if (empty($topAppliances)): ?>
                <div class="admin-empty">Add or save more appliance entries to unlock this list.</div>
            <?php else: ?>
                <div class="admin-list">
                    <?php foreach ($topAppliances as $index => $item): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>#<?php echo $index + 1; ?> <?php echo htmlspecialchars($item['appliance_name']); ?></strong>
                                <p class="admin-mini-note"><?php echo (int) $item['total_entries']; ?> saved entries</p>
                            </div>
                            <span class="admin-badge"><?php echo (int) $item['total_entries']; ?> records</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>

    <article class="admin-panel">
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-section-title">Energy Leaders</h2>
                <p class="admin-muted">Largest estimated monthly consumers from saved appliance data.</p>
            </div>
        </div>
        <div class="admin-panel-body">
            <?php if (empty($topConsumption)): ?>
                <div class="admin-empty">No energy estimates are available yet.</div>
            <?php else: ?>
                <div class="admin-list">
                    <?php foreach ($topConsumption as $index => $item): ?>
                        <div class="admin-list-item">
                            <div>
                                <strong>#<?php echo $index + 1; ?> <?php echo htmlspecialchars($item['appliance_name']); ?></strong>
                                <p class="admin-mini-note">Estimated monthly load</p>
                            </div>
                            <span class="admin-badge admin-badge-accent"><?php echo number_format((float) $item['total_kwh'], 1); ?> kWh</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php
$dashboardScript = '
<script>
    const reportTrendLabels = ' . json_encode($reportTrendLabels) . ';
    const reportTrendValues = ' . json_encode($reportTrendValues) . ';
    const commonApplianceLabels = ' . json_encode(array_map(static fn ($item) => $item['appliance_name'], $topAppliances)) . ';
    const commonApplianceValues = ' . json_encode(array_map(static fn ($item) => (int) $item['total_entries'], $topAppliances)) . ';
    const consumptionLabels = ' . json_encode(array_map(static fn ($item) => $item['appliance_name'], $topConsumption)) . ';
    const consumptionValues = ' . json_encode(array_map(static fn ($item) => round((float) $item['total_kwh'], 2), $topConsumption)) . ';

    const adminChartDefaults = {
        color: "#9fb0c6",
        borderColor: "rgba(255,255,255,0.08)"
    };

    Chart.defaults.color = adminChartDefaults.color;
    Chart.defaults.borderColor = adminChartDefaults.borderColor;
    Chart.defaults.font.family = "Sora, sans-serif";

    new Chart(document.getElementById("reportsTrendChart"), {
        type: "line",
        data: {
            labels: reportTrendLabels,
            datasets: [{
                label: "Reports",
                data: reportTrendValues,
                borderColor: "#f6c21f",
                backgroundColor: "rgba(246, 194, 31, 0.16)",
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 5,
                pointBackgroundColor: "#0b1220",
                pointBorderColor: "#f6c21f",
                pointBorderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById("commonAppliancesChart"), {
        type: "doughnut",
        data: {
            labels: commonApplianceLabels,
            datasets: [{
                data: commonApplianceValues,
                backgroundColor: ["#f6c21f", "#f59e0b", "#fbbf24", "#fde68a", "#fef3c7"],
                borderColor: "rgba(8, 17, 31, 0.8)",
                borderWidth: 3
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 14, usePointStyle: true } }
            }
        }
    });

    new Chart(document.getElementById("consumptionChart"), {
        type: "bar",
        data: {
            labels: consumptionLabels,
            datasets: [{
                label: "Estimated kWh / month",
                data: consumptionValues,
                backgroundColor: "rgba(246, 194, 31, 0.82)",
                borderColor: "#f59e0b",
                borderWidth: 1.5,
                borderRadius: 10
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
';

adminRenderLayoutEnd($dashboardScript);
