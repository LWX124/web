<?php
// 同步线上 calibrationhistory 数据到本地数据库
// 用法: php sync_calibration.php

require_once('../php/sql.php');
require_once('../php/stock/stocksymbol.php');
require_once('../php/sql/sqlstocksymbol.php');

SqlConnectDatabase();

// 获取所有 QDII 基金符号
$arSymbols = array_merge(
    QdiiGetSymbolArray(),
    QdiiHkGetSymbolArray(),
    QdiiJpGetSymbolArray(),
    QdiiEuGetSymbolArray()
);

echo "需要同步的基金数量: " . count($arSymbols) . "\n";

// 线上 API URL
$strBaseUrl = 'https://www.palmmicro.com/woody/res/calibrationhistorycn.php';

$cal_sql = GetCalibrationSql();
$iTotal = 0;
$iInserted = 0;

foreach ($arSymbols as $strSymbol) {
    echo "\n处理: $strSymbol\n";

    // 获取 stock_id
    $strStockId = SqlGetStockId($strSymbol);
    if (!$strStockId) {
        echo "  警告: 找不到 stock_id for $strSymbol\n";
        continue;
    }

    // 获取线上数据 (HTML 页面)
    $strUrl = $strBaseUrl . '?symbol=' . $strSymbol . '&num=500';
    $strHtml = @file_get_contents($strUrl);
    if (!$strHtml) {
        echo "  错误: 无法获取 $strUrl\n";
        continue;
    }

    // 解析表格数据
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2})<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        echo "  警告: 没有找到校准数据\n";
        continue;
    }

    echo "  找到 " . count($matches) . " 条记录\n";
    $iSymbolInserted = 0;

    foreach ($matches as $match) {
        $strDate = $match[1];
        $strClose = str_replace(',', '', $match[2]);
        $strTime = $match[3];
        $strNum = $match[4];

        $iTotal++;

        // 检查是否已存在
        if ($cal_sql->GetRecord($strStockId, $strDate)) {
            continue;
        }

        // 插入数据
        $ar = array(
            'stock_id' => $strStockId,
            'date' => $strDate,
            'close' => $strClose,
            'time' => $strTime,
            'num' => $strNum
        );

        if ($cal_sql->InsertArray($ar)) {
            $iInserted++;
            $iSymbolInserted++;
        }
    }

    echo "  插入了 $iSymbolInserted 条新记录\n";

    // 避免请求过快
    usleep(200000); // 0.2秒
}

echo "\n\n同步完成!\n";
echo "总共处理: $iTotal 条记录\n";
echo "新插入: $iInserted 条记录\n";
?>
