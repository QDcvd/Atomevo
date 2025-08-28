<template>
  <div class="mag-background">

    <el-container>
      <el-aside width="250">
        <div style="height: 3rem;margin: 0.5rem">
          <div style="height: 95%;text-align: center">
            <img class="logo-img" :src="LogoImg"/>
          </div>
          <div style="font-size: 0.1rem;user-select:none;text-align: center">
            <span>ver {{InfoLogs.ver}}</span>
          </div>

        </div>

        <el-menu
            :default-active="this.$route.path.split('/')[1]"
            class="el-menu-vertical-demo"
            @select="changeMenu"
            background-color="#ebf1f5"
            text-color="#35495e"
            active-text-color="#39bae9"
            :router="true"

        >
          <el-menu-item index="info">
            <i class="iconfont icon_mag-1"></i>
            <span slot="title">{{$t('menu.info')}}</span>
          </el-menu-item>

          <!--<el-menu-item index="dataStatistics">-->
          <!--  <i class="iconfont icon_mag-1"></i>-->
          <!--  <span slot="title">{{$t('menu.dataStatistics')}}</span>-->
          <!--</el-menu-item>-->

          <!--动态菜单 TODO: 这里菜单图标的hack操作原因请看项目readme.md-->
          <el-menu-item :index="item" v-for="(item,index) in MenuList" :key="index">
            <i :class="`iconfont icon_mag-${index+2}`" v-show="index <= 22"></i>
            <img
                :src="menuIcon"
                v-show="index> 22"
                style="margin-left: -0.1rem;width: 1.2rem;height: 0.9rem"/>
            <span
                slot="title"
                style="text-transform:capitalize;"
                :style="{marginLeft: index > 22 ? '0.4rem' : '0'}"
            >
              {{item}}
            </span>
          </el-menu-item>


