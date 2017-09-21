<?php

class Shopware_Plugins_Frontend_Boxalino_Benchmark {

    protected $logger;

    protected $time = null;

    protected $stop_time = null;

    protected $logs;

    protected $context;

    protected static $instance;

    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Benchmark();
        return self::$instance;
    }

    public function __construct() {
        $this->logger = Shopware()->PluginLogger();
    }

    public function startRecording($context) {
        $this->context = $context;
        $this->time = microtime(true);
        $this->log("Start of {$this->context}");
    }

    public function log($message) {
        if ($this->stop_time == null){
            $this->stop_time = $this->time;
        }
        $duration = (microtime(true) - $this->stop_time) * 1000;

        $this->logs[] = "BxBenchmark: [{$this->context}][+{$duration} ms] {$message}";
    }

    public function endRecording() {
        $this->log("End of {$this->context}");
        foreach ($this->logs as $log) {
            $this->logger->info($log);
        }
    }

}