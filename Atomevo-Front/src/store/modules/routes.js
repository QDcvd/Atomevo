const Routes = {
  namespaced: true,
  state: {
    // ToolsRouterList: [],       //权限内的工具表
    UserRouterList: [],        //权限内的用户操作表
  },
  mutations: {
    AddUser(state, list) {
      state.UserRouterList = list;
    },
    RemoveRouter(state) {
      state.ToolsRouterList = [];
      state.UserRouterList = [];
    }
  }
};

export default Routes;
