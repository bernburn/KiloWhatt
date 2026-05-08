const DOM = {
  applianceList: document.getElementById("appliance-list"),
  addRowBtn: document.getElementById("addRow"),
  resetRowsBtn: document.getElementById("resetRows"),
  generateBtn: document.getElementById("generateButton"),
  analysisArea: document.getElementById("analysisArea"),
  geminiContent: document.getElementById("geminiContent"),
  globalRateInput: document.getElementById("global-rate"),
  globalAdvancedToggle: document.getElementById("globalAdvancedToggle"),
  globalAdvancedSection: document.getElementById("globalAdvancedSection"),
  billForm: document.getElementById("bill-form"),
  sidebar: document.getElementById("sidebar"),
  downloadPdfBtn: document.getElementById("downloadGeminiOutput"),
};

const CURRENCY_FORMATTER = new Intl.NumberFormat("en-PH", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

let state = {
  presets: [],
  idCounter: 0,
  latestAnalysis: [],
  latestRawReportHtml: "",
  latestGeneratedAt: "",
  latestChartData: null,
};

async function init() {
  await fetchPresets();
  await fetchUserAppliances();
  setupEventListeners();
  setAdvancedOptionsOpen(false);
  refreshIcons();
}

function setupEventListeners() {
  if (DOM.addRowBtn) {
    DOM.addRowBtn.addEventListener("click", handleAddAppliance);
  }

  if (DOM.resetRowsBtn) {
    DOM.resetRowsBtn.addEventListener("click", handleResetAppliances);
  }

  if (DOM.globalAdvancedToggle && DOM.globalAdvancedSection) {
    DOM.globalAdvancedToggle.addEventListener("click", toggleAdvancedOptions);
  }

  document.addEventListener("click", (event) => {
    document.querySelectorAll(".autocomplete-list").forEach((list) => {
      if (!list.parentElement?.contains(event.target)) {
        list.style.display = "none";
      }
    });
  });

  if (DOM.generateBtn) {
    DOM.generateBtn.addEventListener("click", generateAuditReport);
  }

  if (DOM.downloadPdfBtn) {
    DOM.downloadPdfBtn.addEventListener("click", downloadPdfReport);
  }

  if (DOM.billForm) {
    DOM.billForm.addEventListener("submit", handleBillCalibration);
  }
}

function refreshIcons() {
  if (window.lucide) {
    lucide.createIcons();
  }
}

function formatCurrency(amount) {
  return `PHP ${CURRENCY_FORMATTER.format(amount || 0)}`;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function stripHtml(html) {
  const temp = document.createElement("div");
  temp.innerHTML = html;
  return (temp.textContent || temp.innerText || "").trim();
}

function summarizeReportText(html, maxLength = 200) {
  const text = stripHtml(html).replace(/\s+/g, " ");
  if (text.length <= maxLength) {
    return text;
  }
  return `${text.slice(0, maxLength).trim()}...`;
}

function extractChartData(rawHtml) {
  if (!rawHtml) {
    return null;
  }

  const temp = document.createElement("div");
  temp.innerHTML = rawHtml;
  const dataNode = temp.querySelector("#chart-data-json");

  if (!dataNode) {
    return null;
  }

  try {
    return JSON.parse(dataNode.textContent);
  } catch (error) {
    console.error("Chart data parsing failed", error);
    return null;
  }
}

function sanitizeGeminiHtml(rawHtml) {
  if (!rawHtml) {
    return "";
  }

  const temp = document.createElement("div");
  temp.innerHTML = rawHtml;
  temp.querySelectorAll("script, style, #chart-data-json, canvas").forEach((node) => node.remove());
  return temp.innerHTML.trim();
}

function buildFallbackRecommendations(analysis) {
  if (!analysis.length) {
    return [
      {
        title: "Complete your appliance list",
        description: "Add the major appliances in your home so the audit can highlight the real monthly cost drivers.",
      },
      {
        title: "Calibrate with a recent utility bill",
        description: "Applying an actual bill sharpens the estimated monthly cost and makes the audit more reliable.",
      },
    ];
  }

  const sortedByCost = [...analysis].sort((a, b) => b.monthlyCost - a.monthlyCost);
  const topCost = sortedByCost[0];
  const recommendations = [];

  if (topCost) {
    recommendations.push({
      title: `Trim hours on ${topCost.name || "your highest-cost appliance"}`,
      description: "This appliance is currently your biggest monthly cost driver, so even small usage changes here should have the strongest impact.",
    });
  }

  recommendations.push({
    title: "Prioritize upgrades by monthly kWh",
    description: "Focus replacement or inverter-upgrade decisions on the appliances with the highest estimated monthly consumption first.",
  });

  recommendations.push({
    title: "Use calibration regularly",
    description: "Revisit the bill calibration step when your tariff changes so Gemini recommendations stay grounded in your real household rate.",
  });

  return recommendations;
}

function buildApplianceRows(analysis) {
  if (!analysis.length) {
    return `<tr><td colspan="5" class="muted">No appliances available.</td></tr>`;
  }

  return analysis
    .map(
      (item) => `
        <tr>
          <td>${escapeHtml(item.name || "Unnamed Appliance")}</td>
          <td>${Number(item.watts || 0).toFixed(0)} W</td>
          <td>${Number(item.hoursUsed || 0).toFixed(1)} hrs/day</td>
          <td>${Number(item.usageBehaviorPercent || 0).toFixed(0)}%</td>
          <td>${Number(item.monthlyKwh || 0).toFixed(2)} kWh</td>
        </tr>
      `
    )
    .join("");
}

function buildCostRows(analysis) {
  if (!analysis.length) {
    return `<tr><td colspan="3" class="muted">Generate an audit to see a cost breakdown.</td></tr>`;
  }

  return [...analysis]
    .sort((a, b) => b.monthlyCost - a.monthlyCost)
    .map(
      (item) => `
        <tr>
          <td>${escapeHtml(item.name || "Unnamed Appliance")}</td>
          <td>${Number(item.monthlyKwh || 0).toFixed(2)} kWh</td>
          <td>${formatCurrency(item.monthlyCost || 0)}</td>
        </tr>
      `
    )
    .join("");
}

function buildEnhancedReportMarkup(rawHtml, analysis) {
  const totalCost = analysis.reduce((sum, item) => sum + item.monthlyCost, 0);
  const totalKwh = analysis.reduce((sum, item) => sum + item.monthlyKwh, 0);
  const recommendations = buildFallbackRecommendations(analysis);
  const sanitizedHtml = sanitizeGeminiHtml(rawHtml);
  const summaryText =
    summarizeReportText(sanitizedHtml, 220) ||
    "Gemini generated a tailored energy review based on your appliance profile and calibrated usage data.";

  return `
    <div class="report-shell">
      <section class="report-header">
        <div class="relative z-10">
          <p class="text-accent uppercase tracking-[0.22em] text-xs font-bold mb-3">KiloWhatt Expert Audit</p>
          <h3 class="text-2xl font-bold mb-3">Presentation-ready electricity usage analysis</h3>
          <p class="text-slate-300 max-w-3xl">${escapeHtml(summaryText)}</p>
        </div>
      </section>

      <section class="report-grid">
        <div class="report-kpi">
          <div class="report-kpi-label">Total Appliances</div>
          <div class="report-kpi-value">${analysis.length}</div>
        </div>
        <div class="report-kpi">
          <div class="report-kpi-label">Estimated Monthly Consumption</div>
          <div class="report-kpi-value">${totalKwh.toFixed(2)} kWh</div>
        </div>
        <div class="report-kpi">
          <div class="report-kpi-label">Estimated Monthly Cost</div>
          <div class="report-kpi-value">${formatCurrency(totalCost)}</div>
        </div>
      </section>

      <section class="report-section">
        <h3>Appliance Summary</h3>
        <div class="preview-table" style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Appliance</th>
                <th>Power</th>
                <th>Usage</th>
                <th>Behavior</th>
                <th>Monthly kWh</th>
              </tr>
            </thead>
            <tbody>${buildApplianceRows(analysis)}</tbody>
          </table>
        </div>
      </section>

      <section class="report-section">
        <h3>Cost Breakdown</h3>
        <div class="preview-table" style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Appliance</th>
                <th>Monthly kWh</th>
                <th>Monthly Cost</th>
              </tr>
            </thead>
            <tbody>${buildCostRows(analysis)}</tbody>
          </table>
        </div>
      </section>

      <section class="report-section" id="reportChartSection" style="display:none;">
        <h3>Projected Savings Visual</h3>
        <div style="position:relative;min-height:320px;">
          <canvas id="reportSavingsChart"></canvas>
        </div>
      </section>

      <section class="report-section">
        <h3>Gemini Analysis</h3>
        <div class="report-html">${sanitizedHtml || "<p>No Gemini analysis content was returned.</p>"}</div>
      </section>

      <section class="report-section">
        <h3>Recommendations</h3>
        <div class="report-recommendations">
          ${recommendations
            .map(
              (item) => `
                <div class="report-recommendation">
                  <strong>${escapeHtml(item.title)}</strong>
                  <span>${escapeHtml(item.description)}</span>
                </div>
              `
            )
            .join("")}
        </div>
      </section>
    </div>
  `;
}

function renderReportChart(chartData) {
  const chartSection = document.getElementById("reportChartSection");
  const canvas = document.getElementById("reportSavingsChart");

  if (!chartSection || !canvas || typeof window.Chart === "undefined" || !chartData) {
    if (chartSection) {
      chartSection.style.display = "none";
    }
    return;
  }

  chartSection.style.display = "block";

  if (canvas._chartInstance) {
    canvas._chartInstance.destroy();
  }

  canvas._chartInstance = new Chart(canvas, {
    type: "bar",
    data: {
      labels: chartData.labels || [],
      datasets: [
        {
          label: "Current (PHP)",
          data: chartData.current || [],
          backgroundColor: "#0f172a",
          borderRadius: 10,
        },
        {
          label: "Potential (PHP)",
          data: chartData.potential || [],
          backgroundColor: "#f6c21f",
          borderRadius: 10,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            boxWidth: 12,
            usePointStyle: true,
          },
        },
      },
      scales: {
        y: { beginAtZero: true },
      },
    },
  });
}

function renderGeminiReport(rawHtml, analysis) {
  state.latestRawReportHtml = rawHtml;
  state.latestAnalysis = analysis;
  state.latestGeneratedAt = new Date().toISOString();
  state.latestChartData = extractChartData(rawHtml);

  DOM.geminiContent.innerHTML = buildEnhancedReportMarkup(rawHtml, analysis);
  renderReportChart(state.latestChartData);
  refreshIcons();
}

async function api(endpoint, options = {}) {
  const defaultOptions = {
    credentials: "include",
    headers: { "Content-Type": "application/json" },
  };

  try {
    const res = await fetch(`api/${endpoint}`, { ...defaultOptions, ...options });
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.error || data.details || "Server Error");
    }
    return data;
  } catch (err) {
    console.error(`API Error [${endpoint}]:`, err);
    throw err;
  }
}

