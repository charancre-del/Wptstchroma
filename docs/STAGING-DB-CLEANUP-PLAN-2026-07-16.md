# Staging Database Cleanup Plan — 2026-07-16

## Scope and safety

- Target only: `/home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site`
- Verified staging URL: `https://x3yyntt5tp-staging.wpdns.site`
- Verified staging database: `x3yyadl_staging`
- Verified table prefix: `wp_`
- SSH target: `x3yyadl@131.153.236.189`
- This plan was produced from read-only SSH/WP-CLI inspection. No database or remote filesystem writes were executed.
- Every write block below has staging URL/database guards and expected-row-count guards. Do not weaken them.

## Read-only evidence

### Programs and age ranges

Staging has 11 published `program` posts and exactly one `program_age_range` row per program.

| Meta ID | Post ID | Program slug | Current value | Proposed normalized value |
|---:|---:|---|---|---|
| 14359 | 4219 | `infant-care` | `6 weeks - 15mo ( Non walker)` | `6 Weeks - 15 Months (Non-Walkers)` |
| 14437 | 4293 | `toddler-care` | `12 Months ( Walkers) - 24 Months` | `12 - 24 Months (Walkers)` |
| 14960 | 4322 | `preschool` | `24 Months to 36 Month` | `24 - 36 Months` |
| 14992 | 4323 | `pre-k-prep` | `3Yr - 4Yr` | `3 - 4 Years` |
| 15023 | 4324 | `ga-pre-k` | `4 Yr - 5 Yr` | `4 - 5 Years` |
| 15053 | 4325 | `after-school` | `5 Yr - 12 Yr` | `5 - 12 Years` |
| 15083 | 4326 | `camp-summer-winter-fall` | `Seasonal ( Ages 5-12)` | `Ages 5 - 12 (Seasonal)` |
| 15114 | 4327 | `parents-day-out` | `3 Yr to 5 Yr` | `3 - 5 Years` |
| 38908 | 6785 | `kindergarten-1` | `5yr - 6yr` | `5 - 6 Years` |
| 42345 | 7708 | `rising-pre-k` | `Pre-K Eligible` | `Pre-K Eligible` |
| 42401 | 7709 | `rising-kindergarten` | `Pre-K Graduates` | `Pre-K Graduates` |

### `chroma_combo_*` options

- Total prefixed options: 620.
- Control options to preserve: 3 — `chroma_combo_auto_publish`, `chroma_combo_sitemap_flush_Check`, and `chroma_combo_sitemap_flush_v2`.
- Per-combination serialized-array options: 617; all have 20 fields and are autoloaded.
- Original seed to preserve: option ID `432289`, `chroma_combo_parents-day-out_kennesaw_ga`, 1,241 bytes.
- Exact later/stale set: 616 rows, all option IDs greater than `432289`, totaling 644,808 serialized bytes, all autoloaded.
- The 616-row set is equivalently selected by the per-combination name shape while excluding the preserved seed.
- Stored per-combination options do not match the current generator source exactly: current generator output is 572 unique combinations, with 6 expected keys missing and 51 stored keys orphaned. Deleting the later bulk set avoids treating the current generator output as a reliable restore manifest.

The storage class uses the key shape `chroma_combo_{program_slug}_{city_slug}_{state}` and stores a serialized 20-field PHP array. The 617 current data rows decode as 225 `status=auto` / `ai_generated=false` and 392 `status=published` / `ai_generated=true`.

### Post 4406 schema payload

- Post `4406` is the published location `Cherokee Academy by Chroma, Canton GA` (`cherokee-campus`).
- Exact injected wrapper row: meta ID `40898`, key `metasync_schema_markup`, 23,546 bytes.
- The wrapper identifies its source as OTTO and includes `last_synced_at=2026-07-06 06:48:29`.
- Raw OTTO structured data also remains at meta ID `37661`, key `_metasync_otto_structured_data`, 23,395 bytes.
- Current Chroma schema data is separate at meta ID `46336`, key `_chroma_schema_data`, 11,782 bytes, with `_chroma_schema_override=1` at meta ID `43351`.
- The cleanup below removes only the injected `metasync_schema_markup` wrapper. Because MetaSync `2.6.17` is active and its OTTO sync code can recreate that wrapper from `_metasync_otto_structured_data`, recurrence prevention requires disabling/removing the OTTO structured-data source outside this DB-only cleanup.

