<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Absence alerts
    |--------------------------------------------------------------------------
    |
    | Telling a guardian the same morning that their child is not in school.
    | The alert itself is always recorded in `attendance_absence_alerts`; this
    | only decides what carries it.
    |
    | `log` writes the message to the log, which is the default so the pipeline
    | is complete and testable before an SMS provider is chosen. Adding a
    | provider means one more case in `AbsenceAlertService::send()`; nothing
    | that records or reads the alerts changes.
    |
    */

    'alerts' => [
        'driver' => env('ATTENDANCE_ALERT_DRIVER', 'log'),
        'log_channel' => env('ATTENDANCE_ALERT_LOG_CHANNEL', 'stack'),
    ],

];
