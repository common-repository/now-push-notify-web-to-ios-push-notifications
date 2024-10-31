<?php
function nowpushNotifys_scriptWidget($url)
{
	// requires $url
    if (empty($url)) {
        trigger_error("Now Push Notify - error - missing subscribe URL");
        return;
    }
	wp_enqueue_script("nowpushnotify-widget-lib", "https://notify.nowpush.app/wisenotifications-widget.js", null, false, true);

	$embedJs = '
	wisenotifications && wisenotifications.init({
		subscribeUrl: "' . $url . '"
	});';

	wp_add_inline_script("nowpushnotify-widget-lib", $embedJs);
}