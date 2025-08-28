import Vue from 'vue'
import Vuex from 'vuex'
import createPersistedState from 'vuex-persistedstate';     //数据持久化
import SecureLS from "secure-ls";                           //数据加密插件
const ls = new SecureLS({isCompression: false});
Vue.use(Vuex);

import TaskIDList from '@/store/modules/taskid.js';       //模块任务id
// import Routes from '@/store/modules/routes.js';       //添加的动态路由表

const store = new Vuex.Store({
  state: {
    WebState: 0,     //服务器状态
    TasksCount: 0,      //任务状态
    LocalState: 0,     //本地服务器状态
    LocalTasksCount: 0,      //本地任务状态
    UserInfo: '',                //用户登陆信息资料
    Token: '',                   //用户请求验证token
    ToolsRouterList: [],       //权限内的工具表
  },
  plugins: [
    createPersistedState({
      key: 'magical',
      storage: {
        getItem: key => ls.get(key),
        setItem: (key, value) => ls.set(key, value),
        removeItem: key => ls.remove(key)
      },
      reducer(val) {
        return {
          // 只储存state中的Token、UserInfo、ToolsRouterList
          Token: val.Token,
          UserInfo: val.UserInfo,
          ToolsRouterList:val.ToolsRouterList
        }
      }
    })
  ],
  getters: {},
  mutations: {
    //用户登陆信息资料
    SetUserInfo(state, info) {
      state.UserInfo = info;
    },
    //用户请求验证token
    SetToken(state, key) {
      state.Token = key;
    },

    //服务器状态(info页面)
    SetState(state, mode) {
      state.WebState = mode;
    },
    //任务数量(info页面)
    SetCount(state, num) {
      state.TasksCount = num;
    },
    //服务器状态(info页面)
    SetLocalState(state, mode) {
      state.LocalState = mode;
    },
    //任务数量(info页面)
    SetLocalCount(state, num) {
      state.LocalTasksCount = num;
    },
    //增加路由表
    AddTools(state, list) {
      state.ToolsRouterList = list;
    },
    // 移除
    RemoveTools(state) {
      state.ToolsRouterList = [];
    }
  },
  actions: {},
  modules: {
    TaskIDList,             //任务ID缓存
    // Routes                  //动态路由表

  }


});


export default store;

