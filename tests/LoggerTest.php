<?php

use GuzzleHttp\Client;
use Cerpus\xAPI\Logger;
use Cerpus\xAPI\Statement;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Handler\MockHandler;

class LoggerTest extends TestCase
{
    public $validXApiStatementString = '{"actor":{"name":"12","mbox":"mailto:false","objectType":"Agent"},"verb":{"display":{"en-US":"interacted"},"id":"http://adlnet.gov/expapi/verbs/interacted"},"context":{"contextActivities":{"parent":[{"id":"http://contentauthor.local/h5p/2057809762","objectType":"Activity"}],"category":[{"id":"http://h5p.org/libraries/H5P.MultiChoice-1.9","objectType":"Activity"}]},"extensions":{"http://id.tincanapi.com/extension/ending-point":1}},"object":{"definition":{"extensions":{"http://h5p.org/x-api/h5p-local-content-id":2057809762,"http://h5p.org/x-api/h5p-subContentId":"c2594f49-40f1-4aea-a889-811b3be7427c"},"name":{"en-US":"Q1 Edited\n"}},"id":"http://contentauthor.local/h5p/2057809762?subContentId=c2594f49-40f1-4aea-a889-811b3be7427c","objectType":"Activity"}}';
    public $validXApiStatement;

    public function setUp()
    {
        parent::setUp();
        $this->validXApiStatement = json_decode($this->validXApiStatementString);
    }

    public function testYourCanCreateALoggerObject()
    {
        $logger = new Logger('key', 'secret');

        $this->assertTrue($logger instanceof \Cerpus\xAPI\Logger);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAnExceptionIsThrownIfKeyIsNotSet()
    {
        $logger = new Logger(null, 'secret');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAnExceptionIsThrownIfSecretIsNotSet()
    {
        $logger = new Logger('key');
    }

    public function testYouCanLogAStatement()
    {
        $statement = new Statement($this->validXApiStatement);

        $client = $this->getMockClient();

        $logger = new Logger('key', 'secret');
        $logger->setClient($client);

        $this->assertTrue($logger->log($statement));
        $this->assertFalse($logger->hasError());
        $this->assertNull($logger->getErrorCode());
        $this->assertEmpty($logger->getErrorMessage());
    }

    public function testLogStatementWithClientError()
    {
        $statement = new Statement($this->validXApiStatement);

        $client = $this->getMockClient([401]);

        $logger = new Logger('key', 'secret');
        $logger->setClient($client);

        $this->assertFalse($logger->log($statement));
        $this->assertTrue($logger->hasError());
        $this->assertEquals(401, $logger->getErrorCode());
        $this->assertNotEmpty($logger->getErrorMessage());
    }

    public function testLogStatementWithServerError()
    {
        $statement = new Statement($this->validXApiStatement);

        $client = $this->getMockClient([500]);

        $logger = new Logger('key', 'secret');
        $logger->setClient($client);

        $this->assertFalse($logger->log($statement));
        $this->assertTrue($logger->hasError());
        $this->assertEquals(500, $logger->getErrorCode());
        $this->assertNotEmpty($logger->getErrorMessage());
    }

    public function testLoggingForReal()
    {
        $this->markTestSkipped('Use this to test logging manually');

        $key = config('auth.lrs.key');
        $secret = config('auth.lrs.secret');
        $server = config('auth.lrs.server');

        $statement = new Statement($this->validXApiStatement);
        $actor = new \Cerpus\xAPI\Actor(123);
        $statement->setActor($actor);

        $logger = new Logger($key, $secret, $server);
        $logger->log($statement);
    }

    private function getMockClient($responseCodes = [200, 200])
    {
        $responses = [];

        foreach ($responseCodes as $responseCode) {
            $responses[] = new Response($responseCode);
        }

        $handler = HandlerStack::create(
            new MockHandler($responses)
        );

        return new Client(['handler' => $handler]);
    }
}
