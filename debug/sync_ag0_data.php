<?php
// 同步 nf_AG0 白银期货的历史数据
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

echo "同步 nf_AG0 白银期货数据\n";
echo str_repeat("=", 70) . "\n\n";

// 获取 nf_AG0 的 stock_id
$result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = 'nf_AG0'");
if (!$result || mysqli_num_rows($result) == 0) {
    die("找不到 nf_AG0 的 stock_id\n");
}
$row = mysqli_fetch_assoc($result);
$strStockId = $row['id'];
echo "nf_AG0 stock_id: $strStockId\n\n";

// 查看当前数据量
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt, MIN(date) as min_date, MAX(date) as max_date FROM stockhistory WHERE stock_id = '$strStockId'");
$row = mysqli_fetch_assoc($result);
echo "当前本地数据: {$row['cnt']} 条, 日期范围: {$row['min_date']} ~ {$row['max_date']}\n\n";

// 1. 同步 stockhistory
echo "从线上获取 stockhistory...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/stockhistorycn.php?symbol=nf_AG0&num=500';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    // 解析表格数据 - 日期, 收盘价 (价格在 <font> 标签内)
    // 格式: <td class=c1>2026-01-13</td><td class=c1><font color=red>21004.00</font></td>
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*><font[^>]*>([0-9,\.]+)<\/font><\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  线上找到 " . count($matches) . " 条记录\n";
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
                        echo "  更新: $strDate $strClose (原: {$existRow['close']})\n";
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
        echo "  插入 $iInserted 条新记录, 更新 $iUpdated 条记录\n";
    } else {
        echo "  没有找到数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

// 查看同步后数据量
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt, MIN(date) as min_date, MAX(date) as max_date FROM stockhistory WHERE stock_id = '$strStockId'");
$row = mysqli_fetch_assoc($result);
echo "\n同步后本地数据: {$row['cnt']} 条, 日期范围: {$row['min_date']} ~ {$row['max_date']}\n";

mysqli_close($conn);
echo "\n同步完成!\n";
?>
