<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

// 1. 接收前端 POST 数据
$input      = $_POST['input']      ?? '';
$algorithm  = $_POST['algorithm']  ?? '';
$level      = (int)($_POST['level'] ?? 5);
$optionsRaw = $_POST['options']    ?? '{}';
$options    = json_decode($optionsRaw, true) ?: [];

/* ============================================================
 * 2. 与 JS 完全对齐的基础工具函数
 * ============================================================ */

// JS: str.replace(/"/g, '\\"')  —— 只转义双引号（与 JS 一致）
function escapeJsStr($str) {
    return str_replace('"', '\\"', $str);
}

// JS: btoa(unescape(encodeURIComponent(str))) —— 对 UTF-8 字节做 base64
// 对纯 ASCII 输入等价于 base64_encode，故直接用 base64_encode 即可。
function js_btoa($str) {
    return base64_encode($str);
}

// JS: encodeURIComponent(str)
// 不编码：A-Z a-z 0-9  - _ . ! ~ * ' ( )   其余按 UTF-8 字节 %XX 编码
function js_encodeURIComponent($str) {
    $out = '';
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $c   = $str[$i];
        $ord = ord($c);
        if (($ord >= 0x41 && $ord <= 0x5A) || ($ord >= 0x61 && $ord <= 0x7A) ||
            ($ord >= 0x30 && $ord <= 0x39) ||
            $c === '-' || $c === '_' || $c === '.' || $c === '!' ||
            $c === '~' || $c === '*' || $c === "'" || $c === '(' || $c === ')') {
            $out .= $c;
        } else {
            $out .= '%' . strtoupper(bin2hex($c));
        }
    }
    return $out;
}

// JS: escape()  —— 对一组“码元值”做转义（用于 aaa 编译）
// 不编码：A-Z a-z 0-9  @ _ + - . * /
function js_escape_codepoints(array $cps) {
    $out = '';
    foreach ($cps as $cp) {
        $cp &= 0xFFFF; // String.fromCharCode 自动按 16 位截断
        $isAlpha = ($cp >= 0x30 && $cp <= 0x39) || ($cp >= 0x41 && $cp <= 0x5A) || ($cp >= 0x61 && $cp <= 0x7A);
        $isReserved = in_array($cp, [0x40, 0x5F, 0x2B, 0x2D, 0x2E, 0x2A, 0x2F], true); // @ _ + - . * /
        if ($isAlpha || $isReserved) {
            $out .= chr($cp);
        } elseif ($cp < 256) {
            $out .= '%' . strtoupper(str_pad(dechex($cp), 2, '0', STR_PAD_LEFT));
        } else {
            $out .= '%u' . str_pad(dechex($cp), 4, '0', STR_PAD_LEFT);
        }
    }
    return $out;
}

// JS: Math.random().toString(36).slice(2,5)  —— 仅用于 quantumDimension 的噪声位（不参与解密）
function js_noise() {
    $a = '0123456789abcdefghijklmnopqrstuvwxyz';
    $s = '';
    for ($i = 0; $i < 3; $i++) $s .= $a[random_int(0, 35)];
    return $s;
}

/* ============================================================
 * 3. 加密算法实现（逐一对齐 hxjmmw1.js 的 Crypto 对象）
 * ============================================================ */

// --- quantum: 多层 Base64 嵌套 + 反转 ---
function encrypt_quantum($data, $layers) {
    $enc = $data;
    for ($i = 0; $i < $layers; $i++) {
        $enc = js_btoa($enc);
        $enc = strrev($enc);
    }
    $decode = '';
    for ($i = 0; $i < $layers; $i++) {
        $decode .= "\n        e = e.split('').reverse().join('');\n        e = decodeURIComponent(escape(atob(e)));";
    }
    return "<script>\n(function(){\n    try {\n        let e = \"" . escapeJsStr($enc) . "\";\n        " . $decode . "\n        document.write(e);\n    } catch(e) {}\n})();\n</script>";
}

