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
use App\UnitParameter\ParameterForWebsocket;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolForWebsocket implements IEntryUnits
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

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
                'status' => ProtocolStatusEnumForWebsocket::START->value,
                'unit' => $this->getAcceptStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::CREATE->value,
                'unit' => $this->getAcceptCreate()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::SEND->value,
                'unit' => $this->getAcceptSend()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::START->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::LENGTH->value,
                'unit' => $this->getRecvLength()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::MASK->value,
                'unit' => $this->getRecvMask()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::PAYLOAD->value,
                'unit' => $this->getRecvPayload()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::SEND->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::START->value,
                'unit' => $this->getSendStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::SENDING->value,
                'unit' => $this->getSendSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::CLOSE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::START->value,
                'unit' => $this->getCloseStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::SENDING->value,
                'unit' => $this->getCloseSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::ALIVE->value)
        {
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::START->value,
                'unit' => $this->getAliveStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::SENDING->value,
                'unit' => $this->getAliveSending()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::RECV->value,
                'unit' => $this->getRecvStart()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::LENGTH->value,
                'unit' => $this->getRecvLength()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::MASK->value,
                'unit' => $this->getRecvMask()
            ];
            $ret[] = [
                'status' => ProtocolStatusEnumForWebsocket::PAYLOAD->value,
                'unit' => $this->getRecvPayload()
            ];
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
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAcceptStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT START(WEBSOCKET)' => 'START']);

            // ソケット受信
            $rcv = '';
            $p_param->protocol()->recv($rcv);
    
            // データがある時
            if(strlen($rcv) > 0)
            {
                $p_param->logWriter('debug', ['ACCEPT START' => 'DATA EXIST']);

                // 前回まで受信したデータを取得
                $old = '';
                $w_ret = $p_param->getHeaders();
                if($w_ret !== null)
                {
                    $old = $w_ret['buffer'];
                }
        
                // 途中まで受信したデータを結合
                $buf = $old.$rcv;
    
                // ヘッダの末尾を受信したら終わり
                $matches = array();
                if(preg_match('/\r\n\r\n/', $buf, $matches))
                {
                    $w_ret['buffer'] = $buf;
                    $hdrs = $w_ret;
                    $rows = preg_split('/\r\n/', $rcv);
                    foreach($rows as $row)
                    {
                        $row = chop($row);
                        if(preg_match('/\A(\S+): (.*)\z/', $row, $matches))
                        {
                            $hdrs[$matches[1]] = $matches[2];
                        }
                        else
                        {
                            $parts = explode(' ', $row);
                            if(trim($parts[0]) !== '')
                            {
                                $key = $parts[0];
                                array_shift($parts);
                                $hdrs[$key] = $parts;
                            }
                        }
                    }
    
                    // ヘッダ情報を格納
                    $p_param->setHeaders($hdrs);

                    $w_ret = $p_param->getHeaders();
                    $p_param->logWriter('debug', ['headers' => print_r($w_ret, true)]);

                    return ProtocolStatusEnumForWebsocket::CREATE->value;
                }
    
                // 受信中のデータを格納
                $p_param->setHeaders(['buffer' => $buf]);
            }
    
            // ステータス名の取得
            $sta = $p_param->getStatusName();
    
            return $sta;
        };
    }

    /**
     * ステータス名： CREATE
     * 
     * 処理名：データ作成（返信用）
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAcceptCreate()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT CREATE(WEBSOCKET)' => 'START']);

            // ハンドシェイクデータの取得
            $hdrs = $p_param->getHeaders();

            // リッスンホスト名を取得
            $host = $p_param->getAwaitHost();
            $port = $p_param->getAwaitPort();

            // アクセプトキーの生成
            $sec_key = $hdrs['Sec-WebSocket-Key'];
            $sec_accept = base64_encode(pack('H*', sha1($sec_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    
            // ロケーションURLの設定
            $tls = $p_param->getTls();
            $protocol = 'ws';
            if($tls === true)
            {
                $protocol = 'wss';
            }
            $location = "{$protocol}://{$host}:{$port}";
    
            // プロトコルバージョンのチェック
            $ok = false;
            $res = '';
            if(isset($hdrs['Sec-WebSocket-Version']))
            {
                $ver = trim($hdrs['Sec-WebSocket-Version']);
                if($ver == ParameterForWebsocket::CHAT_PROTOCOL_VERSION)
                {
                    $ok = true;
                }
                else
                {
                    // 返信用ハンドシェイクデータの作成
                    $res  =
                        "HTTP/1.1 400 Bad Request\r\n" .
                        "WebSocket-Origin: {$host}\r\n" .
                        "WebSocket-Location: {$location}/\r\n" .
                        "Sec-WebSocket-Accept: {$sec_accept}\r\n" .
                        "Sec-WebSocket-Version: ".ParameterForWebsocket::CHAT_PROTOCOL_VERSION."\r\n\r\n";
                }
            }
            else
            {
                // 返信用ハンドシェイクデータの作成
                $res  = "HTTP/1.1 400 Bad Request\r\n\r\n";
            }

            if($ok === false)
            {
                // 送信データの設定
                $p_param->protocol()->setSendingData($res);
    
                // NGを設定
                $hdrs['result'] = false;
                $p_param->setHeaders($hdrs);

                return ProtocolStatusEnumForWebsocket::SEND->value;
            }

            // 返信用ハンドシェイクデータの作成
            $upgrade  =
                "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "WebSocket-Origin: {$host}\r\n" .
                "WebSocket-Location: {$location}/\r\n" .
                "Sec-WebSocket-Accept: {$sec_accept}\r\n\r\n";
    
            // 送信データの設定
            $p_param->protocol()->setSendingData($upgrade);
    
            // OKを設定
            $hdrs['result'] = true;
            $p_param->setHeaders($hdrs);

            return ProtocolStatusEnumForWebsocket::SEND->value;
        };
    }

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
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['ACCEPT SEND(WEBSOCKET)' => 'START']);

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

                $w_ret = $p_param->getHeaders();
                $p_param->logWriter('debug', ['ACCEPT SEND(WEBSOCKET)' => 'NG', 'headers' => $w_ret]);

                return ProtocolStatusEnumForWebsocket::START->value;
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
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV START(WEBSOCKET)' => 'START']);

            // 現在のステータス名を取得
            $sta = $p_param->getStatusName();
    
            /**
             * ヘッダの先頭の２バイトを取り込む
             * 
             */
    
            // 受信中かどうか
            $w_ret = $p_param->isReceiving();
            if($w_ret === false)
            {
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize(2);

                // リトライ回数初期化
                $p_param->setRecvRetry(0);
            }
    
            // データ受信
            $w_ret = $p_param->protocol()->receiving();
            if($w_ret === null)
            {
                $cnt = $p_param->getRecvRetry() + 1;
                if($cnt >= ParameterForWebsocket::CHAT_RECEIVE_EMPTY_RETRY)
                {
                    // アライブチェックの実行
                    $p_param->aliveCheck(10);
                }
                $p_param->setRecvRetry($cnt);
                return $sta;
            }
            $buf = $w_ret;
    
            /**
             * ２バイト取れたらヘッダ情報を格納する
             * 
             */
    
            // 受信データのアンシリアライズ化
            $dat_ary = unpack('c2chars', $buf);
    
            // データ長種別を設定
            $len_knd = $dat_ary['chars2'] & ParameterForWebsocket::CHAT_PAYLOAD_LEN_MASK;
    
            $entry_data = array();
            if($len_knd == ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_2)
            {
                $entry_data = [
                      'length_byte'  => 2
                    , 'length'       => null
                    , 'data'         => null
                    , 'first_byte'   => $dat_ary['chars1']
                    , 'close_code'   => null
                ];
    
                $sta = ProtocolStatusEnumForWebsocket::LENGTH->value;
    
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize($entry_data['length_byte']);
            }
            elseif($len_knd == ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_8)
            {
                $entry_data = [
                      'length_byte'  => 8
                    , 'length'       => null
                    , 'data'         => null
                    , 'first_byte'   => $dat_ary['chars1']
                    , 'close_code'   => null
                ];
    
                $sta = ProtocolStatusEnumForWebsocket::LENGTH->value;
    
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize($entry_data['length_byte']);
            }
            else
            {
                $entry_data = [
                      'length_byte'  => 1
                    , 'length'       => $len_knd
                    , 'data'         => null
                    , 'first_byte'   => $dat_ary['chars1']
                    , 'close_code'   => null
                ];
    
                $sta = ProtocolStatusEnumForWebsocket::MASK->value;
    
                // 受信サイズを設定
                $p_param->protocol()->setReceivingSize(4);
            }
    
            // テンポラリバッファにセット
            $p_param->setTempBuff(['recv_buff' => $entry_data]);
    
            return $sta;
        };
    }

    /**
     * ステータス名： LENGTH
     * 
     * 処理名：データ長受信
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvLength()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV LENGTH(WEBSOCKET)' => 'START']);

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
    
            // データ長を展開
            $unpack_data = array();
            if($entry_data['length_byte'] == 2)
            {
                $unpack_data = unpack('nlength', $buf);
            }
            else
            {
                $unpack_data = unpack('NNlength', $buf);
            }
            $entry_data['length'] = $unpack_data['length'];
    
            // 受信バッファにセット
            $p_param->setTempBuff(['recv_buff' => $entry_data]);
    
            // 受信サイズを設定
            $p_param->protocol()->setReceivingSize(4);

            return ProtocolStatusEnumForWebsocket::MASK->value;
        };
    }

    /**
     * ステータス名： MASK
     * 
     * 処理名：マスクデータ受信
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvMask()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV MASK(WEBSOCKET)' => 'START']);

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
    
            // マスクデータの格納
            $entry_data['mask'] = $buf;
    
            // 受信バッファにセット
            $p_param->setTempBuff(['recv_buff' => $entry_data]);
    
            // 受信サイズを設定
            $p_param->protocol()->setReceivingSize($entry_data['length']);

            return ProtocolStatusEnumForWebsocket::PAYLOAD->value;
        };
    }

    /**
     * ステータス名： PAYLOAD
     * 
     * 処理名：ペイロードデータ受信
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvPayload()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['RECV PAYLOAD(WEBSOCKET)' => 'START']);

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
                // 切断パラメータを取得
                $close_param = $p_param->getCloseParameter();
                $recv_data_ary = unpack('nshort', $entry_data['data']);
                $entry_data['close_code'] = intval($recv_data_ary['short']);
    
                $p_param->logWriter('debug', ['close code' => $entry_data['close_code'], 'payload' => substr($entry_data['data'], 2)]);
    
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

    /**
     * ステータス名： START
     * 
     * 処理名：送信開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getSendStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND START(WEBSOCKET)' => 'START']);

            // 送信データスタックから取得
            $w_ret = $p_param->protocol()->getSendData();
            $payload = $w_ret['data'];

            //--------------------------------------------------------------------------
            // ヘッダ部の構築
            //--------------------------------------------------------------------------

            $b1 = ParameterForWebsocket::CHAT_FIN_BIT_MASK | (ParameterForWebsocket::CHAT_OPCODE_TEXT_MASK & 0x0f);  // maskなし
            $length = strlen($payload);

            $header = '';

            // データ長１バイト
            if($length <= 125)
            {
                $header = pack('CC', $b1, $length);
            }
            // データ長２バイト
            else
            if($length > 125 && $length < 65536)
            {
                $header = pack('CCn', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_2, $length);
            }
            //データ長８バイト
            else
            if($length >= 65536)
            {
                $header = pack('CCNN', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_8, $length);
            }
        
            $mask = '';
        
            //--------------------------------------------------------------------------
            // 送信データの設定
            //--------------------------------------------------------------------------

            $p_param->protocol()->setSendingData($header.$mask.$payload);

            return ProtocolStatusEnumForWebsocket::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信実行
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getSendSending()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['SEND SENDING(WEBSOCKET)' => 'START']);

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

    /**
     * ステータス名： START
     * 
     * 処理名：切断開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getCloseStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['CLOSE START(WEBSOCKET)' => 'START']);

            // 切断パラメータの取得
            $close_param = $p_param->getCloseParameter();
            $p_param->logWriter('debug', ['GET CLOSE PARAMETER' => $close_param]);
    
            // 切断コード
            $payload = pack('n', $close_param['code']);
    
            // 切断時のデータ
            $payload .= $close_param['data'];

            //--------------------------------------------------------------------------
            // ヘッダ部の構築
            //--------------------------------------------------------------------------
    
            $b1 = ParameterForWebsocket::CHAT_FIN_BIT_MASK | (ParameterForWebsocket::CHAT_OPCODE_CLOSE_MASK & 0x0f);  // maskなし
            $length = strlen($payload);
    
            $header = '';
    
            // データ長１バイト
            if($length <= 125)
            {
                $header = pack('CC', $b1, $length);
            }
            // データ長２バイト
            else
            if($length > 125 && $length < 65536)
            {
                $header = pack('CCn', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_2, $length);
            }
            //データ長８バイト
            else
            if($length >= 65536)
            {
                $header = pack('CCNN', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_8, $length);
            }
        
            //--------------------------------------------------------------------------
            // 送信データの設定
            //--------------------------------------------------------------------------
    
            $p_param->protocol()->setSendingData($header.$payload);

            $p_param->logWriter('debug', ['CLOSE START(WEBSOCKET)' => 'HEX', 'header' => bin2hex($header), 'payload' => bin2hex($payload)]);

            return ProtocolStatusEnumForWebsocket::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：切断パケット送信実行
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getCloseSending()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['CLOSE SENDING(WEBSOCKET)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();
    
            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }
    
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

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ALIVE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：アライブチェック開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAliveStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['ALIVE START(WEBSOCKET)' => 'START']);

            // 接続IDの取得
            $cid = $p_param->getConnectionId();

            // ペイロード設定
            $payload = $cid;
    
            //--------------------------------------------------------------------------
            // ヘッダ部の構築
            //--------------------------------------------------------------------------
    
            $b1 = ParameterForWebsocket::CHAT_FIN_BIT_MASK | (ParameterForWebsocket::CHAT_OPCODE_PING_MASK & 0x0f);  // maskなし
            $length = strlen($payload);
    
            $header = '';
    
            // データ長１バイト
            if($length <= 125)
            {
                $header = pack('CC', $b1, $length);
            }
            // データ長２バイト
            else
            if($length > 125 && $length < 65536)
            {
                $header = pack('CCn', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_2, $length);
            }
            //データ長８バイト
            else
            if($length >= 65536)
            {
                $header = pack('CCNN', $b1, ParameterForWebsocket::CHAT_PAYLOAD_LEN_CODE_8, $length);
            }
        
            //--------------------------------------------------------------------------
            // 送信データの設定
            //--------------------------------------------------------------------------
    
            $p_param->protocol()->setSendingData($header.$payload);
    
            return ProtocolStatusEnumForWebsocket::SENDING->value;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：PING送信実行
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getAliveSending()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['ALIVE SENDING(WEBSOCKET)' => 'START']);

            // データ送信
            $w_ret = $p_param->protocol()->sending();
    
            // 送信中の場合は再実行
            if($w_ret === null)
            {
                $sta = $p_param->getStatusName();
                return $sta;
            }
    
            return ProtocolStatusEnumForWebsocket::RECV->value;
        };
    }

}
