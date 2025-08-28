import {MessageBox} from 'element-ui';

// 设置 rem 函数
function setRem() {
  // 当前页面宽度相对于 1920 宽的缩放比例，可根据自己需要修改。
  const scale = document.documentElement.clientWidth / 100;
  // 设置页面根节点字体大小
  document.documentElement.style.fontSize = scale + 'px';
  let sH = document.documentElement.clientHeight;
  let sW = document.documentElement.clientWidth;

  if (sH <= 540 && sW <= 960) {

    MessageBox.alert(`当前真实分辨率为${sW} X ${sH} 我们推荐您调整合适分辨率再进行操作`, '显示建议', {
      confirmButtonText: '我知道了',
    });
  }

  // console.log('执行Rem',scale,document.documentElement.style.fontSize)
}

// 初始化
setRem();
// 改变窗口大小时重新设置 rem


// 禁止滚轮缩放


window.onresize = function () {
  setRem();
  // console.log('MAGICAL')
};


