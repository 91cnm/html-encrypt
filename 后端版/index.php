<?php
/**
 * 神梦加密 - 后端版（PHP 加密引擎，界面与纯前端版一致）
 */
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>神梦加密 </title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;
            min-height: 100vh;
            background: linear-gradient(160deg, #fff0f5 0%, #ffdbe8 40%, #ffc9d9 75%, #ffe4ec 100%);
            padding: 20px 16px 40px;
            -webkit-font-smoothing: antialiased;
        }
        /* 顶部装饰光斑 */
        .orb { position: fixed; border-radius: 50%; filter: blur(70px); opacity: .45; pointer-events: none; z-index: 0; }
        .orb-1 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(255,107,157,.35), transparent 70%); top: -12%; right: -10%; }
        .orb-2 { width: 360px; height: 360px; background: radial-gradient(circle, rgba(255,154,158,.3), transparent 70%); bottom: -8%; left: -8%; }

        .wrap { position: relative; z-index: 1; max-width: 860px; margin: 0 auto; }
        .header { text-align: center; padding: 10px 0 14px; }
        .header h1 {
            font-size: 21px; font-weight: 800; letter-spacing: 1px;
            background: linear-gradient(135deg, #ff6b9d, #ff9a9e);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .header .sub { font-size: 11px; color: #a34a6e; margin-top: 3px; }
        .header .qq { display: inline-block; margin-top: 6px; font-size: 11px; color: #d06a8f; background: rgba(255,255,255,.65); border: 1px solid #ffd6e2; border-radius: 20px; padding: 3px 12px; }

        /* 白色卡片 + 粉色顶线 */
        .card {
            background: #fff; border-radius: 14px;
            border-top: 3px solid #ff6b9d;
            box-shadow: 0 6px 24px rgba(255,107,157,.14);
            padding: 20px; margin-bottom: 16px;
        }
        .card-title { font-size: 13px; font-weight: 700; color: #a34a6e; letter-spacing: 1px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .card-title::before { content: ''; width: 4px; height: 14px; border-radius: 2px; background: linear-gradient(180deg, #ff6b9d, #ff9a9e); }

        .config-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 640px) { .config-row { grid-template-columns: 1fr; } }
        .field-label { font-size: 12px; font-weight: 600; color: #c05a80; margin-bottom: 8px; display: block; }
        select {
            width: 100%; padding: 12px 14px; border-radius: 10px;
            border: 1px solid #ffd6e2; background: #fff8fb; color: #5a2a44;
            font-size: 15px; outline: none; cursor: pointer; transition: all .2s;
        }
        select:focus { border-color: #ff6b9d; box-shadow: 0 0 0 3px rgba(255,107,157,.12); }
        select option { background: #fff; }
        .range-group { display: flex; align-items: center; gap: 12px; }
        .range-group label { font-size: 12px; font-weight: 600; color: #c05a80; white-space: nowrap; }
        input[type=range] { flex: 1; -webkit-appearance: none; appearance: none; height: 6px; border-radius: 3px; background: #ffe4ec; outline: none; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, #ff6b9d, #ff9a9e); box-shadow: 0 2px 8px rgba(255,107,157,.35); cursor: pointer; }
        #levelValue { min-width: 30px; text-align: center; font-size: 17px; font-weight: 800; color: #ff6b9d; }

        textarea {
            width: 100%; min-height: 220px; height: 42vh; padding: 14px 16px; resize: vertical;
            border: 1px solid #ffd6e2; border-radius: 10px; background: #fff8fb; color: #5a2a44;
            font-family: 'SF Mono', Menlo, Consolas, monospace; font-size: 13px; line-height: 1.6; outline: none; transition: all .2s;
        }
        @media (max-width: 640px) {
            textarea { min-height: 260px; height: 48vh; font-size: 12px; }
        }
        textarea:focus { border-color: #ff6b9d; box-shadow: 0 0 0 3px rgba(255,107,157,.12); }
        textarea::placeholder { color: #d9a8bc; }
        #output { background: #fff5f8; }

        .upload-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
        .file-label {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 10px;
            background: rgba(255,107,157,.1); color: #ff6b9d; border: 1px solid rgba(255,107,157,.3);
            font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s;
        }
        .file-label:hover { background: rgba(255,107,157,.18); }
        .file-label input { display: none; }
        .file-name { font-size: 12px; color: #d9a8bc; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .btn-row { display: flex; flex-wrap: wrap; gap: 10px; }
        .btn {
            flex: 1; min-width: 120px; padding: 12px 16px; border: none; border-radius: 10px;
            font-size: 14px; font-weight: 700; cursor: pointer; transition: all .2s; text-align: center;
        }
        .btn:active { transform: scale(.96); }
        .btn-main { background: linear-gradient(135deg, #ff6b9d, #ff9a9e); color: #fff; box-shadow: 0 4px 14px rgba(255,107,157,.3); }
        .btn-main:hover { box-shadow: 0 6px 20px rgba(255,107,157,.42); }
        .btn-sec { background: #fff; color: #ff6b9d; border: 1px solid #ffd6e2; }
        .btn-sec:hover { background: #fff8fb; }
        .btn-danger { background: #fff0f3; color: #e05a7a; border: 1px solid #ffcdd9; }
        .btn-danger:hover { background: #ffe4ec; }
        .btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; }

        #status { min-height: 22px; text-align: center; font-size: 14px; font-weight: 600; margin-top: 10px; }
        #status.success { color: #2e9e6b; }
        #status.error { color: #e74c3c; }

        .preview-frame { display: none; }
        .preview-frame iframe {
            width: 100%; height: 380px; border: 1px solid #ffd6e2;
            border-radius: 10px; background: #fff; resize: vertical;
        }

        .footer { text-align: center; color: #d9a8bc; font-size: 12px; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="wrap">
        <div class="header">
            <h1>神梦加密</h1>
            <div class="sub">HTML / JS 后端加密（PHP 引擎）</div>
            <span class="qq">474116782@qq.com</span>
        </div>

        <div class="card">
            <div class="card-title">加密设置</div>
            <div class="config-row">
                <div>
                    <label class="field-label">加密算法</label>
                    <select id="algorithm">
                        <option value="quantum">嵌套加密</option>
                        <option value="chaos">位移加密</option>
                        <option value="phantom">幻影Unicode</option>
                        <option value="entropy">逆熵Base64</option>
                        <option value="dynamicXOR1">动态异或矩阵（乱码）</option>
                        <option value="dynamicXOR2">动态异或矩阵（数字）</option>
                        <option value="quantumDimension">维度加密</option>
                        <option value="super">叠加加密</option>
                        <option value="aaaJS">AAA JS加密</option>
                        <option value="aaaHTML">AAA HTML加密</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">加密强度</label>
                    <div class="range-group">
                        <input type="range" id="level" min="1" max="50" value="5">
                        <span id="levelValue">5</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">输入代码</div>
            <textarea id="input" autofocus>这是一个html加密程序</textarea>
            <div class="upload-row">
                <label class="file-label">
                    <input type="file" id="fileInput" accept=".html,.js,.txt">
                    选择文件加密
                </label>
                <span class="file-name" id="fileName"></span>
            </div>
        </div>

        <div class="btn-row" style="margin-bottom:16px;">
            <button class="btn btn-main" id="encryptBtn" onclick="encrypt()">执行加密</button>
            <button class="btn btn-sec" id="exportBtn" onclick="exportFile()" disabled>导出 HTML</button>
            <button class="btn btn-sec" id="copyBtn" onclick="copyResult()" disabled>复制代码</button>
            <button class="btn btn-sec" id="previewBtn" onclick="previewResult()" disabled>预览</button>
            <button class="btn btn-danger" onclick="clearAll()">清空全部</button>
        </div>

        <div class="card">
            <div class="card-title">加密结果</div>
            <textarea id="output" placeholder="加密结果将显示在这里..." readonly></textarea>
            <div id="status"></div>
        </div>

        <div class="preview-frame" id="previewFrame" style="display:none">
            <div class="card">
                <div class="card-title">预览效果</div>
                <iframe id="previewIframe"></iframe>
            </div>
        </div>

        <div class="footer">神梦网络 · 后端加密（PHP 引擎）</div>
    </div>

    <script>
        let currentEncrypted = null, isProcessing = false;

        function showStatus(msg, type) {
            const status = document.getElementById('status');
            status.textContent = msg;
            status.className = type;
            setTimeout(() => { status.textContent = ''; status.className = ''; }, 2500);
        }

        function setButtons(en) {
            ['exportBtn', 'copyBtn', 'previewBtn'].forEach(id => document.getElementById(id).disabled = !en);
        }

        async function encrypt() {
            if (isProcessing) return;
            const input = document.getElementById('input').value.trim();
            if (!input) { showStatus('请输入代码', 'error'); return; }
            isProcessing = true;
            const btn = document.getElementById('encryptBtn');
            btn.disabled = true;
            showStatus('加密中...', '');
            try {
                const fd = new FormData();
                fd.append('input', input);
                fd.append('algorithm', document.getElementById('algorithm').value);
                fd.append('level', document.getElementById('level').value);
                fd.append('options', JSON.stringify({}));
                const res = await fetch('crypto_engine.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    currentEncrypted = data.data;
                    document.getElementById('output').value = data.data;
                    setButtons(true);
                    showStatus('加密完成', 'success');
                } else {
                    showStatus(data.message || '加密失败', 'error');
                }
            } catch (e) {
                showStatus('请求失败: ' + e.message, 'error');
            } finally {
                isProcessing = false;
                btn.disabled = false;
            }
        }

        function exportFile() {
            if (!currentEncrypted) return;
            const blob = new Blob([currentEncrypted], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'encrypted_' + Date.now() + '.html';
            a.click();
            URL.revokeObjectURL(url);
            showStatus('已导出', 'success');
        }

        async function copyResult() {
            if (!currentEncrypted) return;
            try {
                await navigator.clipboard.writeText(currentEncrypted);
                showStatus('复制成功', 'success');
            } catch {
                const textarea = document.createElement('textarea');
                textarea.value = currentEncrypted;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showStatus('已复制到剪贴板', 'success');
            }
        }

        function previewResult() {
            if (!currentEncrypted) { showStatus('请先执行加密', 'error'); return; }
            const frame = document.getElementById('previewFrame');
            if (frame.style.display === 'none') {
                document.getElementById('previewIframe').srcdoc = currentEncrypted;
                frame.style.display = 'block';
                showStatus('预览已生成', 'success');
            } else {
                frame.style.display = 'none';
                showStatus('预览已关闭', 'success');
            }
        }

        function clearAll() {
            document.getElementById('input').value = '';
            document.getElementById('output').value = '';
            document.getElementById('fileName').textContent = '';
            currentEncrypted = null;
            setButtons(false);
            document.getElementById('previewFrame').style.display = 'none';
            showStatus('已清空', 'success');
        }

        // 文件选择：只加载内容，不自动加密
        document.getElementById('fileInput').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            document.getElementById('fileName').textContent = file.name;
            const reader = new FileReader();
            reader.onload = function () {
                document.getElementById('input').value = reader.result;
                showStatus('文件已加载', 'success');
            };
            reader.readAsText(file);
        });

        document.getElementById('level').addEventListener('input', function () {
            document.getElementById('levelValue').textContent = this.value;
        });
    </script>
</body>
</html>
