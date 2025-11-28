// index.js

// Front-end UI logic (module)
// - Uses flex-based layout (CSS).
// - The prompt is NOT generated or editable in the browser.
// -C licking "Generate" sends structured { inputs: [...] } to POST /api/generate.
// - Server (gemini.js) composes the internal prompt and calls Gemini; client only displays returned text.

import { sendToGemini } from "./gemini.js";

const applianceList = document.getElementById("appliance-list");
const addRowBtn = document.getElementById("addRow");
const resetRowsBtn = document.getElementById("resetRows");
const generateBtn = document.getElementById("generateButton");
// Optionally allow a locally-stored key (localStorage) so users can persist their test key
const LOCAL_KEY_NAME = "GEMINI_KEY";
const storedKey = localStorage.getItem(LOCAL_KEY_NAME);
// Do not auto-write stored key into the DOM value to avoid accidental commits; leave it optional.

const validationMessage = document.getElementById("validationMessage");
const analysisArea = document.getElementById("analysisArea");
const geminiContent = document.getElementById("geminiContent");
const globalAdvancedToggle = document.getElementById("globalAdvancedToggle");
const globalAdvancedSection = document.getElementById("globalAdvancedSection");
const globalRateInput = document.getElementById("global-rate");
const globalVoltageSelect = document.getElementById("global-voltage");

const voltageRegions = [
  {
    label: "NCR (Metro Manila) - Meralco (230V)",
    value: "NCR (Metro Manila)",
    rate: 13.4702,
  },
  {
    label: "Region 3 (Central Luzon) - Meralco (230V)",
    value: "Region 3 (Central Luzon)",
    rate: 13.4702,
  },
  {
    label: "Cebu Province (Metro Cebu) - VECO (230V)",
    value: "Cebu Province (Metro Cebu)",
    rate: 11.51,
  },
  {
    label: "Iloilo City (Region 6) - MORE Power (230V)",
    value: "Iloilo City (Region 6)",
    rate: 12.6195,
  },
  {
    label: "Southern Cebu - CEBECO I (230V)",
    value: "Southern Cebu",
    rate: 13.3,
  },
];

const appliances = [
  {
    applianceName: ["Air Conditioner", "Aircon"],
    applianceType: ["Normal", "Inverter"],
    power: 1200,
    usageProfile: { default: 85, Normal: 95, Inverter: 70 },
    defaultRate: 13.5,
    defaultVoltageRegion: "NCR (Metro Manila)",
  },
  {
    applianceName: ["Refrigerator"],
    applianceType: [],
    power: 180,
    usageProfile: { default: 45 },
    defaultRate: 13.5,
    defaultVoltageRegion: "NCR (Metro Manila)",
  },
  {
    applianceName: ["Desktop PC"],
    applianceType: ["Gaming", "Office"],
    power: 450,
    usageProfile: { default: 65, Gaming: 90, Office: 55 },
    defaultRate: 13.5,
    defaultVoltageRegion: "NCR (Metro Manila)",
  },
  {
    applianceName: ["TV"],
    applianceType: ["LED", "OLED"],
    power: 160,
    usageProfile: { default: 55, LED: 50, OLED: 65 },
    defaultRate: 13.5,
    defaultVoltageRegion: "NCR (Metro Manila)",
  },
];

function syncRateWithRegion() {
  if (!globalVoltageSelect || !globalRateInput) return;
  const selectedValue = globalVoltageSelect.value;
  const region = voltageRegions.find((r) => r.value === selectedValue);
  if (region) {
    globalRateInput.value = region.rate;
  }
}

function populateVoltageOptions() {
  if (!globalVoltageSelect) return;
  globalVoltageSelect.innerHTML = "";
  voltageRegions.forEach((region) => {
    const option = document.createElement("option");
    option.value = region.value;
    option.textContent = region.label;
    globalVoltageSelect.appendChild(option);
  });
  if (voltageRegions.length) {
    globalVoltageSelect.value = voltageRegions[0].value;
  }
  syncRateWithRegion();
}

