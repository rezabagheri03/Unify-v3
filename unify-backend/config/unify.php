<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unify application knobs
    |--------------------------------------------------------------------------
    |
    | App-specific tunables that must survive `config:cache` (hence config
    | entries, not runtime env() calls). Documented in .env.example.
    |
    */

    // Hours a temp enrollment lives before the grace wipe (F20/C3).
    'grace_period_hours' => (int) env('GRACE_PERIOD_HOURS', 24),

    // Hours of staff silence before a ticket escalates (L1; L2 = same again).
    'ticket_escalation_hours' => (int) env('TICKET_ESCALATION_HOURS', 48),

];
