<template>
  <div class="mag-background">
    <div class="mag-login-time">
      {{ Nowtime | ResTime }}
    </div>
    <ul class="circles">
      <li v-for="n in 10" :key="n"></li>
    </ul>
    <div class="mag-login-logo">
      <img class="logo-img" :src="LogoImg" />
    </div>
    <div class="mag-login-form">
      <el-form
        :model="ruleForm"
        status-icon
        :rules="rules"
        ref="ruleForm"
        class="demo-ruleForm"
      >
        <el-form-item prop="Name">
          <el-input placeholder="" v-model="ruleForm.Name" autocomplete="on">
            <template slot="prepend">
              <i class="el-icon-user"></i>
              {{ $t("content.user") }}
            </template>
          </el-input>
        </el-form-item>
        <el-form-item prop="Pass">
          <el-input
            placeholder=""
            v-model="ruleForm.Pass"
            autocomplete="off"
            type="password"
          >
            <template slot="prepend">
              <i class="el-icon-c-scale-to-original"></i>
              {{ $t("content.pass") }}
            </template>
          </el-input>
        </el-form-item>
        <el-form-item prop="Code">
          <el-input
            placeholder=""
            v-model="ruleForm.Code"
            autocomplete="off"
            class="show-code"
            maxlength="4"
            @keyup.enter.native="submitForm('ruleForm')"
          >
            <template slot="prepend">
              {{ $t("content.code") }}
            </template>
            <template slot="append">
              <div style="width: 5rem;height: 100%">
                <el-image
                  @click="ReloadImg"
                  :src="CodeImg"
                  fit="cover"
                ></el-image>
              </div>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item>
          <div style="position: relative;height: 2rem">
            <el-link
              :underline="false"
              href="/setpass"
              style="position:absolute;left: 0;user-select:none;"
            >
              <i class="el-icon-thumb"></i>
              {{ $t("content.forget") }}
            </el-link>

            <el-link
              :underline="false"
              href="/register"
              style="position:absolute;right: 0;user-select:none;"
            >
              <i class="el-icon-warning-outline"></i>
              {{ $t("content.register") }}
            </el-link>
          </div>
        </el-form-item>
        <el-form-item style="text-align: right" :label="$t('btn.lang')">
          <el-switch
            v-model="MagLang"
            active-color="#5daf34"
            inactive-color="#374b60"
            active-text="English"
            inactive-text="中文(简体)"
          >
          </el-switch>
        </el-form-item>
        <el-form-item v-loading="loginBtn">
          <el-button type="primary" @click="handleGuest">
            {{ $t("btn.guest") }}</el-button
          >
          <el-button type="primary" @click="submitForm('ruleForm')">
            {{ $t("btn.login") }}</el-button
          >
          <el-button @click="resetForm('ruleForm')">
            {{ $t("btn.remove") }}</el-button
          >
        </el-form-item>
      </el-form>
    </div>
    <div class="design-title">@Designed by Magical-Team | 2019-2021</div>
    <!-- <div class="design-title">@Designed by Magical-Team | 「<el-link href="http://beian.miit.gov.cn/" target="_blank">粤ICP备18121277号-3</el-link>」</div> -->
  </div>
</template>

