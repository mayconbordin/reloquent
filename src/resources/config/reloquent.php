<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When in debug mode, the executed SQL queries will be logged in the log
    | file.
    |
    */
    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Log File
    |--------------------------------------------------------------------------
    |
    | The full path to the log file.
    |
    */
    'log_file' => storage_path('logs/reloquent.log'),

    /*
    |--------------------------------------------------------------------------
    | Record Limit
    |--------------------------------------------------------------------------
    |
    | Used only on verbose calls where the 'Limit' keyword is used without a
    | parameter.
    |
    | Ex.: $repository->findAllByCategoryIdOrderByIdLimit(1);
    |
    */
    'limit' => 20,

    /*
    |--------------------------------------------------------------------------
    | Pagination Parameters
    |--------------------------------------------------------------------------
    |
    | Defines the default number of results per page.
    |
    */
    'pagination' => [
        'per_page' => 15
    ]
];