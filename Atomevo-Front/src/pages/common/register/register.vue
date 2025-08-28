<template>
    <div class="mag-background">
        <div class="mag-register">
            <div style="margin-bottom: 2rem">
                <strong>申请加入 Magical.org</strong>
                &nbsp
                <el-tooltip class="item" effect="dark" content="专注分子科学计算领域" placement="top">
                    <i class="el-icon-question"></i>
                </el-tooltip>


            </div>
            <div class="mag-register-form" v-if="isNext">
                <el-form :model="ruleForm" status-icon :rules="rules" ref="ruleForm" label-width="10rem" class="demo-ruleForm">
                    <el-form-item :label="$t('content.user')" prop="userid">
                        <el-input v-model="ruleForm.userid" :placeholder="$t('content.user_msg')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.pass')" prop="pass">
                        <el-input type="password" v-model="ruleForm.pass" autocomplete="off" :placeholder="$t('content.pass_msg')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.pass2')" prop="checkPass">
                        <el-input type="password" v-model="ruleForm.checkPass" autocomplete="off" :placeholder="$t('content.pass_msg2')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.name')" prop="username">
                        <el-input v-model="ruleForm.username" :placeholder="$t('content.name_msg')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.org')" prop="org">
                        <el-input v-model="ruleForm.org" :placeholder="$t('content.org_msg')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.phone')" prop="phone">
                        <el-input v-model.number="ruleForm.phone" :placeholder="$t('content.phone_msg')"></el-input>
                    </el-form-item>
                    <el-form-item :label="$t('content.mail')" prop="mail">
                        <el-input v-model="ruleForm.mail" :placeholder="$t('content.mail_msg')"></el-input>
                    </el-form-item>
                    <!--<el-form-item>-->
                       <!--协议-->
                    <!--</el-form-item>-->
                    <el-form-item>
                        <div style="text-align: right">
                            <i class="el-icon-key"></i>
                            <el-link href="/login" >【已经有账号去登陆】</el-link>
                    </div>
                    </el-form-item>

                    <el-form-item>
                        <el-button type="primary" @click="submitForm('ruleForm')">{{$t('btn.registered')}}</el-button>
                    </el-form-item>
                </el-form>
            </div>
            <div class="mag-register-form2" v-else>
                <div style="font-size: 1.8rem;">
                    {{$t('info.register_msg')}}
                </div>
                <transition name="el-fade-in">
                <i class="el-icon-success" style="font-size: 8rem;margin-top: 5rem;color: #67c23a" v-if="isIcon"></i>
                </transition>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "register",
        data() {
            let validatePass = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error(this.$t('content.pass_msg')));
                } else {
                    if (this.ruleForm.checkPass !== '') {
                        this.$refs.ruleForm.validateField('checkPass');
                    }
                    callback();
                }
            };
            let validatePass2 = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error(this.$t('content.pass_msg2')));
                } else if (value !== this.ruleForm.pass) {
                    callback(new Error(this.$t('msg.register_info6')));
                } else {
                    callback();
                }
            };

            let validateEmail = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error(this.$t('msg.register_info5')));
                } else {
                    if (value !== '') {
                        // let reg=/^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
                        // if(!reg.test(value)){
                        //     callback(new Error('请输入有效的邮箱'));
                        // }
                    }
                    callback();
                }
            };

            var validateMobilePhone = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error(this.$t('msg.register_info3')));
                } else {
                    if (value !== '') {
                        let reg=/^1[3456789]\d{9}$/;
                        if(!reg.test(value)){
                            callback(new Error(this.$t('msg.register_info4')));
                        }
                    }
                    callback();
                }
            };

            return {
                ruleForm: {
                    userid:'',
                    pass: '',
                    checkPass: '',
                    username:'',
                    org:'',
                    phone:'',
                    mail:''
                },
                rules: {
                    userid: [
                        { required: true, message: this.$t('msg.login_user'), trigger: 'blur' },
                        { min: 3, max: 16, message: this.$t('msg.login_user_info'), trigger: 'blur' }
                    ],
                    pass: [
                        { validator: validatePass, trigger: 'blur',required: true}
                    ],
                    checkPass: [
                        { validator: validatePass2, trigger: 'blur',required: true}
                    ],
                    username: [
                        { required: true, message: this.$t('msg.register_info1'), trigger: 'blur' },
                        { min: 2, max: 5, message: this.$t('msg.register_info'), trigger: 'blur' }
                    ],
                    org: [
                        { required: true, message: this.$t('msg.register_info2'), trigger: 'blur' },
                        { min: 3, max: 16, message: this.$t('msg.login_user_info'), trigger: 'blur' }
                    ],
                    phone: [
                        { validator: validateMobilePhone, trigger: 'blur',required: true}
                    ],
                    mail: [
                        { validator: validateEmail, trigger: 'blur',required: true}
                    ]
                },
                isNext:true,
                isIcon:false    //成功icon显示
            };
        },
        beforeCreate(){
            //登陆页面关闭socket
          this.$socket.client.close();
        },
        methods: {
            submitForm(formName) {
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        let regdata = {
                            username:this.ruleForm.userid,
                            password:this.$md5(this.ruleForm.pass).toString(),
                            realname:this.ruleForm.username,
                            school:this.ruleForm.userid,
                            tel:this.ruleForm.phone,
                            mail:this.ruleForm.mail
                        };
                        this.$api.GainRegister(regdata).then(res=>{

                            this.isNext = false;    //进入下一步
                            let ShowIcon = setTimeout(()=>{
                                clearTimeout(ShowIcon);
                                this.isIcon = true;
                            },500)
                        }).catch(err=>{
                            this.$notify.error({
                                title: this.$t('info.error'),
                                message: err.msg
                            });
                        });


                    } else {

                        return false;
                    }
                });


            },
            resetForm(formName) {
                this.$refs[formName].resetFields();
            }
        }

    }
</script>

<style lang="less">

    .mag-register{
        background-color: #FFFFFF;
        padding: 1rem;
        width: 70%;
        height: 100%;
        position: relative;
        right: 0;
        left: 0;
        /*top: 0;*/
        /*bottom: 0;*/
        margin: auto;
        border-radius: 1rem;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .12), 0 0 6px rgba(0, 0, 0, .04);

        .mag-register-form{
            position: absolute;
            right: 5rem;
            left: 0;
            width: 40%;
            margin: auto;
        }
        .mag-register-form2{
            margin-top: 5rem;
        }
    }
</style>
