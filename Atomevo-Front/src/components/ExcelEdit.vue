<!--表格支持的模块名称要正确输入-->
<!--Vina、LeDock、Plants、Commol、Mktop、trRosetta、g_mmpbsa、g_mmpbsa_analysis-->

<!--excel在线编辑-->
<template>
  <div class="excel-body">
    <div class="excel-btn">
      <el-row>
        <el-col :span="4">
          <el-upload
            ref="upload"
            action="/"
            :show-file-list="false"
            :on-change="importExcel"
            :auto-upload="false"
          >
            <el-button
              slot="trigger"
              icon="el-icon-paperclip"
              size="mini"
              type="warning"
            >
              {{ $t("btn.excel_import") }}
            </el-button>
          </el-upload>
        </el-col>
        <el-col :span="12">
          <div>
            <el-button
              slot="trigger"
              icon="el-icon-bottom"
              size="mini"
              type="success"
              @click="exportHandsontable"
            >
              {{ $t("btn.excel_down") }}
            </el-button>
            <el-button
              slot="trigger"
              icon="el-icon-circle-check"
              size="mini"
              type="primary"
              @click="SaveConfig"
            >
              {{ $t("btn.excel_save") }}
            </el-button>
            <el-button
              slot="trigger"
              icon="el-icon-refresh"
              size="mini"
              type="danger"
              @click="ClearConfig"
            >
              {{ $t("btn.excel_clear") }}
            </el-button>
            <span
              style="    display:inline-block;margin-left: 20px;color: #F56C6C;
    font-size: 12px;"
              >温馨提示:参数间隔符','要转换成'/'喔</span
            >
          </div>
        </el-col>

        <el-col :span="4"></el-col>
        <el-col :span="4"></el-col>
      </el-row>
    </div>

    <div class="excel-table">
      <slot></slot>
      <!--  -->
      <HotTable
        :settings="hotSettings"
        :data="ExcelData"
        :afterChange="handle"
        :colHeaders="ModeHeader"
        :startCols="ModeCols"
        :minCols="ModeCols"
        :maxCols="ModeCols"
        ref="container"
      ></HotTable>
    </div>
    <!--</div>-->
  </div>
</template>

