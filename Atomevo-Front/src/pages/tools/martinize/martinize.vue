<template xmlns="http://www.w3.org/1999/html">
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">Martinize Protein
                <div class="tool-declaration">
                    <!--{{$t('tools.Martinize')}}-->
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
                    <UpLoadFile FormatType=".pdb" UseType="martinize_protein" @GetIDList="GetFileID" :Count="1"></UpLoadFile>
                </div>
                <div class="mag-step-left">
                    <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                        <template slot="prepend">{{$t('content.down_name')}}</template>
                    </el-input>
                    <div style="margin: 1rem">
                        <el-form size="mini" label-width="1rem">
                            <el-form-item label="ff：">
                                <el-radio v-model="ff" label="martini22">martini22</el-radio>
                                <el-radio v-model="ff" label="elnedyn22">elnedyn22</el-radio>
                                <el-radio v-model="ff" label="elnedyn">elnedyn</el-radio>
                                <el-radio v-model="ff" label="elnedyn22p">elnedyn22p</el-radio>
                                <el-radio v-model="ff" label="martini21">martini21</el-radio>
                                <el-radio v-model="ff" label="martini22p">martini22p</el-radio>
                                <el-radio v-model="ff" label="martini21p">martini21p</el-radio>
                            </el-form-item>
                        </el-form>
                    </div>
                    <div style="margin-top: 1rem">
                        <el-button type="primary" size="small" @click="RunMartinize"
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
    name: "martinize_protein",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      return {
        // info: info.martinize_protein,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        FileID: [],                      //文件id
        ZipName:'',                     //zip下载文件名
        ff: 'martini22',
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
        TaksID:state => state.TaskIDList.Martinize_Protein
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


      GetFileID(ids){
        this.FileID = ids;
      },




      RunMartinize() {
        let Params = {
          ids: this.FileID.toString(),
          down_name: this.ZipName,
          ff: this.ff

        };
        this.$api.GainMartinize(Params).then(res => {
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
