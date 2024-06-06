<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;

use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\UnitException;
use SocketManager\Library\UnitExceptionEnum;

use App\UnitParameter\ParameterForMinecraft;
use App\UnitParameter\ParameterForWebsocket;
use App\CommandUnits\CommandQueueEnumForMinecraft;


/**
 * プロトコルUNIT登録クラス
 * 
 * ProtocolForWebsocketクラスをオーバーライドしてマインクラフト版として利用
 */
class ProtocolForMinecraft extends ProtocolForWebsocket
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    // キューリスト
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::ACCEPT->value,	// アクセプトを処理するキュー
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
        ProtocolQueueEnum::SEND->value,		// 送信処理のキュー
        ProtocolQueueEnum::CLOSE->value,	// 切断処理のキュー
        ProtocolQueueEnum::ALIVE->value		// アライブチェック処理のキュー
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
        parent::__construct();
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

        if($p_que === ProtocolQueueEnum::ACCEPT->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::START->value,
                'unit' => $this->getAcceptStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::CREATE->value,
                'unit' => $this->getAcceptCreate()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::SEND->value,
                'unit' => $this->getAcceptSend()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::START->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::LENGTH->value,
                'unit' => $this->getRecvLength()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::MASK->value,
                'unit' => $this->getRecvMask()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::PAYLOAD->value,
                'unit' => $this->getRecvPayload()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::START->value,
                'unit' => $this->getSendStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::SENDING->value,
                'unit' => $this->getSendSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::CLOSE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::START->value,
                'unit' => $this->getCloseStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::SENDING->value,
                'unit' => $this->getCloseSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::ALIVE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::START->value,
                'unit' => $this->getAliveStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::SENDING->value,
                'unit' => $this->getAliveSending()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::RECV->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::LENGTH->value,
                'unit' => $this->getRecvLength()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::MASK->value,
                'unit' => $this->getRecvMask()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForMinecraft::PAYLOAD->value,
                'unit' => $this->getRecvPayload()
            ];
        }

        return $ret;
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ACCEPT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： SEND
     * 
     * 処理名：返信データ送信
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAcceptSend()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT SEND(MINECRAFT)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();

            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }

            // NG判定
            $hdrs = $p_param->getHeaders();
            if($hdrs['result'] === false)
            {
                // リトライカウンター設定
                if(!isset($hdrs['retry']))
                {
                    $hdrs['retry'] = 1;
                }
                else
                {
                    $hdrs['retry']++;
                }

                // リトライカウント判定
                if($hdrs['retry'] >= ParameterForWebsocket::CHAT_HANDSHAKE_RETRY)
                {
                    // 強制切断
                    throw new UnitException(
                        UnitExceptionEnum::ECODE_HANDSHAKE_FAIL->message(),
                        UnitExceptionEnum::ECODE_HANDSHAKE_FAIL->value,
                        $p_param
                    );
                }

                // リトライカウンター更新と受信バッファをクリア
                $hdrs['buffer'] = '';
                $p_param->setHeaders($hdrs);

                return ProtocolStatusEnumForMinecraft::START->value;
            }

            // マインクラフト接続かどうか
            $minecraft = $p_param->isMinecraft();
            if($minecraft === true)
            {
                /**
                 * 入室コマンドの設定
                 */

                $recv_data =
                [
                    'data' =>
                    [
                        'cmd' => CommandQueueEnumForMinecraft::ENTRANCE_WAITING->value,
                    ]
                ];
    
                // 自身の受信スタックへ設定
                $p_param->setRecvStack($recv_data, true);

                // アライブチェックを行う
                $p_param->aliveCheck(10);
            }

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"RECV"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： PAYLOAD
     * 
     * 処理名：ペイロードデータ受信
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvPayload()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV PAYLOAD(MINECRAFT)' => 'START']);

            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                // 現在のステータス名を取得
                $sta = $p_param->getStatusName();
    
                return $sta;
            }
            $buf = $w_ret;
    
            // 受信バッファからデータ取得
            $entry_data = $p_param->getTempBuff(['recv_buff']);
    
            $entry_data = $entry_data['recv_buff'];
            $entry_data['data'] = '';
            for($i = 0; $i < strlen($buf); $i++)
            {
                $entry_data['data'] .= chr(ord($buf[$i]) ^ ord($entry_data['mask'][$i%4]));
            }
    
            // 切断フレームの場合は切断コードを取得する
            if(($entry_data['first_byte'] & 0x0f) === ParameterForWebsocket::CHAT_OPCODE_CLOSE_MASK)
            {
                // マインクラフトの場合はリトルエンディアンで変換
                $format = 'nshort';
                $minecraft = $p_param->isMinecraft();
                if($minecraft === true)
                {
                    $format = 'vshort';
                }

                // 切断パラメータを取得
                $close_param = $p_param->getCloseParameter();
                $recv_data_ary = unpack($format, $entry_data['data']);
                $entry_data['close_code'] = intval($recv_data_ary['short']);
    
                $p_param->logWriter('debug', ['close code' => $entry_data['close_code'], 'payload' => substr($entry_data['data'], 2)]);
    
                if($minecraft === true)
                {
                    $p_param->logWriter('debug', ['minecraft close frame' => 'through']);
                    return null;
                }

                // コマンド送信による切断
                if(isset($close_param['code']) && $entry_data['close_code'] === $close_param['code'])
                {
                    // 例外を投げて切断する
                    throw new UnitException(
                        UnitExceptionEnum::ECODE_REQUEST_CLOSE->message(),
                        UnitExceptionEnum::ECODE_REQUEST_CLOSE->value,
                        $p_param
                    );
                }
                else
                // クライアントからの切断
                if($entry_data['close_code'] === ParameterForWebsocket::CHAT_CLIENT_CLOSE_CODE)
                {
                    // 切断パラメータを設定
                    $payload = substr($entry_data['data'], 2);

                    // ペイロード部へ反映
                    $entry_data['data'] = $payload;
                }
                else
                // その他異常終了用
                {
                    // クライアントからの強制切断時のコールバック
                    $p_param->forcedCloseFromClient($p_param);

                    // 例外を投げて切断する
                    throw new UnitException(
                        UnitExceptionEnum::ECODE_FORCE_CLOSE->message(),
                        UnitExceptionEnum::ECODE_FORCE_CLOSE->value,
                        $p_param
                    );
                }
            }
    
            $p_param->logWriter('debug', ['receive payload data' => $entry_data['data']]);
    
            // PONGの場合は抜ける
            if(($entry_data['first_byte'] & 0x0f) === ParameterForWebsocket::CHAT_OPCODE_PONG_MASK)
            {
                $p_param->logWriter('debug', ['pong receive']);
                return null;
            }

            // データを受信バッファスタックに設定
            $p_param->setRecvStack($entry_data);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"SEND"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"CLOSE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： SENDING
     * 
     * 処理名：切断パケット送信実行
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getCloseSending()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['CLOSE SENDING(MINECRAFT)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();
    
            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }
    
            // マインクラフトの場合
            $minecraft = $p_param->isMinecraft();
            if($minecraft === true)
            {
                // 例外を投げて切断する
                //-マインクラフトがサーバー送信の切断フレームに対応していないため
                $p_param->emergencyShutdown();
            }
            else
            {
                $p_param->delUserName();

                // 切断パラメータの取得
                $close_param = $p_param->getCloseParameter();

                // クライアント要求の切断の場合
                if($close_param['code'] === ParameterForWebsocket::CHAT_CLIENT_CLOSE_CODE)
                {
                    // 例外を投げて切断する
                    throw new UnitException(
                        UnitExceptionEnum::ECODE_REQUEST_CLOSE->message(),
                        UnitExceptionEnum::ECODE_REQUEST_CLOSE->value,
                        $p_param
                    );
                }
            }

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ALIVE"キュー）
    //--------------------------------------------------------------------------

}
