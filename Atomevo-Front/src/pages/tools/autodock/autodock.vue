<template xmlns="http://www.w3.org/1999/html">
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">AutoDock4
                <div class="tool-declaration">
                    {{$t('tools.AutoDock')}}
                </div>
            </div>
            <!--    步骤条-->
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

                    <div>{{$t('content.ligand_file')}}</div>
                    <UpLoadFile FormatType=".pdb" UseType="ligand" @GetIDList="GetFileIDligand" :Count="1"></UpLoadFile>

                    <el-divider></el-divider>

                    <div>{{$t('content.receptor_file')}}</div>
                    <UpLoadFile FormatType=".pdb" UseType="receptor" @GetIDList="GetFileIDreceptor" :Count="1"></UpLoadFile>
                </div>
                <div class="mag-step-left">


                    <div style="width: 60%">
                        <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                            <template slot="prepend">{{$t('content.down_name')}}</template>
                        </el-input>
                        <div style="margin: 15px 0;"></div>
                    </div>
                    <div class="set-auto" v-show="StepIndex==1?true:false">
                        <div style="font-size: 0.8rem;font-weight: bold">ntps / gridcenter 设置</div>
                        <el-switch
                                style="display: block;margin-bottom: 0.7rem"
                                v-model="SetOther"
                                active-color="#67c23a"
                                inactive-color="#3f9eff"
                                :active-text="$t('content.custom')"
                                :inactive-text="$t('content.default')">
                        </el-switch>
                        <div class="set-class" v-show="SetOther">
                            <!--:model="ruleForm" :rules="rules" ref="ruleForm"-->
                            <el-form ref="ruleForm" label-width="5rem" size="mini" :model="ruleForm" :rules="rules">
                                <el-form-item label="npts" prop="nptlist">
                                    <el-input-number v-model="ruleForm.nptlist[0]" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                    <el-input-number v-model="ruleForm.nptlist[1]" controls-position="right"
                                                     placeholder="y"></el-input-number>
                                    <el-input-number v-model="ruleForm.nptlist[2]" controls-position="right"
                                                     placeholder="z"></el-input-number>
                                </el-form-item>
                                <el-form-item label="gridcenter" prop="gridlist">
                                    <el-input-number v-model="ruleForm.gridlist[0]" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                    <el-input-number v-model="ruleForm.gridlist[1]" controls-position="right"
                                                     placeholder="y"></el-input-number>
                                    <el-input-number v-model="ruleForm.gridlist[2]" controls-position="right"
                                                     placeholder="z"></el-input-number>
                                </el-form-item>
                            </el-form>
                        </div>
                        <div style="font-size: 0.8rem;font-weight: bold">ligand4_A / receptor4_A 设置</div>
                        <el-switch
                                style="display: block;margin-bottom: 0.7rem"
                                v-model="SetOther2"
                                active-color="#67c23a"
                                inactive-color="#3f9eff"
                                :active-text="$t('content.custom')"
                                :inactive-text="$t('content.default')">
                        </el-switch>
                        <div class="set-class" v-show="SetOther2">
                            <!--:model="ruleForm" :rules="rules" ref="ruleForm"-->
                            <el-form size="mini">
                                <el-form-item label="ligand4_A：">
                                    <el-radio v-model="ligand4" label="none">default</el-radio>
                                    <el-radio v-model="ligand4" label="bonds_hydrogens">bonds_hydrogens</el-radio>
                                    <el-radio v-model="ligand4" label="bonds">bonds</el-radio>
                                    <el-radio v-model="ligand4" label="hydrogens">hydrogens</el-radio>
                                </el-form-item>
                                <el-form-item label="receptor4_A：">
                                    <el-radio v-model="receptor4" label="none">default</el-radio>
                                    <el-radio v-model="receptor4" label="bonds_hydrogens">bonds_hydrogens</el-radio>
                                    <el-radio v-model="receptor4" label="bonds">bonds</el-radio>
                                    <el-radio v-model="receptor4" label="hydrogens">hydrogens</el-radio>
                                    <el-radio v-model="receptor4" label="checkhydrogens">checkhydrogens</el-radio>
                                </el-form-item>
                            </el-form>
                        </div>

                    </div>
                    <div>
                        <el-button type="primary" size="small" @click="RunDock" :disabled="StepIndex==1?false:true">
                            {{$t('btn.run')}}
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

    </div>
</template>

