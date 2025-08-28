<template>
    <div>
        <div class="mag-step">
        <!-- //需要修改模版的名字Aaabb -->
            <div style="font-weight: bold;margin: 0.5rem">G09
                <div class="tool-declaration">
                <!-- //需要修改模版的名字Aaabb -->
                    <!-- {{$t('tools.Aaabb')}} -->
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
                  <div style="margin: 0.5rem 0">
                    <el-radio-group v-model="RunMode" size="mini" :disabled="!RunSetMode">
                      <el-radio :label="1" border>Normal Server</el-radio>
                      <el-radio :label="2" border>Advanced Server</el-radio>
                    </el-radio-group>
                  </div>
                    <div>{{$t('content.project_files')}}</div>

                    <!-- // FormatType为文件支持的格式'.pdb'，UseType为执行的工具名称'Aaabb' -->
                    <UpLoadFile FormatType=".gjf" UseType="gzeronine" @GetIDList="GetFileID" :Count="1"></UpLoadFile>
                </div>
                <div class="mag-step-left">
                    <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                        <template slot="prepend">{{$t('content.down_name')}}</template>
                    </el-input>
                    <div style="margin-top: 1rem">
                        <el-button type="primary" size="small" @click="Runaaabb" :disabled="StepIndex==1?false:true">
                            {{$t('btn.run')}}
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

  export default {
    //注意全部小写 aaabb  
    name: "gzeronine",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      return {
        // info: info.dssp,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        FileID: [],                      //文件id
        ZipName:'',                     //压缩包名字
        isIndeterminate: true,          //效果图标
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
          
        ],
        RunMode: 1,
      };
    },
    computed: {
      //任务id
      ...mapState({
          //注意首字母大写 Aaabb
        TaksID:state => state.TaskIDList.Aaabb
      }),
       RunSetMode() {
        let {roles} = this.$store.state.UserInfo;
        return roles.includes('vip')
      },
    },
    watch: {
      TaksID(newRes, oldRes) {
        // console.log('新的任务id',newRes);
        // console.log('old task',oldRes);
        this.GetDown(newRes);
      },
      FileID(newRes,oldRes){
        //判断步骤条
        if(newRes.length>0){
          this.StepIndex = 1
        }else{
          this.StepIndex = 0
        }
      },
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
      //版权信息


      //上传文件
      GetFileID(ids){
        this.FileID = ids;
      },

      Runaaabb() {
        let Params = {
          ids: this.FileID.toString(),
          down_name: this.ZipName,
          mode: this.RunMode,
        };
        //模版方法名建议为Gain+aaabb
        this.$api.GainGzeronine(Params).then(res => {
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
            title:this.$t('info.error2'),
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