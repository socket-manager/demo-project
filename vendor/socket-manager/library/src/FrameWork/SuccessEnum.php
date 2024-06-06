<?php
/**
 * 成功コード定義のENUMファイル
 * 
 * フレームワークのコマンド実行用
 */

namespace SocketManager\Library\FrameWork;


/**
 * 成功コード定義
 * 
 * フレームワークのコマンド実行用
 */
enum SuccessEnum: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var string 初期化クラス生成
     */
    case INIT = CraftEnum::INIT->value;

    /**
     * @var string UNITパラメータクラス生成
     */
    case PARAMETER = CraftEnum::PARAMETER->value;

    /**
     * @var string プロトコルUNITクラス生成
     */
    case PROTOCOL = CraftEnum::PROTOCOL->value;

    /**
     * @var string プロトコルUNITのキュー名Enum生成
     */
    case PROTOCOL_QUEUE_ENUM = CraftEnum::PROTOCOL->value.'_queue_enum';

    /**
     * @var string プロトコルUNITのステータス名Enum生成
     */
    case PROTOCOL_STATUS_ENUM = CraftEnum::PROTOCOL->value.'_status_enum';

    /**
     * @var string コマンドUNITクラス生成
     */
    case COMMAND = CraftEnum::COMMAND->value;

    /**
     * @var string コマンドUNITのキュー名Enum生成
     */
    case COMMAND_QUEUE_ENUM = CraftEnum::COMMAND->value.'_queue_enum';

    /**
     * @var string コマンドUNITのステータス名Enum生成
     */
    case COMMAND_STATUS_ENUM = CraftEnum::COMMAND->value.'_status_enum';

    /**
     * @var string メイン処理クラス生成
     */
    case MAIN = CraftEnum::MAIN->value;

    /**
     * @var string コマンドクラス生成（Laravel用）
     */
    case COMMAND_FOR_LARAVEL = 'command_for_laravel';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * メッセージの取得
     * 
     * @param string $p_file ファイル名
     * @param string $p_lang 言語
     * @return string メッセージ
     */
    public function message(string $p_file = null, string $p_lang = 'ja'): string
    {
        $msg = null;
        if($p_lang === 'ja')
        {
            $msg = match($this)
            {
                self::INIT => '初期化クラスの生成に成功しました',
                self::PARAMETER => 'UNITパラメータクラスの生成に成功しました',
                self::PROTOCOL => 'プロトコルUNITクラスの生成に成功しました',
                self::PROTOCOL_QUEUE_ENUM => 'プロトコルUNITのキュー名Enumの生成に成功しました',
                self::PROTOCOL_STATUS_ENUM => 'プロトコルUNITのステータス名Enumの生成に成功しました',
                self::COMMAND => 'コマンドUNITクラスの生成に成功しました',
                self::COMMAND_QUEUE_ENUM => 'コマンドUNITのキュー名Enumの生成に成功しました',
                self::COMMAND_STATUS_ENUM => 'コマンドUNITのステータス名Enumの生成に成功しました',
                self::MAIN => 'メイン処理クラスの生成に成功しました',
                self::COMMAND_FOR_LARAVEL => 'Laravelコマンドクラスの生成に成功しました'
            };
        }
        else
        if($p_lang === 'en')
        {
            $msg = match($this)
            {
                self::INIT => 'Successfully generated initialization class',
                self::PARAMETER => 'Successfully generated UNIT parameter class',
                self::PROTOCOL => 'Successfully generated protocol UNIT class',
                self::PROTOCOL_QUEUE_ENUM => 'Successfully generated queue name Enum for protocol UNIT',
                self::PROTOCOL_STATUS_ENUM => 'Successfully generated status name Enum for protocol UNIT',
                self::COMMAND => 'Successfully generated command UNIT class',
                self::COMMAND_QUEUE_ENUM => 'Successfully generated queue name Enum for command UNIT',
                self::COMMAND_STATUS_ENUM => 'Successfully generated status name Enum for command UNIT',
                self::MAIN => 'Successfully generated main processing class',
                self::COMMAND_FOR_LARAVEL => 'Successfully generated Laravel command class'
            };
        }

        // ファイル名を追加
        if($p_file !== null)
        {
            $msg .= ' '."\033[33m({$p_file})\033[m";
        }
        return $msg;
    }

    /**
     * メッセージ表示
     * 
     * @param string $p_file ファイル名
     * @param string $p_lang 言語
     */
    public function display(string $p_file = null, string $p_lang = 'ja')
    {
        printf("\033[32m[\033[m\033[32msuccess\033[m\033[32m]\033[m %s\n", $this->message($p_file, $p_lang));
    }
}
