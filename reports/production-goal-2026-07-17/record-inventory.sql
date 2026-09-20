SELECT
  p.ID,
  p.post_title,
  p.post_name,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_age_range' THEN pm.meta_value END), '') AS age_range,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_hero_description' THEN pm.meta_value END), '') AS hero_description,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_hero_image' THEN pm.meta_value END), '') AS hero_image,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_locations_served' THEN pm.meta_value END), '') AS locations_served,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_cta_link' THEN pm.meta_value END), '') AS cta_link,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_prism_description' THEN pm.meta_value END), '') AS prism_description,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_schedule_items' THEN pm.meta_value END), '') AS schedule_items,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_meta_title' THEN pm.meta_value END), '') AS meta_title,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'program_meta_description' THEN pm.meta_value END), '') AS meta_description
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID
WHERE p.post_type = 'program' AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title, p.post_name
ORDER BY p.menu_order, p.post_title;

SELECT '__LOCATIONS__';
SELECT
  p.ID,
  p.post_title,
  p.post_name,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_address' THEN pm.meta_value END), '') AS address,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_city' THEN pm.meta_value END), '') AS city,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_state' THEN pm.meta_value END), '') AS state,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_zip' THEN pm.meta_value END), '') AS zip,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_phone' THEN pm.meta_value END), '') AS phone,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_email' THEN pm.meta_value END), '') AS email,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hours' THEN pm.meta_value END), '') AS hours,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_latitude' THEN pm.meta_value END), '') AS latitude,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_longitude' THEN pm.meta_value END), '') AS longitude,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_director_name' THEN pm.meta_value END), '') AS director_name,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_quality_rated' THEN pm.meta_value END), '') AS quality_rated,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_special_programs' THEN pm.meta_value END), '') AS special_programs,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_tour_booking_link' THEN pm.meta_value END), '') AS tour_link,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_google_rating' THEN pm.meta_value END), '') AS google_rating,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_hero_gallery' THEN pm.meta_value END), '') AS hero_gallery,
  COALESCE(MAX(CASE WHEN pm.meta_key = 'location_faq_items' THEN pm.meta_value END), '') AS faq_items
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON pm.post_id = p.ID
WHERE p.post_type = 'location' AND p.post_status = 'publish'
GROUP BY p.ID, p.post_title, p.post_name
ORDER BY p.post_title;
