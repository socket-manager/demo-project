<?php
/**
 * サーバー間通信プロトコル（TCP）実装のチャットサーバー
 * 
 * オリジナルプロトコル実装のサンプル
 */

namespace App\MainClass;

use SocketManager\Library\SocketManager;
use SocketManager\Library\FrameWork\Console;

use App\UnitParameter\ParameterForTcpMulti;
use App\InitClass\InitForTcpMulti;
use App\ProtocolUnits\ProtocolForTcpMulti;
use App\CommandUnits\CommandForTcpMulti;

use App\UnitParameter\ParameterForMinecraft;
use App\InitClass\InitForMinecraft;
use App\ProtocolUnits\ProtocolForMinecraft;
use App\CommandUnits\CommandForMinecraft;


/**
 * サーバー間通信プロトコル（TCP）実装のチャットサーバークラス
 * 
 * オリジナルプロトコル実装のサンプル
 */
class ChatServerForTcpMultiMinecraft extends Console
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * @var string コマンド処理の識別子
     */
    protected string $identifer = 'app:tcpmulti-minecraft-server {port?}{parent_port?}';

    /**
     * @var string コマンド説明
     */
    protected string $description = 'TCPマルチマインクラフト版チャットサーバー';

    /**
     * @var string $host ホスト名（リッスン用）
     */
    private string $host = 'localhost';

    /**
     * @var int $port ポート番号（リッスン用）
     */
    private int $port = 10000;

    /**
     * @var int $parent_port 親のポート番号（サーバー間通信用）
     */
    private int $parent_port = 10010;

    /**
     * @var int $cycle_interval 周期インターバル時間（μs）
     */
    private int $cycle_interval = 1000;

    /**
     * @var int $alive_interval アライブチェックタイムアウト時間（μs）
     */
    private int $alive_interval = 3600;

    /**
     * @var ?bool $parent 親フラグ（サーバー間通信用）
     */
    private ?bool $parent = null;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * サーバー起動
     * 
     */
    public function exec()
    {
        //--------------------------------------------------------------------------
        // 設定値の反映
        //--------------------------------------------------------------------------

        // ホスト名の設定
        $this->host = config('const.host');

        // ポート番号の設定
        $this->port = config('const.port');

        // 周期インターバルの設定
        $this->cycle_interval = config('const.cycle_interval');

        // アライブチェックタイムアウト時間の設定
        $this->alive_interval = config('const.alive_interval');

        // 親ポート番号の設定
        $this->parent_port = config('const.parent_port');

        //--------------------------------------------------------------------------
        // 引数の反映
        //--------------------------------------------------------------------------

        // 引数の取得（ポート番号）
        $port = $this->getParameter('port');
        if($port !== null)
        {
            $this->port = $port;
        }

        // 引数の取得（親ポート番号）
        $parent_port = $this->getParameter('parent_port');
        if($parent_port !== null)
        {
            $this->parent_port = $parent_port;
        }

        // 親フラグ
        $this->parent = false;
        if($this->parent_port === ($this->port + 10))
        {
            $this->parent = true;
        }

        //--------------------------------------------------------------------------
        // SocketManagerの初期化
        //--------------------------------------------------------------------------

        // UNITパラメータのインスタンス化
        $websocket_param = new ParameterForMinecraft(); // Websocket用
        $tcpmulti_param = new ParameterForTcpMulti();   // サーバー間通信（TCP）用

        // UNITパラメータの交換設定
        $websocket_param->setChatParameterForServer($tcpmulti_param);       // サーバー間通信（TCP）用インスタンスをWebsocket用インスタンスへ
        $tcpmulti_param->setChatParameterForWebsocket($websocket_param);    // Websocket用インスタンスをサーバー間通信（TCP）用インスタンスへ

        /**
         * Websocket用SocketManagerの設定
         */

        // SocketManagerのインスタンス設定
        $websocket_manager = new SocketManager($this->host, $this->port);

        // SocketManagerの初期設定
        $init = new InitForMinecraft($websocket_param, $this->port);
        $websocket_manager->setInitSocketManager($init);

        // プロトコルUNITの設定
        $protocol_units = new ProtocolForMinecraft();
        $websocket_manager->setProtocolUnits($protocol_units);

        // コマンドUNITの設定
        $command_units = new CommandForMinecraft();
        $websocket_manager->setCommandUnits($command_units);


        /**
         * サーバー間通信用SocketManagerの設定
         */

        // SocketManagerのインスタンス設定
        $tcpmulti_manager = null;
        if($this->parent === true)
        {
            $tcpmulti_manager = new SocketManager($this->host, $this->parent_port);
        }
        else
        {
            $tcpmulti_manager = new SocketManager();
        }

        // SocketManagerの初期設定
        $init = new InitForTcpMulti($tcpmulti_param, $this->port, $this->parent, $this->parent_port);
        $tcpmulti_manager->setInitSocketManager($init);

        // プロトコルUNITの設定
        $protocol_units = new ProtocolForTcpMulti();
        $tcpmulti_manager->setProtocolUnits($protocol_units);

        // コマンドUNITの設定
        $command_units = new CommandForTcpMulti();
        $tcpmulti_manager->setCommandUnits($command_units);

        //--------------------------------------------------------------------------
        // リッスンポートで待ち受ける
        // ※オリジナルプロトコル実装のSocketManagerはListen or Connectで切り分ける
        //--------------------------------------------------------------------------

        $ret = $websocket_manager->listen();
        if($ret === false)
        {
            goto finish;   // リッスン失敗
        }

        if($this->parent === true)
        {
            $ret = $tcpmulti_manager->listen();
            if($ret === false)
            {
                goto finish;   // リッスン失敗
            }
        }
        else
        {
            $w_ret = $tcpmulti_manager->connect($this->host, $this->parent_port);
            if($w_ret === false)
            {
                goto finish;   // 接続失敗
            }
        }

        //--------------------------------------------------------------------------
        // ノンブロッキングループ
        //--------------------------------------------------------------------------

        while(true)
        {
            // 周期ドリブン
            $ret = $websocket_manager->cycleDriven($this->cycle_interval, $this->alive_interval);
            if($ret === false)
            {
                goto finish;
            }
            $ret = $tcpmulti_manager->cycleDriven($this->cycle_interval);
            if($ret === false)
            {
                goto finish;
            }
        }

finish:
        // 全接続クローズ
        $websocket_manager->shutdownAll();
        $tcpmulti_manager->shutdownAll();
    }

}
