<?php
namespace ieras\cron;

use Closure;
use Cron\CronExpression;
use Carbon\Carbon;
use think\Cache;
use think\console\Input;
use think\console\Output;

abstract class Task
{

    use ManagesFrequencies;

    /** @var \DateTimeZone|string 时区 */
    public $timezone;

    /** @var string 任务周期 */
    public $expression = '* * * * * *';

    /** @var bool 任务是否可以重叠执行 */
    public $withoutOverlapping = false;

    /** @var int 最大执行时间(重叠执行检查用) */
    public $expiresAt = 1440;

    /** @var bool 分布式部署 是否仅在一台服务器上运行 */
    public $onOneServer = false;

    protected $filters = [];
    protected $rejects = [];

    /** @var  Input */
    protected $input;

    /** @var  Output */
    protected $output;

    public function __construct(Input $input,Output $output)
    {
        $this->configure();
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * 是否到期执行
     * @return bool
     */
    public function isDue()
    {
        $date = Carbon::now($this->timezone);

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * 配置任务
     */
    protected function configure()
    {
    }

    /**
     * 执行任务
     * @return mixed
     */
    abstract protected function execute();

    final public function run()
    {
        if ($this->withoutOverlapping &&
            !$this->createMutex()) {
            return;
        }

        register_shutdown_function(function () {
            $this->removeMutex();
        });

        try {
            $this->execute();
        } finally {
            $this->removeMutex();
        }
    }

    /**
     * 过滤
     * @return bool
     */
    public function filtersPass()
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 任务标识
     */
    public function mutexName()
    {
        return 'task-' . sha1(static::class);
    }

    protected function removeMutex()
    {
        return Cache::rm($this->mutexName());
    }

    protected function createMutex()
    {
        $name = $this->mutexName();
        if (!Cache::has($name)) {
            Cache::set($name, true, $this->expiresAt);
            return true;
        }
        return false;
    }

    protected function existsMutex()
    {
        return Cache::has($this->mutexName());
    }

    public function when(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->existsMutex();
        });
    }

    public function onOneServer()
    {
        $this->onOneServer = true;

        return $this;
    }

    /**
     * 把信息美化输出到屏幕上
     * 支持的格式
     * <info>信息</info>
     * <error>信息</error>
     * <comment>信息</comment>
     * <question>信息</question>
     * <highlight>信息</highlight>
     * <warning>信息</warning>
     * @param $info
     * @param $newline
     * @return void
     */
    public function echoInfo($info,$newline=false){
        if($this->input->getOption('origin')){
            if($newline){
                echo $info.PHP_EOL;
            }else{
                echo $info;
            }
        }else{
            $this->output->write($info,$newline);
        }
    }
}