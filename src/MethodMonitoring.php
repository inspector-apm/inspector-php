<?php


namespace Inspector;


trait MethodMonitoring
{
    /**
     * Monitor a method execution.
     *
     * @param string $class
     * @param string $method
     * @param array $arguments
     * @return mixed|void
     * @throws \Throwable
     */
    public function call($class, $method, array $arguments = [])
    {
        if (!$this->isRecording()) {
            return call_user_func([$class, $method], $arguments);
        }

        return $this->addSegment(
            function ($segment) use ($class, $method, $arguments) {
                $segment->addContext('Arguments', $arguments);

                return call_user_func_array([$class, $method], $arguments);
            },
            'method',
            $class.'@'.$method,
            true
        );
    }
    /**
     * Monitor a method execution.
     *
     * @param string $class
     * @param string $method
     * @param array $arguments
     * @return mixed|void
     * @throws \Throwable
     */
    public function callStatic($class, $method, array $arguments = [])
    {
        if (!$this->isRecording()) {
            return call_user_func_array($class.'::'.$method, $arguments);
        }

        return $this->addSegment(
            function ($segment) use ($class, $method, $arguments) {
                $segment->addContext('Arguments', $arguments);

                return call_user_func_array($class.'::'.$method, $arguments);
            },
            'method',
            $class.'::'.$method,
            true
        );
    }
}

