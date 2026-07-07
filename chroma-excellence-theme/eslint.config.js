const browserGlobals = {
    window: 'readonly',
    document: 'readonly',
    navigator: 'readonly',
    performance: 'readonly',
    localStorage: 'readonly',
    location: 'readonly',
    history: 'readonly',
    HTMLElement: 'readonly',
    HTMLIFrameElement: 'readonly',
    IntersectionObserver: 'readonly',
    MutationObserver: 'readonly',
    CustomEvent: 'readonly',
    URL: 'readonly',
    URLSearchParams: 'readonly',
    jQuery: 'readonly',
    $: 'readonly',
    wp: 'readonly',
    L: 'readonly',
    Chart: 'readonly',
    console: 'readonly',
    setTimeout: 'readonly',
    setInterval: 'readonly',
    clearTimeout: 'readonly',
    clearInterval: 'readonly',
    requestAnimationFrame: 'readonly',
    fetch: 'readonly',
    FormData: 'readonly',
    XMLHttpRequest: 'readonly',
    alert: 'readonly'
};

const nodeGlobals = {
    __dirname: 'readonly',
    console: 'readonly',
    process: 'readonly',
    require: 'readonly'
};

module.exports = [
    {
        ignores: [
            'assets/js/**/*.min.js',
            'assets/js/pdf/**',
            'assets/js/*.map',
            'assets/js/*.*.js'
        ]
    },
    {
        files: ['assets/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: 'script',
            globals: browserGlobals
        },
        rules: {
            'no-unused-vars': 'warn',
            'no-undef': 'error',
            'semi': ['warn', 'always'],
            'no-console': 'off'
        }
    },
    {
        files: ['scripts/**/*.js'],
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: 'commonjs',
            globals: nodeGlobals
        },
        rules: {
            'no-unused-vars': 'warn',
            'no-undef': 'error',
            'semi': ['warn', 'always'],
            'no-console': 'off'
        }
    }
];
