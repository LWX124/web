-- =====================================================
-- 更新 stock 表中的名称以匹配线上数据
-- 生成日期: 2026-01-13
-- =====================================================

USE `camman`;

-- 更新现有记录的名称
UPDATE stock SET name = 'SPDR Gold MiniShares Trust' WHERE symbol = 'GLDM';
UPDATE stock SET name = '黄金ETF-iShares' WHERE symbol = 'IAU';
UPDATE stock SET name = '印度股指ETF' WHERE symbol = 'INDA';
UPDATE stock SET name = 'abrdn Physical Gold Shares ETF' WHERE symbol = 'SGOL';
UPDATE stock SET name = '标普500指数ETF-ProShares做空' WHERE symbol = 'SH';
UPDATE stock SET name = '黄金ETF' WHERE symbol = 'SH518880';
UPDATE stock SET name = 'iShares-白银ETF-iShares' WHERE symbol = 'SLV';
UPDATE stock SET name = '黄金ETF基金' WHERE symbol = 'SZ159937';
UPDATE stock SET name = '原油指数ETF-ProShares DJ-UBS两倍做多' WHERE symbol = 'UCO';
UPDATE stock SET name = '富时中国A50指数' WHERE symbol = 'CHA50CFD';
UPDATE stock SET name = '日经225' WHERE symbol = 'NKY';

-- 插入新的股票代码（如果不存在）
INSERT IGNORE INTO stock (symbol, name) VALUES ('^GLD-EU', 'GLD欧洲股市收盘价格');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^GLD-JP', 'GLD日本股市收盘价格');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^HSI', '恒生指数-HSI');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^USO-EU', 'USO欧洲股市收盘价格');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^USO-HK', 'USO香港股市收盘价格');
INSERT IGNORE INTO stock (symbol, name) VALUES ('^USO-JP', 'USO日本股市收盘价格');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_ES', '标普500期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_HSI', '恒生指数期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_NQ', '纳斯达克100期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('hf_CHA50CFD', '富时中国A50期货');
INSERT IGNORE INTO stock (symbol, name) VALUES ('znb_NKY', '日经225指数');

-- 验证更新结果
SELECT symbol, name FROM stock
WHERE symbol IN ('GLDM', 'IAU', 'INDA', 'SGOL', 'SH', 'SH518880', 'SLV', 'SZ159937', 'UCO', 'CHA50CFD', 'NKY',
                 '^GLD-EU', '^GLD-JP', '^HSI', '^USO-EU', '^USO-HK', '^USO-JP')
ORDER BY symbol;
