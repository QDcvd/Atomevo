<template>
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">LEDock
                <div class="tool-declaration">
                    {{$t('tools.Ledock')}}
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
            <el-col :lg="24" :xl="14">
                <div class="mag-step-left">
                    <div style="margin: 0.5rem 0">
                        <el-radio-group v-model="RunMode" size="mini" :disabled="!RunSetMode">
                            <el-radio :label="1" border>Normal Server</el-radio>
                            <el-radio :label="2" border>Advanced Server</el-radio>
                        </el-radio-group>
                    </div>
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
                        <div>{{$t('content.ligand_receptor_file')}}</div>
                        <UpLoadFile v-if="!isZip" FormatType=".mol2,.pdb" UseType="ledock"
                                    @GetIDList="GetFileID"></UpLoadFile>
                    </div>
                </div>
                <div class="mag-step-left">


                    <div style="width: 60%">
                        <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                            <template slot="prepend">{{$t('content.down_name')}}</template>
                        </el-input>
                        <div style="margin: 15px 0;"></div>
                        <el-button size="small" @click="drawer = true" type="primary" icon="el-icon-files">{{$t('btn.excel')}}
                        </el-button>
                        <div style="margin: 15px 0;"></div>
                        <!--修改部分-->
                        <!--<el-button size="small" @click="drawer = true" type="primary"  icon="el-icon-files">导入Excel参数</el-button>-->
                        <div>
                            <el-checkbox v-model="xscore">{{$t('content.handle_xscore')}}</el-checkbox>
                        </div>
                        <div style="margin: 15px 0;"></div>
                    </div>
                    <div>

                        <el-button type="primary" size="small" @click="RunDock"
                                   :disabled="StepIndex==1?false:true">  {{$t('btn.run')}}
                        </el-button>
                    </div>
                </div>
            </el-col>
            <el-col :lg="24" :xl="10">

                <div class="mag-step-right"
                     v-loading="resLoad"
                     element-loading-text="等待计算结果中(预计10分钟)...">
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
            <excel-edit ModeName="LeDock" @HandleExcel="GetExcelData"/>
        </el-drawer>
    </div>
</template>

<script>
  import {mapState} from 'vuex';
  import DownloadTable from '@/components/DownLoadTable';
  import UpLoadFile from '@/components/UploadFile';
  import ExcelEdit from '@/components/ExcelEdit';
  // import info from '@/info';


  export default {
    name: "ledock",
    components: {
      DownloadTable,
      ExcelEdit,
      UpLoadFile
    },
    data() {
      return {
        // info: info.ledock,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        ZipName: '',                     //zip文件名
        FileID: [],                      //配体文件id+受体文件id
        isZip: false,                    //zip模式 二选一
        FileZipID: '',                       //压缩包 md5
        isIndeterminate: true,          //效果图标
        // FileList1: [],                   //文件列表1
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
        ],
        drawer: false,
        ExcelConfig: [],                     //导入的Excel参数
        RunMode: 1,        //1 云服务器 ，2线下服务器
        xscore:true,
      };

    },
    computed: {
      //任务id
      ...mapState({
        TaksID: state => state.TaskIDList.LEDock
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
      FileID(newRes, oldRes) {
        //判断步骤条
        if (newRes.length > 0) {
          this.StepIndex = 1
        } else {
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
    destroyed() {

    },
    activated() {
      this.resLoad = false;
      this.FileData = [];   //再次触发应当清空文件列表
    },
    methods: {
      //版权信息

      //选中的模式
      SelMode(val) {
        this.checkedMode = val;
      },


      GetFileID(ids) {
        this.FileID = ids
      },
      GetFileIDzip(md5id) {
        this.FileZipID = md5id
        // console.log(md5id,'获得GetFileIDzip')
      },

      // 执行计算操作
      RunDock() {

        let Datas = {
          down_name: this.ZipName,
          mode: this.RunMode,
          configure: JSON.stringify(this.ExcelConfig)
        };
        if (this.isZip) {
          Datas.zip_id = this.FileZipID;
        } else {
          Datas.ids = this.FileID.toString()
        }

        this.GetDock(Datas);
      },
      //GetDock 请求封装(方便调整校验)
      GetDock(data) {
        data.xscore = this.xscore;
        this.$api.GainLEDock(data).then(res => {
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
          // console.log(JSON.stringify(err))
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

      // 下载Excel 例子文件
      GetExcelData(data) {
        this.ExcelConfig = data;
        this.drawer = false;
        this.$message({
          message:this.$t('info.save_param'),
          type: 'success'
        });
      },
      handleClose() {
        this.drawer = false;
        // this.$message('取消参');
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

    .mag-step-left {
        .upload-demo {

            .el-upload-list--text {
                height: 5rem;
                overflow: auto;
            }

        }
        .el-divider--horizontal {
            margin: 1rem 0;
        }
    }

</style>
