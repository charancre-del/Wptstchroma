SELECT
  'program' AS record_type,
  p.ID,
  p.post_title,
  p.post_name,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_age_range' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS age_range,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_hero_description' THEN pm.meta_value END), '') <> '' OR LENGTH(TRIM(p.post_content)) >= 120 THEN 'yes' ELSE 'missing' END AS parent_intro,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_features' THEN pm.meta_value END), '') <> '' OR LENGTH(TRIM(p.post_content)) >= 120 THEN 'yes' ELSE 'missing' END AS experience,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_focus_items' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_description' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_physical' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_emotional' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_social' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_academic' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_creative' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS priorities,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_focus_items' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_description' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_physical' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS prism_stage,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_hero_image' THEN pm.meta_value END), '') <> '' OR p.ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = '_thumbnail_id' AND meta_value <> '') THEN 'yes' ELSE 'fallback' END AS image,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_locations_served' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'program_locations' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS campus_links,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_cta_link' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS cta,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_schedule_items' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS schedule,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_faq_items' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS faq,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'program_meta_title' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'meta_description' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS seo
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID
WHERE p.post_type = 'program' AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title, p.post_name
ORDER BY p.menu_order, p.post_title;

SELECT
  'location' AS record_type,
  p.ID,
  p.post_title,
  p.post_name,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_address' THEN pm.meta_value END), '') <> '' AND COALESCE(MAX(CASE WHEN pm.meta_key = 'location_city' THEN pm.meta_value END), '') <> '' AND COALESCE(MAX(CASE WHEN pm.meta_key = 'location_state' THEN pm.meta_value END), '') <> '' AND COALESCE(MAX(CASE WHEN pm.meta_key = 'location_zip' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS address,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_phone' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS phone,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_email' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS email,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hours' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS hours,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_latitude' THEN pm.meta_value END), '') <> '' AND COALESCE(MAX(CASE WHEN pm.meta_key = 'location_longitude' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS coordinates,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_tour_booking_link' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS tour_link,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hero_gallery' THEN pm.meta_value END), '') <> '' OR p.ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = '_thumbnail_id' AND meta_value <> '') THEN 'yes' ELSE 'fallback' END AS images,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_faq_items' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS faq,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_director_name' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'optional' END AS director,
  CASE
    WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_description' THEN pm.meta_value END), '') <> '' OR LENGTH(TRIM(p.post_content)) >= 120 THEN 'yes'
    WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_tagline' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hero_subtitle' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'location_seo_content_text' THEN pm.meta_value END), '') <> '' THEN 'fallback'
    ELSE 'missing'
  END AS intro,
  CASE WHEN EXISTS (
    SELECT 1 FROM wp_posts pp JOIN wp_postmeta ppm ON ppm.post_id = pp.ID
    WHERE pp.post_type = 'program' AND pp.post_status = 'publish'
      AND ppm.meta_key IN ('program_locations','program_locations_served')
      AND ppm.meta_value LIKE CONCAT('%i:', p.ID, ';%')
  ) THEN 'yes' ELSE 'missing' END AS programs,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_ages_served' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'fallback' END AS ages,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_special_programs' THEN pm.meta_value END), '') <> '' OR COALESCE(MAX(CASE WHEN pm.meta_key = 'location_quality_rated' THEN pm.meta_value END), '') = '1' OR COALESCE(MAX(CASE WHEN pm.meta_key = '_chroma_security_cameras' THEN pm.meta_value END), '') = '1' THEN 'yes' ELSE 'optional' END AS features,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_school_pickups' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'optional' END AS school_pickups,
  CASE WHEN EXISTS (
    SELECT 1 FROM wp_postmeta ppm WHERE ppm.post_id = 4324
      AND ppm.meta_key IN ('program_locations','program_locations_served')
      AND ppm.meta_value LIKE CONCAT('%i:', p.ID, ';%')
  ) THEN 'yes' ELSE 'optional' END AS ga_pre_k,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = '_chroma_caps_accepted' THEN pm.meta_value END), '') = '1' THEN 'yes' ELSE 'optional' END AS caps,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_quality_rated' THEN pm.meta_value END), '') = '1' THEN 'yes' ELSE 'optional' END AS quality_rated,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = '_chroma_license_number' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS licensing,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hero_review_text' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'optional' END AS testimonial,
  'fallback' AS tuition,
  'fallback' AS availability,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_seo_content_text' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS nearby_communities,
  CASE WHEN COALESCE(MAX(CASE WHEN pm.meta_key = 'location_seo_content_text' THEN pm.meta_value END), '') <> '' THEN 'yes' ELSE 'missing' END AS nearby_access,
  CASE WHEN p.post_modified_gmt <> '0000-00-00 00:00:00' THEN 'yes' ELSE 'missing' END AS last_reviewed
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID
WHERE p.post_type = 'location' AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title, p.post_name
ORDER BY p.post_title;
