<?php
// 同步 AU0 黄金期货的数据
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

echo "同步 AU0 黄金期货数据\n";
echo str_repeat("=", 70) . "\n\n";

// 获取 AU0 的 stock_id
$result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = 'AU0'");
if (!$result || mysqli_num_rows($result) == 0) {
    die("找不到 AU0 的 stock_id\n");
}
$row = mysqli_fetch_assoc($result);
$strStockId = $row['id'];
echo "AU0 stock_id: $strStockId\n\n";

// 1. 同步 stockhistory
echo "同步 stockhistory...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/stockhistorycn.php?symbol=AU0&num=500';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    // 解析表格数据 - 日期, 收盘价
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  找到 " . count($matches) . " 条记录\n";
        $iInserted = 0;
        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);

            $checkResult = mysqli_query($conn, "SELECT id FROM stockhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                continue;
            }

            $sql = "INSERT INTO stockhistory (stock_id, date, open, high, low, close, volume, adjclose) VALUES ('$strStockId', '$strDate', '$strClose', '$strClose', '$strClose', '$strClose', '0', '$strClose')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
            }
        }
        echo "  插入 $iInserted 条新记录\n";
    } else {
        echo "  没有找到数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

// 2. 同步 calibrationhistory (从 SH518880 获取，因为它配对 AU0)
echo "\n同步 calibrationhistory (从 SH518880 获取)...\n";

// 先获取 SH518880 的 calibrationhistory，它应该已经有了
// 实际上我们需要的是 AU0 本身的校准数据
$strUrl = 'https://www.palmmicro.com/woody/res/calibrationhistorycn.php?symbol=AU0&num=500';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2}(?::\d{2})?)<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  找到 " . count($matches) . " 条记录\n";
        $iInserted = 0;
        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);
            $strTime = $match[3];
            if (strlen($strTime) == 5) {
                $strTime .= ':00';
            }
            $strNum = $match[4];

            $checkResult = mysqli_query($conn, "SELECT id FROM calibrationhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                continue;
            }

            $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$strStockId', '$strDate', '$strClose', '$strTime', '$strNum')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
            }
        }
        echo "  插入 $iInserted 条新记录\n";
    } else {
        echo "  没有找到 AU0 的 calibrationhistory 数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

mysqli_close($conn);
echo "\n同步完成!\n";
?>
