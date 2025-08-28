## 技术栈





## 运行项目

1. 确保你的电脑有`node`环境，没有的话请访问`node`[官网](http://nodejs.cn/download/)进行下载

2. 运行`npm install -g yarn` 安装`yarn`工具

3. `yarn install`安装项目依赖

4. `yarn serve`启动项目

5. 运行`yarn build`可进行项目打包

## 目录说明

   ```markdown
   ├── App.vue 根组件
   ├── api 接口相关的调用
   │   ├── api.js 相关的接口调用
   │   └── api.server.js 请求配置和拦截器
   ├── assets 静态资源
   │   └── main.less
   ├── components  
   │   ├── DownLoadTable.vue  
   │   ├── ExcelEdit.vue
   │   ├── MagIcon.vue
   │   ├── UpInfo.vue
   │   ├── UploadFile.vue
   │   ├── float_menu.vue
   │   └── image
   │       └── base64.js
   ├── config
   │   ├── format.js
   │   └── screen.js
   ├── directives
   │   └── move.js  自定义全局命令(v-move)
   ├── info.js 用户登录后显示的版本更新迭代信息
   ├── lang 中英文配置文件
   │   ├── en-US.js
   │   ├── lang.js
   │   └── zh-CN.js
   ├── main.js  
   ├── pages
   │   ├── common
   │   │   ├── dataStatistics 首页的数据分析
   │   │   │   ├── components
   │   │   │   └── dataStatistics.vue
   │   │   ├── iserror
   │   │   │   ├── error.gif
   │   │   │   └── iserror.vue
   │   │   ├── login 登录页
   │   │   │   ├── image
   │   │   │   └── login.vue
   │   │   ├── register
   │   │   │   └── register.vue
   │   │   └── setpass
   │   │       └── setpass.vue
   │   ├── index
   │   │   ├── image
   │   │   │   └── all.png
   │   │   └── index.vue
   │   ├── tools 相关工具页面
   │   │   ├── ClustalW2
   │   │   │   └── ClustalW2.vue
   │   │   ├── FoldXAlaScan
   │   │   │   └── FoldXAlaScan.vue
   │   │   ├── PrimerDesign
   │   │   │   └── PrimerDesign.vue
   │   │   ├── auto-martini
   │   │   │   ├── auto-martini.vue
   │   │   │   └── automartini.vue
   │   │   ├── autodock
   │   │   │   └── autodock.vue
   │   │   ├── autodock-vina
   │   │   │   └── autodock-vina.vue
   │   │   ├── commol
   │   │   │   └── commol.vue
   │   │   ├── dssp
   │   │   │   └── dssp.vue
   │   │   ├── exp4Cas9
   │   │   │   └── exp4Cas9.vue
   │   │   ├── g-mmpbsa
   │   │   │   └── g-mmpbsa.vue
   │   │   ├── g-mmpbsa-analysis
   │   │   │   └── g-mmpbsa-analysis.vue
   │   │   ├── glapd
   │   │   │   └── glapd.vue
   │   │   ├── gmx
   │   │   │   └── gmx.vue
   │   │   ├── gromacs
   │   │   │   └── gromacs.vue
   │   │   ├── gzeronine
   │   │   │   └── gzeronine.vue
   │   │   ├── ledock
   │   │   │   └── ledock.vue
   │   │   ├── martinize
   │   │   │   └── martinize.vue
   │   │   ├── martinize3.0
   │   │   │   └── martinize.vue
   │   │   ├── mktop
   │   │   │   └── mktop.vue
   │   │   ├── modeller
   │   │   │   └── modeller.vue
   │   │   ├── multiwfn
   │   │   │   └── multiwfn.vue
   │   │   ├── openbabel
   │   │   │   └── openbabel.vue
   │   │   ├── pdb-tool
   │   │   │   └── pdb-tool.vue
   │   │   ├── plants
   │   │   │   └── plants.vue
   │   │   ├── plip
   │   │   │   ├── image
   │   │   │   └── plip.vue
   │   │   ├── procheck
   │   │   │   └── procheck.vue
   │   │   ├── rgb
   │   │   │   └── rgb.vue
   │   │   ├── tksa
   │   │   │   └── tksa.vue
   │   │   ├── tr-rosetta
   │   │   │   └── tr-rosetta.vue
   │   │   ├── xscore
   │   │   │   └── xscore.vue
   │   │   └── xvg-to-csv
   │   │       ├── xvg-to-csv.vue
   │   │       └── xvg2csv.vue
   │   └── user
   │       ├── account
   │       │   ├── config.vue
   │       │   ├── home.vue
   │       │   └── userinfo.vue
   │       ├── info
   │       │   └── info.vue
   │       ├── library
   │       │   └── library.vue
   │       └── toolhub
   │           └── toolhub.vue
   ├── router
   │   ├── index.js
   │   └── modules
   │       ├── common.routes.js
   │       ├── index.routes.js
   │       ├── tools.routes.js 配置工具类页面的路由
   │       └── user.routes.js
   └── store
       ├── index.js
       └── modules
           ├── routes.js
           └── taskid.js
   ```

> 以上的目录分析针对的是`src`目录

## 新增一个计算功能操作流程

这里讲解较为复杂的`PDB-Tool`工具的添加为例子，这个都理解了，那剩下的那些自然很简单了，都是一模一样的代码。

### 后端配合

通知后端在`/magapi/token/getLoginToken`接口上`app_list`上添加新的模块的名称，比如`PDB-tool`

### 新建一个组件

在`src/pages/tools`新建一个组件，组件内代码，可以挑一个跟你需求最接近的一个页面组件先复制一遍，后期不断修改就好

### 添加路由

可以到`src/router/modules/tool.routes.js`中引入上文提到新建组件，

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311419362.png)

并且按照格式添加到`ToolsRoutes`数组中，这时候再刷新页面，就可以看到你新建的这个工具组件了

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311419639.png)

### 常见改动的地方

#### 标题

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311414937.png)

#### 模块

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311427057.png)

#### 修改表格参数

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311431155.png)

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311441944.png)

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311444740.png)

#### 执行计算

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311449755.png)

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311454302.png)

#### 修改调用的接口

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311457304.png)

#### 更新修改信息

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311459896.png)

![](https://cdn.jsdelivr.net/gh/coder-th/static/202112311501230.png)
