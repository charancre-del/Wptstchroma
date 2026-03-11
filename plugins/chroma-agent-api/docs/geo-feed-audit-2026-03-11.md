# Geo Feed Audit (2026-03-11)

- Contract version: `2026-02-28.3`
- Top-level keys: success, cached, contract_version, generated_at_gmt, source, summary, brand, curriculum, locations, programs
- Location count: 19
- Program count: 8
- Average location quality: 83.5
- Average program quality: 81.8

## Priority Findings
- Locations missing reverse program mapping: 19
- Coordinates in legacy fields are string-typed
- Address/state and highlight/FAQ completeness gaps remain

## Per-Location Scores
- 4287 `tyrone-campus`: 74.2 (missing: address.state, email, programs_offered, ages_accepted, service_areas, faqs, events, open_house_date)
- 4289 `pleasanthill-campus-duluth`: 77.4 (missing: address.state, programs_offered, ages_accepted, service_areas, faqs, events, open_house_date)
- 4288 `lawrenceville-campus`: 77.4 (missing: address.state, programs_offered, ages_accepted, service_areas, faqs, events, open_house_date)
- 4290 `tramore-campus-austell`: 77.4 (missing: administrator_name, programs_offered, ages_accepted, facility_highlights.description, service_areas, events, open_house_date)
- 4420 `newnan`: 80.6 (missing: administrator_name, programs_offered, facility_highlights.description, service_areas, events, open_house_date)
- 4410 `ellenwood-campus`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4407 `lilburn-campus`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4408 `east-cobb-campus`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4336 `mcdonough`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4417 `midway-campus`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4411 `west-cobb-campus`: 83.9 (missing: programs_offered, service_areas, faqs, events, open_house_date)
- 4406 `cherokee-campus`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4237 `johns-creek`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4414 `jonesboro-campus`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4419 `north-hall-campus-murraysville`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4415 `rivergreen-campus`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4409 `roswell-campus`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4413 `satellite-bvd-campus`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)
- 4416 `south-cobb-campus-austell`: 87.1 (missing: programs_offered, service_areas, events, open_house_date)

## Per-Program Scores
- 4326 `camp-summer-winter-fall`: 72.7 (missing: related_programs, seo, lesson_plan_url)
- 4324 `ga-pre-k`: 72.7 (missing: related_programs, seo, lesson_plan_url)
- 4327 `parents-day-out`: 81.8 (missing: related_programs, lesson_plan_url)
- 4323 `pre-k-prep`: 81.8 (missing: related_programs, seo)
- 4322 `preschool`: 81.8 (missing: related_programs, seo)
- 4293 `toddler-care`: 81.8 (missing: related_programs, seo)
- 4219 `infant-care`: 90.9 (missing: related_programs)
- 4325 `after-school`: 90.9 (missing: seo)
