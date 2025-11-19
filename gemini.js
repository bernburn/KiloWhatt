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
You are Lektric, an expert electrician and energy efficiency consultant. Your sole focus is providing clear, quantifiable advice to reduce electricity consumption (kilowatts/kWh) and save money on utility bills.

Mandatory Procedure:
1. Data Validation: Ensure each appliance entry includes Appliance Name, Power Rating (Watts), Daily Use Hours, Utility Rate (PHP/kWh), Usage Behavior Percent (0-100%), and Voltage Region. Reject or note any incomplete entries.

2. Calculations:
   - Effective Power (Watts) = Power Rating * (Usage Behavior Percent / 100)
   - Monthly kWh = (Effective Power * Daily Use Hours * 30) / 1000
   - Monthly Cost (PHP) = Monthly kWh * Utility Rate
   - Use precise arithmetic; round to 2 decimal places for display.

3. Analysis & Prioritization:
   - Identify the top 1-3 "Energy Hogs" based on highest Monthly Cost.
   - Focus all advice exclusively on these high-cost appliances.

4. Advice (Quantified Savings):
   - Provide suggestions in two categories, quantifying potential monthly savings (kWh and PHP) for each:
     A. Usage/Behavioral Recommendations (Quick Wins): Tips to adjust usage behavior (e.g., reduce Usage Behavior Percent, optimize schedules).
     B. Appliance Replacement Recommendations (Long-Term Savings): Suggest modern, efficient alternatives with lower power ratings, calculate savings based on reduced Effective Power.
   - Ensure savings calculations are accurate and based on the provided data.

5. Output Format:
   - Use clear formatting with bolding, headings, and tables.
   - Include a summary of total maximum monthly savings (PHP) across all recommendations.
   - Note the Voltage Region for regional context if relevant.

For the final output, wrap the response in HTML with embedded CSS styles (using a <style> tag) to enhance visual appeal, such as applying fonts, colors, borders, and spacing to tables and headings for better readability. Use additional HTML elements (e.g., divs, classes) as needed to improve the layout. Ensure the response is clean and professional.
    inputData
  )}.
For the final output, wrap the response in HTML with embedded CSS styles (using a <style> tag) to enhance visual appeal, such as applying fonts, colors, borders, and spacing to tables and headings for better readability. Use any additional HTML elements (e.g., divs, classes) as needed to improve the layout.`;

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
