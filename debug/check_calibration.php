<?php
$conn = mysqli_connect("localhost", "root", "", "camman");
if (!$conn) { die("连接失败"); }
mysqli_set_charset($conn, "utf8mb4");

$arFunds = array("SH518800", "SH518880", "SZ159934", "SZ159937", "SZ159985", "SZ161226");

echo "检查 calibrationhistory 校准数据:\n";
echo str_repeat("-", 70) . "\n";

foreach ($arFunds as $sym) {
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$sym'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "$sym: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $stockId = $row["id"];

    $result2 = mysqli_query($conn, "SELECT date, close FROM calibrationhistory WHERE stock_id = '$stockId' ORDER BY date DESC LIMIT 1");
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $calRow = mysqli_fetch_assoc($result2);
        echo "$sym (id:$stockId): 最新校准日期=" . $calRow["date"] . ", close=" . $calRow["close"] . "\n";
    } else {
        echo "$sym (id:$stockId): 没有校准数据!\n";
    }
}

// 也检查配对标的的校准数据
echo "\n检查配对标的的校准数据:\n";
echo str_repeat("-", 70) . "\n";

$arPairs = array("nf_AU0", "AU0", "nf_M0", "nf_AG0");
foreach ($arPairs as $sym) {
    $result = mysqli_query($conn, "SELECT id FROM stock WHERE symbol = '$sym'");
    if (!$result || mysqli_num_rows($result) == 0) {
        echo "$sym: 找不到 stock_id\n";
        continue;
    }
    $row = mysqli_fetch_assoc($result);
    $stockId = $row["id"];

    $result2 = mysqli_query($conn, "SELECT date, close FROM calibrationhistory WHERE stock_id = '$stockId' ORDER BY date DESC LIMIT 1");
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $calRow = mysqli_fetch_assoc($result2);
        echo "$sym (id:$stockId): 最新校准日期=" . $calRow["date"] . ", close=" . $calRow["close"] . "\n";
    } else {
        echo "$sym (id:$stockId): 没有校准数据!\n";
    }
}

mysqli_close($conn);
?>
