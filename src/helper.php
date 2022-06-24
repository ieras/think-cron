<?php
use ieras\cron\command\Run;
use ieras\cron\command\Schedule;

\think\Console::addDefaultCommands([
    Run::class,
    Schedule::class
]);