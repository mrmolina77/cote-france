<?php

return [
    /*
     * Los temporales nunca heredan FILESYSTEM_DISK: podría ser "public".
     * Livewire elimina estos objetos temporales según su ciclo normal.
     */
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => null,
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv',
            'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
    ],
];
