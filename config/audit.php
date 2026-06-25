<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit log owner
    |--------------------------------------------------------------------------
    |
    | The email address of the single account allowed to view the audit logs.
    | This is checked instead of a role/permission so that other super-admins
    | (e.g. the CEO) cannot see the logs. This account's own actions are also
    | excluded from the log. Leave empty to fall back to the
    | "view-audit-logs" permission (legacy behaviour).
    |
    */

    'owner_email' => env('AUDIT_OWNER_EMAIL'),

];