populateVoltageOptions();

let idCounter = 0;
function uid(prefix = "id") {
  idCounter += 1;
  return `${prefix}-${Date.now().toString(36)}-${idCounter}`;
}

/* ---------------------------------------------------------
   CREATE APPLIANCE ENTRY (FULLY FIXED)
--------------------------------------------------------- */
function createApplianceEntry(data = {}) {
  const entryId = uid("appliance");
  const entry = document.createElement("div");
  entry.className = "appliance-entry";
  entry.id = entryId;

  entry.innerHTML = `
    <div class="row full-width autocomplete-container">
      <label for="${entryId}-name">Appliance Name</label>
      <input type="text" id="${entryId}-name" class="appliance-name"
        value="${
          data.name || ""
        }" placeholder="e.g., Aircon" autocomplete="off">
      <div class="autocomplete-list"></div>
    </div>

    <div class="row-group">
      <div class="row">
        <label for="${entryId}-type">Appliance Type</label>
        <div class="type-wrapper">
          <input type="text" id="${entryId}-type" class="appliance-type" value="${
    data.type || ""
  }" placeholder="e.g., Inverter">
          <button class="type-dropdown-btn" type="button">▼</button>
          <div class="type-dropdown-menu"></div>
        </div>
      </div>

      <div class="row">
        <label for="${entryId}-watts">Power (Watts)</label>
        <input type="number" id="${entryId}-watts" class="appliance-watts" value="${
    data.watts || 0
  }" min="0">
      </div>

      <div class="row">
        <label for="${entryId}-hours">Hours Used Daily</label>
        <input type="number" id="${entryId}-hours" class="appliance-hours" value="${
    data.hoursUsed || 0
  }" min="0" max="24">
      </div>

      <div class="row slider-row">
        <label for="${entryId}-behavior">Estimated Usage Behavior (%)</label>
        <div class="slider-wrapper">
          <input type="range" id="${entryId}-behavior" class="appliance-behavior" min="10" max="100" step="5" value="${
    data.usageBehaviorPercent || 100
  }">
          <span class="behavior-value">${
            data.usageBehaviorPercent || 100
          }%</span>
        </div>
      </div>
    </div>


    <div class="actions">
      <button class="btn btn-danger remove-row" data-remove-id="${entryId}">Remove</button>
    </div>
  `;

  applianceList.appendChild(entry);

  /* ---------------------------------------------------------
      ELEMENT REFERENCES (PER-ROW)
  --------------------------------------------------------- */
  const nameInput = entry.querySelector(".appliance-name");
  const autoBox = entry.querySelector(".autocomplete-list");
  const typeInput = entry.querySelector(".appliance-type");
  const typeDropdownBtn = entry.querySelector(".type-dropdown-btn");
  const typeDropdownMenu = entry.querySelector(".type-dropdown-menu");
  const wattsInput = entry.querySelector(".appliance-watts");
  const behaviorInput = entry.querySelector(".appliance-behavior");
  const behaviorValue = entry.querySelector(".behavior-value");

  let matchedApplianceData = null;

  /* ---------------------------------------------------------
      AUTOCOMPLETE LOGIC
  --------------------------------------------------------- */
  function updateSuggestions() {
    const text = nameInput.value.toLowerCase();
    autoBox.innerHTML = "";
    if (!text) return;

    const matches = appliances.filter((a) =>
      a.applianceName.some((n) => n.toLowerCase().includes(text))
    );

    matches.forEach((match) => {
      match.applianceName.forEach((name) => {
        const item = document.createElement("div");
        item.className = "auto-item";
        item.textContent = name;

        item.onclick = () => applyAppliance(match, name);
        autoBox.appendChild(item);
      });
    });
  }

  /* ---------------------------------------------------------
      FILL TYPE DROPDOWN
  --------------------------------------------------------- */
  function fillTypeDropdown(types) {
    typeDropdownMenu.innerHTML = "";

    if (!types || types.length === 0) {
      typeDropdownMenu.innerHTML = `<div class="type-item disabled">No preset types</div>`;
      return;
    }

    types.forEach((t) => {
      const item = document.createElement("div");
      item.className = "type-item";
      item.textContent = t;

      item.onclick = () => {
        typeInput.value = t;
        typeDropdownMenu.style.display = "none";
        if (matchedApplianceData) {
          const behavior = resolveBehaviorValue(matchedApplianceData, t);
          if (behavior != null) {
            applyBehaviorValue(behavior);
          }
        }
      };

      typeDropdownMenu.appendChild(item);
    });
  }

  /* toggle dropdown */
  typeDropdownBtn.onclick = () => {
    typeDropdownMenu.style.display =
      typeDropdownMenu.style.display === "block" ? "none" : "block";
  };

  /* close when clicking outside */
  document.addEventListener("click", (e) => {
    if (!entry.contains(e.target)) {
      typeDropdownMenu.style.display = "none";
      autoBox.innerHTML = "";
    }
  });

  function resolveBehaviorValue(entryData, selectedType) {
    if (!entryData?.usageProfile) return null;
    if (selectedType) {
      const keys = Object.keys(entryData.usageProfile);
      const matchKey = keys.find(
        (key) => key.toLowerCase() === selectedType.toLowerCase()
      );
      if (matchKey) {
        return entryData.usageProfile[matchKey];
      }
    }
    return entryData.usageProfile.default ?? null;
  }

  function applyBehaviorValue(value, { skipPreview = false } = {}) {
    if (typeof value !== "number") return;
    behaviorInput.value = value;
    behaviorValue.textContent = `${value}%`;
    if (!skipPreview) {
      renderAnalysisPreview();
    }
  }

  /* ---------------------------------------------------------
      APPLY APPLIANCE SELECTED FROM AUTOCOMPLETE
  --------------------------------------------------------- */
  function applyAppliance(entryData, selectedName) {
    nameInput.value = selectedName;
    matchedApplianceData = entryData;

    fillTypeDropdown(entryData.applianceType);
    typeInput.value = entryData.applianceType[0] || "";
    wattsInput.value = entryData.power;
    const initialBehavior = resolveBehaviorValue(entryData, typeInput.value);
    if (initialBehavior != null) {
      applyBehaviorValue(initialBehavior, { skipPreview: true });
    }

    autoBox.innerHTML = "";
    renderAnalysisPreview();
  }

  /* autocomplete input listener */
  nameInput.addEventListener("input", () => {
    updateSuggestions();

    const match = appliances.find((ap) =>
      ap.applianceName.some(
        (n) => n.toLowerCase() === nameInput.value.toLowerCase()
      )
    );

    if (match) {
      applyAppliance(match, nameInput.value);
    } else {
      matchedApplianceData = null;
    }
  });

  typeInput.addEventListener("input", () => {
    if (!matchedApplianceData) return;
    const behavior = resolveBehaviorValue(
      matchedApplianceData,
      typeInput.value.trim()
    );
    if (behavior != null) {
      applyBehaviorValue(behavior);
    }
  });

  behaviorInput.addEventListener("input", () => {
    behaviorValue.textContent = `${behaviorInput.value}%`;
    renderAnalysisPreview();
  });

  /* remove row */
  entry.querySelector(".remove-row").addEventListener("click", () => {
    entry.remove();
    renderAnalysisPreview();
  });
}

