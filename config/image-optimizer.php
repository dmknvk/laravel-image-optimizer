<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Image optimizer dirs settings
     |--------------------------------------------------------------------------
     |
     | Please fill the array with directories pathes.
     | All images in these folders will be optimized.
     |
     | Example 1:
     | [ public_path('upload'), public_path('media') ]
     | In this example, all png/jpeg images in folders public/upload and public/media will be optimized recursively
     |
     | Example 2:
     | [
     |   public_path('media'),             // like example 1
     |   public_path('upload') => [
     |      'types'     => ['images/png'], // array of mime types, that will be optimized (now supported image/png and image/jpeg)
     |      'recursive' => false,          // search images only in root directory (public/upload)
     |   ],
     | ]
     | This example demonstrates using custom parameters.
     |
     */

    'dirs' => [
    ],

];
