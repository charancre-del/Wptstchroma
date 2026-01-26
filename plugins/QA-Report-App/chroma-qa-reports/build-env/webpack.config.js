const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(__dirname, 'src/index.js'),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, '../build'), // Output to parent plugin folder
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve.alias,
            '@api': path.resolve(__dirname, 'src/api'),
            '@components': path.resolve(__dirname, 'src/components'),
            '@hooks': path.resolve(__dirname, 'src/hooks'),
            '@stores': path.resolve(__dirname, 'src/stores'),
            '@utils': path.resolve(__dirname, 'src/utils'),
        },
    },
};
