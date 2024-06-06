<?php
/**
 * プロトコル部のキュー名のENUMファイル
 * 
 * プロトコル部のキュー名は予約済
 */

namespace App\ProtocolUnits;

use SocketManager\Library\ProtocolQueueEnum;


/**
 * プロトコル部のキュー名定義
 * 
 * ProtocolQueueEnumでエイリアス設定
 */
enum ProtocolQueueEnumForTemplate: string
{
    /**
     * @var アクセプト時のキュー名
     */
    case ACCEPT = ProtocolQueueEnum::ACCEPT->value;

    /**
     * @var コネクション時のキュー名
     */
    case CONNECT = ProtocolQueueEnum::CONNECT->value;

    /**
     * @var 受信時のキュー名
     */
    case RECV = ProtocolQueueEnum::RECV->value;

    /**
     * @var 送信時のキュー名
     */
    case SEND = ProtocolQueueEnum::SEND->value;

    /**
     * @var 切断時のキュー名
     */
    case CLOSE = ProtocolQueueEnum::CLOSE->value;

    /**
     * @var アライブチェック時のキュー名
     */
    case ALIVE = ProtocolQueueEnum::ALIVE->value;
}
