<template xmlns="http://www.w3.org/1999/html">
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">AutoDock-Vina
                <div class="tool-declaration">
                    {{$t('tools.AutoDockVina')}}
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
                        <div>{{$t('content.ligand_file')}}</div>
                        <UpLoadFile v-if="!isZip" FormatType=".pdb" UseType="ligand" @GetIDList="GetFileIDligand"
                                    :Count="50"></UpLoadFile>

                        <el-divider></el-divider>

                        <div>{{$t('content.receptor_file')}}</div>
                        <UpLoadFile v-if="!isZip" FormatType=".pdb" UseType="receptor" @GetIDList="GetFileIDreceptor"
                                    :Count="50"></UpLoadFile>
                    </div>


                </div>
                <div class="mag-step-left">


                    <div style="width: 60%">
                        <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                            <template slot="prepend">{{$t('content.down_name')}}</template>
                        </el-input>
                        <el-divider></el-divider>
                    </div>
                    <!--调试可以修改false-->
                    <div class="set-auto">
                        <!--<el-switch-->
                        <!--style="display: block;margin-bottom: 0.5rem"-->
                        <!--v-model="SetAoM"-->
                        <!--active-color="#67c23b"-->
                        <!--inactive-color="#39bae9"-->
                        <!--active-text="单任务-手动参数"-->
                        <!--inactive-text="多任务-Excel参数">-->
                        <!--</el-switch>-->
                        <!--好像后期不需要单任务执行。 先注释掉-->
                        <div>
                            <el-button size="small" @click="drawer = true" type="primary" icon="el-icon-files">{{$t('btn.excel')}}
                            </el-button>
                        </div>
                        <!-- ad4开始 -->
                        <div style="font-size: 0.8rem;font-weight: bold">ad4打分
                        </div>
                        <el-switch
                              style="display: block;margin-bottom: 0.7rem"
                              v-model="SetAd4"
                              active-color="#67c23a"
                              inactive-color="#3f9eff"
                              :active-text="$t('content.custom')"
                              :inactive-text="$t('content.default')">
                        </el-switch>
                        <div class="set-class" v-show="SetAd4">
                            <el-form ref="Ad4Form" label-width="5rem" size="mini" :model="Ad4Form" :rules="rules">
                                <el-form-item label="ph" prop="ph">
                                    <el-input-number v-model="Ad4Form.ph" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                </el-form-item>
                            </el-form>
                        </div>
                        <!-- ad4结束 -->
                        <div style="font-size: 0.8rem;font-weight: bold">energy_range / exhaustiveness /num_modes
                        </div>
                        <el-switch
                                style="display: block;margin-bottom: 0.7rem"
                                v-model="SetOther"
                                active-color="#67c23a"
                                inactive-color="#3f9eff"
                                :active-text="$t('content.custom')"
                                :inactive-text="$t('content.default')">
                        </el-switch>
                        <div class="set-class" v-show="SetOther">
                            <el-form ref="SelForm2" label-width="5rem" size="mini" :model="SelForm2" :rules="rules">
                                <el-form-item label="energy_range" prop="energy_range">
                                    <el-input-number v-model="SelForm2.energy_range" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                </el-form-item>
                                <el-form-item label="exhaustiveness" prop="exhaustiveness">
                                    <el-input-number v-model="SelForm2.exhaustiveness" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                </el-form-item>
                                <el-form-item label="num_modes" prop="num_modes">
                                    <el-input-number v-model="SelForm2.num_modes" controls-position="right"
                                                     placeholder="x"></el-input-number>
                                </el-form-item>
                            </el-form>
                        </div>


                        <div style="font-size: 0.8rem;font-weight: bold">ligand4_A / receptor4_A </div>
                        <el-switch
                                style="display: block;margin-bottom: 0.7rem"
                                v-model="SetOther2"
                                active-color="#67c23a"
                                inactive-color="#3f9eff"
                                :active-text="$t('content.custom')"
                                :inactive-text="$t('content.default')">
                        </el-switch>
                        <div class="set-class" v-show="SetOther2">
                            <el-form size="mini">
                                <el-form-item label="ligand4_A：">
                                    <el-radio v-model="ligand4" label="none">default</el-radio>
                                    <el-radio v-model="ligand4" label="bonds_hydrogens">bonds_hydrogens</el-radio>
                                    <el-radio v-model="ligand4" label="bonds">bonds</el-radio>
                                    <el-radio v-model="ligand4" label="hydrogens">hydrogens</el-radio>
                                </el-form-item>
                                <el-form-item label="receptor4_A：">
                                    <!--<el-row :justify="flex">-->
                                    <!--<el-col :span="3"><el-radio v-model="receptor4" label="none">default</el-radio></el-col>-->
                                    <!--<el-col :span="6"><el-radio v-model="receptor4" label="bonds_hydrogens">bonds_hydrogens</el-radio></el-col>-->
                                    <!--<el-col :span="3"><el-radio v-model="receptor4" label="bonds">bonds</el-radio></el-col>-->
                                    <!--<el-col :span="4"><el-radio v-model="receptor4" label="hydrogens">hydrogens</el-radio></el-col>-->
                                    <!--<el-col :span="4"><el-radio v-model="receptor4" label="checkhydrogens">checkhydrogens</el-radio></el-col>-->
                                    <!--</el-row>-->
                                    <el-radio v-model="receptor4" label="none">default</el-radio>
                                    <el-radio v-model="receptor4" label="bonds_hydrogens">bonds_hydrogens</el-radio>
                                    <el-radio v-model="receptor4" label="bonds">bonds</el-radio>
                                    <el-radio v-model="receptor4" label="hydrogens">hydrogens</el-radio>
                                    <el-radio v-model="receptor4" label="checkhydrogens">checkhydrogens</el-radio>
                                </el-form-item>
                            </el-form>
                        </div>
                        <div>
                            <el-checkbox v-model="xscore">{{$t('content.handle_xscore')}}</el-checkbox>
                        </div>
                    </div>
                    <div>

                      <!-- -->
                        <el-button type="primary" size="small"
                                   @click="RunDock"
                                   :disabled="StepIndex==1?false:true"
                                   >  {{$t('btn.run')}}
                        </el-button>

                    </div>
                </div>
            </el-col>
            <el-col :lg="24" :xl="10">

                <div class="mag-step-right"
                     v-loading="resLoad"
                     element-loading-text="等待计算结果中(通知中心可查看进度)...">
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
            <excel-edit ModeName="Vina" @HandleExcel="GetExcelData"/>
        </el-drawer>


    </div>
