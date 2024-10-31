<?php

global $nowpushnotifys_logs;
$nowpushnotifys_logs = [];

nowpushnotifys_enableLogging();

function nowpushnotifys_log($message, $obj = null, $severity = 'log')
{
    global $nowpushnotifys_logs;

    if (WP_DEBUG || WP_DEBUG_LOG) {
        $logMsg = date('c') . ' - ' . $severity . ' - ' . $message . ' details: ' . json_encode($obj);
        $nowpushnotifys_logs[] = $logMsg;
    }
}

function nowpushnotifys_enableLogging()
{
    add_action('shutdown', 'nowpushnotifys_saveLogs');
}


function nowpushnotifys_saveLogs()
{
    global $nowpushnotifys_logs;

    if ((WP_DEBUG || WP_DEBUG_LOG) && !empty($nowpushnotifys_logs) && count($nowpushnotifys_logs)) {
        try {
            foreach ($nowpushnotifys_logs as $log) {
                error_log('nowpushnotifys: ' . $log);
            }
        } catch (\Thrrowable $e) {
        }
    }
}
