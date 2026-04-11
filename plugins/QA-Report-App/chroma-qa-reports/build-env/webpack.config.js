const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve( __dirname, 'src/index.js' ),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve( __dirname, '../build' ), // Output to parent plugin folder
        chunkFilename: '[name].[contenthash:8].chunk.js',
    },
    optimization: {
        ...defaultConfig.optimization,
        splitChunks: {
            ...( defaultConfig.optimization?.splitChunks || {} ),
            chunks: 'async',
            cacheGroups: {
                ...( defaultConfig.optimization?.splitChunks?.cacheGroups || {} ),
                reactVendor: {
                    test: /[\\/]node_modules[\\/](react|react-dom|react-router|react-router-dom|scheduler)[\\/]/,
                    name: 'cqa-react-vendor',
                    chunks: 'async',
                    priority: 40,
                    reuseExistingChunk: true,
                },
                uiVendor: {
                    test: /[\\/]node_modules[\\/](lucide-react|recharts|zustand|sonner|@tanstack|@radix-ui)[\\/]/,
                    name: 'cqa-ui-vendor',
                    chunks: 'async',
                    priority: 30,
                    reuseExistingChunk: true,
                },
            },
        },
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve.alias,
            '@api': path.resolve( __dirname, 'src/api' ),
            '@components': path.resolve( __dirname, 'src/components' ),
            '@hooks': path.resolve( __dirname, 'src/hooks' ),
            '@stores': path.resolve( __dirname, 'src/stores' ),
            '@utils': path.resolve( __dirname, 'src/utils' ),
        },
    },
};
