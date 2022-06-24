<?php
use ieras\cron\command\Run;
use ieras\cron\command\Schedule;
use think\Console;

Console::addDefaultCommands([
    Run::class,
    Schedule::class
]);