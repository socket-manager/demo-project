<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetCommandUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\CommandUnits;

use SocketManager\Library\ProtocolQueueEnum;

use App\UnitParameter\ParameterForMinecraft;
use App\UnitParameter\ParameterForWebsocket;


/**
 * コマンドUNIT登録クラス
 * 
 * CommandForWebsocketクラスをオーバーライドしてマインクラフト版として利用
 */
class CommandForMinecraft extends CommandForWebsocket
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    // キューリスト
    protected const QUEUE_LIST = [
        CommandQueueEnumForMinecraft::ENTRANCE->value,             // entranceコマンドを処理するキュー
        CommandQueueEnumForMinecraft::ENTRANCE_WAITING->value,     // entrance-waitingコマンドを処理するキュー
        CommandQueueEnumForMinecraft::MESSAGE->value,              // messageコマンドを処理するキュー
        CommandQueueEnumForMinecraft::EXIT->value,                 // exitコマンドを処理するキュー
        CommandQueueEnumForMinecraft::CLOSE->value,                // closeコマンドを処理するキュー
        CommandQueueEnumForMinecraft::PRIVATE->value,              // privateコマンドを処理するキュー
        CommandQueueEnumForMinecraft::PRIVATE_RESULT->value,       // private-resultコマンドを処理するキュー
        CommandQueueEnumForMinecraft::USERSEARCH_RESULT->value,    // usersearch-resultコマンドを処理するキュー
        CommandQueueEnumForMinecraft::RESPONSE->value              // responseコマンドを処理するキュー
    ];


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    // UNITパラメータ（キャスト用）
    private ParameterForMinecraft $param;


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
     * ステータスUNITリストの取得
     * 
     * @param string $p_que キュー名
     * @return array キュー名に対応するUNITリスト
     */
    public function getUnitList(string $p_que): array
    {
        $ret = [];

        if($p_que === CommandQueueEnumForMinecraft::ENTRANCE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getEntranceStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::ENTRANCE_WAITING->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getEntranceWaitingStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::MESSAGE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getMessageStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::EXIT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getExitStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::CLOSE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getCloseStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::PRIVATE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getPrivateStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::PRIVATE_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getPrivateResultStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::USERSEARCH_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getUserSearchResultStart()
            ];
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::SENDING->value,
                'unit' => $this->getUserSearchResultSending()
            ];
        }
        else
        if($p_que === CommandQueueEnumForMinecraft::RESPONSE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForMinecraft::START->value,
                'unit' => $this->getResponseStart()
            ];
        }

        return $ret;
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ENTRANCE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：入室処理開始
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getEntranceStart()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['MINECRAFT ENTRANCE:START' => 'START']);

            // 受信データを取得
            $w_ret = $p_param->getRecvData();
            $msg = $w_ret['data'];

            // ユーザー名MAX長調整
            $msg['user'] = mb_substr($msg['user'], 0, ParameterForWebsocket::CHAT_USER_NAME_MAX_LENGTH);

            // 現在日時を設定
            $msg['datetime'] = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
    
            // ユーザー名の存在をチェック
            $msg['user'] = $p_param->chopHiddenCharacters($msg['user']);
            if($msg['user'] == '')
            {
                // 自身を切断
                $p_param->closeNoUser();
                return null;
            }

            // HTML変換
            $msg['user'] = htmlspecialchars($msg['user']);

            // ユーザー名の重複チェック
            $w_ret = $p_param->checkUserName($msg['user']);
            if($w_ret === true)
            {
                // ローカルで見つからない場合はサーバー間通信を使う
                $search = $p_param->searchSendUserSearch($msg['user']);
                if($search === true)
                {
                    return null;
                }
                else
                {
                    // 現在のユーザー数を設定
                    $msg['count'] = $p_param->getClientCount();

                    // 入室コメントを設定
                    $msg['comment'] = ParameterForWebsocket::CHAT_ENTRANCE_COMMENT;
            
                    // ユーザー名をリストへ追加
                    $p_param->addUserName($msg['user']);

                    // ユーザーリストを設定
                    $msg['user_list'] = $p_param->getUserList();

                    // 全ブラウザへ配信
                    $data =
                    [
                        'data' => $msg
                    ];
                    $p_param->setSendStackAll($data, true, function(ParameterForMinecraft $p_param)
                    {
                        return !$p_param->isMinecraft();
                    }, $p_param);

                    // マインクラフト接続かどうか
                    $minecraft = $p_param->isMinecraft();

                    // 全マインクラフトへ配信
                    $cmd_data = $p_param->getCommandDataForMessage('entrance', $msg['user'], $msg['comment']);
                    $data =
                    [
                        'data' => $cmd_data
                    ];
                    $p_param->setSendStackAll($data, !$minecraft, function(ParameterForMinecraft $p_param)
                    {
                        return $p_param->isMinecraft();
                    }, $p_param);

                    // マインクラフトでない場合
                    if($minecraft !== true)
                    {
                        // オプションデータを設定
                        $msg['opts'] = $p_param->getOptions();
                        $data =
                        [
                            'data' => $msg
                        ];
                        // 自身へメッセージ配信
                        $p_param->setSendStack($data);
                    }
                    else
                    {
                        /**
                         * サブスクライブのエントリ
                         */

                        // 送信データの設定
                        $w_ret = $p_param->getSubscribeData('PlayerMessage');
                        $subscribe_entry =
                        [
                            "data" => $w_ret
                        ];
                        $p_param->setSendStack($subscribe_entry);
                    }

                    // チャットをログに残す
                    $p_param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);

                    return null;
                }
            }

            // 自身の切断シーケンス開始
            $p_param->closeUserDuplication();

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"ENTRANCE-WAITING"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：入室待ち処理開始
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getEntranceWaitingStart()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['MINECRAFT ENTRANCE-WAITING:START' => 'START']);

            $sta = $p_param->getStatusName();
            $w_ret = $p_param->isExecutedQueue(ProtocolQueueEnum::ALIVE->value);
            $p_param->logWriter('debug', ['MINECRAFT ENTRANCE-WAITING:START' => "alive checking[{$w_ret}]"]);
            if($w_ret === true)
            {
                return $sta;
            }

            // ユーザー名取得
            $hdrs = $p_param->getHeaders();
            $usr_nam = str_replace('/', '', $hdrs['GET'][0]);

            /**
             * 入室コマンドの設定
             */

            $recv_data =
            [
                'data' =>
                [
                    'cmd' => CommandQueueEnumForMinecraft::ENTRANCE->value,
                    'user' => $usr_nam
                ]
            ];

            // 自身の受信スタックへ設定
            $p_param->setRecvStack($recv_data);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"MESSAGE"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"EXIT"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"CLOSE"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"PRIVATE"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"PRIVATE_RESULT"キュー）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"USERSEARCH_RESULT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： SENDING
     * 
     * 処理名：ユーザー名重複時のエラーメッセージ送信中待機
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getUserSearchResultSending()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['MINECRAFT USERSEARCH-RESULT:SENDING' => 'START']);

            // ステータス名取得
            $sta = $p_param->getStatusName();

            // 送信中の間継続
            $w_ret = $p_param->isSending();
            if($w_ret === true)
            {
                return $sta;
            }
    
            // 切断
            $p_param->emergencyShutdown();

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"RESPONSE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：マインクラフトからのレスポンス受信時処理
     * 
     * ※自身の接続へのリクエストに対するレスポンスのみ受け付ける
     * 
     * @param ParameterForMinecraft $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getResponseStart()
    {
        return function(ParameterForMinecraft $p_param): ?string
        {
            $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'START']);

            $rcv = $p_param->getRecvData();
            $w_ret = $p_param->getAwaitResponse();
            if($w_ret['requestId'] === $rcv['data']['header']['requestId'])
            {
                // ユーザー名重複時のレスポンス
                if($w_ret['type'] === 'user-duplication')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'USER-DUPLICATION']);

                    // 例外を投げて切断する
                    //-マインクラフトが切断フレームに対応するまでの仮実装
                    $p_param->emergencyShutdown();
                    // $close_param =
                    // [
                    //     // 切断コード
                    //     'code' => ParameterForMinecraft::CHAT_DUPLICATION_CLOSE_CODE,
                    //     // シリアライズ対象データ
                    //     'data' =>
                    //     [
                    //         'datetime' => date(ParameterForMinecraft::CHAT_DATETIME_FORMAT)
                    //     ]
                    // ];
                    // $p_param->close($close_param);
                }
                else
                // ユーザー名なし時のレスポンス
                if($w_ret['type'] === 'no-user')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'NO-USER']);

                    // 例外を投げて切断する
                    //-マインクラフトが切断フレームに対応するまでの仮実装
                    $p_param->emergencyShutdown();
                    // $close_param =
                    // [
                    //     // 切断コード
                    //     'code' => ParameterForMinecraft::CHAT_NO_USER_CLOSE_CODE,
                    //     // シリアライズ対象データ
                    //     'data' =>
                    //     [
                    //         'datetime' => date(ParameterForMinecraft::CHAT_DATETIME_FORMAT)
                    //     ]
                    // ];
                    // $p_param->close($close_param);
                }
                else
                if($w_ret['type'] === 'exit')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'EXIT']);

                    // 例外を投げて切断する
                    //-マインクラフトが切断フレームに対応するまでの仮実装
                    $p_param->emergencyShutdown();
                    // $close_param =
                    // [
                    //     // 切断コード
                    //     'code' => ParameterForMinecraft::CHAT_MINECRAFT_CLOSE_CODE,
                    //     // シリアライズ対象データ
                    //     'data' =>
                    //     [
                    //         // 切断時パラメータ（現在日時）
                    //         'datetime' => date(ParameterForMinecraft::CHAT_DATETIME_FORMAT)
                    //     ]
                    // ];
    
                    // // 自身を切断
                    // $p_param->close($close_param);
                }
                // 以降の分岐はリザーブ用
                else
                if($w_ret['type'] === 'forced-close')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'FORCED-CLOSE']);
                }
                else
                if($w_ret['type'] === 'entrance')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'ENTRANCE']);
                }
                else
                if($w_ret['type'] === 'message')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'MESSAGE']);
                }
                else
                if($w_ret['type'] === 'close')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'CLOSE']);
                }
                else
                if($w_ret['type'] === 'private')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'PRIVATE']);
                }
                else
                if($w_ret['type'] === 'private-result')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'PRIVATE-RESULT']);
                }
                else
                if($w_ret['type'] === 'private-reply')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'PRIVATE-REPLY']);
                }
                else
                if($w_ret['type'] === 'usersearch-result')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'USERSEARCH-RESULT']);
                }
                else
                if($w_ret['type'] === 'no-comment')
                {
                    $p_param->logWriter('debug', ['MINECRAFT RESPONSE:START' => 'NO-COMMENT']);
                }
            }

            return null;
        };
    }

}
