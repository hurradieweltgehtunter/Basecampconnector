<?php

/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://florianlenz.com
 * @since      1.0.0
 *
 * @package    Bcc
 * @subpackage Bcc/admin/partials
 */

 $redirectUri = get_site_url() . '/wp-admin/admin.php?page=Basecamp+Connector';
 ?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<section class="container p-3 pt-5">
    <div class="clearfix d-flex align-items-center justify-content-start mb-3">
        <img src="<?php echo plugin_dir_url( __DIR__); ?>img/logo-basecamp.jpg" class="rounded me-3">
        <div>
            <h1><?php echo $this->plugin_name; ?></h1>
            <p>Connect your wordpress website with Basecamp and set up automations.<br />
            Plugin Author: Florian Lenz</p>
            <div><b>If you don't know whats happening here do NOT edit anything</b></div>
        </div>
    </div>

    <?php
    
    if (isset($_SESSION['authenticated'])) : 
        if ($_SESSION['authenticated'] === true): ?>
        <div class="alert alert-success" role="alert">
            You successfully connected Wordpress with Basecamp!
        </div>
        <?php
        else :
        ?>
        <div class="alert alert-danger" role="alert">
            Basecamp-Connection failed!<br />
            <?php echo $this->error; 
            echo $_SESSION['error'];
            ?>
        </div>
        <?php
        endif;

        $_SESSION = array();

        // Zum Schluß, löschen der Session.
        session_destroy();
    endif;


    if ($_GET['sync'] && $_GET['sync'] === 'success') : ?>
        <div class="alert alert-success" role="alert">
            Sync was successful
        </div>
    <?php endif;
    ?>

    <div class="col-md-6">
        <table class="table border rounded">
            <tbody>
                <tr scope="row">
                    <td>Access Code available</td>
                    <td>
                        <?php
                        if (trim((string) $wpdb->get_var( "SELECT `value` FROM " . $wpdb->prefix . "bcc_options WHERE `identifier` = 'access_token'" )) !== '') :
                            echo 'Yes';
                        else :
                            echo 'No. Scroll down to bottom of this page and click on "Authenticate App"';
                        endif;
                        ?>
                    </td>
                </tr>
                <tr scope="row">
                    <td>Refresh Token available</td>
                    <td>
                        <?php
                        if (trim((string) $wpdb->get_var( "SELECT `value` FROM " . $wpdb->prefix . "bcc_options WHERE `identifier` = 'refresh_token'" )) !== '') :
                            echo 'Yes';
                        else :
                            echo 'No. Scroll down to bottom of this page and click on "Authenticate App"';
                        endif;
                        ?>
                    </td>
                </tr>
                <tr scope="row">
                    <td>Token expires</td>
                    <td>
                        <?php
                        $expiry = $wpdb->get_var( "SELECT `value` FROM " . $wpdb->prefix . "bcc_options WHERE `identifier` = 'access_token_expires'" );
                        if (trim((string) $expiry) !== ''):
                            echo date('d.m.Y H:i', $expiry);
                        else :
                            echo 'Not set';
                        endif;
                        ?>
                        <p>Tokens refresh automatically, <br />you don't need to do anything</p>
                    </td>
                </tr>
                <tr scope="row">
                    <td>Latest synced Member</td>
                    <td>
                        <?php
                        $evClient = new EasyVereinClient();
                        $latestSyncedMember = $evClient->getLatestSyncedMember();
                        ?>
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>Name</td>
                                    <td><?php 
                                    if ($latestSyncedMember['firstName'] === null || $latestSyncedMember['familyName'] === null) {
                                        echo '-';
                                    } else {
                                        echo $latestSyncedMember['firstName'] . ' ' . $latestSyncedMember['familyName']; 
                                    }
                                    ?></td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td><?php 
                                    if ($latestSyncedMember['emailOrUserName'] === null) {
                                        echo '-';
                                    } else {
                                        echo $latestSyncedMember['emailOrUserName']; 
                                    }
                                    ?></td>
                                </tr>
                                <tr>
                                    <td>Membership Number</td>
                                    <td><?php 
                                    if ($latestSyncedMember['membershipNumber'] === null) {
                                        echo '-';
                                    } else {
                                        echo $latestSyncedMember['membershipNumber'];
                                    }
                                    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Join Date</td>
                                    <td><?php 
                                    if ($latestSyncedMember['joinDate'] === null) {
                                        echo '-';
                                    } else {
                                        echo date('d.m.Y', strtotime($latestSyncedMember['joinDate']));
                                    }
                                    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sync Date</td>
                                    <td><?php 
                                    if ($latestSyncedMember['synced_at'] === null) {
                                        echo '-';
                                    } else {
                                        echo date('d.m.Y', strtotime($latestSyncedMember['synced_at']));
                                    }
                                    ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr scope="row">
                    <td>
                        <div class="wrap">
                            Star Sync manually
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                                <input type="hidden" name="action" value="manual_sync">
                                <?php submit_button('Basecamp Sync manuell starten', 'primary', 'manual_sync'); ?>
                            </form>
                        </div>
                    </td>
                    <td>
                        &nbsp;
                    </td>
            </tbody>
        </table>
    </div>

    <form method="post" action="options.php"> 
        <?php
        settings_fields( 'bcc_options' ); 
        do_settings_sections( 'bcc_options' );
        ?>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_user_agent" class="form-label">Website Identifier</label>
                <input type="text" class="form-control" name="bcc_b3_user_agent" value="<?php echo esc_attr( get_option('bcc_b3_user_agent') ); ?>" />
                <p>Used as User-Agent for Basecamp API-Requests. Must contain some form of contact option - basecamp will use this in case of errors.<br />
                Example: Freddy's Fresh Fanbase (info@freddy.com, https://freddy.com/contact)</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_account_id" class="form-label">Basecamp Account ID</label>
                <input type="text" class="form-control" name="bcc_b3_account_id" value="<?php echo esc_attr( get_option('bcc_b3_account_id') ); ?>" />
                <p>See Basecamp-URL for ID: https://3.basecamp.com/<b>9999999</b>/buckets/...</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_project_id" class="form-label">Project ID</label>
                <input type="text" class="form-control" name="bcc_b3_project_id" value="<?php echo esc_attr( get_option('bcc_b3_project_id') ); ?>" />
                <p>Project in which we want to post. <br />See Basecamp-URL for ID: https://3.basecamp.com/999999999/buckets/<b>11111111</b>/...</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_messageboard_id" class="form-label">Messageboard ID</label>
                <input type="text" class="form-control" name="bcc_b3_messageboard_id" value="<?php echo esc_attr( get_option('bcc_b3_messageboard_id') ); ?>" />
                <p>Project in which we want to post. <br />See Basecamp-URL for ID: https://3.basecamp.com/999999999/buckets/11111111/message_boards/<b>22222222</b>/...</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_message_category_id" class="form-label">Category ID for new posts</label>
                <input type="text" class="form-control" name="bcc_b3_message_category_id" value="<?php echo esc_attr( get_option('bcc_b3_message_category_id') ); ?>" />
                <p>ID of category. Leave empty for no category</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_campfire_id" class="form-label">Campfire ID</label>
                <input type="text" class="form-control" name="bcc_b3_campfire_id" value="<?php echo esc_attr( get_option('bcc_b3_campfire_id') ); ?>" />
                <p>If you like to drop a line in a campfire set the campfire id here. <br />See Basecamp-URL for ID: https://3.basecamp.com/111111/buckets/222222222/chats/<b>333333333</b>...<br />
                Leave empty for no Campfire notification</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_campfire_message" class="form-label">Campfire Message</label>
                <input type="text" class="form-control" name="bcc_b3_campfire_message" value="<?php echo esc_attr( get_option('bcc_b3_campfire_message') ); ?>" placeholder="Hey, check out this new entry:" />
                <p>Set the message you want to drop in the Campfire. A link to the posted messageboard thread will be appended.</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_todolistset_id" class="form-label">ToDo-List-Set ID</label>
                <input type="text" class="form-control" name="bcc_b3_todolistset_id" value="<?php echo esc_attr( get_option('bcc_b3_todolistset_id') ); ?>" />
                <p>ID of Set where a new ToDo-List will be created in. <br />See Basecamp-URL for ID: https://3.basecamp.com/111111/buckets/222222/todosets/<b>333333333</b>...<br />
                Leave empty for no ToDo-List creation</p>
            </div>
        </div>

        <h2>Authentication details</h2>
        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_client_id" class="form-label">Client ID</label>
                <input type="text" class="form-control" name="bcc_b3_client_id" value="<?php echo esc_attr( get_option('bcc_b3_client_id') ); ?>" />
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_b3_client_secret" class="form-label">Client Secret</label>
                <input type="password" class="form-control" name="bcc_b3_client_secret" value="<?php echo esc_attr( get_option('bcc_b3_client_secret') ); ?>" />
            </div>
        </div>

        <h2>Google reCaptcha</h2>
        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_gcaptcha_sitekey" class="form-label">Site Key</label>
                <input type="text" class="form-control" name="bcc_gcaptcha_sitekey" value="<?php echo esc_attr( get_option('bcc_gcaptcha_sitekey') ); ?>" />
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_gcaptcha_secret" class="form-label">Client Secret</label>
                <input type="password" class="form-control" name="bcc_gcaptcha_secret" value="<?php echo esc_attr( get_option('bcc_gcaptcha_secret') ); ?>" />
            </div>
        </div>
                  
        <h2>StrawPoll</h2>
        <p><a href="https://strawpoll.com" target="_blank">Strawpoll.com</a> is used to create polls for projects.</p>
        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_sp_api_key" class="form-label">Strawpolls API KEY</label>
                <input type="password" class="form-control" name="bcc_sp_api_key" value="<?php echo esc_attr( get_option('bcc_sp_api_key') ); ?>" />
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_sp_duration" class="form-label">Abstimmungsdauer (in Tagen)</label>

                <input type="number" class="form-control" name="bcc_sp_duration" value="<?php echo esc_attr( get_option('bcc_sp_duration', 5) ); ?>" min="1" max="30" />
            </div>
        </div>

        <h2>EasyVerein</h2>
        <p><a href="https://easyverein.com" target="_blank">EasyVerein</a> is used manage club members.</p>
        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_api_url" class="form-label">EasyVerein API URL</label>
                <input type="text" class="form-control" name="bcc_ev_api_url" value="<?php echo esc_attr( get_option('bcc_ev_api_url') ); ?>" />
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_api_key" class="form-label">EasyVerein API Token</label>
                <input type="password" class="form-control" name="bcc_ev_api_key" value="<?php echo esc_attr( get_option('bcc_ev_api_key') ); ?>" />
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_project_id" class="form-label">Default Project ID</label>
                <input type="text" class="form-control" name="bcc_ev_project_id" value="<?php echo esc_attr( get_option('bcc_ev_project_id') ); ?>" />
                <p>Project ID where new members should be added to. Only one project ID is valid.<br />See Basecamp-URL for ID: https://3.basecamp.com/xxxxxxx/projects/<b>123456789</b></p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_project_id_additional" class="form-label">Additional default project IDs</label>
                <input type="text" class="form-control" name="bcc_ev_project_id_additional" value="<?php echo esc_attr( get_option('bcc_ev_project_id_additional') ); ?>" />
                <p>Comma separated list of additional projects new users should be added to.<br />See Basecamp-URL for ID: https://3.basecamp.com/xxxxxxx/projects/<b>123456789</b></p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_welcome_text" class="form-label">Welcome text</label>
                <textarea class="form-control" name="bcc_ev_welcome_text"><?php echo esc_attr( get_option('bcc_ev_welcome_text') ); ?></textarea>
                <p>Welcome message for newly added members. Use placeholder {user} to insert the users' name and mention him/her.</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">                
                <label for="bcc_ev_welcome_text_message_id" class="form-label">Message ID for welcome message</label>
                <input type="text" class="form-control" name="bcc_ev_welcome_text_message_id" value="<?php echo esc_attr( get_option('bcc_ev_welcome_text_message_id') ); ?>" />
                <p>ID of the message where the welcome message should be posted in. <br />See Basecamp-URL for ID: https://3.basecamp.com/xxxxxx/buckets/yyyyyy/messages/<b>123456789</b></p>
            </div>
        </div>

        <?php submit_button(); ?>

        <h2 class="mt-6">Authentication process</h2>
        <p>If you filled out all the fields above click on the button to connect Wordpress with your Basecamp account. A few things to consider:</p>
        <ul class="list-group">
            <li  class="list-group-item">Make sure you are logged in to Basecamp with the user account you want Wordpress post to Basecamp . For example create a user account name "Wordpress Bot", login to Basecamp with this user and hit the authentication button below.</li>
            <li  class="list-group-item">You will be forwarded to an oAuth2 page from Basecamp asking for access to your Basecamp accounts. In order to connect Wordpress to Basecamp you must click on "Yes, I'll allow access"</li>
            <li  class="list-group-item">In order to be able to finalize the authentication process you must ensure that the plugin URL (which you can see now in your address bar) is set as redirect URI in the app settings.<br />
                    <ul>
                        <li>Go to <a href="https://launchpad.37signals.com/integrations" target="_blank">https://launchpad.37signals.com/integrations</a> and select your app</li>
                        <li>Enter <i><?php echo $redirectUri; ?></i> as redirect URI</li>
                        <li>Hit "Save"</li>
                    </ul>
            </li>
            <li  class="list-group-item">If anyhting goes wrong let me know: <a href="mailto:hi@florianlenz.com" target="_blank">hi@florianlenz.com</a>
        </ul>

        <button type="button" id="authenticate-app">Authenticate app now</button>
    </form>
</section>