<!--
            <el-menu-item index="modeller">
            <i class="iconfont icon_mag-19"></i>
            <span slot="title">modeller</span>
          </el-menu-item>     -->

          <!--<el-menu-item index="gmx">-->
          <!--<i class="iconfont icon_mag-21"></i>-->
          <!--<span slot="title">GMX</span>-->
          <!--</el-menu-item>-->


          <!--<el-menu-item index="ledock">-->
            <!--<i class="iconfont icon_mag-2"></i>-->
            <!--<span slot="title">LEDock</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="autodock">-->
            <!--<i class="iconfont icon_mag-3"></i>-->
            <!--<span slot="title">AutoDock4</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="autodock-vina">-->
            <!--<i class="iconfont icon_mag-4"></i>-->
            <!--<span slot="title">AutoDock-Vina</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="xscore">-->
            <!--<i class="iconfont icon_mag-5"></i>-->
            <!--<span slot="title">XScore</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="plip">-->
            <!--<i class="iconfont icon_mag-6"></i>-->
            <!--<span slot="title">PLIP</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="dssp">-->
            <!--<i class="iconfont icon_mag-7"></i>-->
            <!--<span slot="title">Dssp</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="procheck">-->
            <!--<i class="iconfont icon_mag-8"></i>-->
            <!--<span slot="title">PROCHECK</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="auto-martini">-->
            <!--<i class="iconfont icon_mag-9"></i>-->
            <!--<span slot="title">Auto-Martini</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="martinize">-->
            <!--<i class="iconfont icon_mag-10"></i>-->
            <!--<span slot="title">Martinize-Protein</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="xvg-to-csv">-->
            <!--<i class="iconfont icon_mag-11"></i>-->
            <!--<span slot="title">XVG2GSV</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="openbabel">-->
            <!--<i class="iconfont icon_mag-12"></i>-->
            <!--<span slot="title">OpenBabel</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="plants">-->
            <!--<i class="iconfont icon_mag-13"></i>-->
            <!--<span slot="title">Plants</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="mktop">-->
            <!--<i class="iconfont icon_mag-14"></i>-->
            <!--<span slot="title">Mktop</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="commol">-->
            <!--<i class="iconfont icon_mag-15"></i>-->
            <!--<span slot="title">Commol</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="tr-rosetta">-->
            <!--<i class="iconfont icon_mag-16"></i>-->
            <!--<span slot="title">trRosetta</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="g-mmpbsa">-->
            <!--<i class="iconfont icon_mag-17"></i>-->
            <!--<span slot="title">g_mmpbsa</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="g-mmpbsa-analysis">-->
            <!--<i class="iconfont icon_mag-18"></i>-->
            <!--<span slot="title">g_mmpbsa analysis</span>-->
          <!--</el-menu-item>-->
          <!--<el-menu-item index="gromacs">-->
            <!--<i class="iconfont icon_mag-19"></i>-->
            <!--<span slot="title">Gromacs model builder</span>-->
          <!--</el-menu-item>-->




          <!--<el-menu-item index="config" disabled="">-->
          <!--<i class="el-icon-set-up"></i>-->
          <!--<span slot="title">配置</span>-->
          <!--</el-menu-item>-->
        </el-menu>
      </el-aside>
      <el-main>

        <div class="mag-header">
          <el-row :gutter="20">
            <el-col :span="15">
              <!--<img class="logo-img" src="./image/all.png"/>-->
              <!--<div style="display: inline-block;font-size: 0.2rem">-->
              <!--<span>ver {{ver}}</span>-->
              <!--</div>-->
              <div class="talk-title" v-show="Onetalk">{{Onetalk.hitokoto}}<span
                  class="from">「 {{Onetalk.from}} 」</span></div>
            </el-col>
            <el-col :span="6">
              <div class="top-info-btn">
                <el-badge class="item">
                  <el-button type="warning" icon="el-icon-s-order" size="mini" circle
                             @click="ToLibrary" v-if="inLibrary"></el-button>
                </el-badge>
                <el-badge class="item">
                  <el-button type="primary" icon="el-icon-s-cooperation" size="mini" circle
                             @click="ToolHub"></el-button>
                </el-badge>
                <el-badge value="new" class="item" :hidden="OneSee">
                  <el-button type="info" icon="el-icon-message-solid" size="mini" circle
                             @click="OpenUp(0)"></el-button>
                </el-badge>
              </div>
            </el-col>
            <el-col :span="3">
              <div class="mag-user-panel">
                <el-dropdown size="medium">
                            <span class="el-dropdown-link">
                                <i class="el-icon-arrow-down el-icon--right"></i>
                                &nbsp;  {{$t('content.user_title')}}【 {{Realname}} 】
                                <i class="el-icon-user"></i>
                            </span>
                  <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item icon="el-icon-setting" @click.native="ToAccount">{{$t('content.config')}}
                    </el-dropdown-item>
                    <el-dropdown-item icon="el-icon-circle-close" @click.native="LoginOut">{{$t('content.logout')}}
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </el-dropdown>
              </div>
            </el-col>
          </el-row>
        </div>

        <!--<el-collapse-transition>-->

        <div class="mag-main">
          <transition name="fade-transform" mode="out-in">
            <keep-alive :max="3" :exclude="OutAlivecomponents">
              <router-view class="mag-main-view"/>
            </keep-alive>
          </transition>
        </div>

        <!--</el-collapse-transition>-->
      </el-main>
    </el-container>
    <up-info ref="infoTab"
             :InfoLogs="InfoLogs"
             @RedPoint="RedChange"/>
    <!--<float-menu v-if="inLibrary"/>  暂时关闭（等开发完毕）-->

    <div class="design-title2">@Designed by Magical-Team/2019-2020</div>
  </div>
</template>

