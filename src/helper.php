<?php
use ieras\cron\command\Run;
use ieras\cron\command\Schedule;
use ieras\cron\command\MySql;

\think\Console::addDefaultCommands([
    Run::class,
    Schedule::class,
    MySql::class,
]);
if (!function_exists('add_cron')) {

    /**
     * 添加到计划任务
     * @param string $title
     * @param string $task
     * @param array $data
     * @param string $exptime
     * @return bool
     */
    function add_cron($title, $task, $data = [], $exptime=null)
    {
        return (new MySql)->add_cron($title, $task, $data, $exptime);
    }
}