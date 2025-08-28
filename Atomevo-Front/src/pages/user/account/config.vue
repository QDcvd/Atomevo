<template>
    <div>
        <div class="mag-step-left">
            <!--{id: "5", last_ip: "211.69.128.41", last_time: "1571563452", realname: "曹诗林", auth: "2"}-->
            <el-table
                    :data="tableData"
                    border
                    height="68vh"
                    width="100%"
                    v-loading="TableLoading">
                <el-table-column
                        prop="id"
                        label="账户编号"
                        min-width="15%">
                </el-table-column>
                <el-table-column
                        prop="realname"
                        label="注册昵称"
                        min-width="20%">
                </el-table-column>
                <el-table-column
                        prop="auth"
                        label="账户类型"
                        min-width="10%">
                    <template slot-scope="scope">
                        <el-tag size="medium" type="success">{{ scope.row.auth |Hierarchy }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                        prop="last_time"
                        label="最后登陆时间"
                        min-width="20%">
                    <template slot-scope="scope">
                        {{scope.row.last_time|ResTime}}
                    </template>
                </el-table-column>
                <el-table-column
                        prop="last_ip"
                        label="最后登陆IP"
                        min-width="15%">
                </el-table-column>

                <el-table-column
                        label="操作"
                        min-width="20%">
                    <template slot-scope="scope">
                        <el-button @click="handleClick(scope.row)" type="text" size="small">修改</el-button>
                    </template>
                </el-table-column>
            </el-table>

        </div>
        <div class="mag-pagination">
            <el-pagination
                    background
                    layout="prev, pager, next"
                    :page-size="userpagesize"
                    @current-change="handleCurrentChange"
                    :current-page.sync="page"
                    :total="usertotal">
            </el-pagination>
        </div>
        <el-dialog
                class="info-dialog"
                :visible.sync="dialogVisible"
                top="30vh"
                width="20%"
                :show-close="false"
                :before-close="handleClose">
            <div >

                <el-divider content-position="left">ID:{{FocusUser.id}}【{{FocusUser.realname}}】</el-divider>
                权限调整：
                <el-select v-model="FocusUser.auth" placeholder="选择用类型" size="mini">
                    <el-option label="高级用户" value="2"></el-option>
                    <el-option label="普通用户" value="3"></el-option>
                </el-select>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button size="mini" @click="dialogVisible = false">放弃</el-button>
                <el-button size="mini" type="primary" @click="handleUserRoles">保存</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
  import {UserType, FormatTime2} from '@/config/format';

  export default {
    name: "config",
    data() {
      return {
        search: '',
        tableData: [],              //表单数据
        page: 1,
        usertotal: 1,
        userpagesize: 12,
        dialogVisible:false,        //修改提示框
        FocusUser:{},               //提示框的用户资料
        TableLoading:false,         //loading显示
      }
    },
    created() {
      this.GetUserList(this.page);              //数据初始化
    },
    filters: {
      ResTime: function (time) {
        return FormatTime2(time * 1000);
      },

      Hierarchy: function (auth) {
        return UserType(auth)
      }
    },
    methods: {
      GetUserList(page) {                   //获取用户列表
        this.TableLoading = true;
        this.$api.GainUserList(page, this.userpagesize).then(res => {
          this.tableData = res.list;
          this.usertotal = res.count;
          this.TableLoading = false;
        })
      },
      handleCurrentChange(page) {            //改变页面
        this.GetUserList(page);
      },
      //点击修改
      handleClick(res){
        this.dialogVisible = true;
        this.FocusUser = res;
        // console.log(res)
      },
      //关闭修改窗口
      handleClose(){
        this.dialogVisible = false;
        this.FocusUser = {};
        // console.log('关闭')
      },
      //提交修改
      handleUserRoles(){
        this.$api.UploadUserInfo(this.FocusUser.id,this.FocusUser.auth).then(res=>{
          this.dialogVisible = false;
          this.$notify({
            title: this.$t('info.success'),
            message: `【${this.FocusUser.realname}】修改成功`,
            type: 'success'
          });
          this.FocusUser = {};
          this.GetUserList(this.page);
        });
      }
    },
  }
</script>

<style lang="less">
    .mag-pagination {
        padding: 0;
        margin: 0 1rem 2rem;
    }
    .info-dialog{
        .el-dialog__header{
            padding: 0;
        }
    }
</style>
