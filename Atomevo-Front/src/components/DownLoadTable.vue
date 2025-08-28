<template>
    <div>
    <el-tabs tab-position="left"  @tab-click="handleClick" v-model="activeName">
        <el-tab-pane :label="$t('content.result')" name="result"><el-link type="primary"> > result / 【{{ResData.length}}】</el-link></el-tab-pane>
        <el-tab-pane :label="$t('content.output_file')" name="output_file"><el-link type="primary"> > output_file / 【{{OutData.length}}】</el-link></el-tab-pane>
        <el-tab-pane :label="$t('content.params')" name="parameter"><el-link type="primary"> > parameter / 【{{ParamData.length}}】</el-link></el-tab-pane>
        <el-tab-pane :label="$t('content.input_file')" name="input_file"><el-link type="primary"> > input_file / 【{{InData.length}}】</el-link></el-tab-pane>
        <el-table
                :data="tableData"
                style="padding-bottom: 1rem"
                height="65vh"
                size="small"
                row-key="id"
                :tree-props="{children: 'dist'}"
                width="100%">
                <!--v-infinite-scroll="handleload"-->
                <!--infinite-scroll-distance="100">-->
            <el-table-column
                    min-width="5%">
            </el-table-column>
            <el-table-column
                    :label="$t('table.date')"
                    min-width="20%">
                <template slot-scope="scope">
                    <div v-if="scope.row.type == 'file'">
                        <i class="el-icon-time"></i>
                        <span>{{ scope.row.create_time |ResTime }}</span>

                    </div>
                </template>
            </el-table-column>
            <el-table-column
                    :label="$t('table.file_name')"
                    min-width="55%">
                <template slot-scope="scope">

                    <el-tag size="medium" type="warning" v-if="scope.row.type == 'file'" ><IconSvg :icon-class="scope.row.file_name|IconType"></IconSvg>{{ scope.row.file_name }}</el-tag>
                    <span v-else><IconSvg icon-class="icon-dist_icon"></IconSvg>{{ scope.row.file_name }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('table.operate')"
                             min-width="20%">
                <template slot-scope="scope">
                    <el-link type="primary" :href="scope.row.url" v-if="scope.row.type == 'file'" :download="scope.row.file_name">{{$t('info.download')}}</el-link>
                </template>
            </el-table-column>
        </el-table>
    </el-tabs>
    </div>
</template>

<script>
    import { FormatTime2,FileIcon } from '@/config/format';
    import IconSvg from '@/components/MagIcon';
    export default {
        name: "DownLoadTable",
        props:{
          DownData:Array,
          // RunTime:{                //更新的日志文件
          //   type:Number,
          //   default:()=>{
          //     return 0
          //   }
          // }
        },
        components:{
          IconSvg
        },
        watch:{
          DownData(nd,od){
            if(nd.length > 0){
              nd.forEach(item=>{                    //重新导入数据
                let target  = item.file_name.split('/')[0];
                switch (target) {
                  case 'result':this.ResData = item.dist;break;
                  case 'output_file':this.OutData = item.dist;break;
                  case 'parameter':this.ParamData = item.dist;break;
                  case 'input_file':this.InData = item.dist;break;
                  default:this.ResData.push(item);break;
                }
              });
              this.tableData = this.ResData;
              // console.log('获取数据')
            }else {
              this.tableData = []
            }
          }
        },
        data(){
          return {
              // FileName:'',                    //文件名
            activeName:'result',
            tableData:[],       //表格当前数据
            ResData:[],         //结果文件数据
            OutData:[],         //输出文件数据
            ParamData:[],       //参数文件数据
            InData:[],          //输入文件数据
            page:[],            //数据分页
          }
        },
        filters:{
            ResTime:function(time){
                return FormatTime2(time*1000);
            },
            IconType:function (name) {
                return FileIcon(name);
            }
        },
        methods:{

          handleClick(){
            if(this.DownData){
              switch (this.activeName) {
                case 'result':this.tableData = this.ResData;break;
                case 'output_file':this.tableData = this.OutData;break;
                case 'parameter':this.tableData = this.ParamData;break;
                case 'input_file':this.tableData = this.InData;break;
              }
            }
          },
          // handleload(){
          //   this.page ++;
          //   console.log('载入',this.page)
          // }
        },
        created(){
          // console.log('打开-down_table')
        },
        activated(){
          // console.log('打开-down_table')
        },
        destroyed(){
          // console.log('关闭-down_table')
        }
    }
</script>

<style scoped>

</style>
