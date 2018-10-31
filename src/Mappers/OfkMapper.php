<?php

namespace Cerpus\xAPI\Mappers;

use Cerpus\xAPI\Activity;

class OfkMapper
{
    protected $tags;

    public function __construct($tags = [])
    {
        $this->tags = $tags;
    }

    public function map()
    {
        $activities = [];

        $ofkTags = $this->filterOfkTags($this->tags);

        $activities = collect($ofkTags)->map(function ($tag) {
            $id = 'https://lmbase.no/avt/area-within-the-map/' . $tag;
            return new Activity($id);
        })->toArray();

        return $activities;
    }

    /**
     * Return only tags that might have something to do with the OFK areas
     *
     * @return mixed
     */
    public function filterOfkTags($tags)
    {
        $ofkTags = collect($tags)
            ->map(function ($tag) { // Uppercase and trim
                return mb_strtoupper(trim($tag));
            })
            ->filter(function ($tag) { // Filter tags that don't match the pattern
                return preg_match("/(^\#*)(OFK\d{5})$/", $tag) === 1;
            })
            ->map(function ($tag) { // remove # characters
                return str_replace('#', '', $tag);
            })
            ->unique()// Remove duplicates from upper / lowercase tags
            ->values()
            ->toArray();

        return $ofkTags;
    }
}
