(function () {
  "use strict";

  var root = document.querySelector("[data-chroma-backup-care-cart]");
  if (!root || !window.ChromaBackupCare) {
    return;
  }

  var state = {
    config: null,
    nonce: "",
    requestId: "bc_" + requestId(),
    children: [newChild()],
    dates: [newDate()],
    parent: {
      firstName: "",
      lastName: "",
      email: "",
      phone: ""
    },
    campusId: root.getAttribute("data-campus") || "",
    policies: {},
    quote: null,
    quoteToken: "",
    parentAccessToken: "",
    verificationChallengeId: "",
    verificationEmail: "",
    busy: false
  };

  function requestId() {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return window.crypto.randomUUID().replace(/-/g, "");
    }
    return Date.now().toString(36) + Math.random().toString(36).slice(2);
  }

  function newChild() {
    return {
      id: "child_" + requestId().slice(0, 16),
      firstName: "",
      lastName: "",
      birthDate: "",
      ageGroup: ""
    };
  }

  function isoDate(date) {
    return date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
  }

  function newDate() {
    var date = new Date();
    date.setDate(date.getDate() + 1);
    while (date.getDay() === 0 || date.getDay() === 6) {
      date.setDate(date.getDate() + 1);
    }
    return {
      id: "date_" + requestId().slice(0, 16),
      value: isoDate(date),
      dropoff: "08:00",
      childIds: {}
    };
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function money(cents) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(cents / 100);
  }

  function campusOptions() {
    return state.config.campuses.map(function (campus) {
      return '<option value="' + escapeHtml(campus.id) + '"' + (campus.id === state.campusId ? " selected" : "") + '>' + escapeHtml(campus.name) + " - " + escapeHtml(campus.address) + "</option>";
    }).join("");
  }

  function childFields() {
    return state.children.map(function (child, index) {
      return '<fieldset class="cbc-child" data-child-id="' + escapeHtml(child.id) + '">' +
        '<legend>Child ' + (index + 1) + "</legend>" +
        '<div class="cbc-field-grid">' +
          field("First name", "child-first", child.firstName, "text", "given-name") +
          field("Last name", "child-last", child.lastName, "text", "family-name") +
          field("Date of birth", "child-birth", child.birthDate, "date", "bday") +
          '<label class="cbc-field"><span>Age group</span><select data-field="child-age" required>' +
            '<option value="">Select age group</option>' +
            option("infant", "Infant", child.ageGroup) +
            option("toddler", "Toddler", child.ageGroup) +
            option("preschool", "Preschool", child.ageGroup) +
            option("school", "School age", child.ageGroup) +
          "</select></label>" +
        "</div>" +
        (state.children.length > 1 ? '<button class="cbc-icon-button" type="button" data-remove-child aria-label="Remove child" title="Remove child">&times;</button>' : "") +
      "</fieldset>";
    }).join("");
  }

  function option(value, label, selected) {
    return '<option value="' + value + '"' + (value === selected ? " selected" : "") + ">" + label + "</option>";
  }

  function field(label, dataField, value, type, autocomplete) {
    return '<label class="cbc-field"><span>' + label + '</span><input data-field="' + dataField + '" type="' + type + '" value="' + escapeHtml(value) + '" autocomplete="' + autocomplete + '" required></label>';
  }

  function verificationControls() {
    if (state.parentAccessToken) {
      return '<div class="cbc-verification is-verified"><strong>Email verified</strong><button type="button" class="cbc-link-button" data-reset-verification>Change email</button></div>';
    }
    if (state.verificationChallengeId) {
      return '<div class="cbc-verification"><label class="cbc-field"><span>Verification code</span><input type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" data-verification-code required></label><button type="button" class="cbc-secondary-button" data-verify-email>Verify email</button><button type="button" class="cbc-link-button" data-send-code>Send a new code</button></div>';
    }
    return '<div class="cbc-verification"><button type="button" class="cbc-secondary-button" data-send-code>Verify email</button></div>';
  }

  function dateFields() {
    var today = new Date();
    var maximum = new Date();
    maximum.setDate(maximum.getDate() + state.config.bookingHorizonDays);
    return state.dates.map(function (careDate, index) {
      var childChecks = state.children.map(function (child) {
        var checked = careDate.childIds[child.id] !== false;
        var name = (child.firstName || "Child " + (state.children.indexOf(child) + 1)) + " " + child.lastName;
        return '<label class="cbc-child-choice"><input type="checkbox" data-date-child="' + escapeHtml(child.id) + '"' + (checked ? " checked" : "") + "><span>" + escapeHtml(name.trim()) + "</span></label>";
      }).join("");
      return '<fieldset class="cbc-date" data-date-id="' + escapeHtml(careDate.id) + '">' +
        '<legend>Care date ' + (index + 1) + "</legend>" +
        '<div class="cbc-date-controls">' +
          '<label class="cbc-field"><span>Date</span><input type="date" data-field="care-date" min="' + isoDate(today) + '" max="' + isoDate(maximum) + '" value="' + escapeHtml(careDate.value) + '" required></label>' +
          '<label class="cbc-field"><span>Planned drop-off</span><input type="time" data-field="dropoff" value="' + escapeHtml(careDate.dropoff) + '" required></label>' +
        "</div>" +
        '<div class="cbc-date-children" aria-label="Children attending this date">' + childChecks + "</div>" +
        (state.dates.length > 1 ? '<button class="cbc-icon-button" type="button" data-remove-date aria-label="Remove date" title="Remove date">&times;</button>' : "") +
      "</fieldset>";
    }).join("");
  }

  function render() {
    if (!state.config) {
      root.innerHTML = '<p class="cbc-status">Loading secure booking...</p>';
      return;
    }
    root.innerHTML = '<form class="cbc-form" data-cart-form novalidate>' +
      '<header class="cbc-heading"><p class="cbc-eyebrow">Backup care reservation</p><h2>Choose care for every child and date</h2><p>' + money(state.config.unitAmountCents) + ' per child, per care date. Full payment is required.</p></header>' +
      '<section class="cbc-section" aria-labelledby="cbc-family-title"><h3 id="cbc-family-title">Family</h3><div class="cbc-field-grid">' +
        field("Parent first name", "parent-first", state.parent.firstName, "text", "given-name") +
        field("Parent last name", "parent-last", state.parent.lastName, "text", "family-name") +
        field("Email", "parent-email", state.parent.email, "email", "email") +
        field("Mobile phone", "parent-phone", state.parent.phone, "tel", "tel") +
      "</div>" + verificationControls() + "</section>" +
      '<section class="cbc-section" aria-labelledby="cbc-campus-title"><h3 id="cbc-campus-title">Campus</h3><label class="cbc-field cbc-field-wide"><span>Care location</span><select data-field="campus" required><option value="">Select a campus</option>' + campusOptions() + '</select></label><p class="cbc-campus-status">Choose the campus where care will be provided.</p></section>' +
      '<section class="cbc-section" aria-labelledby="cbc-children-title"><div class="cbc-section-heading"><h3 id="cbc-children-title">Children</h3><button type="button" class="cbc-secondary-button" data-add-child>+ Add child</button></div><div data-children>' + childFields() + "</div></section>" +
      '<section class="cbc-section" aria-labelledby="cbc-dates-title"><div class="cbc-section-heading"><h3 id="cbc-dates-title">Care dates</h3><button type="button" class="cbc-secondary-button" data-add-date>+ Add date</button></div><div data-dates>' + dateFields() + "</div></section>" +
      '<section class="cbc-section" aria-labelledby="cbc-terms-title"><h3 id="cbc-terms-title">Terms</h3><div class="cbc-policies">' +
        policy("backup_care_terms", "I agree to the backup care terms.") +
        policy("full_payment", "I authorize full payment for all selected child-date units.") +
        policy("refund_and_reschedule_deadline", "I understand cancellations and rescheduling are allowed only up to 72 hours before care.") +
        policy("no_discretionary_exceptions", "I understand there are no exceptions after the 72-hour deadline.") +
        policy("privacy_and_communications", "I agree to required booking and care communications.") +
      "</div></section>" +
      '<div class="cbc-actions"><button class="cbc-primary-button" type="submit" data-quote-button>Review total</button><p class="cbc-status" data-status aria-live="polite"></p></div>' +
      '<div data-quote-output></div>' +
    "</form>";
    bind();
  }

  function policy(name, label) {
    return '<label class="cbc-policy"><input type="checkbox" data-policy="' + name + '"' + (state.policies[name] ? " checked" : "") + ' required><span>' + label + "</span></label>";
  }

  function bind() {
    root.querySelector("[data-add-child]").addEventListener("click", function () {
      syncState();
      state.children.push(newChild());
      state.quote = null;
      render();
    });
    root.querySelector("[data-add-date]").addEventListener("click", function () {
      syncState();
      if (state.dates.length >= state.config.maxCareDatesPerOrder) {
        setStatus("One order can include up to " + state.config.maxCareDatesPerOrder + " care dates.", true);
        return;
      }
      state.dates.push(newDate());
      state.quote = null;
      render();
    });
    root.querySelector('[data-field="campus"]').addEventListener("change", function (event) {
      state.campusId = event.target.value;
    });
    root.querySelector('[data-field="parent-email"]').addEventListener("input", function (event) {
      if (event.target.value.trim().toLowerCase() !== state.verificationEmail) {
        state.parentAccessToken = "";
        state.verificationChallengeId = "";
      }
    });
    var sendCode = root.querySelector("[data-send-code]");
    if (sendCode) { sendCode.addEventListener("click", requestEmailCode); }
    var verifyEmail = root.querySelector("[data-verify-email]");
    if (verifyEmail) { verifyEmail.addEventListener("click", verifyEmailCode); }
    var resetVerification = root.querySelector("[data-reset-verification]");
    if (resetVerification) {
      resetVerification.addEventListener("click", function () {
        state.parentAccessToken = "";
        state.verificationChallengeId = "";
        state.verificationEmail = "";
        render();
        root.querySelector('[data-field="parent-email"]').focus();
      });
    }
    root.querySelectorAll("[data-remove-child]").forEach(function (button) {
      button.addEventListener("click", function () {
        syncState();
        var id = button.closest("[data-child-id]").getAttribute("data-child-id");
        state.children = state.children.filter(function (child) { return child.id !== id; });
        state.quote = null;
        render();
      });
    });
    root.querySelectorAll("[data-remove-date]").forEach(function (button) {
      button.addEventListener("click", function () {
        syncState();
        var id = button.closest("[data-date-id]").getAttribute("data-date-id");
        state.dates = state.dates.filter(function (item) { return item.id !== id; });
        state.quote = null;
        render();
      });
    });
    root.querySelector("[data-cart-form]").addEventListener("submit", submitQuote);
  }

  async function requestEmailCode() {
    if (state.busy) { return; }
    syncState();
    var emailInput = root.querySelector('[data-field="parent-email"]');
    if (!emailInput || !emailInput.reportValidity()) { return; }
    setBusy(true, "Sending verification code...");
    try {
      var response = await api(window.ChromaBackupCare.requestAccessUrl, { email: state.parent.email });
      state.verificationChallengeId = response.challenge_id;
      state.verificationEmail = state.parent.email.trim().toLowerCase();
      state.parentAccessToken = "";
      setBusy(false);
      render();
      setStatus("Verification code sent. Check your email.", false);
      root.querySelector("[data-verification-code]").focus();
    } catch (error) {
      setStatus(error.message || "The verification code could not be sent.", true);
      setBusy(false);
    }
  }

  async function verifyEmailCode() {
    if (state.busy) { return; }
    syncState();
    var codeInput = root.querySelector("[data-verification-code]");
    if (!codeInput || !codeInput.reportValidity()) { return; }
    setBusy(true, "Verifying email...");
    try {
      var response = await api(window.ChromaBackupCare.verifyAccessUrl, {
        challenge_id: state.verificationChallengeId,
        email: state.parent.email,
        code: codeInput.value.trim()
      });
      state.parentAccessToken = response.parent_access_token;
      state.verificationEmail = state.parent.email.trim().toLowerCase();
      state.verificationChallengeId = "";
      setBusy(false);
      render();
      setStatus("Email verified.", false);
    } catch (error) {
      setStatus(error.message || "The verification code is invalid or expired.", true);
      setBusy(false);
    }
  }

  function syncState() {
    var parentFirst = root.querySelector('[data-field="parent-first"]');
    if (parentFirst) {
      state.parent.firstName = parentFirst.value.trim();
      state.parent.lastName = root.querySelector('[data-field="parent-last"]').value.trim();
      state.parent.email = root.querySelector('[data-field="parent-email"]').value.trim();
      state.parent.phone = root.querySelector('[data-field="parent-phone"]').value.trim();
      state.campusId = root.querySelector('[data-field="campus"]').value;
    }
    root.querySelectorAll("[data-policy]").forEach(function (checkbox) {
      state.policies[checkbox.getAttribute("data-policy")] = checkbox.checked;
    });
    root.querySelectorAll("[data-child-id]").forEach(function (fieldset) {
      var child = state.children.find(function (item) { return item.id === fieldset.getAttribute("data-child-id"); });
      if (!child) { return; }
      child.firstName = fieldset.querySelector('[data-field="child-first"]').value.trim();
      child.lastName = fieldset.querySelector('[data-field="child-last"]').value.trim();
      child.birthDate = fieldset.querySelector('[data-field="child-birth"]').value;
      child.ageGroup = fieldset.querySelector('[data-field="child-age"]').value;
    });
    root.querySelectorAll("[data-date-id]").forEach(function (fieldset) {
      var item = state.dates.find(function (date) { return date.id === fieldset.getAttribute("data-date-id"); });
      if (!item) { return; }
      item.value = fieldset.querySelector('[data-field="care-date"]').value;
      item.dropoff = fieldset.querySelector('[data-field="dropoff"]').value;
      item.childIds = {};
      fieldset.querySelectorAll("[data-date-child]").forEach(function (checkbox) {
        item.childIds[checkbox.getAttribute("data-date-child")] = checkbox.checked;
      });
    });
  }

  function collectOrder() {
    syncState();
    var form = root.querySelector("[data-cart-form]");
    if (!form.reportValidity()) {
      return null;
    }
    if (!state.parentAccessToken
      || state.parent.email.trim().toLowerCase() !== state.verificationEmail) {
      setStatus("Verify the parent email before reviewing the booking.", true);
      return null;
    }
    var attendance = [];
    state.dates.forEach(function (careDate) {
      state.children.forEach(function (child) {
        if (careDate.childIds[child.id] !== false) {
          attendance.push({
            client_child_id: child.id,
            care_date: careDate.value,
            planned_dropoff_local: careDate.dropoff
          });
        }
      });
    });
    var policies = {};
    root.querySelectorAll("[data-policy]").forEach(function (checkbox) {
      policies[checkbox.getAttribute("data-policy")] = checkbox.checked;
    });
    return {
      contract_version: 1,
      parent_access_token: state.parentAccessToken,
      client_request_id: state.requestId,
      campus_id: root.querySelector('[data-field="campus"]').value,
      parent: {
        first_name: state.parent.firstName,
        last_name: state.parent.lastName,
        email: state.parent.email,
        mobile_phone: state.parent.phone
      },
      children: state.children.map(function (child) {
        return {
          client_child_id: child.id,
          first_name: child.firstName,
          last_name: child.lastName,
          date_of_birth: child.birthDate,
          age_group: child.ageGroup
        };
      }),
      attendance: attendance,
      policy_acceptance: policies
    };
  }

  async function submitQuote(event) {
    event.preventDefault();
    if (state.busy) { return; }
    var order = collectOrder();
    if (!order) { return; }
    if (!order.attendance.length) {
      setStatus("Select at least one child for a care date.", true);
      return;
    }
    setBusy(true, "Checking enrollment, dates, and availability...");
    try {
      var response = await api(window.ChromaBackupCare.quoteUrl, { order: order });
      if (!response.contract_valid) {
        renderErrors(response, order);
        return;
      }
      state.quote = response.quote;
      state.quoteToken = response.quote_token;
      renderQuote(order);
      setStatus("Quote verified. No payment has been collected.", false);
    } catch (error) {
      setStatus(error.message || "Booking is temporarily unavailable.", true);
    } finally {
      setBusy(false);
    }
  }

  function renderErrors(response, order) {
    var output = root.querySelector("[data-quote-output]");
    var enrollment = "";
    if (response.enrollment_required && response.child_enrollment_form_url) {
      var familyLink = response.family_profile_form_url
        ? '<p><a class="cbc-secondary-button" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(response.family_profile_form_url) + '">Complete family profile</a></p>'
        : "";
      enrollment = '<div class="cbc-enrollment"><h3>Enrollment required</h3>' + familyLink + response.enrollment_required.map(function (item) {
        var child = state.children.find(function (candidate) { return candidate.id === item.client_child_id; });
        var params = new URLSearchParams({
          child_record_key: item.child_record_key
        });
        return '<p><a class="cbc-secondary-button" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(response.child_enrollment_form_url + "?" + params.toString()) + '">Complete enrollment for ' + escapeHtml(child ? child.firstName : "child") + "</a></p>";
      }).join("") + "</div>";
    }
    output.innerHTML = '<div class="cbc-error-summary" role="alert"><h3>We could not prepare payment</h3><ul>' +
      (response.errors || []).map(function (error) { return "<li>" + escapeHtml(error) + "</li>"; }).join("") +
      "</ul></div>" + enrollment;
    setStatus("No payment was created.", true);
  }

  function renderQuote(order) {
    var output = root.querySelector("[data-quote-output]");
    output.innerHTML = '<section class="cbc-quote" aria-labelledby="cbc-quote-title"><div><p class="cbc-eyebrow">Verified total</p><h3 id="cbc-quote-title">' + money(state.quote.total_amount_cents) + '</h3><p>' + state.quote.unit_count + " child-date " + (state.quote.unit_count === 1 ? "unit" : "units") + " at " + money(state.quote.unit_amount_cents) + " each.</p></div>" +
      '<button class="cbc-primary-button" type="button" data-checkout' + (state.config.checkoutEnabled ? "" : " disabled") + ">Continue to secure payment</button>" +
      (!state.config.checkoutEnabled ? '<p class="cbc-test-note">Checkout remains disabled until test infrastructure is approved.</p>' : "") +
    "</section>";
    var button = output.querySelector("[data-checkout]");
    if (button && !button.disabled) {
      button.addEventListener("click", function () { beginCheckout(order); });
    }
  }

  async function beginCheckout(order) {
    if (state.busy) { return; }
    setBusy(true, "Opening secure payment...");
    try {
      var response = await api(window.ChromaBackupCare.checkoutUrl, {
        order: order,
        quote_token: state.quoteToken
      });
      if (!response.invoice_id || response.payment_delivery !== "ghl_invoice_email") {
        throw new Error("GHL did not confirm invoice delivery.");
      }
      root.querySelector("[data-quote-output]").innerHTML = '<section class="cbc-quote" aria-live="polite"><div><p class="cbc-eyebrow">Invoice sent</p><h3>Check your email to pay</h3><p>GHL sent one secure invoice for the complete booking. Care is confirmed only after full payment is verified.</p></div></section>';
      setStatus("Invoice sent through GHL. No appointment has been created yet.", false);
      setBusy(false);
    } catch (error) {
      setStatus(error.message || "Payment could not be opened. No charge was created.", true);
      setBusy(false);
    }
  }

  async function api(url, body) {
    var response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-Chroma-Backup-Care-Nonce": state.nonce
      },
      body: JSON.stringify(body)
    });
    var payload = await response.json().catch(function () { return {}; });
    if (!response.ok && !payload.contract_valid) {
      var message = payload.message || (payload.data && payload.data.message) || "The request could not be completed.";
      throw new Error(message);
    }
    return payload;
  }

  function setBusy(busy, message) {
    state.busy = busy;
    root.querySelectorAll("button, input, select").forEach(function (control) {
      if (busy) {
        control.setAttribute("data-cbc-was-disabled", control.disabled ? "1" : "0");
        control.disabled = true;
      } else if (control.hasAttribute("data-cbc-was-disabled")) {
        control.disabled = control.getAttribute("data-cbc-was-disabled") === "1";
        control.removeAttribute("data-cbc-was-disabled");
      }
    });
    if (message) { setStatus(message, false); }
  }

  function setStatus(message, error) {
    var status = root.querySelector("[data-status]");
    if (!status) { return; }
    status.textContent = message || "";
    status.classList.toggle("is-error", Boolean(error));
  }

  fetch(window.ChromaBackupCare.configUrl, { credentials: "same-origin" })
    .then(function (response) {
      if (!response.ok) { throw new Error("Configuration unavailable"); }
      return response.json();
    })
    .then(function (config) {
      state.config = config;
      state.nonce = config.nonce;
      render();
    })
    .catch(function () {
      root.innerHTML = '<p class="cbc-status is-error">Backup-care booking is temporarily unavailable.</p>';
    });
}());
