<template >
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">PROCHECK
                <div class="tool-declaration">
                    {{$t('tools.Procheck')}}
                </div>
            </div>
            <el-steps :active="StepIndex" simple finish-status="success">
                <el-step :title="$t('content.step1')" icon="el-icon-document-add"></el-step>
                <el-step :title="$t('content.step2')" icon="el-icon-monitor"></el-step>
                <el-step :title="$t('content.step3')" icon="el-icon-loading"></el-step>
                <el-step :title="$t('content.step4')" icon="el-icon-magic-stick"></el-step>
            </el-steps>
        </div>
        <el-row :gutter="20">
            <el-col :lg="24" :xl="12">
                <div class="mag-step-left">
                    <div>{{$t('content.project_files')}}</div>
                    <UpLoadFile FormatType=".pdb" UseType="procheck" @GetIDList="GetFileID"></UpLoadFile>

                </div>
                <div class="mag-step-left">
                    <div>
                        <el-button type="primary" size="small" @click="RunPROCHECK"
                                   :disabled="StepIndex==1?false:true">  {{$t('btn.run')}}
                        </el-button>
                    </div>

                </div>
            </el-col>
            <el-col :lg="24" :xl="12">

                <div class="mag-step-right"
                     v-loading="resLoad"
                     element-loading-text="等待计算结果中...">
                    <div>{{$t('content.handle_result')}} <i :class="isResult?'el-icon-success':'el-icon-error'"></i></div>
                    <!--成功的结果-->
                    <div v-if="isResult" class="res-success">
                        <download-table :DownData="FileData"/>
                    </div>
                    <!--错误的结果-->
                    <div v-else class="res-error">
                                    <pre>
                                        error



                                    </pre>
                    </div>
                </div>
            </el-col>


        </el-row>


    </div>
</template>

<script>
  import { mapState } from 'vuex';
  import DownloadTable from '@/components/DownLoadTable';
  import UpLoadFile from '@/components/UploadFile';
  // import info from '@/info';

  export default {
    name: "procheck",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      return {
        // info: info.PROCHECK,              //注释
        modeBtn: false,                  //模式切换开关
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        loadtext: '正在推送任务...',        //动画2中等待推送文字内容
        FileID: [],                      //文件id
        isIndeterminate: true,          //效果图标
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
        ],


      };
    },
    computed: {
      ...mapState({
        TaksID:state => state.TaskIDList.PROCHECK
      }),
    },
    watch: {
      TaksID(newRes, oldRes) {

        this.GetDown(newRes);
      },
      FileID(newRes,oldRes){
        //判断步骤条
        if(newRes.length>0){
          this.StepIndex = 1
        }else{
          this.StepIndex = 0
        }
      }
    },
    created() {
      // console.log()

      // this.ShowTips();
    },
    activated() {
      this.resLoad = false;
      this.FileData = [];   //再次触发应当清空文件列表
    },
    destroyed() {

    },
    methods: {
      //显示版权信息
      GetFileID(ids){
        this.FileID = ids;
      },

      // 执行计算操作
      /*
      @Param

      */
      RunPROCHECK() {

        this.$api.GainPROCHECK(this.FileID.toString()).then(res => {
          // console.log(res)
          this.$notify({
            title:this.$t('info.create_task'),
            iconClass: 'el-icon-upload',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify2">${res.msg}</strong>`,
          });
          this.StepIndex = 2;
          this.resLoad = true;
        }).catch(err => {
          this.$notify({
            title: this.$t('info.error2'),
            iconClass: 'el-icon-error',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify3">${err.msg}</strong>`,
          });

        })

      },

      //获取下载
      GetDown(id) {
        if (id == null) {
          // this.FileData = [];
        } else {
          let param = {
            id,
            use: this.$route.name
          };
          this.$api.GainFileList(param).then(res => {
            this.StepIndex = 4;
            this.resLoad = false;
            // console.log('任务结果',res.files)
            this.FileData = res.files;

            this.$store.commit(`TaskIDList/${this.$route.name}_ID`, null);
          })
        }
      },





    }
  }
</script>

<style lang="less">
    .res-error {
        font-size: 0.3rem;
        height: 45vh;
        overflow: auto;
    }


</style>
