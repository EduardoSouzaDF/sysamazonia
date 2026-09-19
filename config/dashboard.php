<?php

return [
    // Zero disables caching; the service caps the lifetime at five minutes.
    'cache_ttl' => (int) env('DASHBOARD_CACHE_TTL', 30),
];
