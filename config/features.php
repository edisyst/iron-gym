<?php

return [
    'beta_trainers' => array_filter(explode(',', env('FEATURE_BETA_TRAINERS', ''))),
    'group_classes_enabled' => env('FEATURE_GROUP_CLASSES', false),
];
