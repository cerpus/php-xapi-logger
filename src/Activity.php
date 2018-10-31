<?php

namespace Cerpus\xAPI;


class Activity
{
    /** @var string */
    private $objectType = 'Activity';
    /** @var string */
    protected $id;
    /** @var string */
    protected $type = 'https://w3id.org/xapi/avt/activity-types/area-within-the-map';

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function asObject()
    {
        $activity = new \stdClass();
        $activity->objectType = $this->getObjectType();
        $activity->id = $this->getId();
        $activity->definition = new \stdClass();
        $activity->definition->type = $this->getType();

        return $activity;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     */
    public function setObjectType(string $objectType)
    {
        $this->objectType = $objectType;
    }


}
