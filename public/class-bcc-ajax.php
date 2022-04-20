<?php
/**
 * The public-facing AJAX functionality.
 *
 * Creates the various functions used for AJAX on the front-end.
 *
 * @package    Plugin
 * @subpackage Plugin/public
 * @author     Plugin_Author <email@example.com>
 */

if( ! class_exists( 'Plugin_Public_Ajax' ) ){

	class Plugin_Public_Ajax {

		/**
		 * An example AJAX callback.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function submit_project() {
            global $wpdb;

            // Check the nonce for permission.
			// if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'plugin' ) ) {
			// 	header("HTTP/1.1 401 Not Authorized");
            //     exit();
			// }
            
            // validate reCaptcha
            $client = new \GuzzleHttp\Client();
            $response = $client->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'form_params' => [
                        'secret' => get_option('bcc_gcaptcha_secret'),
                        'response' => $_POST['captchaToken']
                    ]
                ]
            );

            $rData = json_decode($response->getBody()->getContents());

            if ($rData->success !== true) {
                // Google reCaptcha validation failed
                header("HTTP/1.1 401 Unauthorized");
                exit();
            }

            // Validate fields
            if (
                !isset($_POST['data']['project_1']) || trim($_POST['data']['project_1']) === '' ||
                !isset($_POST['data']['project_2']) || trim($_POST['data']['project_2']) === '' ||
                !isset($_POST['data']['project_3']) || trim($_POST['data']['project_3']) === '' ||
                !isset($_POST['data']['project_4']) || trim($_POST['data']['project_4']) === '' ||
                !isset($_POST['data']['project_5']) || trim($_POST['data']['project_5']) === '' ||
                !isset($_POST['data']['project_6']) || trim($_POST['data']['project_6']) === '' ||
                !isset($_POST['data']['project_name']) || trim($_POST['data']['project_name']) === '' ||
                !filter_var($_POST['data']['email'], FILTER_VALIDATE_EMAIL)
            ) {
                header("HTTP/1.1 400 Bad Request");
                exit();
            }

            foreach($_POST['data'] as $key=> $value) {
                $_POST['data'][$key] = strip_tags(htmlspecialchars(trim($value)));
            }

            /* Create the poll */
            $deadline = new DateTime(date('Y-m-d', strtotime('+' . get_option('bcc_sp_duration', 5) . ' days')));

            $body = json_encode([
                "type" => "multiple_choice",
                "title" => "Stimmungsbild Projektanfrage " . $_POST['data']['project_name'],
                "poll_meta" => [
                    "description" => "Würdest du dieses Projekt zukünftig gerne auf dem PLATZprojekt sehen?",
                    "location" => ""
                ],
                "media" => [
                    "path" => null
                ],
                "poll_options" => [
                    ["value" => "Ja"],
                    ["value" => "Nein"],
                    ["value" => "Enthaltung"],
                ],
                "poll_config" => [
                    "is_private" => 1,
                    "allow_comments" => 0,
                    "is_multiple_choice" => 0,
                    "multiple_choice_min" => null,
                    "multiple_choice_max" => null,
                    "require_voter_names" => 1,
                    "duplication_checking" => "ip",
                    "deadline_at" => $deadline->getTimestamp(),
                    "status" => "published",
                    "require_voter_names" => 0,
                    "send_webhooks" => 1
                ]
            ]);
            
            $client = new \GuzzleHttp\Client();
            $pollData = [
                'content_id' => null
            ];

            try {
                $response = $client->request('POST', 'https://api.strawpoll.com/v2/polls', [
                    'headers' => [
                        'X-API-KEY' => get_option('bcc_sp_api_key')
                    ],
                    'body' => $body
                ]);
                
                $pollData = json_decode($response->getBody()->getContents(), true)['poll'];

            } catch(Exception $e) {
                wp_mail( get_option( 'admin_email' ), 'Strawpoll Error', 'Could not create StrawPoll: ' . $e->getMessage() . "\r\n" . 'Project: ' . $_POST['data']['project_name']);
            }

            // Create Basecamp Client
            $bclient = new BClient();

            // Create the main post
            ob_start();
            include 'partials/bcc-basecamp-template-message.php';
            $content = ob_get_clean();

            $options = array(
                'subject' => 'Projekt-Anfrage: ' . $_POST['data']['project_name'],
                'content' => $content,
                'status' => 'active'
            );
            if (get_option('bcc_b3_message_category_id', '') !== '') {
                $options['category_id'] = get_option('bcc_b3_message_category_id', '');
            }
            
            $client = new \GuzzleHttp\Client();
            $newMessage = $bclient->messages()->create(get_option('bcc_b3_project_id'), get_option('bcc_b3_messageboard_id'), $options);

            // Drop a line in campfire
            if (get_option('bcc_b3_campfire_id') !== '') {
                $newChatLine = $bclient->campfires()->createLine(get_option('bcc_b3_project_id'), get_option('bcc_b3_campfire_id'), array(
                    'content' => get_option('bcc_b3_campfire_message') . ' ' . $newMessage->app_url
                ));
            }

            // Create ToDos
            if (get_option('bcc_b3_todolistset_id', '') !== '') {
                $people = $bclient->people()->showInProject(get_option('bcc_b3_project_id'));
                
                $assignees = [];
                foreach($people as $person) {
                    $assignees[] = $person->id;
                }

                $dueDate = date('d.m.Y', strtotime('+' . get_option('bcc_sp_duration', 5) . ' days'));
                $newToDoList = $bclient->todolists()->create(get_option('bcc_b3_project_id'), get_option('bcc_b3_todolistset_id'), array(
                    'name' => 'ToDos Projektbewerbung ' . $_POST['data']['project_name'],
                    'description' => 'Fällig am ' . $dueDate . '<br />' . $newMessage->app_url
                ));

                $newTodo = $bclient->todos()->create(get_option('bcc_b3_project_id'), $newToDoList->id, array(
                    'content' => 'Stimmungsbild',
                    'description' => 'Bitte stimme kurz ab ob du dieses Projekt zukünftig gerne auf dem PLATZprojekt sehen möchtest oder nicht. <br /><a href="' . $pollData['url'] . '">zur Abstimmung</a>',
                    'assignee_ids' => $assignees,
                    'notify' => true,
                    'due_on' => $dueDate,
                    'starts_on' => date('d.m.Y')
                ));

                // ob_start();
                // include 'partials/bcc-basecamp-template-contactinfo.php';
                // $content = ob_get_clean();

                // $newTodo = $bclient->todos()->create(get_option('bcc_b3_project_id'), $newToDoList->id, array(
                //     'content' => 'Projekt kontaktieren',
                //     'description' => $content,
                //     'assignee_ids' => ['29093879'],
                //     'notify' => true,
                //     'due_on' => $dueDate
                // ));
            }

            // Store values in DB for webhooks
            $result = $wpdb->insert(
                $wpdb->prefix . "bcc_projects",
                array( 
                    "bc_message_id" => $newMessage->id,
                    "bc_todo_id" => $newTodo->id,
                    "poll_content_id" => $pollData['id']
                ), array( "%d", "%s", "%s" )
            );
            
            echo json_encode( ['message' => $newMessage] );
            wp_die();
			
        }

        public function addUser() {
            $bclient = new BClient();
            $data = $bclient->people()->create();
            echo '<pre>';
            print_r($data);
            echo '</pre>';
            
        }

        public function removeUser() {
            $bclient = new BClient();
            $projects = $bclient->projects()->active();
            echo '<pre>';
            print_r($projects);
            echo '</pre>';
            
        }
    }
}