async function fetchPresets() {
  try {
    state.presets = await api("get_presets.php", { method: "GET" });
  } catch (err) {
    console.error("Presets failed:", err);
  }
}

async function fetchUserAppliances() {
  try {
    const saved = await api("user_appliances.php", { method: "GET" });
    DOM.applianceList.innerHTML = "";

    if (saved && Array.isArray(saved) && saved.length > 0) {
      saved.forEach((app) =>
        createApplianceEntry({
          dbId: app.id,
          name: app.custom_name,
          watts: app.watts,
          hoursUsed: app.hours_per_day,
          usageBehaviorPercent: app.usage_behavior_percent,
        })
      );
    } else {
      createApplianceEntry();
    }

    renderAnalysisPreview();
  } catch (err) {
    console.error("Persistence failed:", err);
  }
}

async function saveAppliance(appData) {
  return api("user_appliances.php", { method: "POST", body: JSON.stringify(appData) });
}

async function deleteAppliance(id) {
  await api("user_appliances.php", { method: "DELETE", body: JSON.stringify({ id }) });
}

function uid(prefix = "id") {
  state.idCounter += 1;
  return `${prefix}-${Date.now().toString(36)}-${state.idCounter}`;
}

async function handleResetAppliances() {
  const result = await Swal.fire({ title: "Clear everything?", icon: "warning", showCancelButton: true });
  if (result.isConfirmed) {
    DOM.applianceList.innerHTML = "";
    createApplianceEntry();
    renderAnalysisPreview();
  }
}

