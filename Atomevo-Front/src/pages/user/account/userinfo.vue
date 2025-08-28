<template>
    <div class="mag-step-left">
        <el-form ref="form" :model="userform">
            <el-form-item label="登陆账号：">
                {{userform.user}}
            </el-form-item>
            <el-form-item label="账号名称：">
                {{userform.realname}}
            </el-form-item>
            <el-form-item label="账户编号：">
                {{userform.id}}
            </el-form-item>
            <el-form-item label="账号类型：">
                <el-tag type="success"> {{userform.auth | Hierarchy}} </el-tag>
            </el-form-item>

        </el-form>
    </div>
</template>

<script>
  import { UserType } from '@/config/format';
  export default {
    name: "userinfo",
    data(){
      return{
        userform:{},        // 用户数据表
      }
    },
    created(){
      this.GainCacheUser()
    },
    filters:{
      Hierarchy(auth){
        return UserType(auth)
      }
    },
    methods:{
      GainCacheUser(){
        this.userform = this.$store.state.UserInfo;
      }
    }
  }
</script>

<style scoped>

</style>
