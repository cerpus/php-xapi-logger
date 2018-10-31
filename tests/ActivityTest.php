<?php

use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function testYouCanCreateAnActivity()
    {
        $activity = new \Cerpus\xAPI\Activity(1);

        $asActorObject = $activity->asObject();

        $this->validateActivityObject($activity->asObject());
    }

    public function validateActivityObject($asActivityObject)
    {
        $this->assertObjectHasAttribute('objectType', $asActivityObject);
        $this->assertEquals('Activity', $asActivityObject->objectType);

        $this->assertObjectHasAttribute('id', $asActivityObject);
        $this->assertEquals('1', $asActivityObject->id);

        $this->assertObjectHasAttribute('definition', $asActivityObject);
        $this->assertObjectHasAttribute('type', $asActivityObject->definition);
        $this->assertEquals('https://w3id.org/xapi/avt/activity-types/area-within-the-map', $asActivityObject->definition->type);
    }

}
