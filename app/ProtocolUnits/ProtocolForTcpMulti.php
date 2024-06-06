<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;

use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\UnitException;
use SocketManager\Library\UnitExceptionEnum;

use App\UnitParameter\ParameterForTcpMulti;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolForTcpMulti implements IEntryUnits
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::ACCEPT->value,	// アクセプトを処理するキュー
        ProtocolQueueEnum::CONNECT->value,	// コネクションを処理するキュー
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
        ProtocolQueueEnum::SEND->value,		// 送信処理のキュー
        ProtocolQueueEnum::CLOSE->value		// 切断処理のキュー
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
        return (array)self::QUEUE_LIST;
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
                'status' => ProtocolStatusEnumForTcpMulti::START->value,
                'unit' => $this->getAcceptStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::SENDING->value,
                'unit' => $this->getAcceptSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::CONNECT->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::START->value,
                'unit' => $this->getConnectStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::SENDING->value,
                'unit' => $this->getConnectSending()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::RECV->value,
                'unit' => $this->getConnectRecv()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::START->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::PAYLOAD->value,
                'unit' => $this->getRecvPayload()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::START->value,
                'unit' => $this->getSendStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForTcpMulti::SENDING->value,
                'unit' => $this->getSendSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::CLOSE->value)
        {
        }

        return $ret;
    }

    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ACCEPT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：受信
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getAcceptStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT START(ORIGINAL:PARENT)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
    
            // 受信中かどうか
            $w_ret = $p_param->isReceiving();
            if($w_ret === false)
            {
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize(7);
            }
    
            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                return $sta;
            }
            $buf = $w_ret;
    
            $p_param->logWriter('debug', ['accept recv data' => $buf]);

            /**
             * 受信データの一致確認を行って返信の準備をする
             */

            // コマンド不一致なら例外を投げて切断する
            if($buf !== 'connect')
            {
                throw new UnitException(
                    UnitExceptionEnum::ECODE_COMMAND_MISMATCH->message(),
                    UnitExceptionEnum::ECODE_COMMAND_MISMATCH->value,
                    $p_param
                );
            }

            $sta = ProtocolStatusEnumForTcpMulti::SENDING->value;

            // 送信データの設定
            $p_param->protocol()->setSendingData('connect-apply');

            return $sta;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：返信データ送信
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getAcceptSending()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT SENDING(ORIGINAL:PARENT)' => 'START']);

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
    // 以降はステータスUNITの定義（"CONNECT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：コネクション開始
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getConnectStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['CONNECT START(ORIGINAL:CLIENT)' => 'START']);

            // 送信データの設定
            $p_param->protocol()->setSendingData('connect');
    
            return ProtocolStatusEnumForTcpMulti::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：コネクション要求送信
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getConnectSending()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['CONNECT SENDING(ORIGINAL:CLIENT)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();

            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }

            // 受信サイズを設定
            $p_param->protocol()->setReceivingSize(13);

            return ProtocolStatusEnumForTcpMulti::RECV->value;
        };
    }

    /**
     * ステータス名： RECV
     * 
     * 処理名：返信受信
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getConnectRecv()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['CONNECT RECV(ORIGINAL:CLIENT)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
        
            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                return $sta;
            }
            $buf = $w_ret;

            $p_param->logWriter('debug', ['connect recv data' => $buf]);


            // コマンド不一致なら例外を投げて切断する
            if($buf !== 'connect-apply')
            {
                throw new UnitException(
                    UnitExceptionEnum::ECODE_COMMAND_MISMATCH->message(),
                    UnitExceptionEnum::ECODE_COMMAND_MISMATCH->value,
                    $p_param
                );
            }

            return null;
        };
    }

    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"RECV"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：受信開始
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getRecvStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV START(ORIGINAL)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
    
            /**
             * ヘッダの先頭の２バイトを取り込む
             */
    
            // 受信中かどうか
            $w_ret = $p_param->isReceiving();
            if($w_ret === false)
            {
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize(2);
            }
    
            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                return $sta;
            }
            $buf = $w_ret;
    
            /**
             * ２バイト取れたらレングス情報を格納する
             */
    
            $unpack_data = unpack('nlength', $buf);
            $entry_data = [
                  'length' => $unpack_data['length']
                , 'data'   => null
            ];

            $sta = ProtocolStatusEnumForTcpMulti::PAYLOAD->value;

            // 受信バッファにセット
            $p_param->setTempBuff(['recv_buff' => $entry_data]);

            // 受信サイズを設定
            $p_param->protocol()->setReceivingSize($entry_data['length']);

            return $sta;
        };
    }

    /**
     * ステータス名： PAYLOAD
     * 
     * 処理名：ペイロード部受信
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getRecvPayload()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV PAYLOAD(ORIGINAL)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
        
            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                return $sta;
            }
            $buf = $w_ret;

            $sta = null;

            // 受信バッファから取得
            $w_ret = $p_param->getTempBuff(['recv_buff']);
            $entry_data = $w_ret['recv_buff'];
            $entry_data['data'] = $buf;

            // データを受信データスタックに設定
            $p_param->setRecvStack($entry_data['data']);

            $p_param->logWriter('debug', ['receive payload data' => $entry_data['data']]);
    
            return $sta;
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
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getSendStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND START(ORIGINAL)' => 'START']);

            // 送信データスタックから取得
            $payload = $p_param->protocol()->getSendData();

            // ヘッダ部の作成
            $header = pack('n', strlen($payload));

            // 送信データの設定
            $p_param->protocol()->setSendingData($header.$payload);

            return ProtocolStatusEnumForTcpMulti::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信実行
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getSendSending()
    {
        return function(ParameterForTcpMulti $p_param): ?string
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
