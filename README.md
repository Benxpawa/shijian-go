
# shijianwo (视奸我)

一个基于 Go 编写的 Windows 后台工具，能够自动同步当前活跃的窗口进程名到前端页面，并实时显示已使用时长。

![Go](https://img.shields.io/badge/Language-Go-00ADD8?style=flat-square&logo=go)
![PHP](https://img.shields.io/badge/Backend-PHP-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## 功能特性

- **事件驱动**：基于 Windows Event Hook，仅在切换窗口时触发，极低 CPU 占用
- **精确计时**：同一窗口下使用时长持续累加，切换窗口时重新计时
- **命令行控制**：支持 `shijianwo on` / `off` / `exit` 全局指令控制
- **无感运行**：无窗口运行，静默驻留后台

## 部署指南

### 1. 后端部署 (PHP)

将 `api.php` 上传至你的 Web 服务器。确保目录具有**写权限**以生成 `status.json`。

### 2. 客户端编译 (Windows)

```bash
# 克隆仓库
git clone https://github.com/Benxpawa/shijian-go.git
cd shijian-go
```

**修改代码中 `ServerURL` 变量为接口地址**

```bash
# 编译
go build -ldflags="-s -w -H windowsgui" -o shijianwo.exe main.go
```

### 3. 设置环境变量

将生成的 `shijianwo.exe` 放入一个固定目录，并添加到系统环境变量

## 文件说明

| 文件 | 说明 |
|------|------|
| `api.php` | 后端接口，接收客户端数据并保存到 status.json |
| `get_api.php` | 可选的页面，用于查看当前状态（直接读取 status.json） |
| `status.json` | 状态存储文件，由 api.php 自动生成 |

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

    function updateDisplay(data) {
        const now = Math.floor(Date.now() / 1000);

        // 心跳逻辑：40秒内有心跳且状态为 active
        if (data.status === 'active' && (now - data.last_seen < 40)) {
            isBusy = true;
            processName = data.process_name;
            eventTs = Math.floor(new Date(data.event_time).getTime() / 1000);
            dot.style.background = "#ff4757";
        } else {
            isBusy = false;
            dot.style.background = "#7bba7b";
            text.innerText = "空闲中";
        }
    }

    // 时长跳动
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
    setInterval(sync, 1000);
})();
</script>
```

## 常用指令

- `shijianwo`：启动后台监控服务
- `shijianwo off`：进入休眠模式，停止同步
- `shijianwo on`：唤醒服务，恢复同步
- `shijianwo exit`：彻底关闭后台进程

## API 接口

### 获取状态

```
GET api.php?fetch=1
```

返回 JSON：
```json
{
  "process_name": "chrome.exe",
  "app_start_time": "2026-02-25T10:00:00+08:00",
  "event_time": "2026-02-25T10:30:00+08:00",
  "status": "active",
  "last_seen": 1737868800
}
```

### 字段说明

| 字段 | 说明 |
|------|------|
| `process_name` | 当前活跃窗口的进程名 |
| `app_start_time` | 客户端启动时间 |
| `event_time` | 当前窗口开始使用的时间（切换窗口时更新） |
| `status` | 状态：active / off / exit |
| `last_seen` | 最后收到心跳的 Unix 时间戳 |

---

**本项目使用 AI 辅助开发**

基于 [MIT License 协议](https://mit-license.org/)。
