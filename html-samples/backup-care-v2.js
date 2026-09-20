(function () {
  "use strict";

  var campuses = [
    { id: "chadwick", name: "Chadwick Campus", city: "Lawrenceville", address: "1479 Purcell Rd, Lawrenceville, GA 30043", zip: "30043", lat: 34.0036, lng: -84.0095, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/chroma-early-learning-academy-chadwick/", bookingUrl: "" },
    { id: "cherokee", name: "Cherokee Academy by Chroma", city: "Canton", address: "1205 Upper Burris Road, Canton, GA 30114", zip: "30114", lat: 34.3468408, lng: -84.4919414, hours: "6:30am - 6:00pm", url: "https://chromaela.com/locations/cherokee-campus/", bookingUrl: "" },
    { id: "tramore", name: "Tramore Campus", city: "Austell", address: "2081 Mesa Valley Way, Austell, GA 30106", zip: "30106", lat: 33.8540032, lng: -84.6151744, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/tramore-campus-austell/", bookingUrl: "" },
    { id: "downtown-duluth", name: "Downtown Duluth Campus", city: "Duluth", address: "3152 Creek Dr NW, Duluth, GA 30096", zip: "30096", lat: 33.9849116, lng: -84.1527615, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/pleasanthill-campus-duluth/", bookingUrl: "" },
    { id: "east-cobb", name: "East Cobb Campus", city: "Marietta", address: "2499 Shallowford Road NE, Marietta, GA 30066", zip: "30066", lat: 34.0419908, lng: -84.4790683, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/east-cobb-campus/", bookingUrl: "" },
    { id: "ellenwood", name: "Ellenwood Campus", city: "Ellenwood", address: "2765 E Atlanta Rd, Ellenwood, GA 30294", zip: "30294", lat: 33.6152419, lng: -84.2444789, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/ellenwood-campus/", bookingUrl: "" },
    { id: "grayson", name: "Grayson Campus", city: "Grayson", address: "550 Grayson Pkwy, Grayson, GA 30017", zip: "30017", lat: 33.8916793, lng: -83.9608666, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/chroma-early-learning-academy-grayson/", bookingUrl: "" },
    { id: "johns-creek", name: "Johns Creek Campus", city: "Johns Creek", address: "3580 Old Alabama Rd, Johns Creek, GA 30022", zip: "30022", lat: 34.0246511, lng: -84.2575523, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/johns-creek/", bookingUrl: "" },
    { id: "jonesboro", name: "Jonesboro Campus", city: "Jonesboro", address: "1832 Noahs Ark Road, Jonesboro, GA 30236", zip: "30236", lat: 33.4938883, lng: -84.3298115, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/jonesboro-campus/", bookingUrl: "" },
    { id: "lawrenceville", name: "Lawrenceville Campus", city: "Lawrenceville", address: "3650 Club Drive, Lawrenceville, GA 30044", zip: "30044", lat: 33.9410279, lng: -84.1247219, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/lawrenceville-campus/", bookingUrl: "" },
    { id: "lilburn", name: "Lilburn Campus", city: "Lilburn", address: "917 Killian Hill Road Southwest, Lilburn, GA 30047", zip: "30047", lat: 33.8686888, lng: -84.0968565, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/lilburn-campus/", bookingUrl: "" },
    { id: "mcdonough", name: "McDonough Campus", city: "McDonough", address: "90 Hunters Chase, McDonough, GA 30253", zip: "30253", lat: 33.3944376, lng: -84.175741, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/mcdonough/", bookingUrl: "" },
    { id: "midway", name: "Midway Campus", city: "Alpharetta", address: "4015 Discovery Dr, Alpharetta, GA 30004", zip: "30004", lat: 34.1556591, lng: -84.2448638, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/midway-campus/", bookingUrl: "" },
    { id: "north-hall", name: "North Hall Campus", city: "Murrayville", address: "5760 Wade Whelchel Road, Murrayville, GA 30564", zip: "30564", lat: 34.4228388, lng: -83.8835539, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/north-hall-campus-murraysville/", bookingUrl: "" },
    { id: "parklake", name: "Parklake Campus", city: "Atlanta", address: "2309 Parklake Dr NE #250, Atlanta, GA 30345", zip: "30345", lat: 33.8491, lng: -84.271, hours: "6:45am - 6:15pm", url: "https://chromaela.com/locations/parklake-campus/", bookingUrl: "" },
    { id: "rivergreen", name: "Rivergreen Campus", city: "Canton", address: "200 River Green Avenue, Canton, GA 30114", zip: "30114", lat: 34.2145313, lng: -84.5262186, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/rivergreen-campus/", bookingUrl: "" },
    { id: "roswell", name: "Roswell Campus", city: "Roswell", address: "1255 Upper Hembree Rd, Roswell, GA 30076", zip: "30076", lat: 34.0668783, lng: -84.3226899, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/roswell-campus/", bookingUrl: "" },
    { id: "satellite", name: "Satellite Boulevard Campus", city: "Duluth", address: "3730 Satellite Blvd, Duluth, GA 30096", zip: "30096", lat: 33.9563258, lng: -84.1381592, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/satellite-bvd-campus/", bookingUrl: "" },
    { id: "shenandoah", name: "Shenandoah Campus", city: "Newnan", address: "40 Bledsoe Rd, Newnan, GA 30265", zip: "30265", lat: 33.39936, lng: -84.750983, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/newnan/", bookingUrl: "" },
    { id: "south-cobb", name: "South Cobb Campus", city: "Austell", address: "7225 Premier Lane, Austell, GA 30168", zip: "30168", lat: 33.7778694, lng: -84.5651223, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/south-cobb-campus-austell/", bookingUrl: "" },
    { id: "stockbridge", name: "Stockbridge Campus", city: "Stockbridge", address: "300 Inverness Ave, McDonough, GA 30253", zip: "30253", lat: 33.501903, lng: -84.183998, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/chroma-early-learning-academy-stockbridge/", bookingUrl: "" },
    { id: "sugarloaf", name: "Sugarloaf Parkway Campus", city: "Lawrenceville", address: "3155 Sugarloaf Pkwy, Lawrenceville, GA 30045", zip: "30045", lat: 33.9211835, lng: -84.0093449, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/chroma-early-learning-academy-sugarloaf-pkwy/", bookingUrl: "" },
    { id: "tyrone", name: "Tyrone Campus", city: "Tyrone", address: "291 Jenkins Road, Tyrone, GA 30290", zip: "30290", lat: 33.4957283, lng: -84.56748, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/tyrone-campus/", bookingUrl: "" },
    { id: "west-cobb", name: "West Cobb Campus", city: "Marietta", address: "2424 Powder Springs Rd SW, Marietta, GA 30064", zip: "30064", lat: 33.8737104, lng: -84.6265554, hours: "6:30am - 6:30pm", url: "https://chromaela.com/locations/west-cobb-campus/", bookingUrl: "" }
  ];

  var ageLabels = {
    infant: "Infant",
    toddler: "Toddler",
    preschool: "Preschool",
    school: "School age"
  };

  var ghlConfig = window.CHROMA_BACKUP_CARE_GHL || {
    bookingHorizonDays: 365,
    embedBaseUrl: "https://api.leadconnectorhq.com/widget/booking/",
    embedScriptUrl: "https://link.msgsndr.com/js/form_embed.js",
    allowedHosts: ["api.leadconnectorhq.com"],
    campuses: {}
  };

  var lastFocusedElement = null;

  function formatDate(value) {
    if (!value) {
      return "Date selected at checkout";
    }
    var date = new Date(value + "T12:00:00");
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return new Intl.DateTimeFormat("en-US", {
      weekday: "short",
      month: "short",
      day: "numeric"
    }).format(date);
  }

  function tomorrowValue() {
    var date = new Date();
    date.setDate(date.getDate() + 1);
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  function todayValue() {
    var date = new Date();
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  function horizonValue(days) {
    var date = new Date();
    date.setDate(date.getDate() + days);
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, "0");
    var day = String(date.getDate()).padStart(2, "0");
    return year + "-" + month + "-" + day;
  }

  function setDefaultDates() {
    var bookingHorizonDays = Number(ghlConfig.bookingHorizonDays) || 365;
    document.querySelectorAll('input[type="date"]').forEach(function (input) {
      input.min = todayValue();
      input.max = horizonValue(bookingHorizonDays);
      if (!input.value) {
        input.value = tomorrowValue();
      }
    });
  }

  function setupNavigation() {
    var toggle = document.querySelector("[data-menu-toggle]");
    var menu = document.querySelector("[data-mobile-nav]");
    if (!toggle || !menu) {
      return;
    }

    toggle.addEventListener("click", function () {
      var isOpen = menu.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(isOpen));
      toggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
      toggle.querySelector("i").className = isOpen ? "fa-solid fa-xmark" : "fa-solid fa-bars";
    });
  }

  function setupAgeOptions(scope) {
    var hiddenInput = scope.querySelector("[data-age-value]");
    var options = scope.querySelectorAll("[data-age]");

    options.forEach(function (option) {
      option.addEventListener("click", function () {
        options.forEach(function (button) {
          button.classList.remove("is-selected");
          button.setAttribute("aria-pressed", "false");
        });
        option.classList.add("is-selected");
        option.setAttribute("aria-pressed", "true");
        if (hiddenInput) {
          hiddenInput.value = option.dataset.age;
        }
      });
    });
  }

  function selectAge(scope, age) {
    var hiddenInput = scope.querySelector("[data-age-value]");
    var option = scope.querySelector('[data-age="' + age + '"]');
    if (!option) {
      return;
    }
    scope.querySelectorAll("[data-age]").forEach(function (button) {
      var selected = button === option;
      button.classList.toggle("is-selected", selected);
      button.setAttribute("aria-pressed", String(selected));
    });
    if (hiddenInput) {
      hiddenInput.value = age;
    }
  }

  function campusCard(campus, date, age) {
    var article = document.createElement("article");
    article.className = "campus-result";

    var information = document.createElement("div");
    var title = document.createElement("h4");
    title.textContent = campus.name;
    var address = document.createElement("p");
    address.textContent = campus.address + " | " + campus.hours;
    var meta = document.createElement("div");
    meta.className = "campus-result__meta";

    var capacity = document.createElement("span");
    capacity.className = "capacity-note";
    capacity.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>';
    capacity.appendChild(document.createTextNode("Care date confirmed after payment"));
    meta.appendChild(capacity);

    information.appendChild(title);
    information.appendChild(address);
    information.appendChild(meta);

    var actions = document.createElement("div");
    actions.className = "campus-result__actions";
    var booking = document.createElement("button");
    booking.type = "button";
    booking.className = "button button--primary";
    booking.dataset.bookCampus = campus.id;
    booking.dataset.bookingDate = date || "";
    booking.dataset.bookingAge = age || "";
    booking.innerHTML = '<i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Check and book';
    var campusLink = document.createElement("a");
    campusLink.className = "text-link";
    campusLink.href = campus.url;
    campusLink.target = "_blank";
    campusLink.rel = "noopener";
    campusLink.textContent = "View campus";
    actions.appendChild(booking);
    actions.appendChild(campusLink);

    article.appendChild(information);
    article.appendChild(actions);
    return article;
  }

  function populateCampusSelect(select) {
    if (!select || select.options.length > 1) {
      return;
    }
    campuses.slice().sort(function (a, b) {
      return a.name.localeCompare(b.name);
    }).forEach(function (campus) {
      var option = document.createElement("option");
      option.value = campus.id;
      option.textContent = campus.name + " - " + campus.city;
      select.appendChild(option);
    });
  }

  function renderCampus(finder, campusId) {
    var list = finder.querySelector("[data-campus-list]");
    var heading = finder.querySelector("[data-results-heading]");
    var count = finder.querySelector("[data-results-count]");
    var dateInput = finder.querySelector('[name="care-date"]');
    var ageInput = finder.querySelector("[data-age-value]");
    var date = dateInput ? dateInput.value : "";
    var age = ageInput ? ageInput.value : "";
    list.replaceChildren();
    var selectedCampus = campuses.find(function (campus) { return campus.id === campusId; });
    if (!selectedCampus) {
      heading.textContent = "Choose a campus to continue";
      count.textContent = "24 campus choices";
      return;
    }
    list.appendChild(campusCard(selectedCampus, date, age));
    heading.textContent = "Your selected campus";
    count.textContent = selectedCampus.city;
  }

  function setupFullFinder(finder) {
    var campusInput = finder.querySelector('[name="campus"]');
    var status = finder.querySelector("[data-form-status]");
    var params = new URLSearchParams(window.location.search);
    var storedSearch = null;

    try {
      storedSearch = JSON.parse(window.sessionStorage.getItem("chromaBackupCareSearch") || "null");
      window.sessionStorage.removeItem("chromaBackupCareSearch");
    } catch (error) {
      storedSearch = null;
    }

    setupAgeOptions(finder);
    populateCampusSelect(campusInput);
    renderCampus(finder, "");

    if (storedSearch && storedSearch.campus) {
      campusInput.value = String(storedSearch.campus);
    } else if (params.get("campus")) {
      campusInput.value = params.get("campus");
    }
    if (storedSearch && storedSearch.age) {
      selectAge(finder, String(storedSearch.age));
    } else if (params.get("age")) {
      selectAge(finder, params.get("age"));
    }
    if (params.get("date")) {
      var dateInput = finder.querySelector('[name="care-date"]');
      dateInput.value = params.get("date");
    }

    finder.addEventListener("submit", function (event) {
      event.preventDefault();
      var campusId = campusInput.value;
      var age = finder.querySelector("[data-age-value]").value;
      var dateInput = finder.querySelector('[name="care-date"]');
      if (!campusId) {
        status.textContent = "Choose a Chroma campus.";
        status.setAttribute("role", "alert");
        campusInput.focus();
        return;
      }
      if (!dateInput.value) {
        status.textContent = "Choose a care date.";
        status.setAttribute("role", "alert");
        dateInput.focus();
        return;
      }
      if (!age) {
        status.textContent = "Choose your child's age group.";
        status.setAttribute("role", "alert");
        finder.querySelector("[data-age]").focus();
        return;
      }

      status.removeAttribute("role");
      status.textContent = "Campus selected. Review the details and continue to booking.";
      renderCampus(finder, campusId);
    });

    if (campusInput.value && finder.querySelector("[data-age-value]").value) {
      finder.requestSubmit();
    }
  }

  function setupCompactFinder(form) {
    var campusInput = form.querySelector('[name="campus"]');
    populateCampusSelect(campusInput);
    form.addEventListener("submit", function (event) {
      event.preventDefault();
      var campusId = campusInput.value;
      var age = form.querySelector('[name="age"]').value;
      if (!campusId) {
        campusInput.focus();
        return;
      }
      var destination = new URL("backup-care-v2.html", window.location.href);
      try {
        window.sessionStorage.setItem("chromaBackupCareSearch", JSON.stringify({
          campus: campusId,
          age: age
        }));
      } catch (error) {
        destination.searchParams.set("campus", campusId);
        if (age) {
          destination.searchParams.set("age", age);
        }
      }
      window.location.href = destination.toString();
    });
  }

  function isAllowedGhlUrl(value) {
    try {
      var url = new URL(value);
      var allowedHosts = Array.isArray(ghlConfig.allowedHosts) ? ghlConfig.allowedHosts : [];
      return url.protocol === "https:" && allowedHosts.some(function (host) {
        return url.hostname === host || url.hostname.endsWith("." + host);
      });
    } catch (error) {
      return false;
    }
  }

  function resolveGhlRoute(campusId, age) {
    var campusRoutes = ghlConfig.campuses && ghlConfig.campuses[campusId];
    if (!campusRoutes) {
      return null;
    }
    var routeValue = campusRoutes[age] || campusRoutes.default || "";
    var routeUrl = "";
    var calendarId = "";

    if (routeValue && typeof routeValue === "object") {
      routeUrl = String(routeValue.url || "").trim();
      calendarId = String(routeValue.calendarId || "").trim();
    } else {
      routeValue = String(routeValue || "").trim();
      if (/^https:\/\//i.test(routeValue)) {
        routeUrl = routeValue;
      } else {
        calendarId = routeValue;
      }
    }

    if (calendarId && /^[A-Za-z0-9_-]{6,120}$/.test(calendarId)) {
      routeUrl = String(ghlConfig.embedBaseUrl || "") + encodeURIComponent(calendarId);
    }
    if (!routeUrl || !isAllowedGhlUrl(routeUrl)) {
      return null;
    }

    if (!calendarId) {
      try {
        var routeParts = new URL(routeUrl).pathname.split("/").filter(Boolean);
        calendarId = routeParts[routeParts.length - 1] || "";
      } catch (error) {
        calendarId = "";
      }
    }

    return {
      calendarId: calendarId || campusId + "-" + (age || "default"),
      url: routeUrl
    };
  }

  function ensureGhlEmbedScript() {
    if (document.querySelector('script[data-chroma-ghl-calendar-script], script[src*="link.msgsndr.com/js/form_embed.js"]')) {
      return;
    }
    var scriptUrl = String(ghlConfig.embedScriptUrl || "");
    if (!isAllowedGhlUrl(scriptUrl)) {
      return;
    }
    var script = document.createElement("script");
    script.src = scriptUrl;
    script.async = true;
    script.dataset.chromaGhlCalendarScript = "true";
    document.body.appendChild(script);
  }

  function mountGhlCalendar(modal, route, campus, age) {
    var host = modal.querySelector("[data-ghl-frame-host]");
    var setupState = modal.querySelector("[data-ghl-setup-state]");
    if (!host || !setupState) {
      return;
    }

    host.replaceChildren();
    host.hidden = false;
    setupState.hidden = true;
    var safeId = route.calendarId.replace(/[^A-Za-z0-9_-]/g, "-");
    var iframe = document.createElement("iframe");
    iframe.src = route.url;
    iframe.id = "inline-backup-care-" + safeId;
    iframe.title = campus.name + " backup care booking - " + (ageLabels[age] || "all ages");
    iframe.loading = "eager";
    iframe.scrolling = "yes";
    iframe.setAttribute("data-layout", '{"id":"INLINE"}');
    iframe.setAttribute("data-trigger-type", "alwaysShow");
    iframe.setAttribute("data-trigger-value", "");
    iframe.setAttribute("data-activation-type", "alwaysActivated");
    iframe.setAttribute("data-activation-value", "");
    iframe.setAttribute("data-deactivation-type", "neverDeactivate");
    iframe.setAttribute("data-deactivation-value", "");
    iframe.setAttribute("data-calendar-id", route.calendarId);
    host.appendChild(iframe);
    ensureGhlEmbedScript();
  }

  function openBookingModal(campus, date, age) {
    var modal = document.querySelector("[data-booking-modal]");
    if (!modal) {
      return;
    }
    lastFocusedElement = document.activeElement;
    modal.querySelector("[data-modal-campus]").textContent = campus.name;
    modal.querySelector("[data-modal-date]").textContent = formatDate(date);
    modal.querySelector("[data-modal-age]").textContent = ageLabels[age] || "Selected in checkout";
    var heading = modal.querySelector("#booking-modal-title");
    var host = modal.querySelector("[data-ghl-frame-host]");
    var setupState = modal.querySelector("[data-ghl-setup-state]");
    var checkout = modal.querySelector("[data-checkout-link]");

    heading.textContent = "Review your care request";
    modal.classList.remove("is-ghl-ready");
    if (host) {
      host.replaceChildren();
      host.hidden = true;
    }
    if (setupState) {
      setupState.hidden = false;
    }
    checkout.removeAttribute("href");
    checkout.removeAttribute("target");
    checkout.removeAttribute("rel");
    checkout.setAttribute("aria-disabled", "true");
    checkout.textContent = "Checkout disabled in preview";
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("is-locked");
    modal.querySelector("[data-modal-close]").focus();
  }

  function closeBookingModal() {
    var modal = document.querySelector("[data-booking-modal]");
    if (!modal) {
      return;
    }
    modal.classList.remove("is-open");
    modal.classList.remove("is-ghl-ready");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("is-locked");
    var host = modal.querySelector("[data-ghl-frame-host]");
    if (host) {
      host.replaceChildren();
      host.hidden = true;
    }
    if (lastFocusedElement) {
      lastFocusedElement.focus();
    }
  }

  function setupBookingModal() {
    document.addEventListener("click", function (event) {
      var bookingButton = event.target.closest("[data-book-campus]");
      if (bookingButton) {
        var campus = campuses.find(function (item) { return item.id === bookingButton.dataset.bookCampus; });
        if (campus) {
          var date = bookingButton.dataset.bookingDate || document.querySelector('[name="care-date"]')?.value || "";
          var age = bookingButton.dataset.bookingAge || document.querySelector("[data-age-value]")?.value || document.querySelector('[name="age"]')?.value || "";
          openBookingModal(campus, date, age);
        }
      }
      if (event.target.closest("[data-modal-close]")) {
        closeBookingModal();
      }
      if (event.target.matches("[data-booking-modal]")) {
        closeBookingModal();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeBookingModal();
      }
    });

    document.querySelectorAll("[data-location-booking]").forEach(function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        var campus = campuses.find(function (item) { return item.id === form.dataset.locationBooking; });
        if (campus) {
          openBookingModal(campus, form.querySelector('[name="care-date"]').value, form.querySelector('[name="age"]').value);
        }
      });
    });
  }

  setDefaultDates();
  setupNavigation();
  document.querySelectorAll("[data-full-finder]").forEach(setupFullFinder);
  document.querySelectorAll("[data-compact-finder]").forEach(setupCompactFinder);
  setupBookingModal();
}());
