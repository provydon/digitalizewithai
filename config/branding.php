<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application display name
    |--------------------------------------------------------------------------
    |
    | Shown in the UI (welcome page, sidebar, etc.). Uses APP_NAME by default.
    | Change APP_NAME when rebranding (e.g. for product launch).
    |
    */

    'name' => env('APP_NAME', 'Digitalize with AI'),

    /*
    |--------------------------------------------------------------------------
    | AI attribution (e.g. for hackathon)
    |--------------------------------------------------------------------------
    |
    | When set, the app shows "Powered by {value}" and uses this name in copy.
    | Set to empty or remove to run as a standalone product (no partner branding).
    |
    | Hackathon: APP_AI_ATTRIBUTION=Amazon Nova
    | Product:   APP_AI_ATTRIBUTION=  (or omit) — default: standalone, no partner branding.
    |
    */

    'ai_attribution' => env('APP_AI_ATTRIBUTION', ''),

];