function handleAddAppliance() {
  createApplianceEntry();
  renderAnalysisPreview();
}

function toggleAdvancedOptions() {
  const isOpen = DOM.globalAdvancedToggle.getAttribute("aria-expanded") === "true";
  setAdvancedOptionsOpen(!isOpen);
}

function setAdvancedOptionsOpen(isOpen) {
  if (!DOM.globalAdvancedToggle || !DOM.globalAdvancedSection) {
    return;
  }

  DOM.globalAdvancedToggle.setAttribute("aria-expanded", String(isOpen));
  DOM.globalAdvancedToggle.classList.toggle("is-open", isOpen);
  DOM.globalAdvancedSection.classList.toggle("open", isOpen);
  DOM.globalAdvancedSection.setAttribute("aria-hidden", String(!isOpen));
}

function createApplianceEntry(data = {}) {
  const entryId = data.dbId || uid("appliance");
  const entry = document.createElement("div");
  entry.className = "appliance-item animate-fade";
  entry.id = entryId;

  if (data.dbId) {
    entry.dataset.dbId = data.dbId;
  }

  entry.innerHTML = `
    <div class="appliance-card-header">
      <div>
        <p class="appliance-card-title">${escapeHtml(data.name || "Appliance")}</p>
        <p class="appliance-card-meta">Usage inputs for monthly estimate</p>
      </div>
      <button class="btn btn-danger btn-remove appliance-remove-btn" type="button" title="Remove appliance" aria-label="Remove appliance">
        <i data-lucide="trash-2" size="16"></i>
      </button>
    </div>

    <div class="appliance-grid">
      <div class="form-group appliance-name-group">
        <label>Appliance Name</label>
        <div class="autocomplete-wrapper">
          <input type="text" class="appliance-name" value="${escapeHtml(data.name || "")}" placeholder="e.g. Inverter Aircon" autocomplete="off">
          <div class="autocomplete-list glass-panel"></div>
        </div>
      </div>

      <div class="form-group">
        <label>Watts</label>
        <input type="number" class="appliance-watts input-compact" value="${Number(data.watts || 0)}" min="0">
      </div>

      <div class="form-group">
        <label>Daily Hours</label>
        <input type="number" class="appliance-hours input-compact" value="${Number(data.hoursUsed || 0)}" min="0" max="24">
      </div>

      <div class="form-group">
        <label>Usage Behavior (%)</label>
        <div class="behavior-row">
          <input type="range" class="appliance-behavior" min="10" max="100" step="5" value="${Number(data.usageBehaviorPercent || 100)}">
          <span class="behavior-label">${Number(data.usageBehaviorPercent || 100)}%</span>
        </div>
      </div>
    </div>
  `;

  DOM.applianceList.appendChild(entry);
  refreshIcons();

  const entryTitle = entry.querySelector(".appliance-card-title");
  const nameInput = entry.querySelector(".appliance-name");
  const autoBox = entry.querySelector(".autocomplete-list");
  const wattsInput = entry.querySelector(".appliance-watts");
  const hoursInput = entry.querySelector(".appliance-hours");
  const behaviorInput = entry.querySelector(".appliance-behavior");
  const behaviorLabel = entry.querySelector(".behavior-label");
  const removeBtn = entry.querySelector(".btn-remove");

  nameInput.addEventListener("input", () => {
    const text = nameInput.value.toLowerCase().trim();
    entryTitle.textContent = nameInput.value.trim() || "Appliance";
    autoBox.innerHTML = "";

    if (!text) {
      autoBox.style.display = "none";
      renderAnalysisPreview();
      return;
    }

    const matches = state.presets.filter((preset) => preset.name.toLowerCase().includes(text)).slice(0, 8);
    if (!matches.length) {
      autoBox.style.display = "none";
      renderAnalysisPreview();
      return;
    }

    autoBox.style.display = "block";
    matches.forEach((match) => {
      const item = document.createElement("div");
      item.className = "auto-item";
      item.textContent = match.name;
      item.addEventListener("click", async () => {
        nameInput.value = match.name;
        wattsInput.value = match.default_watts;
        behaviorInput.value = match.default_usage_behavior;
        behaviorLabel.textContent = `${match.default_usage_behavior}%`;
        entryTitle.textContent = match.name;
        autoBox.style.display = "none";
        renderAnalysisPreview();

        const res = await saveAppliance({
          name: match.name,
          watts: match.default_watts,
          hoursUsed: Number(hoursInput.value || 1),
          usageBehaviorPercent: match.default_usage_behavior,
        });

        entry.dataset.dbId = res.id;
        Swal.fire({ icon: "info", title: "Preset applied", toast: true, position: "top-end", showConfirmButton: false, timer: 1000 });
      });
      autoBox.appendChild(item);
    });

    renderAnalysisPreview();
  });

  behaviorInput.addEventListener("input", () => {
    behaviorLabel.textContent = `${behaviorInput.value}%`;
    renderAnalysisPreview();
  });

  [nameInput, wattsInput, hoursInput].forEach((element) => {
    element.addEventListener("input", renderAnalysisPreview);
  });

  removeBtn.addEventListener("click", async () => {
    if (entry.dataset.dbId) {
      await deleteAppliance(entry.dataset.dbId);
    }
    entry.remove();
    renderAnalysisPreview();
  });
}

