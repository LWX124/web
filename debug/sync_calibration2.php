<?php
// 同步线上 calibrationhistory 数据到本地数据库 - 简化版
// 用法: php sync_calibration2.php

error_reporting(E_ERROR | E_PARSE);

// 直接连接数据库
$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// QDII 基金符号列表
$arSymbols = array(
    // 美股 QDII
    'SH501300', 'SH513290', 'SH513400', 'SZ160140', 'SZ161126', 'SZ161128', 'SZ162415', 'SZ164824', 'SZ164906',
    'SZ159502', 'SZ161127', // XBI
    'SH513350', 'SZ159518', 'SZ162411', // XOP
    'SZ160416', 'SZ162719', 'SZ163208', // Oil ETF
    'SZ161815', // Commodity
    'SH513100', 'SH513110', 'SH513300', 'SH513390', 'SH513870', 'SZ159501', 'SZ159513', 'SZ159632', 'SZ159659', 'SZ159660', 'SZ159696', 'SZ159941', 'SZ161130', // QQQ
    'SH513500', 'SH513650', 'SZ159612', 'SZ159655', 'SZ161125', // SPY
);

echo "需要同步的基金数量: " . count($arSymbols) . "\n";

$strBaseUrl = 'https://www.palmmicro.com/woody/res/calibrationhistorycn.php';

$iTotal = 0;
$iInserted = 0;

// 创建 stream context 以设置超时
$context = stream_context_create(array(
    'http' => array(
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    )
));

foreach ($arSymbols as $strSymbol) {
    echo "\n处理: $strSymbol ";

    // 获取 stock_id
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$strSymbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "- 警告: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $strStockId = $row['id'];
    echo "(stock_id: $strStockId) ";

    // 获取线上数据
    $strUrl = $strBaseUrl . '?symbol=' . $strSymbol . '&num=500';
    $strHtml = @file_get_contents($strUrl, false, $context);
    if (!$strHtml) {
        echo "- 错误: 无法获取数据\n";
        continue;
    }

    // 解析表格数据
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2})<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        echo "- 警告: 没有找到数据\n";
        continue;
    }

    echo "- 找到 " . count($matches) . " 条";
    $iSymbolInserted = 0;

    foreach ($matches as $match) {
        $strDate = $match[1];
        $strClose = str_replace(',', '', $match[2]);
        $strTime = $match[3];
        $strNum = $match[4];

        $iTotal++;

        // 检查是否已存在
        $checkResult = mysqli_query($conn, "SELECT id FROM calibrationhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            continue;
        }

        // 插入数据
        $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$strStockId', '$strDate', '$strClose', '$strTime', '$strNum')";
        if (mysqli_query($conn, $sql)) {
            $iInserted++;
            $iSymbolInserted++;
        }
    }

    echo ", 插入 $iSymbolInserted 条\n";
    flush();

    // 避免请求过快
    usleep(300000); // 0.3秒
}

mysqli_close($conn);

echo "\n\n=== 同步完成 ===\n";
echo "总共处理: $iTotal 条记录\n";
echo "新插入: $iInserted 条记录\n";
?>
