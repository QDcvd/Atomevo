<template>
    <div>
        <el-row :gutter="20">
            <el-col :lg="24" :xl="14">

                <div class="mag-server-info">
                    <el-row type="flex" class="row-bg">
                        <el-col :span="12" class="mag-info-block">
                            <el-progress type="dashboard" :percentage="getState" :width="60" :color="colors"></el-progress>

                            <el-tooltip class="item" effect="dark" content="常规-服务器算力使用状态" placement="top">
                                <div style="width: 100%">Normal  <IconSvg icon-class="icon-cpu1"></IconSvg></div>
                            </el-tooltip>

                        </el-col>
                        <!--<el-col :span="6" class="mag-info-block">-->
                            <!--<el-progress type="dashboard" :percentage="getCount" :width="60" :color="colors"></el-progress>-->


                            <!--<el-tooltip class="item" effect="dark" content="常规-服务器任务上限负载状态" placement="top">-->
                                <!--<div style="width: 100%">Normal Tasks  <IconSvg icon-class="icon-liebiaomoshi"></IconSvg></div>-->
                            <!--</el-tooltip>-->
                        <!--</el-col>-->
                        <el-col :span="12" class="mag-info-block">
                            <el-progress type="dashboard" :percentage="getLocalState" :width="60" :color="colors"></el-progress>
                            <el-tooltip class="item" effect="dark" content="高级-服务器算力使用状态" placement="top">
                                <div style="width: 100%"> Advanced <IconSvg icon-class="icon-cpu"></IconSvg></div>
                            </el-tooltip>

                        </el-col>
                        <!--<el-col :span="6" class="mag-info-block">-->
                            <!--<el-progress type="dashboard" :percentage="getLocalCount" :width="60" :color="colors"></el-progress>-->
                            <!--<el-tooltip class="item" effect="dark" content="高级-服务器任务上限负载状态" placement="top">-->
                                <!--<div style="width: 100%">Advanced Tasks  <IconSvg icon-class="icon-listview"></IconSvg></div>-->
                            <!--</el-tooltip>-->

                        <!--</el-col>-->
                    </el-row>

                        <!--<el-tag type="success"><i class="el-icon-loading" v-show="!getState"/>{{getCount}}</el-tag>-->
                        <!--<el-tag type="success"><i class="el-icon-loading" v-show="!getState"/>{{getState}}</el-tag>-->

                </div>

                <div style="margin-bottom: 2rem;">
                    <el-table
                            v-loading="tableLoad"
                            size="mini"
                            :data="tableData"
                            style="width: 100%"
                            height="40rem"
                            border
                            :row-class-name="isNewData">
                        <el-table-column
                                :label="$t('table.date')"
                                min-width="15%">
                            <template slot-scope="scope">
                                <span class="table-time">{{ scope.row.creat_time |ResTime}}</span>

                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('table.id')"
                                min-width="7%">
                            <template slot-scope="scope">
                                <span class="table-id">{{ scope.row.id }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('table.custom_name')"
                                min-width="18%">
                            <template slot-scope="scope">
                                <span class="table-name">{{ scope.row.down_name }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('table.count')"
                                min-width="8%">
                            <template slot-scope="scope">
                                <i class="el-icon-folder-opened"></i>
                                <span>{{ scope.row.files_num }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('table.machine')"
                                min-width="11%" align="center">
                            <template slot-scope="scope">
                                <el-tag type="info" size="small" v-if="scope.row.mode ==1">Normal</el-tag>
                                <el-tag type="success" size="small" v-else>Advanced</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('table.tool')"
                                min-width="13%" align="center">
                            <template slot-scope="scope">
                                <el-tag size="small">{{ scope.row.use }}</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column :label="$t('table.status')" min-width="10%" align="center">
                            <template slot-scope="scope">
                                <i class="el-icon-refresh" v-if="scope.row.state == 0">{{$t('info.status2')}}</i>
                                <i class="el-icon-finished" v-else-if="scope.row.state == 1"
                                   style="color: #5daf34">{{$t('info.status1')}}</i>
                                <i class="el-icon-bottom-left" v-else-if="scope.row.state == 2" style="color: #dda84c">{{$t('info.status3')}}</i>
                                <i class="el-icon-folder-delete" v-else-if="scope.row.state == -1"
                                   style="color: #f56370">{{$t('info.status4')}}</i>
                            </template>
                        </el-table-column>
                        <el-table-column min-width="10%" align="center">
                            <template slot-scope="scope">
                                <el-button
                                        size="mini"
                                        @click="ToResDetail(scope.row)"
                                        :disabled="scope.row.state ==0 || scope.row.state == 2">{{$t('btn.view')}}
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </el-col>
            <el-col :lg="24" :xl="10">


                <ve-pie :data="chartData" :settings="chartSettings"></ve-pie>
                <el-divider></el-divider>
                <ve-bar :data="chartData"></ve-bar>
                <el-divider></el-divider>
            </el-col>
        </el-row>

        <el-drawer

                :visible.sync="drawer"
                direction="rtl"
                size="50%"
                >
            <template slot="title">
                <div>{{drawer_title}}</div>
                <div class="run-time-tips"> <i class="el-icon-odometer"></i>运算耗时：{{RunTime}}</div>
            </template>

            <div style="padding:0 1rem">

                <download-table
                        v-if="drawer"
                        v-loading="!FileData.length>0"
                        :DownData="FileData"
                        />
            </div>
        </el-drawer>
    </div>

</template>

<script>
  import IconSvg from '@/components/MagIcon';
  import {FormatTime2} from '@/config/format';
  import DownloadTable from '@/components/DownLoadTable';

  export default {
    name: "info",
    components:{
      DownloadTable,
      IconSvg
    },
    data() {

      this.chartSettings = {
        limitShowNum: 10,
      };
      return {
        tableLoad:true,
        tableData: [

        ],
        chartData: {
          columns: ['工具', '总计算次数'],
          rows: [
            {'工具': 'toolname', '总计算次数': 0},
          ]
        },

        drawer:false,           //查看任务详情弹出
        drawer_title:'',         //动态标题
        FileData:[],            //任务下载数据
        RunTime:'',
        listpage: 1,  //任务列表页码
        colors:[
          {color: '#39bae9', percentage: 30},
          {color: '#5cb87a', percentage: 50},
          {color: '#e6a23c', percentage: 80},
          {color: '#dd061c', percentage: 100},
          ],

      }
    },
    computed: {
      getState() {
        return this.$store.state.WebState;
      },

      getCount() {
        return this.$store.state.TasksCount;
      },
      getLocalState() {
        return this.$store.state.LocalState;
      },

      getLocalCount() {
        return this.$store.state.LocalTasksCount;
      },

    },

    filters: {
      ResTime: function (time) {
        return FormatTime2(time * 1000);
      }
    },
    created() {
      this.$message.closeAll();
      this.GetList(this.listpage);
      this.GetChart();
    },
    methods: {

      //判断是否30分钟内创建的内容(绿色高亮)
      isNewData({row, rowIndex}) {
        if (new Date().getTime() - row.creat_time * 1000 <= 30 * 60 * 1000) {
          return 'new-data-list';

        } else {
          return '';
        }
      },
      //  去详情页面(打开任务详情)
      ToResDetail(item) {

        this.RunTime = (item.success_time-item.creat_time)/60<60?`${((item.success_time-item.creat_time)/60).toFixed(2)} min`:`${((item.success_time-item.creat_time)/60/60).toFixed(1)} h`;          //分钟单位

        this.FileData = []; //先清空上次
        this.drawer_title = `【${item.use.slice(0,1).toUpperCase() + item.use.slice(1)}】结果查看`;
        this.drawer = true;
        let param = {
          id:item.id,
          use: item.use
        };
        this.$api.GainFileList(param).then(res => {
          this.resLoad = false;

            // console.log('任务结果',res.files)
          this.FileData = res.files;
        })
      },

      //获取任务列表
      GetList(page, type) {
        this.$api.GainAllList(page, type).then(res => {
          // console.log(res.list);
          this.tableData = res.list;
          this.tableLoad = false;
          // this.tableData.push(res.list)
        }).catch(err=>{
          this.tableLoad = false;
        })
      },
      //任务列表无限滚动（还没写
      GetListload() {
        // console.log('地步')
            // this.listpage++;
      },

      //获取统计表单
      GetChart() {
        this.$api.GainChart().then(res => {

          let row = [
            {'工具': 'auto-martini', '总计算次数': Number(res.chart.auto_martini)},
            {'工具': 'Procheck', '总计算次数': Number(res.chart.procheck)},
            {'工具': 'XVG2CSV', '总计算次数': Number(res.chart.xvg_to_csv)},
            {'工具': 'AutoDock', '总计算次数': Number(res.chart.autodock)},
            {'工具': 'AutoDock_Vina', '总计算次数': Number(res.chart.autodock_vina)},
            {'工具': 'DSSP', '总计算次数': Number(res.chart.dssp)},
            {'工具': 'Martinize_Protein', '总计算次数': Number(res.chart.martinize_protein)},
            {'工具': 'PLIP', '总计算次数': Number(res.chart.plip)},
            {'工具': 'XSCore', '总计算次数': Number(res.chart.xscore)},
            {'工具': 'LEDock', '总计算次数': Number(res.chart.ledock)},
            {'工具': 'OpenBabel', '总计算次数': Number(res.chart.obabel)},
            {'工具': 'Plants', '总计算次数': Number(res.chart.plants)},
            {'工具': 'Mktop', '总计算次数': Number(res.chart.mktop)},
            {'工具': 'Commol', '总计算次数': Number(res.chart.commol)},
            {'工具': 'trRosetta', '总计算次数': Number(res.chart.trrosetta)},
            {'工具': 'g_mmpbsa', '总计算次数': Number(res.chart.g_mmpbsa)},
            {'工具': 'g_mmpbsa_analysis', '总计算次数': Number(res.chart.g_mmpbsa_analysis)},
            {'工具': 'gromacs', '总计算次数': Number(res.chart.gromacs)},
            {'工具': 'rgb', '总计算次数': Number(res.chart.rgb)},
            {'工具': 'tksa', '总计算次数': Number(res.chart.tksa)},
            {'工具': 'glapd', '总计算次数': Number(res.chart.glapd)},
            {'工具': 'gmx', '总计算次数': Number(res.chart.gmx)},
            {'工具': 'Modeller', '总计算次数': Number(res.chart.modeller)},
            {'工具': 'Gzeronine', '总计算次数': Number(res.chart.Gzeronine)},
          ];
          // console.log(typeof Number(res.chart.auto_martini) )
          this.chartData.rows = row;
        })
      }


    }
  }
</script>

<style lang="less">
  .mag-server-info{
      user-select:none;
      font-size: 0.8rem;
      margin: 0.3rem;
      /*height: 5rem;*/
      .mag-info-block{
          text-align: center;
      }
  }
  .run-time-tips{
    margin-right: 4rem;
    font-size: 0.5rem;
  }
</style>
