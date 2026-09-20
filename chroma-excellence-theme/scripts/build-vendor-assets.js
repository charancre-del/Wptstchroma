/* eslint-disable no-console */
const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const cssnano = require('cssnano');
const { minify } = require('terser');

const THEME_ROOT = path.resolve(__dirname, '..');
const LEAFLET_ROOT = path.join(THEME_ROOT, 'node_modules', 'leaflet');
const TARGET_ROOT = path.join(THEME_ROOT, 'assets', 'vendor', 'leaflet-1.9.4');

async function buildLeaflet() {
  const sourceJs = path.join(LEAFLET_ROOT, 'dist', 'leaflet.js');
  const sourceCss = path.join(LEAFLET_ROOT, 'dist', 'leaflet.css');
  const sourceImages = path.join(LEAFLET_ROOT, 'dist', 'images');
  const sourceLicense = path.join(LEAFLET_ROOT, 'LICENSE');

  for (const requiredPath of [sourceJs, sourceCss, sourceImages, sourceLicense]) {
    if (!fs.existsSync(requiredPath)) {
      throw new Error(`Missing Leaflet vendor source: ${requiredPath}`);
    }
  }

  fs.rmSync(TARGET_ROOT, { recursive: true, force: true });
  fs.mkdirSync(path.join(TARGET_ROOT, 'images'), { recursive: true });

  const jsResult = await minify(fs.readFileSync(sourceJs, 'utf8'), {
    compress: { passes: 2 },
    mangle: true,
    format: { comments: false },
  });
  if (!jsResult.code) {
    throw new Error('Leaflet JavaScript minification produced no output.');
  }
  fs.writeFileSync(path.join(TARGET_ROOT, 'leaflet.min.js'), jsResult.code);

  const cssResult = await postcss([cssnano({ preset: 'default' })])
    .process(fs.readFileSync(sourceCss, 'utf8'), { from: sourceCss });
  fs.writeFileSync(path.join(TARGET_ROOT, 'leaflet.min.css'), cssResult.css);

  for (const imageName of fs.readdirSync(sourceImages)) {
    fs.copyFileSync(
      path.join(sourceImages, imageName),
      path.join(TARGET_ROOT, 'images', imageName)
    );
  }
  fs.copyFileSync(sourceLicense, path.join(TARGET_ROOT, 'LICENSE'));

  console.log('Built local Leaflet 1.9.4 vendor assets.');
}

buildLeaflet().catch((error) => {
  console.error(error);
  process.exit(1);
});
