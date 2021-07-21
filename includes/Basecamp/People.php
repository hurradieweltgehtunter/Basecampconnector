<?php
namespace Basecamp\Api;

/**
 * People API.
 *
 * @link https://github.com/basecamp/bc3-api/blob/master/sections/people.md
 */
class People extends AbstractApi
{
    /**
     * All people on the account.
     *
     * @return array
     */
    public function all()
    {
        $data = $this->get('/people.json');

        return $data;
    }

    /**
     * Show People in project
     *
     * @return array
     */
    public function showInProject($projectId)
    {
        $data = $this->get('/projects/' . $projectId . '/people.json');

        return $data;
    }

    /**
     * Get person.
     *
     * @param integer $userId
     *
     * @return object
     */
    public function show($userId)
    {
        $data = $this->get('/people/' . $userId . '.json');

        return $data;
    }

    /**
     * Add person to project
     * @param integer $projectId
     * @param integer $userId
     *
     * @return object
     */
    public function addToProject($projectId, $userId)
    {
        $data = $this->put('/projects/' . $projectId . '/people/users.json', [
            'grant' => [$userId]
        ]);

        return $data;
    }

    public function create($details) {
        $params = [
            'create'=> [
                [
                    'name' => $details['name'],
                    'email_address' => $details['email'],
                    'title' => $details['title'],
                    'company_name' => $details['company'],
                ]
            ]
        ];
        
        $data = $this->put('/projects/' . get_option('bcc_ev_project_id') . '/people/users.json', $params);

        return $data;
    }
}
