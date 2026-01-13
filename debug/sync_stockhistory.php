<?php
// 同步线上 stockhistory 数据到本地数据库
// 用于美股参考标的的历史价格数据
// 用法: php sync_stockhistory.php

error_reporting(E_ERROR | E_PARSE);

// 直接连接数据库
$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// 美股参考标的符号列表 (用于 QDII 基金估值)
$arSymbols = array(
    // 美股指数和ETF
    '^NDX',     // 纳斯达克100 - QQQ基金参考
    '^GSPC',    // 标普500 - SPY基金参考
    '^DJI',     // 道琼斯工业平均指数
    'XBI',      // 生物科技ETF
    'XOP',      // 油气开采ETF
    'AGG',      // 债券ETF
    'IBB',      // 生物科技ETF (iShares)
    'VNQ',      // 房地产ETF
    'XLK',      // 科技ETF
    'XLY',      // 消费ETF
    'IEO',      // 油气开采ETF (iShares)
    'XLE',      // 能源ETF
    'INDA',     // 印度ETF
    'KWEB',     // 中概互联网ETF
    'GSG',      // 大宗商品ETF
    'RSPH',     // 医疗保健ETF
    'IXC',      // 全球能源ETF
    // 港股指数
    '^HSI',     // 恒生指数
    '^HSTECH',  // 恒生科技
    '^HSCE',    // 恒生中国企业指数
    // 外汇
    'USCNY',    // 美元人民币
    'HKCNY',    // 港币人民币
);

echo "需要同步的标的数量: " . count($arSymbols) . "\n";

$strBaseUrl = 'https://www.palmmicro.com/woody/res/stockhistorycn.php';

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

    // 获取线上数据 (获取500条历史记录)
    $strUrl = $strBaseUrl . '?symbol=' . urlencode($strSymbol) . '&num=500';
    $strHtml = @file_get_contents($strUrl, false, $context);
    if (!$strHtml) {
        echo "- 错误: 无法获取数据\n";
        continue;
    }

    // 解析表格数据 - stockhistory 表格格式:
    // <tr><td>日期</td><td><font color=...>价格</font></td><td>数量</td><td><font color=...>复权价格</font></td></tr>
    preg_match_all('/<tr>\s*<td[^>]*>(20\d{2}-\d{2}-\d{2})<\/td>\s*<td[^>]*><font[^>]*>([0-9,\.]+)<\/font><\/td>\s*<td[^>]*>(\d+)<\/td>\s*<td[^>]*><font[^>]*>([0-9,\.]+)<\/font><\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        echo "- 警告: 没有找到数据\n";
        continue;
    }

    echo "- 找到 " . count($matches) . " 条";
    $iSymbolInserted = 0;

    foreach ($matches as $match) {
        $strDate = $match[1];
        $strClose = str_replace(',', '', $match[2]);
        $strVolume = $match[3];
        $strAdjClose = str_replace(',', '', $match[4]);

        $iTotal++;

        // 检查是否已存在
        $checkResult = mysqli_query($conn, "SELECT id FROM stockhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            continue;
        }

        // 插入数据
        $sql = "INSERT INTO stockhistory (stock_id, date, close, volume, adjclose) VALUES ('$strStockId', '$strDate', '$strClose', '$strVolume', '$strAdjClose')";
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