### Raw keyword meta

- `_metasync_otto_keywords`: 316 rows on 316 posts; all 316 are nonempty.
- `meta_keywords`: 163 rows on 163 post IDs; 59 empty and 104 nonempty.
- Combined exact deletion set: 479 rows.
- The inventory includes one orphan `meta_keywords` row whose `post_id` no longer joins to `wp_posts`; the exact SQL deletion intentionally includes it.

### Footer duplicate

- Footer menu is term/menu ID `15`, assigned to location `footer`.
- Item `4452`: `Stories`, page object `4361`, `/stories/`, position 5 — preserve.
- Item `6115`: `Stories`, page object `4361`, `/stories/`, position 6 — remove.
- The Spanish footer has one separate Stories item (`6098`) and is not part of the duplicate.

### GA Pre-K availability

Staging has 24 published locations. The evidence does **not** support the claim that all 24 offer GA Pre-K.

- GA Pre-K program: post `4324`, slug `ga-pre-k`.
- Direct legacy `program_locations` assignments: 19 location IDs.
- `program_location` taxonomy assignments: 0; the legacy meta and taxonomy are not synchronized.
- `_chroma_ga_pre_k_accepted`: 20 stored rows, but 19 are empty and only North Hall (`4419`) is `1`; North Hall is already one of the 19 direct assignments.
- `location_special_programs` adds claims for 3 locations not directly assigned: Grayson (`6781`), Stockbridge (`6782`), and Sugarloaf Pkwy (`6780`).
- Any supporting signal: 22 of 24.
- No GA Pre-K signal in assignment or location meta: Chadwick (`6779`) and Parklake (`8641`).
- Therefore: 19 are directly assigned, 3 more are marketing-meta-only, and 2 have no supporting evidence. Do not state that all 24 offer GA Pre-K without operational confirmation and data correction.

## Exact command sequence

Run each block from a local PowerShell terminal. The cleanup block is intentionally separate from the backup block.

### 1. Create exact full-database backup and evidence exports

```powershell
$script = @'
set -euo pipefail

ROOT=/home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site
EXPECTED_URL=https://x3yyntt5tp-staging.wpdns.site
EXPECTED_DB=x3yyadl_staging

cd "$ROOT"
[ "$(pwd)" = "$ROOT" ] || { echo "ABORT: wrong root" >&2; exit 10; }
[ "$(wp option get siteurl)" = "$EXPECTED_URL" ] || { echo "ABORT: not staging URL" >&2; exit 11; }
[ "$(wp db query 'SELECT DATABASE();' --skip-column-names)" = "$EXPECTED_DB" ] || { echo "ABORT: not staging DB" >&2; exit 12; }

STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP_DIR="/home/x3yyadl/staging-db-cleanup-backups/$STAMP"
mkdir -p "$BACKUP_DIR"

wp db export "$BACKUP_DIR/staging-before-cleanup.sql" --add-drop-table --single-transaction --quick --lock-tables=false
gzip -9 "$BACKUP_DIR/staging-before-cleanup.sql"
sha256sum "$BACKUP_DIR/staging-before-cleanup.sql.gz" | tee "$BACKUP_DIR/SHA256SUMS"

wp db query "SELECT DATABASE() AS db_name, @@hostname AS db_host;" > "$BACKUP_DIR/database-identity.tsv"
wp db query "SELECT pm.meta_id,pm.post_id,p.post_type,p.post_status,p.post_name,p.post_title,pm.meta_key,LENGTH(pm.meta_value) AS bytes,HEX(pm.meta_value) AS value_hex FROM wp_postmeta pm LEFT JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key IN ('meta_keywords','_metasync_otto_keywords') ORDER BY pm.post_id,pm.meta_key,pm.meta_id;" > "$BACKUP_DIR/keyword-meta.tsv"
wp db query "SELECT option_id,option_name,autoload,LENGTH(option_value) AS bytes,HEX(option_value) AS value_hex FROM wp_options WHERE option_name REGEXP '^chroma_combo_.+_.+_ga$' AND option_name <> 'chroma_combo_parents-day-out_kennesaw_ga' ORDER BY option_id;" > "$BACKUP_DIR/stale-combo-options.tsv"
wp db query "SELECT meta_id,post_id,meta_key,LENGTH(meta_value) AS bytes,HEX(meta_value) AS value_hex FROM wp_postmeta WHERE post_id=4406 AND meta_key IN ('metasync_schema_markup','_metasync_otto_structured_data','_chroma_schema_data','_chroma_schema_override') ORDER BY meta_id;" > "$BACKUP_DIR/post-4406-schema-meta.tsv"
wp menu item list 15 --fields=db_id,title,type,object,object_id,url,menu_item_parent,position --format=csv > "$BACKUP_DIR/footer-menu-before.csv"

echo "BACKUP_DIR=$BACKUP_DIR"
echo "DB_BACKUP=$BACKUP_DIR/staging-before-cleanup.sql.gz"
sha256sum -c "$BACKUP_DIR/SHA256SUMS"
# end
'@
($script -replace "`r", "") | ssh -i $HOME\.ssh\wordpress x3yyadl@131.153.236.189 bash -s
```

Record the printed `BACKUP_DIR` before continuing.

### 2. Apply the guarded cleanup

This is the write block. It was **not** executed while preparing this plan.

```powershell
$script = @'
set -euo pipefail

