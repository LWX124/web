<?php
$conn = mysqli_connect("localhost", "root", "", "camman");
if (!$conn) { die("连接失败"); }
mysqli_set_charset($conn, "utf8mb4");

echo "SZ159985 豆粕ETF 配对分析:\n";
echo str_repeat("-", 70) . "\n";

// 从 fundpair 查找 SZ159985 的配对
$result = mysqli_query($conn, "SELECT f.stock_id, s.symbol as pair_symbol FROM fundpair f JOIN stock s ON f.stock_id = s.id WHERE f.id = (SELECT id FROM stock WHERE symbol = 'SZ159985')");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "SZ159985 配对标的: " . $row['pair_symbol'] . " (stock_id: " . $row['stock_id'] . ")\n";
    $pairStockId = $row['stock_id'];
    $pairSymbol = $row['pair_symbol'];

    // 检查配对标的的校准数据
    $result2 = mysqli_query($conn, "SELECT date, close FROM calibrationhistory WHERE stock_id = '$pairStockId' ORDER BY date DESC LIMIT 5");
    echo "\n配对标的 $pairSymbol 的 calibrationhistory 数据:\n";
    if ($result2 && mysqli_num_rows($result2) > 0) {
        while ($calRow = mysqli_fetch_assoc($result2)) {
            echo "  日期=" . $calRow["date"] . ", close=" . $calRow["close"] . "\n";
        }
    } else {
        echo "  没有校准数据!\n";
    }

    // 检查配对标的的实时价格
    $result3 = mysqli_query($conn, "SELECT * FROM stockhistory WHERE stock_id = '$pairStockId' ORDER BY date DESC LIMIT 5");
    echo "\n配对标的 $pairSymbol 的 stockhistory 数据:\n";
    if ($result3 && mysqli_num_rows($result3) > 0) {
        while ($hisRow = mysqli_fetch_assoc($result3)) {
            echo "  日期=" . $hisRow["date"] . ", close=" . $hisRow["close"] . "\n";
        }
    } else {
        echo "  没有历史数据!\n";
    }
} else {
    echo "找不到 SZ159985 的配对信息!\n";
}

// 检查 SZ159985 自身的校准数据
echo "\nSZ159985 自身的 calibrationhistory 数据:\n";
$result4 = mysqli_query($conn, "SELECT date, close FROM calibrationhistory WHERE stock_id = (SELECT id FROM stock WHERE symbol = 'SZ159985') ORDER BY date DESC LIMIT 5");
if ($result4 && mysqli_num_rows($result4) > 0) {
    while ($calRow = mysqli_fetch_assoc($result4)) {
        echo "  日期=" . $calRow["date"] . ", close=" . $calRow["close"] . "\n";
    }
} else {
    echo "  没有校准数据!\n";
}

mysqli_close($conn);
?>
