<?php
// 允许跨域请求并设置返回格式为 JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

// 你的云端数据库文件名
$db_file = 'database.json';

// 处理网页发来的保存请求 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    // 将数据写入服务器文件
    if (file_put_contents($db_file, $json_data)) {
        echo json_encode(['status' => 'success', 'message' => 'Data saved to cloud.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to write to database.']);
    }
} 
// 处理网页的读取请求 (GET)
else {
    if (file_exists($db_file)) {
        echo file_get_contents($db_file);
    } else {
        // 如果数据库文件还不存在，返回空
        echo json_encode(null);
    }
}
?>
