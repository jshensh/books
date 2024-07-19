-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2024-07-20 04:41:48
-- 服务器版本： 10.4.19-MariaDB-log
-- PHP 版本： 7.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- 数据库： `zb`
--

-- --------------------------------------------------------

--
-- 表的结构 `currency`
--

CREATE TABLE `currency` (
  `code` varchar(10) NOT NULL COMMENT 'CNY',
  `name` varchar(10) NOT NULL COMMENT '人民币',
  `scale` tinyint(1) UNSIGNED NOT NULL DEFAULT 2 COMMENT '2',
  `symbol` varchar(5) NOT NULL DEFAULT '' COMMENT '￥',
  `unit_name` varchar(5) NOT NULL DEFAULT '' COMMENT '元',
  `sortid` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `loan`
--

CREATE TABLE `loan` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `transactmode_id` int(10) UNSIGNED NOT NULL,
  `money` decimal(20,8) NOT NULL,
  `is_frozen` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `txt` text NOT NULL,
  `t` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `statements`
--

CREATE TABLE `statements` (
  `id` int(10) UNSIGNED NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `t` int(10) UNSIGNED NOT NULL,
  `low` decimal(20,8) NOT NULL,
  `high` decimal(20,8) NOT NULL,
  `closed` decimal(20,8) NOT NULL,
  `income` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `expend` decimal(20,8) NOT NULL DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `transactmode_id` int(10) UNSIGNED NOT NULL,
  `money` decimal(20,8) NOT NULL,
  `txt` text NOT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `t` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transactmode`
--

CREATE TABLE `transactmode` (
  `id` int(10) UNSIGNED NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `name` varchar(30) NOT NULL,
  `topup` tinyint(1) NOT NULL,
  `withdrawal` tinyint(1) NOT NULL,
  `sk` tinyint(1) NOT NULL,
  `pay` tinyint(1) NOT NULL,
  `sortid` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_shown` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transfer_request`
--

CREATE TABLE `transfer_request` (
  `id` int(10) UNSIGNED NOT NULL,
  `loan_name_from` varchar(60) NOT NULL DEFAULT '',
  `loan_name_to` varchar(60) NOT NULL DEFAULT '',
  `txt` text NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `money` decimal(20,8) UNSIGNED NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL COMMENT '[0 => 待审核, 1 => 已入账, 2 => 已拒绝]',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`code`);

--
-- 表的索引 `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- 表的索引 `statements`
--
ALTER TABLE `statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t` (`t`) USING BTREE;

--
-- 表的索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t` (`t`),
  ADD KEY `transactMode` (`transactmode_id`);

--
-- 表的索引 `transactmode`
--
ALTER TABLE `transactmode`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `transfer_request`
--
ALTER TABLE `transfer_request`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `loan`
--
ALTER TABLE `loan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `statements`
--
ALTER TABLE `statements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `transactmode`
--
ALTER TABLE `transactmode`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `transfer_request`
--
ALTER TABLE `transfer_request`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
