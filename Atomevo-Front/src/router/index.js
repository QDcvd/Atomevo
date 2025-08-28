import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);
import store from "@/store";
import NProgress from "nprogress"; // progress bar
import "nprogress/nprogress.css"; // progress bar
NProgress.configure({ showSpinner: false }); // 关闭默认圈圈

import ComRoutes from "./modules/common.routes.js"; //初始路由表
import IndexRoutes from "./modules/index.routes"; // 全局路由表
import UserRoutes from "./modules/user.routes.js"; //用户类
import ToolsRoutes from "./modules/tools.routes.js"; //工具类

const index = () =>
  import(/* webpackChunkName: "index" */ "@/pages/index/index"); //首页

const createRouter = () =>
  new VueRouter({
    //初始化路由
    mode: "history",
    routes: ComRoutes,
  });

const router = createRouter();

function resetRouter() {
  // 卸载登陆
  store.commit("RemoveTools"); //清除路由
  const newRouter = createRouter();
  router.matcher = newRouter.matcher;
}

function addRouter(list) {
  //添加路由
  resetRouter();
  store.commit("AddTools", list); //加入 VueX 中数据
  let RolesRouter = [
    //登陆后的路由表
    {
      path: "/index",
      name: "index",
      component: index,
      meta: {
        title: "首页",
        roles: ["user"],
      },
      children: [
        //index下路由表
        ...IndexRoutes,
        ...UserRoutes, //工具类 暂时不动态
      ],
    },
    { path: "*", redirect: "/404" },
  ];
  //筛选用户拥有的Tools 权限列表
  ToolsRoutes.forEach((item) => {
    list =
      Object.prototype.toString.call(list) === "[object Array]"
        ? list
        : Object.values(list);
    if (list.includes(item.name)) {
      RolesRouter[0].children.push(item);
    }
  });

  router.addRoutes(RolesRouter);
}

router.beforeEach((to, from, next) => {
  document.title = "Atomevo | " + to.meta.title; //标题
  let { roles } = store.state.UserInfo; //用户的权限列表

  if (roles) {
    if (to.path === "/" || to.path === "/login") {
      next("/info");
    } else {
      next();
    }
  } else {
    if (to.path === "/" || to.name === null) {
      next({ path: "/login" });
    } else {
      next();
    }
  }
});

router.onReady(() => {
  if (store.state.Token) {
    addRouter(store.state.ToolsRouterList);
  }
});

export { router, addRouter, resetRouter };
