<?php
// 同步期货历史数据的通用脚本
// 用法: php sync_future_data.php [symbol]
// 例如: php sync_future_data.php nf_M0
//       php sync_future_data.php nf_AG0
//       php sync_future_data.php all  (同步所有期货)

error_reporting(E_ERROR | E_PARSE);

$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

$context = stream_context_create(array(
    'http' => array(
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    )
));

// 支持的期货品种列表
$arFutures = array(
    'nf_AG0' => '上海白银期货',
    'nf_AU0' => '上海黄金期货',
    'nf_M0'  => '大连豆粕期货',
    'nf_SC0' => '上海原油期货',
);

// 获取命令行参数
$strSymbol = isset($argv[1]) ? $argv[1] : 'all';

if ($strSymbol === 'all') {
    $arToSync = array_keys($arFutures);
} else {
    if (!isset($arFutures[$strSymbol])) {
        echo "未知的期货品种: $strSymbol\n";
        echo "支持的品种: " . implode(', ', array_keys($arFutures)) . "\n";
        exit(1);
    }
    $arToSync = array($strSymbol);
}

echo "期货历史数据同步工具\n";
echo str_repeat("=", 70) . "\n\n";

foreach ($arToSync as $strSymbol) {
    syncFutureData($conn, $context, $strSymbol, $arFutures[$strSymbol]);
    echo "\n";
}

mysqli_close($conn);
echo "同步完成!\n";

function syncFutureData($conn, $context, $strSymbol, $strName) {
    echo "同步 $strSymbol ($strName)\n";
    echo str_repeat("-", 50) . "\n";

    // 获取 stock_id
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$strSymbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "  找不到 $strSymbol 的 stock_id，跳过\n";
        return;
    }
    $row = mysqli_fetch_assoc($result);
    $strStockId = $row['id'];
    echo "  stock_id: $strStockId\n";

    // 查看当前数据量
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt, MIN(date) as min_date, MAX(date) as max_date FROM stockhistory WHERE stock_id = '$strStockId'");
    $row = mysqli_fetch_assoc($result);
    echo "  当前本地: {$row['cnt']} 条";
    if ($row['cnt'] > 0) {
        echo ", 日期: {$row['min_date']} ~ {$row['max_date']}";
    }
    echo "\n";

    // 从线上获取数据
    $strUrl = "https://www.palmmicro.com/woody/res/stockhistorycn.php?symbol=$strSymbol&num=500";
    $strHtml = @file_get_contents($strUrl, false, $context);
    if (!$strHtml) {
        echo "  无法获取线上数据\n";
        return;
    }

    // 解析表格数据 - 日期, 收盘价 (价格在 <font> 标签内)
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*><font[^>]*>([0-9,\.]+)<\/font><\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        echo "  线上没有找到数据\n";
        return;
    }

    echo "  线上找到: " . count($matches) . " 条记录\n";

    $iInserted = 0;
    $iUpdated = 0;

    foreach ($matches as $match) {
        $strDate = $match[1];
        $strClose = str_replace(',', '', $match[2]);

        // 检查是否已存在
        $checkResult = mysqli_query($conn, "SELECT id, close FROM stockhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            // 检查数据是否相同
            $existRow = mysqli_fetch_assoc($checkResult);
            if (abs(floatval($existRow['close']) - floatval($strClose)) > 0.01) {
                // 数据不同，更新
                $sql = "UPDATE stockhistory SET close = '$strClose', adjclose = '$strClose' WHERE stock_id = '$strStockId' AND date = '$strDate'";
                if (mysqli_query($conn, $sql)) {
                    $iUpdated++;
                }
            }
            continue;
        }

        // 插入新记录
        $sql = "INSERT INTO stockhistory (stock_id, date, close, volume, adjclose) VALUES ('$strStockId', '$strDate', '$strClose', '0', '$strClose')";
        if (mysqli_query($conn, $sql)) {
            $iInserted++;
        }
    }

    echo "  结果: 插入 $iInserted 条, 更新 $iUpdated 条\n";

    // 查看同步后数据量
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt, MIN(date) as min_date, MAX(date) as max_date FROM stockhistory WHERE stock_id = '$strStockId'");
    $row = mysqli_fetch_assoc($result);
    echo "  同步后: {$row['cnt']} 条, 日期: {$row['min_date']} ~ {$row['max_date']}\n";
}
?>
