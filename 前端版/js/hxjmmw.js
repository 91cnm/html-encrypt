const Crypto = {
    base64Encode: str => btoa(unescape(encodeURIComponent(str))),
    base64Decode: str => decodeURIComponent(escape(atob(str))),

    quantum: (data, layers) => {
        let encrypted = data;
        for (let i = 0; i < layers; i++) {
            encrypted = Crypto.base64Encode(encrypted);
            encrypted = encrypted.split('').reverse().join('');
        }
        return `<script>
(function(){
    try {
        let e = "${encrypted.replace(/"/g, '\\"')}";
        ${Array(layers).fill().map(() => `
        e = e.split('').reverse().join('');
        e = decodeURIComponent(escape(atob(e)));`).join('')}
        document.write(e);
    } catch(e) {}
})();\n<\/script>`;
    },

    chaos: (data, strength) => {
        const encrypted = [...data]
            .map((c, i) => String.fromCharCode(c.charCodeAt(0) + strength * 2 + i % strength))
            .join('');
        return `<script>
(function(){
    try {
        const encrypted = "${Crypto.base64Encode(encrypted)}";
        let e = decodeURIComponent(escape(atob(encrypted)));
        document.write(e.split('').map((c, i) =>
            String.fromCharCode(c.charCodeAt(0) - ${strength} * 2 - i % ${strength})
        ).join(''));
    } catch(e) {}
})();\n<\/script>`;
    },

    phantom: (data, level) => {
        const unicode = [...data]
            .map(c => '%u' + (c.charCodeAt(0) + level * 100).toString(16).padStart(4, '0'))
            .join('');
        return `<script>
(function(){
    try {
        const encoded = "${unicode}";
        document.write(unescape(encoded)
            .split('')
            .map(c => String.fromCharCode(c.charCodeAt(0) - ${level} * 100))
            .join(''));
    } catch(e) {}
})();\n<\/script>`;
    },

    entropy: (data, times) => {
        let encrypted = data;
        for (let i = 0; i < times; i++) {
            encrypted = Crypto.base64Encode(encrypted);
            encrypted = encrypted.split('').reverse().join('');
        }
        return `<script>
(function(){
    try {
        let e = "${encrypted.replace(/"/g, '\\"')}";
        ${Array(times).fill().map(() => `
        e = e.split('').reverse().join('');
        e = decodeURIComponent(escape(atob(e)));`).join('')}
        document.write(e);
    } catch(e) {}
})();\n<\/script>`;
    },

    dynamicXOR1: (data, complexity) => {
        const key = Math.floor(Math.random() * 9000) + 1000;
        const encrypted = [...data]
            .map(c => String.fromCharCode(c.charCodeAt(0) ^ key))
            .join('');
        return `<script>
(function(){
    try {
        const key = ${key};
        document.write("${encrypted.replace(/"/g, '\\"')}"
            .split('')
            .map(c => String.fromCharCode(c.charCodeAt(0) ^ key))
            .join(''));
    } catch(e) {}
})();\n<\/script>`;
    },

    dynamicXOR2: (data, complexity) => {
        const key = Math.floor(Math.random() * 9000) + 1000;
        // 密文以数字数组形式存储，保证输出为纯 ASCII 数字，
        // 避免浏览器以 GBK 等编码读取文件时密文被误读导致解密失败。
        // 使用 codePointAt/fromCodePoint 以支持 emoji 等超出 BMP 的字符。
        const encrypted = [...data]
            .map(c => c.codePointAt(0) ^ key)
            .join(',');
        return `<script>
(function(){
    try {
        var k = ${key};
        var a = [${encrypted}];
        var s = '';
        for (var i = 0; i < a.length; i++) s += String.fromCodePoint(a[i] ^ k);
        document.write(s);
    } catch(e) {}
})();\n<\/script>`;
    },

    quantumDimension: (data, levels) => {
        const parts = [];
        const checkValues = [];
        const encoded = encodeURIComponent(data);
        [...encoded].forEach((c, i) => {
            const multiplier = (i % 7) + 1;
            const code = c.charCodeAt(0) * multiplier;
            const noise = Math.random().toString(36).slice(2, 5);
            parts.push(`${code}|${noise}`);
            checkValues.push(code);
        });

        const checkSum = checkValues.reduce((a, b) => a + b, 0) * levels;
        return `<script>
(function(){
    try {
        const dataStr = "${parts.join(':').replace(/"/g, '\\"')}";
        const data = dataStr.split(':');
        const check = ${checkSum};
        const sum = data.reduce((total, part) => total + parseInt(part.split('|')[0]), 0);
        if (sum * ${levels} !== check) throw new Error('数据校验失败');
        const result = data.map((part, index) => {
            const [codeStr] = part.split('|');
            const code = parseInt(codeStr);
            const divisor = (index % 7) + 1;
            return String.fromCharCode(code / divisor);
        }).join('');
        document.write(decodeURIComponent(result));
    } catch(e) {}
})();\n<\/script>`;
    },

    super: (data, layers) => {
        const processors = [Crypto.quantum, Crypto.chaos, Crypto.phantom, Crypto.entropy];
        return processors.reduce((result, processor) => processor(result, layers), data);
    },

    aaaJS: (data) => {
        function compile(code) {
            let c = String.fromCharCode(code.charCodeAt(0) + code.length);
            for (let i = 1; i < code.length; i++) {
                c += String.fromCharCode(code.charCodeAt(i) + code.charCodeAt(i - 1));
            }
            return escape(c);
        }
        const compiled = compile(data);
        return `eval(function(a){a=unescape(a);var c=String.fromCharCode(a.charCodeAt(0)-a.length);for(var i=1;i<a.length;i++){c+=String.fromCharCode(a.charCodeAt(i)-c.charCodeAt(i-1))}return c}("${compiled}"));`;
    },

    aaaHTML: (data) => {
        function compile(code) {
            let c = String.fromCharCode(code.charCodeAt(0) + code.length);
            for (let i = 1; i < code.length; i++) {
                c += String.fromCharCode(code.charCodeAt(i) + code.charCodeAt(i - 1));
            }
            return escape(c);
        }
        const compiled = compile(data);
        const result = `document.write(function(a){a=unescape(a);var c=String.fromCharCode(a.charCodeAt(0)-a.length);for(var i=1;i<a.length;i++){c+=String.fromCharCode(a.charCodeAt(i)-c.charCodeAt(i-1))}return c}("${compiled}"));`;
        return `<script>${result}<\/script>`;
    }
};

