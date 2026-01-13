<?php
// 同步 ChinaFuture 基金的 calibrationhistory 数据
// 用法: php sync_chinafuture_calibration.php

error_reporting(E_ERROR | E_PARSE);

$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// ChinaFuture 基金列表
$arSymbols = array('SH518800', 'SH518880', 'SZ159934', 'SZ159937', 'SZ159985', 'SZ161226');

echo "同步 ChinaFuture 基金的 calibrationhistory 数据\n";
echo str_repeat("=", 70) . "\n\n";

$strBaseUrl = 'https://www.palmmicro.com/woody/res/calibrationhistorycn.php';

$context = stream_context_create(array(
    'http' => array(
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
    )
));

$iTotalInserted = 0;

foreach ($arSymbols as $strSymbol) {
    echo "处理: $strSymbol\n";

    // 获取 stock_id
    $escapedSymbol = mysqli_real_escape_string($conn, $strSymbol);
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$escapedSymbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "  警告: 找不到 stock_id\n\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $strStockId = $row['id'];
    echo "  stock_id: $strStockId\n";

    // 分页获取所有线上数据
    $iSymbolInserted = 0;
    $iStart = 0;
    $iPageSize = 500;

    while (true) {
        $strUrl = $strBaseUrl . '?symbol=' . urlencode($strSymbol) . '&num=' . $iPageSize . '&start=' . $iStart;
        echo "  获取: $strUrl\n";
        $strHtml = @file_get_contents($strUrl, false, $context);
        if (!$strHtml) {
            echo "  错误: 无法获取数据\n";
            break;
        }

        // 解析表格数据
        preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2}(?::\d{2})?)<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            break;
        }

        echo "  本页找到 " . count($matches) . " 条记录\n";

        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);
            $strTime = $match[3];
            if (strlen($strTime) == 5) {
                $strTime .= ':00';
            }
            $strNum = $match[4];

            // 检查是否已存在
            $checkResult = mysqli_query($conn, "SELECT id FROM calibrationhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                continue;
            }

            // 插入数据
            $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$strStockId', '$strDate', '$strClose', '$strTime', '$strNum')";
            if (mysqli_query($conn, $sql)) {
                $iSymbolInserted++;
            }
        }

        // 如果返回的记录数少于 pageSize，说明已经是最后一页
        if (count($matches) < $iPageSize) {
            break;
        }

        $iStart += $iPageSize;
        usleep(300000); // 0.3秒延迟
    }

    echo "  插入 $iSymbolInserted 条新记录\n\n";
    $iTotalInserted += $iSymbolInserted;

    usleep(300000); // 0.3秒延迟
}

mysqli_close($conn);

echo str_repeat("=", 70) . "\n";
echo "同步完成! 总共插入 $iTotalInserted 条记录\n";
?>
