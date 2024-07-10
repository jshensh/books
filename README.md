```sql
--
-- 表 `currency`
--

CREATE TABLE `currency` (
  `code` varchar(10) NOT NULL COMMENT 'CNY',
  `name` varchar(10) NOT NULL COMMENT '人民币',
  `scale` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '2',
  `symbol` varchar(5) NOT NULL DEFAULT '' COMMENT '￥',
  `unit_name` varchar(5) NOT NULL DEFAULT '' COMMENT '元',
  `sortid` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

ALTER TABLE `currency`
  ADD PRIMARY KEY (`code`);
COMMIT;

--
-- 表 `statements`
--

ALTER TABLE `statements` ADD `currency_code` VARCHAR(10) NOT NULL AFTER `id`;
ALTER TABLE `statements` CHANGE `low` `low` DECIMAL(20,8) NOT NULL, CHANGE `high` `high` DECIMAL(20,8) NOT NULL, CHANGE `closed` `closed` DECIMAL(20,8) NOT NULL, CHANGE `income` `income` DECIMAL(20,8) NOT NULL DEFAULT '0.00', CHANGE `expend` `expend` DECIMAL(20,8) NOT NULL DEFAULT '0.00';
ALTER TABLE `statements` DROP INDEX `t`, ADD INDEX `t` (`t`) USING BTREE;

--
-- 表 `transactions`
--

ALTER TABLE `transactions` CHANGE `money` `money` DECIMAL(20,8) NOT NULL, CHANGE `amount` `amount` DECIMAL(20,8) NOT NULL DEFAULT '0.00';

--
-- 表 `transactmode`
--

ALTER TABLE `transactmode` ADD `currency_code` VARCHAR(10) NOT NULL AFTER `id`;

--
-- 表 `loan`
--

ALTER TABLE `loan` CHANGE `money` `money` DECIMAL(20,8) NOT NULL;
ALTER TABLE `loan` ADD `is_frozen` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `money`;
```