function addNewInput() {
  // dagdag div
  const newDiv = document.createElement("div");
  newDiv.className = "appliance-entry";
  const container = document.getElementById("appliance-container");
  container.appendChild(newDiv);

  // dagdag input para sa appliance
  const newApplianceInput = document.createElement("input");
  newApplianceInput.type = "text";
  newApplianceInput.name = "appliance";
  newApplianceInput.id = "appliance";
  newApplianceInput.className = "appliance-input";
  newApplianceInput.placeholder = "Enter appliance details";
  newDiv.appendChild(newApplianceInput);

  // dagdag input para sa kilowatt
  const newHoursUsed = document.createElement("input");
  newHoursUsed.type = "text";
  newHoursUsed.name = "hoursUsed";
  newHoursUsed.id = "hoursUsed";
  newHoursUsed.className = "appliance-input";
  newHoursUsed.placeholder = "Enter hours used per day";
  newDiv.appendChild(newHoursUsed);

  const newKilowattConsumptionInput = document.createElement("input");
  newKilowattConsumptionInput.type = "text";
  newKilowattConsumptionInput.name = "kilowattConsumption";
  newKilowattConsumptionInput.id = "kilowattConsumption";
  newKilowattConsumptionInput.className = "appliance-input";
  newKilowattConsumptionInput.placeholder =
    "Enter Kilowatt consumption per hour";
  newDiv.appendChild(newKilowattConsumptionInput);

  const newKilowattPerHourRate = document.createElement("input");
  newKilowattPerHourRate.type = "text";
  newKilowattPerHourRate.name = "kilowattPerHourRate";
  newKilowattPerHourRate.id = "kilowattPerHourRate";
  newKilowattPerHourRate.className = "appliance-input";
  newKilowattPerHourRate.placeholder = "Enter Kilowatt per hour rate";
  newDiv.appendChild(newKilowattPerHourRate);
}
