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

const appliances = [
  { applianceName: ["Air Conditioner","Aircon"], applianceType: ["Normal","Inverter",], power: 30 },
  { applianceName: ["Refrigerator"], applianceType: [], power: 30 },
  { applianceName: ["Desktop PC"], applianceType: ["Gaming","Office",], power: 30 },
  { applianceName: ["TV"], applianceType: ["Gaming","Office",], power: 30 },
];


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
        value="${data.name || ""}" placeholder="e.g., Aircon" autocomplete="off">
      <div class="autocomplete-list"></div>
    </div>

    <div class="row-group">
      <div class="row">
        <label for="${entryId}-type">Appliance Type</label>
        <div class="type-wrapper">
          <input type="text" id="${entryId}-type" class="appliance-type" value="${data.type || ""}" placeholder="e.g., Inverter">
          <button class="type-dropdown-btn" type="button">▼</button>
          <div class="type-dropdown-menu"></div>
        </div>
      </div>

      <div class="row">
        <label for="${entryId}-watts">Power (Watts)</label>
        <input type="number" id="${entryId}-watts" class="appliance-watts" value="${data.watts || 0}" min="0">
      </div>

      <div class="row">
        <label for="${entryId}-hours">Hours Used Daily</label>
        <input type="number" id="${entryId}-hours" class="appliance-hours" value="${data.hoursUsed || 0}" min="0" max="24">
      </div>

      <div class="row">
        <label for="${entryId}-rate">Utility Rate (PHP/kWh)</label>
        <input type="number" id="${entryId}-rate" class="appliance-rate" value="${data.ratePhpPerKwh || 13.5}" min="0" step="0.1">
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

  /* ---------------------------------------------------------
      AUTOCOMPLETE LOGIC
  --------------------------------------------------------- */
  function updateSuggestions() {
    const text = nameInput.value.toLowerCase();
    autoBox.innerHTML = "";
    if (!text) return;

    const matches = appliances.filter(a =>
      a.applianceName.some(n => n.toLowerCase().includes(text))
    );

    matches.forEach(match => {
      match.applianceName.forEach(name => {
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

    types.forEach(t => {
      const item = document.createElement("div");
      item.className = "type-item";
      item.textContent = t;

      item.onclick = () => {
        typeInput.value = t;
        typeDropdownMenu.style.display = "none";
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

  /* ---------------------------------------------------------
      APPLY APPLIANCE SELECTED FROM AUTOCOMPLETE
  --------------------------------------------------------- */
  function applyAppliance(entryData, selectedName) {
    nameInput.value = selectedName;

    fillTypeDropdown(entryData.applianceType);
    typeInput.value = entryData.applianceType[0] || "";
    wattsInput.value = entryData.power;

    autoBox.innerHTML = "";
    renderAnalysisPreview();
  }

  /* autocomplete input listener */
  nameInput.addEventListener("input", () => {
    updateSuggestions();

    const match = appliances.find(ap =>
      ap.applianceName.some(n => n.toLowerCase() === nameInput.value.toLowerCase())
    );

    if (match) applyAppliance(match, nameInput.value);
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
  applianceEntries.forEach((entry) => {
    const name = entry.querySelector(".appliance-name").value;
    const watts = parseFloat(entry.querySelector(".appliance-watts").value);
    const hoursUsed = parseFloat(entry.querySelector(".appliance-hours").value);
    const ratePhpPerKwh = parseFloat(
      entry.querySelector(".appliance-rate").value
    );
    inputs.push({ name, watts, hoursUsed, ratePhpPerKwh });
  });
  return inputs;
}

function calculateAnalysis(inputs) {
  return inputs.map((d) => {
    const monthlyKwh = (d.watts * d.hoursUsed * 30) / 1000;
    const monthlyCost = monthlyKwh * d.ratePhpPerKwh;
    return { ...d, monthlyKwh, monthlyCost };
  });
}

function renderAnalysisPreview() {
  const inputs = gatherInputData();
  const analysis = calculateAnalysis(inputs);

  if (analysis.length === 0) {
    analysisArea.innerHTML = "<p>No appliances to analyze.</p>";
    return;
  }

  let table = `
    <table>
      <thead>
        <tr>
          <th>Appliance</th>
          <th>Monthly kWh</th>
          <th>Monthly Cost (PHP)</th>
        </tr>
      </thead>
      <tbody>
  `;
  analysis.forEach((item) => {
    table += `
      <tr>
        <td>${item.name}</td>
        <td>${item.monthlyKwh.toFixed(2)}</td>
        <td>${item.monthlyCost.toFixed(2)}</td>
      </tr>
    `;
  });
  table += "</tbody></table>";
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
  }
  return null; // No error
}

// Event Listeners
addRowBtn.addEventListener("click", () =>
  createApplianceEntry({ name: "", watts: 0, hoursUsed: 0, ratePhpPerKwh: 0 })
);

resetRowsBtn.addEventListener("click", () => {
  applianceList.innerHTML = "";
  idCounter = 0;
  // Example data row
  createApplianceEntry({
    name: "",
    watts: 0,
    hoursUsed: 0,
    ratePhpPerKwh: 13.5,
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
  renderAnalysisPreview(); // show the deterministic numbers before sending

  // Prefer explicit input, fall back to a key in localStorage (local testing only)
  const defaultApiKey = "AIzaSyBw5d8MMPUaD3MRzoGKly3PS3nder1LQj4";
  const apiKey = defaultApiKey;

  // Save key to local storage for persistence

  if (apiKey) {
    // call Gemini directly from client using the provided API key (local testing only)
    try {
      geminiContent.textContent = "Calling Gemini (client-side)...";
      // FIX: The sendToGemini function now correctly accepts the apiKey as the second argument
      const res = await sendToGemini(inputs, apiKey);
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
