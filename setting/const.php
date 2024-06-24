<?php

return [

    /**
     * @var string ホスト名
     */
    'host' => 'localhost',

    /**
     * @var int ポート番号（UDPマルチサーバーの親ポートと兼用）
     */
    'port' => 10000,

    /**
     * @var int 周期インターバル時間（μs）
     */
    'cycle_interval' => 1000,

    /**
     * @var int アライブチェックタイムアウト時間（s）
     */
    'alive_interval' => 3600,

    /**
     * @var int 親ポート番号（TCPマルチサーバー用）
     */
    'parent_port' => 10010,
];
