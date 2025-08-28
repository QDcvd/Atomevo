<template>
    <div class="page">
        <div class="error-icon">
            <div class="img-bg">

            </div>
            <!--<img src="./error.gif" class="icon">-->
            <div class="font">
                 无权访问
                <br>
                <span style="font-size: 2rem">>: -404 You're lost.</span>

            </div>
            <div class="btn">
                <el-button type="danger" @click="Goinfo">Back</el-button>
            </div>
        </div>

    </div>
</template>

<script>
  export default {
    name: "iserror",
    methods:{
      Goinfo(){
        this.$router.push('login')
      }
    }
  }
</script>

<style lang="less">
    .page {
        width: 100%;
        height: 100vh;
        .error-icon{
            /*width: 30rem;*/
            margin: auto;
            .img-bg{
                margin-top: 5rem;
                height: 25rem;
                width: 100%;
                background-size: 20rem;
                background-image: url("error.gif");
                background-position: center;
                background-repeat:no-repeat;
            }
            .icon{
                width: 100%;
            }
            .font{
                margin:1rem 0;
                color: #3fdb58;
                text-align: center;
                font-size: 4rem;
                text-shadow: -2px 0 0 rgba(0,201,16,0.4), -4px 0 4px rgba(108,236,103,0.6), 5px 0 4px rgba(69,204,91,0.5);
                cursor:default;
                user-select:none;
            }
            .btn{
                text-align: center;
            }
        }
    }
</style>
