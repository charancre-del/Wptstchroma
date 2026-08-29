(function () {
  "use strict";

  var root = document.querySelector("[data-chroma-backup-care-ghl]");
  var config = window.ChromaBackupCareGHL;
  if (!root || !config || !config.formUrl) {
    return;
  }

  function uid(prefix) {
    var value = window.crypto && window.crypto.randomUUID
      ? window.crypto.randomUUID()
      : Date.now().toString(36) + Math.random().toString(36).slice(2);
    return prefix + "_" + value.replace(/-/g, "").slice(0, 20);
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function dateValue(date) {
    return date.getUTCFullYear() + "-" + String(date.getUTCMonth() + 1).padStart(2, "0") + "-" + String(date.getUTCDate()).padStart(2, "0");
  }

  function parseDate(value) {
    return new Date(String(value) + "T12:00:00Z");
  }

  function addDays(value, days) {
    var date = typeof value === "string" ? parseDate(value) : new Date(value.getTime());
    date.setUTCDate(date.getUTCDate() + days);
    return dateValue(date);
  }

  function easternNowParts() {
    var parts = new Intl.DateTimeFormat("en-US", {
      timeZone: config.timezone || "America/New_York",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      hourCycle: "h23"
    }).formatToParts(new Date());
    var values = {};
    parts.forEach(function (part) { values[part.type] = part.value; });
    return {
      date: values.year + "-" + values.month + "-" + values.day,
      minutes: Number(values.hour) * 60 + Number(values.minute)
    };
  }

  function nextWeekday() {
    var value = addDays(easternNowParts().date, 1);
    while ([0, 6].indexOf(parseDate(value).getUTCDay()) !== -1) {
      value = addDays(value, 1);
    }
    return value;
  }

  function money(cents) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(cents || 0) / 100);
  }

  function newChild() {
    return { id: uid("child"), firstName: "", lastName: "", birthDate: "" };
  }

  function newCareDate() {
    return { id: uid("date"), value: nextWeekday(), dropoff: "08:00" };
  }

  var requestedCampus = root.getAttribute("data-campus") || "";
  if (!Object.prototype.hasOwnProperty.call(config.campuses || {}, requestedCampus)) {
    requestedCampus = "";
  }

  var state = {
    step: 1,
    parent: { firstName: "", lastName: "", email: "", phone: "" },
    campusId: requestedCampus,
    children: [newChild()],
    dates: [newCareDate()],
    policies: {},
    message: "",
    checkoutUrl: "",
    orderId: ""
  };

  function campusName() {
    return state.campusId && config.campuses[state.campusId] ? config.campuses[state.campusId] : "Not selected";
  }

  function unitCount() {
    return state.children.length * state.dates.length;
  }

  function totalCents() {
    return unitCount() * Number(config.unitAmountCents || 11500);
  }

  function stepper() {
    var labels = ["Family & campus", "Children & dates", "Review", "Secure payment"];
    return '<ol class="cbc-stepper" aria-label="Reservation progress">' + labels.map(function (label, index) {
      var step = index + 1;
      var current = step === state.step;
      var complete = step < state.step;
      return '<li class="' + (current ? "is-current " : "") + (complete ? "is-complete" : "") + '"><button type="button" disabled><span>' + step + "</span>" + escapeHtml(label) + "</button></li>";
    }).join("") + "</ol>";
  }

  function field(label, name, value, type, autocomplete) {
    return '<label class="cbc-field"><span>' + escapeHtml(label) + ' <b aria-hidden="true">*</b></span><input data-field="' + name + '" type="' + (type || "text") + '" value="' + escapeHtml(value) + '" autocomplete="' + (autocomplete || "off") + '" required></label>';
  }

  function campusOptions() {
    return Object.keys(config.campuses || {}).map(function (id) {
      return '<option value="' + escapeHtml(id) + '"' + (id === state.campusId ? " selected" : "") + ">" + escapeHtml(config.campuses[id]) + "</option>";
    }).join("");
  }

  function summaryHtml() {
    return '<div class="cbc-summary"><p class="cbc-eyebrow">Your reservation</p><dl>' +
      '<div><dt>Campus</dt><dd>' + escapeHtml(campusName()) + "</dd></div>" +
      '<div><dt>Children</dt><dd>' + state.children.length + "</dd></div>" +
      '<div><dt>Care dates</dt><dd>' + state.dates.length + "</dd></div>" +
      '<div><dt>Child-date units</dt><dd>' + unitCount() + "</dd></div></dl>" +
      '<div class="cbc-summary-total"><span>Total</span><strong>' + money(totalCents()) + "</strong></div>" +
      '<p>' + money(config.unitAmountCents || 11500) + " per child, per care date.</p></div>";
  }

  function nav(back, nextLabel, action) {
    return '<div class="cbc-nav-actions">' +
      (back ? '<button type="button" class="cbc-link-button" data-action="back">Back</button>' : "") +
      '<button type="button" class="cbc-primary-button" data-action="' + action + '">' + escapeHtml(nextLabel) + "</button></div>";
  }

  function stepOne() {
    return '<section class="cbc-panel" aria-labelledby="cbc-family-title"><p class="cbc-eyebrow">Step 1 of 3</p>' +
      '<h2 id="cbc-family-title">Tell us who is booking.</h2><p>This information connects the reservation and payment to your GHL family record.</p>' +
      '<div class="cbc-field-grid">' +
      field("Parent first name", "parent-first", state.parent.firstName, "text", "given-name") +
      field("Parent last name", "parent-last", state.parent.lastName, "text", "family-name") +
      field("Email address", "parent-email", state.parent.email, "email", "email") +
      field("Mobile phone", "parent-phone", state.parent.phone, "tel", "tel") +
      '</div><label class="cbc-field cbc-field-wide"><span>Campus <b aria-hidden="true">*</b></span><select data-field="campus" required><option value="">Select a campus</option>' + campusOptions() + "</select></label>" +
      nav(false, "Add children and dates", "next") + "</section>";
  }

  function childCard(child, index) {
    return '<fieldset class="cbc-child" data-child-id="' + escapeHtml(child.id) + '"><legend>Child ' + (index + 1) + "</legend>" +
      '<div class="cbc-field-grid">' +
      field("First name", "child-first", child.firstName, "text", "given-name") +
      field("Last name", "child-last", child.lastName, "text", "family-name") +
      field("Date of birth", "child-birth", child.birthDate, "date", "bday") +
      "</div>" +
      (state.children.length > 1 ? '<button type="button" class="cbc-icon-button" data-remove-child="' + escapeHtml(child.id) + '" aria-label="Remove child">&times;</button>' : "") +
      "</fieldset>";
  }

  function dateCard(careDate, index) {
    var today = easternNowParts().date;
    var maximum = addDays(today, Number(config.bookingHorizonDays || 365));
    return '<fieldset class="cbc-date" data-date-id="' + escapeHtml(careDate.id) + '"><legend>Care date ' + (index + 1) + "</legend>" +
      '<div class="cbc-date-controls"><label class="cbc-field"><span>Date <b aria-hidden="true">*</b></span><input data-field="care-date" type="date" min="' + today + '" max="' + maximum + '" value="' + escapeHtml(careDate.value) + '" required></label>' +
      '<label class="cbc-field"><span>Planned drop-off <b aria-hidden="true">*</b></span><input data-field="dropoff" type="time" max="' + escapeHtml(config.dropoffCutoff || "09:30") + '" value="' + escapeHtml(careDate.dropoff) + '" required></label></div>' +
      (state.dates.length > 1 ? '<button type="button" class="cbc-icon-button" data-remove-date="' + escapeHtml(careDate.id) + '" aria-label="Remove care date">&times;</button>' : "") +
      "</fieldset>";
  }

  function stepTwo() {
    return '<section class="cbc-panel" aria-labelledby="cbc-care-title"><p class="cbc-eyebrow">Step 2 of 3</p>' +
      '<h2 id="cbc-care-title">Choose every child and care date.</h2><p>Each listed child will attend every listed date. Create a separate reservation if children need different dates.</p>' +
      '<div class="cbc-section-heading"><h3>Children</h3><button type="button" class="cbc-secondary-button" data-action="add-child">Add child</button></div>' +
      state.children.map(childCard).join("") +
      '<div class="cbc-section-heading"><h3>Care dates</h3><button type="button" class="cbc-secondary-button" data-action="add-date">Add date</button></div>' +
      state.dates.map(dateCard).join("") +
      nav(true, "Review reservation", "next") + "</section>";
  }

  function policy(name, label) {
    return '<label class="cbc-policy"><input type="checkbox" data-policy="' + name + '"' + (state.policies[name] ? " checked" : "") + ' required><span>' + escapeHtml(label) + "</span></label>";
  }

  function stepThree() {
    var childNames = state.children.map(function (child) { return (child.firstName + " " + child.lastName).trim(); }).join(", ");
    var dates = state.dates.map(function (careDate) { return careDate.value + " at " + careDate.dropoff; }).join(", ");
    return '<section class="cbc-panel" aria-labelledby="cbc-review-title"><p class="cbc-eyebrow">Step 3 of 3</p>' +
      '<h2 id="cbc-review-title">Review before secure payment.</h2>' + summaryHtml() +
      '<div class="cbc-review-details"><p><strong>Children:</strong> ' + escapeHtml(childNames) + '</p><p><strong>Dates:</strong> ' + escapeHtml(dates) + "</p></div>" +
      '<div class="cbc-policies">' +
      policy("terms", "I confirm the campus, children, dates, and drop-off times above are accurate.") +
      policy("payment", "I authorize full payment of $115 for every child-date unit.") +
      policy("deadline", "I understand cancellation refunds and rescheduling require at least 72 hours notice, with no exceptions.") +
      policy("records", "I understand required enrollment and health records must be complete before care begins.") +
      "</div>" + nav(true, "Continue to secure GHL payment", "checkout") + "</section>";
  }

  function orderDetails() {
    return {
      schema_version: 1,
      order_id: state.orderId,
      campus_id: state.campusId,
      campus_name: campusName(),
      timezone: config.timezone || "America/New_York",
      unit_amount_cents: Number(config.unitAmountCents || 11500),
      unit_count: unitCount(),
      total_amount_cents: totalCents(),
      children: state.children.map(function (child) {
        return { first_name: child.firstName, last_name: child.lastName, date_of_birth: child.birthDate };
      }),
      care_dates: state.dates.map(function (careDate) {
        return { date: careDate.value, planned_dropoff: careDate.dropoff };
      })
    };
  }

  function buildCheckoutUrl() {
    var orderToken = uid("bc").replace(/[^a-z0-9]/gi, "").slice(-10).toUpperCase();
    state.orderId = state.orderId || ("BC-" + easternNowParts().date.replace(/-/g, "") + "-" + orderToken);
    var dates = state.dates.map(function (careDate) { return careDate.value; }).sort();
    var params = new URLSearchParams({
      first_name: state.parent.firstName,
      last_name: state.parent.lastName,
      email: state.parent.email,
      phone: state.parent.phone,
      order_id: state.orderId,
      client_request_id: state.orderId,
      campus_id: state.campusId,
      status: "Pending Payment",
      unit_amount_cents: String(config.unitAmountCents || 11500),
      unit_count: String(unitCount()),
      total_amount_cents: String(totalCents()),
      earliest_care_date: dates[0],
      latest_care_date: dates[dates.length - 1],
      terms_accepted_at: new Date().toISOString(),
      refund_status: "None",
      reservation_details: JSON.stringify(orderDetails())
    });
    return config.formUrl + "?" + params.toString();
  }

  function checkoutStep() {
    var frameId = "inline-" + String(config.formId || "backup-care");
    return '<section class="cbc-panel cbc-ghl-checkout" aria-labelledby="cbc-payment-title"><p class="cbc-eyebrow">Secure GHL checkout</p>' +
      '<h2 id="cbc-payment-title">Complete your reservation and payment.</h2>' +
      '<div class="cbc-payment-callout"><strong>Payment quantity: ' + unitCount() + '</strong><span>Your GHL order total must be ' + money(totalCents()) + ".</span></div>" +
      '<p>Confirm the product quantity shown in the secure form, enter payment through GHL using Chroma\'s connected Stripe account, and submit once.</p>' +
      '<iframe class="cbc-ghl-frame" src="' + escapeHtml(state.checkoutUrl) + '" id="' + escapeHtml(frameId) + '" title="Chroma Backup Care secure GHL reservation and payment" loading="eager" referrerpolicy="strict-origin-when-cross-origin" data-layout="{&quot;id&quot;:&quot;INLINE&quot;}" data-trigger-type="alwaysShow" data-activation-type="alwaysActivated" data-deactivation-type="neverDeactivate" data-form-name="Chroma Backup Care Reservation" data-layout-iframe-id="' + escapeHtml(frameId) + '" data-form-id="' + escapeHtml(config.formId || "") + '"></iframe>' +
      '<div class="cbc-nav-actions"><button type="button" class="cbc-link-button" data-action="edit-reservation">Edit reservation</button></div></section>';
  }

  function ensureGhlEmbedScript() {
    if (document.querySelector('script[data-chroma-ghl-form-embed]')) { return; }
    var script = document.createElement("script");
    script.src = "https://link.msgsndr.com/js/form_embed.js";
    script.async = true;
    script.setAttribute("data-chroma-ghl-form-embed", "true");
    document.body.appendChild(script);
  }

  function render() {
    var content = state.step === 1 ? stepOne() : state.step === 2 ? stepTwo() : state.step === 3 ? stepThree() : checkoutStep();
    root.innerHTML = '<div class="cbc-form cbc-flow">' + stepper() + '<div class="cbc-flow-layout"><div class="cbc-flow-main">' + content + '<p class="cbc-status' + (state.message ? " is-error" : "") + '" aria-live="polite">' + escapeHtml(state.message) + '</p></div><aside class="cbc-flow-summary" aria-label="Reservation summary">' + summaryHtml() + "</aside></div></div>";
    if (state.step === 4) { ensureGhlEmbedScript(); }
  }

  function setMessage(message) {
    state.message = message || "";
    var node = root.querySelector(".cbc-status");
    if (node) { node.textContent = state.message; node.classList.toggle("is-error", Boolean(state.message)); }
  }

  function validateStepOne() {
    if (!state.parent.firstName || !state.parent.lastName || !state.parent.email || !state.parent.phone || !state.campusId) {
      return "Enter the parent contact information and select a campus.";
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.parent.email)) {
      return "Enter a valid email address.";
    }
    return "";
  }

  function validateStepTwo() {
    var now = easternNowParts();
    var maximum = addDays(now.date, Number(config.bookingHorizonDays || 365));
    var seenDates = {};
    var error = "";
    state.children.some(function (child) {
      if (!child.firstName || !child.lastName || !child.birthDate) {
        error = "Complete every child's name and date of birth.";
        return true;
      }
      return false;
    });
    if (error) { return error; }

    state.dates.some(function (careDate) {
      if (!careDate.value || !careDate.dropoff) {
        error = "Complete every care date and drop-off time.";
        return true;
      }
      if (careDate.value < now.date || careDate.value > maximum) {
        error = "Care dates must be within the next " + Number(config.bookingHorizonDays || 365) + " days.";
        return true;
      }
      if ([0, 6].indexOf(parseDate(careDate.value).getUTCDay()) !== -1) {
        error = "Backup Care may only be booked on weekdays.";
        return true;
      }
      if (seenDates[careDate.value]) {
        error = "Each care date may only be added once.";
        return true;
      }
      seenDates[careDate.value] = true;
      if (careDate.dropoff > String(config.dropoffCutoff || "09:30")) {
        error = "Planned drop-off must be no later than 9:30 AM.";
        return true;
      }
      if (careDate.value === now.date) {
        var cutoffParts = String(config.sameDayDeadline || "07:30").split(":");
        var cutoffMinutes = Number(cutoffParts[0]) * 60 + Number(cutoffParts[1]);
        var dropoffParts = careDate.dropoff.split(":");
        var dropoffMinutes = Number(dropoffParts[0]) * 60 + Number(dropoffParts[1]);
        if (now.minutes > cutoffMinutes || dropoffMinutes - now.minutes < Number(config.minimumNoticeMinutes || 120)) {
          error = "Same-day care must be booked by 7:30 AM and at least two hours before drop-off.";
          return true;
        }
      }
      return state.children.some(function (child) {
        var birthDate = parseDate(child.birthDate);
        var careDateValue = parseDate(careDate.value);
        var minimumDate = new Date(birthDate.getTime());
        minimumDate.setUTCDate(minimumDate.getUTCDate() + 42);
        var thirteenthBirthday = new Date(birthDate.getTime());
        thirteenthBirthday.setUTCFullYear(thirteenthBirthday.getUTCFullYear() + 13);
        if (careDateValue < minimumDate || careDateValue >= thirteenthBirthday) {
          error = child.firstName + " must be between 6 weeks and 12 years old on every selected care date.";
          return true;
        }
        return false;
      });
    });
    return error;
  }

  function validatePolicies() {
    return ["terms", "payment", "deadline", "records"].every(function (name) { return state.policies[name]; })
      ? ""
      : "Accept each policy before continuing to secure payment.";
  }

  root.addEventListener("input", function (event) {
    var target = event.target;
    var fieldName = target.getAttribute("data-field");
    if (!fieldName) { return; }
    if (fieldName.indexOf("parent-") === 0) {
      var parentMap = { "parent-first": "firstName", "parent-last": "lastName", "parent-email": "email", "parent-phone": "phone" };
      state.parent[parentMap[fieldName]] = target.value.trim();
    } else if (fieldName === "campus") {
      state.campusId = target.value;
    } else if (fieldName.indexOf("child-") === 0) {
      var childNode = target.closest("[data-child-id]");
      var child = state.children.find(function (item) { return item.id === childNode.getAttribute("data-child-id"); });
      var childMap = { "child-first": "firstName", "child-last": "lastName", "child-birth": "birthDate" };
      if (child) { child[childMap[fieldName]] = target.value.trim(); }
    } else {
      var dateNode = target.closest("[data-date-id]");
      var careDate = state.dates.find(function (item) { return item.id === dateNode.getAttribute("data-date-id"); });
      if (careDate) { careDate[fieldName === "care-date" ? "value" : "dropoff"] = target.value; }
    }
    setMessage("");
  });

  root.addEventListener("change", function (event) {
    var policyName = event.target.getAttribute("data-policy");
    if (policyName) {
      state.policies[policyName] = event.target.checked;
      setMessage("");
    }
  });

  root.addEventListener("click", function (event) {
    var button = event.target.closest("button");
    if (!button) { return; }
    var action = button.getAttribute("data-action");
    var removeChild = button.getAttribute("data-remove-child");
    var removeDate = button.getAttribute("data-remove-date");

    if (removeChild) {
      state.children = state.children.filter(function (child) { return child.id !== removeChild; });
      render();
      return;
    }
    if (removeDate) {
      state.dates = state.dates.filter(function (careDate) { return careDate.id !== removeDate; });
      render();
      return;
    }
    if (action === "add-child") {
      if (state.children.length >= Number(config.maxChildren || 8)) {
        setMessage("A reservation may include up to " + Number(config.maxChildren || 8) + " children.");
        return;
      }
      state.children.push(newChild());
      render();
      return;
    }
    if (action === "add-date") {
      if (state.dates.length >= Number(config.maxCareDates || 31)) {
        setMessage("A reservation may include up to " + Number(config.maxCareDates || 31) + " care dates.");
        return;
      }
      state.dates.push(newCareDate());
      render();
      return;
    }
    if (action === "back") {
      state.step = Math.max(1, state.step - 1);
      state.message = "";
      render();
      root.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }
    if (action === "next") {
      var nextError = state.step === 1 ? validateStepOne() : validateStepTwo();
      if (nextError) { setMessage(nextError); return; }
      state.step += 1;
      state.message = "";
      render();
      root.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }
    if (action === "checkout") {
      var policyError = validatePolicies();
      if (policyError) { setMessage(policyError); return; }
      state.checkoutUrl = buildCheckoutUrl();
      state.step = 4;
      state.message = "";
      render();
      root.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }
    if (action === "edit-reservation") {
      state.step = 3;
      state.checkoutUrl = "";
      render();
    }
  });

  render();
}());
