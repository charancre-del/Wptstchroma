SELECT '__PROGRAM_KEYS__';
SELECT DISTINCT pm.meta_key
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.post_type = 'program'
  AND p.post_status = 'publish'
  AND LEFT(pm.meta_key, 1) <> '_'
ORDER BY pm.meta_key;

SELECT '__LOCATION_KEYS__';
SELECT DISTINCT pm.meta_key
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE p.post_type = 'location'
  AND p.post_status = 'publish'
  AND LEFT(pm.meta_key, 1) <> '_'
ORDER BY pm.meta_key;