</template>

<script>
  import {mapState} from 'vuex';
  import UpLoadFile from '@/components/UploadFile';
  import DownloadTable from '@/components/DownLoadTable';
  import ExcelEdit from '@/components/ExcelEdit';
  // import info from '@/info';

  export default {
    name: "autodock_vina",
    components: {
      DownloadTable,
      ExcelEdit,
      UpLoadFile
    },
    data() {
      let validatecents = (rule, value, callback) => {
        let tarOK = 0;
        value.forEach((item, index) => {
          if (typeof item !== "number") {
            switch (index) {
              case 0:
                callback(new Error(this.$t('msg.autodock_error_x')));
                break;
              case 1:
                callback(new Error(this.$t('msg.autodock_y')));
                break;
              case 2:
                callback(new Error(this.$t('msg.autodock_z')));
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

      let validatesize = (rule, value, callback) => {
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
      let validateother = (rule, value, callback) => {
        if (typeof value !== 'number') {
          callback(new Error('请输入合法参数'))
        } else {
          callback();
        }
      };
      return {

        rules: {
          center: [
            {required: true, validator: validatecents, trigger: 'blur'}
          ],
          size: [
            {required: true, validator: validatesize, trigger: 'blur'}
          ],
          energy_range: [
            {required: true, validator: validateother, trigger: 'blur'}
          ],
          exhaustiveness: [
            {required: true, validator: validateother, trigger: 'blur'}
          ],
          num_modes: [
            {required: true, validator: validateother, trigger: 'blur'}
          ],
          ph: [
             {required: true, validator: validateother, trigger: 'blur'}
          ]

        }, //自定义校验
        rules2: {},
        // info: info.AutoDockVina,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        ZipName: '',                     //zip文件名
        FileID1: [],                      //配体文件id
        FileID2: [],                      //受体文件id
        FileZipID: '',                      //zip文件id
        SetOther: false,                       //设置自定义值
        SetOther2: false,                       //设置自定义值
        SetAd4: false,                // 设置ad4显示
        SelForm2: {
          energy_range: 4,
          exhaustiveness: 9,
          num_modes: 9
        },
        Ad4Form: {
          ph: 7.0
        },
        ligand4: 'hydrogens',                                 //ligand4_A 自定义参数
        receptor4: 'hydrogens',                                 //receptor4_A 自定义参数
        isIndeterminate: true,          //效果图标
        isZip: false,                        //Zip 和常规二选一
        drawer: false,                       //文件导入弹框
        ExcelConfig: [],                     //导入的Excel参数
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
        ],
        RunMode: 1,        //1 云服务器 ，2线下服务器
        xscore:true,

      };

    },
    computed: {
      //任务id
      ...mapState({
        TaksID: state => state.TaskIDList.AutoDock_Vina
      }),
      FileIDs() {                 //任务执行总ID列表
        return this.FileID1.concat(this.FileID2);
      },
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
      FileIDs(newRes, oldRes) {
        if (newRes.length > 0 && this.FileID1.length > 0 && this.FileID2.length > 0) {
          this.StepIndex = 1;
        } else {
          this.StepIndex = 0;
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
          this.FileID1 = [];
          this.FileID2 = [];
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

      //选中的模式
      SelMode(val) {
        this.checkedMode = val;
      },

      GetFileIDligand(ids) {
        this.FileID1 = ids;
        // console.log(ids,'获得GetFileIDligand')
      },
      GetFileIDreceptor(ids) {
        this.FileID2 = ids;
      },
      GetFileIDzip(md5id) {
        this.FileZipID = md5id
        // console.log(md5id,'获得GetFileIDzip')
      },

      // 执行计算操作
      RunDock() {
        //默认传参位置

        let Datas = {
          down_name: this.ZipName,
        };

        if (this.ExcelConfig.length == 0) {
          this.$message('没有检测到表格数据，该模式需要配置表格参数');
          return
        }
        Datas.configure = JSON.stringify(this.ExcelConfig);        //插入excel文件

        if (this.isZip) {
          Datas.zip_id = this.FileZipID;                // 使用文件md5
        } else {
          let IDlist = this.FileIDs;
          let PIDlist = IDlist.map(num => {
            return parseInt(num)
          });        //对不同模式对现有id排序
          PIDlist.sort(function (a, b) {
            return a - b
          });
          Datas.ids = PIDlist.toString();           //使用任务id
        }


        //校验插入值是否合法
        let Togo = [];
        this.$refs.SelForm2.validate((valid) => {
          if (valid) {
            // 插入自定义值
            Datas.energy_range = this.SelForm2.energy_range;
            Datas.exhaustiveness = this.SelForm2.exhaustiveness;
            Datas.num_modes = this.SelForm2.num_modes;
            Togo = true;
          } else {
            Togo = false;
            this.$alert('energy_range/exhaustiveness /num_modes请输入合法参数', '参数异常', {
              confirmButtonText: this.$t('btn.confirm'),
            });
          }
        });
        // 校验ad4的填写
        this.$refs.Ad4Form.validate((valid) => {
          if(valid) {
            // 插入自定义值
            Datas.ph = this.Ad4Form.ph;
            Togo = true;  
          }else {
            Togo = false;
             this.$alert('ad4打分请输入合法参数', '参数异常', {
              confirmButtonText: this.$t('btn.confirm'),
            });
          }
        })

        Datas.ligand4_A = this.ligand4;
        Datas.receptor4_A = this.receptor4;
        if (Togo) {
          this.GetVina(Datas);          //参数合法进入下一步
        }


      },
      //GetDock 请求封装(方便调整校验)
      GetVina(data) {
        data.mode = this.RunMode;
        data.xscore = this.xscore;
        data.ph = !this.SetAd4?0:(data.ph?data.ph:0)
        this.$api.GainAutoDockVina(data).then(res => {
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
                overflow: auto;
            }

        }
        .el-divider--horizontal {
            margin: 1rem 0;
        }
    }

</style>