function gatherInputData() {
  const inputs = [];
  const entries = DOM.applianceList.querySelectorAll(".appliance-item");
  const rate = DOM.globalRateInput ? parseFloat(DOM.globalRateInput.value) : 13.47;

  entries.forEach((entry) => {
    inputs.push({
      name: entry.querySelector(".appliance-name").value,
      watts: parseFloat(entry.querySelector(".appliance-watts").value) || 0,
      hoursUsed: parseFloat(entry.querySelector(".appliance-hours").value) || 0,
      usageBehaviorPercent: parseFloat(entry.querySelector(".appliance-behavior").value) || 100,
      ratePhpPerKwh: rate,
    });
  });

  return inputs.filter((item) => item.name || item.watts || item.hoursUsed);
}

function calculateAnalysis(inputs) {
  return inputs.map((item) => {
    const behaviorFactor = item.usageBehaviorPercent / 100;
    const monthlyKwh = (item.watts * behaviorFactor * item.hoursUsed * 30) / 1000;
    const monthlyCost = monthlyKwh * item.ratePhpPerKwh;
    return { ...item, monthlyKwh, monthlyCost };
  });
}

function updateDashboardStats(inputs, analysis) {
  const totalCost = analysis.reduce((total, item) => total + item.monthlyCost, 0);
  const totalKwh = analysis.reduce((total, item) => total + item.monthlyKwh, 0);
  const elCost = document.getElementById("stat-total-cost");
  const elKwh = document.getElementById("stat-total-kwh");
  const elApps = document.getElementById("stat-active-appliances");

  if (elCost) {
    elCost.textContent = formatCurrency(totalCost);
  }
  if (elKwh) {
    elKwh.textContent = `${totalKwh.toFixed(2)} kWh`;
  }
  if (elApps) {
    elApps.textContent = inputs.length;
  }
}

