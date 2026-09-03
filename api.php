<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// ==========================================
// 1. 股票实时行情代理 (彻底解决手机浏览器跨域拦截)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'quote') {
    $symbols = isset($_GET['symbols']) ? $_GET['symbols'] : '';
    if (empty($symbols)) {
        echo json_encode(['status' => 'error', 'prices' => []]);
        exit;
    }

    // 采用稳定无防盗链的腾讯财经接口
    $url = "http://qt.gtimg.cn/q=" . $symbols;
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    
    $prices = [];
    if ($result) {
        // 腾讯接口返回的是 GBK 编码，需转为 UTF-8
        $result = mb_convert_encoding($result, 'UTF-8', 'GBK');
        $lines = explode(';', $result);
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            // 正则提取：v_usNIO="200~蔚来~NIO~4.01~... 价格固定在第3个位置
            if (preg_match('/v_(.*?)="([^"]+)"/', $line, $matches)) {
                $sym = $matches[1];
                $data = explode('~', $matches[2]);
                if (isset($data[3])) {
                    $prices[$sym] = floatval($data[3]);
                }
            }
        }
    }
    
    echo json_encode(['status' => 'success', 'prices' => $prices]);
    exit;
}

// ==========================================
// 2. 云端数据库持久化存储
// ==========================================
$db_file = 'database.json';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    if (file_put_contents($db_file, $json_data)) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error']);
    }
} else {
    if (file_exists($db_file)) {
        echo file_get_contents($db_file);
    } else {
        echo json_encode(null);
    }
}
?>
