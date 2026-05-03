// index.js

// Front-end UI logic for KiloWhatt (Modern Full-Stack Version)

const DOM = {
  applianceList: document.getElementById("appliance-list"),
  addRowBtn: document.getElementById("addRow"),
  resetRowsBtn: document.getElementById("resetRows"),
  generateBtn: document.getElementById("generateButton"),
  analysisArea: document.getElementById("analysisArea"),
  geminiContent: document.getElementById("geminiContent"),
  globalRateInput: document.getElementById("global-rate"),
  billForm: document.getElementById('bill-form'),
  sidebar: document.getElementById('sidebar'),
  downloadPdfBtn: document.getElementById('downloadGeminiOutput')
};

let state = {
  presets: [],
  idCounter: 0
};

// --- Initialization ---
async function init() {
  await fetchPresets();
  await fetchUserAppliances();
  setupEventListeners();
}

function setupEventListeners() {
    DOM.addRowBtn.addEventListener("click", () => createApplianceEntry());
    DOM.resetRowsBtn.addEventListener("click", async () => {
        const result = await Swal.fire({ title: 'Clear everything?', icon: 'warning', showCancelButton: true });
        if (result.isConfirmed) { DOM.applianceList.innerHTML = ""; createApplianceEntry(); renderAnalysisPreview(); }
    });
}


// --- API Services ---
async function api(endpoint, options = {}) {
  const defaultOptions = {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' }
  };
  try {
    const res = await fetch(`api/${endpoint}`, { ...defaultOptions, ...options });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || data.details || 'Server Error');
    return data;
  } catch (err) {
    console.error(`API Error [${endpoint}]:`, err);
    throw err;
  }
}

async function fetchPresets() {
  try {
    state.presets = await api('get_presets.php', { method: 'GET' });
  } catch (err) { console.error("Presets failed:", err); }
}

async function fetchUserAppliances() {
    try {
        const saved = await api('user_appliances.php', { method: 'GET' });
        DOM.applianceList.innerHTML = "";
        if (saved && Array.isArray(saved)) {
            saved.forEach(app => createApplianceEntry({
                dbId: app.id,
                name: app.custom_name,
                watts: app.watts,
                hoursUsed: app.hours_per_day,
                usageBehaviorPercent: app.usage_behavior_percent
            }));
        }
        renderAnalysisPreview();
    } catch (err) { console.error("Persistence failed:", err); }
}

async function saveAppliance(appData) {
    return await api('user_appliances.php', { method: 'POST', body: JSON.stringify(appData) });
}

async function deleteAppliance(id) {
    await api('user_appliances.php', { method: 'DELETE', body: JSON.stringify({ id: id }) });
}

function uid(prefix = "id") {
  state.idCounter += 1;
  return `${prefix}-${Date.now().toString(36)}-${state.idCounter}`;
}

