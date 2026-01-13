<?php
// 检查 AU0 的实时价格获取
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../php/stock.php');

echo "检查 AU0 实时价格\n";
echo str_repeat("=", 70) . "\n\n";

$ref = new MyStockReference('AU0');
echo "Symbol: " . $ref->GetSymbol() . "\n";
echo "Price: " . var_export($ref->GetPrice(), true) . "\n";
echo "Date: " . var_export($ref->GetDate(), true) . "\n";
echo "IsSinaFutureCN: " . var_export($ref->IsSinaFutureCN(), true) . "\n";
echo "IsSinaFutureUS: " . var_export($ref->IsSinaFutureUS(), true) . "\n";

echo "\n检查 SH518880 的 FundPairReference:\n";
echo str_repeat("-", 70) . "\n";

$fund_ref = new FundPairReference('SH518880');
echo "Symbol: " . $fund_ref->GetSymbol() . "\n";
echo "pair_ref: " . ($fund_ref->pair_ref ? $fund_ref->pair_ref->GetSymbol() : 'null') . "\n";
if ($fund_ref->pair_ref) {
    echo "pair_ref Price: " . var_export($fund_ref->pair_ref->GetPrice(), true) . "\n";
    echo "pair_ref Date: " . var_export($fund_ref->pair_ref->GetDate(), true) . "\n";
}
echo "GetOfficialDate: " . var_export($fund_ref->GetOfficialDate(), true) . "\n";
echo "GetOfficialNetValue: " . var_export($fund_ref->GetOfficialNetValue(), true) . "\n";
?>
