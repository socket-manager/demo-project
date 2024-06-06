<?php
/**
 * コマンドUNITステータス名のENUMファイル
 * 
 * StatusEnumの定義を除いて自由定義
 */

namespace App\CommandUnits;


use SocketManager\Library\StatusEnum;


/**
 * コマンドUNITステータス名定義
 * 
 * コマンドUNITのステータス予約名はSTART（処理開始）のみ
 */
enum CommandStatusEnumForTemplate: string
{
    /**
     * @var 処理開始時のステータス名
     */
    case START = StatusEnum::START->value;
}