<script>
// let CodeTime  = '';
import { FormatTime } from "@/config/format";
// import {mapState} from 'vuex';
import { addRouter } from "@/router/index"; //添加路由表
import LogoImg from "~/image/logo.3ed2276d.png";
export default {
  name: "login",
  data() {
    return {
      MagLang: false,
      LogoImg,
      CodeImg: "", //图片地址
      ImgKey: "", //图片key
      Nowtime: "", //背景时间
      ruleForm: {
        Name: "",
        Pass: "",
        Code: "",
      },
      rules: {
        Name: [
          {
            required: true,
            message: this.$t("msg.login_user"),
            trigger: "blur",
          },
          {
            min: 3,
            max: 16,
            message: this.$t("msg.login_user_info"),
            trigger: "blur",
          },
        ],
        Pass: [
          {
            required: true,
            message: this.$t("msg.login_pass"),
            trigger: "blur",
          },
          {
            min: 3,
            max: 30,
            message: this.$t("msg.login_pass_info"),
            trigger: "blur",
          },
        ],
        Code: [
          {
            required: true,
            message: this.$t("msg.login_code"),
            trigger: "blur",
          },
          { max: 4, message: this.$t("msg.login_code_info"), trigger: "blur" },
        ],
      },
      loginBtn: false,
    };
  },
  watch: {
    MagLang(n, o) {
      if (n) {
        this.$i18n.locale = "en-US";
        localStorage.setItem("lang", "en-US");
      } else {
        this.$i18n.locale = "zh-CN";
        localStorage.setItem("lang", "zh-CN");
      }
    },
  },
  computed: {},
  beforeCreate() {},
  created() {
    this.ReloadImg();
    //加载语言选择
    let SetLang = localStorage.getItem("lang");
    // console.log('==>',SetLang)
    // this.$i18n.locale = SetLang;
    if (SetLang == "en-US") {
      this.MagLang = true;
    }
  },
  mounted() {
    //计时效果
    setInterval(() => {
      this.Nowtime = new Date().getTime();
    }, 1000);
  },
  destroyed() {
    // clearInterval(CodeTime);
  },
  filters: {
    ResTime: function(time) {
      return FormatTime(time);
    },
  },
  methods: {
    //登陆
    submitForm(formName) {
      if (this.ruleForm.Code.length < 4) {
        return; //防止还没输入完按回车
      }
      this.$refs[formName].validate((valid) => {
        if (valid) {
          // alert('submit!');
          this.loginBtn = true;
          let data = {
            time: new Date().getTime(),
            username: this.$md5(this.ruleForm.Name).toString(),
            password: this.$md5(this.ruleForm.Pass).toString(),
            verify: this.ruleForm.Code.toLocaleLowerCase(),
            verify_key: this.ImgKey,
          };
          this.$api
            .GainLogin(data)
            .then((res) => {
              this.$store.commit("SetToken", res.token);
              // this.$socket.client.emit('token',res.token); //触发socket登陆
              // this.$store.commit('SetUserInfo', res.user);
              let userdata = res.user;
              //配置权限 1、管理员 ；2：高级用户；3：普通用户
              switch (res.user.auth) {
                case "1":
                  userdata.roles = ["user", "vip", "admin"];
                  this.$store.commit("SetUserInfo", userdata);
                  break;
                case "2":
                  userdata.roles = ["user", "vip"];
                  this.$store.commit("SetUserInfo", userdata);
                  break;
                default:
                  userdata.roles = ["user"];
                  this.$store.commit("SetUserInfo", userdata);
                  break;
              }

              this.$notify({
                title: this.$t("info.success"),
                message: this.$t("msg.login_success"),
                type: "success",
              });

              // 添加权限路由
              addRouter(res.user.app_list); //添加路由
              // console.log('路由列表',this.$store.state.ToolsRouterList);
              this.$router.push("/info");
              this.loginBtn = false;
            })
            .catch((err) => {
              this.$notify.error({
                title: this.$t("info.error"),
                message: err.msg,
              });
              this.ReloadImg();
              this.loginBtn = false;
            });
        } else {
          this.$message({
            showClose: true,
            message: this.$t("msg.login_msg"),
            type: "error",
          });

          return false;
        }
      });
    },
    //重置
    resetForm(formName) {
      this.$refs[formName].resetFields();
    },
    //获取刷新验证码
    ReloadImg() {
      // clearInterval(CodeTime);
      // CodeTime = setInterval(() => {
      //     this.ReloadImg();
      // }, 60000);
      this.$api.GainCode().then((res) => {
        // console.log(res);
        this.CodeImg = res.img;
        this.ImgKey = res.verify_key;
      });
    },
    // 使用游客账户
    handleGuest() {
      this.ruleForm.Name = "guest";
      this.ruleForm.Pass = "magical";
    },
  },
};
</script>

<style lang="less">
//底部时间样式
.mag-login-time {
  position: absolute;
  width: 100%;
  text-align: center;
  font-size: 20rem;
  bottom: 0;
  color: rgba(129, 182, 235, 0.2);
  font-weight: bold;
}

//头部logo
.mag-login-logo {
  width: 12rem;
  padding: 2rem;
  position: relative;
  right: 0;
  left: 0;
  top: 8rem;
  margin: auto;

  .logo-img {
    height: 100%;
    width: 100%;
    object-fit: scale-down;
  }
}

//登陆验证码
.show-code {
  .el-input-group__append {
    padding: 0;
  }
  .el-image__inner {
  }
}

//登陆窗口
.mag-login-form {
  background-color: #ffffff;
  padding: 1rem;
  width: 18rem;
  /*height: 30%;*/
  position: relative;
  right: 0;
  left: 0;
  top: 8rem;
  bottom: 0;
  margin: auto;
  border-radius: 1rem;
  text-align: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12), 0 0 6px rgba(0, 0, 0, 0.04);
}

.design-title {
  position: absolute;
  text-align: center;
  font-size: 0.8rem;
  user-select: none;
  bottom: 0;
  left: 0;
  right: 0;
}

//白块背景
.circles {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  padding: 0;
  margin: 0;
  li {
    position: absolute;
    display: block;
    list-style: none;
    width: 20px;
    height: 20px;
    background: #fff;
    animation: animate 25s linear infinite;
    bottom: -200px;
    @keyframes animate {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
        border-radius: 0;
      }
      100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
        border-radius: 60%;
      }
    }
    &:nth-child(1) {
      left: 15%;
      width: 80px;
      height: 80px;
      animation-delay: 0s;
    }
    &:nth-child(2) {
      left: 5%;
      width: 20px;
      height: 20px;
      animation-delay: 2s;
      animation-duration: 12s;
    }
    &:nth-child(3) {
      left: 70%;
      width: 20px;
      height: 20px;
      animation-delay: 4s;
    }
    &:nth-child(4) {
      left: 40%;
      width: 60px;
      height: 60px;
      animation-delay: 0s;
      animation-duration: 18s;
    }
    &:nth-child(5) {
      left: 65%;
      width: 20px;
      height: 20px;
      animation-delay: 0s;
    }
    &:nth-child(6) {
      left: 75%;
      width: 150px;
      height: 150px;
      animation-delay: 3s;
    }
    &:nth-child(7) {
      left: 35%;
      width: 200px;
      height: 200px;
      animation-delay: 7s;
    }
    &:nth-child(8) {
      left: 50%;
      width: 25px;
      height: 25px;
      animation-delay: 15s;
      animation-duration: 45s;
    }
    &:nth-child(9) {
      left: 20%;
      width: 15px;
      height: 15px;
      animation-delay: 2s;
      animation-duration: 35s;
    }
    &:nth-child(10) {
      left: 85%;
      width: 150px;
      height: 150px;
      animation-delay: 0s;
      animation-duration: 11s;
    }
  }
}

@media screen and (max-width: 400px) {
  .mag-login-form {
    width: 25rem;
  }
}
</style>
