//自定义拖拽
import Vue from 'vue';
Vue.directive('move',{
  inserted: function (el,binding) {
    // console.log('渲染位置',binding.value._self)
    // console.log(el.getBoundingClientRect())
    // let menuinit = sessionStorage.getItem('xy');
    // if (menuinit) {
    //   console.log('可以初始化')
    //   el.style.top = JSON.parse(menuinit).top + 'px';
    // }


    let VueThis = binding.value._self;                    //指向this
    let clientWidth = document.documentElement.clientWidth;
    let clientHeight = document.documentElement.clientHeight;
    let disX = '';                 //点击的坐标校对
    let disY = '';                  //点击的坐标校对
    let isMove = false;
    let RunTime;                      //状态允许计时
    let DoTime = 200;                 //长按响应时间--修改拖拽时间
    const MenuMove = (event) => {                                 //位移函数
      VueThis.handle = false;                     //打断methods 的事件
      let L = event.clientX - disX;                     //X轴
      let T = event.clientY - disY;                     //Y轴

      L = Math.min(Math.max(L, 0), clientWidth - el.offsetWidth);     //不能超过屏幕
      T = Math.min(Math.max(T, 0), clientHeight - el.offsetHeight);

      el.style.left = L + 'px';
      el.style.top = T + 'px';
    };
    const MenuUp = (event) => {                                     //鼠标抬起，位移结束处理参数
      isMove = false;
      // VueThis.handle = true;                     //打断methods 的事件
      let movedata = el.getBoundingClientRect();
      if (clientWidth / 2 < event.clientX) {              //右边半场
        el.style.transition = `ease 0.5s`;
        el.style.left = clientWidth - movedata.width + 'px';           //边缘位置
        let easetime = setTimeout(() => {
          el.style.transition = `ease 0.3s`;
          el.style.left = clientWidth - movedata.width - el.offsetWidth / 4 + 'px';           //优雅位置
          clearTimeout(easetime);
          VueThis.handle = true;                     //开启methods 的事件
        }, 500);

        if (clientHeight - event.clientY <= el.offsetHeight * 2) {             //底部范围
          el.style.top = clientHeight - el.offsetHeight - el.offsetHeight / 4 + 'px';
        } else if (event.clientY <= el.offsetHeight * 2) {                    //顶部范围
          el.style.top = el.offsetHeight / 4 + 'px';
        }

      } else {                                          //左边半场
        el.style.transition = `ease 0.5s`;
        el.style.left = movedata.width - el.offsetWidth + 'px';         //边缘位置
        let easetime = setTimeout(() => {
          el.style.transition = `ease 0.3s`;
          el.style.left = movedata.width - el.offsetWidth + el.offsetWidth / 4 + 'px';         //优雅位置
          clearTimeout(easetime);
          VueThis.handle = true;                     //开启methods 的事件
        }, 500);

        if (clientHeight - event.clientY <= el.offsetHeight * 2) {             //底部范围
          el.style.top = clientHeight - el.offsetHeight - el.offsetHeight / 4 + 'px';
        } else if (event.clientY <= el.offsetHeight * 2) {                    //顶部范围
          el.style.top = el.offsetHeight / 4 + 'px';
        }
      }
    };

    el.onmousedown = function (event) {         // 鼠标按下事件
      event.stopPropagation();
      disX = event.clientX - el.offsetLeft;                 //点击的坐标校对
      disY = event.clientY - el.offsetTop;                  //点击的坐标校对
      el.style.transition = 'none';                           //清除上次过渡动画（会影响移动
      isMove = false;
      RunTime = setTimeout(() => {                          //按下 DoTime 的时候允许执行 移动函数
        isMove = true;
        clearTimeout(RunTime)
      }, DoTime);
      el.onmousemove = function (event) {             // 鼠标移动事件-----给文档流绑定移动事件
        event.preventDefault();
        if (isMove) {
          MenuMove(event)
        }
      };
      el.onmouseleave = function () {           // 鼠标失去焦点离开事件
        clearTimeout(RunTime);
        if (isMove) {                          //中断执行
          let focus = setTimeout(() => {
            MenuUp(event);
            clearTimeout(focus);
            // sessionStorage.setItem('xy',JSON.stringify(el.getBoundingClientRect()));
          }, 500)
        }
      };

      el.onmouseup = function (event) {            //鼠标抬起
        clearTimeout(RunTime);
        if (isMove) {
          MenuUp(event);
          // sessionStorage.setItem('xy',JSON.stringify(el.getBoundingClientRect()));
        }
      }
    };
  }

});
