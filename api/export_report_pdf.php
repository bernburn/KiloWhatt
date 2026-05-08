<?php
require_once 'session_check.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login.']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

function cleanReportHtml(string $html): string
{
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<div[^>]*id=["\']chart-data-json["\'][^>]*>.*?<\/div>/is', '', $html);
    return trim($html);
}

function formatCurrencyPdf(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

function buildFallbackRecommendations(array $analysis): array
{
    if (empty($analysis)) {
        return [
            ['title' => 'Build a fuller appliance profile', 'body' => 'Add more appliances and daily usage patterns to give the report a stronger baseline.'],
            ['title' => 'Calibrate your bill', 'body' => 'Use a recent utility bill so the monthly estimate can better match your actual household rate.'],
        ];
    }

    usort($analysis, static fn ($a, $b) => ($b['monthlyCost'] ?? 0) <=> ($a['monthlyCost'] ?? 0));
    $top = $analysis[0] ?? null;
    $recommendations = [];

    if ($top) {
        $recommendations[] = [
            'title' => 'Reduce runtime on ' . ($top['name'] ?: 'your highest-cost appliance'),
            'body' => 'This appliance is the largest monthly cost driver. Lowering daily hours or usage intensity here should create the fastest savings.'
        ];
    }

    $recommendations[] = [
        'title' => 'Use calibrated pricing for better accuracy',
        'body' => 'Apply a recent bill in the calibration step so the estimated monthly costs reflect your real household electricity rate.'
    ];

    $recommendations[] = [
        'title' => 'Prioritize appliance upgrades by monthly impact',
        'body' => 'Focus efficiency upgrades on appliances with the highest estimated kWh use before replacing low-impact devices.'
    ];

    return $recommendations;
}

$input = json_decode(file_get_contents('php://input'), true);
$analysis = $input['analysis'] ?? [];
$reportHtml = cleanReportHtml((string) ($input['reportHtml'] ?? ''));
$generatedAt = (string) ($input['generatedAt'] ?? date('c'));
$userName = (string) ($_SESSION['user_name'] ?? 'KiloWhatt User');

if (empty($analysis) && $reportHtml === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No report data provided.']);
    exit;
}

$totalKwh = 0.0;
$totalCost = 0.0;
$applianceRows = '';
$costRows = '';

foreach ($analysis as $item) {
    $name = htmlspecialchars((string) ($item['name'] ?? 'Unnamed Appliance'), ENT_QUOTES, 'UTF-8');
    $watts = (float) ($item['watts'] ?? 0);
    $hours = (float) ($item['hoursUsed'] ?? 0);
    $behavior = (float) ($item['usageBehaviorPercent'] ?? 0);
    $monthlyKwh = (float) ($item['monthlyKwh'] ?? 0);
    $monthlyCost = (float) ($item['monthlyCost'] ?? 0);
    $totalKwh += $monthlyKwh;
    $totalCost += $monthlyCost;

    $applianceRows .= '
        <tr>
            <td>' . $name . '</td>
            <td>' . number_format($watts, 0) . ' W</td>
            <td>' . number_format($hours, 1) . ' hrs/day</td>
            <td>' . number_format($behavior, 0) . '%</td>
            <td>' . number_format($monthlyKwh, 2) . ' kWh</td>
        </tr>';

    $costRows .= '
        <tr>
            <td>' . $name . '</td>
            <td>' . number_format($monthlyKwh, 2) . ' kWh</td>
            <td>' . formatCurrencyPdf($monthlyCost) . '</td>
        </tr>';
}

$recommendations = buildFallbackRecommendations($analysis);
$recommendationMarkup = '';
foreach ($recommendations as $recommendation) {
    $recommendationMarkup .= '
        <div class="recommendation-card">
            <h4>' . htmlspecialchars($recommendation['title'], ENT_QUOTES, 'UTF-8') . '</h4>
            <p>' . htmlspecialchars($recommendation['body'], ENT_QUOTES, 'UTF-8') . '</p>
        </div>';
}

$generatedLabel = date('M d, Y g:i A', strtotime($generatedAt));

$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KiloWhatt Energy Audit</title>
    <style>
        @page { margin: 28px 26px 34px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #122033;
            font-size: 12px;
            line-height: 1.55;
        }
        .report-header {
            background: #0b1220;
            color: #ffffff;
            border-radius: 16px;
            padding: 24px 26px;
            margin-bottom: 18px;
        }
        .report-header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }
        .report-header p {
            margin: 4px 0;
            color: #d7e0eb;
        }
        .meta-row {
            margin-top: 14px;
            font-size: 11px;
        }
        .kpi-grid {
            width: 100%;
            margin-bottom: 18px;
        }
        .kpi-grid td {
            width: 33.33%;
            padding-right: 12px;
            vertical-align: top;
        }
        .kpi-card {
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }
        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .section h2 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #0f172a;
            border-bottom: 2px solid #f6c21f;
            padding-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #dbe3ee;
            padding: 8px 9px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background: #f8fafc;
            color: #334155;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .analysis-html h1,
        .analysis-html h2,
        .analysis-html h3,
        .analysis-html h4 {
            color: #0f172a;
            margin: 14px 0 8px;
            page-break-after: avoid;
        }
        .analysis-html p,
        .analysis-html li {
            margin: 0 0 8px;
            word-wrap: break-word;
        }
        .analysis-html ul,
        .analysis-html ol {
            padding-left: 18px;
        }
        .recommendation-card {
            border: 1px solid #f4d266;
            background: #fff8dc;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }
        .recommendation-card h4 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 13px;
        }
        .recommendation-card p {
            margin: 0;
        }
        .footer-note {
            margin-top: 18px;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>KiloWhatt Energy Audit Report</h1>
        <p>Prepared for ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</p>
        <p>Professional electricity usage review with Gemini-assisted analysis.</p>
        <div class="meta-row">Generated ' . htmlspecialchars($generatedLabel, ENT_QUOTES, 'UTF-8') . '</div>
    </div>

    <table class="kpi-grid" cellspacing="0" cellpadding="0">
        <tr>
            <td><div class="kpi-card"><div class="kpi-label">Total Appliances</div><div class="kpi-value">' . count($analysis) . '</div></div></td>
            <td><div class="kpi-card"><div class="kpi-label">Estimated Monthly kWh</div><div class="kpi-value">' . number_format($totalKwh, 2) . '</div></div></td>
            <td><div class="kpi-card"><div class="kpi-label">Estimated Monthly Cost</div><div class="kpi-value">' . formatCurrencyPdf($totalCost) . '</div></div></td>
        </tr>
    </table>

    <div class="section">
        <h2>Appliance Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Appliance</th>
                    <th>Power</th>
                    <th>Daily Usage</th>
                    <th>Behavior</th>
                    <th>Monthly Consumption</th>
                </tr>
            </thead>
            <tbody>' . $applianceRows . '</tbody>
        </table>
    </div>

    <div class="section">
        <h2>Cost Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Appliance</th>
                    <th>Estimated Monthly kWh</th>
                    <th>Estimated Monthly Cost</th>
                </tr>
            </thead>
            <tbody>' . $costRows . '</tbody>
        </table>
    </div>

    <div class="section">
        <h2>Gemini Analysis</h2>
        <div class="analysis-html">' . $reportHtml . '</div>
    </div>

    <div class="section">
        <h2>Recommendations</h2>
        ' . $recommendationMarkup . '
    </div>

    <div class="footer-note">
        KiloWhatt report export. Values are estimates based on saved appliance inputs and bill calibration data.
    </div>
</body>
</html>';

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    header_remove('Content-Type');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="KiloWhatt-Energy-Audit.pdf"');
    echo $dompdf->output();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to generate PDF.', 'details' => $e->getMessage()]);
}
