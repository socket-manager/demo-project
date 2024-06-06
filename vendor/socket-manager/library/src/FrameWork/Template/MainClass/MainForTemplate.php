<?php
/**
 * メイン処理クラスのファイル
 * 
 * SocketManagerの実行
 */

namespace App\MainClass;

use SocketManager\Library\SocketManager;
use SocketManager\Library\FrameWork\Console;


/**
 * メイン処理クラス
 * 
 * SocketManagerの初期化と実行
 */
class MainForTemplate extends Console
{
    /**
     * @var string $identifer サーバー識別子
     */
    protected string $identifer = 'app:template-server {port_no?}';

    /**
     * @var string $description コマンド説明
     */
    protected string $description = 'Command description';


    /**
     * サーバー起動
     * 
     */
    public function exec()
    {
        // 引数の取得
        $port_no = $this->getParameter('port_no');

        // ソケットマネージャーのインスタンス設定
        $manager = new SocketManager('localhost', $port_no);

        /***********************************************************************
         * ソケットマネージャーの初期設定
         * 
         * プロトコル／コマンド部等で実装したクラスのインスタンスをここで設定します
         **********************************************************************/

        /**
         * 初期化クラスの設定
         * 
         * $manager->setInitSocketManager()メソッドで初期化クラスを設定します
         */

        /**
         * プロトコルUNITの設定
         * 
         * $manager->setProtocolUnits()メソッドでプロトコルUNITクラスを設定します
         */

        /**
         * コマンドUNITの設定
         * 
         * $manager->setCommandUnits()メソッドでコマンドUNITクラスを設定します
         */

        /***********************************************************************
         * ソケットマネージャーの実行
         * 
         * ポートの待ち受け処理や周期ドリブン処理を実行します
         **********************************************************************/

        // リッスンポートで待ち受ける
        $ret = $manager->listen();
        if($ret === false)
        {
            goto finish;   // リッスン失敗
        }

        // ノンブロッキングループ
        while(true)
        {
            // 周期ドリブン
            $ret = $manager->cycleDriven();
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
