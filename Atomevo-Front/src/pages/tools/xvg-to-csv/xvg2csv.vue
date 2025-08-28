<template >
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">XVG TO CSV
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
                    <!--:data="UpFile[1]"-->
                    <el-upload
                            class="upload-demo"
                            ref="upload"
                            :action="UpFile[0].url"
                            name="file"
                            multiple
                            :auto-upload="false"
                            :file-list="fileList"
                            :on-preview="SetFileNameOnly"
                            :on-change="SetFileName"
                            :on-remove="removeRes"
                            accept=".xvg"
                            :limit="20">
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
                            <el-button slot="reference" style="margin-left: 10px;" size="small" type="success" >上传到服务器</el-button>
                        </el-popover>
                        <div class="diyname" >
                            <el-input placeholder="(选填)默认同上传文件名" v-model="UpFile[1].down_name" size="small">
                                <template slot="prepend">自定义文件名</template>
                            </el-input>
                        </div>

                        <div slot="tip" class="el-upload__tip">只能上传xvg文件，且不超过3MB</div>
                    </el-upload>

                </div>
                <div class="mag-step-left">
                    <div>
                        <el-button type="primary" size="small" @click="RunXVG2CSV" :disabled="StepIndex==1?false:true">执行计算「{{FileID.length>0?FileID.length:'暂无文件'}}」</el-button>
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
        name: "xvg2csv",
        components:{
            DownloadTable
        },
        data(){
            return {
                info:info.XVG2CSV,         //注释
                InfoShow:false,                 //显示版本信息
                StepIndex:0,                   // 步骤位置定位
                isResult:true,                  // 处理结果
                resLoad:false,                  //loading 动画
                visible:false,                     //重复确认提示框
                isUpLoad:true,                          //确认上传最终按钮
                TaskID:'',                      //任务id
                FileID:[],                      //需要执行的id(多文件)
                isIndeterminate: true,          //效果图标
                fileList:[],                    //选中的文件列表
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
                        action:'uploads',           //这个s是多文件上传的接口
                        use:'xvg_to_csv',
                        down_name:'',       //自定义文件名
                        token:localStorage.getItem('token')
                    }

                ]

            };
        },
        computed:{
            TaksID(){
                return this.$store.state.XVG2CSVID;
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
                this.$message.closeAll();
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
            //上传文件(多文件)
            submitUpload(){

                this.visible = false;
                //校验文件大小是否违规3M
                let OverSize = new Array();
                this.fileList.forEach((file,index) => {
                    if(file.size/1024/1024 >3){

                        OverSize.push(index); //整理超限的文件index
                    }
                });
                // 统一弹出提示警告用户
                if(OverSize.length>0){
                    OverSize.forEach(idx=>{
                        setTimeout(()=>{
                            this.$notify({
                                title: '文件警告',
                                message: `文件【${this.fileList[idx].name}】超出3MB`,
                                position: 'top-left',
                                type: 'warning'
                            });
                        },200);   //需要加定时器，不然样式会折叠

                    });
                    return;
                }


                let formData = new FormData();  //  用FormData存放上传文件

                this.fileList.forEach(file => {
                    formData.append('file[]', file.raw)
                });
                //上传文件
                this.$api.UploadsFile(formData,this.UpFile[1].down_name).then((res) => {
                    this.StepIndex = 1;             //跳转步骤
                    this.FileID = res.id;             //获取返回的任务id

                    //修改成功图标
                    res.id.forEach((id,idx)=>{
                        this.fileList[idx].status = 'success';
                    });

                    this.$notify({
                        title: '完成',
                        message: '文件上传成功，可以执行计算',
                        type: 'success'
                    });

                })

            },


            //移除上传文件
            removeRes(file, fileList){

                if(fileList.length == 0){
                    this.isUpLoad = true //禁用上传
                }
                this.StepIndex = 0;
                this.TaskID = '';
                this.UpFile[1].down_name = '';

            },


            RunXVG2CSV(){

                // 后段传回来的是string
               let PFileID = this.FileID.map(num=>{
                    return parseInt(num);
                });
                PFileID.sort(function(a,b){ // 这是比较函数
                    return a - b;    // 升序
                });

                this.$api.GainXVG2GSV(PFileID).then(res=>{
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
                        use:'xvg_to_csv'
                    };
                    this.$api.GainFileList(param).then(res=>{
                        this.StepIndex = 4;
                        this.resLoad = false;
                        // console.log('任务结果',res.files)
                        this.FileData = res.files;

                        this.$store.commit("SelXVG2CSV", null);
                    })
                }
            },
            SetFileName(file,filelist){

                this.isUpLoad = false;
                if(this.UpFile[1].down_name.length == 0) {
                    this.UpFile[1].down_name = file.name.slice(0, file.name.length - 4);  //默认名字
                }

                this.fileList = filelist;
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
    .el-upload-list {
        margin: 0;
        padding: 0;
        list-style: none;
        overflow: auto;
        max-height: 23rem;
    }
</style>
