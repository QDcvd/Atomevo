<template>
    <el-dialog
            :title="$t('info.updata_log')"
            :visible.sync="UpDataInfoBtn"
            width="25%"
    >
        <div class="info-main">
            <div class="time">
                <span>时间: {{InfoLogs.time}}</span>
                &nbsp;
                <span>版本号: {{InfoLogs.ver}}</span>
            </div>
            <div class="add">
                <div class="title">新增功能：</div>
                <div v-for="(item,index) in InfoLogs.add" :key="index" class="font">
                    {{index+1}}、{{item}}。
                </div>

            </div>
            <div class="fix">
                <div class="title">修复问题:</div>
                <div v-for="(item,index) in InfoLogs.fix" :key="index" class="font">
                    {{index+1}}、{{item}}。
                </div>
            </div>
        </div>
        <div style="text-align: center;margin-top: 1rem">
            <el-button type="primary" size="mini" @click="OpenLog(1)">{{$t('btn.confirm')}}</el-button>
        </div>
    </el-dialog>
</template>

<script>
  export default {
    name: "UPInfo",
    props:{
      InfoLogs:{                //更新的日志文件
        type:Object,
        default:()=>{
            return{
              ver:'0.0.0',
              time:'--',
              add:[],
              fix:[],
            }
        }
      }
    },
    data(){
      return {
        UpDataInfoBtn:false,        //显示状态
        OneSee:false,
      }
    },
    methods:{

      //获取打开更新日志窗口
      OpenLog(type) {
        if (type == 0) {           //点击打开
          this.UpDataInfoBtn  = true;
        } else if (type == 1) {      //点击我知道了
          this.UpDataInfoBtn = false;
          this.OneSee  = true;
          this.$emit('RedPoint',this.OneSee);
          let nowinfo = JSON.parse(localStorage.getItem('config'));
          nowinfo.sw = false;
          localStorage.setItem('config', JSON.stringify(nowinfo));
        }

      }
    },
    mounted(){
      // =====================更新弹出框位置
      if (!localStorage.getItem('config')) {                //初始化配置资料
        let conval = {
          ver: this.InfoLogs.ver,                    //初始化版本号
          sw: true                                     //可以允许自动弹出
        };
        localStorage.setItem('config', JSON.stringify(conval));            //创建用户自定义设置
      } else {
        let nowinfo = JSON.parse(localStorage.getItem('config'));
        if (nowinfo.ver !== this.InfoLogs.ver) {              //不是最新版本
          nowinfo.ver = this.InfoLogs.ver;
          nowinfo.sw = true;
          localStorage.setItem('config', JSON.stringify(nowinfo));         //更新配置
        }
      }
      this.OneSee = !JSON.parse(localStorage.getItem('config')).sw;
      this.$emit('RedPoint',this.OneSee);
      this.UpDataInfoBtn = JSON.parse(localStorage.getItem('config')).sw;   //主动显示同步缓存的记录
      // =====================更新弹出框位置============
    }
  }
</script>

<style lang="less">
    .info-main {

        height: 12rem;
        font-size: 0.7rem;
        border: 1px solid #a5d2ff;
        padding: 1rem 0.5rem;
        border-radius: 0.3rem;
        overflow: auto;
        .time {
            font-weight: bold;
            text-align: center;
        }
        .add {
            margin: 1rem 0;
            color: #359dc6;
            .title {
                font-weight: bold;

            }
            .font {
                padding-left: 1rem;
            }
        }
        .fix {
            margin: 1rem 0;
            color: #67c23b;
            .title {
                font-weight: bold;
            }
            .font {
                padding-left: 1rem;
            }
        }
    }
</style>
