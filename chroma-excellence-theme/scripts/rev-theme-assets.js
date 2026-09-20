/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { minify } = require('terser');

const THEME_ROOT = path.resolve(__dirname, '..');

const ASSETS_TO_REV = [
  'assets/css/main.css',
  'assets/css/font-awesome-subset.css',
  'assets/css/page-effects.css',
  'assets/css/page-forms.css',
  'assets/css/page-careers.css',
  'assets/js/main.js',
  'assets/js/map-facade.js',
  'assets/js/map-layer.js',
  'assets/js/admin.js',
];

const hashedNamePattern = (baseName, ext) =>
  new RegExp(`^${baseName.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\$&')}\\.[a-f0-9]{12}${ext.replace('.', '\\.')}$`);

async function buildRevisionedAssets() {
  const manifest = {};
  const cleanStaleAssets = process.env.CHROMA_CLEAN_REV_ASSETS === '1';

  for (const relativeFile of ASSETS_TO_REV) {
    const sourcePath = path.join(THEME_ROOT, relativeFile);
    if (!fs.existsSync(sourcePath)) {
      continue;
    }

    const sourceDir = path.dirname(sourcePath);
    const ext = path.extname(sourcePath);
    const base = path.basename(sourcePath, ext);
    let content = fs.readFileSync(sourcePath);

    // Production serves only the hashed copy. Minify that copy while keeping
    // the readable source file intact for maintenance and debugging.
    if (ext === '.js') {
      const result = await minify(content.toString('utf8'), {
        compress: { passes: 2 },
        mangle: true,
        format: { comments: false },
      });

      if (!result.code) {
        throw new Error(`Terser did not produce output for ${relativeFile}`);
      }

      content = Buffer.from(result.code, 'utf8');
    }

    const hash = crypto.createHash('md5').update(content).digest('hex').slice(0, 12);
    const hashedFileName = `${base}.${hash}${ext}`;
    const hashedPath = path.join(sourceDir, hashedFileName);

    // Keep older hashed variants by default so CDN-cached HTML does not request
    // missing assets immediately after a deploy. Set CHROMA_CLEAN_REV_ASSETS=1
    // for an intentional cleanup pass.
    if (cleanStaleAssets) {
      for (const candidate of fs.readdirSync(sourceDir)) {
        if (hashedNamePattern(base, ext).test(candidate) && candidate !== hashedFileName) {
          fs.rmSync(path.join(sourceDir, candidate), { force: true });
        }
      }
    }

    fs.writeFileSync(hashedPath, content);
    manifest[relativeFile.replace(/\\/g, '/')] = path
      .relative(THEME_ROOT, hashedPath)
      .replace(/\\/g, '/');
  }

  const manifestPath = path.join(THEME_ROOT, 'assets', 'manifest.json');
  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2) + '\n', 'utf8');

  console.log(`Wrote ${Object.keys(manifest).length} asset mappings to ${manifestPath}`);
}

buildRevisionedAssets().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
