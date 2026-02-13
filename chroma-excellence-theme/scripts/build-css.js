/* eslint-disable no-console */
const { spawnSync } = require('child_process');

const command = 'npx';
const args = ['postcss', 'assets/css/input.css', '-o', 'assets/css/main.css'];

const result = spawnSync(command, args, {
  stdio: 'inherit',
  shell: true,
  env: {
    ...process.env,
    NODE_ENV: 'production',
  },
});

if (result.error) {
  console.error(result.error.message);
  process.exit(1);
}

process.exit(result.status || 0);
