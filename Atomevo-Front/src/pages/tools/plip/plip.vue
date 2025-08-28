<template xmlns="http://www.w3.org/1999/html">
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">Protein-Ligand Interaction Profiler
                <div class="tool-declaration">
                    {{$t('tools.Plip')}}
                </div>
            </div>

            <el-steps :active="StepIndex" simple finish-status="success">
                <el-step :title="$t('content.step1')" icon="el-icon-document-add"></el-step>
                <el-step :title="$t('content.step2')" icon="el-icon-monitor"></el-step>
                <el-step :title="$t('content.step3')" icon="el-icon-loading"></el-step>
                <el-step :title="$t('content.step4')" icon="el-icon-magic-stick"></el-step>
            </el-steps>
        </div>

        <div class="mag-step-left">
            <el-switch
                    disabled
                    style="display: block"
                    v-model="Modeswitch"
                    @change="SetMode"
                    active-text="PDB file"
                    inactive-text="PDB ID">
            </el-switch>
            <el-divider></el-divider>
            <div v-if="Modeswitch">
                <UpLoadFile FormatType=".pdb" UseType="plip" @GetIDList="GetFileID" :Count="1"></UpLoadFile>
            </div>
            <div v-else>
                <el-input placeholder="请输入查询ID" v-model="pdbID" style="width: 40%">
                    <template slot="prepend">PDB-ID</template>
                </el-input>
            </div>
            <div style="margin-top: 1rem">
                <div style="margin: 0.5rem 0;width: 30%" v-if="Modeswitch">
                    <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                        <template slot="prepend">{{$t('content.down_name')}}(Zip)</template>
                    </el-input>
                </div>
                <el-button type="primary" size="small" @click="RunPLIP" :disabled="StepIndex==1?false:true">Run
                    analysis
                </el-button>

            </div>
        </div>
        <div class="mag-step-left">
            <!--<el-link type="primary" :href="scope.row.url" >下载</el-link>-->

            <download-table :DownData="FileData"/>
        </div>
        <div class="mag-step-left" id="PreviewMain">
            <!--<el-button @click="PreviewPdb">预览</el-button>-->
            <!--<el-tabs type="card"  v-model="activeName">-->
            <!--<el-tab-pane  v-for="(item,index) in PreviewData" :label="item.name" :name="index" :key="index" >-->
            <!--<div style="height: 400px; width: 400px; position: relative;"-->
            <!--class='viewer_3Dmoljs'-->
            <!--data-href='http://api.magical.liulianpisa.top/tool/get?token=5ca5acb9dc3743b8fdfbe0882eac8cb5&method=tasks&action=tasks_file_catch&id=130&file_name=1vsn.pdb&new_name=1vsn.pdb'-->
            <!--data-backgroundcolor='0xffffff'-->
            <!--data-surface1='opacity:.7;color:white'-->
            <!--data-style='stick'></div>-->
            <!--</el-tab-pane>-->
            <!--</el-tabs>-->
            <!--<div style="height: 400px; width: 400px; position: relative;"-->
            <!--v-for="(item,index) in PreviewData"-->
            <!--:key="index"-->
            <!--class='viewer_3Dmoljs'-->
            <!--data-href=''-->
            <!--data-backgroundcolor='0xffffff'-->
            <!--data-surface1='opacity:.7;color:white'-->
            <!--data-style='stick'></div>-->

        </div>


    </div>
</template>

<script>
  import { mapState } from 'vuex';
  // const parseString = require('xml2js').parseString;
  import DownloadTable from '@/components/DownLoadTable';
  import UpLoadFile from '@/components/UploadFile';
  // import info from '@/info';

  export default {
    name: "plip",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      return {
        // info: info.Plip,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        Modeswitch: true,                      // 文件 和 id查询之间切换
        pdbID: '',
        isResult: true,                  // 处理结果
        FileID: '',                              //文件id
        isXML: false,
        XMLMain: [],                       //XML 解析出来的渲染内容
        XMLactiveNames: '',                  //xml 展开
        isIndeterminate: true,          //效果图标
        FileData: [     //返回的文件列表
          // {
          //     success_time: '2016-05-02',
          //     name: '工程文件_AB0023.xyz',
          //     url:'www.baidu.com/test/test.xyz'
          // },
        ],
        PreviewData: [],    //预览文件
        activeName: 0,
        ZipName:'',         //压缩包名字

      };
    },
    filters: {

    },
    computed: {
      //任务id
      ...mapState({
        TaksID:state => state.TaskIDList.Plip
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
      this.FileData = [];

    },
    destroyed() {

    },
    methods: {

      //切换模式
      SetMode(state) {
        if (state) { //文件查询

          this.StepIndex = 0;
        } else {    //id查询
          this.StepIndex = 1
        }
      },
      //选中的模式
      SelMode(val) {
        this.checkedMode = val;
      },

      GetFileID(ids){
        this.FileID = ids;
      },




      RunPLIP() {
        let Params = {
          ids: this.FileID.toString(),
          down_name: this.ZipName
        };
        this.$api.GainPLIP(Params).then(res => {
          // console.log(res)
          this.$notify({
            title: this.$t('info.create_task'),
            iconClass: 'el-icon-upload',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify2">${res.msg}</strong>`,
          });
          this.StepIndex = 2;

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
            this.PreviewData = res.preview;
            this.$store.commit(`TaskIDList/${this.$route.name}_ID`, null);
          })
        }
      },


      PreviewPdb() {
        //   let mode = [];
        //   this.PreviewData.forEach((item,index)=>{
        //
        //      let list = `
        //           <div style="height: 400px; width: 400px; position: relative;"
        //            class='viewer_3Dmoljs'
        //            data-href='${item.url}'
        //            data-backgroundcolor='0xffffff'
        //            data-surface1='opacity:.7;color:white'
        //            data-style='stick'></div>
        //         `;
        //     mode.push(list);
        //
        //     // console.log(mode);
        //   })
        //   let doc = document.getElementById("PreviewMain");
        //   doc.innerHTML = mode
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
</style>
