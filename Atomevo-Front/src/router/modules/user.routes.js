const AccountHome = () => import (/* webpackChunkName: "account-home" */ '@/pages/user/account/home');                        //账户设置——首页
const AccountConfig = () => import (/* webpackChunkName: "account-config" */ '@/pages/user/account/config');                  //账户设置——管理员权限
const AccountUserinfo = () => import (/* webpackChunkName: "account-userinfo" */ '@/pages/user/account/userinfo');            //账户设置——普通用户设置
const Info = () => import(/* webpackChunkName: "info" */ '@/pages/user/info/info');
const Library = () => import(/* webpackChunkName: "library" */ '@/pages/user/library/library');
const Toolhub = () => import(/* webpackChunkName: "toolhub" */ '@/pages/user/toolhub/toolhub');

const UserRoutes = [
  {
    path: '/account-home',
    name: 'account-home',
    component: AccountHome,
    children: [                   //账户设置 路由位置
      {
        path: '/account-config',
        name: 'account-config',
        component: AccountConfig,
        meta: {
          title: '权限设置',
          roles: ['admin']
        }
      },
      {
        path: '/account-userinfo',
        name: 'account-userinfo',
        component: AccountUserinfo,
        meta: {
          title: '账户设置',
          roles: ['user']
        }
      }
    ]
  },
  {
    path: '/info',
    name: 'info',
    component: Info,
    meta: {
      title: '通知中心',
      roles: ['user']
    }
  },

  {
    path: '/library',
    name: 'library',
    component: Library,
    meta: {
      title: '文件库',
      roles: ['vip']
    }
  },
  {
    path: '/toolhub',
    name: 'toolhub',
    component: Toolhub,
    meta: {
      title: 'ToolHub',
      roles: ['user']
    }
  }
];

export default UserRoutes;
