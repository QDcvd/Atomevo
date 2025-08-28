import axios from 'axios';
import qs from 'qs';
import store from '@/store';
import {MessageBox} from 'element-ui';
import i18n from '@/lang/lang.js';    //多国语言
// import Vue from 'vue';

const {CancelToken} = axios;    //获取
const service = axios.create({      //配置
  headers:{'version': process.env.VUE_APP_VERSION},       //判断本地版本，便于版本处理
  timeout: 15000,       //30s超时吧
});

let PendingList = [];  //Pengding队列

// 请求拦截处理============(Request)=====================
 const handleRequest = (config)=>{
   const ReqMark = `${config.method} ${config.url}`+JSON.stringify(config.data?config.data:config.params)+JSON.stringify(config.headers);  //请求识别唯一的参数
   const MarkIndex = PendingList.findIndex(item =>{
     return item.label === ReqMark;
   });                                                                                      //匹配是否有相同的参数存在
   let cancel;                                                                              //定义取消
   config.cancelToken = new CancelToken(
     function executor(c) {
       cancel = c;
      }
   );
   config.ReqMark = ReqMark;                                                               //自定义标记 ，在响应拦截时候可以操作
   if(MarkIndex > -1){
     cancel();                                                                             //axios文档介绍+（） 可以执行
   }else {
     PendingList.push({
       label:ReqMark,                   //加入到队列中
       // routeChangeCancel: config.routeChangeCancel    //可以增加一个 路由停止
     })
   }
   return config;
};
//=======================（Request操作）=================
  service.interceptors.request.use(
    config=>{
      return handleRequest(config);
    },
    error => {
      // return Promise.reject(error)
      return Promise.reject(error)
    }
  );



//响应拦截处理=================(Response)==================
const handleResponse = (config)=>{
  const MarkIndex = PendingList.findIndex(item =>{
    return item.label === config.ReqMark;
  });

  if (MarkIndex > -1){                        //找到了需要删除的标识
    PendingList.splice(MarkIndex,1);          //请求已经结束。可以清除自身在队伍中的位置
  }
};

//===============(Response操作)=========================

service.interceptors.response.use(
  response =>{

    handleResponse(response.config);
    const res = response.data;
    if (res.resultCode === 1) {
      // console.log(res.resultCode)
      return res.data?res.data:res;
    } else if (res.resultCode === -1) {
      localStorage.clear();                     // -1 为token超时，过期
    } else {
      return Promise.reject(res)
    }
  },
  error => {

    if(error.code == 'ECONNABORTED'){   //如果是请求超时
      MessageBox.alert(i18n.tc('info.network_msg'), i18n.tc('info.network'), {
        confirmButtonText:i18n.tc('btn.confirm'),
      });
    }
      // console.log(PendingList)
    if(error.config){
      const MarkIndex = PendingList.findIndex(item =>{      //清除
        return item.label === error.config.ReqMark;
      });
      if (MarkIndex > -1){
        PendingList.splice(MarkIndex,1);          //请求已经结束。可以清除自身在队伍中的位置
      } 
    }else if(axios.isCancel(error)){
      MessageBox.alert(i18n.tc('info.network_msg2'), i18n.tc('info.network_fault'), {
        confirmButtonText:i18n.tc('btn.confirm'),
      });
    }
    return Promise.reject(error)

  }
);

//===============(前置结束)==============


function MagGet(url, params, config) {
  params.token = store.state.Token;
  return new Promise((resolve, reject) => {
    service.get(url, {params}, config).then(res => {
      resolve(res);
    }).catch(err => {
      reject(err);
    });

  })
}

// @param FileType :  1 --多文件/单文件上传;
//
function MagPost(url, data, config,FileType) {
  return new Promise((resolve, reject) => {
    let Datas = '';

    if (FileType === 1) {
      Datas = data;
      Datas.append('token', store.state.Token);
    } else {
      // data.token = localStorage.getItem('token');
      data.token = store.state.Token;
      Datas = qs.stringify(data);
    }
    service.post(url, Datas, config).then(res => {
      resolve(res)
    }).catch(err => {
      reject(err)
    })
  })
}



export {MagGet, MagPost};
