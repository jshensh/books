账簿
====

首先说明，作者不是做财务的，部分概念可能会有错误，请各位指出，谢谢。
手机上用得比较多，懒得写 UI，各位好心人可以帮忙加上。。。

作者博客 [http://233.imjs.work/](http://233.imjs.work/)

### 安装

1. 修改 ``/.env`` 中的数据库连接信息
2. 导入 ``import.sql``
3. bash 下执行 ``composer install && chown -R www:www .``
4. bash 下执行 ``php think ResetPassword`` 完成密码的重设
5. bash 下执行 ``php think ResetLoanShareKey`` 完成账单分享密钥的重设

### 旧版本升级

1. 数据库中执行

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

INSERT INTO `currency` (`code`, `name`, `scale`, `symbol`, `unit_name`, `sortid`) VALUES ('CNY', '人民币', '2', '¥', '元', '0');

--
-- 表 `statements`
--

ALTER TABLE `statements` ADD `currency_code` VARCHAR(10) NOT NULL AFTER `id`;
ALTER TABLE `statements` CHANGE `low` `low` DECIMAL(20,8) NOT NULL, CHANGE `high` `high` DECIMAL(20,8) NOT NULL, CHANGE `closed` `closed` DECIMAL(20,8) NOT NULL, CHANGE `income` `income` DECIMAL(20,8) NOT NULL DEFAULT '0.00', CHANGE `expend` `expend` DECIMAL(20,8) NOT NULL DEFAULT '0.00';
ALTER TABLE `statements` DROP INDEX `t`, ADD INDEX `t` (`t`) USING BTREE;
UPDATE `statements` SET `currency_code`='CNY';

--
-- 表 `transactions`
--

ALTER TABLE `transactions` CHANGE `money` `money` DECIMAL(20,8) NOT NULL, CHANGE `amount` `amount` DECIMAL(20,8) NOT NULL DEFAULT '0.00';

--
-- 表 `transactmode`
--

ALTER TABLE `transactmode` ADD `currency_code` VARCHAR(10) NOT NULL AFTER `id`;
UPDATE `transactmode` SET `currency_code`='CNY';

--
-- 表 `loan`
--

ALTER TABLE `loan` CHANGE `money` `money` DECIMAL(20,8) NOT NULL;
ALTER TABLE `loan` ADD `is_frozen` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `money`;

--
-- 表 `transfer_request`
--

CREATE TABLE `transfer_request` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loan_name_from` varchar(60) NOT NULL DEFAULT '',
  `loan_name_to` varchar(60) NOT NULL DEFAULT '',
  `txt` text NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `money` decimal(20,8) UNSIGNED NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL COMMENT '[0 => 待审核, 1 => 已入账, 2 => 已拒绝]',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
```

2. bash 下执行 ``php think ResetPassword`` 完成密码的重设
3. bash 下执行 ``php think ResetLoanShareKey`` 完成账单分享密钥的重设