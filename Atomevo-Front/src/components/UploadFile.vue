<template>
    <div style="margin: 0.5rem 0">
        <el-upload
                class="upload-demo"
                :ref="UseType"
                :action="url"
                name="file"
                :multiple = "multi"
                :on-change="handleChange"
                :auto-upload="false"
                :accept="FormatType"
                :show-file-list="false"
                :file-list="FileList"
                :limit="Count">
            <el-button slot="trigger" size="mini" type="primary">{{$t('content.upload_focus')}}</el-button>
            <el-popover
                    placement="top"
                    width="200"
                    v-model="visible">
                <p>{{$t('msg.upload_msg')}}</p>
                <div style="text-align: right; margin: 0">
                    <el-button size="mini" type="text" @click="visible = false">{{$t('btn.cancel')}}</el-button>
                    <el-button type="success" size="mini" @click="handleSubmit" :disabled="!FileList.length>0 ||isRemove">
                        {{$t('btn.confirm')}}
                    </el-button>
                </div>
                <el-button slot="reference" style="margin-left: 10px;" size="mini" type="success">{{$t('content.upload_server')}}
                </el-button>
            </el-popover>
            <el-button type="warning" size="mini" style="margin-left: 0.5rem" :disabled="!FileList.length>0" @click="handleRemove('all')">{{$t('content.upload_clear')}}「{{FileList.length}}」</el-button>
            <div slot="tip" class="el-upload__tip">
                <span v-if="Mode === 1">{{$t('content.upload_txt1')}}{{FormatType}}{{$t('content.upload_txt2')}}{{$t('content.upload_txt3')}}{{MaxSize}}MB</span>
                <div v-else>
                    <div>
                        <span >{{$t('content.upload_txt1')}}{{FormatType}}{{$t('content.upload_txt2')}}{{$t('content.upload_txt4')}}{{MaxSize}}MB</span>
                    </div>
                    <el-button type="primary" icon="el-icon-s-opportunity" circle size="mini" @click="OpenZipTips = true"></el-button> &nbsp;
                    <el-tag type="danger"> {{$t('msg.upload_msg2')}}</el-tag>
                </div>


            </div>
        </el-upload>

        <div class="tips-font">{{$t('content.upload_txt5')}}「{{FileList.length}}」</div>
        <el-table
                :data="FileList"
                size="mini"
                width="100%"
                :height="Tableheight"
                v-loading="md5loading"
                :element-loading-text="md5ShowText">
            <el-table-column
                    fixed="left"
                    :label="$t('table.file_name')"
                    min-width="60%"
            >
                <template slot-scope="scope">
                    <IconSvg :icon-class="scope.row.name|IconType"></IconSvg>
                    {{scope.row.name}}
                </template>
            </el-table-column>

            <el-table-column
                    align="center"
                    :label="$t('table.size')"
                    min-width="15%"
                    >
                <template slot-scope="scope">
                    {{scope.row.size|MBSize}}
                </template>
            </el-table-column>
            <el-table-column
                    align="center"
                    :label="$t('table.edit')"
                    min-width="15%"
                    >
                <template slot-scope="scope">
                    <!--移除{{scope.$index}}-->
                    <el-button  icon="el-icon-delete"  size="mini" @click="handleRemove(scope.$index)" :disabled="isRemove">{{$t('btn.deleted')}}</el-button>
                </template>
            </el-table-column>
        </el-table>
        <el-dialog
                title="Zip操作提示"
                :visible.sync="OpenZipTips"
                width="35%">
            <el-image
                    style="width: 100%; height: 100%"
                    :src="TipsUrl"
                    fit="contain"></el-image>

            <span slot="footer" class="dialog-footer">
            <el-button type="primary" @click="OpenZipTips = false">{{$t('btn.confirm')}}</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
  import IconSvg from '@/components/MagIcon';
  import { FileIcon } from '@/config/format';
  import { TipsBase64 } from '@/components/image/base64';
  import BMF from 'browser-md5-file';
  export default {
    name: "UploadFile",
    components:{
      IconSvg
    },
    props:{
      UseType:{                     //上传的目的用途   （请求携带）
        type:String,
        default:()=>{
            return 'plants'          //默认使用plants
        }
      },
      FormatType:{                  //支持的上传格式
        type:String,
        default:()=>{
            return '.mol'           //默认mol文件
        }
      },
      MaxSize:{                     //单个文件最大限制 (单位MB)
        type:Number,
        default:()=>{
            return 3                //默认3MB
        }
      },
      Count:{                       //文件添加数量，不设置则20个
        type:Number,
        default:()=>{
          return 20
        }
      },
      Mode:{                        //文件上传模式  默认：1 常规模式 ， 2 Zip分片上传模式
        type:Number,
        default:()=>{
          return 1
        }
      }


    },
    computed:{
      Tableheight(){                //动态表格高度
        if(this.Count > 2){
          this.multi = true;
          return '15rem';

        }else {
          this.multi = false;
          return '7rem';
        }
      },

    },
    data(){
      return{
        //@Param:  'ligand' ,'plants'
        // UseType:'ligand',             //上传的目的用途   （请求携带）
        url:'',                 //请求连接
        FileList:[],            //文件列表
        multi:true,            //多任务计算
        visible:false,
        isUpLoad:true,
        isRemove:false,             //是否允许移除操作，如果文件上传完禁止移除
        md5loading:false,           //md5文件校验时候的loading
        md5ShowText:'',           //md5文件校验时候的文字提示
        OpenZipTips:false,          //压缩包例子图片展示
        TipsUrl:TipsBase64,         //图片地址
      }
    },
    filters:{
      MBSize(size){             //显示文件大小格式单位
        let ToKB = '';
        let ToMB = '';
        if (size < 1024*1024){
          ToKB  = (size/1024).toFixed(2);
          return `${ToKB}KB`;
        }else {
          ToKB = size/1024;
          ToMB = (ToKB/1024).toFixed(2);
          return `${ToMB}MB`;
        }
      },
      IconType:function (name) {        //文件图标
        return FileIcon(name);
      }
    },
    methods:{

      handleChange(file){
            // console.log(file)


            if(file.size < this.MaxSize*1024*1024){
                this.FileList.push(file);           //符合文件添加 //添加符合zip标准的文件
            }else{
              let TimeSW = setTimeout(() => {
                this.$notify({
                  title: '文件警告',
                  message: `文件【${file.name}】超出限制大小`,
                  position: 'top-left',
                  type: 'warning'
                });
                clearTimeout(TimeSW)
              }, 200);
            }
      },
      handleRemove(type){
            // console.log(type)
          if (type == 'all'){
            this.$confirm(this.$t('info.file_clear_all'), this.$t('info.tips'), {
              confirmButtonText: this.$t('btn.confirm'),
              cancelButtonText: this.$t('btn.cancel'),
              size:'mini',
              type: 'warning'
            }).then(() => {
              this.FileList = [];
              this.isRemove = false;    //恢复移除功能
              this.$emit('GetIDList','');       //传递排序完成的id数组
              this.$message({
                type: 'success',
                message: '清除成功!'
              });

            })

            // return
          }else {
            this.FileList.splice(type,1);

          }

      },
      handleSuccess(){

      },
      handleSubmit(){               //执行模式选择判断
        switch (this.Mode) {
          case 1:this.NormalMode();break;
          case 2:this.ZipMode();break;
        }
      },

      //  通常情况的文件上传
      NormalMode(){
        let formData = new FormData();  //  用FormData存放上传文件
        this.FileList.forEach(file => {
          formData.append('file[]', file.raw)
        });
        this.$api.UploadFiles(formData, this.UseType).then((res) => {         //上传文件
          this.visible = false;   //隐藏提示框
          this.$notify({
            title: this.$t('info.success'),
            message: `${this.UseType}用途文件上传成功`,
            type: 'success'});

          //从小到大排序返回的任务id
          let IDlist = res.id;
          let PIDlist = IDlist.map(num => {
            return parseInt(num)
          });
          PIDlist.sort(function (a, b) {
            return a - b;
          });
          this.$emit('GetIDList',PIDlist);       //传递排序完成的id数组
          this.isRemove = true;             //文件移除禁止
        }).catch(err=>{
          this.$notify({
            title: this.$t('info.error'),
            message: err.msg,
            type: 'error'});
        })
      },
      async ZipMode(){

        this.visible = false;   //隐藏提示框
        let BlobInfo = await this.FileChunk(this.FileList[0]);
        this.PushZipFile(BlobInfo);


      },
      //js分片函数
       async FileChunk(File){
        let Run = 1;                        //运行次数
        let ChunkSize = 1024*1024;          //1M 一块
        let Size = File.size;               //文件大小
        let ChunkSum = Math.ceil(Size/ChunkSize);    //共分多少块
        let BlobList = [];
        while(Run<=ChunkSum){
            let NextChunk = Math.min(Run*ChunkSize,Size);        //下一片区块
            let FileData = File.raw.slice((Run-1)*ChunkSize,NextChunk);     //每一块的文件
            BlobList.push({blob:FileData,index:Run});       //blob 区块文件，index 位置
            Run ++;
        }

        return {
            List:BlobList,
            Chunk:ChunkSum,
            Md5:await this.GetFileMd5(File)
        };            //返回blob数组、参数
      },

      GetFileMd5(File){         //获取文件md5
        return new Promise((resolve, reject) => {
          const bmf = new BMF();
          bmf.md5(File.raw,
            (err, md5) => {
              // console.log('md5 string:', md5);
              resolve(md5);
              reject(err);
            },
            progress => {
              if (progress<1){
                this.md5loading = true;
                let num = progress.toFixed(2)*100;
                this.md5ShowText =`文件校验中${Math.ceil(num)}%`;
              } else {
                this.md5ShowText = '校验完成';
                this.md5loading = false;
              }
            },
          );
        })
      },
      PushZipFile(File,Run){                // 同步按顺序上传
        let Step = Run?Run:1;                   //如果没有步数定位，则默认初始化
        let AllStep =File.List.length;          //需要执行总步数
        let item = File.List[Step-1];           //定位数组中的item内容
        let Data = new FormData();
        Data.append('zip',item.blob);
        Data.append('blob_num',item.index);
        Data.append('total_blob_num',AllStep);
        Data.append('md5_file',File.Md5);
        let tags = File.Md5+item.index;
        this.$api.UploadZip(Data,tags).then(res=>{
            this.md5loading = true;
            if(Step<AllStep){
              let NextStep = Step + 1;
              this.md5ShowText =`文件上传中${Math.ceil(Step/AllStep*100)}%`;
              this.PushZipFile(File,NextStep)
            }else {
              this.md5ShowText =`上传完成`;
              this.md5loading = false;
              this.isRemove = true;
              this.$emit('GetIDList',res.file_id);       //传递排序完成的md5id
              this.$notify({
                title: 'Zip创建成功',
                iconClass: 'el-icon-upload',
                dangerouslyUseHTMLString: true,
                message: `<strong class="mag-notify2">${res.msg}</strong>`,
              });
            }
        }).catch(err=>{
          this.md5loading = false;
          console.log(err.data)
          this.$notify({
            title: err.data.file_id!==undefined?'文件加速':'操作异常',
            iconClass: err.data.file_id!==undefined?'el-icon-upload':'el-icon-error',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify3">${err.msg}</strong>`,
          });
          if(err.data.tips === 1){
            this.OpenZipTips = true;
          }

          if(err.data.file_id!==undefined){         //如果有id
            this.isRemove = true;
            this.$emit('GetIDList',err.data.file_id);       //传递排序完成的md5id
          }else{
            this.isRemove = false;
          }

        });

      },


    },
    activated(){
      // console.log('-创建组件-',this.UseType)
    },
    deactivated(){
      // console.log('卸载组件',this.UseType)
    },

    destroyed(){
      // this.FileList = [];       //清空
      // console.log('卸载组件2',this.UseType)
    }
  }
</script>

<style lang="less">
    .tips-font{
        font-size: 0.5rem;
        margin: 0.2rem 0;
    }
</style>
