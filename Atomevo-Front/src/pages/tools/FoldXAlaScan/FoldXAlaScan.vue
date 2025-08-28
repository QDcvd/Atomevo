<template>
  <div>
    <div class="mag-step">
      <!-- 菜单Title -->
      <div style="font-weight: bold;margin: 0.5rem">FoldX-AlaScan</div>

      <!-- 步进器 -->
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
          <!-- 上传组件 -->
          <div>{{$t('content.project_files')}}</div>
          <UpLoadFile
              FormatType=".pdb"
              UseType="foldx_alascan"
              @GetIDList="GetFileID"
              :Count="2"
          />
        </div>

        <!--导入Excel参数执行块 -->
        <div class="mag-step-left">
          <!-- 输入下载文件名 -->
          <div style="width: 60%">
            <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
              <template slot="prepend">{{$t('content.down_name')}}</template>
            </el-input>
            <el-divider />
          </div>

          <!--调试可以修改false-->
          <div class="set-auto">
              <el-button
                  size="small"
                  @click="drawer = true"
                  type="primary"
                  icon="el-icon-files"
              >
                {{$t('btn.excel')}}
              </el-button>

            <!-- 参数表单 ph | temperature -->
            <div style="font-size: 0.8rem;font-weight: bold; margin-top: 0.9rem">ph / temperature</div>
            <el-switch
                style="display: block;margin-bottom: 0.7rem"
                v-model="SetOther"
                active-color="#67c23a"
                inactive-color="#3f9eff"
                :active-text="$t('content.custom')"
                :inactive-text="$t('content.default')"
            />
            <div class="set-class" v-show="SetOther">
              <el-form
                  ref="SelForm"
                  label-width="5rem"
                  size="mini"
                  :model="SelForm"
                  :rules="rules"
              >

                <el-form-item label="ph" prop="ph">
                  <el-input-number
                      v-model="SelForm.ph"
                      controls-position="right"
                     placeholder="x"
                  />
                </el-form-item>
                <el-form-item label="temperature" prop="temperature">
                  <el-input-number
                      v-model="SelForm.temperature"
                      controls-position="right"
                      placeholder="x"
                  />
                </el-form-item>
              </el-form>
            </div>
          </div>

          <!-- 执行计算 -->
          <el-button
              type="primary"
              size="small"
              @click="RunDock"
              :disabled="StepIndex == 1 ? false : true"
          >
            {{$t('btn.run')}}
          </el-button>
        </div>
      </el-col>

      <el-col :lg="24" :xl="10">
        <div
            class="mag-step-right"
            v-loading="resLoad"
            element-loading-text="等待计算结果中(通知中心可查看进度)..."
        >
          {{$t('content.handle_result')}}
          <i :class="isResult ? 'el-icon-success':'el-icon-error'" />

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

    <!-- 表格抽屉 -->
    <el-drawer
        :with-header="false"
        :visible.sync="drawer"
        direction="rtl"
        size="70%"
        :before-close="handleClose">
      <excel-edit ModeName="FoldXAlaScan" @HandleExcel="GetExcelData"/>
    </el-drawer>
  </div>
</template>

<script>
import {mapState} from 'vuex';

import UpLoadFile from '@/components/UploadFile';
import DownloadTable from '@/components/DownLoadTable';
import ExcelEdit from '@/components/ExcelEdit';

