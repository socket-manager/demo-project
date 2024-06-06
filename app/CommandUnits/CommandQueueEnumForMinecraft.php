<?php
/**
 * コマンド部のキュー名のENUMファイル
 * 
 * マインクラフト用
 */

namespace App\CommandUnits;


/**
 * コマンド部のキュー名定義
 * 
 * マインクラフト用
 */
enum CommandQueueEnumForMinecraft: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var 入室時のキュー名
     */
    case ENTRANCE = CommandQueueEnumForWebsocket::ENTRANCE->value;

    /**
     * @var 入室待機時のキュー名
     */
    case ENTRANCE_WAITING = 'entrance-waiting';

    /**
     * @var チャットコメント時のキュー名
     */
    case MESSAGE = CommandQueueEnumForWebsocket::MESSAGE->value;

    /**
     * @var 退室時のキュー名
     */
    case EXIT = CommandQueueEnumForWebsocket::EXIT->value;

    /**
     * @var クライアント要求切断時のキュー名
     */
    case CLOSE = CommandQueueEnumForWebsocket::CLOSE->value;

    /**
     * @var プライベートコメント送信時のキュー名
     */
    case PRIVATE = CommandQueueEnumForWebsocket::PRIVATE->value;

    /**
     * @var プライベートコメント送信結果受信時のキュー名
     */
    case PRIVATE_RESULT = CommandQueueEnumForWebsocket::PRIVATE_RESULT->value;

    /**
     * @var ユーザー名重複チェック結果受信時のキュー名
     */
    case USERSEARCH_RESULT = CommandQueueEnumForWebsocket::USERSEARCH_RESULT->value;

    /**
     * @var マインクラフトからのレスポンス時のキュー名
     */
    case RESPONSE = 'response';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

}
