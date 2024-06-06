<?php
/**
 * コマンド部のキュー名のENUMファイル
 * 
 * UDPマルチサーバー用
 */

namespace App\CommandUnits;


/**
 * コマンド部のキュー名定義
 * 
 * UDPマルチサーバー用
 */
enum CommandQueueEnumForUdpMulti: string
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var プライベートコメント送信検索のキュー名
     */
    case PRIVATE_SEARCH = CommandQueueEnumForTcpMulti::PRIVATE_SEARCH->value;

    /**
     * @var プライベートコメント送信結果受信時のキュー名
     */
    case PRIVATE_RESULT = CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value;

    /**
     * @var ユーザー検索時のキュー名
     */
    case USERSEARCH = CommandQueueEnumForTcpMulti::USERSEARCH->value;

    /**
     * @var ユーザー検索結果受信時のキュー名
     */
    case USERSEARCH_RESULT = CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

}
