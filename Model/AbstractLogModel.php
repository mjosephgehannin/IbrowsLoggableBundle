<?php
namespace Ibrows\LoggableBundle\Model;

/**
 * AbstractLog
 */
abstract class AbstractLogModel
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $action
     */
    protected $action;

    /**
     * @var string $loggedAt
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     */
    protected $objectId;

    /**
     * @var string $objectClass
     */
    protected $objectClass;

    /**
     * @var integer $version
     */
    protected $version;

    /**
     * @var array $data
     */
    protected $data;

    /**
     * @var string $data
     */
    protected $username;

    /**
     * @var array $olddata
     *
     */
    protected $oldData;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get object class
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set object id
     *
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get loggedAt
     *
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * Set loggedAt to "now"
     */
    public function setLoggedAt()
    {
        $this->loggedAt = new \DateTime();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get current version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set current version
     *
     * @param integer $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return array
     */
    public function getComparedData()
    {
        $newData = $this->getData();
        $oldData = $this->getOldData();

        $comparedData = array();

        foreach ($newData as $key => $data) {
            $comparedData[$key] = array(
                'new' => static::normalizeData($data),
                'old' => isset($oldData[$key]) ? self::normalizeData($oldData[$key]) : null
            );
        }

        return $comparedData;
    }

    /**
     * @return array
     */
    public function getOldData()
    {
        return $this->oldData;
    }

    /**
     * @param array $oldData
     * @return $this
     */
    public function setOldData(array $oldData)
    {
        $this->oldData = $oldData;

        return $this;
    }


    /**
     * @param $value
     * @return array|mixed|string
     */
    protected static function normalizeData($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format("d M Y");
        }

        if (is_array($value)) {
            if (sizeof($value) == 1) {
                return reset($value);
            } else {
                return print_r($value, true);
            }
        }

        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param mixed $oldvalue
     */
    public function addData($key, $value, $oldvalue)
    {
        $this->data[$key] = $value;
        $this->oldData[$key] = $oldvalue;
    }

    /**
     * @return string
     */
    public function getType()
    {
        $pos = strrpos($this->objectClass, '\\');
        ++$pos;
        $type = substr($this->objectClass, $pos);

        return $type;
    }

}