ROOT=/home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site
EXPECTED_URL=https://x3yyntt5tp-staging.wpdns.site
EXPECTED_DB=x3yyadl_staging

cd "$ROOT"
[ "$(pwd)" = "$ROOT" ] || { echo "ABORT: wrong root" >&2; exit 10; }
[ "$(wp option get siteurl)" = "$EXPECTED_URL" ] || { echo "ABORT: not staging URL" >&2; exit 11; }
[ "$(wp db query 'SELECT DATABASE();' --skip-column-names)" = "$EXPECTED_DB" ] || { echo "ABORT: not staging DB" >&2; exit 12; }

age_old_count=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='program_age_range' AND ((meta_id=14359 AND post_id=4219 AND meta_value='6 weeks - 15mo ( Non walker)') OR (meta_id=14437 AND post_id=4293 AND meta_value='12 Months ( Walkers) - 24 Months') OR (meta_id=14960 AND post_id=4322 AND meta_value='24 Months to 36 Month') OR (meta_id=14992 AND post_id=4323 AND meta_value='3Yr - 4Yr') OR (meta_id=15023 AND post_id=4324 AND meta_value='4 Yr - 5 Yr') OR (meta_id=15053 AND post_id=4325 AND meta_value='5 Yr - 12 Yr') OR (meta_id=15083 AND post_id=4326 AND meta_value='Seasonal ( Ages 5-12)') OR (meta_id=15114 AND post_id=4327 AND meta_value='3 Yr to 5 Yr') OR (meta_id=38908 AND post_id=6785 AND meta_value='5yr - 6yr') OR (meta_id=42345 AND post_id=7708 AND meta_value='Pre-K Eligible') OR (meta_id=42401 AND post_id=7709 AND meta_value='Pre-K Graduates'));" --skip-column-names)
[ "$age_old_count" = 11 ] || { echo "ABORT: expected 11 exact old age rows, found $age_old_count" >&2; exit 20; }

combo_count=$(wp db query "SELECT COUNT(*) FROM wp_options WHERE option_name REGEXP '^chroma_combo_.+_.+_ga$' AND option_name <> 'chroma_combo_parents-day-out_kennesaw_ga';" --skip-column-names)
[ "$combo_count" = 616 ] || { echo "ABORT: expected 616 stale combo rows, found $combo_count" >&2; exit 21; }

seed_count=$(wp db query "SELECT COUNT(*) FROM wp_options WHERE option_id=432289 AND option_name='chroma_combo_parents-day-out_kennesaw_ga';" --skip-column-names)
[ "$seed_count" = 1 ] || { echo "ABORT: preserved combo seed changed" >&2; exit 22; }