<script>
  import info from '@/info';
  import UpInfo from '@/components/UpInfo';
  import {resetRouter} from '@/router/index';      // 重制路由表权限
  // import FloatMenu from '@/components/float_menu';
  import LogoImg from '../../../static/image/logo.png';
  import menuIcon from '../../../static/newIcon/menu_icon.png';


  export default {
    name: "index",
    components: {
      UpInfo,
      // FloatMenu,
    },
    data() {
      return {
        menuIcon,
        LogoImg,
        // MenuList:[],
        Onetalk: '',         //一言
        // FocusIndex:''
        // OpenEmail:true,           //邮箱接收结果
        // usermail:'',
        // timeclose: '',         //socket关闭控制
        inLibrary: false,            //库权限
        Realname: '',
        GaintaskID: '',            //socket推送的任务id
        InfoLogs: info.UpData,
        OneSee: true,                     //查看信息红色标签
        OutAlivecomponents: [               //不需要keep -alive
          'info',
          'home',
          'library',
          'UploadFile'
        ]
      }
    },
    watch: {},
    computed:{
      MenuList(){
        return this.$store.state.ToolsRouterList
      }
    },

    beforeCreate() {
      console.log(info.ConsoleIMG);                 //控制台图案
    },
    created() {
      this.Realname = this.$store.state.UserInfo.realname;
      this.inLibrary = this.$store.state.UserInfo.roles.includes('vip');       //是否显示【 库入口】
      // 设定超时关闭客户端链接

      if (this.$i18n.locale === 'zh-CN') {
        this.GetOne();    //载入一言
      }


      Notification.requestPermission().then(permission => {
        if (permission === 'denied') {
          this.$notify({
            title: this.$t('info.service_failure'),
            message: this.$t('info.service_failure_msg'),
            type: 'error',
            position: 'top-left'
          });
        }
      });         //询问允许 浏览器通知

    },
    mounted() {

      // console.log('检测登陆状态',this.$store.state.Token !== undefined);

      if (this.$store.state.Token !== undefined) {
        this.$socket.client.open(); //登陆后手动启动链接Socket 服务
        this.$socket.client.emit('token', this.$store.state.Token);       //触发socket登陆
      }
      // console.log('链接socket')
      //触发socket连接
    },
    //Socket.io 事件
    sockets: {
      connect(value) {
        // console.log('连接成功',value);
      },
      disconnect(value) {
        // console.log('断开链接',value);
      },

      message(value) {
        // console.log(value);
        this.$notify({
          title: '任务完成',
          iconClass: 'el-icon-share',
          dangerouslyUseHTMLString: true,
          message: `<strong class="mag-notify">${value.msg}</strong>`,
          onClick: () => {
            this.GoTools(value)
          }
        });
        this.Webnotification(value.msg);
        //获取应用的结果(工具页面自动判断VueX)
        if (value.resultCode === 1) {
          this.$store.commit(`TaskIDList/${value.use}_ID`, value.tasks_id);
        }
      },
      serverstate(value) {

        this.$store.commit("SetState", value.cpu);
        this.$store.commit("SetCount", value.taskscount);
      },
      localstate(value) {

        this.$store.commit("SetLocalState", value.cpu);
        this.$store.commit("SetLocalCount", value.taskscount);
      },
      reconnect() {
        // console.log('重新链接')
      },
      token(value) {
        if (value.token != this.$store.state.Token) {

          this.$router.go('/login');
          // this.$router.push({name: 'login'});
          this.$socket.client.close();
          this.$notify.error({
            title: this.$t('info.error'),
            message: '账号登陆状态失效'
          });
          // console.log('需要返回');
          this.$store.commit('SetUserInfo', '');
          this.$store.commit('SetToken', '');
          resetRouter();
        }
      }
    },
    //浏览器通知
    methods: {
      changeMenu(key, keyPath) {
        // console.log(key, keyPath);
        this.FocusIndex = key;
      },
      //更新模版传递回来参数
      OpenUp(type) {
        this.$refs.infoTab.OpenLog(type)
      },
      //跳转ToolHub
      ToolHub() {

        this.$router.push({path: '/toolhub'})

      },

      Webnotification(msg) {
        //发送通知
        let newNotify = function () {
          new Notification("计算任务结果:", {
            requireInteraction: true,            ///不自动关闭 通知
            silent: true,
            tag: "Magical",
            icon: "https://xiaoniangao-1256879753.cos.ap-guangzhou.myqcloud.com/magical_logo.png",
            body: msg
          });

        };
        //权限判断
        if (Notification.permission == "granted") {
          newNotify();
        } else {
          //请求权限
          Notification.requestPermission().then(function (perm) {
            if (perm == "granted") {
              newNotify();
            }
          })
        }

      },

      //跳转文件库
      ToLibrary() {
        this.$router.push({path: '/library'})
      },
      RedChange(res) {
        this.OneSee = res;
      },

      //去账户设置
      ToAccount() {

        if (this.$route.matched[1].name != 'home') {
          this.$router.push({name: 'account-home'})
        }

      },
      //退出登陆
      LoginOut() {

        this.$api.GainDisLogin().then(res => {
          this.$store.commit('SetUserInfo', '');
          this.$store.commit('SetToken', '');
          this.$router.push('/login');
          resetRouter();                //清除路由

          this.$notify.success({
            title: this.$t('info.success'),
            message: res.msg
          });

        });

      },

      //进入工具具体工具位置
      GoTools(res) {
        // console.log('----click',)
        // 可以使用路由拦截（后期修改
        this.$router.push({name: res.use, query: {taskID: res.tasks_id}});
      },
      GetOne() {
        //第三方请求在Catch接收数据

        this.$api.GainOne().then().catch(res => {
          this.Onetalk = res;
        })
      },

    }
  }
