<?php
namespace ieras\cron\command;

use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use ieras\cron\Task;
use Carbon\Carbon;

class Run extends Command
{
    /** @var Carbon */
    protected $startedAt;

    protected function configure()
    {
        $this->startedAt = Carbon::now();
        $this->setName('cron:run')
            ->addOption('origin', null, Option::VALUE_NONE, 'origin info output')
            ->setDescription('Running crontab tasks');
    }

    public function execute(Input $input, Output $output)
    {
        $tasks = Config::get('cron.tasks');
        foreach ($tasks as $taskClass) {
            if (is_subclass_of($taskClass, Task::class)) {
                /** @var Task $task */
                $task = new $taskClass($input,$output);
                if ($task->isDue()) {
                    if (!$task->filtersPass())
                        continue;
                    if ($task->onOneServer) {
                        $this->runSingleServerTask($task,$taskClass);
                    } else {
                        $this->runTask($task,$taskClass);
                    }
                    //$output->writeln("Task {$taskClass} run at " . Carbon::now());
                }
            }
        }
    }

    /**
     * @param $task Task
     * @return bool
     */
    protected function serverShouldRun($task)
    {
        $key = $task->mutexName() . $this->startedAt->format('Hi');
        if (cache($key)) {
            return false;
        }
        cache($key, true, 60);
        return true;
    }

    protected function runSingleServerTask($task,$taskClass)
    {
        if ($this->serverShouldRun($task)) {
            $this->runTask($task,$taskClass);
        } else {
            $this->output->writeln('<info>Skipping task (has already run on another server):</info> ' . get_class($task));
        }
    }

    /**
     * @param $task Task
     */
    protected function runTask($task,$taskClass)
    {
        $task->echoInfo("<warning>⏳|".date('Y-m-d H:i:s')."|Processing:{$taskClass}</warning>",true);
        $task->run();
        $task->echoInfo("<question>⌛️|".date('Y-m-d H:i:s')."|Processed:{$taskClass}</question>");
    }

}