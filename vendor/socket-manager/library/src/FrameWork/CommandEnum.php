<?php
/**
 * コマンドのENUMファイル
 * 
 * フレームワーク用
 */

namespace SocketManager\Library\FrameWork;


/**
 * コマンドの定義
 * 
 * フレームワーク用
 */
enum CommandEnum: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var 生成アウトプット
     */
    case CRAFT = 'craft';

    /**
     * @var Laravel操作
     */
    case LARAVEL = 'laravel';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コマンド名の取得
     * 
     * @return string コマンド名
     */
    public function name(): string
    {
        return match($this)
        {
            self::CRAFT => self::CRAFT->value,
            self::LARAVEL => self::LARAVEL->value
        };
    }

}
