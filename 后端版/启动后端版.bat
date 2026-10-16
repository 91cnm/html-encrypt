@echo off
chcp 65001 >nul
cd /d "%~dp0"
title 神梦网络 HTML加密 - 后端版启动器
echo ============================================
echo   神梦网络 HTML加密 - 后端版启动器
echo   浏览器将自动打开 http://127.0.0.1:8899/index.php
echo   关闭本窗口即可停止服务
echo ============================================
echo.
start "" http://127.0.0.1:8899/index.php
"%~dp0php\php.exe" -S 127.0.0.1:8899 -t "%~dp0"
