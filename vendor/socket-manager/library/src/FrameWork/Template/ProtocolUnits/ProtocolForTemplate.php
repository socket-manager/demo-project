<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\SocketManagerParameter;

use App\ProtocolUnits\ProtocolStatusEnumForTemplate;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolForTemplate implements IEntryUnits
{
    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::ACCEPT->value,	// アクセプトを処理するキュー
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
        ProtocolQueueEnum::SEND->value,		// 送信処理のキュー
        ProtocolQueueEnum::CLOSE->value,	// 切断処理のキュー
        ProtocolQueueEnum::ALIVE->value		// アライブチェック処理のキュー
    ];


    /**
     * コンストラクタ
     * 
     */
    public function __construct()
    {
    }

    /**
     * キューリストの取得
     * 
     * @return array キュー名のリスト
     */
    public function getQueueList(): array
    {
        return (array)static::QUEUE_LIST;
    }

    /**
     * ステータスUNITリストの取得
     * 
     * @param string $p_que キュー名
     * @return array キュー名に対応するUNITリスト
     */
    public function getUnitList(string $p_que): array
    {
        $ret = [];

        if($p_que === ProtocolQueueEnum::ACCEPT->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTemplate::START->value,
                'unit' => $this->getAcceptStart()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTemplate::START->value,
                'unit' => $this->getRecvStart()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTemplate::START->value,
                'unit' => $this->getSendStart()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::CLOSE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTemplate::START->value,
                'unit' => $this->getCloseStart()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::ALIVE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTemplate::START->value,
                'unit' => $this->getAliveStart()
            ];
        }

        return $ret;
    }


    /**
     * 以降はステータスUNITの定義（"ACCEPT"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：アクセプト開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAcceptStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT' => 'START']);

            return null;
        };
    }


    /**
     * 以降はステータスUNITの定義（"RECV"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：受信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV' => 'START']);

            return null;
        };
    }


    /**
     * 以降はステータスUNITの定義（"SEND"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：送信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getSendStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND' => 'START']);

            return null;
        };
    }


    /**
     * 以降はステータスUNITの定義（"CLOSE"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：切断開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getCloseStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->logWriter('debug', ['CLOSE' => 'START']);

            return null;
        };
    }


    /**
     * 以降はステータスUNITの定義（"ALIVE"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：アライブチェック開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAliveStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $p_param->logWriter('debug', ['ALIVE' => 'START']);

            return null;
        };
    }
}
