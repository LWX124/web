<?php
// 同步 SZ159937 (博时黄金ETF) 的数据
// 从线上 palmmicro.com 同步到本地数据库
// 用法: php sync_sz159937.php

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

echo "同步 SZ159937 博时黄金ETF 数据\n";
echo str_repeat("=", 70) . "\n\n";

$strSymbol = 'SZ159937';

// 获取 stock_id
$result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$strSymbol'");
if (!$result || mysqli_num_rows($result) == 0) {
    die("找不到 $strSymbol 的 stock_id\n");
}
$row = mysqli_fetch_assoc($result);
$strStockId = $row['id'];
echo "$strSymbol stock_id: $strStockId\n\n";

// ============================================================
// 1. 同步 calibrationhistory (校准记录) - 修复时间字段
// ============================================================
echo "1. 同步 calibrationhistory (校准记录)...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/calibrationhistorycn.php?symbol=' . $strSymbol . '&num=100';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(\d{4}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2}(?::\d{2})?)<\/td>\s*<td[^>]*>(\d+)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  找到 " . count($matches) . " 条记录\n";
        $iInserted = 0;
        $iUpdated = 0;

        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);
            $strTime = $match[3];
            if (strlen($strTime) == 5) {
                $strTime .= ':00';
            }
            $strNum = $match[4];

            // 检查是否已存在
            $checkResult = mysqli_query($conn, "SELECT id, time FROM calibrationhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // 已存在，检查时间是否需要更新
                $existingRow = mysqli_fetch_assoc($checkResult);
                if ($existingRow['time'] != $strTime) {
                    $sql = "UPDATE calibrationhistory SET time = '$strTime', close = '$strClose', num = '$strNum' WHERE id = '" . $existingRow['id'] . "'";
                    if (mysqli_query($conn, $sql)) {
                        $iUpdated++;
                        echo "  更新: $strDate 时间 " . $existingRow['time'] . " -> $strTime\n";
                    }
                }
                continue;
            }

            // 插入新记录
            $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$strStockId', '$strDate', '$strClose', '$strTime', '$strNum')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
            }
        }
        echo "  插入 $iInserted 条, 更新 $iUpdated 条\n";
    } else {
        echo "  没有找到数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

// ============================================================
// 2. 同步 stockhistory (价格历史)
// ============================================================
echo "\n2. 同步 stockhistory (价格历史)...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/stockhistorycn.php?symbol=' . $strSymbol . '&num=100';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    // stockhistory 表格格式: 日期, 价格, 数量, 复权价格
    preg_match_all('/<tr>\s*<td[^>]*>(20\d{2}-\d{2}-\d{2})<\/td>\s*<td[^>]*>(?:<font[^>]*>)?([0-9,\.]+)(?:<\/font>)?<\/td>\s*<td[^>]*>(\d+)<\/td>\s*<td[^>]*>(?:<font[^>]*>)?([0-9,\.]+)(?:<\/font>)?<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  找到 " . count($matches) . " 条记录\n";
        $iInserted = 0;
        $iUpdated = 0;

        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);
            $strVolume = $match[3];
            $strAdjClose = str_replace(',', '', $match[4]);

            // 检查是否已存在
            $checkResult = mysqli_query($conn, "SELECT id, close FROM stockhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                // 已存在，检查价格是否需要更新
                $existingRow = mysqli_fetch_assoc($checkResult);
                if (abs(floatval($existingRow['close']) - floatval($strClose)) > 0.001) {
                    $sql = "UPDATE stockhistory SET close = '$strClose', volume = '$strVolume', adjclose = '$strAdjClose' WHERE id = '" . $existingRow['id'] . "'";
                    if (mysqli_query($conn, $sql)) {
                        $iUpdated++;
                        echo "  更新: $strDate 价格 " . $existingRow['close'] . " -> $strClose\n";
                    }
                }
                continue;
            }

            // 插入新记录
            $sql = "INSERT INTO stockhistory (stock_id, date, close, volume, adjclose) VALUES ('$strStockId', '$strDate', '$strClose', '$strVolume', '$strAdjClose')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
                echo "  插入: $strDate 价格 $strClose\n";
            }
        }
        echo "  插入 $iInserted 条, 更新 $iUpdated 条\n";
    } else {
        echo "  没有找到数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

// ============================================================
// 3. 同步 netvaluehistory (净值历史)
// ============================================================
echo "\n3. 同步 netvaluehistory (净值历史)...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/netvaluehistorycn.php?symbol=' . $strSymbol . '&num=100';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    preg_match_all('/<tr>\s*<td[^>]*>(20\d{2}-\d{2}-\d{2})<\/td>\s*<td[^>]*>(?:<font[^>]*>)?([0-9,\.]+)(?:<\/font>)?<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        echo "  找到 " . count($matches) . " 条记录\n";
        $iInserted = 0;

        foreach ($matches as $match) {
            $strDate = $match[1];
            $strClose = str_replace(',', '', $match[2]);

            // 检查是否已存在
            $checkResult = mysqli_query($conn, "SELECT id FROM netvaluehistory WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                continue;
            }

            // 插入新记录
            $sql = "INSERT INTO netvaluehistory (stock_id, date, close) VALUES ('$strStockId', '$strDate', '$strClose')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
                echo "  插入: $strDate 净值 $strClose\n";
            }
        }
        echo "  插入 $iInserted 条\n";
    } else {
        echo "  没有找到数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

// ============================================================
// 4. 同步 fundest (基金估值)
// ============================================================
echo "\n4. 同步 fundest (基金估值)...\n";
$strUrl = 'https://www.palmmicro.com/woody/res/fundestcn.php?symbol=' . $strSymbol . '&num=100';
$strHtml = @file_get_contents($strUrl, false, $context);
if ($strHtml) {
    // fundest 表格格式: 日期, 估值, 时间
    preg_match_all('/<tr[^>]*>\s*<td[^>]*>(20\d{2}-\d{2}-\d{2})<\/td>\s*<td[^>]*>([0-9,\.]+)<\/td>\s*<td[^>]*>(\d{2}:\d{2}(?::\d{2})?)<\/td>/s', $strHtml, $matches, PREG_SET_ORDER);

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

            // 检查是否已存在
            $checkResult = mysqli_query($conn, "SELECT id FROM fundest WHERE stock_id = '$strStockId' AND date = '$strDate'");
            if ($checkResult && mysqli_num_rows($checkResult) > 0) {
                continue;
            }

            // 插入新记录
            $sql = "INSERT INTO fundest (stock_id, date, close, time) VALUES ('$strStockId', '$strDate', '$strClose', '$strTime')";
            if (mysqli_query($conn, $sql)) {
                $iInserted++;
                echo "  插入: $strDate 估值 $strClose 时间 $strTime\n";
            }
        }
        echo "  插入 $iInserted 条\n";
    } else {
        echo "  没有找到 fundest 数据\n";
    }
} else {
    echo "  无法获取数据\n";
}

mysqli_close($conn);
echo "\n" . str_repeat("=", 70) . "\n";
echo "SZ159937 数据同步完成!\n";
?>
