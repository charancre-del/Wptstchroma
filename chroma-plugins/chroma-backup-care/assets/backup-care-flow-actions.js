(function () {
  "use strict";

  var flow = window.ChromaBackupCareFlow;
  if (!flow || !window.ChromaBackupCare) { return; }
  var root = flow.root;
  var state = flow.state;

  function track(name, values) {
    var event = Object.assign({ event: name }, values || {});
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(event);
  }

  function api(url, body) {
    return fetch(url, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", "X-Chroma-Backup-Care-Nonce": state.nonce },
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok) {
          throw new Error(payload.message || (payload.data && payload.data.message) || "The request could not be completed.");
        }
        return payload;
      });
    });
  }

  function sync() {
    var node = root.querySelector('[data-field="parent-first"]');
    if (node) {
      state.parent.firstName = node.value.trim();
      state.parent.lastName = root.querySelector('[data-field="parent-last"]').value.trim();
      state.parent.email = root.querySelector('[data-field="parent-email"]').value.trim();
      state.parent.phone = root.querySelector('[data-field="parent-phone"]').value.trim();
    }
    node = root.querySelector('[data-field="campus"]');
    if (node) { state.campusId = node.value; }
    root.querySelectorAll('[data-date-id]').forEach(function (fieldset) {
      var careDate = state.dates.find(function (item) { return item.id === fieldset.getAttribute('data-date-id'); });
      if (!careDate) { return; }
      careDate.value = fieldset.querySelector('[data-field="care-date"]').value;
      careDate.dropoff = fieldset.querySelector('[data-field="dropoff"]').value;
    });
    root.querySelectorAll('[data-child-id]').forEach(function (fieldset) {
      var child = state.children.find(function (item) { return item.id === fieldset.getAttribute('data-child-id'); });
      if (!child) { return; }
      child.firstName = fieldset.querySelector('[data-field="child-first"]').value.trim();
      child.lastName = fieldset.querySelector('[data-field="child-last"]').value.trim();
      child.birthDate = fieldset.querySelector('[data-field="child-birth"]').value;
      child.ageGroup = fieldset.querySelector('[data-field="child-age"]').value;
    });
    root.querySelectorAll('[data-attendance-date]').forEach(function (fieldset) {
      var careDate = state.dates.find(function (item) { return item.id === fieldset.getAttribute('data-attendance-date'); });
      if (!careDate) { return; }
      careDate.childIds = {};
      fieldset.querySelectorAll('[data-date-child]').forEach(function (checkbox) { careDate.childIds[checkbox.getAttribute('data-date-child')] = checkbox.checked; });
    });
    root.querySelectorAll('[data-policy]').forEach(function (checkbox) { state.policies[checkbox.getAttribute('data-policy')] = checkbox.checked; });
  }

  function invalidateQuote() {
    state.quote = null;
    state.quoteToken = "";
    state.quoteOrder = null;
  }

  function firstInvalid(container) {
    var invalid = container.querySelector(':invalid');
    if (invalid) {
      invalid.focus();
      invalid.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
      invalid.reportValidity();
      return true;
    }
    return false;
  }

  function validateStep() {
    var panel = root.querySelector('.cbc-panel');
    if (!panel || firstInvalid(panel)) { return false; }
    if (state.step === 1 && (!state.parentAccessToken || state.parent.email.toLowerCase() !== state.verificationEmail)) {
      flow.setMessage('Verify the parent email before continuing.', true);
      var button = root.querySelector('[data-action="send-code"], [data-verification-code]');
      if (button) { button.focus(); }
      return false;
    }
    if (state.step === 2) {
      var weekends = state.dates.some(function (item) { var date = new Date(item.value + 'T12:00:00'); return date.getDay() === 0 || date.getDay() === 6; });
      if (weekends) { flow.setMessage('Backup Care dates must be weekdays.', true); return false; }
    }
    if (state.step === 3) {
      var hasAttendance = state.dates.some(function (date) { return state.children.some(function (child) { return date.childIds[child.id] !== false; }); });
      if (!hasAttendance) { flow.setMessage('Select at least one child for a care date.', true); return false; }
    }
    flow.setMessage('', false);
    return true;
  }

  function setBusy(busy, message) {
    state.busy = busy;
    root.setAttribute('aria-busy', busy ? 'true' : 'false');
    root.querySelectorAll('button, input, select').forEach(function (control) {
      if (busy) {
        control.setAttribute('data-cbc-was-disabled', control.disabled ? '1' : '0');
        control.disabled = true;
      } else if (control.hasAttribute('data-cbc-was-disabled')) {
        control.disabled = control.getAttribute('data-cbc-was-disabled') === '1';
        control.removeAttribute('data-cbc-was-disabled');
      }
    });
    if (message) { flow.setMessage(message, false); }
  }

  function go(step) {
    sync();
    state.step = Math.max(1, Math.min(4, step));
    state.completedStep = Math.max(state.completedStep, state.step - 1);
    flow.render();
    var heading = root.querySelector('.cbc-panel h2');
    if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus(); }
    track('backup_care_step_view', { step: state.step });
  }

  function requestCode() {
    if (state.busy) { return; }
    sync();
    if (firstInvalid(root.querySelector('.cbc-panel'))) { return; }
    setBusy(true, 'Sending a verification code...');
    api(window.ChromaBackupCare.requestAccessUrl, { email: state.parent.email }).then(function (response) {
      state.verificationChallengeId = response.challenge_id;
      state.verificationEmail = state.parent.email.toLowerCase();
      state.parentAccessToken = '';
      state.message = 'Verification code sent. Check your email.';
      state.messageError = false;
      setBusy(false);
      flow.render();
      var code = root.querySelector('[data-verification-code]');
      if (code) { code.focus(); }
    }).catch(function (error) { setBusy(false); flow.setMessage(error.message || 'The code could not be sent.', true); });
  }

  function verifyCode() {
    if (state.busy) { return; }
    sync();
    var input = root.querySelector('[data-verification-code]');
    if (!input || !input.reportValidity()) { return; }
    setBusy(true, 'Verifying your email...');
    api(window.ChromaBackupCare.verifyAccessUrl, { challenge_id: state.verificationChallengeId, email: state.parent.email, code: input.value.trim() }).then(function (response) {
      state.parentAccessToken = response.parent_access_token;
      state.verificationEmail = state.parent.email.toLowerCase();
      state.verificationChallengeId = '';
      state.completedStep = Math.max(state.completedStep, 1);
      return api(window.ChromaBackupCare.profilesUrl, { email: state.parent.email, parent_access_token: state.parentAccessToken }).then(function (profilesResponse) {
        state.profiles = Array.isArray(profilesResponse.profiles) ? profilesResponse.profiles : [];
        state.message = state.profiles.length ? 'Email verified. Saved child profiles are ready to use.' : 'Email verified. Continue to choose care.';
      }).catch(function () {
        state.profiles = [];
        state.message = 'Email verified. Saved profiles are temporarily unavailable; you can add child details manually.';
      });
    }).then(function () {
      state.messageError = false;
      setBusy(false);
      flow.render();
      track('backup_care_email_verified', { saved_profile_count: state.profiles.length });
    }).catch(function (error) {
      state.parentAccessToken = '';
      state.verificationEmail = '';
      setBusy(false);
      flow.setMessage(error.message || 'The verification code is invalid or expired.', true);
    });
  }

  function collectOrder() {
    sync();
    var children = flow.activeChildren();
    var attendance = [];
    state.dates.forEach(function (careDate) {
      children.forEach(function (child) {
        if (careDate.childIds[child.id] !== false) {
          attendance.push({ client_child_id: child.id, care_date: careDate.value, planned_dropoff_local: careDate.dropoff });
        }
      });
    });
    return {
      contract_version: 1,
      parent_access_token: state.parentAccessToken,
      client_request_id: state.requestId,
      campus_id: state.campusId,
      parent: { first_name: state.parent.firstName, last_name: state.parent.lastName, email: state.parent.email, mobile_phone: state.parent.phone },
      children: children.map(function (child) { return { client_child_id: child.id, first_name: child.firstName, last_name: child.lastName, date_of_birth: child.birthDate, age_group: child.ageGroup }; }),
      attendance: attendance,
      policy_acceptance: Object.assign({}, state.policies)
    };
  }

  function renderQuoteErrors(response) {
    var output = root.querySelector('[data-quote-output]');
    var enrollment = '';
    if (response.enrollment_required && response.child_enrollment_form_url) {
      enrollment = '<div class="cbc-enrollment"><h3>Enrollment records are required before care</h3>' + (response.family_profile_form_url ? '<p><a class="cbc-secondary-button" target="_blank" rel="noopener noreferrer" href="' + flow.escapeHtml(response.family_profile_form_url) + '">Complete family profile</a></p>' : '') + response.enrollment_required.map(function (item) {
        var child = state.children.find(function (candidate) { return candidate.id === item.client_child_id; });
        var separator = response.child_enrollment_form_url.indexOf('?') === -1 ? '?' : '&';
        return '<p><a class="cbc-secondary-button" target="_blank" rel="noopener noreferrer" href="' + flow.escapeHtml(response.child_enrollment_form_url + separator + 'child_record_key=' + encodeURIComponent(item.child_record_key)) + '">Complete records for ' + flow.escapeHtml(child ? child.firstName : 'child') + '</a></p>';
      }).join('') + '</div>';
    }
    output.innerHTML = '<div class="cbc-error-summary" role="alert" tabindex="-1"><h3>We could not prepare payment</h3><ul>' + (response.errors || ['Please review the reservation details.']).map(function (message) { return '<li>' + flow.escapeHtml(message) + '</li>'; }).join('') + '</ul></div>' + enrollment;
    var summary = output.querySelector('.cbc-error-summary');
    if (summary) { summary.focus(); }
    flow.setMessage('No invoice or charge was created.', true);
  }

  function quote() {
    if (state.busy) { return; }
    sync();
    if (!validateStep()) { return; }
    var order = collectOrder();
    if (!order.attendance.length) { flow.setMessage('Select at least one child for a care date.', true); return; }
    setBusy(true, 'Checking dates, records, and availability...');
    api(window.ChromaBackupCare.quoteUrl, { order: order }).then(function (response) {
      if (!response.contract_valid) { setBusy(false); renderQuoteErrors(response); return; }
      state.quote = response.quote;
      state.quoteToken = response.quote_token;
      state.quoteOrder = order;
      state.message = 'Total verified. No invoice or charge has been created yet.';
      state.messageError = false;
      setBusy(false);
      flow.render();
      track('backup_care_quote_ready', { child_date_units: state.quote.unit_count });
    }).catch(function (error) { setBusy(false); flow.setMessage(error.message || 'Booking is temporarily unavailable.', true); });
  }

  function checkout() {
    if (state.busy || !state.quoteOrder || !state.quoteToken) { return; }
    setBusy(true, 'Creating one secure invoice for this reservation...');
    api(window.ChromaBackupCare.checkoutUrl, { order: state.quoteOrder, quote_token: state.quoteToken }).then(function (response) {
      if (!response.request_id || !response.invoice_id || response.payment_delivery !== 'ghl_invoice_email') { throw new Error('Secure invoice delivery was not confirmed.'); }
      state.requestIdConfirmed = response.request_id;
      state.invoiceId = response.invoice_id;
      state.message = '';
      state.messageError = false;
      setBusy(false);
      flow.render();
      attachCalendar();
      track('backup_care_invoice_sent', { child_date_units: state.quote.unit_count });
      pollPayment(0);
    }).catch(function (error) { setBusy(false); flow.setMessage(error.message || 'The invoice could not be created. No charge was made.', true); });
  }

  function pollPayment(attempt) {
    if (!state.requestIdConfirmed || attempt > 20) { return; }
    window.setTimeout(function () {
      api(window.ChromaBackupCare.paymentStatusUrl, { request_id: state.requestIdConfirmed, email: state.parent.email, parent_access_token: state.parentAccessToken }).then(function (response) {
        if (response.status === 'paid' || response.status === 'fulfilled') {
          track('backup_care_payment_status', { status: response.status });
          var heading = root.querySelector('.cbc-confirmation h2');
          if (heading) { heading.textContent = 'Payment received. Your reservation is being finalized.'; }
          return;
        }
        pollPayment(attempt + 1);
      }).catch(function () { pollPayment(attempt + 1); });
    }, attempt === 0 ? 5000 : 15000);
  }

  function calendarUrl() {
    var campus = flow.selectedCampus();
    var events = state.dates.map(function (careDate) {
      var date = careDate.value.replace(/-/g, '');
      return ['BEGIN:VEVENT', 'UID:' + state.requestIdConfirmed + '-' + date + '@chromaela.com', 'DTSTART;VALUE=DATE:' + date, 'DTEND;VALUE=DATE:' + date, 'SUMMARY:Chroma Backup Care', 'LOCATION:' + (campus ? campus.name + ', ' + campus.address : ''), 'DESCRIPTION:Backup Care request ' + state.requestIdConfirmed + '. Check your email for payment and enrollment instructions.', 'END:VEVENT'].join('\r\n');
    }).join('\r\n');
    return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Chroma Early Learning//Backup Care//EN', events, 'END:VCALENDAR'].join('\r\n'));
  }

  function attachCalendar() {
    var link = root.querySelector('[data-calendar-link]');
    if (link) { link.setAttribute('href', calendarUrl()); }
  }

  function handleAction(button) {
    var action = button.getAttribute('data-action');
    if (action === 'send-code') { requestCode(); return; }
    if (action === 'verify-code') { verifyCode(); return; }
    if (action === 'reset-email') {
      sync(); state.parentAccessToken = ''; state.verificationEmail = ''; state.verificationChallengeId = ''; state.profiles = []; state.completedStep = 0; invalidateQuote(); flow.render(); return;
    }
    if (action === 'add-date') {
      sync(); if (state.dates.length >= Number(state.config.maxCareDatesPerOrder || 31)) { flow.setMessage('This reservation can include up to ' + state.config.maxCareDatesPerOrder + ' care dates.', true); return; }
      state.dates.push(flow.newCareDate()); invalidateQuote(); flow.render(); return;
    }
    if (action === 'add-child') { sync(); state.children.push(flow.newChild()); invalidateQuote(); flow.render(); return; }
    if (action === 'next') { sync(); if (validateStep()) { go(state.step + 1); } return; }
    if (action === 'back') { go(state.step - 1); return; }
    if (action === 'quote') { quote(); return; }
    if (action === 'checkout') { checkout(); return; }
    if (action === 'print') { window.print(); }
  }

  flow.bind = function () {
    attachCalendar();
    root.querySelectorAll('[data-action]').forEach(function (button) { button.addEventListener('click', function () { handleAction(button); }); });
    root.querySelectorAll('[data-go-step]').forEach(function (button) { button.addEventListener('click', function () { if (!button.disabled) { go(Number(button.getAttribute('data-go-step'))); } }); });
    root.querySelectorAll('[data-remove-date]').forEach(function (button) { button.addEventListener('click', function () { sync(); state.dates = state.dates.filter(function (item) { return item.id !== button.getAttribute('data-remove-date'); }); invalidateQuote(); flow.render(); }); });
    root.querySelectorAll('[data-remove-child]').forEach(function (button) { button.addEventListener('click', function () { sync(); var id = button.getAttribute('data-remove-child'); state.children = state.children.filter(function (item) { return item.id !== id; }); state.dates.forEach(function (date) { delete date.childIds[id]; }); invalidateQuote(); flow.render(); }); });
    root.querySelectorAll('[data-profile-id]').forEach(function (button) { button.addEventListener('click', function () { var profile = state.profiles.find(function (item) { return String(item.id) === button.getAttribute('data-profile-id'); }); if (profile) { var blank = state.children.length === 1 && !state.children[0].firstName && !state.children[0].lastName; if (blank) { state.children = []; } state.children.push(flow.newChild(profile)); invalidateQuote(); flow.render(); } }); });
    if (!root.hasAttribute('data-cbc-delegated')) {
      root.setAttribute('data-cbc-delegated', '1');
      root.addEventListener('input', function (event) {
        if (event.target.matches('[data-field="parent-email"]') && event.target.value.trim().toLowerCase() !== state.verificationEmail) { state.parentAccessToken = ''; state.verificationChallengeId = ''; state.profiles = []; }
        if (!event.target.matches('[data-policy]')) { invalidateQuote(); }
      });
      root.addEventListener('change', function (event) {
        sync();
        if (!event.target.matches('[data-policy]')) { invalidateQuote(); }
      });
      root.addEventListener('submit', function (event) {
        if (event.target.matches('[data-cart-form]')) { event.preventDefault(); }
      });
    }
  };

  fetch(window.ChromaBackupCare.configUrl, { credentials: 'same-origin' }).then(function (response) {
    if (!response.ok) { throw new Error('Configuration unavailable'); }
    return response.json();
  }).then(function (config) {
    state.config = config;
    state.nonce = config.nonce;
    flow.render();
    track('backup_care_step_view', { step: 1 });
  }).catch(function () {
    root.innerHTML = '<p class="cbc-status is-error">Backup Care booking is temporarily unavailable. Please contact info@chromaela.com.</p>';
  });
}());
