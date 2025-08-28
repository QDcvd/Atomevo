<template >
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">Auto-Martini
                <!--<el-button slot="reference" icon="el-icon-key" circle size="mini" @click="ShowTips"></el-button>-->
                <div class="tool-declaration">
                    {{$t('tools.AutoMartini')}}
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
                    <UpLoadFile FormatType=".sdf,.smi" UseType="auto_martini" @GetIDList="GetFileID" :Count="1"></UpLoadFile>
                </div>
                <div class="mag-step-left">
                    <div class="diyname">
                        <el-input :placeholder="$t('content.custom_down_name')" v-model="ZipName" size="small">
                            <template slot="prepend">{{$t('content.down_name')}}</template>
                        </el-input>
                    </div>
                    <div>

                        <el-button type="primary" size="small" @click="RunMartini" :disabled="StepIndex==1?false:true">
                            {{$t('btn.run')}}
                        </el-button>
                    </div>
                    <div>
                        <!--<el-checkbox :indeterminate="isIndeterminate" @change="AllMode">全选</el-checkbox>-->
                        <div style="margin: 15px 0;"></div>
                        <!--<el-checkbox-group v-model="checkedMode" @change="SelMode">-->
                        <el-checkbox v-for="(item,index) in Modelist" :label="item.res" :key="index" true-label="1"
                                     false-label="0" v-model="item.res" :checked="item.res==1?true:false">{{item.mode}}
                        </el-checkbox>
                        <!--</el-checkbox-group>-->
                    </div>
                    <div style="width: 60%">
                        <div style="margin: 15px 0;"></div>
                        <el-input :placeholder="$t('content.martini_mol')" v-model="MolType" size="small">
                            <template slot="prepend">--mol</template>
                        </el-input>
                    </div>

                </div>
                <div class="mag-step-left">
                    <div>
                        <el-link type="primary">■sdf/smi</el-link>
                        : 输入文件, 指定其中一个就可以. 可以使用openbabel将pdb或其他格式的文件转化为sdf或者smi文件. <br>
                        <el-link type="warning">■--mol</el-link>
                        : 必须选项, 输出文件中残基的名称<br>
                        <el-link type="warning">■--xyz、 --gro</el-link>
                        : 可选的输出文件<br>
                        <el-link type="warning">■--verbose、 --fpred</el-link>
                        : 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差<br>
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
    name: "auto_martini",
    components: {
      DownloadTable,
      UpLoadFile
    },
    data() {
      return {
        // info: info.AutoMartini,         //注释
        InfoShow: false,                 //显示版本信息
        StepIndex: 0,                   // 步骤位置定位
        isResult: true,                  // 处理结果
        resLoad: false,                  //loading 动画
        MolType: '',                     //mol 输入的分子类
        FileID: [],                      //文件id
        ZipName:'',                     //压缩包文件名
        isIndeterminate: true,          //效果图标
        Modelist: [                      //可选模式  默认选中 1
          {
            mode: '--mol',
            res: 1
          },
          {
            mode: '--gro',
            res: 1
          },
          {
            mode: '--xyz',
            res: 0
          },
          {
            mode: '--verbose',
            res: 0
          },
          {
            mode: '--fpred',
            res: 0
          }
        ],
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
        TaksID:state => state.TaskIDList.Auto_Martini
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

      //接收上传成功
      submitRes(res) {
        if (res.resultCode === 1) {
          this.StepIndex = 1;
          this.FileID = res.data.id;

          this.$notify({
            title: '完成',
            message: '文件上传成功，可以执行计算',
            type: 'success'
          });
        }

      },

      // 执行计算操作
      /*
      @Param mol (字符值)--mol: 必须选项, 输出文件中残基的名称
      @Param gro (1:选中0:不选中)--xyz: 可选的输出文件
      @Param xyz (1:选中0:不选中)--gro: 可选的输出文件
      @Param verbose (1:选中0:不选中)--verbose: 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差
      @Param fpred (1:选中0:不选中)--fpred: 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差
      */
      RunMartini() {
        let Params = {
          ids: this.FileID.toString(),
          mol: this.MolType,          //mol为输入必选项
          gro: this.Modelist[1].res,
          xyz: this.Modelist[2].res,
          verbose: this.Modelist[3].res,
          fpred: this.Modelist[4].res,
          down_name:this.ZipName
        };
        this.$api.GainMartini(Params).then(res => {
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
