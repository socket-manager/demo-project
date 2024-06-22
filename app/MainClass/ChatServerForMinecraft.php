<?php
/**
 * チャットサーバークラスのファイル
 * 
 * Websocket（マインクラフト用）プロトコル対応
 */

namespace App\MainClass;


use SocketManager\Library\SocketManager;
use SocketManager\Library\FrameWork\Console;

use App\UnitParameter\ParameterForMinecraft;
use App\InitClass\InitForMinecraft;
use App\ProtocolUnits\ProtocolForMinecraft;
use App\CommandUnits\CommandForMinecraft;


/**
 * チャットサーバークラス
 * 
 * Websocket（マインクラフト用）プロトコル対応
 */
class ChatServerForMinecraft extends Console
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
    protected string $identifer = 'app:minecraft-chat-server {port?}';

    /**
     * @var string コマンド説明
     */
    protected string $description = 'マインクラフト版チャットサーバー';

    /**
     * @var string $host ホスト名（リッスン用）
     */
    private string $host = 'localhost';

    /**
     * @var int $port ポート番号（リッスン用）
     */
    private int $port = 10000;

    /**
     * @var int $cycle_interval 周期インターバル時間（μs）
     */
    private int $cycle_interval = 1000;

    /**
     * @var int $alive_interval アライブチェックタイムアウト時間（μs）
     */
    private int $alive_interval = 3600;


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

        //--------------------------------------------------------------------------
        // 引数の反映
        //--------------------------------------------------------------------------

        // ポート番号の設定
        $port = $this->getParameter('port');
        if($port !== null)
        {
            $this->port = $port;
        }

        //--------------------------------------------------------------------------
        // SocketManagerの初期化
        //--------------------------------------------------------------------------

        // ソケットマネージャーのインスタンス設定
        $manager = new SocketManager($this->host, $this->port);

        // UNITパラメータインスタンスの設定
        $param = new ParameterForMinecraft();

        // SocketManagerの設定値初期設定
        $init = new InitForMinecraft($param, $this->port);
        $manager->setInitSocketManager($init);

        // プロトコルUNITの設定
        $entry = new ProtocolForMinecraft();
        $manager->setProtocolUnits($entry);

        // コマンドUNITの設定
        $entry = new CommandForMinecraft();
        $manager->setCommandUnits($entry);

        //--------------------------------------------------------------------------
        // リッスンポートで待ち受ける
        //--------------------------------------------------------------------------

        $ret = $manager->listen();
        if($ret === false)
        {
            goto finish;   // リッスン失敗
        }

        //--------------------------------------------------------------------------
        // ノンブロッキングループ
        //--------------------------------------------------------------------------

        while(true)
        {
            // 周期ドリブン
            $ret = $manager->cycleDriven($this->cycle_interval, $this->alive_interval);
            if($ret === false)
            {
                goto finish;
            }
        }

finish:
        // 全接続クローズ
        $manager->shutdownAll();
    }

}
