<?php
/**
 * 例外コード定義のENUMファイル
 * 
 * UNIT用
 */

namespace SocketManager\Library;


/**
 * 例外コード定義
 * 
 * UNIT用
 */
enum UnitExceptionEnum: int
{
    //--------------------------------------------------------------------------
    // 定数（ステータスUNIT共通）
    //--------------------------------------------------------------------------

    /**
     * @var int ハンドシェイク失敗
     */
    case ECODE_HANDSHAKE_FAIL = 10;

    /**
     * @var int ユーザープロパティ取得失敗
     */
    case ECODE_USER_PROPERTY_GET_FAIL = 20;

    /**
     * @var int ユーザープロパティ設定失敗
     */
    case ECODE_USER_PROPERTY_SET_FAIL = 30;

    /**
     * @var int プロパティ取得失敗
     */
    case ECODE_PROPERTY_GET_FAIL = 40;

    /**
     * @var int プロパティ設定失敗
     */
    case ECODE_PROPERTY_SET_FAIL = 50;

    /**
     * @var int データ受信サイズ設定失敗
     */
    case ECODE_RECEIVING_SIZE_SET_FAIL = 60;

    /**
     * @var int データ受信失敗
     */
    case ECODE_RECEIVING_FAIL = 70;

    /**
     * @var int 受信データのスタック失敗
     */
    case ECODE_RECEIVE_DATA_STACK_FAIL = 80;

    /**
     * @var int 送信データの設定失敗
     */
    case ECODE_SENDING_DATA_SET_FAIL = 90;

    /**
     * @var int データ送信失敗
     */
    case ECODE_SENDING_FAIL = 100;

    /**
     * @var int ピックアップ受信データ取得失敗
     */
    case ECODE_PICKUP_RECEIVE_DATA_GET_FAIL = 110;

    /**
     * @var int ピックアップ受信データ設定失敗
     */
    case ECODE_PICKUP_RECEIVE_DATA_SET_FAIL = 120;

    /**
     * @var int ピックアップ送信データ取得失敗
     */
    case ECODE_PICKUP_SEND_DATA_GET_FAIL = 130;

    /**
     * @var int メソッドコール失敗
     */
    case ECODE_METHOD_CALL_FAIL = 140;

    /**
     * @var int 送信データのスタック失敗
     */
    case ECODE_SEND_DATA_STACK_FAIL = 150;

    /**
     * @var int 切断シーケンス実行失敗
     */
    case ECODE_CLOSE_FAIL = 160;

    /**
     * @var int 例外メッセージ取得失敗
     */
    case ECODE_EX_MESSAGE_GET_FAIL = 170;

    /**
     * @var int コマンド不一致
     */
    case ECODE_COMMAND_MISMATCH = 180;

    /**
     * @var int アライブチェックの実行失敗
     */
    case ECODE_ALIVE_CHECK_FAIL = 190;

    /**
     * @var int 緊急停止
     */
    case ECODE_EMERGENCY_SHUTDOWN = 9000;

    /**
     * @var int スローブレイク時に発行する例外コード
     */
    case ECODE_THROW_BREAK = 9999;


    //--------------------------------------------------------------------------
    // 定数（プロトコルUNIT用）
    //--------------------------------------------------------------------------

    /**
     * @var int クライアント要求による切断
     */
    case ECODE_REQUEST_CLOSE = 1010;

