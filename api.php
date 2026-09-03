<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// ==========================================
// 1. 获取全球实时外汇汇率 (每4小时前端会请求一次)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'fx') {
    // 使用稳定的开源汇率 API
    $url = "https://open.er-api.com/v6/latest/USD";
    $opts = ["http" => ["method" => "GET", "timeout" => 3]]; // 设置3秒超时防卡死
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    
    // 默认备用汇率底座
    $rates = ['US' => 1.0, 'CN' => 7.20, 'HK' => 7.80]; 
    if ($result) {
        $data = json_decode($result, true);
        if (isset($data['rates']['CNY'])) $rates['CN'] = floatval($data['rates']['CNY']);
        if (isset($data['rates']['HKD'])) $rates['HK'] = floatval($data['rates']['HKD']);
    }
    echo json_encode(['status' => 'success', 'rates' => $rates]);
    exit;
}

// ==========================================
// 2. 股票实时行情代理 (腾讯财经)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'quote') {
    $symbols = isset($_GET['symbols']) ? $_GET['symbols'] : '';
    if (empty($symbols)) {
        echo json_encode(['status' => 'error', 'prices' => []]);
        exit;
    }

    $url = "http://qt.gtimg.cn/q=" . $symbols;
    $opts = ["http" => ["method" => "GET", "header" => "User-Agent: Mozilla/5.0\r\n"]];
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);
    
    $prices = [];
    if ($result) {
        $result = mb_convert_encoding($result, 'UTF-8', 'GBK');
        $lines = explode(';', $result);
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
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
// 3. 云端数据库持久化存储
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
    if (file_exists($db_file)) echo file_get_contents($db_file);
    else echo json_encode(null);
}
?>
