<?php

namespace Cerpus\xAPI;


class Actor
{
    protected $name;

    protected $homePage = 'https://learning-id.com';
    protected $objectType = 'Agent';

    /**
     * Actor constructor.
     * @param $id - auth_id or local user id, you need to give anonymous users an identifier;
     */
    public function __construct($id)
    {
        if (!$id) {
            throw new \InvalidArgumentException('id is required!');
        }

        $this->name = $id;
    }

    /**
     * GDPR safe actor
     */
    public function asObject()
    {
        return (object)[
            'objectType' => 'Agent',
            'name' => (string)$this->getName(),
            'account' => (object)[
                'name' => (string)$this->getName(),
                'homePage' => $this->getHomePage(),
            ]
        ];
    }

    public function setHomePage($homePage)
    {
        $this->homePage = $homePage;
    }

    public function getHomePage()
    {
        return $this->homePage;
    }

    public function getName()
    {
        return $this->name;
    }
}
