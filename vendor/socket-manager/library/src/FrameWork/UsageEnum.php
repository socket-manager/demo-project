<?php
/**
 * Usage定義のENUMファイル
 * 
 * フレームワーク用
 */

namespace SocketManager\Library\FrameWork;


/**
 * Usage定義
 * 
 * フレームワーク用
 */
enum UsageEnum: int
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var string ヘッダ情報共通定義
     */
    private const CONST_HEADER =
<<<EOD
socket-manager Framework \033[32m:version\033[m

\033[33mUsage:\033[m
  command [arguments]


EOD;

    /**
     * @var string セパレータ共通定義
     */
    private const CONST_SEPARATOR =
<<<EOD

--------------------------------------------------------------------------------

EOD;

    /**
     * @var int ヘッダ情報
     */
    case HEADER = 10;

    /**
     * @var int メイン処理コマンド
     */
    case MAIN = 20;

    /**
     * @var int メイン処理識別子
     */
    case MAIN_IDENTIFER = 30;

    /**
     * @var int メイン処理無し表記
     */
    case MAIN_EMPTY = 40;

    /**
     * @var int craftコマンド
     */
    case CRAFT = 50;

    /**
     * @var int Laravelコマンド
     */
    case LARAVEL = 60;

    /**
     * @var int セパレータ（artisanとの境界線）
     */
    case SEPARATOR = 70;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * メッセージの取得
     * 
     * @param string $p_lang 言語
     * @return string メッセージ
     */
    public function message(string $p_lang = 'ja'): string
    {
        if($p_lang === 'ja')
        {
            return match($this)
            {
                self::HEADER => str_replace(':version', SystemEnum::VERSION->value, self::CONST_HEADER),
                self::MAIN => " \033[33mmain\033[m\n",
                self::MAIN_IDENTIFER => "  \033[32m:identifer\033[m",
                self::MAIN_EMPTY => "  \033[34mEmpty...\033[m\n",
                self::CRAFT =>
<<<EOD
 \033[33mcraft\033[m
  \033[32mcraft:init\033[m <初期化クラス名>                     初期化クラスの生成
  \033[32mcraft:parameter\033[m <UNITパラメータクラス名>        UNITパラメータクラスの生成
  \033[32mcraft:protocol\033[m <プロトコルUNIT定義のクラス名>   プロトコルUNIT定義のクラスとステータス名Enumの生成
  \033[32mcraft:command\033[m <コマンドUNIT定義のクラス名>      コマンドUNIT定義のクラスとキュー／ステータス名Enumの生成
  \033[32mcraft:main\033[m <メイン処理のクラス名>               メイン処理クラスの生成

EOD,
                self::LARAVEL =>
<<<EOD
 \033[33mlaravel\033[m
  \033[32mlaravel:command\033[m <メイン処理のクラス名>          Laravelコマンドクラスの生成

EOD,
                self::SEPARATOR => self::CONST_SEPARATOR
            };
        }
        else
        if($p_lang === 'en')
        {
            return match($this)
            {
                self::HEADER => str_replace(':version', SystemEnum::VERSION->value, self::CONST_HEADER),
                self::MAIN => " \033[33mmain\033[m\n",
                self::MAIN_IDENTIFER => "  \033[32m:identifer\033[m",
                self::MAIN_EMPTY => "  \033[34mEmpty...\033[m\n",
                self::CRAFT =>
<<<EOD
 \033[33mcraft\033[m
  \033[32mcraft:init\033[m <initialization class name>                  Generating initialization class
  \033[32mcraft:parameter\033[m <UNIT parameter class name>             Generate UNIT parameter class
  \033[32mcraft:protocol\033[m <Class name of protocol UNIT definition> Generate class and status name Enum for protocol UNIT definition
  \033[32mcraft:command\033[m <Command UNIT definition class name>      Generate command UNIT definition class and queue/status name Enum
  \033[32mcraft:main\033[m <Main processing class name>                 Generating main processing class

EOD,
                self::LARAVEL =>
<<<EOD
 \033[33mlaravel\033[m
  \033[32mlaravel:command\033[m <Main processing class name>            Generating Laravel command class

EOD,
                self::SEPARATOR => self::CONST_SEPARATOR
            };
        }
    }

    /**
     * 置き換えメッセージの取得
     * 
     * @param string $p_str 置き換える文字列
     * @param string $p_lang 言語
     * @return string 置換後のメッセージ
     */
    public function replace(string $p_str, string $p_lang = 'ja'): string
    {
        if($p_lang === 'ja')
        {
            return match($this)
            {
                self::MAIN_IDENTIFER => str_replace(':identifer', $p_str, $this->message($p_lang))
            };
        }
        else
        if($p_lang === 'en')
        {
            return match($this)
            {
                self::MAIN_IDENTIFER => str_replace(':identifer', $p_str, $this->message($p_lang)),
            };
        }
    }
}
