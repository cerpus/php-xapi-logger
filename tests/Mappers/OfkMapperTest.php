<?php

use PHPUnit\Framework\TestCase;
use Cerpus\xAPI\Mappers\OfkMapper;

class OfkMapperTest extends TestCase
{
    public function testYouCanCreateAnOfkMapper()
    {
        $tags = ['tag1', 'tag2'];

        $mapper = new OfkMapper($tags);

        $this->assertTrue($mapper instanceof OfkMapper);
    }

    public function testMapFunctionReturnsAnArray()
    {
        $tags = ['tag1', 'tag2'];

        $mapper = new OfkMapper($tags);

        $this->assertTrue(is_array($mapper->map()));
    }

    public function testFilterOfkTags()
    {
        $tags = [
            'ofk10000 ',
            'OFK10000', // This should be merged
            '#OFK10000', // This should be merged
            '##OFK10000', // This should be merged
            'OFK10001',
            '#ofk10002',
            '#OFK10003',
            '##ofk10004',
            '##OFK10005',
            '###ofk10006',
            '###OFK10007',
            ' ofk10008',
            ' #ofk10008', // This should be merged
            ' ##ofk10008', // This should be merged
            ' #################ofk10008', // This should be merged
            // Below should not match on these 'ofk' like tags
            'tag1',
            'aofk10000',
            'a##ofk10544',
            '# #ofk10544',
        ];

        $mapper = new OfkMapper([]);
        $result = $mapper->filterOfkTags($tags);

        $this->assertCount(9, $result);

        $this->assertTrue(in_array('OFK10001', $result));
        $this->assertTrue(in_array('OFK10002', $result));
        $this->assertTrue(in_array('OFK10003', $result));
        $this->assertTrue(in_array('OFK10004', $result));
        $this->assertTrue(in_array('OFK10005', $result));
        $this->assertTrue(in_array('OFK10006', $result));
        $this->assertTrue(in_array('OFK10007', $result));
        $this->assertTrue(in_array('OFK10008', $result));
    }
}
