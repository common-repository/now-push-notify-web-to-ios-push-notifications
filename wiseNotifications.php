<?php
/**
 * @package Now Push Notify - Web to iOS Push Notifications
 * @version 1.3.1
 */
/*
Plugin Name: Now Push Notify - Web to iOS Push Notifications
Description: Send push notifications to your iOS website visitors.
Author: Now Push
Version: 1.3.1
Author URI: https://notify.nowpush.app
*/

$nowpushNotifys_pluginPath = plugin_dir_path(__FILE__);

include $nowpushNotifys_pluginPath . 'embedjs.php';
include $nowpushNotifys_pluginPath . 'logging.php';


add_action('wp_footer', 'nowpushNotifys_addWidget');
function nowpushNotifys_addWidget()
{
    $uagent  = strtolower($_SERVER['HTTP_USER_AGENT']);
    $iPod    = stripos($uagent, "iPod") !== false;
    $iPhone  = stripos($uagent, "iPhone") !== false;
    $iPad    = stripos($uagent, "iPad") !== false;

    // show the widget only on iOS device
    if (!$iPad && !$iPod && !$iPhone) {
        return;
    }

    $pluginSettings = get_option('now_push_notify_name');

    if (empty($pluginSettings['topicId']) || empty($pluginSettings['apiKey']) || empty($pluginSettings['subscribeLink'])) {
        return;
    }

    $url = $pluginSettings['subscribeLink'];
    nowpushNotifys_scriptWidget($url);
}

if (is_admin()) {
    include $nowpushNotifys_pluginPath . 'settingsPage.php';
    include $nowpushNotifys_pluginPath . 'metaBox.php';
    nowpushnotifys_log('check OneSignal exists', class_exists('OneSignal_Admin'));

    // hooks
    if (class_exists('OneSignal_Admin')) {
        // send when OneSignal sends
        add_filter('onesignal_send_notification', function ($fields, $new_status, $old_status, $post) {
            nowpushnotifys_log('sending notification for post', $post);
            try {
                nowpushNotifys_sendNotificationForPost($post);
            } catch (\Exception $e) {
                nowpushnotifys_log('could not send notitication', ['err' => $e, 'post' => $post], 'error');
            }

            return $fields;
        }, 10, 4);
    } else {
        nowpushnotifys_log('add filter transition_post_status');
        add_action('transition_post_status', 'nowpushNotifys_postPublishedNotification', 10, 3);
    }

    function nowpushNotifys_postPublishedNotification($new_status, $old_status, $post)
    {
        nowpushnotifys_log('nowpushNotifys_postPublishedNotification started', ['new_status' => $new_status, 'old_status' => $old_status ]);
        // Check if someone published a post for the first time.
        if ($new_status == 'publish') {
            // TODO make sure the post is new and not updated (maybe check revisions?)
            try {
                nowpushNotifys_sendNotificationForPost($post);
            } catch (\Exception $e) {
                nowpushnotifys_log('could not send notitication', ['err' => $e, 'post' => $post], 'error');
            }
        }
    }

    function nowpushNotifys_sendNotificationForPost($post)
    {

        // do not resend of already sent
        $wasNotificationSentAlready = get_post_meta ( $post->ID, 'nowpushNotifys_notification_sent', true );

        if ($wasNotificationSentAlready) {
            nowpushnotifys_log('notification was already sent', ['post' => $post]);
            return;
        }

        $title = $post->post_title;
        $permalink = get_permalink($post->ID);

        $pluginSettings = get_option('now_push_notify_name');

        if (empty($pluginSettings['topicId']) || empty($pluginSettings['apiKey'])) {
            nowpushnotifys_log('config data is missing', $pluginSettings, 'error');
            // TODO https://premium.wpmudev.org/blog/adding-admin-notices/
            return;
        }

        // check if the post type has enabled notifications
        if (empty($pluginSettings['postTypes']) || !in_array($post->post_type, $pluginSettings['postTypes'])) {
        	nowpushnotifys_log('post type not included for notifications', $pluginSettings, 'error');
            return;
        }

        // check if the checkbox was checked for this post to send notification
        if (!isset($_POST['nowpushnotifys-checkbox']) || (isset($_POST['nowpushnotifys-checkbox']) && $_POST['nowpushnotifys-checkbox'] != 1)) {
            nowpushnotifys_log('post should not have notifications sent from checkbox', $pluginSettings, 'error');
            return;
        }

        $data = [
            'title' => get_bloginfo('name'),
            'body' => $title,
            'topicId' => $pluginSettings['topicId'],
            'destinationUrl' => $permalink
        ];

        if (has_post_thumbnail($post->ID)) {
            $thumbnailId = get_post_thumbnail_id($post->ID);
            // requires WP 4.4.0
            $imageUrl = wp_get_attachment_image_url($thumbnailId, [192, 192], true);
            if ($imageUrl) {
                $data['imageUrl'] = $imageUrl;
            }
        }

        nowpushnotifys_log('sending notifiations with data', $data);

        // $url = 'https://skdlxfssth.execute-api.eu-central-1.amazonaws.com/dev/';
        $url = 'https://ktzcwkt3bf.execute-api.eu-central-1.amazonaws.com/dev/';
        // $url = 'https://api.wisenotifications.com/v1/';
        $isNotificationSent = nowpushNotifys_postData($url, $data, $pluginSettings['apiKey']);

        if ($isNotificationSent) {
            update_post_meta( $post->ID, 'nowpushNotifys_notification_sent', true );
        }
    }

    // https://stackoverflow.com/questions/11319520/php-posting-json-via-file-get-contents
    function nowpushNotifys_postData($url, $data, $apiKey)
    {
        $postdata = json_encode($data);
        $args = [
                'blocking' => true,
                'headers' => [
                    'Content-type' => 'application/json',
                    'X-Api-Key' => $apiKey,
                ],
                'body' => $postdata
        ];


        $result = wp_remote_post($url, $args);

        nowpushnotifys_log('notification sending args', $args);
        nowpushnotifys_log('notification sending received headers', $result);

        return 1|| $result['http_response']->get_response_object()->success;
    }
}
