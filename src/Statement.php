<?php

namespace Cerpus\xAPI;

use Log;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Cerpus\xAPI\Mappers\OfkMapper;

class Statement
{
    protected $statement;
    protected $client;

    /**
     * Statement constructor.
     * @param string $rawStatement
     */
    public function __construct($rawStatement = null, Client $client = null)
    {
        if (!$rawStatement) {
            throw new \InvalidArgumentException('rawStatement is required');
        }

        if (!is_object($rawStatement)) {
            throw new \InvalidArgumentException('rawStatement must be an object');
        }

        if (!property_exists($rawStatement, 'actor')) {
            throw new \InvalidArgumentException("actor does not exist in rawStatement");
        }

        if (!property_exists($rawStatement, 'verb')) {
            throw new \InvalidArgumentException("verb does not exist in rawStatement");
        }

        if (!property_exists($rawStatement, 'object')) {
            throw new \InvalidArgumentException("object does not exist in rawStatement");
        }

        $this->statement = $rawStatement;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function getActor()
    {
        if (!property_exists($this->statement, 'actor')) {
            throw new \Exception("statement->actor does not exist");
        }

        return $this->statement->actor;
    }

    public function setActor(Actor $actor)
    {
        $this->statement->actor = $actor->asObject();
    }

    public function asJsonString()
    {
        return json_encode($this->getStatement());
    }

    public function getObject()
    {
        if (!property_exists($this->statement, 'object')) {
            throw new \Exception("statement->object does not exist");
        }

        return $this->statement->object;
    }

    public function addAVTContextActivities()
    {
        $metaTags = $this->getMetaDataTags();
        $metaTagsAsStatementFragment = $this->mapMetaTagsToStatementActivity($metaTags);
        $this->mergeContextActivities($metaTagsAsStatementFragment);
    }

    protected function getMetaDataTags(): array
    {
        $tags = [];

        try {
            $baseUri = $this->getUrlWithPath($this->getObject()->id);
            if (!Str::endsWith($baseUri, '/')) {
                $baseUri .= '/';
            }

            if (!$this->client) {
                $this->client = new Client([
                    'base_uri' => $baseUri,
                ]);
            }

            $tags = json_decode($this->client->get('tags')->getBody());
        } catch (\Throwable $exception) {
            Log::error(__METHOD__ . ': (' . $exception->getCode() . ') ' . $exception->getMessage());
        }

        return $tags;
    }

    protected function parseContentId($contentId)
    {
        $itemId = null;

        $path = parse_url($contentId, PHP_URL_PATH);
        if ($path) {
            $pathParts = explode('/', $path);
            $itemId = end($pathParts);
        }

        return $itemId;
    }

    protected function getUrlWithPath($contentId)
    {
        $urlWithPath = null;

        $urlParts = parse_url($contentId);
        $scheme = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '';
        $host = isset($urlParts['host']) ? $urlParts['host'] : '';
        $port = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $user = isset($urlParts['user']) ? $urlParts['user'] : '';
        $pass = isset($urlParts['pass']) ? ':' . $urlParts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($urlParts['path']) ? $urlParts['path'] : '';

        $urlWithPath = "$scheme$user$pass$host$port$path";

        return $urlWithPath;
    }

    public function mergeContextActivities($activities = [])
    {
        if (empty($activities)) {
            return;
        }

        if (!property_exists($this->statement, 'context')) {
            $this->statement->context = new \stdClass();
        }

        if (!property_exists($this->statement->context, 'contextActivities')) {
            $this->statement->context->contextActivities = new \stdClass();
        }

        if (!property_exists($this->statement->context->contextActivities, 'grouping')) {
            $this->statement->context->contextActivities->grouping = [];
        }

        $existingActivitiesKeys = collect($this->statement->context->contextActivities->grouping)
            ->keyBy(function ($activity) {
                return $activity->id;
            });

        $rawStatement = $this->statement;

        collect($activities)
            ->keyBy(function ($activity) {
                return $activity->getId();
            })
            ->diffKeys($existingActivitiesKeys)
            ->each(function ($activity) use ($rawStatement) {
                $rawStatement->context->contextActivities->grouping[] = $activity->asObject();
            });
    }

    private function mapMetaTagsToStatementActivity($tags = [])
    {
        if (empty($tags)) {
            return [];
        }

        $mapper = new OfkMapper($tags);

        $activities = $mapper->map();

        return $activities;
    }
}
