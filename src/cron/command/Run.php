<?php
namespace ieras\cron\command;

use think\Config;
use think\console\Command;
use think\console\Input;
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
        $this->setName('cron:run');
    }

    public function execute(Input $input, Output $output)
    {

        $tasks = Config::get('cron.tasks');

        foreach ($tasks as $taskClass) {

            if (is_subclass_of($taskClass, Task::class)) {

                /** @var Task $task */
                $task = new $taskClass();
                if ($task->isDue()) {

                    if (!$task->filtersPass()) {
                        continue;
                    }

                    if ($task->onOneServer) {
                        $this->runSingleServerTask($task);
                    } else {
                        $this->runTask($task);
                    }

                    $output->writeln("Task {$taskClass} run at " . Carbon::now());
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

    protected function runSingleServerTask($task)
    {
        if ($this->serverShouldRun($task)) {
            $this->runTask($task);
        } else {
            $this->output->writeln('<info>Skipping task (has already run on another server):</info> ' . get_class($task));
        }
    }

    /**
     * @param $task Task
     */
    protected function runTask($task)
    {
        echo "<warning>⏳|".date('Y-m-d H:i:s')."|Processing:{$taskClass}</warning>\r\n";
        $task->run();
        echo "<question>⌛️|".date('Y-m-d H:i:s')."|Processed:{$taskClass}</question>";
    }
}