<?php namespace App\Api;

/**
 * Class Response
 * Standard class for returning API responses in a uniform format
 */
class Response
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
     * @return Response
     */
    public function setSuccess(bool $success): Response
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSimulation($format = 'array'): mixed
    {
        switch ($format) {
            case 'array':
                return $this->simulation;
            case 'json':
                return json_encode($this->simulation);
            default:
                return 'Unsupported format';
        }
    }

    /**
     * @param array $simulation
     * @return Response
     */
    public function setSimulation(array $simulation): Response
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
     * @return Response
     */
    public function setLogs(array $log): Response
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
     * @return Response
     */
    public function setAudit(string $audit): Response
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
     * @return Response
     */
    public function setPayload(array $payload): Response
    {
        $this->payload = $payload;
        return $this;
    }

}
