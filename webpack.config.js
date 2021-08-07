const path = require('path');
const webpack = require('webpack');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');

var config = {
  watch: false,
  stats: "minimal",
  entry: {
      app: [
        '@babel/polyfill',
        path.resolve('app/client/js/app.js'),
        path.resolve('app/client/scss/app.scss'),
      ],
      cms: [
        '@babel/polyfill',
        path.resolve('app/client/js/cms/cms.js'),
        path.resolve('app/client/scss/cms.scss'),
      ]
  },
  output: {
    jsonpFunction: 'webpackjsnp',
    filename: '[name].js',
    path: path.resolve('app/client/dist/'),
    pathinfo: false,
  },
  module: {
    rules: [
      {
        enforce: 'pre',
        include: path.resolve('src'),
        test: /\.(js|vue)$/,
        loader: 'eslint-loader',
        exclude: /node_modules/
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              presets: [
                [
                  '@babel/preset-env',
                  {
                    debug: false,
                    useBuiltIns: 'usage',
                    corejs: 3,
                  }
                ]
              ],
              plugins: [
                ['@babel/plugin-proposal-decorators', { decoratorsBeforeExport: true }],
                ['@babel/plugin-proposal-class-properties'],
                ['@babel/transform-runtime'],
                ['@babel/plugin-transform-arrow-functions'],
              ],
            },
          },
          'eslint-loader',
        ]
      },
      {
        test: /\.vue$/,
        use: 'vue-loader',
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false
            }
          },
          'postcss-loader',
          {
            loader: 'sass-loader',
            options: {
              additionalData: `
                @import "app/client/scss/variables";
              `,
            }
          }
        ]
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              url: false
            }
          },
        ]
      },
    ],
  },
  resolve: {
    extensions: ['.js', '.vue', '.json'],
    alias: {
      vue: 'vue/dist/vue.js',
    }
  },
  plugins: [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].css'
    })
  ],
};

module.exports = (env, argv) => {
  config.optimization = {
    splitChunks: {
      cacheGroups: {
        commons: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendor',
          chunks: 'initial',
        },
      },
    },
  };

  if (argv.mode === 'development') {
    config.devtool = 'inline-source-map';
  }

  if (argv.mode === 'production') {
    config.optimization = {
      ...config.optimization,
      minimize: true,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            output: {
              comments: false,
            },
          },
          extractComments: false,
        }),
      ],
    };
  }

  return config;
};
