<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

class Host extends Arrayable
{
    /**
     * Host constructor.
     */
    public function __construct()
    {
        $this->hostname = gethostname();
        $this->ip = gethostbyname(gethostname());

        if (PHP_OS_FAMILY === 'Linux') {
            $this->memory_usage = $this->getHostMemoryUsage();
            $this->disk_usage = $this->getHostDiskUsage();
            $this->cpu_usage = $this->getHostCpuUsage();
        }
    }

    /**
     * Sample host memory usage (%).
     *
     * @return false|float|int
     */
    public function getHostMemoryUsage()
    {
        try {
            $free = shell_exec('free');

            if($free === null) {
                return 0;
            }

            $free_arr = explode("\n", (string)trim($free));
            $mem = explode(" ", $free_arr[1]);
            $mem = array_merge(array_filter($mem));
            // used - buffers - cached
            return round((($mem[2]-$mem[5]-$mem[6]) / $mem[1]) * 100, 2);

        } catch (\Throwable $exception) {
            return 0;
        }
    }

    /**
     * Sample host disk usage (%).
     *
     * @return false|float
     */
    public function getHostDiskUsage()
    {
        return @is_readable('/') 
            ? round(100 - ((disk_free_space('/') / disk_total_space('/')) * 100), 2) 
            : false;
    }

    /**
     * Sample host cpu usage (%).
     *
     * @return false|float|int
     */
    public function getHostCpuUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg()[0];
            $proc = exec('nproc');
            return is_numeric($load) && is_numeric($proc)
                ? round($load * 100 / $proc, 2)
                : 0;
        }

        return 0;
    }
}
