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
    // $this->api_token = $this->db->get_var('SELECT `value` FROM ' . $this->db->prefix . 'bcc_options WHERE `identifier` = "ev_api_token"');
    $this->client = new GuzzleHttp\Client();
  }

  /**
   * Method to read option values from plugin table bcc_options (not to be confused with get_option() function from WP core reading from wp_options table)
   * 
   * @param string $option
   * @return string or null
   */
  public static function getOption ($option): ?string
  {
    global $wpdb;
    return $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bcc_options WHERE `identifier` = "' . $option . '"');
  }

  public function refreshApiToken(): string
  {
    $url = get_option('bcc_ev_api_url') . 'refresh-token';

    $headers = [
      'Authorization' => 'Bearer ' . get_option('bcc_ev_api_key')
    ];

    try {
      $response = $this->client->request('GET', $url, [
        'headers' => $headers
      ]);
    } catch (Exception $e) {
      throw new Exception('Could not refresh API token: ' . $e->getMessage());
    }

    $responseBody = $response->getBody()->getContents();
    $result = json_decode($responseBody, true);

    if (isset($result['Bearer'])) {
      update_option('bcc_ev_api_key', $result['Bearer']);
      // $this->db->query($this->db->prepare("UPDATE `" . $this->db->prefix . "bcc_options` SET `value` = %s WHERE `" . $this->db->prefix . "bcc_options`.`identifier` = 'ev_api_token';", array($result['Bearer'])));
      return $result['Bearer'];
    } else {
      
      // Throw
      throw new Exception('Could not refresh API token, no Bearer token found in response.');
    }
  }

  public function getMembers($filterString): array
  {
    $url = get_option('bcc_ev_api_url') . 'member?' . $filterString;

    $response = $this->client->request('GET', $url, [
      'headers' => [
        'Authorization' => 'Bearer ' . get_option('bcc_ev_api_key')
      ]
    ]);

    // Check header if tokenRefreshNeeded is true
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
      $lastSyncedMember = json_decode($result, true);

      // If its not a valid json string, it's a legacy format with | as delimiter
      // TODO: Remove this after latest member got saved in json format (3.7.2024, FL)
      if ($lastSyncedMember === null) {
        $result = explode('|', $result);

        $lastSyncedMember = [
          'membershipNumber' => $result[0],
          'emailOrUserName' => $result[1]
        ];
      }
    } else {
      $lastSyncedMember = null;
    }

    return $lastSyncedMember;
  }

  /**
   * Sets the latest synced member
   * 
   * @param array $memberData
   */
  public function setLatestSyncedMember($memberData): void
  {
    // Add current date
    $memberData['synced_at'] = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->format(DateTime::ATOM);

    // Merge all data to JSON
    $mergedData = json_encode($memberData);

    // Save to DB
    $this->db->query(
      $this->db->prepare(
        "UPDATE `" . $this->db->prefix . "bcc_options` SET 
        `value` = %s 
        WHERE `" . $this->db->prefix . "bcc_options`.`identifier` = 'ev_bc_sync_last_new';",
        [$mergedData]
      )
    );
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

