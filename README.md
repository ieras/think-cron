# think-cron 计划任务
## 项目目标：
thinkphp 官方居然没有出一个跟laravel一样的程序内维护的计划任务功能，依然使用Linux的crontab会很繁琐，所以thinkphp版的计划任务还是很多人的痛点的！本项目实现的便是程序内、但易入口的计划任务。支持配置文件(file)、mysql(需要自己实现往库里插任务,不太推荐，不过有些特殊场景还是不错的，例如计划任务是非即使人员进行添加的那种，比如各种模式的报表任务)的方式处理任务。

## 安装方法
```
composer require ieras/think-cron
```

## 使用方法

### 创建任务类

```
<?php

namespace app\task;

use ieras\cron\Task;

class DemoTask extends Task
{

    public function configure()
    {
        $this->exptime = "*/1 * * * *"; 
        //设置任务的周期，每分钟执行一次，使用标准的Crontab语法,当配置文件中设置了执行周期将优先配置文件中的设置
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        //...具体的任务执行
        $time = date('Y-m-d H:i:s');
        $this->statusDesc = $time;
        $data = $this->payload;//定义任务时配置的参数数组 data
        return true;
    }
}

```

### 配置
配置文件位于 application/extra/cron.php


```
return [
    'type'  =>    'file',       //支持 file,mysql驱动
    'table' =>    'think_cron', //驱动类型为mysql时存储任务所用的表，修改时候记得一起修改实际表名
    'tasks'   => [             //file 模式 定时任务列表
        'demo'  =>[
            'title'     =>  '测试',
            'task'      =>  \app\task\DemoTask::class,
            'data'      =>  [],
            'exptime'       =>  '* * * * *'
        ]
    ]
];
```
使用mysql存储定时任务时，请将 type 设置为 mysql，然后控制台执行 php think cron:install 初始化数据库，或者手动创建如下数据表
```
CREATE TABLE `think_cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '任务状态',
  `count` int(11) NOT NULL DEFAULT '0' COMMENT '执行次数',
  `title` char(50) DEFAULT NULL COMMENT '任务名称',
  `exptime` char(200) NOT NULL DEFAULT '* * * * *' COMMENT '任务周期',
  `task` varchar(500) DEFAULT NULL COMMENT '任务命令',
  `data` longtext COMMENT '附加参数',
  `status_desc` varchar(1000) DEFAULT NULL COMMENT '上次执行结果',
  `last_time` datetime DEFAULT NULL COMMENT '最后执行时间',
  `next_time` datetime DEFAULT NULL COMMENT '下次执行时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
### 创建计划任务(Mysql模式时需要、file模式只需在配置文件里定义即可)
```
/**
 * 创建计划任务(Mysql模式时需要)
 * @param string $title 任务名
 * @param string $task  类名
 * @param array $data   参数 数组格式
 * @param string $exptime 任务执行周期,使用标准的Crontab语法,默认60秒
 * @return bool
 */
 add_cron($title, $task, $data = [], $exptime=null);
```
### 创建计划任务例子
```
$data = ['name' => 'thinkphp'];
add_cron('test', \app\task\DemoTask::class, $data, '*/5 * * * *');
```

### 任务监听

#### 两种方法：

> 方法一 

##### 起一个常驻进程，或者配合supervisor使用 (推荐)
~~~
php think cron:schedule start --daemon
~~~

##### 创建 supervisor 
```
[program:php]
command= /usr/bin/php think cron:schedule start; 被监控进程
directory=/home/wwwroot/shabi.in
process_name=%(program_name)s
numprocs=1 ;启动几个进程 别改 扩展限制了一个进程运行
autostart=true ;随着supervisord的启动而启动
autorestart=true ;自动启动
startsecs=1 ;程序重启时候停留在runing状态的秒数
startretries=10 ;启动失败时的最多重试次数
redirect_stderr=true ;重定向stderr到stdout
stdout_logfile=/root/supervisor.log ;stdout文件
```

> 方法二

#### 在系统的计划任务里添加
~~~
* * * * * php /path/to/think cron:run >> /dev/null 2>&1
~~~

## 特别鸣谢
- [DarkiT/think-cron](https://github.com/DarkiT/think-cron)

## 写在最后
- 代码中有很多不成熟的地方，期待您的issue。最好能fork，将您的想法贡献出来。让这个项目更适应更多的场景。
- 邮箱：ieras@qq.com
