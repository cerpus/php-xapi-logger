<?php

use PHPUnit\Framework\TestCase;


class StatementTest extends TestCase
{
    public $validXApiStatementString = '{"actor":{"name":"12","mbox":"mailto:false","objectType":"Agent"},"verb":{"display":{"en-US":"interacted"},"id":"http://adlnet.gov/expapi/verbs/interacted"},"context":{"contextActivities":{"parent":[{"id":"http://contentauthor.local/h5p/2057809762","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.MultiChoice-1.9","objectType":"Activity"}]},"extensions":{"http://id.tincanapi.com/extension/ending-point":1}},"object":{"definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":2057809762,"http://h5p.org/x-api/h5p-subContentId":"c2594f49-40f1-4aea-a889-811b3be7427c"},"name":{"en-US":"Q1 Edited\n"}},"id":"http://contentauthor.local/h5p/2057809762?subContentId=c2594f49-40f1-4aea-a889-811b3be7427c","objectType":"Activity"}}';

    public $validXApiStatement;
    public $xApiStatementNoVerb;
    public $xApiStatementNoActor;
    public $xApiStatementNoObject;

    public function setUp()
    {
        parent::setUp();

        $this->validXApiStatement = json_decode($this->validXApiStatementString);

        $this->xApiStatementNoActor = clone $this->validXApiStatement;
        unset($this->xApiStatementNoActor->actor);

        $this->xApiStatementNoVerb = clone $this->validXApiStatement;
        unset($this->xApiStatementNoVerb->verb);

        $this->xApiStatementNoObject = clone $this->validXApiStatement;
        unset($this->xApiStatementNoObject->object);
    }

    public function testYouCanCreateANewStatementObject()
    {
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);
        $this->assertNotNull($statement->getStatement());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testActorIsRequired()
    {
        $statement = new \Cerpus\xAPI\Statement($this->xApiStatementNoActor);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testVerbIsRequired()
    {
        $statement = new \Cerpus\xAPI\Statement($this->xApiStatementNoVerb);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testObjectIsRequired()
    {
        $statement = new \Cerpus\xAPI\Statement($this->xApiStatementNoObject);
    }

    public function testYouCanGetTheActorObject()
    {
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);

        $actor = $statement->getActor();

        $this->assertEquals(12, $actor->name);
    }

    public function testYouCanSetTheActorObject()
    {
        $newUserId = 42;

        $actor = new \Cerpus\xAPI\Actor($newUserId);
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);
        $statement->setActor($actor);
        $stmtActor = $statement->getActor();

        $this->assertEquals($newUserId, $stmtActor->name);
    }

    public function testYouCanGetAJsonStringVersionOfTheStatement()
    {
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);

        $this->assertEquals($this->validXApiStatement, json_decode($statement->asJsonString()));
    }

    public function testYouCanGetTheObject()
    {
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);
        $expectedId = 'http://contentauthor.local/h5p/2057809762?subContentId=c2594f49-40f1-4aea-a889-811b3be7427c';

        $object = $statement->getObject();

        $this->assertEquals($expectedId, $object->id);
    }

    public function testYouCanMergeIntoContextActivityGrouping()
    {
        $statement = new \Cerpus\xAPI\Statement($this->validXApiStatement);

        $activities = [
            new \Cerpus\xAPI\Activity(1),
            new \Cerpus\xAPI\Activity(2),
        ];

        $this->assertObjectNotHasAttribute('grouping', $statement->getStatement()->context->contextActivities);
        $statement->mergeContextActivities($activities);
        $this->assertObjectHasAttribute('grouping', $statement->getStatement()->context->contextActivities);
        $this->assertCount(2, $statement->getStatement()->context->contextActivities->grouping);

        $activities2 = [
            new \Cerpus\xAPI\Activity(3),
            new \Cerpus\xAPI\Activity(4),
        ];
        $statement->mergeContextActivities($activities2);
        $this->assertCount(4, $statement->getStatement()->context->contextActivities->grouping);

        $activities3 = [
            new \Cerpus\xAPI\Activity(4), // Should not be merged
            new \Cerpus\xAPI\Activity(5),
            new \Cerpus\xAPI\Activity(3), // Should not be merged
        ];
        $statement->mergeContextActivities($activities3);
        $this->assertCount(5, $statement->getStatement()->context->contextActivities->grouping);
    }
}