schema_count=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_id=40898 AND post_id=4406 AND meta_key='metasync_schema_markup' AND LENGTH(meta_value)=23546;" --skip-column-names)
[ "$schema_count" = 1 ] || { echo "ABORT: post 4406 injected schema row changed" >&2; exit 23; }

meta_keywords_count=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='meta_keywords';" --skip-column-names)
otto_keywords_count=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_metasync_otto_keywords';" --skip-column-names)
[ "$meta_keywords_count" = 163 ] || { echo "ABORT: expected 163 meta_keywords rows, found $meta_keywords_count" >&2; exit 24; }
[ "$otto_keywords_count" = 316 ] || { echo "ABORT: expected 316 OTTO keyword rows, found $otto_keywords_count" >&2; exit 25; }

stories_count=$(wp db query "SELECT COUNT(*) FROM wp_posts p JOIN wp_term_relationships tr ON tr.object_id=p.ID JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='nav_menu' JOIN wp_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_menu_item_object_id' AND pm.meta_value='4361' WHERE p.post_type='nav_menu_item' AND tt.term_id=15 AND p.ID IN (4452,6115);" --skip-column-names)
[ "$stories_count" = 2 ] || { echo "ABORT: expected footer Stories items 4452 and 6115" >&2; exit 26; }

wp db query <<'SQL'
UPDATE wp_postmeta
SET meta_value = CASE meta_id
  WHEN 14359 THEN '6 Weeks - 15 Months (Non-Walkers)'
  WHEN 14437 THEN '12 - 24 Months (Walkers)'
  WHEN 14960 THEN '24 - 36 Months'
  WHEN 14992 THEN '3 - 4 Years'
  WHEN 15023 THEN '4 - 5 Years'
  WHEN 15053 THEN '5 - 12 Years'
  WHEN 15083 THEN 'Ages 5 - 12 (Seasonal)'
  WHEN 15114 THEN '3 - 5 Years'
  WHEN 38908 THEN '5 - 6 Years'
  WHEN 42345 THEN 'Pre-K Eligible'
  WHEN 42401 THEN 'Pre-K Graduates'
END
WHERE meta_key='program_age_range'
  AND meta_id IN (14359,14437,14960,14992,15023,15053,15083,15114,38908,42345,42401);
SQL

wp db query "DELETE FROM wp_options WHERE option_name REGEXP '^chroma_combo_.+_.+_ga$' AND option_name <> 'chroma_combo_parents-day-out_kennesaw_ga';"
wp db query "DELETE FROM wp_postmeta WHERE meta_id=40898 AND post_id=4406 AND meta_key='metasync_schema_markup' AND LENGTH(meta_value)=23546;"
wp db query "DELETE FROM wp_postmeta WHERE meta_key IN ('meta_keywords','_metasync_otto_keywords');"
wp menu item delete 6115
wp cache flush
# end
'@
($script -replace "`r", "") | ssh -i $HOME\.ssh\wordpress x3yyadl@131.153.236.189 bash -s
```

### 3. Verify the cleanup read-only

```powershell
$script = @'
set -euo pipefail
cd /home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site

[ "$(wp option get siteurl)" = "https://x3yyntt5tp-staging.wpdns.site" ] || exit 11
[ "$(wp db query 'SELECT DATABASE();' --skip-column-names)" = "x3yyadl_staging" ] || exit 12

