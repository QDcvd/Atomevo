<template>
  <div id="app">
    <!--<transition name="el-fade-in-linear">-->
    <!--<el-collapse-transition>-->
      <router-view/>
    <!--</el-collapse-transition>-->
    <!--</transition>-->
  </div>
</template>

<script>
  export default {
    name: 'app',
    components: {},
    data() {
      return {}
    },
    computed:{
      // ...mapState({
      //   Token:state => state.Token,              //监听登陆控制路由
      //   Info:state=>state.UserInfo
      // })
    },
    watch: {

    },
    created() {
      //初始化语言
      let SetLang = localStorage.getItem('lang');
      if(SetLang){
        this.$i18n.locale = SetLang;
      }else {
        localStorage.setItem('lang', this.$i18n.locale);
      }

    },
    mounted() {

    },
    destroyed() {


    },
    methods: {}
  }
</script>

<style>
  #app {
    font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", "微软雅黑", Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;

  }
</style>
