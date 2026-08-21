(function () {
  "use strict";

  var root = document.querySelector("[data-chroma-backup-care-manage]");
  if (!root || !window.ChromaBackupCareManage) {
    return;
  }

  var state = {
    token: new URLSearchParams(window.location.hash.slice(1)).get("token") || "",
    nonce: "",
    config: null,
    order: null,
    busy: false
  };

  if (state.token && window.history && window.history.replaceState) {
    window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
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

  function dateLimit(days) {
    var date = new Date();
    date.setDate(date.getDate() + days);
    return date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, "0") + "-" + String(date.getDate()).padStart(2, "0");
  }

  function render() {
    var confirmed = state.order.units.filter(function (unit) { return unit.status === "confirmed"; });
    var units = state.order.units.map(function (unit) {
      var active = unit.status === "confirmed";
      return '<article class="cbc-manage-unit" data-manage-unit="' + escapeHtml(unit.line_item_key) + '">' +
        '<div class="cbc-manage-summary"><div><p class="cbc-eyebrow">' + escapeHtml(unit.status) + '</p><h3>' + escapeHtml(unit.child_name) + '</h3><p>' + escapeHtml(unit.care_date) + ' at ' + escapeHtml(unit.planned_dropoff_local || "By 9:30 AM") + '</p></div>' +
        (active ? '<label class="cbc-child-choice"><input type="checkbox" data-cancel-unit><span>Cancel and refund this date</span></label>' : "") + '</div>' +
        (active ? '<form class="cbc-reschedule" data-reschedule-form><div class="cbc-date-controls"><label class="cbc-field"><span>New care date</span><input type="date" data-new-date min="' + dateLimit(0) + '" max="' + dateLimit(state.config.bookingHorizonDays) + '" required></label><label class="cbc-field"><span>New drop-off</span><input type="time" data-new-dropoff value="08:00" required></label></div><button type="submit" class="cbc-secondary-button">Reschedule this date</button></form>' : "") +
      '</article>';
    }).join("");

    root.innerHTML = '<div class="cbc-form"><header class="cbc-heading"><p class="cbc-eyebrow">Reservation ' + escapeHtml(state.order.request_id) + '</p><h2>Manage backup care</h2><p>' + money(state.order.amount_cents) + ' paid at the ' + escapeHtml(state.order.campus_id) + ' campus.</p></header>' +
      '<section class="cbc-section"><p>Refundable cancellation and rescheduling close 72 hours before each care date. No late exceptions apply.</p><div class="cbc-manage-list">' + units + '</div></section>' +
      (confirmed.length ? '<div class="cbc-actions"><button type="button" class="cbc-primary-button" data-cancel-selected>Cancel selected dates</button><p class="cbc-status" data-status aria-live="polite"></p></div>' : '<div class="cbc-actions"><p class="cbc-status">There are no active child-date units to change.</p></div>') +
      '</div>';

    root.querySelectorAll("[data-reschedule-form]").forEach(function (form) {
      form.addEventListener("submit", reschedule);
    });
    var cancelButton = root.querySelector("[data-cancel-selected]");
    if (cancelButton) {
      cancelButton.addEventListener("click", cancelSelected);
    }
  }

  async function loadOrder() {
    if (!state.token) {
      throw new Error("This management link is missing or invalid. Use the link in your confirmation email.");
    }
    state.order = await api(window.ChromaBackupCareManage.manageUrl, { manage_token: state.token });
    render();
  }

  async function cancelSelected() {
    if (state.busy) { return; }
    var keys = Array.prototype.map.call(root.querySelectorAll("[data-cancel-unit]:checked"), function (checkbox) {
      return checkbox.closest("[data-manage-unit]").getAttribute("data-manage-unit");
    });
    if (!keys.length) {
      setStatus("Select at least one date to cancel.", true);
      return;
    }
    if (!window.confirm("Cancel " + keys.length + " child-date unit(s) and request the eligible refund?")) {
      return;
    }
    setBusy(true, "Requesting the refund...");
    try {
      var result = await api(window.ChromaBackupCareManage.cancelUrl, {
        manage_token: state.token,
        line_item_keys: keys
      });
      await loadOrder();
      setStatus("Cancelled " + result.cancelled_unit_count + " child-date unit(s). Refund: " + money(result.refund_amount_cents) + ".", false);
    } catch (error) {
      setStatus(error.message, true);
    } finally {
      setBusy(false);
    }
  }

  async function reschedule(event) {
    event.preventDefault();
    if (state.busy || !event.target.reportValidity()) { return; }
    var unit = event.target.closest("[data-manage-unit]");
    setBusy(true, "Checking the new date...");
    try {
      var result = await api(window.ChromaBackupCareManage.rescheduleUrl, {
        manage_token: state.token,
        line_item_key: unit.getAttribute("data-manage-unit"),
        new_date: event.target.querySelector("[data-new-date]").value,
        new_dropoff: event.target.querySelector("[data-new-dropoff]").value
      });
      await loadOrder();
      setStatus("Rescheduled to " + result.new_date + " at " + result.new_dropoff + ".", false);
    } catch (error) {
      setStatus(error.message, true);
    } finally {
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
    if (!response.ok) {
      throw new Error(payload.message || (payload.data && payload.data.message) || "The request could not be completed.");
    }
    return payload;
  }

  function setBusy(busy, message) {
    state.busy = busy;
    root.querySelectorAll("button, input").forEach(function (control) {
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

  fetch(window.ChromaBackupCareManage.configUrl, { credentials: "same-origin" })
    .then(function (response) {
      if (!response.ok) { throw new Error("Configuration unavailable."); }
      return response.json();
    })
    .then(function (config) {
      state.config = config;
      state.nonce = config.nonce;
      return loadOrder();
    })
    .catch(function (error) {
      root.innerHTML = '<div class="cbc-error-summary" role="alert"><h3>Reservation unavailable</h3><p>' + escapeHtml(error.message) + '</p></div>';
    });
}());
