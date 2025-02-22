<?php
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\nData: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    $log .= "\n-------------------\n";
    error_log($log, 3, __DIR__ . '/debug.log');
}
?>
