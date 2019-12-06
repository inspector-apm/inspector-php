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

        $this->cpu_usage = $this->getHostCpuUsage();
        $this->memory_usage = $this->getHostMemoryUsage();
        $this->disk_usage = $this->getHostDiskUsage();
    }

    /**
     * Sample host memory usage (%).
     *
     * @return false|float|int
     */
    public function getHostMemoryUsage()
    {
        $free = shell_exec('free');

        if($free === null) {
            return 0;
        }

        $free_arr = explode("\n", (string)trim($free));
        $mem = explode(" ", $free_arr[1]);
        $mem = array_merge(array_filter($mem));
        // used - buffers - cached
        return round((($mem[2]-$mem[5]-$mem[6]) / $mem[1]) * 100, 2);
    }

    /**
     * Sample host disk usage (%).
     *
     * @return false|float
     */
    public function getHostDiskUsage()
    {
        return round(100 - ((disk_free_space('/') / disk_total_space('/')) * 100), 2);
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
            return round($load * 100 / $proc, 2);
        }

        return 0;
    }
}