    /**
     * @var int クライアント強制要求による切断
     */
    case ECODE_FORCE_CLOSE = 1020;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * 例外メッセージの取得
     * 
     * @param string $p_lang 言語
     * @return string 例外メッセージ
     */
    public function message(string $p_lang = 'ja'): string
    {
        if($p_lang === 'ja')
        {
            return match($this)
            {
                self::ECODE_HANDSHAKE_FAIL => 'ハンドシェイク失敗',
                self::ECODE_USER_PROPERTY_GET_FAIL => 'ユーザープロパティ取得失敗',
                self::ECODE_USER_PROPERTY_SET_FAIL => 'ユーザープロパティ設定失敗',
                self::ECODE_PROPERTY_GET_FAIL => 'プロパティ取得失敗',
                self::ECODE_PROPERTY_SET_FAIL => 'プロパティ設定失敗',
                self::ECODE_RECEIVING_SIZE_SET_FAIL => 'データ受信サイズ設定失敗',
                self::ECODE_RECEIVING_FAIL => 'データ受信失敗',
                self::ECODE_RECEIVE_DATA_STACK_FAIL => '受信データのスタック失敗',
                self::ECODE_SENDING_DATA_SET_FAIL => '送信データの設定失敗',
                self::ECODE_SENDING_FAIL => 'データ送信失敗',
                self::ECODE_PICKUP_RECEIVE_DATA_GET_FAIL => 'ピックアップ受信データ取得失敗',
                self::ECODE_PICKUP_RECEIVE_DATA_SET_FAIL => 'ピックアップ受信データ設定失敗',
                self::ECODE_PICKUP_SEND_DATA_GET_FAIL => 'ピックアップ送信データ取得失敗',
                self::ECODE_METHOD_CALL_FAIL => 'メソッドコール失敗',
                self::ECODE_SEND_DATA_STACK_FAIL => '送信データのスタック失敗',
                self::ECODE_CLOSE_FAIL => '切断シーケンス実行失敗',
                self::ECODE_EX_MESSAGE_GET_FAIL => '例外メッセージ取得失敗',
                self::ECODE_COMMAND_MISMATCH => 'コマンド不一致',
                self::ECODE_REQUEST_CLOSE => 'クライアント要求による切断',
                self::ECODE_FORCE_CLOSE => 'クライアント強制要求による切断',
                self::ECODE_ALIVE_CHECK_FAIL => 'アライブチェックの実行失敗',
                self::ECODE_EMERGENCY_SHUTDOWN => '緊急停止',
                self::ECODE_THROW_BREAK => 'スローブレイク発生',
                default => '存在しないEnum値です'
            };
        }
        else
        if($p_lang === 'en')
        {
            return match($this)
            {
                self::ECODE_HANDSHAKE_FAIL => 'handshake failure',
                self::ECODE_USER_PROPERTY_GET_FAIL => 'Failed to get user properties',
                self::ECODE_USER_PROPERTY_SET_FAIL => 'User property setting failure',
                self::ECODE_PROPERTY_GET_FAIL => 'Property acquisition failure',
                self::ECODE_PROPERTY_SET_FAIL => 'Property setting failed',
                self::ECODE_RECEIVING_SIZE_SET_FAIL => 'Data reception size setting failure',
                self::ECODE_RECEIVING_FAIL => 'Data reception failure',
                self::ECODE_RECEIVE_DATA_STACK_FAIL => 'Receive data stack failure',
                self::ECODE_SENDING_DATA_SET_FAIL => 'Send data setting failure',
                self::ECODE_SENDING_FAIL => 'Data transmission failure',
                self::ECODE_PICKUP_RECEIVE_DATA_GET_FAIL => 'Pickup reception data acquisition failure',
                self::ECODE_PICKUP_RECEIVE_DATA_SET_FAIL => 'Pickup reception data setting failure',
                self::ECODE_PICKUP_SEND_DATA_GET_FAIL => 'Pickup transmission data acquisition failure',
                self::ECODE_METHOD_CALL_FAIL => 'Method call failure',
                self::ECODE_SEND_DATA_STACK_FAIL => 'Send data stack failure',
                self::ECODE_CLOSE_FAIL => 'Disconnection sequence execution failure',
                self::ECODE_EX_MESSAGE_GET_FAIL => 'Failed to get exception message',
                self::ECODE_COMMAND_MISMATCH => 'Command mismatch',
                self::ECODE_REQUEST_CLOSE => 'Disconnection due to client request',
                self::ECODE_FORCE_CLOSE => 'Disconnection due to client forced request',
                self::ECODE_ALIVE_CHECK_FAIL => 'Alive check execution failure',
                self::ECODE_EMERGENCY_SHUTDOWN => 'emergency stop',
                self::ECODE_THROW_BREAK => 'Throw break occurs',
                default => 'Enum value that does not exist'
            };
        }

        return null;
    }

}
