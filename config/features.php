<?php

return [
    'beta_trainers' => array_filter(explode(',', env('FEATURE_BETA_TRAINERS', ''))),
    'group_classes_enabled' => env('FEATURE_GROUP_CLASSES', false),
    'in_app_feedback_enabled' => env('FEATURE_IN_APP_FEEDBACK', false),
];