// --- chaos: 字符位移 + 强度因子 ---
function encrypt_chaos($data, $strength) {
    $enc = '';
    $n   = strlen($data);
    for ($i = 0; $i < $n; $i++) {
        $v   = ord($data[$i]) + $strength * 2 + ($i % $strength);
        $enc .= chr($v);
    }
    $b = base64_encode($enc); // JS: 直接 btoa(encrypted)
    return "<script>\n(function(){\n    try {\n        const encrypted = \"" . $b . "\";\n        let e = atob(encrypted);\n        document.write(decodeURIComponent(escape(e.split('').map((c,i)=>String.fromCharCode(c.charCodeAt(0)-" . $strength . "*2-i%" . $strength . ")).join(''))));\n    } catch(e) {}\n})();\n</script>";
}

// --- phantom: Unicode 编码 + 幻影偏移 ---
function encrypt_phantom($data, $level) {
    $uni = '';
    $n   = strlen($data);
    for ($i = 0; $i < $n; $i++) {
        $code = ord($data[$i]) + $level * 100;
        $uni .= '%u' . str_pad(dechex($code), 4, '0', STR_PAD_LEFT);
    }
    return "<script>\n(function(){\n    try {\n        const encoded = \"" . $uni . "\";\n        document.write(decodeURIComponent(escape(unescape(encoded).split('').map(c=>String.fromCharCode(c.charCodeAt(0)-" . $level . "*100)).join(''))));\n    } catch(e) {}\n})();\n</script>";
}

// --- entropy: 逆熵 Base64（与 quantum 同逻辑） ---
function encrypt_entropy($data, $times) {
    $enc = $data;
    for ($i = 0; $i < $times; $i++) {
        $enc = js_btoa($enc);
        $enc = strrev($enc);
    }
    $decode = '';
    for ($i = 0; $i < $times; $i++) {
        $decode .= "\n        e = e.split('').reverse().join('');\n        e = decodeURIComponent(escape(atob(e)));";
    }
    return "<script>\n(function(){\n    try {\n        let e = \"" . escapeJsStr($enc) . "\";\n        " . $decode . "\n        document.write(e);\n    } catch(e) {}\n})();\n</script>";
}

// --- dynamicXOR: 动态异或随机密钥 ---
function encrypt_dynamicXOR($data) {
    // JS: Math.floor(Math.random()*9000)+1000 -> 1000..9999；complexity 参数不使用
    $key = random_int(1000, 9999);
    $enc = '';
    $n   = strlen($data);
    for ($i = 0; $i < $n; $i++) {
        $code = ord($data[$i]) ^ $key; // 码元可 >255，需按码点输出为真实字符
        $enc .= mb_chr_safe($code);
    }
    return "<script>\n(function(){\n    try {\n        const key = " . $key . ";\n        document.write(\"" . escapeJsStr($enc) . "\"\n            .split('')\n            .map(c=>String.fromCharCode(c.charCodeAt(0)^key))\n            .join(''));\n    } catch(e) {}\n})();\n</script>";
}

// --- dynamicXOR1: 动态异或（乱码版，与前端 JS 完全一致） ---
// 按 UTF-16 码元异或（复刻前端 Crypto.dynamicXOR1），观感为真乱码字符，运行还原正确；
// 代理区码元(0xD800-0xDFFF)做 \uXXXX 转义，避免 UTF-8 编码代理值被浏览器替换导致解密失败。
// 加密结果自带 <meta charset="UTF-8"> 声明：另存为独立 HTML 时浏览器强制按 UTF-8 读取。
function encrypt_dynamicXOR1($data) {
    $key = random_int(1000, 9999);
    $enc = '';
    foreach (utf8_to_utf16_codepoints($data) as $cp) {
        $code = $cp ^ $key;                     // 0..65535
        if ($code >= 0xD800 && $code <= 0xDFFF) {
            $enc .= '\\u' . str_pad(dechex($code), 4, '0', STR_PAD_LEFT);
        } else {
            $enc .= mb_chr_safe($code);
        }
    }
    $raw = json_encode($enc, JSON_UNESCAPED_UNICODE);
    $raw = str_replace('\\u', '\u', $raw);
    $raw = str_replace('</script>', '<\/script>', $raw);
    return "<meta charset=\"UTF-8\">\n<script>\n(function(){\n    try {\n        const key = " . $key . ";\n        const raw = " . $raw . ";\n        if (raw.indexOf('\\uFFFD') >= 0) { document.write('<b style=\"color:#c00;font-family:sans-serif;font-size:14px\">&#27491;&#30830;&#25552;&#31034;&#65306;&#25991;&#20214;&#32534;&#30721;&#19981;&#27491;&#30830;&#65292;&#35831;&#29992;UTF-8&#32534;&#30721;&#37325;&#26032;&#20445;&#23384;&#27492;&#25991;&#20214;&#12290;</b>'); } else { document.write(raw.split('').map(c=>String.fromCharCode(c.charCodeAt(0)^key)).join('')); }\n    } catch(e) {}\n})();\n</script>";
}

