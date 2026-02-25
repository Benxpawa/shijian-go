<?php

$statusFile = __DIR__ . '/status.json';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>API 接口内容</title>";
echo "<style>";
echo "body { font-family: 'Microsoft YaHei', Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo "h1 { color: #333; }";
echo ".card { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 10px 0; }";
echo ".label { font-weight: bold; color: #666; }";
echo ".value { color: #000; }";
echo ".status-active { color: #ff4757; font-weight: bold; }";
echo ".status-off { color: #7bba7b; font-weight: bold; }";
echo "pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 5px; overflow-x: auto; }";
echo ".time { color: #888; font-size: 14px; }";
echo ".error { background: #ffe6e6; color: #cc0000; padding: 15px; border-radius: 5px; }";
echo "</style></head><body>";

echo "<h1>API 接口内容</h1>";

if (!file_exists($statusFile)) {
    echo "<div class='error'>";
    echo "<p>status.json 文件不存在</p>";
    echo "<p>请确保 api.php 已正常运行并生成了状态文件</p>";
    echo "</div>";
} else {
    $content = file_get_contents($statusFile);
    if ($content === false) {
        echo "<div class='error'>";
        echo "<p>读取 status.json 失败</p>";
        echo "</div>";
    } else {
        $data = json_decode($content, true);

        echo "<div class='card'>";
        echo "<p class='label'>JSON 原始数据:</p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";

        if ($data) {
            echo "<div class='card'>";
            echo "<p class='label'>进程名称:</p>";
            echo "<p class='value'>" . ($data['process_name'] ?? 'N/A') . "</p>";
            echo "</div>";

            echo "<div class='card'>";
            echo "<p class='label'>状态:</p>";
            $statusClass = ($data['status'] ?? '') === 'active' ? 'status-active' : 'status-off';
            echo "<p class='value $statusClass'>" . ($data['status'] ?? 'N/A') . "</p>";
            echo "</div>";

            echo "<div class='card'>";
            echo "<p class='label'>事件时间:</p>";
            echo "<p class='value'>" . ($data['event_time'] ?? 'N/A') . "</p>";
            echo "</div>";

            echo "<div class='card'>";
            echo "<p class='label'>最后活跃时间:</p>";
            $lastSeen = $data['last_seen'] ?? 0;
            if ($lastSeen) {
                echo "<p class='value'>" . date('Y-m-d H:i:s', $lastSeen) . " (Unix: $lastSeen)</p>";
            } else {
                echo "<p class='value'>N/A</p>";
            }
            echo "</div>";

            $timeoutSeconds = 40;
            $now = time();

            $eventTime = $data['event_time'] ?? '';
            $eventTs = $eventTime && $eventTime !== 'keep' ? strtotime($eventTime) : 0;
            $lastSeen = $data['last_seen'] ?? 0;
            $lastSeenDiff = $lastSeen ? $now - $lastSeen : 999;
            $usageDiff = $eventTs ? $now - $eventTs : 0;

            echo "<div class='card'>";
            echo "<p class='label'>活跃状态:</p>";
            if ($data['status'] === 'active' && $lastSeenDiff < $timeoutSeconds) {
                echo "<p class='value status-active'>● 忙碌中 (已使用 " . formatDuration($usageDiff) . ")</p>";
            } else {
                echo "<p class='value status-off'>○ 空闲</p>";
            }
            echo "</div>";
        }
    }
}

function formatDuration($seconds) {
    if ($seconds < 60) return $seconds . "秒";
    if ($seconds < 3600) return floor($seconds / 60) . "分" . ($seconds % 60) . "秒";
    return floor($seconds / 3600) . "时" . floor(($seconds % 3600) / 60) . "分";
}

echo "<p class='time'>请求时间: " . date('Y-m-d H:i:s') . "</p>";
echo "<p class='time'>数据文件: status.json</p>";
echo "<p><a href='?refresh=1'>刷新</a></p>";
echo "</body></html>";
