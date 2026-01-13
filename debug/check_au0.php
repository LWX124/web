<?php
$conn = mysqli_connect("localhost", "root", "", "camman");
if (!$conn) { die("连接失败"); }
mysqli_set_charset($conn, "utf8mb4");

echo "检查黄金相关配对数据:\n";
echo str_repeat("=", 70) . "\n\n";

// 检查配对关系
$arFunds = array("SH518800", "SH518880", "SZ159934", "SZ159937");
foreach ($arFunds as $sym) {
    $result = mysqli_query($conn, "SELECT f.stock_id, s.symbol as pair_symbol FROM fundpair f JOIN stock s ON f.stock_id = s.id WHERE f.id = (SELECT id FROM stock WHERE symbol = '$sym')");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "$sym 配对标的: " . $row['pair_symbol'] . " (stock_id: " . $row['stock_id'] . ")\n";
    }
}

echo "\n检查配对标的数据:\n";
echo str_repeat("-", 70) . "\n";

$arPairs = array("nf_AU0", "AU0");
foreach ($arPairs as $sym) {
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$sym'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "$sym: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $stockId = $row['id'];
    echo "\n$sym (stock_id: $stockId):\n";

    // 检查 stockhistory
    $result2 = mysqli_query($conn, "SELECT date, close FROM stockhistory WHERE stock_id = '$stockId' ORDER BY date DESC LIMIT 3");
    echo "  stockhistory:\n";
    if ($result2 && mysqli_num_rows($result2) > 0) {
        while ($r = mysqli_fetch_assoc($result2)) {
            echo "    " . $r['date'] . " => " . $r['close'] . "\n";
        }
    } else {
        echo "    无数据\n";
    }

    // 检查 calibrationhistory
    $result3 = mysqli_query($conn, "SELECT date, close FROM calibrationhistory WHERE stock_id = '$stockId' ORDER BY date DESC LIMIT 3");
    echo "  calibrationhistory:\n";
    if ($result3 && mysqli_num_rows($result3) > 0) {
        while ($r = mysqli_fetch_assoc($result3)) {
            echo "    " . $r['date'] . " => " . $r['close'] . "\n";
        }
    } else {
        echo "    无数据\n";
    }
}

mysqli_close($conn);
?>