<script>
import XLSX from "xlsx";
import { HotTable } from "@handsontable/vue";
import { Choosefont } from "@/config/format"; //字体格式化
export default {
  props: {
    ModeName: {
      type: String,
      default: () => {
        return "plants"; //默认如果为空的情况下
      },
    },
  },
  data() {
    return {
      ExcelData: [],
      hotSettings: {
        startRows: 10, //行列范围
        // minRows: 10,  //最小行列
        maxRows: 5000, //最大行列

        rowHeaders: true, //行表头，可以使布尔值（行序号），可以使字符串（左侧行表头相同显示内容，可以解析html），也可以是数组（左侧行表头单独显示内容）。

        // colHeaders:   [ 'center_x', 'center_y', 'center_z', 'size_x', 'size_y','size_z','xmin', 'xmax', 'ymin', 'ymax', 'zmin','zmax','center_x','center_y','center_z','radius','receptor','ligand','species'],//自定义列表头or 布尔值

        // minSpareCols: 2, //列留白

        // minSpareRows: 0,//行留白
        // width: 1800,

        height: "50vh",

        // currentRowClassName: 'currentRow', //为选中行添加类名，可以更改样式
        //
        // currentColClassName: 'currentCol',//为选中列添加类名

        autoWrapRow: true, //自动换行

        contextMenu: {
          //自定义右键菜单，可汉化，默认布尔值

          items: {
            row_above: {
              name: this.$t("btn.excel_above"),
            },
            row_below: {
              name: this.$t("btn.excel_below"),
            },
            remove_row: {
              name: this.$t("btn.excel_row"),
            },
          },
        }, //右键效果

        fillHandle: true, //选中拖拽复制 possible values: true, false, "horizontal", "vertical"

        fixedColumnsLeft: 0, //固定左边列数

        fixedRowsTop: 0, //固定上边列数
        customBorders: [],

        manualColumnFreeze: true, //手动固定列

        // manualColumnMove: true, //手动移动列

        // manualRowMove: true,   //手动移动行

        manualColumnResize: true, //手工更改列距

        // manualRowResize: true,//手动更改行距

        stretchH: "all", //根据宽度横向扩展，last:只扩展最后一列，none：默认不扩展
      },
    };
  },

  name: "ExcelEdit",

  components: {
    HotTable,
  },
  computed: {
    ModeExcelData() {
      let Data = {
        Vina: [
          {
            center_x: null,
            center_y: null,
            center_z: null,
            size_x: null,
            size_y: null,
            size_z: null,
            receptor: null,
            ligand: null,
            species: null,
          },
        ],
        LeDock: [
          {
            xmin: null,
            xmax: null,
            ymin: null,
            ymax: null,
            zmin: null,
            zmax: null,
            RMSD: null,
            Number_of_binding_poses: null,
            receptor: null,
            ligand: null,
            species: null,
          },
        ],
        Plants: [
          {
            center_x: null,
            center_y: null,
            center_z: null,
            radius: null,
            receptor: null,
            ligand: null,
            species: null,
          },
        ],
        Commol: [
          {
            A: null,
            B: null,
            C: null,
            input: null,
          },
        ],
        Mktop: [
          {
            pdb_name: null,
            charge_filename: null,
            conect: null,
          },
        ],
        trRosetta: [
          {
            fasta_name: null,
            a3m_name: null,
            model_name: null,
            model_number: null,
          },
        ],
        g_mmpbsa: [
          {
            xtc_filename: null,
            tpr_filename: null,
            ndx_filename: null,
            start_time: null,
            end_time: null,
            protein_group_number: null,
            ligand_group_number: null,
          },
        ],
        g_mmpbsa_analysis: [
          {
            energy_MM: null,
            polar: null,
            apolar: null,
            contrib_MM: null,
            contrib_pol: null,
            contrib_apol: null,
            nbs: null,
            ct: null,
          },
        ],
        Gromacs: [
          {
            model_name: null,
            top_name: null,
            boxsize: null,
            boxshape: null,
            for_genion_mdp_name: null,
            em_mdp_name: null,
            water_number: null,
          },
        ],
        Gmx: [
          {
            file_name: null,
            x: null,
            y: null,
          },
        ],
        Modeller: [
          {
            modelname: null,
            sequence: null,
          },
        ],
        Multiwfn: [{ file_name: null, charge: 0, spin: 1 }],
        Exp4overlapDonor: [{ name: null, donor_F: null, donor_R: null }],
        Exp4gRNA: [{ N20_name: null, N20_sequence: null }],
        pdb_reres: [{ num: null, pdb: null }],
        pdb_selchain: [{ chain: null, pdb: null }],
        pdb_selatom: [{ atom: null, pdb: null }],
        pdb_delhetatm: [{ selchain: null, pdb: null }],
        OnePCR: [{ name: null, seq: null }],
        OverlapPCR: [{ name: null, donor_F: null, donor_R: null }],
        MultiplexPCR: [{ name: null, UP: null, MID: null, DOWN: null }],
        FoldXAlaScan: [{ name: null, pdbFile: null }],
        ClustalW2: [{ name: null, FileName: null }],
      };
      return Data[this.ModeName];
    },
    ModeHeader() {
      //动态表格头
      let ComArray = ["receptor", "ligand", "species"]; //公共后缀
      let ArrayHub = {
        // 表格头信息
        pdb_selchain: ["链名", "pdb文件"],
        pdb_selatom: ["骨架原子名", "pdb文件"],
        pdb_delhetatm: ["链名", "pdb文件"],
        pdb_reres: ["编号数字", "pdb文件"],
        Vina: [
          "center_x",
          "center_y",
          "center_z",
          "size_x",
          "size_y",
          "size_z",
        ],
        LeDock: [
          "xmin",
          "xmax",
          "ymin",
          "ymax",
          "zmin",
          "zmax",
          "RMSD",
          "Number_of_binding_poses",
        ],
        Plants: ["center_x", "center_y", "center_z", "radius"],
        Commol: ["A", "B", "C", "input"],
        Mktop: ["pdb_name", "charge_filename", "conect"],
        trRosetta: ["fasta_name", "a3m_name", "model_name", "model_number"],
        g_mmpbsa: [
          "xtc_filename",
          "tpr_filename",
          "ndx_filename",
          "start_time",
          "end_time",
          "protein_group_number",
          "ligand_group_number",
        ],
        g_mmpbsa_analysis: [
          "energy_MM(xvg)",
          "polar(xvg)",
          "apolar(xvg)",
          "contrib_MM(dat)",
          "contrib_pol(dat)",
          "contrib_apol(dat)",
          "nbs",
          "ct",
        ],
        Gromacs: [
          "model_name",
          "top_name",
          "boxsize",
          "boxshape",
          "for_genion_mdp_name",
          "em_mdp_name",
          "water_number",
        ],
        Gmx: ["file_name", "x", "y"],
        Modeller: ["modelname", "sequence"],
        Multiwfn: ["file_name", "charge", "spin"],
        Exp4overlapDonor: ["name", "donor_F", "donor_R"],
        Exp4gRNA: ["N20_name", "N20_sequence"],
        OnePCR: ["name", "seq"],
        OverlapPCR: ["name", "donor_F", "donor_R"],
        MultiplexPCR: ["name", "UP", "MID", "DOWN"],
        FoldXAlaScan: ["Name", "pdbFile"],
        ClustalW2: ["name", "FileName"],
      };
      // return ArrayHub[this.ModeName].concat(ComArray);
      let Header = [];
      switch (this.ModeName) {
        case "pdb_reres":
          Header = ArrayHub.pdb_reres;
          break;
        case "pdb_selchain":
          Header = ArrayHub.pdb_selchain;
          break;
        case "pdb_selatom":
          Header = ArrayHub.pdb_selatom;
          break;
        case "pdb_delhetatm":
          Header = ArrayHub.pdb_delhetatm;
          break;
        case "Vina":
          Header = ArrayHub.Vina.concat(ComArray);
          break;
        case "LeDock":
          Header = ArrayHub.LeDock.concat(ComArray);
          break;
        case "Plants":
          Header = ArrayHub.Plants.concat(ComArray);
          break;
        case "Commol":
          Header = ArrayHub.Commol;
          break;
        case "Mktop":
          Header = ArrayHub.Mktop;
          break;
        case "trRosetta":
          Header = ArrayHub.trRosetta;
          break;
        case "g_mmpbsa":
          Header = ArrayHub.g_mmpbsa;
          break;
        case "g_mmpbsa_analysis":
          Header = ArrayHub.g_mmpbsa_analysis;
          break;
        case "Gromacs":
          Header = ArrayHub.Gromacs;
          break;
        case "Gmx":
          Header = ArrayHub.Gmx;
          break;
        case "Modeller":
          Header = ArrayHub.Modeller;
          break;
        case "Multiwfn":
          Header = ArrayHub.Multiwfn;
          break;
        case "Exp4overlapDonor":
          Header = ArrayHub.Exp4overlapDonor;
          break;
        case "Exp4gRNA":
          Header = ArrayHub.Exp4gRNA;
          break;

        case "OnePCR":
          Header = ArrayHub.OnePCR;
          break;
        case "OverlapPCR":
          Header = ArrayHub.OverlapPCR;
          break;
        case "MultiplexPCR":
          Header = ArrayHub.MultiplexPCR;
          break;
        case "FoldXAlaScan":
          Header = ArrayHub.FoldXAlaScan;
          break;
        case "ClustalW2":
          Header = ArrayHub.ClustalW2;
          break;
      }
      return Header;
    },
    ModeCols() {
      //模式下有多少列
      let ColsHub = {
        Vina: 9,
        LeDock: 11,
        Plants: 7,
      };
      return ColsHub[this.ModeName];
    },
  },

  methods: {
    importExcel(file) {
      //导入Excel文件
      // let file = file.files[0] // 使用传统的input方法需要加上这一步
      const types = file.name.split(".")[1];
      const fileType = ["xlsx", "xlc", "xlm", "xls", "xlt", "xlw", "csv"].some(
        (item) => item === types
      );
      if (!fileType) {
        alert(this.$t("msg.excel_format"));
        return;
      }
      this.file2Xce(file).then((tabJson) => {
        let verify = this.ModeHeader; //表格数据是否合法校对 --校验表头
        let FileData = tabJson[0].sheet; //导入文件的 数据内容Json
        if (FileData.length > 0) {
          let TargetList = [];
          FileData.map((item) => {
            //校验表格空数据
            let target = Object.keys(item); //表格中每一行的key- 表头

            //检查判断-- 导入Excel文件 与预设字段的 差值
            let difference = verify
              .concat(target)
              .filter((v) => !verify.includes(v) || !target.includes(v));
            // console.log(difference)
            //判断 得到的差值是否合法，得到和初始化一样
            let union = verify.concat(
              difference.filter((v) => !verify.includes(v))
            ); //

            if (JSON.stringify(verify) === JSON.stringify(union)) {
              difference.forEach((key) => (item[key] = null)); //补充漏填参数
              TargetList.push(item);
            }

            // item[difference[0]] = null;
          });

          if (TargetList.length > 0) {
            this.ExcelData = TargetList; //直接附值
          } else {
            this.$message({
              type: "error",
              message: this.$t("info.excel_val"),
            });
          }

          // console.log(TargetList)
        } else {
          this.$message({
            type: "error",
            message: this.$t("info.excel_null"),
          });
        }
      });
    },

    file2Xce(file) {
      return new Promise(function(resolve, reject) {
        const reader = new FileReader();
        reader.onload = function(e) {
          // console.log('onload----->',e.target.result);
          const data = e.target.result;
          this.wb = XLSX.read(data, {
            type: "binary",
          });
          const result = [];
          this.wb.SheetNames.forEach((sheetName) => {
            result.push({
              sheetName: sheetName,
              sheet: XLSX.utils.sheet_to_json(this.wb.Sheets[sheetName]),
            });
          });
          resolve(result);
        };
        reader.readAsBinaryString(file.raw);
        // reader.readAsBinaryString(file) // 传统input方法
      });
    },

    exportHandsontable() {
      //导出模版内容
      // console.log('导出')
      const container = this.$refs.container.hotInstance;
      const hot = Object.assign(container, {
        data: this.ExcelData, // 导出数据
        colHeaders: true,
        rowHeaders: true,
      });
      // console.log('s', hot)
      // access to exportFile plugin instance
      const exportPlugin = hot.getPlugin("exportFile");
      // console.log('exportPlugin', exportPlugin)
      exportPlugin.downloadFile("csv", {
        bom: "UTF-8", // 允许您使用BOM签名导出数据。
        // columnDelimiter: ',', // 允许您定义列分隔符。
        columnHeaders: true, // 允许使用列标题导出数据。
        rowHeaders: false, // 允许您使用行标题导出数据。
        fileExtension: "csv", // 允许您定义文件扩展名。
        filename: this.ModeName + "Config", // 允许您定义文件名。
      });
    },

    SaveConfig() {
      //保存数据
      if (
        JSON.stringify(this.ExcelData) == JSON.stringify(this.ModeExcelData)
      ) {
        this.$message(this.$t("info.excel_null2"));
      } else {
        let filterData = [];

        let filterLength = Object.keys(this.ExcelData[0]).length;
        this.ExcelData.forEach((item) => {
          //判断Item是否全部为null ，如果全Null不添加
          let counter = 0; //记录Item null次数
          Object.keys(item).map((key) => {
            //查询Item里面每个key值
            if (item[key] == null) {
              counter++;
            } else {
              // return item[key]*2;
              return (item[key] = Choosefont(item[key]));

              // console.log(Choosefont(''),' 看看')
            }
          });

          if (counter != filterLength) {
            //如果不全为Null就添加

            // Object.keys(item).map(val=>{
            //   // console.log(item[val])
            //
            //   // return item[val] = this.Choosefont(item[val])           //格式化一下 存在特殊字符的情况
            // });
            // console.log(item,'这是当前行')
            filterData.push(item);
          }
        });

        this.$emit("HandleExcel", filterData); //传递已经筛选的参数
      }
    },
    //清除数据
    ClearConfig() {
      this.$confirm(this.$t("info.excel_clear_all"), this.$t("info.tips"), {
        confirmButtonText: this.$t("btn.confirm"),
        cancelButtonText: this.$t("btn.cancel"),
        type: "error",
      })
        .then(() => {
          this.ExcelData = JSON.parse(JSON.stringify(this.ModeExcelData));
          this.$message({
            type: "success",
            message: this.$t("info.success"),
          });
        })
        .catch(() => {
          this.$message({
            type: "info",
            message: this.$t("info.cancel"),
          });
        });
    },
  },
  created() {
    //初始化
    this.ExcelData = JSON.parse(JSON.stringify(this.ModeExcelData));
    // console.log('Excel编辑器启动')
  },
};
</script>

<style lang="less">
@import "~handsontable/dist/handsontable.full.css";

@vina: #f2d19d; //vina颜色
@ledock: #8cc5fc; //ledock颜色
@plants: #aedcbc; //plants 颜色
#hot-display-license-info {
  display: none;
}

.htContextMenu:not(.htGhostTable) {
  z-index: 3000;
}

.excel-body {
  overflow: auto;
  width: 100%;
  height: 100%;

  .excel-btn {
    padding: 0 2rem;
    margin: 1rem 0;
  }
  .excel-top {
    text-align: center;
    font-size: 0.8rem;
    margin: 0.5rem 0;
    display: flex;

    .excel-list {
      /*height: 2rem;*/
      width: 2rem;
      margin: 0.5rem 1rem;
      flex: 1;
      .excel-block {
        height: 1rem;
        width: 2rem;
        border-radius: 10%;
        display: inline-block;
      }
      .font {
        display: inline-block;
      }
      .vina {
        background-color: @vina;
      }
      .ledock {
        background-color: @ledock;
      }
      .plants {
        background-color: @plants;
      }
    }
  }
  .excel-table {
    padding: 0 1rem;

    .htCore {
      thead {
        tr {
          th {
          }
        }
      }
    }
  }
}
</style>
