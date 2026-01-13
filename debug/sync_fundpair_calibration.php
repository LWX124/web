<?php
// 同步线上 FundPair 标的的 calibrationhistory 数据到本地数据库
// 这些标的用于 FundPairReference 类的参考估值计算
// 用法: php sync_fundpair_calibration.php

error_reporting(E_ERROR | E_PARSE);

// 直接连接数据库
$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// FundPair 参考标的符号列表 (从 fundpair 表获取)
$arSymbols = array(
    // 美股/指数 FundPair 参考标的
    'INDA',         // 印度基金参考 -> znb_SENSEX
    'KWEB',         // 中概互联网参考
    '^NDX',         // 纳斯达克100指数
    '^GSPC',        // 标普500指数
    '^DJI',         // 道琼斯指数
    'XBI',          // 生物科技ETF
    'XOP',          // 油气ETF
    'IBB',          // 生物科技ETF
    'VNQ',          // 房地产ETF
    'XLK',          // 科技ETF
    'XLY',          // 消费ETF
    'IEO',          // 油气ETF
    'XLE',          // 能源ETF
    'RSPH',         // 医疗保健ETF
    'IXC',          // 全球能源ETF
    'GSG',          // 大宗商品ETF
    'AGG',          // 债券ETF
    // 港股指数
    '^HSI',         // 恒生指数
    '^HSTECH',      // 恒生科技
    '^HSCE',        // 恒生中国企业指数
);

echo "需要同步的标的数量: " . count($arSymbols) . "\n";

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
    $escapedSymbol = mysqli_real_escape_string($conn, $strSymbol);
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$escapedSymbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "- 警告: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $strStockId = $row['id'];
    echo "(stock_id: $strStockId) ";

    // 获取线上数据
    $strUrl = $strBaseUrl . '?symbol=' . urlencode($strSymbol) . '&num=500';
    $strHtml = @file_get_contents($strUrl, false, $context);
    if (!$strHtml) {
        echo "- 错误: 无法获取数据\n";
        continue;
    }

    // 解析表格数据 - calibrationhistory 表格格式:
    // <tr><td>日期</td><td>校准值</td><td>时间</td><td>次数</td>...</tr>
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2}(?::\d{2})?)<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

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
        // 确保时间格式一致 (HH:MM:SS 或 HH:MM)
        if (strlen($strTime) == 5) {
            $strTime .= ':00';
        }
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
