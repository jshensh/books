账簿
====

首先说明，作者不是做财务的，部分概念可能会有错误，请各位指出，谢谢。
手机上用得比较多，懒得写 UI，各位好心人可以帮忙加上。。。

作者博客 [http://233.imjs.work/](http://233.imjs.work/)

### 安装

1. 修改 ``/config/system.php``
2. 修改 ``/config/database.php`` 中的数据库连接信息

### 旧版本升级

1. 数据库中执行

```sql
rename table `transactMode` to `transactmode`;
ALTER TABLE `transactmode` CHANGE `sortId` `sortid` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `transactmode` CHANGE `topUp` `topup` TINYINT(1) NOT NULL;
ALTER TABLE `transactions` ADD `amount` DECIMAL(12,2) NOT NULL DEFAULT '0' AFTER `txt`;
ALTER TABLE `transactions` CHANGE `transactMode` `transactmode_id` INT(10) UNSIGNED NOT NULL;
ALTER TABLE `loan` CHANGE `transactMode` `transactmode_id` INT(10) UNSIGNED NOT NULL;
ALTER TABLE `transactmode` ADD `is_shown` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `sortid`;
```

2. bash 下执行

```shell
php think UpdateAmount
```

### 添加交易方式

需要在数据库手动添加
![screenshot2](http://233.imjs.work/uploads/2016/07/QQ截图20160711012709.jpg)

### 截图

![screenshot1](http://233.imjs.work/uploads/2016/07/20160711012241.png)