<?php
namespace Basecamp\Api;

/**
 * Campfires API.
 *
 * @link https://github.com/basecamp/bc3-api/blob/master/sections/campfires.md
 */
class Campfires extends AbstractApi
{
    /**
     * Specified message.
     *
     * @param integer $projectId
     * @param integer $campfireId
     *
     * @return object
     */
    public function getCampfire($projectId, $campfireId)
    {
        $data = $this->get('/buckets/' . $projectId . '/chats/' . $campfireId . '.json');

        return $data;
    }


    /**
     * Create chat line in Campfire.
     *
     * @param integer $projectId
     * @param array $params
     *
     * @return object
     */
    public function createLine($projectId, $campfireId, array $params)
    {
        $data = $this->post('/buckets/' . $projectId .  '/chats/' . $campfireId . '/lines.json', $params);

        return $data;
    }
}