</script>

<style lang="less">
  //头部样式
  .iconfont {
    font-size: 1rem;
    margin-right: 0.5rem;
  }

  .design-title2 {
    position: absolute;
    text-align: center;
    font-size: 0.8rem;
    bottom: 0.2rem;
    margin-right: 1vh;
    right: 1rem;
    cursor: default;
    user-select: none;
  }

  .el-main {
    padding: 1rem;
    .mag-header {
      height: 1rem;
      .el-row {
        height: 100%;

        .el-col {
          position: relative;
          height: 100%;
        }

      }
      .talk-title {
        padding-left: 1rem;
        font-size: 0.5rem;
        position: absolute;
        width: 100%;
        overflow: hidden; //超出的文本隐藏
        text-overflow: ellipsis; //溢出用省略号显示
        white-space: nowrap; //溢出不换行

        .from {
          font-size: 0.1rem;
        }
      }
      .mag-user-panel {
        font-weight: bold;
        font-size: 0.9rem;
        height: 2rem;
        line-height: 1.5rem;
        position: absolute;
        margin: auto;
        top: 0;
        bottom: 0;
        right: 2rem;
        .el-dropdown-link {
          font-size: 0.7rem;
          user-select: none;
        }
      }
      .mag-set-mail {
        position: absolute;
        top: 25%;
      }

      .top-info-btn {
        position: absolute;
        top: -0.5rem;
        right: 0;
        .item {
          margin: 0 0.2rem;
        }
      }
    }
    .mag-main {
      background-color: #FFFFFF;
      border-radius: 0.3rem;
      overflow: hidden;
      margin: 1vh;
      box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
      .mag-main-view {
        height: calc(100vh - 7rem);
        overflow: auto;
        padding: 1rem;

      }
    }
  }

  .logo-img {
    height: 100%;
    object-fit: contain;
    pointer-events: none;
  }

  .el-icon-share {
    color: #67c23a;
  }

  .el-menu-vertical-demo {
    .el-menu-item {
      &:hover {
        border-radius: 0.2rem;
        background-color: #bce0f5 !important;

      }

      i {
        color: inherit;;
      }

    }

    .is-active {
      font-weight: bold !important;

      .iconfont {
        -webkit-text-stroke-width: 0.6px;
        -webkit-text-stroke-color: #35495e;
      }
    }

  }


</style>