function gatherInputData() {
  const inputs = [];
  const applianceEntries = applianceList.querySelectorAll(".appliance-entry");
  const globalRate = parseFloat(globalRateInput.value);
  const globalVoltage = globalVoltageSelect.value;
  applianceEntries.forEach((entry) => {
    const name = entry.querySelector(".appliance-name").value;
    const type = entry.querySelector(".appliance-type").value;
    const watts = parseFloat(entry.querySelector(".appliance-watts").value);
    const hoursUsed = parseFloat(entry.querySelector(".appliance-hours").value);
    const usageBehaviorPercent = parseFloat(
      entry.querySelector(".appliance-behavior").value
    );
    inputs.push({
      name,
      type,
      watts,
      hoursUsed,
      ratePhpPerKwh: globalRate,
      usageBehaviorPercent,
      voltageRegion: globalVoltage,
    });
  });
  return inputs;
}

function calculateAnalysis(inputs) {
  return inputs.map((d) => {
    // 1. Calculate Effective Power (Active Use)
    const behaviorFactor = isNaN(d.usageBehaviorPercent)
      ? 1
      : d.usageBehaviorPercent / 100;
      
    // Equivalent to: Effective_Power = Power_Rating * (Usage_Behavior / 100)
    const adjustedWatts = d.watts * behaviorFactor;

    // 2. Calculate Active Daily kWh
    // Equivalent to: Daily_kWh = (Effective_Power * Daily_Use_Hours) / 1000
    const activeDailyKwh = (adjustedWatts * d.hoursUsed) / 1000;
    
    // 3. Calculate Standby Daily kWh
    // Check if standbyWatts is provided and positive. If not, assume 0.
    const standbyWatts = d.standbyWatts || 0;
    // Standby_Hours is often derived as 24 - hoursUsed. Use d.standbyHours if available.
    const standbyHours = d.standbyHours || Math.max(0, 24 - (d.hoursUsed || 0));
    
    // Equivalent to: Standby_kWh = (Standby_Watts * Standby_Hours) / 1000
    const standbyDailyKwh = (standbyWatts * standbyHours) / 1000;
    
    // 4. Calculate Total Monthly kWh
    // Equivalent to: Monthly_kWh = (Daily_kWh + Standby_kWh) * 30
    const totalDailyKwh = activeDailyKwh + standbyDailyKwh;
    const monthlyKwh = totalDailyKwh * 30;

    // 5. Calculate Monthly Cost (PHP)
    // Equivalent to: Monthly_Cost = Monthly_kWh * Utility_Rate
    const monthlyCost = monthlyKwh * d.ratePhpPerKwh;

    return { 
      ...d, 
      adjustedWatts, 
      activeDailyKwh,
      standbyDailyKwh,
      monthlyKwh, 
      monthlyCost 
    };
  });
}
function renderAnalysisPreview() {
  const inputs = gatherInputData();
  const analysis = calculateAnalysis(inputs);

  if (analysis.length === 0) {
    analysisArea.innerHTML = "<p>No appliances to analyze.</p>";
    return;
  }

  // 1. Calculate the total monthly cost
  const totalMonthlyCost = analysis.reduce(
    (total, item) => total + item.monthlyCost,
    0
  );

  let table = `
    <table>
      <thead>
        <tr>
          <th>Appliance</th>
          <th>Usage Behavior</th>
          <th>Monthly kWh</th>
          <th>Monthly Cost (PHP)</th>
        </tr>
      </thead>
      <tbody>
  `;
  analysis.forEach((item) => {
    const usageDisplay = Number.isFinite(item.usageBehaviorPercent)
      ? `${item.usageBehaviorPercent.toFixed(0)}%`
      : "—";
    table += `
      <tr>
        <td>${item.name}</td>
        <td>${usageDisplay}</td>
        <td>${item.monthlyKwh.toFixed(2)}</td>
        <td>${item.monthlyCost.toFixed(2)}</td>
      </tr>
    `;
  });

  // 2. Add a footer row for the total monthly cost
  table += `
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" style="text-align: right;">Total Monthly Cost:</th>
          <th>${totalMonthlyCost.toFixed(2)}</th>
        </tr>
      </tfoot>
    </table>
  `;
  analysisArea.innerHTML = table;
}

