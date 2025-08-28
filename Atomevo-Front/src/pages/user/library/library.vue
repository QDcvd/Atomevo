<template>
    <div>
        <div class="mag-step">
            <div style="font-weight: bold;margin: 0.5rem">计算文件库 &nbsp;&nbsp;

            </div>

        </div>
        <div class="mag-create-file">
            <el-row :gutter="20">
                <el-divider content-position="left">创建库文件记录</el-divider>
                <el-col :span="12">
                    <el-form ref="form" :model="Uploadform" label-position="left" label-width="80px">
                        <el-form-item :label="$t('table.file_name')" size="mini">
                            <el-input v-model="Uploadform.upload_name"
                                      class="library-input"
                                      placeholder="默认为上传文件名"
                            ></el-input>
                        </el-form-item>
                        <el-form-item label="描述:" size="mini">
                            <el-input
                                    type="textarea"
                                    class="library-input"
                                    :autosize="{ minRows: 2, maxRows: 4}"
                                    resize="none"
                                    placeholder="输入库文件介绍/描述(选填)"
                                    v-model="Uploadform.description">
                            </el-input>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" icon="el-icon-circle-plus-outline" size="mini"
                                       @click="UpDataFile">创建
                            </el-button>
                        </el-form-item>
                    </el-form>
                </el-col>
                <el-col :span="12">
                    <div style="margin: 0.5rem 0">
                        <el-switch
                                v-model="Uploadform.authority"
                                active-text="私人文件"
                                active-value="0"
                                inactive-text="公开库文件"
                                inactive-value="1">
                        </el-switch>
                    </div>

                    <el-upload
                            class="upload-demo"
                            :auto-upload="false"
                            :action="UpFile[0].url"
                            :on-preview="SetFileNameOnly"
                            :on-change="SetFile"
                            :on-remove="RemoveFile"
                            :file-list="filelist"
                            accept=".pdb,fasta,.dna,.txt,.mol,.mol2"
                            :limit="1">
                        <el-button slot="trigger" size="small" type="primary">添加文件</el-button>
                        <div slot="tip" class="el-upload__tip">只能上传pdb、fasta、dna、txt、mol、mol2文件，且不超过3MB</div>
                    </el-upload>
                </el-col>
            </el-row>
        </div>
        <div class="mag-step-left">
            <el-tabs type="border-card" @tab-click="handletabs" v-model="statusmode">
                <el-tab-pane label="私有库">
                    <el-table
                            :data="PrivateData"
                            border
                            width="100%"
                            height="42vh"
                            size="mini"
                            style="width: 100%">
                        <el-table-column
                                prop="id"
                                label="文件编号"
                                min-width="5%">
                        </el-table-column>
                        <el-table-column
                                prop="upload_name"
                                :label="$t('table.file_name')"
                                min-width="25%">
                        </el-table-column>
                        <el-table-column
                                prop="description"
                                label="描述"
                                min-width="40%">
                        </el-table-column>
                        <el-table-column
                                label="创建时间"
                                min-width="10%">
                            <template slot-scope="scope">
                                <span class="table-time">{{ scope.row.time |ResTime}}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                                label="操作"
                                min-width="15%">
                            <template slot-scope="scope">
                                <!--<el-button  type="text" size="small">编辑</el-button>-->
                                <!--<el-button @click="handleType(scope.row,1)" type="text" size="small">删除</el-button>-->
                                <el-link @click="handleType(scope.row,0)" type="primary" >{{$t('btn.edit')}}</el-link>
                                &nbsp;
                                <el-link @click="handleType(scope.row,1)" type="primary" >{{$t('btn.deleted')}}</el-link>
                                &nbsp;
                                <el-link type="primary" :href="scope.row.url">{{$t('info.download')}}</el-link>

                            </template>
                        </el-table-column>
                    </el-table>
                    <div style="margin-top: 1rem">
                        <el-pagination
                                layout="prev, pager, next"
                                @current-change="res=>{return handlePage(0,res)}"
                                :page-size="16"
                                :total="PriTotal">
                        </el-pagination>
                    </div>
                </el-tab-pane>
                <el-tab-pane label="公共库">
                    <el-table
                            :data="PublicData"
                            border
                            width="100%"
                            height="42vh"
                            size="mini"
                            style="width: 100%"
                            :row-class-name="isNewData"
                    >
                        <el-table-column
                                prop="id"
                                label="文件编号"
                                min-width="5%">
                        </el-table-column>
                        <el-table-column
                                prop="upload_name"
                                :label="$t('table.file_name')"
                                min-width="25%">
                        </el-table-column>
                        <el-table-column
                                prop="description"
                                label="描述"
                                min-width="40%">
                        </el-table-column>
                        <el-table-column
                                label="创建时间"
                                min-width="10%">
                            <template slot-scope="scope">
                                <span class="table-time">{{ scope.row.time |ResTime}}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                                :label="$t('info.download')"
                                min-width="10%">
                            <template slot-scope="scope">
                                <el-link type="primary" :href="scope.row.url">{{$t('info.download')}}</el-link>
                            </template>
                        </el-table-column>
                        <el-table-column
                                prop="realname"
                                label="提交者"
                                min-width="10%">
                        </el-table-column>
                    </el-table>
                    <div style="margin-top: 1rem">
                        <el-pagination
                                layout="prev, pager, next"
                                :page-size="16"
                                @current-change="res=>{return handlePage(1,res)}"
                                :total="PubTotal">
                        </el-pagination>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>
        <el-dialog
                class="library-dialog"
                :visible.sync="ModalShow"
                title="编辑文件"
                top="30vh"
                width="20%"
                :show-close="false">
            <div>

                <el-divider content-position="left">{{$t('table.file_name')}}</el-divider>
                <el-input v-model="ModalData.upload_name"
                          size="mini"
                          class="library-input"
                          placeholder="默认为上传文件名">
                </el-input>
                <el-divider content-position="left">描述</el-divider>
                <el-input
                        type="textarea"
                        size="mini"
                        class="library-input"
                        :autosize="{ minRows: 2, maxRows: 4}"
                        resize="none"
                        placeholder="输入库文件介绍/描述(选填)"
                        v-model="ModalData.description">
                </el-input>
                <el-divider content-position="left">是否公开</el-divider>
                <el-switch
                        v-model="ModalData.authority"
                        active-text="私人文件"
                        active-value="0"
                        inactive-text="公开库文件"
                        inactive-value="1">
                </el-switch>

            </div>
            <span slot="footer" class="dialog-footer">
                <el-button size="mini" @click="ModalShow = false">放弃</el-button>
                <el-button size="mini" type="primary" @click="handleFile(0,ModalData.id,ModalData.upload_name,ModalData.description,ModalData.authority)">保存</el-button>
            </span>
        </el-dialog>

    </div>
