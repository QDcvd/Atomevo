## Atomevo整体介绍

Atomevo-Front是Atomevo的前端部分，主要代码由vue.js编写

Atomevo-Rear是Atomevo的后端部分，主要代码以php语言进行编写，其框架类似于ThinkPhP

服务器文件内容表述（两台服务器类似，这里拿常规服务器作说明）：
1．	anaconda2（实现python环境切换）安装路径：/root/anaconda2

2．	LNMP环境搭建，推荐使用oneinstack一键安装，勾选完环境之后复制命令到linux执行即可（https://oneinstack.com/auto/）
 
3．	Nginx配置文件路径：/usr/local/nginx/conf/vhost，每个域名对应一个配置文件
 
其中ssl文件夹为https证书存放位置，证书过期需要重新替换文件夹中的key和pem文件
4．	代码存放：/data目录下
 
Binaryroot和pyroot分别为科研计算项目软件和脚本存放目录
wwwroot下的dist对应科研计算前端文件，Atomevo-Rear对应后端文件
 
5．	Atomevo-Rear文件介绍：
（1）后端接口实现使用原生代码方式，与前端对接接口基本都放在Atomevo-Rear下的app目录：
基本原理可参照：
浏览器进入科研计算平台F12启用控制台再点击对应功能（下图点击通知中心），可以看到前端请求接口地址

<img width="522" height="244" alt="42f320c56febf5ec2116632b0ace04a" src="https://github.com/user-attachments/assets/cfa1d9df-35d5-4f3b-aa92-060851481e7f" />


以图中的请求地址为例，https://atomevo.com/magapi/ 为nginx处理，定位到/data/wwwroot/mol/下的入口文件index.php，/tool/get告诉入口文件去/data/wwwroot/mol/app下找tool文件夹下的get.php文件，method=tasks&action=tasks_chart则告诉get.php，到get文件夹下寻找tasks.php中的tasks_chart方法
简单来说：
当你看到前端请求的链接时，https://atomevo.com/magapi/  +  文件夹路径，method对应文件名称，action 对应文件中的方法名称即可
Token文件夹处理登陆密钥相关信息
			  
<img width="530" height="200" alt="4342e912b7221ec7c13089ca02bd6f7" src="https://github.com/user-attachments/assets/20556b31-22f3-482d-8587-c6a3d9b64a82" />


（2）Atomevo-Rear后端代码配置文件：

<img width="353" height="322" alt="0b4768162954d3ffc00d2297de88885" src="https://github.com/user-attachments/assets/3d390ba9-2919-430f-a1bc-eb6da1e34030" />


（3）config文件夹存放基本配置信息：

app_config存放任务基本设置
app_list模块对应文件（前端左侧导航信息，新增模块时需要在上面添加模块名）
db_config存放数据库配置
ftp_config存放ftp配置
tencent_config存放腾讯云配置
core文件夹下的Cache.php存放reids缓存配置以及方法

（4） core以及db文件夹下存放一些公共调用的方法，具体可进入文件查看对应注释：

<img width="477" height="314" alt="0756575cdf323f64fe99a9b2c83b8f3" src="https://github.com/user-attachments/assets/39ab27ed-d10b-40d8-ae67-daaa9756dd09" />


（5）Down文件夹存:
放计算任务完成前后的文件，需要复制以及执行，所以一般都在linux给到全部权限 chmod +x 777 ./down
timing文件夹存放定时脚本，参照说明(linux上crontab –l命令可查)：

<img width="467" height="277" alt="5aa8ea817ed06e83b3f8ab1fd5d9d88" src="https://github.com/user-attachments/assets/e0cf61b9-959d-4bdc-bff1-af2a2063e114" />


（6）upload文件夹存放用户上传文件
		 
（7）Workerman文件实现与前端实时通讯，代码上会调用，但是不用做什么配置

（8）Service.php为启用workerman推送等任务脚本：
		 
通过linux上后台执行此文件，实现服务的实时启动和监控
 
		（9）其余相关文件为php扩展等配置类，基本不用操作，所以此处不作说明