// 核心功能
let currentEncrypted = null;

function encrypt() {
    try {
        const input = document.getElementById('input').value.trim();
        const algorithm = document.getElementById('algorithm').value;
        const level = parseInt(document.getElementById('level').value);

        if (!input) {
            showStatus('请输入内容', 'error');
            return;
        }

        currentEncrypted = Crypto[algorithm](input, level);
        document.getElementById('output').value = currentEncrypted;
        showStatus('加密成功', 'success');
    } catch (e) {
        showStatus(`加密失败: ${e.message}`, 'error');
    }
}

function exportFile() {
    if (!currentEncrypted) {
        showStatus('没有可导出的内容', 'error');
        return;
    }
    const blob = new Blob([currentEncrypted], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `encrypted_${Date.now()}.html`;
    a.click();
    URL.revokeObjectURL(url);
}

async function copyResult() {
    if (!currentEncrypted) {
        showStatus('没有可复制的内容', 'error');
        return;
    }
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
    const out = document.getElementById('output').value;
    if (!out) { showStatus('请先执行加密', 'error'); return; }
    const frame = document.getElementById('previewFrame');
    if (frame.style.display === 'none') {
        document.getElementById('previewIframe').srcdoc = out;
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
    currentEncrypted = null;
    document.getElementById('previewFrame').style.display = 'none';
    showStatus('已清空', 'success');
}

function showStatus(msg, type) {
    const status = document.getElementById('status');
    status.textContent = msg;
    status.className = type;
    setTimeout(() => {
        status.textContent = '';
        status.className = '';
    }, 2000);
}

// 文件选择：只加载内容到输入框，不自动加密下载
document.getElementById('fileInput').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function () {
        const content = reader.result;
        document.getElementById('input').value = content;
        document.getElementById('output').value = '';
        currentEncrypted = null;
        showStatus('文件已加载，点击执行加密', 'success');
    };
    reader.readAsText(file);
});

// 初始化事件
document.getElementById('level').addEventListener('input', function () {
    document.getElementById('levelValue').textContent = this.value;
});