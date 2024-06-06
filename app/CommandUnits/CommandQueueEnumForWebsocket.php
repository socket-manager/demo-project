<?php
/**
 * コマンド部のキュー名のENUMファイル
 * 
 * Websocket用
 */

namespace App\CommandUnits;


/**
 * コマンド部のキュー名定義
 * 
 * Websocket用
 */
enum CommandQueueEnumForWebsocket: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var 入室時のキュー名
     */
    case ENTRANCE = 'entrance';

    /**
     * @var チャットコメント時のキュー名
     */
    case MESSAGE = 'message';

    /**
     * @var 退室時のキュー名
     */
    case EXIT = 'exit';

    /**
     * @var クライアント要求切断時のキュー名
     */
    case CLOSE = 'close';

    /**
     * @var プライベートコメント送信時のキュー名
     */
    case PRIVATE = 'private';

    /**
     * @var プライベートコメント送信結果受信時のキュー名
     */
    case PRIVATE_RESULT = 'private-result';

    /**
     * @var ユーザー名重複チェック結果受信時のキュー名
     */
    case USERSEARCH_RESULT = 'usersearch-result';


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

}
