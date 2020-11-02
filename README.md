账簿
====

首先说明，作者不是做财务的，部分概念可能会有错误，请各位指出，谢谢。
手机上用得比较多，懒得写 UI，各位好心人可以帮忙加上。。。

作者博客 [http://233.imjs.work/](http://233.imjs.work/)

### 安装

1. 修改 ``/config/system.php``
2. 修改 ``/config/database.php`` 中的数据库连接信息
3. 导入 ``import.sql``
4. bash 下执行 ``composer install && chown -R www:www .``
5. 【可选】进行第一步操作时可保存 md5 等 Hash 算法的计算结果，编辑 [/application/common/service/Auth.php](https://github.com/jshensh/books/blob/master/application/common/service/Auth.php#L21)，修改为 ``if (md5($pass) === $configPass) {``，避免明文方式存储密码

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

### 使用命令行记账

```shell
# 未发生借贷关系的
php think Insert 1 0 测试 100

# 发生借贷关系的
php think Insert 1 0 测试 100 --loan="测试"
```

具体用法

```
Usage:
  Insert [options] [--] <outTransactMode> <inTransactMode> <txt> <money>

Arguments:
  outTransactMode       支出款项交易方式 ID，纯收入账目请填写 0
  inTransactMode        收入款项交易方式 ID，纯支出账目请填写 0
  txt                   交易备注
  money                 交易金额

Options:
      --loan[=LOAN]     借/贷款人姓名
```

### 添加交易方式

需要在数据库手动添加
![screenshot2](https://233.imjs.work/uploads/2020/09/QQ%E6%88%AA%E5%9B%BE20200910220448.png)

### 截图

![screenshot1](https://233.imjs.work/uploads/2016/07/20160711012241.png)

### 数据统一原则

首页总计金额 - 首页借款金额 = 图表最后一天 Close 金额