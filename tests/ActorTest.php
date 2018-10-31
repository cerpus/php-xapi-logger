<?php

use PHPUnit\Framework\TestCase;

class ActorTest extends TestCase
{
    public function testYouCanCreateAnActor()
    {
        $actor = new \Cerpus\xAPI\Actor(1);

        $asActorObject = $actor->asObject();

        $this->validateActorObject($asActorObject, 1);
    }

    public function testYouCanCreateAnActor2()
    {
        $guid = "0837aa1e-b8a1-4790-a7a4-5723822ad96c";
        $actor = new \Cerpus\xAPI\Actor($guid);

        $asActorObject = $actor->asObject();

        $this->validateActorObject($asActorObject, $guid);
    }

    public function testYourCanSetHomePage()
    {
        $homePage = 'http://another-place.com';
        $actor = new \Cerpus\xAPI\Actor(1);
        $actor->setHomePage($homePage);

        $actorAsObject = $actor->asObject();

        $this->validateActorObject($actorAsObject, 1, $homePage);
    }

    /**
     * @param $asActorObject
     * @param $id
     * @param string $homePage
     */
    protected function validateActorObject($asActorObject, $id, $homePage = 'https://learning-id.com')
    {
        $this->assertObjectHasAttribute('objectType', $asActorObject);
        $this->assertEquals('Agent', $asActorObject->objectType);

        $this->assertObjectHasAttribute('name', $asActorObject);
        $this->assertEquals($id, $asActorObject->name);
        $this->assertTrue(is_string($asActorObject->name));

        $this->assertObjectHasAttribute('account', $asActorObject);
        $this->assertObjectHasAttribute('name', $asActorObject->account);
        $this->assertEquals((string)$id, $asActorObject->account->name);

        $this->assertObjectHasAttribute('homePage', $asActorObject->account);
        $this->assertEquals($homePage, $asActorObject->account->homePage);
    }
}
