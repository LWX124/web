-- =====================================================
-- 基金指数对照表数据
-- 数据来源: https://www.palmmicro.com/woody/res/fundlistcn.php
-- 生成日期: 2026-01-06
-- =====================================================
-- fundpair 表结构: id (基金stock_id), stock_id (指数stock_id)
-- fundposition 表结构: id (基金stock_id), close (仓位)
-- =====================================================

USE `camman`;

-- =====================================================
-- 首先确保 stock 表中有这些代码
-- =====================================================

-- 基金代码
INSERT IGNORE INTO stock (symbol, name) VALUES ('ASHR', '沪深300 ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('CPER', '美国铜指数基金');
INSERT IGNORE INTO stock (symbol, name) VALUES ('DIA', '道指ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('GLD', '金价ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('GLDM', 'SPDR Gold MiniShares');
INSERT IGNORE INTO stock (symbol, name) VALUES ('IAU', 'iShares Gold Trust');
INSERT IGNORE INTO stock (symbol, name) VALUES ('INDA', 'iShares MSCI印度ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('QQQ', '纳指100ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SGOL', 'Aberdeen Standard Physical Gold Shares');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH', 'ProShares做空标普500');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH501043', '沪深300LOF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH510300', '沪深300ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH510310', '沪深300ETF易方达');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH510330', '沪深300ETF华夏');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH518800', '黄金基金ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH518880', '黄金ETF华安');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SLV', 'iShares白银ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SPY', 'SPDR标普500 ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SZ159919', '沪深300ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SZ159934', '黄金ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SZ159937', '博时黄金ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SZ159985', '豆粕ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('SZ161226', '国投白银LOF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('TQQQ', '纳斯达克指数ETF-ProShares三倍做多');
INSERT IGNORE INTO stock (symbol, name) VALUES ('UCO', 'ProShares二倍做多原油ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('USO', '油价ETF');
INSERT IGNORE INTO stock (symbol, name) VALUES ('CHA50CFD', '富时中国A50 CFD');
INSERT IGNORE INTO stock (symbol, name) VALUES ('NKY', '日经225 ETF');

-- 指数/期货代码 (使用新浪格式)
INSERT IGNORE INTO stock (symbol, name) VALUES ('SH000300', '沪深300指数');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_HG', 'COMEX铜期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^DJI', '道琼斯工业平均指数');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_GC', 'COMEX黄金期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('GC', 'COMEX黄金期货现货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^NDX', '纳斯达克100指数');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^GSPC', '标普500指数');
INSERT IGNORE INTO stock (symbol, name) VALUES ('nf_AU0', '上海黄金期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('AU0', '上海黄金期货现货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_SI', 'COMEX白银期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('nf_AG0', '上海白银期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_CL', 'WTI原油期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('znb_SENSEX', '印度Sensex指数');
INSERT IGNORE INTO stock (symbol, name) VALUES ('nf_M0', '大连豆粕期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_NK', '日经225期货');

-- =====================================================
-- 清空现有 fundpair 数据重新插入
-- =====================================================
-- DELETE FROM fundpair;

-- =====================================================
-- 插入 fundpair 对照关系
-- 表结构: id = 基金stock_id, stock_id = 指数stock_id
-- =====================================================

-- ASHR -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'ASHR' AND s2.symbol = 'SH000300';

-- CPER -> hf_HG
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'CPER' AND s2.symbol = 'hf_HG';

-- DIA -> ^DJI
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'DIA' AND s2.symbol = '^DJI';

-- GLD -> hf_GC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'GLD' AND s2.symbol = 'hf_GC';

-- GLDM -> GC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'GLDM' AND s2.symbol = 'GC';

-- IAU -> GC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'IAU' AND s2.symbol = 'GC';

-- INDA -> znb_SENSEX
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'INDA' AND s2.symbol = 'znb_SENSEX';

-- QQQ -> ^NDX
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'QQQ' AND s2.symbol = '^NDX';

-- SGOL -> GC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SGOL' AND s2.symbol = 'GC';

-- SH -> ^GSPC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH' AND s2.symbol = '^GSPC';

-- SH501043 -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH501043' AND s2.symbol = 'SH000300';

-- SH510300 -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH510300' AND s2.symbol = 'SH000300';

-- SH510310 -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH510310' AND s2.symbol = 'SH000300';

-- SH510330 -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH510330' AND s2.symbol = 'SH000300';

-- SH518800 -> nf_AU0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH518800' AND s2.symbol = 'nf_AU0';

-- SH518880 -> AU0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SH518880' AND s2.symbol = 'AU0';

-- SLV -> hf_SI
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SLV' AND s2.symbol = 'hf_SI';

-- SPY -> ^GSPC
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SPY' AND s2.symbol = '^GSPC';

-- SZ159919 -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SZ159919' AND s2.symbol = 'SH000300';

-- SZ159934 -> AU0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SZ159934' AND s2.symbol = 'AU0';

-- SZ159937 -> AU0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SZ159937' AND s2.symbol = 'AU0';

-- SZ159985 -> nf_M0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SZ159985' AND s2.symbol = 'nf_M0';

-- SZ161226 -> nf_AG0
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'SZ161226' AND s2.symbol = 'nf_AG0';

-- TQQQ -> ^NDX
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'TQQQ' AND s2.symbol = '^NDX';

-- UCO -> hf_CL
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'UCO' AND s2.symbol = 'hf_CL';

-- USO -> hf_CL
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'USO' AND s2.symbol = 'hf_CL';

-- CHA50CFD -> SH000300
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'CHA50CFD' AND s2.symbol = 'SH000300';

-- NKY -> hf_NK
REPLACE INTO fundpair (id, stock_id)
SELECT s1.id, s2.id FROM stock s1, stock s2
WHERE s1.symbol = 'NKY' AND s2.symbol = 'hf_NK';

-- =====================================================
-- 插入 fundposition 仓位数据
-- 表结构: id = 基金stock_id, close = 仓位
-- 默认仓位为1.0，特殊的会单独标注
-- =====================================================

-- 1倍仓位基金
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'ASHR';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'CPER';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'DIA';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'GLD';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'GLDM';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'IAU';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'INDA';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'QQQ';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SGOL';
REPLACE INTO fundposition (id, close) SELECT id, -1.00 FROM stock WHERE symbol = 'SH';  -- 做空ETF
REPLACE INTO fundposition (id, close) SELECT id, 0.95 FROM stock WHERE symbol = 'SH501043';  -- LOF仓位略低
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SH510300';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SH510310';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SH510330';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SH518800';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SH518880';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SLV';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SPY';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SZ159919';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SZ159934';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SZ159937';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SZ159985';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'SZ161226';
REPLACE INTO fundposition (id, close) SELECT id, 3.00 FROM stock WHERE symbol = 'TQQQ';  -- 3倍杠杆
REPLACE INTO fundposition (id, close) SELECT id, 2.00 FROM stock WHERE symbol = 'UCO';   -- 2倍杠杆
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'USO';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'CHA50CFD';
REPLACE INTO fundposition (id, close) SELECT id, 1.00 FROM stock WHERE symbol = 'NKY';

-- =====================================================
-- 验证数据
-- =====================================================
SELECT
    s1.symbol AS fund_symbol,
    s1.name AS fund_name,
    s2.symbol AS pair_symbol,
    s2.name AS pair_name,
    fp.close AS position
FROM fundpair f
JOIN stock s1 ON f.id = s1.id
JOIN stock s2 ON f.stock_id = s2.id
LEFT JOIN fundposition fp ON fp.id = s1.id
ORDER BY s1.symbol;
