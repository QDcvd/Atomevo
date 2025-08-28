<template>
  <div>
    <div class="mag-step">
      <div style="font-weight: bold;margin: 0.5rem">
        <!--        当前工具的名称-->
        PDB-tools
        <div class="tool-declaration">
          {{$t('tools.rgb')}}
        </div>
      </div>
    <!--      计算结果的步骤条-->
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
            FormatType=".pdb"
            UseType="pdb_tool"
            @GetIDList="GetFileID"
            :MaxSize="20"
          ></UpLoadFile>
        </div>
        <div class="mag-step-left">
        <!--项目下有什么子模块-->1
          <el-radio-group v-model="choice" size="medium">
            <el-radio-button label="1">重新编号结构</el-radio-button>
            <el-radio-button label="2">选择链</el-radio-button>
            <el-radio-button label="3">选择骨架原子</el-radio-button>
            <el-radio-button label="4">选择链并删除HET</el-radio-button>
          </el-radio-group>
          <div style="margin:0.5rem"></div>
          <div>
            <el-button
              size="small"
              v-if="choice == 1"
              type="primary"
              icon="el-icon-files"
              @click="handleOpenDrawer(1)"
              >{{ $t("btn.excel") }}</el-button
            >
            <el-button
              size="small"
              type="primary"
              icon="el-icon-files"
              v-if="choice == 2"
              @click="handleOpenDrawer(2)"
              >{{ $t("btn.excel") }}</el-button
            >
            <el-button
              size="small"
              type="primary"
              icon="el-icon-files"
              v-if="choice == 3"
              @click="handleOpenDrawer(3)"
              >{{ $t("btn.excel") }}</el-button
            >
            <el-button
              size="small"
              type="primary"
              icon="el-icon-files"
              v-if="choice == 4"
              @click="handleOpenDrawer(4)"
              >{{ $t("btn.excel") }}</el-button
            >
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
        <!--          执行计算-->
            <el-button  :disabled="StepIndex == 1 ? false : true" type="primary" size="small" @click="GainPdbTools">
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
            </pre>
          </div>
        </div>
      </el-col>
    </el-row>
    <el-drawer
      :with-header="false"
      :visible.sync="showDrawer[1].show"
      direction="rtl"
      size="70%"
      :destroy-on-close="true"
      :before-close="handleClose"
    >
      <!--      表格参数ModeName的名称经常需要改变-->
      <excel-edit ModeName="pdb_reres" @HandleExcel="GetExcelData" />
    </el-drawer>
    <el-drawer
      :with-header="false"
      :visible.sync="showDrawer[2].show"
      direction="rtl"
      size="70%"
      :destroy-on-close="true"
      :before-close="handleClose"
    >
      <excel-edit ModeName="pdb_selchain" @HandleExcel="GetExcelData" />
    </el-drawer>
    <el-drawer
      :with-header="false"
      :visible.sync="showDrawer[3].show"
      direction="rtl"
      size="70%"
      :destroy-on-close="true"
      :before-close="handleClose"
    >
      <excel-edit ModeName="pdb_selatom" @HandleExcel="GetExcelData" />
    </el-drawer>
    <el-drawer
      :with-header="false"
      :visible.sync="showDrawer[4].show"
      direction="rtl"
      size="70%"
      :destroy-on-close="true"
      :before-close="handleClose"
    >

      <excel-edit ModeName="pdb_delhetatm" @HandleExcel="GetExcelData" />
    </el-drawer>
  </div>
</template>

<script>
import { mapState } from "vuex";
import DownloadTable from "@/components/DownLoadTable";
import UpLoadFile from "@/components/UploadFile";
import ExcelEdit from "@/components/ExcelEdit";
// import info from '@/info';

export default {
  name: "modeller",
  components: {
    DownloadTable,
    UpLoadFile,
    ExcelEdit,
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
      choice: 1, // 当前选择的模块,1代表donor，2代表rna
      donorDrawer: false, // 表格的模态框
      rnaDrawer: false,
      ExcelConfig: [], //excel -data
      FileData: [
        //返回的文件列表
        // {
        //     success_time: '2016-05-02',
        //     name: '工程文件_AB0023.xyz',
        //     url:'www.baidu.com/test/test.xyz'
        // },
      ],
      showDrawer: [
        { show: false },
        { show: false },
        { show: false },
        { show: false },
        { show: false },
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
    /**
     * 执行计算
     * @constructor
     */
    GainPdbTools() {
      let Params = {
        ids: this.FileID.toString(),
        down_name: this.ZipName,
        choice: this.choice,
      };
      Params.configure = this.ExcelConfig;
      this.$api
        .GainPdbTools(Params)
        .then((res) => {
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

    //打开模态框
    handleOpenDrawer(type) {
      this.choice = type;
      this.showDrawer[Number(type)].show = true;
    },
    //关闭模态框
    handleClose() {
      this.showDrawer[Number(this.choice)].show = false;
    },

    GetExcelData(data) {
      this.ExcelConfig = data;
      this.$message({
        message: this.$t("info.save_param"),
        type: "success",
      });
    },
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