</template>

<script>
  import {FormatTime2} from '@/config/format';

  export default {
    name: "library",
    components: {},
    data() {
      return {
        resLoad: false,                  //loading 动画
        Uploadform: {
          upload_name: '',
          description: '',
          authority: 0,
        },
        statusmode: '',  //状态模式      0 是私有库，1是公有库
        PublicData: [            //公有
          //   {
          //   id:'',
          //   name:'',
          //   desc:''
          // }
        ],
        PubPage: 1,      //公共库页码
        PubTotal: 0,      //公共库页码
        PrivateData: [],          //私有
        PriPage: 1,              //私有库页码
        PriTotal: 0,              //私有库页码
        filelist: [],
        UpFile: [            //文件上传配置
          {
            url: '/magapi/tool/post'
          },
        ],
        ModalShow: false,
        ModalData: {},   //模态窗数据

      };
    },
    computed: {
      //任务id
    },
    watch: {},
    filters: {
      ResTime: function (time) {
        return FormatTime2(time * 1000);
      }
    },
    created() {
      // console.log()
      this.getFileLibrary('0', this.PriPage);    //初始化
    },
    activated() {


    },
    destroyed() {

    },
    methods: {
      //表格高亮显示
      //判断是否12小时内创建的内容(绿色高亮-仅时间)
      isNewData({row, rowIndex}) {
        if (new Date().getTime() - row.time * 1000 <= 12 * 60 * 60 * 1000) {
          return 'new-data-list';

        } else {
          return '';
        }
      },

      //添加文件到列表中
      SetFile(file, filelist) {
        // console.log(file,filelist)
        this.filelist = filelist;
        let name = file.name.split('.')
        this.Uploadform.upload_name = name[0];
      },
      //移除文件
      RemoveFile(file, filelist) {
        // console.log(file,filelist)
        this.Uploadform.upload_name = '';
        this.filelist = filelist;
      },
      SetFileNameOnly(file) {
        let name = file.name.split('.')
        this.Uploadform.upload_name = name[0]  //选中名字
      },
      //上传
      UpDataFile() {
        if(this.filelist.length == 0){
          return
        }
        let formData = new FormData();
        formData.append('upload_name', this.Uploadform.upload_name);
        formData.append('description', this.Uploadform.description);
        formData.append('authority', this.Uploadform.authority);
        formData.append('file', this.filelist[0].raw);
        this.$api.UploadLibrary(formData).then(res => {
          // console.log(res)
          this.$notify({
            title: this.$t('info.success'),
            iconClass: 'el-icon-upload',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify2">${res.msg}</strong>`,
          });
          this.getFileLibrary('0',this.PriPage);

        }).catch(err => {
          this.$notify({
            title: this.$t('info.error2'),
            iconClass: 'el-icon-error',
            dangerouslyUseHTMLString: true,
            message: `<strong class="mag-notify3">${err.msg}</strong>`,
          });
        });
      },
      //获取文件列表
      getFileLibrary(type, page, name) {
        let params = {
          keyword: name,         //搜索名字
          authority: type,
          page
        };
        this.$api.GainFileLibrary(params).then(res => {
          // console.log(res)
          switch (type) {
            case '0':
              this.PrivateData = res.list;
              this.PriTotal = res.recordsCount;
              break;
            case '1':
              this.PublicData = res.list;
              this.PubTotal = res.recordsCount;
              break;
          }
        })
      },
      handletabs() {         //切换tab
        // console.log(this.statusmode);
        switch (this.statusmode) {
          case '0':
            this.getFileLibrary('0', this.PriPage);
            break;
          case '1':
            this.getFileLibrary('1', this.PubPage);
            break;
        }
      },
      handlePage(type, res) {         //翻页
        // console.log('handle-->',type,res)
        switch (type) {
          case 0:
            this.PriPage = res;
            this.getFileLibrary('0', res);
            break;
          case 1:
            this.PubPage = res;
            this.getFileLibrary('1', res);
            break;
        }
      },
      //
      handleType(item, type) {
        // console.log(item,type)
        this.ModalData = item;
        switch (type) {
          case 0:                   //编辑
            // console.log('编辑');
            this.ModalShow = true;
            break;
          case 1:                   //删除
            this.$confirm('此操作将永久删除该文件【'+item.upload_name+'】, 是否继续?', '提示', {
              confirmButtonText: this.$t('btn.confirm'),
              cancelButtonText: '取消',
              type: 'warning'
            }).then(() => {
              this.handleFile(1,item.id);
            }).catch(() => {
              this.$message({
                type: 'info',
                message: '已取消删除'
              });
            });
            break;
        }
      },
      handleFile(del,id,name,desc,auth) {
        let data = {
          id,                   //文件Id
          upload_name:name,          //文件名
          description:desc,          //文件描述
          is_delete:del,            //是否删除（1：删 0: 不删）
          authority:auth            //私人或者公开
        };
        this.$api.EditFileLibrary(data).then(res=>{
          this.ModalShow =false;
          this.ModalData = {};  //清空数据
          this.getFileLibrary('0',this.PriPage);
            // console.log(res)
          this.$message({
            type: 'success',
            message: res.msg
          });
        })

      }

    }
  }
</script>

<style lang="less">
    .mag-create-file {
        margin: 0 1rem 2rem;
        background-color: #ebf1f5;
        border-radius: 0.5rem;
        padding: 0 2rem;

        .library-input {
            width: 60%;
        }
    }

    .mag-step-left {
        margin: 0 1rem;
    }

    .library-dialog {
        .el-dialog__header {
            padding: 0.5rem;
        }
        .el-dialog__body {
            padding: 0.5rem;
            .el-divider--horizontal {
                margin: 1rem 0;
            }
        }
    }

</style>



