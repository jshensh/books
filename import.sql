-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2020-09-05 01:50:07
-- 服务器版本： 10.3.15-MariaDB-log
-- PHP 版本： 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- 数据库： `newzb`
--

-- --------------------------------------------------------

--
-- 表的结构 `loan`
--

CREATE TABLE `loan` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `transactmode_id` int(10) UNSIGNED NOT NULL,
  `money` decimal(12,2) NOT NULL,
  `txt` text NOT NULL,
  `t` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `statements`
--

CREATE TABLE `statements` (
  `id` int(10) UNSIGNED NOT NULL,
  `t` int(10) UNSIGNED NOT NULL,
  `low` decimal(12,2) NOT NULL,
  `high` decimal(12,2) NOT NULL,
  `closed` decimal(12,2) NOT NULL,
  `income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expend` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `transactmode_id` int(10) UNSIGNED NOT NULL,
  `money` decimal(12,2) NOT NULL,
  `txt` text NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `t` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transactmode`
--

CREATE TABLE `transactmode` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(30) NOT NULL,
  `topup` tinyint(1) NOT NULL,
  `withdrawal` tinyint(1) NOT NULL,
  `sk` tinyint(1) NOT NULL,
  `pay` tinyint(1) NOT NULL,
  `sortid` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转储表的索引
--

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
  ADD UNIQUE KEY `t` (`t`);

--
-- 表的索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t` (`t`),
  ADD KEY `transactmode_id` (`transactmode_id`);

--
-- 表的索引 `transactmode`
--
ALTER TABLE `transactmode`
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
COMMIT;
