<?php
namespace ieras\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Process;

class Schedule extends Command
{

    protected function configure()
    {
        $this->setName('cron:schedule')
            ->addOption('daemon', null, Option::VALUE_NONE, 'Run the worker in daemon mode')
            ->setDescription('Daemon Running crontab tasks');
    }

    protected function execute(Input $input, Output $output)
    {
        if ('\\' == DIRECTORY_SEPARATOR) {
            $command = 'start /B "' . PHP_BINARY . '" think cron:run';
        } else {
            if($input->getOption('daemon')){
                $command = 'nohup "' . PHP_BINARY . '" think cron:run --origin >> /dev/null 2>&1 &';
            }else{
                $command = 'nohup "' . PHP_BINARY . '" think cron:run --origin';
            }
        }
        $process = new Process($command);
        while (true) {
            $this->output->writeln("<info>❤️💗 ".date('Y-m-d H:i:s')." Task Loops Starting~</info>");
            $process->run();
            $this->output->writeln("{$process->getOutput()}");//输出任务内的打印信息
            $this->output->writeln("<comment>❤️💗 ".date('Y-m-d H:i:s')." Task Loops Finishing~</comment>");
            $next_minute = strtotime(date('Y-m-d H:i:00')) + 60;//下一分钟的时间戳
            sleep($next_minute-time());
        }
    }
}