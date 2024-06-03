<?php

class EasyVereinClient {
  /**
   * Account data.
   *
   * @var array
   */
  private $accountData = null;
  private $db;
  private $debug;
  private $api_token;
  private $client;


  /**
   * Class constructor
   * 
   * @param array $accountData Assotiative array
   * <code>
   */
  public function __construct($debug = false)
  {
    global $wpdb;
    $this->db = $wpdb;

    $this->debug = $debug;
    $this->api_token = $this->db->get_var('SELECT `value` FROM ' . $this->db->prefix . 'bcc_options WHERE `identifier` = "ev_api_token"');
    $this->client = new GuzzleHttp\Client();
  }

  public static function getOption ($option) {
    global $wpdb;
    return $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bcc_options WHERE `identifier` = "' . $option . '"');
  }

  public function refreshApiToken(): string
  {
    $url = get_option('bcc_ev_api_url') . 'refresh-token';

    $response = $this->client->request('GET', $url, [
      'headers' => [
        'Authorization' => 'Bearer ' . get_option('bcc_ev_api_key')
      ]
    ]);

    $responseBody = $response->getBody()->getContents();
    $result = json_decode($responseBody, true);
    
    if (isset($result['Bearer'])) {
      $this->db->query($this->db->prepare("UPDATE `" . $this->db->prefix . "bcc_options` SET `value` = %s WHERE `" . $this->db->prefix . "bcc_options`.`identifier` = 'ev_api_token';", array($result['Bearer'])));
      return $result['Bearer'];
    } else {
      
      // Throw
      throw new Exception('Could not refresh API token');
    }
  }

  public function getMembers($filterString): array
  {
    $url = get_option('bcc_ev_api_url') . 'member/?' . $filterString;

		$response = $this->client->request('GET', $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . get_option('bcc_ev_api_key')
			]
		]);

    // CHeck header if tokenRefreshNeeded is true
    $tokenRefreshNeeded = $response->getHeader('tokenRefreshNeeded');
    if ($tokenRefreshNeeded[0] === 'true') {
      $this->refreshApiToken();
    }

    $responseBody = $response->getBody()->getContents();    
    $result = json_decode($responseBody);    
		$members = $result->results;

    return $members;
  }

  /**
   * Returns the latest synced member
   * 
   * @return array|null
   */
  public function getLatestSyncedMember(): ?array
  {
    $result = $this->db->get_var('SELECT `value` FROM ' . $this->db->prefix . 'bcc_options WHERE `identifier` = "ev_bc_sync_last_new"');
    
    if ($result !== null) {
      $result = explode('|', $result);
      $lastSyncedMember = [
        'membership_number' => $result[0],
        'username' => $result[1]
      ];

    } else {
      $lastSyncedMember = null;
    }

    return $lastSyncedMember;
  }

  public function getMemberDetails($member): object
  {
    $response = $this->client->request('GET', $member->contactDetails, [
      'headers' => [
        'Authorization' => 'Bearer ' . get_option('bcc_ev_api_key')
      ]
    ]);

    $responseBody = $response->getBody()->getContents();
    $result = json_decode($responseBody);    

    return $result;
  }
}

