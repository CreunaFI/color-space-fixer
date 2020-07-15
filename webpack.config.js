const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const WatchTimePlugin = require('webpack-watch-time-plugin');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

module.exports = (env, argv) => {
    let config = {
        entry: {
            style: './src/style.scss',
            script: './src/script.js',
        },
        output: {
            filename: '[name].js',
            chunkFilename: '[name].js?ver=[chunkhash]',
            publicPath: '/wp-content/plugins/color-space-fixer/dist/',
        },
        resolve: {
            extensions: ['*', '.js', '.vue'],
            alias: {
                vue$: 'vue/dist/vue.esm.js',
            },
        },
        mode: 'development',
        performance: {
            hints: false,
        },
        devtool: 'source-map',
        module: {
            rules: [
                {
                    test: /\.js$/,
                    use: [
                        {
                            loader: 'babel-loader',
                            options: {
                                presets: ['@babel/env'],
                            },
                        },
                    ],
                },
                {
                    test: /\.vue$/,
                    use: {
                        loader: 'vue-loader',
                        options: {
                            loaders: {
                                js: {
                                    loader: 'babel-loader',
                                    options: {
                                        presets: ['@babel/preset-env'],
                                    },
                                },
                            },
                        },
                    },
                },
                {
                    test: /\.(png|svg|jpg|jpeg|tiff|webp|gif|ico|woff|woff2|eot|ttf|otf|mp4|webm|wav|mp3|m4a|aac|oga)$/,
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                context: 'src',
                                name: '[path][name].[ext]?ver=[md5:hash:8]',
                            },
                        },
                    ],
                },
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: '[name].css',
                chunkFilename: '[id].css',
            }),
            new WatchTimePlugin(),
            new VueLoaderPlugin(),
        ],
    };

    if (argv.mode !== 'production') {
        config.module.rules.push({
            test: /\.s?css$/,
            use: [
                MiniCssExtractPlugin.loader,
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: true,
                    },
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        ident: 'postcss',
                        plugins: [autoprefixer({})],
                        sourceMap: true,
                    },
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sourceMap: true,
                        precision: 10,
                    },
                },
            ],
        });
    }

    if (argv.mode === 'production') {
        config.module.rules.push({
            test: /\.s?css$/,
            use: [
                MiniCssExtractPlugin.loader,
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: true,
                    },
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        ident: 'postcss',
                        plugins: [
                            cssnano({
                                preset: 'default',
                            }),
                            autoprefixer({}),
                        ],
                        sourceMap: true,
                    },
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sourceMap: true,
                        precision: 10,
                    },
                },
            ],
        });

        config.module.rules.push({
            test: /\.svg$/,
            enforce: 'pre',
            use: [
                {
                    loader: 'svgo-loader',
                    options: {
                        precision: 2,
                        plugins: [
                            {
                                removeViewBox: false,
                            },
                        ],
                    },
                },
            ],
        });
    }

    return config;
};