// 引入页面
const Login = () => import(/* webpackChunkName: "login" */ '@/pages/common/login/login');
const Iserror = () => import(/* webpackChunkName: "iserror" */ '@/pages/common/iserror/iserror');            //错误导航
const Register = () => import(/* webpackChunkName: "register" */ '@/pages/common/register/register');
const Setpass = () => import(/* webpackChunkName: "setpass" */ '@/pages/common/setpass/setpass');


const ComRoutes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: {
      title: '登陆'
    }
  },
  {
    path: '/404',
    name: 'iserror',
    component: Iserror,
    meta: {
      title: '登陆'
    }
  },
  {
    path: '/register',
    name: 'register',
    component: Register,
    meta: {
      title: '用户申请'
    }
  },
  
  {
    path: '/setpass',
    name: 'setpass',
    component: Setpass,
    meta: {
      title: '找回密码'
    }
  },
];

export default ComRoutes;
