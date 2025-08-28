<template xmlns="http://www.w3.org/1999/html">
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">Auto-Martini
                    <el-button slot="reference" icon="el-icon-key" circle size="mini" @click="ShowTips"></el-button>
            </div>

            <el-steps :active="StepIndex" simple finish-status="success">
                <el-step title="上传源文件" icon="el-icon-document-add"></el-step>
                <el-step title="输入指令" icon="el-icon-monitor"></el-step>
                <el-step title="等待计算结果" icon="el-icon-loading"></el-step>
                <el-step title="完成" icon="el-icon-magic-stick"></el-step>
            </el-steps>
        </div>
        <el-row :gutter="20">
            <el-col :lg="24" :xl="12">
                <div class="mag-step-left">
                    <div>工程源文件</div>
                    <el-upload
                             class="upload-demo"
                             ref="upload"
                            :action="UpFile[0].url"
                             name="file"
                            :data="UpFile[1]"
                            :on-preview="SetFileNameOnly"
                            :on-change="SetFileName"
                            :auto-upload="false"
                            :on-remove="removeRes"
                             accept=".sdf,.smi"
                            :on-success="(res)=>{return submitRes(res)}"
                            :limit="1">
                        <el-button slot="trigger" size="small" type="primary">选取文件</el-button>
                        <el-popover
                                placement="top"
                                width="200"
                                v-model="visible">
                            <p>执行任务文件名以【<strong>{{UpFile[1].down_name}}</strong>】上传至服务器？</p>
                            <div style="text-align: right; margin: 0">
                                <el-button size="mini" type="text" @click="visible = false">取消</el-button>
                                <el-button type="success" size="mini" @click="submitUpload" :disabled="isUpLoad">确定并上传</el-button>
                            </div>
                            <el-button slot="reference" style="margin-left: 10px;" size="small" type="success">上传到服务器</el-button>
                        </el-popover>
                        <div class="diyname" >
                            <el-input placeholder="(选填)默认同上传文件名" v-model="UpFile[1].down_name" size="small">
                                <template slot="prepend">自定义文件名</template>
                            </el-input>
                        </div>

                        <div slot="tip" class="el-upload__tip">只能上传sdf/smi文件，且不超过3MB</div>
                    </el-upload>

                </div>
                <div class="mag-step-left">
                    <div>
                        <el-button type="primary" size="small" @click="RunMartini" :disabled="StepIndex==1?false:true">执行计算</el-button>
                    </div>
                    <div>
                        <!--<el-checkbox :indeterminate="isIndeterminate" @change="AllMode">全选</el-checkbox>-->
                        <div style="margin: 15px 0;"></div>
                        <!--<el-checkbox-group v-model="checkedMode" @change="SelMode">-->
                            <el-checkbox v-for="(item,index) in Modelist" :label="item.res" :key="index" true-label="1" false-label="0" v-model="item.res" :checked="item.res==1?true:false" >{{item.mode}}</el-checkbox>
                        <!--</el-checkbox-group>-->
                    </div>
                    <div style="width: 60%">
                        <div style="margin: 15px 0;"></div>
                        <el-input placeholder="输入mol自定义值" v-model="MolType" size="small">
                            <template slot="prepend">--mol</template>
                        </el-input>
                    </div>

                </div>
                <div class="mag-step-left">
                    <div>
                        <el-link type="primary">■sdf/smi</el-link>: 输入文件, 指定其中一个就可以. 可以使用openbabel将pdb或其他格式的文件转化为sdf或者smi文件. <br>
                        <el-link type="warning">■--mol</el-link>: 必须选项, 输出文件中残基的名称<br>
                        <el-link type="warning">■--xyz、 --gro</el-link>: 可选的输出文件<br>
                        <el-link type="warning">■--verbose、 --fpred</el-link>: 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差<br>
                    </div>
                </div>
            </el-col>
            <el-col :lg="24" :xl="12">

                <div class="mag-step-right"
                     v-loading="resLoad"
                     element-loading-text="等待计算结果中...">
                    <div>处理结果 <i :class="isResult?'el-icon-success':'el-icon-error'"></i></div>
                    <!--成功的结果-->
                    <div v-if="isResult" class="res-success">

                        <download-table :DownData="FileData"/>
                        <!--<el-table-->
                                <!--:data="FileData"-->
                                <!--height="57vh"-->
                                <!--width="100%">-->
                            <!--<el-table-column-->
                                    <!--label="日期"-->
                                    <!--min-width="25%">-->
                                <!--<template slot-scope="scope">-->
                                    <!--<i class="el-icon-time"></i>-->
                                    <!--<span style="margin-left: 10px">{{ scope.row.success_time |ResTime }}</span>-->
                                <!--</template>-->
                            <!--</el-table-column>-->
                            <!--<el-table-column-->
                                    <!--label="文件名"-->
                                    <!--min-width="50%">-->
                                <!--<template slot-scope="scope">-->
                                    <!--<el-tag size="medium" :type="scope.row.suffix == 'zip'? 'success':'info'">{{ FileName.length>1?FileName+'.'+scope.row.suffix:scope.row.name }}</el-tag>-->
                                <!--</template>-->
                            <!--</el-table-column>-->
                            <!--<el-table-column label="操作"-->
                                             <!--min-width="25%">-->
                                <!--<template slot-scope="scope">-->
                                    <!--<el-link type="primary" :href="FileName.length>1?scope.row.url+'&new_name='+FileName+'.'+scope.row.suffix:scope.row.url+'&new_name='+scope.row.name" >下载</el-link>-->
                                <!--</template>-->
                            <!--</el-table-column>-->
                        <!--</el-table>-->
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
    import DownloadTable from '@/components/download_table';
    import info from '@/info';
    export default {
        name: "automartini",
        components:{
            DownloadTable
        },
        data(){
            return {
                info:info.AutoMartini,         //注释
                InfoShow:false,                 //显示版本信息
                StepIndex:0,                   // 步骤位置定位
                isResult:true,                  // 处理结果
                resLoad:false,                  //loading 动画
                MolType:'',                     //mol 输入的分子类
                visible:false,                     //重复确认提示框
                isUpLoad:true,                          //确认上传最终按钮
                FileID:'',                      //文件id
                isIndeterminate: true,          //效果图标
                Modelist:[                      //可选模式  默认选中 1
                    {
                        mode:'--mol',
                        res:1
                    },
                    {
                        mode:'--gro',
                        res:1
                    },
                    {
                        mode:'--xyz',
                        res:0
                    },
                    {
                        mode:'--verbose',
                        res:0
                    },
                    {
                        mode:'--fpred',
                        res:0
                    }
                ],
                FileData: [     //返回的文件列表
                    // {
                    //     success_time: '2016-05-02',
                    //     name: '工程文件_AB0023.xyz',
                    //     url:'www.baidu.com/test/test.xyz'
                    // },
                ],
                UpFile:[            //文件上传配置
                    {
                        url:'/magapi/tool/post'
                    },
                    {
                        method:'upload',
                        action:'upload',
                        use:'auto_martini',
                        down_name:'',       //自定义文件名
                        token:localStorage.getItem('token')
                    }

                ]

            };
        },
        computed:{
            //任务id
          TaksID(){
              return this.$store.state.MartiniID;
          }
        },
        watch:{
            TaksID(newRes,oldRes){
                // console.log('新的任务id',newRes);
                // console.log('old task',oldRes);
                this.GetDown(newRes);
          }
        },
        created(){
          // console.log()

            if(this.$route.query.taskID){
                // this.$store.commit("SelMartini", this.$route.query.taskID);
                this.GetDown(this.$route.query.taskID);
            }else {
               this.ShowTips();
            }

        },
        destroyed(){

        },
        methods:{
            //版权信息
            ShowTips(){
                this.$message.closeAll()
                this.$message({
                    dangerouslyUseHTMLString: true,
                    duration:1500,
                    message: this.info
                });
            },
            //选中的模式
            SelMode(val){
                this.checkedMode = val;
            },
            //上传文件
            submitUpload(){
                this.$refs.upload.submit();
                this.visible = false;
            },

            //接收上传成功
            submitRes(res){
                if(res.resultCode ===1 ){
                    this.StepIndex = 1;
                    this.FileID = res.data.id;

                    this.$notify({
                        title: '完成',
                        message: '文件上传成功，可以执行计算',
                        type: 'success'
                    });
                }

            },
            //移除上传文件
            removeRes(){
                this.isUpLoad = true; //禁用上传
                this.StepIndex = 0;
                this.FileID = '';
                this.UpFile[1].down_name = '';

            },

             // 执行计算操作
            /*
            @Param mol (字符值)--mol: 必须选项, 输出文件中残基的名称
            @Param gro (1:选中0:不选中)--xyz: 可选的输出文件
            @Param xyz (1:选中0:不选中)--gro: 可选的输出文件
            @Param verbose (1:选中0:不选中)--verbose: 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差
            @Param fpred (1:选中0:不选中)--fpred: 无法找到符合的参数时, 使用按原子或按片段判别珠子的方法, 准确度较差
            */
            RunMartini(){
                let Params ={
                    id:this.FileID,
                    mol: this.MolType,          //mol为输入必选项
                    gro: this.Modelist[1].res,
                    xyz: this.Modelist[2].res,
                    verbose: this.Modelist[3].res,
                    fpred: this.Modelist[4].res,
                };
                this.$api.GainMartini(Params).then(res=>{
                    // console.log(res)
                    this.$notify({
                        title: '创建任务成功',
                        iconClass:'el-icon-upload',
                        dangerouslyUseHTMLString: true,
                        message: `<strong class="mag-notify2">${res.msg}</strong>`,
                    });
                    this.StepIndex = 2;
                    this.resLoad = true;
                }).catch(err=>{
                    this.$notify({
                        title: '操作错误',
                        iconClass:'el-icon-error',
                        dangerouslyUseHTMLString: true,
                        message: `<strong class="mag-notify3">${err.data.msg}</strong>`,
                    });

                })

            },

            //获取下载
            GetDown(id){
                if(id == null ){
                    return
                }else {
                    let param = {
                        id,
                        use:'auto_martini'
                    };
                    this.$api.GainFileList(param).then(res=>{
                        this.StepIndex = 4;
                        this.resLoad = false;
                        // console.log('任务结果',res.files)
                        this.FileData = res.files;

                        this.$store.commit("SelMartini", null);
                    })
                }
            },
            SetFileName(file){
                this.isUpLoad = false;
                if(this.UpFile[1].down_name.length == 0) {
                    this.UpFile[1].down_name = file.name.slice(0, file.name.length - 4);  //默认名字
                }
            },
            SetFileNameOnly(file){
                this.UpFile[1].down_name = file.name.slice(0, file.name.length - 4);  //选中名字
            }

        }
    }
</script>

<style lang="less">
    .res-error{
        font-size: 0.3rem;
        height: 45vh;
        overflow: auto;
    }
</style>
