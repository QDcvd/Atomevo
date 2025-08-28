<template>
  <div>
    <div class="mag-step">
      <div style="font-weight: bold;margin: 0.5rem">
        Modeller
        <div class="tool-declaration">
          <!--{{$t('tools.rgb')}}-->
        </div>
      </div>

      <el-steps :active="StepIndex" simple finish-status="success">
        <el-step
          :title="$t('content.step1')"
          icon="el-icon-document-add"
        ></el-step>
        <el-step :title="$t('content.step2')" icon="el-icon-monitor"></el-step>
        <el-step :title="$t('content.step3')" icon="el-icon-loading"></el-step>
        <el-step
          :title="$t('content.step4')"
          icon="el-icon-magic-stick"
        ></el-step>
      </el-steps>
    </div>
    <el-row :gutter="20">
      <el-col :lg="24" :xl="12">
        <div class="mag-step-left">
          <div>{{ $t("content.project_files") }}</div>
          <UpLoadFile
            FormatType=".pdb,.csv,.ali"
            UseType="modeller"
            @GetIDList="GetFileID"
            :MaxSize="20"
          ></UpLoadFile>
        </div>
        <div class="mag-step-left">
          <el-radio-group v-model="radio" size="medium">
            <el-radio-button label="1">单模板建模</el-radio-button>
            <el-radio-button label="2">多模板建模</el-radio-button>
            <el-radio-button label="3">序列对比</el-radio-button>
          </el-radio-group>
          <div style="margin:0.5rem"></div>
          <div>
             <el-button size="small" v-if="radio == 3" drawer = "true" type="primary" icon="el-icon-files" @click="drawer = true">{{$t('btn.excel')}}</el-button>
          </div>
          <div style="margin:0.5rem"></div>
          <el-input
            :placeholder="$t('content.custom_down_name')"
            v-model="ZipName"
            size="small"
          >
            <template slot="prepend">{{ $t("content.down_name") }}</template>
          </el-input>
          <div style="margin-top: 1rem">
            <el-button
              type="primary"
              size="small"
              @click="RunModeller"
              :disabled="StepIndex == 1 ? false : true"
            >
              {{ $t("btn.run") }}
            </el-button>
          </div>
        </div>
      </el-col>
      <el-col :lg="24" :xl="12">
        <div
          class="mag-step-right"
          v-loading="resLoad"
          element-loading-text="等待计算结果中..."
        >
          <div>
            {{ $t("content.handle_result") }}
            <i :class="isResult ? 'el-icon-success' : 'el-icon-error'"></i>
          </div>
          <!--成功的结果-->
          <div v-if="isResult" class="res-success">
            <download-table :DownData="FileData" />
          </div>
          <!--错误的结果-->
          <div v-else class="res-error">
            <pre>
                            error



                        </pre
            >
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
      <excel-edit ModeName="Modeller" @HandleExcel="GetExcelData"/>
    </el-drawer>
  </div>
</template>

<script>
import { mapState } from "vuex";
import DownloadTable from "@/components/DownLoadTable";
import UpLoadFile from "@/components/UploadFile";
import ExcelEdit from '@/components/ExcelEdit';
// import info from '@/info';

export default {
  name: "modeller",
  components: {
    DownloadTable,
    UpLoadFile,
    ExcelEdit
  },
  data() {
    return {
      InfoShow: false, //显示版本信息
      StepIndex: 0, // 步骤位置定位
      isResult: true, // 处理结果
      resLoad: false, //loading 动画
      FileID: [], //文件id
      ZipName: "", //压缩包名字
      isIndeterminate: true, //效果图标
      radio:"",             //选择模式
      drawer:false,         //excel
      ExcelConfig:[],       //excel -data
      FileData: [
        //返回的文件列表
        // {
        //     success_time: '2016-05-02',
        //     name: '工程文件_AB0023.xyz',
        //     url:'www.baidu.com/test/test.xyz'
        // },
      ],
    };
  },
  computed: {
    //任务id
    ...mapState({
      TaksID: (state) => state.TaskIDList.Modeller,
    }),
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
        this.StepIndex = 1;
      } else {
        this.StepIndex = 0;
      }
    },
  },
  created() {
    // console.log()
    // this.ShowTips();
  },
  activated() {
    this.resLoad = false;
    this.FileData = []; //再次触发应当清空文件列表
  },
  destroyed() {},
  methods: {
    //版权信息

    //上传文件
    GetFileID(ids) {
      this.FileID = ids;
    },

    RunModeller() {
      let Params = {
        ids: this.FileID.toString(),
        down_name: this.ZipName,
        choice:this.radio,
      };
      if(this.radio==3){
        Params.configure = this.ExcelConfig
      }else{
         Params.configure = []
      }
      this.$api
        .GainModeller(Params)
        .then((res) => {
          // console.log(res)
          this.$notify({
            title: this.$t("info.create_task"),
            iconClass: "el-icon-upload",
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify2">${res.msg}</strong>`,
          });
          this.StepIndex = 2;
          this.resLoad = true;
        })
        .catch((err) => {
          this.$notify({
            title: this.$t("info.error2"),
            iconClass: "el-icon-error",
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify3">${err.msg}</strong>`,
          });
        });
    },

    //获取下载
    GetDown(id) {
      if (id == null) {
        // this.FileData = [];
      } else {
        let param = {
          id,
          use: this.$route.name,
        };
        this.$api.GainFileList(param).then((res) => {
          this.StepIndex = 4;
          this.resLoad = false;
          // console.log('任务结果',res.files)
          this.FileData = res.files;

          this.$store.commit(`TaskIDList/${this.$route.name}_ID`, null);
        });
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



  },
};
</script>

<style lang="less">
.res-error {
  font-size: 0.3rem;
  height: 45vh;
  overflow: auto;
}
</style>
