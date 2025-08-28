<template>
    <div class="float-menu"
         v-move="this">
        <!--<el-tooltip class="item" effect="dark" content="长按可以移动位置" placement="top">-->
        <!--</el-tooltip>-->
        <el-popover
                placement="bottom"
                width="150"
                trigger="manual"
                v-model="MenuShow"
        >
            <div class="menu-list" @mouseleave="handleMenu">
                <!--<el-option label="文件库">文件库</el-option>-->
                <!---->
                <div class="menu-title">悬浮菜单</div>
                <div class="menu-item" @click="OpenFileTable" v-if="support">库文件计算</div>
                <div class="dis-menu-item" v-else >库文件计算(暂不支持)</div>
            </div>
            <el-button class=" el-icon-s-grid"
                       type="primary"
                       slot="reference"
                       @click="handleMenu"
                       circle/>
        </el-popover>
    </div>
</template>

<script>
  import '../directives/move';       //自定义指令v-move
  export default {
    name: "float_menu",
    watch:{
      '$route'(next,prev){
        switch (next.name) {
          case 'autodockvina':this.support = true;break;
          case 'ledock':this.support = true;break;
          case 'plants':this.support = true;break;
          default:this.support = false;break;
        }
        // this.support = false;
      }
    },
    data() {
      return {
        MenuShow: false,
        handle: true,            //参数
        support:false,              //当前工具支持
      }
    },
    methods: {
      handleMenu() {
        if (this.handle) {            //点击展开菜单
          this.MenuShow = !this.MenuShow;
        }
      },
      //打开文件列表
      OpenFileTable (){

      },

    }
  }
</script>

<style lang="less">
    .float-menu {
        height: 2rem;
        width: 2rem;
        cursor: default;
        border-radius: 50%;
        position: absolute;
        bottom: 1rem;
        left: 1rem;
        z-index: 2500;

    }

    .menu-list {
        .menu-title {
            cursor: default;
            border-bottom: #dcdfe6 solid 1px;
            padding-bottom: 6px;
            margin-bottom: 0.2rem;
            color: #35495e;
            font-weight: bold;
        }
        .menu-item {

            height: 1.5rem;
            line-height: 1.5rem;
            cursor: pointer;
            color: #606266;
            &:hover {
                font-weight: bold;
                color: #66b1ff;
            }
        }
        .dis-menu-item{
            height: 1.5rem;
            line-height: 1.5rem;
            cursor:not-allowed;
            color: #808286;
            &:hover {


            }
        }
    }

</style>
