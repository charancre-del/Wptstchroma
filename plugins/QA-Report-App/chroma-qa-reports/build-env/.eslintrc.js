module.exports = {
    extends: [ require.resolve( '@wordpress/scripts/config/.eslintrc.js' ) ],
    env: {
        browser: true,
        es2021: true,
    },
    settings: {
        'import/resolver': {
            node: {
                extensions: [ '.js', '.jsx', '.json' ],
            },
        },
    },
    rules: {
        camelcase: 'off',
        'jsdoc/require-param': 'off',
        'jsdoc/require-param-type': 'off',
        'jsdoc/require-returns': 'off',
        'jsdoc/require-returns-type': 'off',
        'no-alert': 'off',
        'no-console': 'off',
        'no-nested-ternary': 'off',
        'import/no-extraneous-dependencies': 'off',
        'import/no-unresolved': 'off',
    },
};
