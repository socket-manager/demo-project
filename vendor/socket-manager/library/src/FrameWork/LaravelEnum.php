<?php
/**
 * laravelタイプのENUMファイル
 * 
 * フレームワーク用
 */

namespace SocketManager\Library\FrameWork;


/**
 * laravelタイプの定義
 * 
 * フレームワーク用
 */
enum LaravelEnum: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var コマンドクラス
     */
    case COMMAND = 'command';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * エイリアス名の取得
     * 
     * @return string エイリアス名
     */
    public function alias(): string
    {
        return match($this)
        {
            self::COMMAND => SuccessEnum::COMMAND_FOR_LARAVEL->value
        };
    }

    /**
     * ディレクトリ名の取得（取得元）
     * 
     * @return string ディレクトリ名
     */
    public function srcDirectory(): string
    {
        return match($this)
        {
            self::COMMAND => 'MainClass'
        };
    }

    /**
     * ディレクトリ名の取得（出力先）
     * 
     * @return string ディレクトリ名
     */
    public function dstDirectory(): string
    {
        return match($this)
        {
            self::COMMAND => 'Console'.DIRECTORY_SEPARATOR.'Commands'
        };
    }
}
