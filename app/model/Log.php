<?php
/**
 * User: Roman
 * Date: 03.02.2016
 * Time: 15:34
 */
namespace App\Model;
use Nette\Object;
use Nette\Utils\DateTime;

class Log extends Object {

    /** LOG TYPES */
    const TYPE_ERROR = 'ERROR';
    const TYPE_INFO = 'INFO';
    const TYPE_WARNING = 'WARNING';

    /** @var int $id */
    private $id;
    /** @var DateTime $created */
    private $created;
    /** @var string $ip */
    private $ip;
    /** @var string $url */
    private $url;
    /** @var string $type */
    private $type;
    /** @var string $operation */
    private $operation;
    /** @var string $message */
    private $message;

    /**
     * @param string   $type
     * @param string   $operation
     * @param string   $message
     */
    public function __construct($type, $operation, $message)
    {
        $this->created = new DateTime();
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $this->type = $type;
        $this->operation = $operation;
        $this->message = $message;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function toArray() {
        return array(
            'created' => $this->created,
            'ip' => $this->ip,
            'url' => $this->url,
            'type' => $this->type,
            'operation' => $this->operation,
            'message' => $this->message
        );
    }

}