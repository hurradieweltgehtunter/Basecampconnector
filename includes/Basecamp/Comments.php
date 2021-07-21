<?php
namespace Basecamp\Api;

/**
 * Comments API.
 *
 * @link https://github.com/basecamp/bcx-api/blob/master/sections/comments.md
 */
class Comments extends AbstractApi
{
    /**
     * New comment for message.
     *
     * @param integer $projectId
     * @param integer $messageId
     * @param array $params
     *
     * @return object
     */
    public function create($projectId, $messageId, array $params)
    {
        $data = $this->post('/buckets/' . $projectId . '/recordings/' . $messageId . '/comments.json', $params);

        return $data;
    }
}
