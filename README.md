
# shijianwo(视奸我)

一个基于 Go 编写的 Windows 后台工具，能够自动同步当前活跃的窗口进程名到前端页面，并实时显示已使用时长。

![Go](https://img.shields.io/badge/Language-Go-00ADD8?style=flat-square&logo=go)
![PHP](https://img.shields.io/badge/Backend-PHP-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## 功能特性

- **事件驱动**：基于 Windows Event Hook，仅在切换窗口时触发，极低 CPU 占用
- **心跳守护**：即使进程被任务管理器意外关闭，后端也能通过心跳过期自动回退状态
- **命令行控制**：支持 `shijianwo on` / `off` / `exit` 全局指令控制。
- **无感运行**：无窗口运行，静默驻留后台。

## 部署指南

### 1. 后端部署 (PHP)
将 `api.php` 上传至你的 Web 服务器。确保目录具有**写权限**以生成 `status.json`。

### 2. 客户端编译 (Windows)
```bash
# 克隆仓库
git clone [https://github.com/Benxpawa/shijian-go.git](https://github.com/Benxpawa/shijian-go.git)
cd shijianwo
```

**修改代码中`ServerURL`变量**为接口地址

```
# 编译
go build -ldflags="-s -w -H windowsgui" -o shijianwo.exe main.go

```

### 3. 设置环境变量

将生成的 `shijianwo.exe` 放入一个固定目录，并添加到系统环境变量

## 前端调用
```html
<div id="shijianwo-container">
    <span id="sw-dot" style="width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:5px; background:#ccc;"></span>
    <span id="sw-text">正在同步状态...</span>
</div>

<script>
(function() {
    let isBusy = false;
    let processName = "";
    let eventTs = 0;

    const dot = document.getElementById('sw-dot');
    const text = document.getElementById('sw-text');

    // 核心逻辑
    function updateDisplay(data) {
        const now = Math.floor(Date.now() / 1000);
        
        // 心跳逻辑
        // 30秒内有心跳且状态为 active
        if (data.status === 'active' && (now - data.last_seen < 30)) {
            isBusy = true;
            processName = data.process_name;
            eventTs = Math.floor(new Date(data.event_time).getTime() / 1000);
            dot.style.background = "#ff4757"; // 忙碌
        } else {
            isBusy = false;
            dot.style.background = "#7bba7b"; // 空闲
            text.innerText = "空闲中";
        }
    }

    // 时长跳动
    // 目前只能实现在这个窗口待了多长时间，没办法知道这个程序用了多久
    setInterval(() => {
        if (!isBusy) return;
        const now = Math.floor(Date.now() / 1000);
        const diff = now - eventTs;
        
        let timeStr = "";
        if (diff < 60) timeStr = diff + "秒";
        else if (diff < 3600) timeStr = Math.floor(diff / 60) + "分";
        else timeStr = Math.floor(diff / 3600) + "时" + Math.floor((diff % 3600) / 60) + "分";

        text.innerText = `正在使用 ${processName} (${timeStr})`;
    }, 1000);

    // 轮询同步后端数据
    async function sync() {
        try {
            const res = await fetch('api.php?fetch=1');
            const data = await res.json();
            updateDisplay(data);
        } catch (e) {
            console.error("shijianwo: 同步失败");
        }
    }

    sync();
    setInterval(sync, 1000); // 1秒同步一次
})();
</script>
```

## 常用指令

* `shijianwo`：启动后台监控服务。
* `shijianwo off`：进入休眠模式，停止同步。
* `shijianwo on`：唤醒服务，恢复同步。
* `shijianwo exit`：彻底关闭后台进程。

### 其他

**本项目使用AI辅助开发**

基于 [MIT License](https://github.com/Benxpawa/shijian-go?tab=MIT-1-ov-file) 协议。
