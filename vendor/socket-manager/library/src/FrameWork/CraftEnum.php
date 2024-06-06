<?php
/**
 * craftタイプのENUMファイル
 * 
 * フレームワーク用
 */

namespace SocketManager\Library\FrameWork;


/**
 * craftタイプの定義
 * 
 * フレームワーク用
 */
enum CraftEnum: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var 初期化クラス
     */
    case INIT = 'init';

    /**
     * @var UNITパラメータクラス
     */
    case PARAMETER = 'parameter';

    /**
     * @var プロトコルUNITクラス
     */
    case PROTOCOL = 'protocol';

    /**
     * @var コマンドUNITクラス
     */
    case COMMAND = 'command';

    /**
     * @var メインクラス
     */
    case MAIN = 'main';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * ディレクトリ名の取得
     * 
     * @return string ディレクトリ名
     */
    public function directory(): string
    {
        return match($this)
        {
            self::INIT => 'InitClass',
            self::PARAMETER => 'UnitParameter',
            self::PROTOCOL => 'ProtocolUnits',
            self::COMMAND => 'CommandUnits',
            self::MAIN => 'MainClass'
        };
    }

    /**
     * クラス名の取得
     * 
     * @return string クラス名
     */
    public function class(): string
    {
        return match($this)
        {
            self::INIT => 'InitForTemplate',
            self::PARAMETER => 'ParameterForTemplate',
            self::PROTOCOL => 'ProtocolForTemplate',
            self::COMMAND => 'CommandForTemplate',
            self::MAIN => 'MainForTemplate'
        };
    }

    /**
     * Enum名の取得（キュー定義）
     * 
     * @return string Enum名（キュー定義）
     */
    public function enumQueue(): string
    {
        return match($this)
        {
            self::PROTOCOL => 'ProtocolQueueEnumForTemplate',
            self::COMMAND => 'CommandQueueEnumForTemplate'
        };
    }

    /**
     * Enum名の取得（ステータス定義）
     * 
     * @return string Enum名（ステータス定義）
     */
    public function enumStatus(): string
    {
        return match($this)
        {
            self::PROTOCOL => 'ProtocolStatusEnumForTemplate',
            self::COMMAND => 'CommandStatusEnumForTemplate'
        };
    }
}