function renderAnalysisPreview() {
  const inputs = gatherInputData();
  const analysis = calculateAnalysis(inputs);
  state.latestAnalysis = analysis;
  updateDashboardStats(inputs, analysis);

  if (!analysis.length) {
    DOM.analysisArea.innerHTML = "<p class='muted'>Add your first appliance to see an estimate.</p>";
    return;
  }

  DOM.analysisArea.innerHTML = `
    <table class="animate-fade">
      <thead>
        <tr>
          <th>Device</th>
          <th>kWh / month</th>
          <th>Estimated cost</th>
        </tr>
      </thead>
      <tbody>
        ${analysis
          .map(
            (item) => `
              <tr>
                <td>${escapeHtml(item.name || "Unnamed")}</td>
                <td>${item.monthlyKwh.toFixed(2)}</td>
                <td style="font-weight:700;">${formatCurrency(item.monthlyCost)}</td>
              </tr>
            `
          )
          .join("")}
      </tbody>
    </table>
  `;
}

async function generateAuditReport() {
  const inputs = gatherInputData();
  if (!inputs.length) {
    Swal.fire({ icon: "warning", title: "Empty list" });
    return;
  }

  Swal.fire({
    title: "Consulting Lektric...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  try {
    const analysis = calculateAnalysis(inputs);
    const data = await api("generate.php", { method: "POST", body: JSON.stringify({ analysis }) });
    Swal.close();
    renderGeminiReport(data.output, analysis);

    if (typeof window.showSection === "function") {
      window.showSection("recommendations", document.querySelector('[onclick*="recommendations"]'));
    }

    Swal.fire({ icon: "success", title: "Audit ready", toast: true, position: "top-end", showConfirmButton: false, timer: 2400 });
  } catch (err) {
    Swal.fire("AI Failure", err.message, "error");
  }
}

async function downloadPdfReport() {
  if (!state.latestAnalysis.length || !state.latestRawReportHtml) {
    Swal.fire("No report", "Generate an audit first.", "info");
    return;
  }

  Swal.fire({
    title: "Preparing PDF...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  try {
    const response = await fetch("api/export_report_pdf.php", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        reportHtml: state.latestRawReportHtml,
        analysis: state.latestAnalysis,
        generatedAt: state.latestGeneratedAt,
      }),
    });

    if (!response.ok) {
      let errorMessage = "Failed to generate PDF.";
      try {
        const errorData = await response.json();
        errorMessage = errorData.error || errorMessage;
      } catch (error) {
        console.error(error);
      }
      throw new Error(errorMessage);
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "KiloWhatt-Energy-Audit.pdf";
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    Swal.close();
  } catch (err) {
    console.error(err);
    Swal.fire("PDF Error", err.message || "Failed to generate PDF.", "error");
  }
}

async function handleBillCalibration(event) {
  event.preventDefault();
  const kwh = parseFloat(document.getElementById("bill-kwh").value);
  const amount = parseFloat(document.getElementById("bill-amount").value);

  if (kwh > 0 && amount > 0) {
    const rate = amount / kwh;
    document.getElementById("computed-rate-display").textContent = `PHP ${rate.toFixed(4)} / kWh`;
    document.getElementById("calibration-result").style.display = "block";
    DOM.globalRateInput.value = rate.toFixed(4);
    Swal.fire({ icon: "success", title: "Calibrated", text: `Rate: PHP ${rate.toFixed(4)}/kWh applied.` });
    renderAnalysisPreview();
    api("save_bill.php", { method: "POST", body: JSON.stringify({ total_kwh: kwh, total_amount: amount }) });
  }
}

init();
