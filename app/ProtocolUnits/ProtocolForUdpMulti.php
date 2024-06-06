<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;

use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;

use App\UnitParameter\ParameterForUdpMulti;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolForUdpMulti implements IEntryUnits
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    // キューリスト
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
        ProtocolQueueEnum::SEND->value		// 送信処理のキュー
    ];


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

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
        return self::QUEUE_LIST;
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

        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForUdpMulti::START->value,
                'unit' => $this->getRecvStart()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForUdpMulti::START->value,
                'unit' => $this->getSendStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForUdpMulti::SENDING->value,
                'unit' => $this->getSendSending()
            ];
        }

        return $ret;
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"RECV"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：受信開始
     * 
     * @param ParameterForUdpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getRecvStart()
    {
        return function(ParameterForUdpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV START(ORIGINAL)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
    
            // データ受信
            $buf = '';
            $w_ret = $p_param->protocol()->recv($buf);
            if($w_ret === 0)
            {
                return $sta;
            }

            /**
             * レングス情報を格納する
             */
    
            $unpack_data = unpack('nlength', $buf);
            $entry_data = [
                  'length' => $unpack_data['length']
                , 'data'   => substr($buf, 2)
            ];

            // データを受信データスタックに設定
            $p_param->setRecvStack($entry_data['data']);

            $p_param->logWriter('debug', ['receive payload data' => $entry_data['data']]);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"SEND"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：送信開始
     * 
     * @param ParameterForUdpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getSendStart()
    {
        return function(ParameterForUdpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND START(ORIGINAL)' => 'START']);

            // 送信データスタックから取得
            $payload = $p_param->protocol()->getSendData();

            // ヘッダ部の作成
            $header = pack('n', strlen($payload));

            // 送信データの設定
            $p_param->protocol()->setSendingData($header.$payload);

            return ProtocolStatusEnumForUdpMulti::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信実行
     * 
     * @param ParameterForUdpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getSendSending()
    {
        return function(ParameterForUdpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND SENDING(ORIGINAL)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();
    
            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }
    
            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"CLOSE"キュー）
    //--------------------------------------------------------------------------

}
