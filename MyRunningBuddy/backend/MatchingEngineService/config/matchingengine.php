<?php

return [
    'RadiusOfSearch' => env('RADIUS_OF_SEARCH', 30),
    'NumberOfRunnersInRadius' => env('NUMBER_OF_RUNNERS_IN_RADIUS', 30),
    'NumberOfTopRunnersForMatching' => env('NUMBER_OF_TOP_RUNNERS_FOR_MATCHING', 10),
    'ScalingFactorForPriorityField' => env('SCALING_FACTOR_FOR_PRIORITY_FIELDS', 2)
];
