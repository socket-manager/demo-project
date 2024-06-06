<?php
/**
 * サーバー間通信プロトコル（UDP）実装のチャットサーバー
 * 
 * オリジナルプロトコル実装のサンプル
 */

namespace App\MainClass;

use SocketManager\Library\SocketManager;
use SocketManager\Library\FrameWork\Console;

use App\UnitParameter\ParameterForUdpMulti;
use App\InitClass\InitForUdpMulti;
use App\ProtocolUnits\ProtocolForUdpMulti;
use App\CommandUnits\CommandForUdpMulti;

use App\UnitParameter\ParameterForWebsocket;
use App\InitClass\InitForWebsocket;
use App\ProtocolUnits\ProtocolForWebsocket;
use App\CommandUnits\CommandForWebsocket;


/**
 * サーバー間通信プロトコル（UDP）実装のチャットサーバー
 * 
 * オリジナルプロトコル実装のサンプル
 */
class ChatServerForUdpMulti extends Console
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
    protected string $identifer = 'app:udpmulti-server {port?}{parent_port?}';

    /**
     * @var string コマンド説明
     */
    protected string $description = 'UDPマルチチャットサーバー';

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
    private int $parent_port = 10000;

    /**
     * @var int $cycle_interval 周期インターバル時間（μs）
     */
    private int $cycle_interval = 1000;

    /**
     * @var int $alive_interval アライブチェックタイムアウト時間（μs）
     */
    private int $alive_interval = 3600;

    /**
     * 親フラグ（サーバー間通信用）
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
        if($this->parent_port === $this->port)
        {
            $this->parent = true;
        }

        //--------------------------------------------------------------------------
        // 初期化
        //--------------------------------------------------------------------------

        // UNITパラメータのインスタンス化
        $websocket_param = new ParameterForWebsocket(); // Websocket用
        $udpmulti_param = new ParameterForUdpMulti();   // サーバー間通信（UDP）用

        // UNITパラメータの交換設定
        $websocket_param->setChatParameterForServer($udpmulti_param);       // サーバー間通信（UDP）用インスタンスをWebsocket用インスタンスへ
        $udpmulti_param->setChatParameterForWebsocket($websocket_param);    // Websocket用インスタンスをサーバー間通信（UDP）用インスタンスへ

        /**
         * Websocket用SocketManagerの設定
         */

        // SocketManagerのインスタンス設定
        $websocket_manager = new SocketManager($this->host, $this->port);

        // SocketManagerの初期設定
        $init = new InitForWebsocket($websocket_param, $this->port);
        $websocket_manager->setInitSocketManager($init);

        // プロトコルUNITの設定
        $protocol_units = new ProtocolForWebsocket();
        $websocket_manager->setProtocolUnits($protocol_units);

        // コマンドUNITの設定
        $command_units = new CommandForWebsocket();
        $websocket_manager->setCommandUnits($command_units);


        /**
         * サーバー間通信用SocketManagerの設定
         */

        // SocketManagerのインスタンス設定
        $udpmulti_manager = new SocketManager($this->host, $this->port);

        // SocketManagerの初期設定
        $init = new InitForUdpMulti($udpmulti_param, $this->port, $this->parent, $this->parent_port);
        $udpmulti_manager->setInitSocketManager($init);

        // プロトコルUNITの設定
        $protocol_units = new ProtocolForUdpMulti();
        $udpmulti_manager->setProtocolUnits($protocol_units);

        // コマンドUNITの設定
        $command_units = new CommandForUdpMulti();
        $udpmulti_manager->setCommandUnits($command_units);

        //--------------------------------------------------------------------------
        // リッスンポートで待ち受ける
        // ※オリジナルプロトコル実装のSocketManagerはBind or Connectで切り分ける
        //--------------------------------------------------------------------------

        $ret = $websocket_manager->listen();
        if($ret === false)
        {
            goto finish;   // リッスン失敗
        }

        if($this->parent === true)
        {
            $ret = $udpmulti_manager->bind();
            if($ret === false)
            {
                goto finish;   // BIND失敗
            }
        }
        else
        {
            $w_ret = $udpmulti_manager->connect($this->host, $this->parent_port, true);
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
            $ret = $udpmulti_manager->cycleDriven($this->cycle_interval);
            if($ret === false)
            {
                goto finish;
            }
        }

finish:
        // 全接続クローズ
        $websocket_manager->shutdownAll();
        $udpmulti_manager->shutdownAll();
    }

}
