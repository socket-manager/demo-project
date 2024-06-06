<?php
/**
 * SocketManager初期化クラスのファイル
 * 
 * SocketManagerのsetInitSocketManagerメソッドへ引き渡される初期化クラスのファイル
 */

namespace App\InitClass;

use App\UnitParameter\ParameterForUdpMulti;


/**
 * SocketManager初期化クラス
 * 
 * IInitSocketManagerインタフェースをインプリメントする
 */
class InitForUdpMulti extends InitForTcpMulti
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コマンドディスパッチャーの取得
     * 
     * 受信データからコマンドを解析して返す
     * 
     * コマンドUNIT実行中に受信データが溜まっていた場合でもコマンドUNITの処理が完了するまで
     * 待ってから起動されるため処理競合の調停役を兼ねる
     * 
     * nullを返す場合は無効化となる。エラー発生時はUnitExceptionクラスで例外をスローして切断する。
     * 
     * @return mixed "function(SocketManagerParameter $p_param, mixed $p_dat): ?string" or null（変更なし）
     */
    public function getCommandDispatcher()
    {
        return function(ParameterForUdpMulti $p_param, $p_dat): ?string
        {
            return $p_dat['cmd'];
        };
    }

}