<script>
  import { mapState } from 'vuex';
  import UpLoadFile from '@/components/UploadFile';
  import DownloadTable from '@/components/DownLoadTable';
  // import info from '@/info';

  export default {
    name: "autodock",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      let validatenpts = (rule, value, callback) => {
        let tarOK = 0;
        value.forEach((item, index) => {
          if (typeof item !== "number") {
            switch (index) {
              case 0:
                callback(new Error(this.$t('msg.autodock_error_x')));
                break;
              case 1:
                callback(new Error(this.$t('msg.autodock_error_y')));
                break;
              case 2:
                callback(new Error(this.$t('msg.autodock_error_z')));
                break;
            }
          } else {
            tarOK++;
          }
        });
        if (tarOK === 3) {
          callback();
        }
      };

      let validategrid = (rule, value, callback) => {
        let tarOK = 0;
        value.forEach((item, index) => {
          if (typeof item !== "number") {
            switch (index) {
              case 0:
                callback(new Error(this.$t('msg.autodock_error_x')));
                break;
              case 1:
                callback(new Error(this.$t('msg.autodock_error_y')));
                break;
              case 2:
                callback(new Error(this.$t('msg.autodock_error_z')));
                break;
            }
          } else {
            tarOK++;
          }
        });
        if (tarOK === 3) {
          callback();
        }

      };
      return {

        rules:
          {
            nptlist: [
              {required: true, validator: validatenpts, trigger: 'blur'}
            ],
            gridlist: [
              {required: true, validator: validategrid, trigger: 'blur'}
            ]
          },                          //自定义校验
        // info: info.AutoDock4,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        ZipName: '',                     //zip文件名
        FileID1: [],                      //配体文件id
        FileID2: [],                      //受体文件id
        SetOther: false,                       //设置自定义值
        SetOther2: false,                       //设置自定义值
        ruleForm: {
          nptlist: [],                             //自定义npt值
          gridlist: [],                             //自定义gridcenter值
        },
        ligand4: 'none',                                 //ligand4_A 自定义参数
        receptor4: 'none',                                 //receptor4_A 自定义参数
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
        TaksID:state => state.TaskIDList.AutoDock
      }),
      FileIDs(){                 //任务执行总ID列表
        return this.FileID1.concat(this.FileID2);
      }
    },
    watch: {
      TaksID(newRes, oldRes) {
        // console.log('新的任务id',newRes);
        // console.log('old task',oldRes);
        this.GetDown(newRes);
      },
      FileIDs(newRes,oldRes){
        if(newRes.length>0&&this.FileID1.length>0&&this.FileID2.length>0){
          this.StepIndex = 1;
        }else{
          this.StepIndex = 0;
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

      //选中的模式
      SelMode(val) {
        this.checkedMode = val;
      },

      GetFileIDligand(ids){         //配体id
        this.FileID1 = ids;
      },
      GetFileIDreceptor(ids){       //受体id
        this.FileID2 = ids;
      },




      // 执行计算操作
      RunDock() {
        //默认传参位置
        let Params = {
          ids: this.FileIDs.toString(),
          down_name: this.ZipName,
          ligand4_A: this.ligand4,
          receptor4_A: this.receptor4
        };
        //校验ntps以及gridcenter插入值是否合法
        if (this.SetOther) {

          this.$refs.ruleForm.validate((valid) => {
            if (valid) {
              // 插入自定义值
              Params.npts = this.ruleForm.nptlist;
              Params.gridcenter = this.ruleForm.gridlist;
              this.GetAutoDock(Params);
            } else {
              this.$confirm('ntps/gridcenter坐标参数输入有误，是否选择默认参数', '操作参数异常', {
                confirmButtonText: '确定(默认参数)',
                cancelButtonText: '返回修改参数',
                type: 'warning'
              }).then(() => {
                this.GetAutoDock(Params);
              }).catch(() => {

              });
            }
          });
        } else {
          this.GetAutoDock(Params);
        }
      },
      //GetDock 请求封装(方便调整校验)
      GetAutoDock(Params) {
        this.$api.GainAutoDock(Params).then(res => {
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



    }
  }
</script>

<style lang="less">
    .res-error {
        font-size: 0.3rem;
        height: 45vh;
        overflow: auto;
    }

    .set-auto {
        margin: 1rem 0;

        .set-class {
            margin: 1rem;
        }
    }

    .mag-step-left {
        .upload-demo {

            .el-upload-list--text {
                height: 3rem;
            }

        }
        .el-divider--horizontal {
            margin: 1rem 0;
        }
    }

</style>