function generateLocalAnalysis(inputs) {
  renderAnalysisPreview(); // Already does what's needed
}

function validateInputs(inputs) {
  for (const item of inputs) {
    if (!item.name) return "Appliance name is required.";
    if (isNaN(item.watts) || item.watts <= 0)
      return "Invalid power value. Must be a positive number.";
    if (isNaN(item.hoursUsed) || item.hoursUsed < 0 || item.hoursUsed > 24)
      return "Invalid hours. Must be between 0 and 24.";
    if (isNaN(item.ratePhpPerKwh) || item.ratePhpPerKwh <= 0)
      return "Invalid utility rate. Must be a positive number.";
    if (
      isNaN(item.usageBehaviorPercent) ||
      item.usageBehaviorPercent < 10 ||
      item.usageBehaviorPercent > 100
    )
      return "Usage behavior must be between 10% and 100%.";
  }
  return null; // No error
}

// Event Listeners
addRowBtn.addEventListener("click", () =>
  createApplianceEntry({
    name: "",
    watts: 0,
    hoursUsed: 0,
    usageBehaviorPercent: 100,
  })
);

resetRowsBtn.addEventListener("click", () => {
  applianceList.innerHTML = "";
  idCounter = 0;
  // Example data row
  createApplianceEntry({
    name: "",
    watts: 0,
    hoursUsed: 0,
    usageBehaviorPercent: 100,
  });
  renderAnalysisPreview();
});

