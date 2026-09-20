(function () {
  "use strict";

  var root = document.querySelector("[data-chroma-backup-care-cart]");
  if (!root || !window.ChromaBackupCare) { return; }

  function uid(prefix) {
    var value = window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2);
    return prefix + "_" + value.replace(/-/g, "").slice(0, 20);
  }

  function newChild(profile) {
    profile = profile || {};
    return {
      id: uid("child"),
      profileId: profile.id || "",
      firstName: profile.first_name || "",
      lastName: profile.last_name || "",
      birthDate: profile.date_of_birth || "",
      ageGroup: profile.age_group || "",
      enrollmentComplete: Boolean(profile.enrollment_complete)
    };
  }

  function dateValue(date) {
    return date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
  }

  function newCareDate() {
    var date = new Date();
    date.setDate(date.getDate() + 1);
    while (date.getDay() === 0 || date.getDay() === 6) { date.setDate(date.getDate() + 1); }
    return { id: uid("date"), value: dateValue(date), dropoff: "08:00", childIds: {} };
  }

  var state = {
    config: null,
    nonce: "",
    step: 1,
    completedStep: 0,
    requestId: uid("bc"),
    parent: { firstName: "", lastName: "", email: "", phone: "" },
    parentAccessToken: "",
    verificationEmail: "",
    verificationChallengeId: "",
    profiles: [],
    campusId: root.getAttribute("data-campus") || "",
    dates: [newCareDate()],
    children: [newChild()],
    policies: {},
    quote: null,
    quoteToken: "",
    quoteOrder: null,
    requestIdConfirmed: "",
    invoiceId: "",
    busy: false,
    message: "",
    messageError: false
  };

  function escapeHtml(value) {
    return String(value == null ? "" : value).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  function money(cents) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format((Number(cents) || 0) / 100);
  }

  function selectedCampus() {
    return state.config && state.config.campuses ? state.config.campuses.find(function (campus) { return String(campus.id) === String(state.campusId); }) : null;
  }

  function activeChildren() {
    return state.children.filter(function (child) {
      return child.profileId || child.firstName || child.lastName || child.birthDate || child.ageGroup;
    });
  }

  function selectedUnits() {
    var units = 0;
    var children = activeChildren();
    state.dates.forEach(function (careDate) {
      children.forEach(function (child) {
        if (careDate.childIds[child.id] !== false) { units += 1; }
      });
    });
    return units;
  }

  function summaryHtml(compact) {
    var campus = selectedCampus();
    var units = selectedUnits();
    var total = state.quote ? state.quote.total_amount_cents : units * (state.config ? state.config.unitAmountCents : 11500);
    var children = activeChildren();
    return '<div class="cbc-summary' + (compact ? ' cbc-summary--compact' : '') + '">' +
      '<p class="cbc-eyebrow">Your reservation</p>' +
      '<dl><div><dt>Campus</dt><dd>' + escapeHtml(campus ? campus.name : "Not selected") + '</dd></div>' +
      '<div><dt>Dates</dt><dd>' + state.dates.length + '</dd></div>' +
      '<div><dt>Children</dt><dd>' + children.length + '</dd></div>' +
      '<div><dt>Child-date units</dt><dd>' + units + '</dd></div></dl>' +
      '<div class="cbc-summary-total"><span>Estimated total</span><strong>' + money(total) + '</strong></div>' +
      '<p>Final availability and enrollment requirements are checked before an invoice is created.</p>' +
    '</div>';
  }

  function stepNav() {
    var labels = ["Verify email", "Campus & dates", "Children", "Review & payment"];
    return '<ol class="cbc-stepper" aria-label="Reservation progress">' + labels.map(function (label, index) {
      var step = index + 1;
      var current = step === state.step;
      var complete = step <= state.completedStep;
      return '<li class="' + (current ? 'is-current ' : '') + (complete ? 'is-complete' : '') + '"><button type="button" data-go-step="' + step + '"' + (step > state.completedStep + 1 ? ' disabled' : '') + (current ? ' aria-current="step"' : '') + '><span>' + step + '</span>' + escapeHtml(label) + '</button></li>';
    }).join("") + '</ol>';
  }

  function field(label, name, value, type, autocomplete, required) {
    return '<label class="cbc-field"><span>' + escapeHtml(label) + (required ? ' <b aria-hidden="true">*</b>' : '') + '</span><input data-field="' + name + '" type="' + (type || 'text') + '" value="' + escapeHtml(value) + '" autocomplete="' + (autocomplete || 'off') + '"' + (required ? ' required' : '') + '></label>';
  }

  function option(value, label, selected) {
    return '<option value="' + escapeHtml(value) + '"' + (String(value) === String(selected) ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
  }

  function stepOne() {
    var verified = state.parentAccessToken && state.parent.email.toLowerCase() === state.verificationEmail;
    var verification = verified
      ? '<div class="cbc-verified"><strong>Email verified</strong><span>' + escapeHtml(state.parent.email) + '</span><button type="button" class="cbc-link-button" data-action="reset-email">Use another email</button></div>'
      : state.verificationChallengeId
        ? '<div class="cbc-verification"><label class="cbc-field"><span>Six-digit verification code</span><input data-verification-code inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></label><button type="button" class="cbc-primary-button" data-action="verify-code">Verify and continue</button><button type="button" class="cbc-link-button" data-action="send-code">Send a new code</button></div>'
        : '<button type="button" class="cbc-primary-button" data-action="send-code">Email me a verification code</button>';
    var profiles = verified && state.profiles.length
      ? '<div class="cbc-saved-profiles"><h3>Children already connected to this email</h3><p>Select a profile now or add a child in step 3.</p><div>' + state.profiles.map(function (profile) {
          var used = state.children.some(function (child) { return child.profileId === profile.id; });
          return '<button type="button" class="cbc-profile-card' + (used ? ' is-selected' : '') + '" data-profile-id="' + escapeHtml(profile.id) + '"' + (used ? ' disabled' : '') + '><strong>' + escapeHtml((profile.first_name + ' ' + profile.last_name).trim()) + '</strong><span>' + escapeHtml(profile.age_group || 'Saved child profile') + '</span></button>';
        }).join('') + '</div></div>'
      : '';
    return '<section class="cbc-panel" aria-labelledby="cbc-step-one"><p class="cbc-eyebrow">Step 1 of 4</p><h2 id="cbc-step-one">Start with the parent email.</h2><p>We use a one-time code to protect saved child profiles and reservation details.</p><div class="cbc-field-grid">' +
      field("Parent first name", "parent-first", state.parent.firstName, "text", "given-name", true) +
      field("Parent last name", "parent-last", state.parent.lastName, "text", "family-name", true) +
      field("Email address", "parent-email", state.parent.email, "email", "email", true) +
      field("Mobile phone", "parent-phone", state.parent.phone, "tel", "tel", true) +
      '</div>' + verification + profiles + (verified ? navButtons(false, "Choose campus and dates") : '') + '</section>';
  }

  function stepTwo() {
    var max = new Date();
    max.setDate(max.getDate() + Number(state.config.bookingHorizonDays || 365));
    var today = dateValue(new Date());
    var campuses = (state.config.campuses || []).map(function (campus) { return option(campus.id, campus.name + " — " + campus.address, state.campusId); }).join('');
    var dates = state.dates.map(function (careDate, index) {
      return '<fieldset class="cbc-date" data-date-id="' + escapeHtml(careDate.id) + '"><legend>Care date ' + (index + 1) + '</legend><div class="cbc-date-controls">' +
        '<label class="cbc-field"><span>Date <b aria-hidden="true">*</b></span><input data-field="care-date" type="date" min="' + today + '" max="' + dateValue(max) + '" value="' + escapeHtml(careDate.value) + '" required></label>' +
        '<label class="cbc-field"><span>Planned drop-off <b aria-hidden="true">*</b></span><input data-field="dropoff" type="time" value="' + escapeHtml(careDate.dropoff) + '" required></label>' +
        '</div>' + (state.dates.length > 1 ? '<button type="button" class="cbc-icon-button" data-remove-date="' + escapeHtml(careDate.id) + '" aria-label="Remove care date">&times;</button>' : '') + '</fieldset>';
    }).join('');
    return '<section class="cbc-panel" aria-labelledby="cbc-step-two"><p class="cbc-eyebrow">Step 2 of 4</p><h2 id="cbc-step-two">Choose a campus and care dates.</h2><p>Options remain subject to closures, staffing, classroom ratios, and completion of required records.</p><label class="cbc-field cbc-field-wide"><span>Campus <b aria-hidden="true">*</b></span><select data-field="campus" required><option value="">Select a campus</option>' + campuses + '</select></label><div class="cbc-section-heading"><h3>Dates</h3><button type="button" class="cbc-secondary-button" data-action="add-date">Add another date</button></div>' + dates + navButtons(true, "Continue to children") + '</section>';
  }

  function childCard(child, index) {
    return '<fieldset class="cbc-child" data-child-id="' + escapeHtml(child.id) + '"><legend>Child ' + (index + 1) + '</legend><div class="cbc-field-grid">' +
      field("First name", "child-first", child.firstName, "text", "given-name", true) +
      field("Last name", "child-last", child.lastName, "text", "family-name", true) +
      field("Date of birth", "child-birth", child.birthDate, "date", "bday", true) +
      '<label class="cbc-field"><span>Age group <b aria-hidden="true">*</b></span><select data-field="child-age" required><option value="">Select age group</option>' + option("infant", "Infant", child.ageGroup) + option("toddler", "Toddler", child.ageGroup) + option("preschool", "Preschool", child.ageGroup) + option("school", "School age", child.ageGroup) + '</select></label></div>' +
      (child.enrollmentComplete ? '<p class="cbc-record-status is-complete">Required enrollment record is on file.</p>' : '<p class="cbc-record-status">Enrollment and health records will be checked before care.</p>') +
      (state.children.length > 1 ? '<button type="button" class="cbc-icon-button" data-remove-child="' + escapeHtml(child.id) + '" aria-label="Remove child">&times;</button>' : '') + '</fieldset>';
  }

  function stepThree() {
    var attendance = state.dates.map(function (careDate) {
      return '<fieldset class="cbc-attendance" data-attendance-date="' + escapeHtml(careDate.id) + '"><legend>' + escapeHtml(careDate.value) + '</legend>' + state.children.map(function (child, index) {
        var checked = careDate.childIds[child.id] !== false;
        var name = (child.firstName + ' ' + child.lastName).trim() || 'Child ' + (index + 1);
        return '<label class="cbc-child-choice"><input type="checkbox" data-date-child="' + escapeHtml(child.id) + '"' + (checked ? ' checked' : '') + '><span>' + escapeHtml(name) + '</span></label>';
      }).join('') + '</fieldset>';
    }).join('');
    return '<section class="cbc-panel" aria-labelledby="cbc-step-three"><p class="cbc-eyebrow">Step 3 of 4</p><h2 id="cbc-step-three">Who needs care?</h2><p>Use a saved child profile or add the details needed to check age eligibility and enrollment readiness.</p><div class="cbc-section-heading"><h3>Children</h3><button type="button" class="cbc-secondary-button" data-action="add-child">Add another child</button></div>' + state.children.map(childCard).join('') + '<div class="cbc-attendance-list"><h3>Children attending each date</h3>' + attendance + '</div>' + navButtons(true, "Review reservation") + '</section>';
  }

  function policy(name, label) {
    return '<label class="cbc-policy"><input type="checkbox" data-policy="' + name + '"' + (state.policies[name] ? ' checked' : '') + ' required><span>' + escapeHtml(label) + '</span></label>';
  }

  function stepFour() {
    var refundHours = Number(state.config.refundCutoffHours || 72);
    var rescheduleHours = Number(state.config.rescheduleCutoffHours || refundHours);
    return '<section class="cbc-panel" aria-labelledby="cbc-step-four"><p class="cbc-eyebrow">Step 4 of 4</p><h2 id="cbc-step-four">Review the total and policies.</h2><p>No charge is created until availability, records, and selected child-dates pass the server checks.</p>' + summaryHtml(false) + '<div class="cbc-policies">' +
      policy("backup_care_terms", "I agree to the Backup Care terms.") +
      policy("full_payment", "I authorize full payment for every selected child-date.") +
      policy("refund_and_reschedule_deadline", "I understand refundable cancellation closes " + refundHours + " hours before care and rescheduling closes " + rescheduleHours + " hours before care.") +
      policy("no_discretionary_exceptions", "I understand campus closures, staffing controls, and required ratios may affect available dates.") +
      policy("privacy_and_communications", "I agree to required reservation, enrollment, care, and payment communications.") +
      '</div><div data-quote-output>' + quoteHtml() + '</div>' + navButtons(true, state.quote ? "" : "Check availability and total", true) + '</section>';
  }

  function quoteHtml() {
    if (!state.quote) { return ''; }
    return '<div class="cbc-quote"><div><p class="cbc-eyebrow">Verified total</p><h3>' + money(state.quote.total_amount_cents) + '</h3><p>' + state.quote.unit_count + ' child-date ' + (state.quote.unit_count === 1 ? 'unit' : 'units') + ' at ' + money(state.quote.unit_amount_cents) + ' each.</p></div><button type="button" class="cbc-primary-button" data-action="checkout"' + (state.config.checkoutEnabled ? '' : ' disabled') + '>Send secure invoice</button>' + (!state.config.checkoutEnabled ? '<p class="cbc-test-note">Checkout remains disabled until the operational release gates are approved.</p>' : '') + '</div>';
  }

  function navButtons(back, nextLabel, submit) {
    return '<div class="cbc-nav-actions">' + (back ? '<button type="button" class="cbc-link-button" data-action="back">Back</button>' : '') + (nextLabel ? '<button type="button" class="cbc-primary-button" data-action="' + (submit ? 'quote' : 'next') + '">' + escapeHtml(nextLabel) + '</button>' : '') + '</div>';
  }

  function confirmationHtml() {
    var campus = selectedCampus();
    var refundHours = Number(state.config.refundCutoffHours || 72);
    var rescheduleHours = Number(state.config.rescheduleCutoffHours || refundHours);
    return '<section class="cbc-confirmation" aria-labelledby="cbc-confirmation-title"><span class="cbc-confirmation-icon" aria-hidden="true">&#10003;</span><p class="cbc-eyebrow">Invoice sent</p><h2 id="cbc-confirmation-title">Check your email to complete payment.</h2><p>Your request is protected while payment is pending. Care is confirmed only after full payment and required enrollment records are verified.</p><dl><div><dt>Request number</dt><dd>' + escapeHtml(state.requestIdConfirmed) + '</dd></div><div><dt>Campus</dt><dd>' + escapeHtml(campus ? campus.name : '') + '</dd></div><div><dt>Dates</dt><dd>' + escapeHtml(state.dates.map(function (item) { return item.value; }).join(', ')) + '</dd></div><div><dt>Children</dt><dd>' + escapeHtml(activeChildren().map(function (child) { return child.firstName; }).join(', ')) + '</dd></div></dl><div class="cbc-confirmation-actions">' + (campus && campus.directionsUrl ? '<a class="cbc-secondary-button" target="_blank" rel="noopener noreferrer" href="' + escapeHtml(campus.directionsUrl) + '">Directions</a>' : '') + '<a class="cbc-secondary-button" data-calendar-link download="chroma-backup-care.ics">Add to calendar</a><button type="button" class="cbc-secondary-button" data-action="print">Print confirmation</button></div><p class="cbc-record-warning">If enrollment or health records are incomplete, use the links in your reservation email before the first care date.</p><p>Use the secure management link in your email for changes. Refundable cancellation closes ' + refundHours + ' hours before each care date; rescheduling closes ' + rescheduleHours + ' hours before each care date.</p></section>';
  }

  function render() {
    if (!state.config) { root.innerHTML = '<p class="cbc-status">Loading secure booking...</p>'; return; }
    if (state.requestIdConfirmed) {
      root.innerHTML = confirmationHtml();
      window.ChromaBackupCareFlow.bind();
      return;
    }
    var content = state.step === 1 ? stepOne() : state.step === 2 ? stepTwo() : state.step === 3 ? stepThree() : stepFour();
    root.innerHTML = '<form class="cbc-form cbc-flow" data-cart-form novalidate>' + stepNav() + '<details class="cbc-mobile-summary"><summary>Reservation summary</summary>' + summaryHtml(true) + '</details><div class="cbc-flow-layout"><div class="cbc-flow-main">' + content + '<p class="cbc-status' + (state.messageError ? ' is-error' : '') + '" data-status aria-live="polite">' + escapeHtml(state.message) + '</p></div><aside class="cbc-flow-summary" aria-label="Reservation summary">' + summaryHtml(false) + '</aside></div></form>';
    window.ChromaBackupCareFlow.bind();
  }

  window.ChromaBackupCareFlow = {
    root: root,
    state: state,
    uid: uid,
    newChild: newChild,
    newCareDate: newCareDate,
    escapeHtml: escapeHtml,
    money: money,
    dateValue: dateValue,
    activeChildren: activeChildren,
    selectedCampus: selectedCampus,
    render: render,
    summaryHtml: summaryHtml,
    bind: function () {},
    setMessage: function (message, error) { state.message = message || ''; state.messageError = Boolean(error); var node = root.querySelector('[data-status]'); if (node) { node.textContent = state.message; node.classList.toggle('is-error', state.messageError); } }
  };
}());
