<?php

return [
    'max_pending' => (int) env('VOTER_IMPORT_MAX_PENDING', 50),
    'max_pending_per_user' => (int) env('VOTER_IMPORT_MAX_PENDING_PER_USER', 5),
    'retention_days' => (int) env('VOTER_IMPORT_RETENTION_DAYS', 7),
    'node_memory_mb' => (int) env('VOTER_IMPORT_NODE_MEMORY_MB', 256),
];
