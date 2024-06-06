<?php
/**
 * プロトコルUNITステータス名のENUMファイル
 * 
 * マインクラフト用
 */

namespace App\ProtocolUnits;


/**
 * プロトコルUNITステータス名定義
 * 
 * マインクラフト用
 */
enum ProtocolStatusEnumForMinecraft: string
{
    //--------------------------------------------------------------------------
    // 定数（共通）
    //--------------------------------------------------------------------------

    /**
     * @var string 処理開始時のステータス共通
     */
    case START = ProtocolStatusEnumForWebsocket::START->value;

    /**
     * @var string 送信中のステータス名
     */
    case SENDING = ProtocolStatusEnumForWebsocket::SENDING->value;

    /**
     * @var string ペイロード長受信のステータス名
     */
    case LENGTH = ProtocolStatusEnumForWebsocket::LENGTH->value;

    /**
     * @var string マスクデータ受信のステータス名
     */
    case MASK = ProtocolStatusEnumForWebsocket::MASK->value;

    /**
     * @var string ペイロードデータ受信のステータス名
     */
    case PAYLOAD = ProtocolStatusEnumForWebsocket::PAYLOAD->value;


    //--------------------------------------------------------------------------
    // 定数（ProtocolQueueEnum::ACCEPTキュー）
    //--------------------------------------------------------------------------

    /**
     * @var string レスポンスデータ作成のステータス名
     */
    case CREATE = ProtocolStatusEnumForWebsocket::CREATE->value;

    /**
     * @var string レスポンス送信のステータス名
     */
    case SEND = ProtocolStatusEnumForWebsocket::SEND->value;


    //--------------------------------------------------------------------------
    // 定数（ProtocolQueueEnum::RECVキュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 定数（ProtocolQueueEnum::SENDキュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 定数（ProtocolQueueEnum::CLOSEキュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 定数（ProtocolQueueEnum::ALIVEキュー）
    //--------------------------------------------------------------------------

    /**
     * @var string 受信中のステータス名
     */
    case RECV = ProtocolStatusEnumForWebsocket::RECV->value;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

}
