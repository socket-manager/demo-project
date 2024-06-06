<?php
/**
 * エラーメッセージのENUMファイル
 * 
 * ライブラリ用
 */

namespace SocketManager\Library;


use Socket;


/**
 * エラーメッセージ定義
 * 
 * ライブラリ用
 */
enum LogMessageEnum
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var ソケットエラー
     */
    case SOCKET_ERROR;

    /**
     * @var アライブチェックタイムアウト
     */
    case ALIVE_CHECK_TIMEOUT;

    /**
     * @var アライブチェック開始までのタイムアウトが発生
     */
    case ALIVE_CHECK_START_TIMEOUT;

    /**
     * @var ソケット生成に失敗
     */
    case SOCKET_CREATE_FAIL;

    /**
     * @var キュー処理開始設定に失敗
     */
    case QUEUE_START_FAIL;

    /**
     * @var ソケットオプションの設定に失敗
     */
    case SOCKET_OPTION_SETTING_FAIL;

    /**
     * @var 処理対象のソケットが存在しない
     */
    case SOCKET_NO_COUNT;

    /**
     * @var 受信サイズが未設定
     */
    case RECEIVE_SIZE_NO_SETTING;

    /**
     * @var 送信データが未設定
     */
    case SEND_DATA_NO_SETTING;

    /**
     * @var 処理対象のUNITが未登録
     */
    case UNIT_NO_SETTING;

    /**
     * @var ノンブロック設定失敗
     */
    case NONBLOCK_SETTING_FAIL;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * エラーメッセージの取得
     * 
     * @param string $p_lang 言語
     * @return string エラーメッセージ
     */
    public function message(string $p_lang = 'ja'): string
    {
        if($p_lang === 'ja')
        {
            return match($this)
            {
                self::ALIVE_CHECK_TIMEOUT => 'アライブチェックタイムアウト発生',
                self::ALIVE_CHECK_START_TIMEOUT => 'アライブチェック開始までのタイムアウトが発生',
                self::SOCKET_CREATE_FAIL => 'ソケット生成に失敗',
                self::QUEUE_START_FAIL => 'キュー処理開始設定に失敗',
                self::SOCKET_OPTION_SETTING_FAIL => 'ソケットオプションの設定に失敗',
                self::SOCKET_NO_COUNT => '処理対象のソケットが存在しない',
                self::RECEIVE_SIZE_NO_SETTING => '受信サイズが未設定',
                self::SEND_DATA_NO_SETTING => '送信データが未設定',
                self::UNIT_NO_SETTING => '処理対象のUNITが未登録',
                self::NONBLOCK_SETTING_FAIL => 'ノンブロック設定失敗',
                default => '存在しないEnum値です'
            };
        }
        else
        if($p_lang === 'en')
        {
            return match($this)
            {
                self::ALIVE_CHECK_TIMEOUT => 'Alive check timeout occurred',
                self::ALIVE_CHECK_START_TIMEOUT => 'Timeout occurred before alive checking started',
                self::SOCKET_CREATE_FAIL => 'Failed to create socket',
                self::QUEUE_START_FAIL => 'Queue processing start setting failed',
                self::SOCKET_OPTION_SETTING_FAIL => 'Failed to set socket options',
                self::SOCKET_NO_COUNT => 'No socket to process exists',
                self::RECEIVE_SIZE_NO_SETTING => 'Reception size not set',
                self::SEND_DATA_NO_SETTING => 'Transmission data not set',
                self::UNIT_NO_SETTING => 'UNIT to be processed is not registered',
                self::NONBLOCK_SETTING_FAIL => 'Non-blocking setting failed',
                default => 'Enum value that does not exist'
            };
        }
    }

    /**
     * ソケット用エラーメッセージの取得
     * 
     * @param Socket $p_soc ソケットリソース
     * @return string エラーメッセージ
     */
    public function socket(Socket $p_soc = null): string
    {
        $cod = socket_last_error($p_soc);
        return match($this)
        {
            self::SOCKET_ERROR => "[{$cod}]".socket_strerror($cod),
            default => 'Enum value that does not exist'
        };
    }

    /**
     * ソケット用エラー情報の取得
     * 
     * @param Socket $p_soc ソケットリソース
     * @return array ['code' => <コード>, 'message' => <メッセージ>]
     */
    public function array(Socket $p_soc = null): array
    {
        $cod = socket_last_error($p_soc);
        $message = $this->socket($p_soc);

        return ['code' => $cod, 'message' => $message];
    }

}
