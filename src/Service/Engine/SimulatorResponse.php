<?php namespace App\Service\Engine;

/**
 * Class SimulatorResponse
 * Standard class for returning API responses in a uniform format
 */
class SimulatorResponse
{
    /** @var bool */
    private bool $success;

    /** @var array */
    private array $simulation;

    /** @var array */
    private array $log;

    /** @var string */
    private string $audit;

    /** @var array */
    private array $payload;

    public function __construct(bool $success = false, array $simulation = [], array $logs = [], string $audit = '')
    {
        $this->success = $success;
        $this->setSimulation($simulation);
        $this->setLogs($logs);
        $this->setAudit($audit);
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return SimulatorResponse
     */
    public function setSuccess(bool $success): SimulatorResponse
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return array
     */
    public function getSimulation(): array
    {
        return $this->simulation;
    }

    /**
     * @param array $simulation
     * @return SimulatorResponse
     */
    public function setSimulation(array $simulation): SimulatorResponse
    {
        $this->simulation = $simulation;
        return $this;
    }

    /**
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * @param array $log
     * @return SimulatorResponse
     */
    public function setLogs(array $log): SimulatorResponse
    {
        $this->log = $log;
        return $this;
    }

    /**
     * @return array[]
     */
    public function serialize(): array
    {
        return [
            'simulation' => $this->getSimulation(),
            'logs' => $this->getLog()
        ];
    }

    /**
     * @return string
     */
    public function getAudit(): string
    {
        return $this->audit;
    }

    /**
     * @param string $audit
     * @return SimulatorResponse
     */
    public function setAudit(string $audit): SimulatorResponse
    {
        $this->audit = $audit;
        return $this;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     * @return SimulatorResponse
     */
    public function setPayload(array $payload): SimulatorResponse
    {
        $this->payload = $payload;
        return $this;
    }

}
