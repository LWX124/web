<?php
// 修复 fundpair 表中的配对关系
// 把 AU0 改成 nf_AU0

error_reporting(E_ERROR | E_PARSE);

$conn = mysqli_connect('localhost', 'root', '', 'camman');
if (!$conn) {
    die("数据库连接失败\n");
}
mysqli_set_charset($conn, 'utf8mb4');

echo "修复 fundpair 表中的配对关系\n";
echo str_repeat("=", 70) . "\n\n";

// 获取 AU0 和 nf_AU0 的 stock_id
$result = mysqli_query($conn, "SELECT id, symbol FROM stock WHERE symbol IN ('AU0', 'nf_AU0')");
$arStockIds = array();
while ($row = mysqli_fetch_assoc($result)) {
    $arStockIds[$row['symbol']] = $row['id'];
    echo $row['symbol'] . " => stock_id: " . $row['id'] . "\n";
}

if (!isset($arStockIds['AU0'])) {
    die("\n找不到 AU0 的 stock_id\n");
}
if (!isset($arStockIds['nf_AU0'])) {
    die("\n找不到 nf_AU0 的 stock_id\n");
}

$strAu0Id = $arStockIds['AU0'];
$strNfAu0Id = $arStockIds['nf_AU0'];

echo "\n准备将 fundpair.stock_id 从 $strAu0Id (AU0) 改为 $strNfAu0Id (nf_AU0)\n";

// 查找使用 AU0 作为配对的基金
$result = mysqli_query($conn, "SELECT f.id, s.symbol FROM fundpair f JOIN stock s ON f.id = s.id WHERE f.stock_id = '$strAu0Id'");
echo "\n当前使用 AU0 配对的基金:\n";
$arFundIds = array();
while ($row = mysqli_fetch_assoc($result)) {
    echo "  " . $row['symbol'] . " (id: " . $row['id'] . ")\n";
    $arFundIds[] = $row['id'];
}

if (count($arFundIds) == 0) {
    echo "没有找到使用 AU0 配对的基金\n";
    mysqli_close($conn);
    exit;
}

// 更新配对
echo "\n更新配对...\n";
$sql = "UPDATE fundpair SET stock_id = '$strNfAu0Id' WHERE stock_id = '$strAu0Id'";
if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "成功更新 $affected 条记录\n";
} else {
    echo "更新失败: " . mysqli_error($conn) . "\n";
}

// 验证更新
echo "\n验证更新结果:\n";
foreach ($arFundIds as $fundId) {
    $result = mysqli_query($conn, "SELECT f.id, s1.symbol as fund, f.stock_id, s2.symbol as pair FROM fundpair f JOIN stock s1 ON f.id = s1.id JOIN stock s2 ON f.stock_id = s2.id WHERE f.id = '$fundId'");
    if ($row = mysqli_fetch_assoc($result)) {
        echo "  " . $row['fund'] . " => " . $row['pair'] . "\n";
    }
}

mysqli_close($conn);
echo "\n修复完成!\n";
?>