function createApplianceEntry(data = {}) {
  const entryId = data.dbId || uid("appliance");
  const entry = document.createElement("div");
  entry.className = "appliance-item animate-fade";
  entry.id = entryId;
  if (data.dbId) entry.dataset.dbId = data.dbId;

  entry.innerHTML = `
    <div class="form-group" style="margin-bottom: 0;">
      <label>Appliance Name</label>
      <div style="position: relative;">
        <input type="text" class="appliance-name" value="${data.name || ""}" placeholder="e.g. Inverter Aircon" autocomplete="off">
        <div class="autocomplete-list glass-panel" style="position: absolute; width: 100%; z-index: 100; display: none; margin-top: 4px; background: white; border: 1px solid var(--border); max-height: 200px; overflow-y: auto;"></div>
      </div>
    </div>

    <div class="form-group" style="margin-bottom: 0;">
      <label>Watts</label>
      <input type="number" class="appliance-watts" value="${data.watts || 0}" min="0">
    </div>

    <div class="form-group" style="margin-bottom: 0;">
      <label>Daily Hours</label>
      <input type="number" class="appliance-hours" value="${data.hoursUsed || 0}" min="0" max="24">
    </div>

    <div class="form-group" style="margin-bottom: 0;">
      <label>Usage Behavior (%)</label>
      <div style="display: flex; align-items: center; gap: 12px;">
        <input type="range" class="appliance-behavior" min="10" max="100" step="5" value="${data.usageBehaviorPercent || 100}" style="flex: 1;">
        <span class="behavior-label" style="font-weight: 600; width: 42px; font-size: 0.85rem;">${data.usageBehaviorPercent || 100}%</span>
      </div>
    </div>

    <button class="btn btn-danger btn-remove" style="padding: 8px; width: 38px; height: 38px; min-height: 38px; flex-shrink: 0;" title="Remove">
        <i data-lucide="trash-2" size="16"></i>
    </button>
  `;

  DOM.applianceList.appendChild(entry);
  lucide.createIcons();

  const nameInput = entry.querySelector(".appliance-name");
  const autoBox = entry.querySelector(".autocomplete-list");
  const wattsInput = entry.querySelector(".appliance-watts");
  const hoursInput = entry.querySelector(".appliance-hours");
  const behaviorInput = entry.querySelector(".appliance-behavior");
  const behaviorLabel = entry.querySelector(".behavior-label");
  const removeBtn = entry.querySelector(".btn-remove");

  nameInput.addEventListener("input", () => {
    const text = nameInput.value.toLowerCase();
    autoBox.innerHTML = "";
    if (!text) { autoBox.style.display = 'none'; return; }
    const matches = state.presets.filter(p => p.name.toLowerCase().includes(text));
    if (matches.length === 0) { autoBox.style.display = 'none'; return; }
    autoBox.style.display = 'block';
    matches.forEach(match => {
      const item = document.createElement("div");
      item.className = "auto-item";
      item.style.padding = '10px 16px'; item.style.cursor = 'pointer'; item.textContent = match.name;
      item.onclick = async () => {
        nameInput.value = match.name; wattsInput.value = match.default_watts; behaviorInput.value = match.default_usage_behavior; behaviorLabel.textContent = `${match.default_usage_behavior}%`; autoBox.style.display = 'none';
        renderAnalysisPreview();
        const res = await saveAppliance({ name: match.name, watts: match.default_watts, hoursUsed: 1, usageBehaviorPercent: match.default_usage_behavior });
        entry.dataset.dbId = res.id;
        Swal.fire({ icon: 'info', title: 'Preset Applied', toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
      };
      autoBox.appendChild(item);
    });
  });

  behaviorInput.addEventListener("input", () => { behaviorLabel.textContent = `${behaviorInput.value}%`; renderAnalysisPreview(); });
  [nameInput, wattsInput, hoursInput].forEach(el => el.addEventListener("input", renderAnalysisPreview));
  removeBtn.onclick = async () => {
    if (entry.dataset.dbId) await deleteAppliance(entry.dataset.dbId);
    entry.remove(); renderAnalysisPreview();
  };
  document.addEventListener("click", (e) => { if (!entry.contains(e.target)) autoBox.style.display = 'none'; });
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
      ratePhpPerKwh: rate
    });
  });
  return inputs;
}

function calculateAnalysis(inputs) {
  return inputs.map((d) => {
    const behaviorFactor = d.usageBehaviorPercent / 100;
    const monthlyKwh = (d.watts * behaviorFactor * d.hoursUsed * 30) / 1000;
    const monthlyCost = monthlyKwh * d.ratePhpPerKwh;
    return { ...d, monthlyKwh, monthlyCost };
  });
}

function renderAnalysisPreview() {
  const inputs = gatherInputData();
  const analysis = calculateAnalysis(inputs);
  updateDashboardStats(inputs, analysis);

  if (analysis.length === 0) {
    DOM.analysisArea.innerHTML = "<p class='muted'>Add your first appliance to see an estimate.</p>";
    return;
  }

  let html = `<table class="animate-fade"><thead><tr><th>Device</th><th>kWh/mo</th><th>Cost</th></tr></thead><tbody>`;
  analysis.forEach((item) => {
    html += `<tr><td>${item.name || 'Unnamed'}</td><td>${item.monthlyKwh.toFixed(2)}</td><td style="font-weight:700;">₱${item.monthlyCost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td></tr>`;
  });
  html += `</tbody></table>`;
  DOM.analysisArea.innerHTML = html;
}

