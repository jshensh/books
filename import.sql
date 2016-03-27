-- phpMyAdmin SQL Dump
-- version 4.4.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2016-03-27 20:02:35
-- 服务器版本： 10.0.17-MariaDB-log
-- PHP Version: 5.6.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `zb`
--

DELIMITER $$
--
-- 存储过程
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `doRollback`( IN `loanID1` INT UNSIGNED, IN `loanID2` INT UNSIGNED, IN `transactionsID1` INT UNSIGNED, IN `transactionsID2` INT UNSIGNED, IN `lowIn` DECIMAL(12,2), IN `highIn` DECIMAL(12,2), IN `closedIn` DECIMAL(12,2), IN `tIn` INT UNSIGNED, OUT `status` TINYINT(1) UNSIGNED)
label: begin
    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
        BEGIN
            SHOW WARNINGS;
            ROLLBACK;
        END;

    START TRANSACTION;
        set `status`=0;
        delete from `loan` where `id` in (`loanID1`,`loanID2`);
        delete from `transactions` where `id` in (`transactionsID1`,`transactionsID2`);
        update `statements` set `low`=`lowIn`, `high`=`highIn`, `closed`=`closedIn` where `t`=`tIn`;
        set `status`=1;
    commit;
end$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertNew`( IN `outTransactMode` INT UNSIGNED, IN `inTransactMode` INT UNSIGNED, IN `txtIn` TEXT, IN `moneyIn` DECIMAL(12,2), IN `tIn` INT(10) UNSIGNED, IN `today` INT(10) UNSIGNED, IN `nameIn` VARCHAR(60), OUT `loanID1` INT UNSIGNED, OUT `loanID2` INT UNSIGNED, OUT `transactionsID1` INT UNSIGNED, OUT `transactionsID2` INT UNSIGNED, OUT `lowOut` DECIMAL(12,2), OUT `highOut` DECIMAL(12,2), OUT `closedOut` DECIMAL(12,2))
label: begin
    DECLARE `txt2` text;
    DECLARE `loanAction` varchar(6);
    DECLARE `transactModeTemp` INT UNSIGNED;
    DECLARE `hisLoan` DECIMAL(12,2);
    DECLARE `hisLoanAddNew` DECIMAL(12,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION, SQLWARNING
        BEGIN
            SHOW WARNINGS;
            ROLLBACK;
        END;

    START TRANSACTION;

    select 0 into `loanID1`;
    select 0 into `loanID2`;
    select 0 into `transactionsID1`;
    select 0 into `transactionsID2`;
    select 0 into `lowOut`;
    select 0 into `highOut`;
    select 0 into `closedOut`;
    if ((select count(`id`) from `statements`)!=0) then
        select `low`,`high`,`closed` from `statements` order by `t` desc limit 1 into `lowOut`, `highOut`, `closedOut`;
    end if;
    
    insert into `statements`(`t`,`low`,`high`,`closed`) SELECT `today`, `closedOut`, `closedOut` ,`closedOut` FROM dual WHERE not exists (select `id` from `statements` where `statements`.`t` = `today`);
    select `low`,`high`,`closed` from `statements` where `t` = `today` into `lowOut`, `highOut`, `closedOut` for update;

    if (`moneyIn`<0) then
        set `transactModeTemp`=`outTransactMode`;
    else
        set `transactModeTemp`=`inTransactMode`;
    end if;

    if (`nameIn` != '') then
        select ifnull(sum(`money`),0) from `loan` where `name` = `nameIn` into `hisLoan`;
        set `txt2`=`txtIn`;
        if (`hisLoan`=0) then
            if (`moneyIn`<0) then
                set `txtIn`=concat(`nameIn`,"借款");
            else
                set `txtIn`="借款";
            end if;
        elseif (`hisLoan`<0) then
            if (`moneyIn`<0) then
                set `txtIn`=concat(`nameIn`,"借款");
            else
                set `txtIn`=concat(`nameIn`,"还款");
            end if;
        elseif (`hisLoan`>0) then
            if (`moneyIn`<0) then
                set `txtIn`="还款";
            else
                set `txtIn`="借款";
            end if;
        end if;

        if (`hisLoan`^`moneyIn`>>63 = 1 and `moneyIn` != 0 and `hisLoan`!=0) then
            set `hisLoanAddNew`=`hisLoan`+`moneyIn`;
            if (`hisLoan`^`hisLoanAddNew`>>63 = 1 and `hisLoanAddNew`!=0) then
                set `moneyIn`=`hisLoanAddNew`;
                insert into `loan`(`name`,`transactMode`,`money`,`txt`,`t`) values (`nameIn`,`transactModeTemp`,-`hisLoan`,`txt2`,`tIn`);
                select LAST_INSERT_ID() into `loanID1`;
                if (`loanID1` = 0) then
                    rollback;
                    leave label;
                end if;

                insert into `transactions`(`transactMode`,`money`,`txt`,`t`) values (`transactModeTemp`,-`hisLoan`,`txtIn`,`tIn`);
                select LAST_INSERT_ID() into `transactionsID1`;
                if (`transactionsID1` = 0) then
                    rollback;
                    leave label;
                end if;

                if (`moneyIn`>0) then
                    set `txtIn`="借款";
                else
                    set `txtIn`=concat(`nameIn`,"借款");
                end if;
            end if;
        end if;

        insert into `loan`(`name`,`transactMode`,`money`,`txt`,`t`) values (`nameIn`,`transactModeTemp`,`moneyIn`,`txt2`,`tIn`);
        select LAST_INSERT_ID() into `loanID2`;
        if (`loanID2` = 0) then
            rollback;
            leave label;
        end if;
    else
        if (`outTransactMode`>0 && `inTransactMode`>0) then
            set `moneyIn`=abs(`moneyIn`);
            set `transactModeTemp`=`inTransactMode`;
            insert into `transactions`(`transactMode`,`money`,`txt`,`t`) values (`outTransactMode`,-`moneyIn`,if (`outTransactMode`=1,`txtIn`,"提现"),`tIn`);
            select LAST_INSERT_ID() into `transactionsID1`;
            if (`transactionsID1` = 0) then
                rollback;
                leave label;
            end if;
            set `txtIn`=if (`inTransactMode`=1,"提现",`txtIn`);
        else
            update `statements` set `closed` = `moneyIn`+`closedOut` where `t` = `today`;
            if (`moneyIn`<0) then
                if (`moneyIn`+`closedOut`<`lowOut`) then
                    update `statements` set `low` = `moneyIn`+`closedOut` where `t` = `today`;
                end if;
                if (`txtIn`="") then
                    set `txtIn`="支出";
                end if;
            elseif (`moneyIn`>0) then
                if (`moneyIn`+`closedOut`>`highOut`) then
                    update `statements` set `high` = `moneyIn`+`closedOut` where `t` = `today`;
                end if;
                if (`txtIn`="") then
                    set `txtIn`="收入";
                end if;
            end if;
        end if;
    end if;

    insert into `transactions`(`transactMode`,`money`,`txt`,`t`) values (`transactModeTemp`,`moneyIn`,`txtIn`,`tIn`);
    select LAST_INSERT_ID() into `transactionsID2`;
    if (`transactionsID2` = 0) then
        rollback;
        leave label;
    end if;

    commit;
end$$

DELIMITER ;

-- --------------------------------------------------------

--
-- 表的结构 `loan`
--

CREATE TABLE IF NOT EXISTS `loan` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `transactMode` int(10) unsigned NOT NULL,
  `money` decimal(12,2) NOT NULL,
  `txt` text NOT NULL,
  `t` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `statements`
--

CREATE TABLE IF NOT EXISTS `statements` (
  `id` int(10) unsigned NOT NULL,
  `t` int(10) unsigned NOT NULL,
  `low` decimal(12,2) NOT NULL,
  `high` decimal(12,2) NOT NULL,
  `closed` decimal(12,2) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(10) unsigned NOT NULL,
  `transactMode` int(10) unsigned NOT NULL,
  `money` decimal(12,2) NOT NULL,
  `txt` text NOT NULL,
  `t` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `transactMode`
--

CREATE TABLE IF NOT EXISTS `transactMode` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(30) NOT NULL,
  `topUp` tinyint(1) NOT NULL,
  `withdrawal` tinyint(1) NOT NULL,
  `sk` tinyint(1) NOT NULL,
  `pay` tinyint(1) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `transactMode`
--

INSERT INTO `transactMode` (`id`, `name`, `topUp`, `withdrawal`, `sk`, `pay`) VALUES
(1, '现金', 1, 1, 1, 1),
(2, '支付宝', 1, 1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `statements`
--
ALTER TABLE `statements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `t` (`t`),
  ADD KEY `t_2` (`t`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `t` (`t`),
  ADD KEY `transactMode` (`transactMode`);

--
-- Indexes for table `transactMode`
--
ALTER TABLE `transactMode`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `statements`
--
ALTER TABLE `statements`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `transactMode`
--
ALTER TABLE `transactMode`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;