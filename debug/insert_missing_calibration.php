<?php
// 插入缺失的 2026-01-09 校准数据
// 这些数据是从线上获取的

error_reporting(E_ERROR | E_PARSE);

$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// 缺失的数据 (从线上获取)
$missingData = array(
    'SH518800' => array('date' => '2026-01-09', 'close' => '105.6546', 'time' => '20:45', 'num' => '1'),
    'SH518880' => array('date' => '2026-01-09', 'close' => '104.3674', 'time' => '21:30', 'num' => '1'),
    'SZ159934' => array('date' => '2026-01-09', 'close' => '99.9101', 'time' => '21:30', 'num' => '1'),
    'SZ159937' => array('date' => '2026-01-09', 'close' => '104.5911', 'time' => '21:30', 'num' => '1'),
    'SZ159985' => array('date' => '2026-01-09', 'close' => '1448.4767', 'time' => '21:30', 'num' => '1'),
    'SZ161226' => array('date' => '2026-01-09', 'close' => '8853.2598', 'time' => '20:37', 'num' => '1'),
);

echo "开始插入缺失的 2026-01-09 校准数据...\n\n";

$inserted = 0;
$skipped = 0;

foreach ($missingData as $symbol => $data) {
    // 获取 stock_id
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$symbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "$symbol - 错误: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $stockId = $row['id'];

    // 检查是否已存在
    $checkResult = mysqli_query($conn, "SELECT id FROM calibrationhistory WHERE stock_id = '$stockId' AND date = '{$data['date']}'");
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        echo "$symbol - 跳过: 数据已存在\n";
        $skipped++;
        continue;
    }

    // 插入数据
    $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$stockId', '{$data['date']}', '{$data['close']}', '{$data['time']}', '{$data['num']}')";

    if (mysqli_query($conn, $sql)) {
        echo "$symbol - 成功: 插入 {$data['date']} 校准值 {$data['close']}\n";
        $inserted++;
    } else {
        echo "$symbol - 错误: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);

echo "\n=== 完成 ===\n";
echo "插入: $inserted 条\n";
echo "跳过: $skipped 条\n";
?>