function updateDashboardStats(inputs, analysis) {
  const totalCost = analysis.reduce((t, i) => t + i.monthlyCost, 0);
  const totalKwh = analysis.reduce((t, i) => t + i.monthlyKwh, 0);
  const elCost = document.getElementById('stat-total-cost');
  const elKwh = document.getElementById('stat-total-kwh');
  const elApps = document.getElementById('stat-active-appliances');
  
  if (elCost) elCost.textContent = `₱${totalCost.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
  if (elKwh) elKwh.textContent = `${totalKwh.toFixed(2)} kWh`;
  if (elApps) elApps.textContent = inputs.length;
}

DOM.generateBtn.addEventListener("click", async () => {
    const inputs = gatherInputData();
    if (inputs.length === 0) { Swal.fire({ icon: 'warning', title: 'Empty List' }); return; }
    Swal.fire({ title: 'Consulting Lektric...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    try {
      const data = await api('generate.php', { method: 'POST', body: JSON.stringify({ analysis: calculateAnalysis(inputs) }) });
      Swal.close();
      DOM.geminiContent.innerHTML = data.output;
      initChartsFromOutput();
      showSection('recommendations', document.querySelector('[onclick*="recommendations"]'));
      Swal.fire({ icon: 'success', title: 'Audit Ready', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
    } catch (err) { Swal.fire('AI Failure', err.message, 'error'); }
});

function initChartsFromOutput() {
    const chartDataEl = document.getElementById('chart-data-json');
    if (!chartDataEl) return;
    try {
        const data = JSON.parse(chartDataEl.textContent);
        const parent = chartDataEl.parentNode;
        const existing = parent.querySelector('canvas');
        if (existing) existing.remove();
        const ctx = document.createElement('canvas');
        ctx.style.marginTop = '32px';
        parent.appendChild(ctx);
        new Chart(ctx, {
            type: 'bar',
            data: { labels: data.labels, datasets: [{ label: 'Current (PHP)', data: data.current, backgroundColor: '#1e293b', borderRadius: 8 }, { label: 'Potential (PHP)', data: data.potential, backgroundColor: '#facc15', borderRadius: 8 }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    } catch (e) { console.error("Chart error", e); }
}

if (DOM.downloadPdfBtn) {
    DOM.downloadPdfBtn.addEventListener('click', async () => {
        const reportArea = document.getElementById('geminiContent');
        const reportContent = reportArea ? reportArea.innerHTML.trim() : '';
        
        if (!reportContent || reportContent.includes('ready to help')) {
            Swal.fire('No Report', 'Generate an audit first.', 'info');
            return;
        }

        Swal.fire({ title: 'Preparing PDF...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        try {
            await new Promise(resolve => setTimeout(resolve, 800));
            
            const canvas = await html2canvas(reportArea, { 
                scale: 1.5, 
                useCORS: true, 
                backgroundColor: '#ffffff' 
            });
            
            const imgData = canvas.toDataURL('image/jpeg', 0.8);
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = 210;
            const pdfHeight = 297;
            
            const imgProps = { width: canvas.width, height: canvas.height };
            const ratio = imgProps.height / imgProps.width;
            const docWidth = pdfWidth - 20; 
            const docHeight = docWidth * ratio;
            
            let heightLeft = docHeight;
            let position = 10;
            
            pdf.addImage(imgData, 'JPEG', 10, position, docWidth, docHeight);
            heightLeft -= pdfHeight;
            
            while (heightLeft > 0) {
                position = heightLeft - docHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'JPEG', 10, position, docWidth, docHeight);
                heightLeft -= pdfHeight;
            }
            
            pdf.save('KiloWhatt-Energy-Audit.pdf');
            Swal.close();
        } catch (err) {
            console.error(err);
            Swal.fire('PDF Error', 'Failed to generate PDF.', 'error');
        }
    });
}

if (DOM.billForm) {
  DOM.billForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const kwh = parseFloat(document.getElementById('bill-kwh').value);
    const amount = parseFloat(document.getElementById('bill-amount').value);
    if (kwh > 0 && amount > 0) {
      const rate = amount / kwh;
      document.getElementById('computed-rate-display').textContent = `₱${rate.toFixed(4)}`;
      document.getElementById('calibration-result').style.display = 'block';
      DOM.globalRateInput.value = rate.toFixed(4);
      Swal.fire({ icon: 'success', title: 'Calibrated', text: `Rate: ₱${rate.toFixed(4)}/kWh applied.` });
      renderAnalysisPreview();
      api('save_bill.php', { method: 'POST', body: JSON.stringify({ total_kwh: kwh, total_amount: amount }) });
    }
  });
}

// Events & Start
DOM.addRowBtn.addEventListener("click", () => createApplianceEntry());
DOM.resetRowsBtn.addEventListener("click", async () => {
    const result = await Swal.fire({ title: 'Clear everything?', icon: 'warning', showCancelButton: true });
    if (result.isConfirmed) { DOM.applianceList.innerHTML = ""; createApplianceEntry(); renderAnalysisPreview(); }
});

init();
