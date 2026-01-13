<?php
// 使用现有接口更新净值数据（定时任务）
// 用法: php sync_netvalue.php [--force]
// --force: 强制更新，忽略时间检查

error_reporting(E_ERROR | E_PARSE);

// 引入必要的接口（轻量级）
require_once(__DIR__ . '/../php/sql.php');
require_once(__DIR__ . '/../php/stock/stocksymbol.php');
require_once(__DIR__ . '/../php/sql/sqlstocksymbol.php');
require_once(__DIR__ . '/../php/stock/yahoostock.php');

// 初始化数据库连接
SqlConnectDatabase();
InitGlobalStockSql();

// 检查是否强制更新
$bForce = in_array('--force', $argv ?? array());

// QDII 基金符号及其对应的 Yahoo 符号
// 格式: 'A股符号' => 'Yahoo符号'
$arFundMap = array(
    // 美股 QDII - SPY 相关
    'SH501300' => 'SPY',
    'SH513500' => 'SPY',
    'SH513650' => 'SPY',
    'SZ159612' => 'SPY',
    'SZ159655' => 'SPY',
    'SZ161125' => 'SPY',

    // 美股 QDII - QQQ 相关
    'SH513100' => 'QQQ',
    'SH513110' => 'QQQ',
    'SH513300' => 'QQQ',
    'SH513390' => 'QQQ',
    'SH513870' => 'QQQ',
    'SZ159501' => 'QQQ',
    'SZ159513' => 'QQQ',
    'SZ159632' => 'QQQ',
    'SZ159659' => 'QQQ',
    'SZ159660' => 'QQQ',
    'SZ159696' => 'QQQ',
    'SZ159941' => 'QQQ',
    'SZ161130' => 'QQQ',

    // 美股 QDII - 其他
    'SH513290' => 'DIA',      // 道指
    'SH513400' => 'IWM',      // 罗素2000
    'SZ160140' => 'SPY',
    'SZ161126' => 'SPY',
    'SZ161128' => 'QQQ',
    'SZ162415' => 'QQQ',
    'SZ164824' => 'SPY',
    'SZ164906' => 'QQQ',

    // XBI 相关
    'SZ159502' => 'XBI',
    'SZ161127' => 'XBI',

    // XOP 相关
    'SH513350' => 'XOP',
    'SZ159518' => 'XOP',
    'SZ162411' => 'XOP',

    // Oil ETF
    'SZ160416' => 'USO',
    'SZ162719' => 'USO',
    'SZ163208' => 'USO',

    // Commodity
    'SZ161815' => 'DBC',
);

echo "=== 净值数据更新 ===\n";
echo "基金数量: " . count($arFundMap) . "\n";
echo "模式: " . ($bForce ? "强制更新" : "正常更新") . "\n\n";

// 设置美股时区
date_default_timezone_set('America/New_York');

$iUpdated = 0;
$iSkipped = 0;
$net_sql = GetNetValueHistorySql();

// 先获取所有唯一的 Yahoo 符号的净值
$arYahooData = array();
$arUniqueYahoo = array_unique(array_values($arFundMap));

echo "获取 Yahoo 数据...\n";
foreach ($arUniqueYahoo as $strYahoo) {
    echo "  $strYahoo: ";

    // 调用 Yahoo API 获取数据
    $strUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$strYahoo}?interval=1d&range=5d";
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        )
    ));

    $strJson = @file_get_contents($strUrl, false, $context);
    if ($strJson) {
        $arResult = json_decode($strJson, true);
        if (isset($arResult['chart']['result'][0])) {
            $result = $arResult['chart']['result'][0];
            $arTimestamp = $result['timestamp'] ?? array();
            $arAdjClose = $result['indicators']['adjclose'][0]['adjclose'] ?? array();

            if (!empty($arTimestamp) && !empty($arAdjClose)) {
                // 取最后一个有效数据
                for ($i = count($arTimestamp) - 1; $i >= 0; $i--) {
                    if (isset($arAdjClose[$i]) && $arAdjClose[$i] > 0) {
                        $strDate = date('Y-m-d', $arTimestamp[$i]);
                        $strClose = number_format($arAdjClose[$i], 4, '.', '');
                        $arYahooData[$strYahoo] = array('date' => $strDate, 'close' => $strClose);
                        echo "$strClose ($strDate)\n";
                        break;
                    }
                }
            }
        }
    }

    if (!isset($arYahooData[$strYahoo])) {
        echo "无数据\n";
    }

    usleep(200000); // 0.2秒延迟
}

echo "\n更新净值历史...\n";
foreach ($arFundMap as $strSymbol => $strYahoo) {
    echo "处理: $strSymbol -> $strYahoo ";

    // 获取 stock_id
    $strStockId = SqlGetStockId($strSymbol);
    if (!$strStockId) {
        echo "- 跳过: 找不到 stock_id\n";
        $iSkipped++;
        continue;
    }

    // 获取 Yahoo 数据
    if (!isset($arYahooData[$strYahoo])) {
        echo "- 跳过: 无 Yahoo 数据\n";
        $iSkipped++;
        continue;
    }

    $strDate = $arYahooData[$strYahoo]['date'];
    $strClose = $arYahooData[$strYahoo]['close'];

    // 检查是否已存在（非强制模式）
    if (!$bForce) {
        if ($net_sql->GetRecord($strStockId, $strDate)) {
            echo "- 跳过: 已存在 ($strDate)\n";
            $iSkipped++;
            continue;
        }
    }

    // 写入数据库
    if ($net_sql->WriteDaily($strStockId, $strDate, $strClose)) {
        echo "- 更新: $strClose ($strDate)\n";
        $iUpdated++;
    } else {
        echo "- 已存在\n";
        $iSkipped++;
    }
}

echo "\n=== 更新完成 ===\n";
echo "更新: $iUpdated 条\n";
echo "跳过: $iSkipped 条\n";
?>
