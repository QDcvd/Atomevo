process.env.VUE_APP_VERSION = require('./package.json').version;      //引入版本号
const path = require('path');
function resolve(dir) {
  return path.join(__dirname, dir)
}

module.exports = {
  outputDir:`dist${process.env.VUE_APP_VERSION}`,
  lintOnSave: false,
  publicPath: '/',
  css: { 
    // modules: true, 
    extract: true, 
    sourceMap: process.env.NODE_ENV === 'development', // 是否在构建样式地图，false将提高构建速度
    loaderOptions: { // css预设器配置项
      sass: {
        data: ''//`@import "@/assets/scss/mixin.scss";`
      },
    }
  },
  productionSourceMap: process.env.NODE_ENV === 'development',              //productionSourceMap false 不打包map文件
  chainWebpack: config => {
    // 移除 prefetch 插件
    config.plugins.delete('preload');
    config.plugins.delete('prefetch');
    config.optimization.minimize(process.env.NODE_ENV !== 'development');
    config.optimization.splitChunks({
      chunks: 'all'
    });

  },
  configureWebpack: {
    output:{                                                                //注入版本号
      filename: `js/[name]-${process.env.VUE_APP_VERSION}-[hash:5].min.js`,
      chunkFilename: `js/[name]-${process.env.VUE_APP_VERSION}-[hash:5].min.js`
    },
    resolve: {
      alias: {
        '@': resolve('src'),
        '~':resolve('static')
      }
    }
  },
  devServer: {

    host: "0.0.0.0",       //可以自动配置局域网0.0.0.0
    port: 4333,       //端口
    https: false,   //开启https
    open: true,        //编译后自动打开
    proxy: {
      "/magapi": {
        target: 'https://atomevo.com/magapi/',
        // target:'192.168.10.18',
        ws: true,         //proxy websockets
        changeOrigin: true,      //跨域
        secure: false,  //https 要用false
        pathRewrite: {
          '^/magapi': ''  //请求的时候使用
        }
      }
    }
  }


};
