<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facebook Graph API
    |--------------------------------------------------------------------------
    |
    | Configure your Facebook Developer App credentials here. These are used
    | to authenticate with the Facebook Graph API for posting and marketing.
    |
    */

    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v21.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram Graph API
    |--------------------------------------------------------------------------
    |
    | Instagram Graph API uses the same Facebook App credentials. Configure
    | additional Instagram-specific settings here.
    |
    */

    'instagram' => [
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v21.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Storage
    |--------------------------------------------------------------------------
    |
    | Configure where social media files are stored. Default is local storage
    | in storage/app/social-media/.
    |
    */

    'media_disk' => env('SOCIAL_MEDIA_DISK', 'social'),

    /*
    |--------------------------------------------------------------------------
    | Post Publishing
    |--------------------------------------------------------------------------
    |
    | Configure post publishing behavior including retry limits and queue
    | settings for async publishing.
    |
    */

    'publishing' => [
        'max_retries' => env('SOCIAL_PUBLISH_MAX_RETRIES', 3),
        'queue' => env('SOCIAL_PUBLISH_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Sync
    |--------------------------------------------------------------------------
    |
    | Configure how often campaign insights are synced from the API.
    |
    */

    'campaign_sync' => [
        'interval_minutes' => env('SOCIAL_CAMPAIGN_SYNC_INTERVAL', 60),
    ],

];
