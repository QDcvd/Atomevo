//公共格式化位置

//格式化时间1
export const FormatTime = (time) =>{
    const date = new Date(Number(time));
    const hour = date.getHours();
    const minute = date.getMinutes();
    const second = date.getSeconds();
    if (!time) {
        return ' --:--:--'
    }else {
        return `${hour}:${minute<10?'0'+minute:minute}:${second<10?'0'+second:second}`;
    }

};
//格式化时间2
export const FormatTime2 = (time) =>{
    const date = new Date(Number(time));
    const year = date.getFullYear();
    const month = date.getMonth()+1;
    const day = date.getDate();
    const hour = date.getHours();
    const minute = date.getMinutes();
    const second = date.getSeconds();
    if(!time){
        return '0000-00-00 --:--:--'
    }else {
        return `${year}-${month}-${day} ${hour}:${minute}:${second}`
    }
};


//判断用户类型
export const UserType = (auth) =>{
      let type = '';
      switch (auth) {
        case '1': type = '管理员';break;
        case '2': type = '高级用户';break;
        default: type = '普通用户';break;
      }
      return type;
};


//读取文件后缀名+文件名
// @name    Element传入的文件名
export const FileInfo = (name) =>{
  if(!name){
    return '文件-undefined-'
  }
  let FileSuffix = name.split('.').slice(-1).toString();    //后缀名
  let FileName = name.slice(0, name.length - FileSuffix.length-1);  //文件名
  let Info = {
    name:FileName,
    suffix:FileSuffix
  };
  return Info
};

//下载表格中文件后缀名返回图标class
export const FileIcon = (name) =>{
  if (!name){
    return 'icon-file_icon';
  }
  let Suffix = name.split('.').slice(-1).toString();    //后缀名
  let className = '';
  switch (Suffix) {
    case 'xlsx':className = 'icon-xls_icon';break;
    case 'log':className = 'icon-log_icon';break;
    case 'txt':className = 'icon-txt_icon';break;
    case 'csv':className = 'icon-csv_icon';break;
    case 'zip':className = 'icon-zip_icon';break;
    case 'pdf':className = 'icon-pdf_icon';break;
    case 'xml':className = 'icon-xml_icon';break;
    case 'xls':className = 'icon-xls_icon';break;
    case 'dat':className = 'icon-dat_icon';break;
    default:className = 'icon-file_icon';break;
  }
  return className;
};


//Excel 表格数据里面 去除特殊字符（规则与后端同步)
export const Choosefont = (Font) =>{
  if(Font == undefined ||Font === ''){      // console.log('输入值为 undefined或者为空')
    return Font;
  }else {
    let Self = Font.toString();
    Self =Self.replace(" ",'_');        //空格替换成下划线
    Self = Self.replace("'",'');
    Self = Self.replace('"','');
    Self = Self.replace(';','');
    Self = Self.replace('&','');
    Self = Self.replace('|','');
    Self = Self.replace('\\','');
    Self = Self.replace('#','');
    Self = Self.replace('*','');
    Self = Self.replace('?','');
    Self = Self.replace('$','');
    Self = Self.replace(',','');
    Self = Self.replace('!','');
    Self = Self.replace('\x0A','');
    Self = Self.replace('\xFF','');
    return  Self;
  }
};

