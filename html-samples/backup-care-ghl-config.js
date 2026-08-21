(function () {
  "use strict";

  var emptyAgeRoutes = function () {
    return {
      infant: "",
      toddler: "",
      preschool: "",
      school: "",
      default: ""
    };
  };

  /*
   * Only public family and child enrollment form URLs belong here. Order and
   * payment records are server-authoritative and must never use a public form.
   * Never place a Private
   * Integration Token or any GHL payment credential in this file.
   *
   * Example:
   * lilburn: {
   *   infant: "GHL_CALENDAR_ID",
   *   toddler: "https://api.leadconnectorhq.com/widget/booking/CALENDAR_ID",
   *   preschool: "GHL_CALENDAR_ID",
   *   school: "GHL_CALENDAR_ID",
   *   default: ""
   * }
   */
  window.CHROMA_BACKUP_CARE_GHL = {
    mode: "wordpress-coordinator-with-native-ghl-forms",
    bookingHorizonDays: 365,
    embedBaseUrl: "https://api.leadconnectorhq.com/widget/booking/",
    embedScriptUrl: "https://link.msgsndr.com/js/form_embed.js",
    allowedHosts: [
      "api.leadconnectorhq.com",
      "link.msgsndr.com",
      "links.gohighlevel.com"
    ],
    forms: {
      familyProfile: { id: "gH2g9JDSqv0EhBPuLk2n", url: "https://api.leadconnectorhq.com/widget/form/gH2g9JDSqv0EhBPuLk2n" },
      childEnrollment: { id: "JSSCutlpdu1QdvPvq18d", url: "https://api.leadconnectorhq.com/widget/form/JSSCutlpdu1QdvPvq18d" }
    },
    servicesV2: {
      serviceId: "6a852f7edd9a7f8f82f4fa36",
      bookingUrl: "",
      privateTestService: true,
      nativeCheckoutAcceptancePassed: false
    },
    serviceLocationIds: {
      chadwick: "6a852f79657b0fa5fff9333d",
      cherokee: "6a852f7980396748faf16833",
      tramore: "6a852f793d659beb309c1c88",
      "downtown-duluth": "6a852f792811afba8a35d6df",
      "east-cobb": "6a852f7a650991ceb6a7d30b",
      ellenwood: "6a852f7a2986f43169568442",
      grayson: "6a852f7ab35331651b810836",
      "johns-creek": "6a852f7b841e7f57330fd810",
      jonesboro: "6a852f7b0747e48b08f8a7db",
      lawrenceville: "6a852f7b618ed2a1169acccc",
      lilburn: "6a852f7b91badd4631cbef97",
      mcdonough: "6a852f7b618ed2a1169accd5",
      midway: "6a852f7b7b068bf85c020f58",
      "north-hall": "6a852f7b8e7594f5de268108",
      parklake: "6a852f7c91badd4631cbefa0",
      rivergreen: "6a852f7c618ed2a1169accec",
      roswell: "6a852f7d3b0d174bf9c4142f",
      satellite: "6a852f7d7a2822e84a439313",
      shenandoah: "6a852f7db35331651b810846",
      "south-cobb": "6a852f7df1d5ffcee926e5b1",
      stockbridge: "6a852f7d0747e48b08f8a7f3",
      sugarloaf: "6a852f7d46250e1b14ef4c0f",
      tyrone: "6a852f7ece1d72e2f88c719e",
      "west-cobb": "6a852f7eaf746a520bd48480"
    },
    campuses: {
      chadwick: emptyAgeRoutes(),
      cherokee: emptyAgeRoutes(),
      tramore: emptyAgeRoutes(),
      "downtown-duluth": emptyAgeRoutes(),
      "east-cobb": emptyAgeRoutes(),
      ellenwood: emptyAgeRoutes(),
      grayson: emptyAgeRoutes(),
      "johns-creek": emptyAgeRoutes(),
      jonesboro: emptyAgeRoutes(),
      lawrenceville: emptyAgeRoutes(),
      lilburn: emptyAgeRoutes(),
      mcdonough: emptyAgeRoutes(),
      midway: emptyAgeRoutes(),
      "north-hall": emptyAgeRoutes(),
      parklake: emptyAgeRoutes(),
      rivergreen: emptyAgeRoutes(),
      roswell: emptyAgeRoutes(),
      satellite: emptyAgeRoutes(),
      shenandoah: emptyAgeRoutes(),
      "south-cobb": emptyAgeRoutes(),
      stockbridge: emptyAgeRoutes(),
      sugarloaf: emptyAgeRoutes(),
      tyrone: emptyAgeRoutes(),
      "west-cobb": emptyAgeRoutes()
    }
  };
}());
