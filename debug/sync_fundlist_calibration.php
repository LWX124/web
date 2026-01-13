<?php
// 同步线上 fundlist 页面的 calibrationhistory 数据到本地数据库
// 用法: php sync_fundlist_calibration.php

error_reporting(E_ERROR | E_PARSE);

// 连接数据库
$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

// 从线上 fundlist 页面提取的校准数据 (2026-01-13 从线上获取)
// 格式: symbol => [pair_symbol, position, calibration, date]
$arOnlineData = array(
    'ASHR' => ['SH000300', '1.00', '20.1704', '2026-01-12'],
    'CPER' => ['HG', '1.00', '16.2881', '2026-01-12'],
    'DIA' => ['^DJI', '1.00', '100.0028', '2026-01-09'],
    'GLD' => ['GC', '1.00', '10.9021', '2026-01-12'],
    'GLDM' => ['GC', '1.00', '50.6401', '2026-01-12'],
    'IAU' => ['GC', '1.00', '53.2341', '2026-01-12'],
    'INDA' => ['SENSEX', '1.00', '1567.5730', '2026-01-12'],
    'QQQ' => ['^NDX', '1.00', '41.0969', '2025-10-03'],
    'SGOL' => ['GC', '1.00', '105.1852', '2026-01-12'],
    'SH' => ['^GSPC', '-1.00', '197.0691', '2026-01-12'],
    'SH501043' => ['SH000300', '0.95', '2936.2570', '2026-01-12'],
    'SH510300' => ['SH000300', '1.00', '973.9165', '2026-01-12'],
    'SH510310' => ['SH000300', '1.00', '1016.6220', '2026-01-12'],
    'SH510330' => ['SH000300', '1.00', '960.2345', '2026-01-09'],
    'SH518800' => ['AU0', '1.00', '105.2637', '2026-01-12'],
    'SH518880' => ['AU0', '1.00', '103.9818', '2026-01-12'],
    'SLV' => ['SI', '1.00', '1.1047', '2026-01-12'],
    'SPY' => ['^GSPC', '1.00', '10.0361', '2026-01-12'],
    'SZ159919' => ['SH000300', '1.00', '958.6543', '2026-01-12'],
    'SZ159934' => ['AU0', '1.00', '99.5409', '2026-01-12'],
    'SZ159937' => ['AU0', '1.00', '104.2087', '2026-01-12'],
    'SZ159985' => ['M0', '1.00', '1448.4014', '2026-01-12'],
    'SZ161226' => ['AG0', '1.00', '8864.7537', '2026-01-12'],
    'TQQQ' => ['^NDX', '3.00', '461.3475', '2026-01-12'],
    'UCO' => ['CL', '2.00', '2.9148', '2026-01-12'],
    'USO' => ['CL', '1.00', '0.8277', '2026-01-12'],
    // 指数类
    '^GLD-EU' => ['GC', '1.00', '10.9021', '2026-01-12'],
    '^GLD-JP' => ['GC', '1.00', '10.9021', null],
    '^GSPC' => ['ES', '1.00', '1.0056', '2026-01-12'],
    '^HSI' => ['HSI', '1.00', '1.0014', '2026-01-13'],
    '^NDX' => ['NQ', '1.00', '1.0066', '2026-01-12'],
    '^USO-EU' => ['CL', '1.00', '0.8277', '2026-01-12'],
    '^USO-HK' => ['CL', '1.00', '0.8277', null],
    '^USO-JP' => ['CL', '1.00', '0.8277', null],
    'CHA50CFD' => ['SH000300', '1.00', '0.0444', '2026-01-13'],
    'NKY' => ['NK', '1.00', '1.0012', '2026-01-13'],
);

echo "=== 开始同步 fundlist calibration 数据 ===\n";
echo "需要处理: " . count($arOnlineData) . " 个符号\n\n";

$iUpdated = 0;
$iInserted = 0;
$iSkipped = 0;

foreach ($arOnlineData as $strSymbol => $data) {
    list($strPairSymbol, $strPosition, $strCalibration, $strDate) = $data;

    echo "处理: $strSymbol ";

    // 获取 stock_id
    $escapedSymbol = mysqli_real_escape_string($conn, $strSymbol);
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$escapedSymbol'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "- 警告: stock 表中找不到此符号，跳过\n";
        $iSkipped++;
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $strStockId = $row['id'];

    if ($strDate === null) {
        echo "- 跳过(无日期)\n";
        $iSkipped++;
        continue;
    }

    // 检查 calibrationhistory 是否已存在此日期的记录
    $checkResult = mysqli_query($conn, "SELECT id, close FROM calibrationhistory WHERE stock_id = '$strStockId' AND date = '$strDate'");

    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $existingRow = mysqli_fetch_assoc($checkResult);
        $existingClose = floatval($existingRow['close']);
        $newClose = floatval($strCalibration);

        // 如果值不同，更新
        if (abs($existingClose - $newClose) > 0.0001) {
            $sql = "UPDATE calibrationhistory SET close = '$strCalibration' WHERE id = '" . $existingRow['id'] . "'";
            if (mysqli_query($conn, $sql)) {
                echo "- 更新: $existingClose -> $strCalibration\n";
                $iUpdated++;
            } else {
                echo "- 更新失败\n";
            }
        } else {
            echo "- 已是最新\n";
        }
    } else {
        // 插入新记录
        $sql = "INSERT INTO calibrationhistory (stock_id, date, close, time, num) VALUES ('$strStockId', '$strDate', '$strCalibration', '00:00:00', '1')";
        if (mysqli_query($conn, $sql)) {
            echo "- 新增: $strCalibration ($strDate)\n";
            $iInserted++;
        } else {
            echo "- 插入失败: " . mysqli_error($conn) . "\n";
        }
    }
}

mysqli_close($conn);

echo "\n=== 同步完成 ===\n";
echo "更新: $iUpdated 条\n";
echo "新增: $iInserted 条\n";
echo "跳过: $iSkipped 条\n";
?>
