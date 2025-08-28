import Vue from 'vue';
import VueI18n from 'vue-i18n';         //多国语言
// import enLocale from 'element-ui/lib/locale/lang/en';
// import zhLocale from 'element-ui/lib/locale/lang/zh-CN';
import zh_CN from '@/lang/zh-CN.js';
import en_US from '@/lang/en-US.js';


Vue.use(VueI18n);

// const messages = {
//   'en-US': {
//     message: import('@/lang/en-US.js'),
//     enLocale // 或者用 Object.assign({ message: 'hello' }, enLocale)
//   },
//   'zh-CN': {
//     message: import('@/lang/zh-CN.js'),
//     zhLocale // 或者用 Object.assign({ message: '你好' }, zhLocale)
//   }
// };


const i18n = new VueI18n({
  locale: 'zh-CN', // 语言标识
  messages: {
    'zh-CN':zh_CN,
    'en-US':en_US
  }
});

// const i18n = new VueI18n({
//   locale: 'zh-CN', // set locale
//   messages, // set locale messages
// });

export default i18n;
