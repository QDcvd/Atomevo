<template >
  <div>
    <div class="mag-step">
      <div style="font-weight: bold;margin: 0.5rem">Tksa
        <div class="tool-declaration">
          <!--{{$t('tools.Tksa')}}-->
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
          <UpLoadFile FormatType=".pdb" UseType="tksa" @GetIDList="GetFileID" :Count="1"></UpLoadFile>
        </div>
        <div class="mag-step-left">
          <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
            <template slot="prepend">{{$t('content.down_name')}}</template>
          </el-input>
          <div style="margin: 1rem"></div>
          <el-form ref="SelForm" label-width="5rem" size="mini" :model="SelForm">
            <el-form-item label="ph" prop="ph">
              <el-input-number v-model="SelForm.ph" controls-position="right"
                               placeholder="x"></el-input-number>
            </el-form-item>
            <el-form-item label="t" prop="t">
              <el-input-number v-model="SelForm.t" controls-position="right"
                               placeholder="x"></el-input-number>
            </el-form-item>
          </el-form>
          <div style="margin-top: 1rem">
            <el-button type="primary" size="small" @click="RunTksa" :disabled="StepIndex==1?false:true">
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
    name: "tksa",
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
        SelForm:{
          ph:7.0,
          t:300
        }
      };
    },
    computed: {
      //任务id
      ...mapState({
        TaksID:state => state.TaskIDList.Tksa
      }),
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
      //版权信息


      //上传文件
      GetFileID(ids){
        this.FileID = ids;
      },

      RunTksa() {
        let Params = {
          ids: this.FileID.toString(),
          down_name: this.ZipName,
          ph:this.SelForm.ph,
          t:this.SelForm.t
        };
        this.$api.GainTksa(Params).then(res => {
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
