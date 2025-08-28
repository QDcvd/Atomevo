import Vue from 'vue';
import App from './App.vue';

//引入element
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-chalk/index.css';

import i18n from '@/lang/lang.js';    //多国语言
Vue.use(ElementUI,{
  i18n: (key, value) => i18n.t(key, value)
});





//全局样式
import '@/assets/main.less';
//引入图标
import '../static/iconfont/iconfont.css';
import '../static/iconfont2/iconfont.js';   //Symbol  (文件分类图标
import '../static/iconfont3/iconfont.js';   //Symbol  (文件分类图标

//页面Rem适配
import '@/config/screen.js';
//引入router
import {router} from '@/router/index.js';
//api接口
import api from '@/api/api.js';

Vue.prototype.$api = api;
//md5加密
import md5 from 'crypto-js/md5';

Vue.prototype.$md5 = md5;


//Vux模块
import store from '@/store';
//nprogress 进度条模块


//socket服务
import VueSocketIOExt from 'vue-socket.io-extended';
import io from 'socket.io-client';

const socket = io('https://atomevo.com',{
  autoConnect: false,
  path: '/socket'
});

// const socket = io('https://w.magical.liulianpisa.top/',{
//   autoConnect: false
// });

Vue.use(VueSocketIOExt, socket);



//v-chart图表
import VeLine from 'v-charts/lib/pie.common';
import VeLine2 from 'v-charts/lib/bar.common';
import VeLine3 from 'v-charts/lib/line.common';
import VeLine4 from 'v-charts/lib/histogram.common';
import 'v-charts/lib/style.css';

Vue.component(VeLine.name, VeLine);
Vue.component(VeLine2.name, VeLine2);
Vue.component(VeLine3.name, VeLine3);
Vue.component(VeLine4.name, VeLine4);


Vue.config.productionTip = false;

new Vue({
  i18n,
  router,
  store,
  render: h => h(App),
}).$mount('#app');
