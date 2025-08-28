import { MagGet, MagPost } from "./api.server.js";
// @param MagGet 为默认get请求
// @param MagPost 为默认post请求
import store from "@/store";
const GetUrl = "/magapi/tool/get"; //工具类的地址get
const GetUserUrl = "/magapi/user/get"; //用户类的地址get
const PostUrl = "/magapi/tool/post"; //工具类的地址post
const PostUserUrl = "/magapi/user/post"; //用户类的地址post
const request = {
  //一言接口

  GainOne() {
    let str = "abc";
    let array = str.split("");
    let i = Math.round(Math.random() * 2);
    let param = {
      c: array[i], //随机主题
    };
    return MagGet("https://v1.hitokoto.cn/", param);
  },
  //登陆接口
  GainLogin(data) {
    return MagPost("/magapi/token/getLoginToken", data);
  },
  //退出登陆
  GainDisLogin() {
    let data = {};
    data.method = "logout";
    data.action = "logout";
    data.id = store.state.UserInfo.id;
    return MagPost(PostUserUrl, data);
  },
  //获取验证码
  GainCode() {
    let param = {
      time: new Date().getTime(),
      fontFamily: 9,
    };
    return MagGet("/magapi/token/getVerify", param);
  },
  // 获取文件列表
  // @param use 工具名称
  // @param page 页码
  GainFile(param) {
    param.action = "get_upload_files";
    param.method = "files";
    param.pageindex = 16;
    return MagGet(GetUrl, param);
  },

  //auto_martini执行脚本
  GainMartini(data) {
    data.method = "auto_martini";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  //获取完成任务下载列表
  GainFileList(param) {
    param.method = "tasks";
    param.action = "get_tasks_file_lists";
    return MagGet(GetUrl, param);
  },

  //注册接口
  GainRegister(data) {
    data.time = new Date().getTime();
    return MagPost("/magapi/token/register", data);
  },

  //获取找回密码Code值
  GainPass(mail) {
    let data = {};
    data.mail = mail;
    return MagPost("/magapi/token/forget_mail", data);
  },

  //验证后Code重置密码
  SetPass(data) {
    return MagPost("/magapi/token/forget_reset", data);
  },

  // 首页获取任务列表
  // @param page 页码
  // @param type 筛选工具类型（暂时用不上
  GainAllList(page, type) {
    let param = {};
    param.page = page;
    param.pageindex = 30;
    param.method = "tasks";
    param.action = "get_tasks_list";
    param.use = type;
    return MagGet(GetUrl, param);
  },

  //PROCHECK执行
  GainPROCHECK(id) {
    let data = {};
    data.method = "procheck";
    data.ids = id;
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  // 获取info页面中的图表统计
  GainChart() {
    let param = {};
    param.method = "tasks";
    param.action = "tasks_chart";
    return MagGet(GetUrl, param);
  },

  //单次请求多文件上传接口 [公共上传文件位置]
  UploadFiles(Data, type) {
    Data.append("method", "upload");
    Data.append("action", "uploads");
    Data.append("use", type);
    // console.log(qs.parse(data))
    //MultiFile
    let config = {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    };
    // console.log(file)
    return MagPost(PostUrl, Data, config, 1);
  },

  //文件库上传文件
  UploadLibrary(Data) {
    Data.append("method", "upload");
    Data.append("action", "uploadLibraryFile");
    let config = {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    };

    return MagPost(PostUrl, Data, config, 1);
  },

  //zip压缩包上传
  UploadZip(Data, info) {
    Data.append("method", "upload");
    Data.append("action", "uploadZipFiles");
    let config = {
      headers: {
        "Content-Type": "multipart/form-data",
        Tabs: info,
      },
    };
    return MagPost(PostUrl, Data, config, 1);
  },

  // 工具执行接口==============================
  GainXVG2GSV(id) {
    let data = {};
    data.method = "xvg_to_csv";
    data.ids = id;
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  GainAutoDock(param) {
    param.method = "autodock";
    param.action = "run";
    return MagGet(GetUrl, param);
  },

  GainAutoDockVina(data) {
    data.method = "autodock_vinasV3";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  Gaindssp(data) {
    data.method = "dssp";
    data.action = "run";
    // return MagGet(GetUrl, param)
    return MagPost(PostUrl, data);
  },
  Gainmktop(data) {
    data.method = "mktopV2";
    data.action = "run";
    // return MagGet(GetUrl, param)
    return MagPost(PostUrl, data);
  },

  GainMartinize(param) {
    param.method = "martinize_protein";
    param.action = "run";
    return MagGet(GetUrl, param);
  },
  GainMartinize3(param) {
    param.method = "martinize_protein3";
    param.action = "run";
    return MagGet(GetUrl, param);
  },

  GainPLIP(data) {
    data.method = "plip";
    data.action = "run";
    // return MagGet(GetUrl, param)
    return MagPost(PostUrl, data);
  },

  GainXScore(param) {
    param.method = "xscoreV2";
    param.action = "run";
    return MagGet(GetUrl, param);
  },

  GainLEDock(data) {
    data.method = "ledockV2";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  GainBabel(data) {
    data.method = "obabel";
    data.action = "run";
    return MagPost(PostUrl, data);
    // return MagGet(GetUrl, param)
  },

  GainPlants(data) {
    data.method = "plants";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  GainCommol(data) {
    data.method = "commol";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  GaintrRosetta(data) {
    data.method = "trrosetta";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGmmpbsa(data) {
    data.method = "g_mmpbsa";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGmmpbsaAnalysis(data) {
    data.method = "g_mmpbsa_analysis";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGromacs(data) {
    data.method = "gromacs";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainRgb(data) {
    data.method = "rgb";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainTksa(data) {
    data.method = "tksa";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGlapd(data) {
    data.method = "glapd";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGmx(data) {
    data.method = "gmx";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  Multiwfn(data) {
    data.method = "multiwfn";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainExp4cas9(data) {
    data.method = "exp4cas9";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainPdbTools(data) {
    data.method = "pdb_tool";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainModeller(data) {
    data.method = "modeller";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainGzeronine(data) {
    data.method = "gzeronine";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainPrimerDesign(data) {
    data.method = "primer_design";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainFoldXAlaScan(data) {
    data.method = "foldx_alascan";
    data.action = "run";
    return MagPost(PostUrl, data);
  },
  GainClustalW2(data) {
    data.method = "clustal_w2";
    data.action = "run";
    return MagPost(PostUrl, data);
  },

  //工具执行接口结束============

  //获取用户列表(管理员使用)
  GainUserList(page, pageindex) {
    let param = {};
    param.method = "admin";
    param.action = "getUserList";
    param.page = page;
    param.pageindex = pageindex;

    return MagGet(GetUserUrl, param);
  },
  //提交用户修改
  UploadUserInfo(id, auth) {
    let data = {};
    data.id = id;
    data.auth = auth;
    data.method = "admin";
    data.action = "setUserAuth";
    return MagPost(PostUserUrl, data);
  },

  //获取文件库中的文件
  // method	String	是	file
  // action	String	是	get_library_files
  // token	String	是	登陆密钥
  // keyword	String	是	文件名
  // authority	Int	是	0 私有库 1公有库
  // page	Int	是	页码
  // paggindex	Int	是	每页显示条数
  GainFileLibrary(param) {
    param.method = "files";
    param.action = "get_library_files";
    param.pageindex = 16;
    return MagGet(GetUrl, param);
  },

  //编辑文件库中的文件参数 （删除/修改）
  EditFileLibrary(data) {
    data.method = "upload";
    data.action = "editLibraryFile";
    return MagPost(PostUrl, data);
  },

  //获取下载例子表格文件
  GainExcelFile() {
    let param = {};
    param.method = "tasks";
    param.action = "get_sample_file";
    param.file_name = "NEWconfigure.xlsx";
    return MagGet(GetUrl, param);
  },

  //大文件Zip上传
  GainZip(File) {
    let Data = {};
    Data.Zip = File;
    Data.method = "upload";
    Data.action = "uploadZipFile";
    return MagPost(PostUrl, Data);
  },
};

export default request;
