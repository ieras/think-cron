# think-cron 计划任务

## 安装方法
```
composer require ieras/think-cron
# 如果上面的命令按照不上执行下面的
composer require ieras/think-cron --with-all-dependencies
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
        //$this->everyMinute();//每分钟
        $this->daily(); //设置任务的周期，每天执行一次，更多的方法可以查看源代码，都有注释
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        //...具体的任务执行
        $time = date('Y-m-d H:i:s');
        $this->echoInfo("<info>{$time}</info>\n",true);//美化输出信息
        return true;
    }
}

```

### 配置
> 配置文件位于 application/extra/cron.php

```
return [
    'tasks' => [
        \app\task\DemoTask::class, //任务的完整类名
    ]
];
```

### 任务监听

#### 两种方法：

> 方法一 (推荐)

起一个常驻进程，可以配合supervisor使用
~~~
php think cron:schedule
~~~

> 方法二

在系统的计划任务里添加
~~~
* * * * * php /path/to/think cron:run >> /dev/null 2>&1
~~~
### 鸣谢
- [yunwuxin/think-cron](https://packagist.org/packages/yunwuxin/think-cron/)
