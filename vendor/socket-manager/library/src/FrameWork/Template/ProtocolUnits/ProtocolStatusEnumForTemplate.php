<?php
/**
 * プロトコルUNITステータス名のENUMファイル
 * 
 * StatusEnumの定義を除いて自由定義
 */

namespace App\ProtocolUnits;


use SocketManager\Library\StatusEnum;


/**
 * プロトコルUNITステータス名定義
 * 
 * プロトコルUNITのステータス予約名はSTART（処理開始）のみ
 */
enum ProtocolStatusEnumForTemplate: string
{
    /**
     * @var string 処理開始時のステータス共通
     */
    case START = StatusEnum::START->value;
}