// UTF-8 字符串 -> UTF-16 码元数组（BMP 字符 1 码元；emoji 等 4 字节字符拆为代理对）
function utf8_to_utf16_codepoints($str) {
    $cps = [];
    $len = strlen($str);
    for ($i = 0; $i < $len; ) {
        $b1 = ord($str[$i]);
        if ($b1 < 0x80) { $cps[] = $b1; $i++; }
        elseif (($b1 & 0xE0) === 0xC0) { $cps[] = (($b1 & 0x1F) << 6) | (ord($str[$i + 1]) & 0x3F); $i += 2; }
        elseif (($b1 & 0xF0) === 0xE0) { $cps[] = (($b1 & 0x0F) << 12) | ((ord($str[$i + 1]) & 0x3F) << 6) | (ord($str[$i + 2]) & 0x3F); $i += 3; }
        else {
            $cp = (($b1 & 0x07) << 18) | ((ord($str[$i + 1]) & 0x3F) << 12) | ((ord($str[$i + 2]) & 0x3F) << 6) | (ord($str[$i + 3]) & 0x3F);
            $cp -= 0x10000;
            $cps[] = 0xD800 | ($cp >> 10);
            $cps[] = 0xDC00 | ($cp & 0x3FF);
            $i += 4;
        }
    }
    return $cps;
}

// --- dynamicXOR2: 动态异或（纯数字数组版） ---
// 密文以数字数组存储（只含数字和逗号），浏览器按任意编码读取文件都不会被误读；
// 解密端用 String.fromCharCode 还原 UTF-8 字节序列，浏览器渲染即得原文。
function encrypt_dynamicXOR2($data) {
    $key  = random_int(1000, 9999);
    $nums = [];
    $n    = strlen($data);
    for ($i = 0; $i < $n; $i++) {
        $nums[] = ord($data[$i]) ^ $key;
    }
    return "<script>\n(function(){\n    try {\n        var k = " . $key . ";\n        var a = [" . implode(',', $nums) . "];\n        var s = '';\n        for (var i = 0; i < a.length; i++) s += String.fromCharCode(a[i] ^ k);\n        document.write(decodeURIComponent(escape(s)));\n    } catch(e) {}\n})();\n</script>";
}

// --- quantumDimension: 多维校验 + 噪声注入 ---
function encrypt_quantumDimension($data, $levels) {
    $encoded = js_encodeURIComponent($data);
    $parts  = [];
    $sum    = 0;
    $len    = strlen($encoded);
    for ($i = 0; $i < $len; $i++) {
        $multiplier = ($i % 7) + 1;
        $code       = ord($encoded[$i]) * $multiplier;
        $parts[]    = $code . '|' . js_noise();
        $sum       += $code;
    }
    $checkSum = $sum * $levels;
    $dataStr  = implode(':', $parts);
    return "<script>\n(function(){\n    try {\n        const dataStr = \"" . escapeJsStr($dataStr) . "\";\n        const data = dataStr.split(':');\n        const check = " . $checkSum . ";\n        const s = data.reduce((total,part)=>total+parseInt(part.split('|')[0]),0);\n        if (s*" . $levels . " !== check) throw new Error('数据校验失败');\n        const result = data.map((part,index)=>{const [cs]=part.split('|');const code=parseInt(cs);const d=(index%7)+1;return String.fromCharCode(code/d);}).join('');\n        document.write(decodeURIComponent(result));\n    } catch(e) {}\n})();\n</script>";
}