generateBtn.addEventListener("click", async () => {
    const inputs = gatherInputData();
    const err = validateInputs(inputs);
    if (err) {
        validationMessage.textContent = err;
        return;
    }
    validationMessage.textContent = "";

    // 1. Run the client-side High-Accuracy Calculation (Source of Truth)
    const analysis = calculateAnalysis(inputs); // This array now includes monthlyKwh and monthlyCost

    renderAnalysisPreview(); // show the deterministic numbers before sending

    // Prefer explicit input, fall back to a key in localStorage (local testing only)
    const defaultApiKey = "AIzaSyAhM165xDsq1vB8lNCgqDe8g27Ji1irh3g";
    const apiKey = defaultApiKey;

    if (apiKey) {
        try {
            geminiContent.textContent = "Calling Gemini (client-side)...";
            // 2. PASS THE PRE-CALCULATED 'analysis' ARRAY TO GEMINI
            const res = await sendToGemini(analysis, apiKey); // Change 'inputs' to 'analysis'
            geminiContent.innerHTML = res ?? "(no text returned)";
        } catch (err) {
            console.error(err);
            geminiContent.textContent = `Gemini error: ${err.message || err}`;
        }
    } else {
        // fallback to local analysis
        generateLocalAnalysis(inputs);
    }
});

// initialize with one example row
resetRowsBtn.click();

// Recompute preview when fields change (event delegation)
applianceList.addEventListener("input", (e) => {
  if (e.target.closest(".appliance-entry")) {
    renderAnalysisPreview();
  }
});

if (globalVoltageSelect) {
  globalVoltageSelect.addEventListener("change", () => {
    syncRateWithRegion();
    renderAnalysisPreview();
  });
}

if (globalRateInput) {
  globalRateInput.addEventListener("input", () => {
    renderAnalysisPreview();
  });
}

if (globalAdvancedToggle && globalAdvancedSection) {
  globalAdvancedToggle.addEventListener("click", () => {
    const isOpen = globalAdvancedSection.classList.toggle("open");
    globalAdvancedSection.setAttribute("aria-hidden", (!isOpen).toString());
    globalAdvancedToggle.setAttribute("aria-expanded", isOpen.toString());
  });
}

// SAVE GEMINI OUTPUT AS PDF

// Source - https://stackoverflow.com/a
// Posted by Kevin Florida, modified by community. See post 'Timeline' for change history
// Retrieved 2025-11-23, License - CC BY-SA 4.0

let savePdfBtn = document.getElementById("downloadGeminiOutput");

function printDiv() {
  const geminiContent = document.getElementById("geminiContent").innerText;
  if (
    geminiContent ===
    "No output yet. Press Generate to send inputs to the server and receive Gemini's output here."
  ) {
    alert("No content available for printing.");
    return;
  }

  // Open the print page in a new window
  window.open("print.html", "_blank");
}

savePdfBtn.addEventListener("click", printDiv);
