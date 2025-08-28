<template>
  <div>
    <el-tabs v-model="activeName" @tab-click="handleClick">
      <el-tab-pane v-for="(item,index) in routerlist" :key="index" :label="item.meta.title"
                   :name="item.name"></el-tab-pane>
      <!--<el-tab-pane label="配置管理" name="config">配置管理</el-tab-pane>-->
    </el-tabs>
    <router-view></router-view>
  </div>
</template>

<script>
  export default {
    name: "home",
    data() {
      return {
        roles: [],
        routerlist:[],
        routesHub:[                   //账户设置 路由位置
          {
            name: 'account-config',
            meta: {
              title: '权限设置',
              roles: ['admin']
            }
          },
          {
            name: 'account-userinfo',
            meta: {
              title: '账户设置',
              roles: ['user']
            }
          }
        ],
        activeName: '',

      }
    },
    beforeCreate() {

    },
    created() {
      this.GainRouter();
      //获得路由允许列表
    },
    methods: {
      // 切换路由
      handleClick() {
        this.$router.push({path:`/`+this.activeName})
      },
      //获取权限列表允许的路由
      GainRouter() {
        let roles = this.$store.state.UserInfo.roles;           //载入权限
        // console.log('--->',roles)
        this.routerlist =this.routesHub.filter(item=>{
          return roles.includes(item.meta.roles.toString())
        });

        this.activeName = this.routerlist[0].name;
        this.$router.push({path:`/`+this.activeName})
      }

    }
  }
</script>

<style scoped>

</style>
