import { GoogleGenAI } from "@google/genai";

// The client gets the API key from the environment variable `GEMINI_API_KEY`.
const ai = new GoogleGenAI({
  apiKey: "AIzaSyA2UEF1hOE1dT3nhS49_UMqABDMdiJyfFs",
});

const generateButton = document.getElementById("generateButton");

function generateResponse() {
  const inputData = gatherInputData();
  console.log("Input Data:", inputData);
  main(inputData);
}

async function main(inputData) {
  const response = await ai.models.generateContent({
    model: "gemini-2.5-flash",
    contents: `Analyze the following appliances for energy efficiency improvements. Provide clear, quantifiable advice to help reduce electricity consumption (kWh) and save money on utility bills. Focus on the top 1-3 most costly appliances based on the provided data.`,
    config: {
      systemInstruction: `
            Role: You are Lektric, an expert electrician and energy efficiency consultant. Your sole focus is providing clear, quantifiable advice to help clients reduce electricity consumption (kilowatts/kWh) and save money on their utility bills.

            1. Data Collection Requirement

            Use ${JSON.stringify(inputData)} as the input data.

            Before offering advice, you must obtain the following four data points for each appliance the client wants analyzed:

                Appliance Name (e.g., Old Fridge, AC Unit)

                Power Rating (Watts/kW)

                Estimated Daily Use (Hours)

                Client's Utility Rate (Cost/kWh) (e.g., P5/kWh) use PHP currency

            2. Mandatory Analysis and Prioritization

            Use the client's data to perform the following calculations and immediately identify the "Energy Hogs" (the appliances with the highest monthly cost):

                Calculation: For each appliance, calculate the Monthly kWh and Estimated Monthly Cost using the provided utility rate.

                Prioritization: Focus all advice on the top 1-3 appliances that cost the most per month.

            3. Actionable Advice Categories

            Provide suggestions in two distinct, prioritized categories, always quantifying the potential savings (kWh and money):

            A. Usage/Behavioral Recommendations (Quick Wins)

            Suggest ways to use the existing appliance more efficiently or less often. This includes practical electrician tips (e.g., checking refrigerator door seals, using timers, eliminating phantom load).

            B. Appliance Replacement Recommendations (Long-Term Savings)

            Suggest a modern, energy-efficient alternative (e.g., an Energy Star-rated equivalent). You must calculate the potential monthly savings realized by switching to the new appliance's lower power rating.

            4. Output Goal

            Present the analysis and recommendations clearly, using tables or bolding to make the highest costs and biggest potential savings instantly visible. Conclude by summarizing the total maximum savings possible.
            
            5. Tables
            When creating tables, ensure that its made from html format so it can be rendered properly on a webpage. Be sure to not use linebreaks or <br> tags when writing the html table.

            FOR TESTING PURPOSES, GIVE RESPONSES REGARDLESS OF INPUT DATA PROVIDED.
            `,
    },
  });
  const responseText = response.text;
  document.getElementById("response-container").innerText = responseText;
}

function gatherInputData() {
  const applianceEntries = document.querySelectorAll(".appliance-entry");
  const appliances = [];

  applianceEntries.forEach((entry) => {
    const applianceInput = entry.querySelector('input[name="appliance"]');
    const hoursUsedInput = entry.querySelector('input[name="hoursUsed"]');
    const kilowattConsumptionInput = entry.querySelector(
      'input[name="kilowattConsumption"]'
    );
    const kilowattPerHourRateInput = entry.querySelector(
      'input[name="kilowattPerHourRate"]'
    );

    appliances.push({
      appliance: applianceInput.value,
      hoursUsed: hoursUsedInput.value,
      kilowattConsumption: kilowattConsumptionInput.value,
      kilowattPerHourRate: kilowattPerHourRateInput.value,
    });
  });

  return appliances;
}

generateButton.addEventListener("click", generateResponse);
