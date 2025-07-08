<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;
use Inspector\OS;

class Host extends Model
{
    public string $hostname;
    public string $ip;
    public string $os;
    public ?string $cpu = null;
    public ?string $ram = null;
    public ?string $hdd = null;

    /**
     * Host constructor.
     */
    public function __construct()
    {
        $this->hostname = \gethostname();
        $this->ip = \gethostbyname(\gethostname());
        $this->os = \PHP_OS_FAMILY;
    }

    /**
     * Collect server status information.
     *
     * @deprecated It's not used anymore, but it's interesting to take this script in mind for future use cases.
     */
    public function withServerStatus(): Host
    {
        if (OS::isLinux() && \function_exists('shell_exec')) {
            try {
                $status = \shell_exec('echo "`LC_ALL=C top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'`%;`free -m | awk \'/Mem:/ { printf("%3.1f%%", $3/$2*100) }\'`;`df -h / | awk \'/\// {print $(NF-1)}\'`"');
                $status = \str_replace('%', '', $status);
                $status = \str_replace("\n", '', $status);

                $status = \explode(';', $status);

                $this->cpu = $status[0];
                $this->ram = $status[1];
                $this->hdd = $status[2];
            } catch (\Throwable $exception) {
                // do nothing
            }
        }

        return $this;
    }
}
