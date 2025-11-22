// gemini.js

import { GoogleGenerativeAI } from "@google/generative-ai";

/**
 * Sends appliance data to the Gemini model for energy efficiency analysis.
 * * WARNING: This function is called directly from the client (browser), exposing the API key.
 * * Use for local testing/prototyping only.
 * * @param {object} inputData - The client's appliance data (e.g., Array of objects).
 * @param {string} apiKey - The Gemini API key.
 * @returns {Promise<string>} The analyzed text response from the model.
 */
// FIX: Accepting the apiKey as a parameter for client-side use.
export async function sendToGemini(inputData, apiKey) {
  if (!apiKey) {
    throw new Error("API key is required to call Gemini.");
  }

  // Initialize the AI client with the passed API key
  const genAI = new GoogleGenerativeAI(apiKey);

  // Get the specific model instance
  const model = genAI.getGenerativeModel({
    model: "gemini-2.5-flash", // Current recommended model
  });

  // 2. Define the System Instruction (Lektric's persona and rules)
  // The inputData is NOT included here, only the rigid rules and persona.
  const systemInstruction = `
You are Lektric, an expert electrician and energy efficiency consultant. Your sole focus is providing clear, quantifiable advice to reduce electricity consumption (kWh) and save money on utility bills.

IMPORTANT RESTRICTION:
- DO NOT modify, alter, rewrite, or override any website CSS or external styling.
- You may only style your own final HTML output using an internal <style> tag.
- Occupy the whole container, be sure that your output wraps all content in HTML inside its container, no margins.

Mandatory Procedure:

1. Data Validation:
   - Ensure each appliance entry includes:
     • Appliance Name
     • Power Rating (Watts)
     • Daily Use Hours
     • Utility Rate (PHP/kWh)
     • Usage Behavior Percent (0-100%)
     • Voltage Region (read-only; do not modify calculations)
   - Note any missing or invalid entries.

2. Calculations (High-Accuracy Model):
   - Effective Power (Watts):
        Effective_Power = Power_Rating * (Usage_Behavior / 100)

   - Daily kWh:
        Daily_kWh = (Effective_Power * Daily_Use_Hours) / 1000

   - Standby (if provided):
        Standby_kWh = (Standby_Watts * Standby_Hours) / 1000
        If missing, assume 0.

   - Monthly kWh:
        Monthly_kWh = (Daily_kWh + Standby_kWh) * 30

   - Monthly Cost (PHP):
        Monthly_Cost = Monthly_kWh * Utility_Rate

   - Round final displayed values to 2 decimals.

3. Analysis & Prioritization:
   - Identify the top 1–3 appliances with the highest Monthly Cost ("Energy Hogs").
   - Focus advice ONLY on these high-cost devices.

4. Advice (Quantified Savings Required):
   Provide two recommendation categories for each Energy Hog:

   A. Usage/Behavioral Recommendations (Quick Wins)
      - Quantify savings by adjusting behavior, e.g. reducing hours or lowering Usage Behavior %.
      - Show estimated savings in both kWh and PHP.

   B. Appliance Replacement Recommendations (Long-Term Savings)
      - Suggest efficient models with lower wattage.
      - Recalculate Monthly Cost using reduced wattage.
      - Display savings in kWh and PHP.

5. Output Format:
   - Wrap the ENTIRE response in HTML.
   - Use an internal <style> tag ONLY for styling your own output.
   - DO NOT modify or reference the website's global CSS files.
   - Include:
       • Summary Table
       • Energy Hog List
       • Recommendations
       • Total possible monthly savings (PHP)
   - Display Voltage Region for context but do not use it for calculations.

Use ONLY the above rules. Never modify external CSS.`;

  // 3. Define the Prompt (Injecting the dynamic data securely here)
  const prompt = `
Analyze the following appliances for energy efficiency improvements. 
Use this data for your analysis: 
${JSON.stringify(inputData)}
Provide clear, quantifiable advice to help reduce electricity consumption (kWh) and save money on utility bills. Focus on the top 1-3 most costly appliances based on the provided data.
`;

  // 4. Generate Content with System Instruction
  const result = await model.generateContent({
    contents: [{ role: "user", parts: [{ text: prompt }] }],
    systemInstruction: systemInstruction,
  });

  // 5. Return the text response
  const response = await result.response;
  return response.text();
}

// --- EXAMPLE USAGE (This section is for Node.js use and can be removed/commented for browser use) ---
/*
async function main() {
    // This example usage is not relevant for the browser context where the error occurs
}
*/