// --- super: 叠加加密 quantum -> chaos -> phantom -> entropy ---
function encrypt_super($data, $layers) {
    $result = encrypt_quantum($data, $layers);
    $result = encrypt_chaos($result, $layers);
    $result = encrypt_phantom($result, $layers);
    $result = encrypt_entropy($result, $layers);
    return $result;
}

// --- aaaJS / aaaHTML 共用编译 ---
function aaa_compile($data) {
    $len = strlen($data); // ASCII 下等于 UTF-16 码元数
    $cps = [];
    $cps[0] = (ord($data[0]) + $len) & 0xFFFF;
    for ($i = 1; $i < $len; $i++) {
        $cps[$i] = (ord($data[$i]) + ord($data[$i - 1])) & 0xFFFF;
    }
    return js_escape_codepoints($cps);
}
function aaa_decoder_body($compiled) {
    return 'function(a){a=unescape(a);var c=String.fromCharCode(a.charCodeAt(0)-a.length);for(var i=1;i<a.length;i++){c+=String.fromCharCode(a.charCodeAt(i)-c.charCodeAt(i-1))}return c}("' . $compiled . '")';
}

function encrypt_aaaJS($data) {
    return 'eval(decodeURIComponent(escape(' . aaa_decoder_body(aaa_compile($data)) . ')));';
}
function encrypt_aaaHTML($data) {
    return '<script>document.write(decodeURIComponent(escape(' . aaa_decoder_body(aaa_compile($data)) . ')));</script>';
}

/* 码点 -> UTF-8 字符（dynamicXOR 用，码元可 >255） */
function mb_chr_safe($code) {
    $code &= 0xFFFF;
    if ($code <= 0x7F) return chr($code);
    if ($code <= 0x7FF) return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
    return chr(0xE0 | ($code >> 12)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
}

/* ============================================================
 * 4. 路由分发
 * ============================================================ */
$allowedAlgorithms = ['quantum', 'chaos', 'phantom', 'entropy', 'dynamicXOR',
                      'dynamicXOR1', 'dynamicXOR2',
                      'quantumDimension', 'super', 'aaaJS', 'aaaHTML'];

if (empty($input)) {
    echo json_encode(['success' => false, 'message' => '请输入要加密的内容']);
    exit;
}
if (!in_array($algorithm, $allowedAlgorithms)) {
    echo json_encode(['success' => false, 'message' => '不支持的算法: ' . $algorithm]);
    exit;
}

// 5. 加密前自动保存未加密源码到 html/out/
$outDir = __DIR__ . '/out/';
if (!is_dir($outDir)) @mkdir($outDir, 0777, true);
$saved = '';
try {
    $saveName = '源码_' . date('Ymd_His') . '_' . rand(100, 999) . '.txt';
    @file_put_contents($outDir . $saveName, $input);
    $saved = $saveName;
} catch (Exception $e) {
    $saved = '';
}

try {
    switch ($algorithm) {
        case 'quantum':          $result = encrypt_quantum($input, $level); break;
        case 'chaos':            $result = encrypt_chaos($input, $level); break;
        case 'phantom':          $result = encrypt_phantom($input, $level); break;
        case 'entropy':          $result = encrypt_entropy($input, $level); break;
        case 'dynamicXOR':       $result = encrypt_dynamicXOR($input); break;
        case 'dynamicXOR1':      $result = encrypt_dynamicXOR1($input); break;
        case 'dynamicXOR2':      $result = encrypt_dynamicXOR2($input); break;
        case 'quantumDimension': $result = encrypt_quantumDimension($input, $level); break;
        case 'super':            $result = encrypt_super($input, $level); break;
        case 'aaaJS':            $result = encrypt_aaaJS($input); break;
        case 'aaaHTML':          $result = encrypt_aaaHTML($input); break;
        default:                 $result = '';
    }
    echo json_encode(['success' => true, 'data' => $result, 'saved' => $saved]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '加密失败: ' . $e->getMessage()]);
}
