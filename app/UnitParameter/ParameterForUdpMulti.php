<?php
/**
 * UNITパラメータクラスのファイル
 * 
 * UDPマルチサーバー用
 */

namespace App\UnitParameter;


/**
 * UNITパラメータクラス
 * 
 * ParameterForTcpMultiクラスをオーバーライドしてUDP版として利用
 */
class ParameterForUdpMulti extends ParameterForTcpMulti
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
     * コンストラクタ
     * 
     */
    public function __construct()
    {
        parent::__construct();
    }

}