wp db query "SELECT pm.meta_id,pm.post_id,p.post_name,pm.meta_value FROM wp_postmeta pm JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key='program_age_range' ORDER BY pm.meta_id;"
wp db query "SELECT COUNT(*) AS stale_combo_rows FROM wp_options WHERE option_name REGEXP '^chroma_combo_.+_.+_ga$' AND option_name <> 'chroma_combo_parents-day-out_kennesaw_ga';"
wp db query "SELECT option_id,option_name,autoload,LENGTH(option_value) AS bytes FROM wp_options WHERE option_name LIKE 'chroma\\_combo\\_%' ORDER BY option_id;"
wp db query "SELECT COUNT(*) AS injected_schema_rows FROM wp_postmeta WHERE post_id=4406 AND meta_key='metasync_schema_markup';"
wp db query "SELECT meta_key,COUNT(*) AS rows_count FROM wp_postmeta WHERE meta_key IN ('meta_keywords','_metasync_otto_keywords') GROUP BY meta_key;"
wp menu item list 15 --fields=db_id,title,type,object,object_id,url,position --format=table
# end
'@
($script -replace "`r", "") | ssh -i $HOME\.ssh\wordpress x3yyadl@131.153.236.189 bash -s
```

Expected results:

- 11 normalized age rows.
- `stale_combo_rows=0`.
- Exactly four remaining `chroma_combo_*` options: the three controls plus preserved seed `432289`.
- `injected_schema_rows=0` for `metasync_schema_markup` on post 4406.
- No rows returned by the keyword-meta grouped query.
- Footer menu contains only Stories item `4452`; item `6115` is absent.

### 4. Exact full rollback

Set `BACKUP_DIR` to the directory printed by step 1. This restores the complete staging database, including original IDs and serialized values.

```powershell
$script = @'
set -euo pipefail

ROOT=/home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site
EXPECTED_URL=https://x3yyntt5tp-staging.wpdns.site
EXPECTED_DB=x3yyadl_staging
BACKUP_DIR=/home/x3yyadl/staging-db-cleanup-backups/REPLACE_WITH_RECORDED_TIMESTAMP

cd "$ROOT"
[ "$(pwd)" = "$ROOT" ] || { echo "ABORT: wrong root" >&2; exit 10; }
[ "$(wp option get siteurl)" = "$EXPECTED_URL" ] || { echo "ABORT: not staging URL" >&2; exit 11; }
[ "$(wp db query 'SELECT DATABASE();' --skip-column-names)" = "$EXPECTED_DB" ] || { echo "ABORT: not staging DB" >&2; exit 12; }

sha256sum -c "$BACKUP_DIR/SHA256SUMS"
gzip -dc "$BACKUP_DIR/staging-before-cleanup.sql.gz" | wp db cli
wp cache flush
wp option get siteurl
wp db query 'SELECT DATABASE() AS db_name;'
# end
'@
($script -replace "`r", "") | ssh -i $HOME\.ssh\wordpress x3yyadl@131.153.236.189 bash -s
```

## Read-only keyword listing command

Use this independently whenever the raw keyword inventory must be reviewed before approval:

```powershell
$script = @'
set -euo pipefail
cd /home/x3yyadl/public_html/x3yyntt5tp-staging.wpdns.site
[ "$(wp option get siteurl)" = "https://x3yyntt5tp-staging.wpdns.site" ] || exit 11
[ "$(wp db query 'SELECT DATABASE();' --skip-column-names)" = "x3yyadl_staging" ] || exit 12
wp db query "SELECT pm.meta_id,pm.post_id,p.post_type,p.post_status,p.post_name,p.post_title,pm.meta_key,LENGTH(pm.meta_value) AS bytes,pm.meta_value FROM wp_postmeta pm LEFT JOIN wp_posts p ON p.ID=pm.post_id WHERE pm.meta_key IN ('meta_keywords','_metasync_otto_keywords') ORDER BY pm.post_id,pm.meta_key,pm.meta_id;"
# end
'@
($script -replace "`r", "") | ssh -i $HOME\.ssh\wordpress x3yyadl@131.153.236.189 bash -s
```

## Approval gates before writes

1. Confirm the proposed age-range wording, especially whether the two eligibility labels should remain descriptive rather than numeric.
2. Confirm preservation of seed combo option `432289`; the guarded delete targets the exact 616 later rows requested.
3. Confirm deletion of all 104 nonempty `meta_keywords` values and all 316 nonempty OTTO keyword values, not only empty rows.
4. Decide whether OTTO structured data for post 4406 should also be removed/disabled at its source to prevent `metasync_schema_markup` regeneration.
5. Resolve GA Pre-K truth with operations before changing assignments: 19 direct, 3 meta-only, 2 unsupported.
