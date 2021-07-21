<?php
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/Campfires.php');
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/Messages.php');
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/Todos.php');
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/Todolists.php');
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/Comments.php');
require_once(plugin_dir_path(__FILE__) . '/../includes/Basecamp/People.php');

use Basecamp\Api\Campfires;
use Basecamp\Storage;
use Basecamp\StorageSession;
use Basecamp\Client;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;

if( ! class_exists( 'BClient' ) ){
    class BClient extends Client
    {
        const BASE_URL = 'https://3.basecampapi.com/';

        /**
         * Account data.
         *
         * @var array
         */
        private $accountData = null;

        /**
         * Class constructor.
         *
         * @param array $accountData Assotiative array
        *                           <code>
        *                           [
        *                           'accountId' => '', // Basecamp account ID
        *                           'appName' =>  '', // Application name (used as User-Agent header)
        *                           'token' =>    '', // OAuth token
        *                           'login' =>    '', // 37Signal's account login
        *                           'password' => '', // 37Signal's account password
        *                           ]
        *                           </code> 
         */
        public function __construct($debug = false)
        {
            global $wpdb;
            $this->debug = $debug;

            $this->accountData = [
                'accountId' => get_option('bcc_b3_account_id'),
                'appName' => get_option('bcc_b3_user_agent'),
                'access_token' => $wpdb->get_var( 'SELECT `value` FROM ' . $wpdb->prefix . 'bcc_options WHERE `identifier` = "access_token"' ),
                'refresh_token' => $wpdb->get_var( 'SELECT `value` FROM ' . $wpdb->prefix . 'bcc_options WHERE `identifier` = "refresh_token"' ),
                'access_token_expires' => $wpdb->get_var( 'SELECT `value` FROM ' . $wpdb->prefix . 'bcc_options WHERE `identifier` = "access_token_expires"' ),
                'client_id' => get_option('bcc_b3_client_id'),
                'client_secret' => get_option('bcc_b3_client_secret'),
            ];
            if ($this->debug) {
                echo '<pre>';
                print_r($this->accountData);
                echo '</pre>';
                
            }
            parent::__construct($this->accountData);

            $this->headers = [
                'User-Agent: ' . $this->getAccountData()['appName'],
                'Content-Type: application/json'
            ];

            $tokenExpired = false;
            if (time() - (int) $this->accountData['access_token_expires'] >= 0) {
                $tokenExpired = true;
            }

            if ($this->accountData['access_token'] === '' || $this->accountData['access_token'] === NULL || $tokenExpired) {
                if ($this->debug) {
                    echo "\r\n" . 'Token expired or invalid';
                }
                
                $this->renewAccessToken();
                
            } else {
                // Token is available and valid, test the connection
                if ($this->debug) {
                    echo "\r\n" . 'Token valid; Testing connection';
                }

                if ($this->connectionValid()) {
                    $this->renewAccessToken();
                }
            }

            if ($this->debug) {
                $this->connectionValid();
                exit('done');
            }
        }

        private function connectionValid() {
            $message = new Request('GET', '', 'https://launchpad.37signals.com/authorization.json');
            $headers = array_merge($this->headers, ['Authorization: Bearer '.$this->accountData['access_token']]);
            $message->setHeaders($headers);
            
            $response = new Response();

            $bc = $this->createCurl();
            $bc->setTimeout(10);

            $bc->send($message, $response);
            
            if ($this->debug) {
                echo "\r\n" . 'Connection test: ' . $response->getStatusCode() . ':' . $response->getContent();
            }

            if ($response->getStatusCode() !== 200) {
                return false;
            } else {
                return true;
            }
        }

        public function renewAccessToken() {
            global $wpdb;

            if ($this->debug) {
                echo "\r\n" . 'Renewing Token';
            }

            $message = new Request('POST', '', 'https://launchpad.37signals.com/authorization/token?type=refresh&refresh_token=' .$this->accountData['refresh_token'] . '&client_id=' . $this->accountData['client_id'] . '&redirect_uri=https://platzprojekt.de&client_secret=' . $this->accountData['client_secret'] . '');
            $message->setHeaders($this->headers);

            $response = new Response();

            $bc = $this->createCurl();
            $bc->setTimeout(10);
            $bc->send($message, $response);
            
            $result = json_decode($response->getContent());

            if ($this->debug) {
                echo "\r\n" . 'Result: ' . $response->getStatusCode() . ':' . $response->getContent();
            }

            if ($response->getStatusCode() === 200) {
                $expiry = (time() + $result->expires_in - 10);

                // Update DB
                if (!$this->debug) {
                    $wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->prefix . "bcc_options` SET `value` = %s WHERE `" . $wpdb->prefix . "bcc_options`.`identifier` = 'access_token';", array($result->access_token)));
                    $wpdb->query( $wpdb->prepare( "UPDATE `" . $wpdb->prefix . "bcc_options` SET `value` = %s WHERE `" . $wpdb->prefix . "bcc_options`.`identifier` = 'access_token_expires';", array($expiry)));
                }

                // Update live data
                $this->accountData['access_token'] = $result->access_token;
                $this->accountData['access_token_expires'] = $expiry;

                return true;
            } else {                
                throw new \Exception('Could not renew Basecamp access token. Aborting. (' . $result->error .')');
            }
        }

        /**
         * Campfires instance.
         *
         * @return Campfires
         */
        public function campfires()
        {
            return new Campfires($this);
        }

        /**
         * Make HTTP Request.
         *
         * @return mixed[]
         */
        public function request($method, $resource, $params = [], $timeout = 10)
        {        
            $storage = Storage::get();
            $hash = $storage->createHash($method, $resource, $params);
            $etag = $storage->get($hash);

            if ($etag) {
                $this->headers[] = 'If-None-Match: '.$etag;
            }

            $message = new Request($method, $resource, self::BASE_URL.$this->getAccountData()['accountId']);
            
            $message->setHeaders($this->headers);

            if (!empty($params)) {
                // When attaching files set content as is
                if (array_key_exists('binary', $params)) {
                    $message->setContent($params['binary']);
                } else {
                    $message->setContent(json_encode($params));
                }
            }

            $response = new Response();

            $bc = $this->createCurl();
            $bc->setTimeout($timeout);

            $message->addHeader('Authorization: Bearer '.$this->accountData['access_token']);

            $bc->send($message, $response);
            
            $storage->put($hash, trim($response->getHeader('ETag'), '"'));

            $data = new \stdClass();

            switch ($response->getStatusCode()) {
                case 201:
                    $data = json_decode($response->getContent());
                    $data->message = 'Created';
                    break;
                case 204:
                    $data->message = 'Resource succesfully deleted';
                    break;
                case 304:
                    $data->message = '304 Not Modified';
                    break;
                case 400:
                    $data->message = '400 Bad Request';
                    break;
                case 403:
                    $data->message = '403 Forbidden';
                    break;
                case 404:
                    $data->message = '404 Not Found';
                    break;
                case 415:
                    $data->message = '415 Unsupported Media Type';
                    break;
                case 429:
                    $data->message = '429 Too Many Requests. '.$response->getHeader('Retry-After');
                    break;
                case 500:
                    $data->message = '500 Hmm, that isn’t right';
                    break;
                case 502:
                    $data->message = '502 Bad Gateway';
                    break;
                case 503:
                    $data->message = '503 Service Unavailable';
                    break;
                case 504:
                    $data->message = '504 Gateway Timeout';
                    break;
                default:
                    $data = json_decode($response->getContent());
                    break;
            }

            return $data;
        }
    }
}