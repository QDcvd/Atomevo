<template>
    <div class="mag-background">
        <div class="mag-setpass-form">
            <div class="mag-setpass">
                <div class="mag-setpass-step">
                    <el-steps :active="StepIndex" simple finish-status="success">
                        <el-step :title="$t('info.email')" icon="el-icon-edit"></el-step>
                        <el-step :title="$t('info.verification')" icon="el-icon-upload"></el-step>
                        <el-step :title="$t('info.complete')" icon="el-icon-picture"></el-step>
                    </el-steps>
                </div>

                <div class="mag-setpass-in">

                    <el-carousel  arrow="never" :autoplay="false" :loop="false" indicator-position="none" ref="stepitem">
                        <el-carousel-item >
                            <div class="mag-setpass-g1" >
                                <el-form  status-icon label-width="8rem" class="demo-ruleForm">
                                    <el-form-item :label="$t('info.register_email')" prop="pass">
                                        <el-input  v-model="userEmail" autocomplete="off" :placeholder="$t('info.register_email_msg')"></el-input>
                                    </el-form-item>
                                    <el-form-item style="text-align: center;margin-top: 5rem">
                                        <el-button type="primary" @click="SendEmail" >{{$t('btn.next')}}</el-button>
                                    </el-form-item>
                                </el-form>
                            </div>
                        </el-carousel-item>
                        <el-carousel-item >
                            <div class="mag-setpass-g2" >
                                <el-form :model="ruleForm" status-icon :rules="rules" ref="ruleForm" label-width="5rem" class="demo-ruleForm">
                                    <el-form-item :label="$t('info.register_pass')" prop="pass">
                                        <el-input type="password" v-model="ruleForm.pass" autocomplete="off"></el-input>
                                    </el-form-item>
                                    <el-form-item :label="$t('info.register_pass2')" prop="checkPass">
                                        <el-input type="password" v-model="ruleForm.checkPass" autocomplete="off"></el-input>
                                    </el-form-item>
                                    <el-form-item :label="$t('info.register_code')" prop="code">
                                        <el-input v-model="ruleForm.code" :placeholder="$t('info.register_code_msg')"></el-input>
                                    </el-form-item>
                                    <el-form-item style="text-align: center;margin-top: 5rem">
                                        <el-button type="primary" @click="submitForm('ruleForm')">{{$t('btn.submit')}}</el-button>
                                    </el-form-item>
                                </el-form>
                            </div>
                        </el-carousel-item>
                        <el-carousel-item >
                            <div class="mag-setpass-g3" >
                                <div style="text-align: center">
                                    <div style="margin-bottom: 5rem;font-size: 2.5rem">
                                        {{$t('info.repass_msg')}}
                                        <i class="el-icon-check" style="color: #67c23a;font-size: 3rem"> </i>
                                    </div>

                                    <el-button type="primary" @click="toLogin">{{$t('btn.login')}}</el-button>
                                </div>

                            </div>
                        </el-carousel-item>
                    </el-carousel>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "setpass",
        data() {

            var validatePass = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error(this.$t('msg.login_pass')));
                } else {
                    if (this.ruleForm.checkPass !== '') {
                        this.$refs.ruleForm.validateField('checkPass');
                    }
                    callback();
                }
            };
            var validatePass2 = (rule, value, callback) => {
                if (value === '') {
                    callback(new Error('请再次输入密码'));
                } else if (value !== this.ruleForm.pass) {
                    callback(new Error(this.$t('msg.register_info6')));
                } else {
                    callback();
                }
            };
            var validateCode = (rule, value, callback) =>{
                if (value === '') {
                    callback(new Error('请输入邮箱接收的验证吗'));
                } else if (value.length != 16) {
                    callback(new Error(this.$t('msg.login_code_info')));
                } else {
                    callback();
                }
            };
            return {
                ruleForm: {
                    code:'',
                    pass:'',
                    checkPass: '',
                },
                rules: {
                    code:[
                        { validator:validateCode, trigger: 'blur'}
                    ],
                    pass: [
                        { validator: validatePass, trigger: 'blur' }
                    ],
                    checkPass: [
                        { validator: validatePass2, trigger: 'blur' }
                    ]
                },
                userEmail:'',
                StepIndex:1        //步骤定位
            };
        },
        methods:{
            //步骤
            stepnext(idx){
                this.StepIndex = idx+1;                     //步骤切换
                this.$refs.stepitem.setActiveItem(idx)      //幻灯切换
            },
            // 返回登陆
            toLogin(){
                this.$router.push('/login')
            },
            //发送至邮箱
            SendEmail(){

                let reg=/^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
                if(reg.test(this.userEmail)){

                    this.$api.GainPass(this.userEmail).then(res=>{
                        this.stepnext(1);
                    }).catch(err=>{
                        // console.log()
                        this.$notify({
                            title: this.$t('info.error'),
                            message: err.msg,
                            type: 'warning'
                        });
                    })
                }else {
                    this.$notify({
                        title:this.$t('info.error'),
                        message: this.$t('msg.register_info5'),
                        type: 'warning'
                    });
                }

            },
            //重新设定密码
            submitForm(formName){

                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        let data = {
                            password:this.$md5(this.ruleForm.pass).toString(),
                            verify:this.ruleForm.code
                        };
                        this.$api.SetPass(data).then(res=>{
                            this.stepnext(2);
                        }).catch(err=>{
                            this.$notify({
                                title: this.$t('info.error'),
                                message: err.data.msg,
                                type: 'warning'
                            });
                        })

                    } else {

                        return false;
                    }
                });
            },


        }
    }
</script>

<style lang="less">
    .mag-setpass-form{
        background-color: #FFFFFF;
        padding: 1rem;
        width: 80%;
        height: 100%;
        position: relative;
        right: 0;
        left: 0;
        top: 0rem;
        bottom: 0;
        margin: auto;
        border-radius: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .12), 0 0 6px rgba(0, 0, 0, .04);

        .mag-setpass{
            width: 60%;
            position: absolute;
            margin: auto;
            right: 0;
            left: 0;
            .mag-setpass-step{
                margin-top: 5rem;
            }

            .mag-setpass-in{
                margin-top: 5rem;

                .mag-setpass-g1{
                    width: 50%;
                    margin: auto;
                }

                .mag-setpass-g2{
                    width: 30%;
                    margin: auto;
                }
            }
        }

    }
</style>
