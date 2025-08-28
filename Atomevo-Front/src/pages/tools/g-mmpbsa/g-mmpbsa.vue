<template xmlns="http://www.w3.org/1999/html">
  <div>
    <div class="mag-step">
      <div style="font-weight: bold;margin: 0.5rem">g_mmpbsa
        <div class="tool-declaration">
          {{$t('tools.GMmpbsa')}}
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
          <el-switch
              style="display: block;margin-bottom: 0.5rem"
              v-model="isZip"
              active-color="#67c23b"
              inactive-color="#39bae9"
              :active-text="this.$t('content.upload_zip')"
              :inactive-text="this.$t('content.upload_normal')"
              :disabled="RunMode === 2">
          </el-switch>
          <div v-show="isZip">
            <div>{{$t('content.upload_zip')}}</div>
            <UpLoadFile v-if="isZip" FormatType=".zip" UseType="package" @GetIDList="GetFileIDzip"
                        :Count="1" :MaxSize="100" :Mode="2"></UpLoadFile>
          </div>
          <div v-show="!isZip">
            <div>{{$t('content.project_files')}}</div>
            <UpLoadFile FormatType=".tpr,.xtc,.ndx" UseType="g_mmpbsa" @GetIDList="GetFileID" :MaxSize="20"></UpLoadFile>
          </div>
        </div>
        <div class="mag-step-left">
          <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
            <template slot="prepend">{{$t('content.down_name')}}</template>
          </el-input>
          <el-divider></el-divider>
          <!--<br/>-->
          <el-button size="small" @click="drawer = true" type="primary" icon="el-icon-files">{{$t('btn.excel')}}
          </el-button>
          <div style="margin-top: 1rem">
            <el-button type="primary" size="small" @click="RunGmmpbsa" :disabled="StepIndex==1?false:true">
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
    <el-drawer
        :with-header="false"
        :visible.sync="drawer"
        direction="rtl"
        size="70%"
        :before-close="handleClose">
      <excel-edit ModeName="g_mmpbsa" @HandleExcel="GetExcelData"/>
    </el-drawer>

  </div>
</template>

<script>
  import { mapState } from 'vuex';
  import DownloadTable from '@/components/DownLoadTable';
  import UpLoadFile from '@/components/UploadFile';
  import ExcelEdit from '@/components/ExcelEdit';
  // import info from '@/info';

  export default {
    name: "g_mmpbsa",
    components: {
      DownloadTable,
      UpLoadFile,
      ExcelEdit
    },
    data() {
      return {
        // info: info.g_mmpbsa,             //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        FileID: [],                      //文件id
        drawer: false,                       //文件导入弹框
        ExcelConfig: [],                     //导入的Excel参数
        ZipName:'',                     //压缩包名字
        isIndeterminate: true,          //效果图标
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
        ],
        isZip:false,
        RunMode: 1,        //1 云服务器 ，2线下服务器
        FileZipID:''

      };
    },
    computed: {
      ...mapState({
        TaksID:state => state.TaskIDList.g_mmpbsa
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
      },
      FileZipID(newRes, oldRes) {
        if (newRes) {
          this.StepIndex = 1;
        } else {
          this.StepIndex = 0;
        }
      },
      isZip(newRes, oldRes) {
        if (newRes) {
          this.FileZipID = '';
          this.StepIndex = 0;
        } else {
          this.FileID = [];
          this.StepIndex = 0;
        }
      },
      RunMode(newRes, oldRes) {
        if (newRes === 2) {
          this.isZip = true;
        } else {
          this.isZip = false;
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
      GetFileIDzip(md5id) {
        this.FileZipID = md5id
        // console.log(md5id,'获得GetFileIDzip')
      },

      RunGmmpbsa() {
        let Params = {
          ids: this.FileID.toString(),
          configure: JSON.stringify(this.ExcelConfig),
          down_name: this.ZipName
        };
        if (this.isZip) {
          Params.zip_id = this.FileZipID;
        } else {
          Params.ids = this.FileID.toString()
        }

        this.$api.GainGmmpbsa(Params).then(res => {
          // console.log(res)
          this.$notify({
            title: this.$t('info.create_task'),
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

      handleClose() {
        this.drawer = false;
        // this.$message('取消参');
      },
      GetExcelData(data) {
        this.ExcelConfig = data;
        this.drawer = false;
        this.$message({
          message:this.$t('info.save_param'),
          type: 'success'
        });
      }

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
