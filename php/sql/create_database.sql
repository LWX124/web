-- =====================================================
-- 创建数据库和数据表 SQL
-- 本地开发环境使用
-- =====================================================

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `camman` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

USE `camman`;

-- =====================================================
-- 基础表
-- =====================================================

-- member 表 (会员)
CREATE TABLE IF NOT EXISTS `camman`.`member` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `password` CHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `status` TINYINT UNSIGNED NOT NULL,
    `activity` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `signature` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `ip_id` INT UNSIGNED NOT NULL,
    FULLTEXT (`signature`),
    INDEX (`status`),
    INDEX (`date`, `time`),
    INDEX (`ip_id`),
    UNIQUE (`email`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- page 表 (页面/URI)
CREATE TABLE IF NOT EXISTS `camman`.`page` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `uri` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    UNIQUE (`uri`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- pagecomment 表 (页面评论)
CREATE TABLE IF NOT EXISTS `camman`.`pagecomment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL,
    `ip_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `comment` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`page_id`) REFERENCES `page`(`id`) ON DELETE CASCADE,
    INDEX (`ip_id`),
    INDEX (`date`, `time`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- profile 表 (会员资料)
CREATE TABLE IF NOT EXISTS `camman`.`profile` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `phone` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `address` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `web` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    `signature` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    FOREIGN KEY (`member_id`) REFERENCES `member`(`id`) ON DELETE CASCADE
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- visitor 表 (访客记录)
CREATE TABLE IF NOT EXISTS `camman`.`visitor` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT UNSIGNED NOT NULL,
    `ip_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    FOREIGN KEY (`page_id`) REFERENCES `page`(`id`) ON DELETE CASCADE,
    INDEX (`ip_id`),
    INDEX (`date`, `time`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- =====================================================
-- 股票相关表
-- =====================================================

-- stock 表 (股票代码)
CREATE TABLE IF NOT EXISTS `camman`.`stock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `symbol` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
    `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    UNIQUE (`symbol`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockhistory 表 (股票历史数据)
CREATE TABLE IF NOT EXISTS `camman`.`stockhistory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(10,3) NOT NULL,
    `volume` BIGINT UNSIGNED NOT NULL,
    `adjclose` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockdividend 表 (股票分红)
CREATE TABLE IF NOT EXISTS `camman`.`stockdividend` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stocksplit 表 (股票拆分)
CREATE TABLE IF NOT EXISTS `camman`.`stocksplit` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockgroup 表 (股票分组)
CREATE TABLE IF NOT EXISTS `camman`.`stockgroup` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT UNSIGNED NOT NULL,
    `groupname` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    FOREIGN KEY (`member_id`) REFERENCES `member`(`id`) ON DELETE CASCADE,
    UNIQUE (`groupname`, `member_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockgroupitem 表 (股票分组项目)
CREATE TABLE IF NOT EXISTS `camman`.`stockgroupitem` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stockgroup_id` INT UNSIGNED NOT NULL,
    `stock_id` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `cost` DOUBLE(10,3) NOT NULL,
    `record` INT NOT NULL,
    INDEX (`record`),
    FOREIGN KEY (`stockgroup_id`) REFERENCES `stockgroup`(`id`) ON DELETE CASCADE,
    UNIQUE (`stock_id`, `stockgroup_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stocktransaction 表 (股票交易记录)
CREATE TABLE IF NOT EXISTS `camman`.`stocktransaction` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `groupitem_id` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `price` DOUBLE(10,3) NOT NULL,
    `fees` DOUBLE(10,3) NOT NULL,
    `filled` DATETIME NOT NULL,
    `remark` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockema50 表 (50日均线)
CREATE TABLE IF NOT EXISTS `camman`.`stockema50` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stockema200 表 (200日均线)
CREATE TABLE IF NOT EXISTS `camman`.`stockema200` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- =====================================================
-- 配对和关联表
-- =====================================================

-- ahpair 表 (A股H股配对)
CREATE TABLE IF NOT EXISTS `camman`.`ahpair` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `pair_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- abpair 表 (A股B股配对)
CREATE TABLE IF NOT EXISTS `camman`.`abpair` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `pair_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- adrpair 表 (ADR配对)
CREATE TABLE IF NOT EXISTS `camman`.`adrpair` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `pair_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- fundpair 表 (基金配对)
CREATE TABLE IF NOT EXISTS `camman`.`fundpair` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `pair_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- =====================================================
-- 其他辅助表
-- =====================================================

-- netvaluehistory 表 (净值历史)
CREATE TABLE IF NOT EXISTS `camman`.`netvaluehistory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- fundest 表 (基金估值)
CREATE TABLE IF NOT EXISTS `camman`.`fundest` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    INDEX (`date`, `time`),
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- fundposition 表 (基金仓位)
CREATE TABLE IF NOT EXISTS `camman`.`fundposition` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `close` DOUBLE(13,6) NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- holdings 表 (持仓)
CREATE TABLE IF NOT EXISTS `camman`.`holdings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- calibration 表 (校准)
CREATE TABLE IF NOT EXISTS `camman`.`calibration` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- lastcalibration 表 (最后校准)
CREATE TABLE IF NOT EXISTS `camman`.`lastcalibration` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `close` DOUBLE(13,6) NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- futurepremium 表 (期货溢价)
CREATE TABLE IF NOT EXISTS `camman`.`futurepremium` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- shareshistory 表 (股份历史)
CREATE TABLE IF NOT EXISTS `camman`.`shareshistory` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- sharesdiff 表 (股份变动)
CREATE TABLE IF NOT EXISTS `camman`.`sharesdiff` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `stock_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `close` DOUBLE(13,6) NOT NULL,
    FOREIGN KEY (`stock_id`) REFERENCES `stock`(`id`) ON DELETE CASCADE,
    UNIQUE (`date`, `stock_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- stocktick 表 (股票tick)
CREATE TABLE IF NOT EXISTS `camman`.`stocktick` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `tick` INT UNSIGNED NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- commonphrase 表 (常用短语)
CREATE TABLE IF NOT EXISTS `camman`.`commonphrase` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT UNSIGNED NOT NULL,
    `str` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    FOREIGN KEY (`member_id`) REFERENCES `member`(`id`) ON DELETE CASCADE,
    UNIQUE (`str`, `member_id`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- groupitemamount 表 (分组项目金额)
CREATE TABLE IF NOT EXISTS `camman`.`groupitemamount` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `num` INT UNSIGNED NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- groupitemextra 表 (分组项目额外信息)
CREATE TABLE IF NOT EXISTS `camman`.`groupitemextra` (
    `id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `record` INT UNSIGNED NOT NULL,
    `quantity` INT NOT NULL,
    `cost` DOUBLE(10,3) NOT NULL
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- =====================================================
-- Bot 相关表
-- =====================================================

-- botmsg 表 (机器人消息)
CREATE TABLE IF NOT EXISTS `camman`.`botmsg` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `text` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    UNIQUE (`text`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- botsrc 表 (机器人来源)
CREATE TABLE IF NOT EXISTS `camman`.`botsrc` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `src` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
    UNIQUE (`src`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- telegrambot 表 (Telegram机器人)
CREATE TABLE IF NOT EXISTS `camman`.`telegrambot` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `botmsg_id` INT UNSIGNED NOT NULL,
    `ip_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `botsrc_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`botmsg_id`) REFERENCES `botmsg`(`id`) ON DELETE CASCADE,
    INDEX (`ip_id`),
    INDEX (`botsrc_id`),
    INDEX (`date`, `time`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- wechatbot 表 (微信机器人)
CREATE TABLE IF NOT EXISTS `camman`.`wechatbot` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `botmsg_id` INT UNSIGNED NOT NULL,
    `ip_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `botsrc_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`botmsg_id`) REFERENCES `botmsg`(`id`) ON DELETE CASCADE,
    INDEX (`ip_id`),
    INDEX (`botsrc_id`),
    INDEX (`date`, `time`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- =====================================================
-- 完成
-- =====================================================
SELECT 'Database and tables created successfully!' AS Status;