export default {
  name: "FoldX_AlaScan",
  components: {
    DownloadTable,
    ExcelEdit,
    UpLoadFile
  },
  data() {
    // 表单验证方法
    let validateOther = (rule, value, callback) => {
      if (typeof value !== 'number') {
        callback(new Error('请输入合法参数'))
      } else {
        callback();
      }
    };

    return {
      // 表单ph | temperature 规则
      rules: {
        ph: [
          {required: true, validator: validateOther, trigger: 'blur'}
        ],
        temperature: [
          {required: true, validator: validateOther, trigger: 'blur'}
        ]
      },

      StepIndex: 0, // 步骤位置定位
      isResult: true, // 处理结果
      resLoad: false, // loading 动画
      ZipName: '', // zip文件名
      FileID1: [], // 配体文件id
      FileID2: [], // 受体文件id
      FileID: [], // ids
      FileZipID: '', // zip文件id
      SetOther: false, // 设置自定义值
      SetOther2: false, // 设置自定义值
      // ph | temperature 表单参数数据
      SelForm: {
        ph: 7,
        temperature: 298,
      },
      ligand4: 'hydrogens', // ligand4_A 自定义参数
      receptor4: 'hydrogens', // receptor4_A 自定义参数
      isIndeterminate: true, // 效果图标
      isZip: false, // Zip 和常规二选一
      drawer: false,// 文件导入弹框
      ExcelConfig: [],// 导入的Excel参数
      FileData: [ ], // 返回的文件列表
      RunMode: 2, //1 云服务器 ，2线下服务器
    };
  },
  computed: {
    //任务id
    ...mapState({
      TaksID: state => state.TaskIDList.AutoDock_Vina
    }),
    // 任务执行总ID列表
    FileIDs() {
      return this.FileID1.concat(this.FileID2);
    },
    RunSetMode() {
      let {roles} = this.$store.state.UserInfo;
      return roles.includes('vip')
    },
  },
  watch: {
    TaksID(newRes) {
      this.GetDown(newRes);
    },
    FileIDs(newRes) {
      if (newRes.length > 0 && this.FileID1.length > 0 && this.FileID2.length > 0) {
        this.StepIndex = 1;
      } else {
        this.StepIndex = 0;
      }
    },
    FileZipID(newRes) {
      if (newRes) {
        this.StepIndex = 1;
      } else {
        this.StepIndex = 0;
      }
    },
    isZip(newRes) {
      if (newRes) {
        this.FileZipID = '';
        this.StepIndex = 0;
      } else {
        this.FileID1 = [];
        this.FileID2 = [];
        this.StepIndex = 0;
      }
    },
    RunMode(newRes) {
      if (newRes === 2) {
        this.isZip = true;
      } else {
        this.isZip = false;
      }
    }
  },
  activated() {
    this.resLoad = false;
    this.FileData = [];   //再次触发应当清空文件列表
  },
  methods: {
    GetFileID(ids){
      this.FileID = ids;
      this.StepIndex = 1;
    },
    //选中的模式
    SelMode(val) {
      this.checkedMode = val;
    },
    GetFileIDligand(ids) {
      this.FileID1 = ids;
    },
    GetFileIDreceptor(ids) {
      this.FileID2 = ids;
    },
    GetFileIDzip(md5id) {
      this.FileZipID = md5id
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
      // 插入excel文件
      Datas.configure = JSON.stringify(this.ExcelConfig);

      if (this.isZip) {
        // 使用文件md5
        Datas.zip_id = this.FileZipID;
      } else {
        let IDlist = this.FileIDs;
        // 对不同模式对现有id排序
        let PIDlist = IDlist.map(num => {
          return parseInt(num)
        });
        PIDlist.sort(function (a, b) {
          return a - b
        });
        // 使用任务id
        Datas.ids = PIDlist.toString();
      }
      //校验插入值是否合法
      let Togo = [];
      this.$refs.SelForm.validate((valid) => {
        if (valid) {
          // 插入自定义值
          Datas.ph = this.SelForm.ph;
          Datas.temperature = this.SelForm.temperature;
          Togo = true;
        } else {
          Togo = false;
          this.$alert('energy_range/exhaustiveness /num_modes请输入合法参数', '参数异常', {
            confirmButtonText: this.$t('btn.confirm'),
          });
        }
      });

      if (Togo) {
        // 参数合法进入下一步
        this.GetVina(Datas);
      }
    },
    // 请求封装(方便调整校验)
    GetVina(data) {
      data.ids = this.FileID.toString();
      data.mode = this.RunMode;
      data.ph = typeof data.ph === 'number' ? data.ph : 0
      this.$api.GainFoldXAlaScan(data).then(res => {
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
          this.FileData = res.files;
          this.$store.commit(`TaskIDList/${this.$route.name}_ID`, null);
        })
      }
    },
    handleClose() {
      this.drawer = false;
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
