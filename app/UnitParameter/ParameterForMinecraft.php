<?php
/**
 * UNITパラメータクラスのファイル
 * 
 * マインクラフト版
 */

namespace App\UnitParameter;


use App\CommandUnits\CommandQueueEnumForMinecraft;
use App\CommandUnits\CommandStatusEnumForMinecraft;

/**
 * UNITパラメータクラス
 * 
 * ParameterForWebsocketクラスをオーバーライドしてマインクラフト版として利用
 */
class ParameterForMinecraft extends ParameterForWebsocket
{
    //--------------------------------------------------------------------------
    // 定数（first byte）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 定数（second byte）
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // 定数（切断コード）
    //--------------------------------------------------------------------------

    /**
     * マインクラフトの切断コード
     */
    public const CHAT_MINECRAFT_CLOSE_CODE = 3020;


    //--------------------------------------------------------------------------
    // 定数（その他）
    //--------------------------------------------------------------------------

    /**
     * 運営サイドのユーザー名
     */
    public const CHAT_ADMIN_USER = '運営チーム';


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
     * @param bool $p_tls TLSフラグ
     */
    public function __construct(bool $p_tls = null)
    {
        parent::__construct($p_tls);
    }


    //--------------------------------------------------------------------------
    // マインクラフト専用
    //--------------------------------------------------------------------------

    /**
     * 自身の接続がマインクラフト接続かどうかを検査
     * 
     * @param string $p_cid 接続ID
     * @return bool true（マインクラフト接続） or false（マインクラフト接続以外）
     */
    public function isMinecraft(string $p_cid = null)
    {
        $cid = null;
        if($p_cid !== null)
        {
            $cid = $p_cid;
        }

        $ret = true;
        $hdrs = $this->getHeaders($cid);
        if(isset($hdrs['User-Agent']))
        {
            $ret = false;
        }
        return $ret;
    }

    /**
     * UUIDv4の取得
     * 
     * @return string UUID(V4)
     */
    public function getUuidv4()
    {
        $pattern = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx';
        $chrs = str_split($pattern);
        foreach($chrs as $i => $chr)
        {
            if($chr === 'x')
            {
                $chrs[$i] = dechex(random_int(0, 15));
            }
            else
            if($chr === 'y')
            {
                $chrs[$i] = dechex(random_int(8, 11));
            }
        }
        $uuidv4 = implode('', $chrs);

        return $uuidv4;
    }

    /**
     * マインクラフトへ送信するサブスクライブデータを取得
     * 
     * @param string $p_eve サブスクライブするイベント名
     * @return array 送信データ
     */
    public function getSubscribeData(string $p_eve): array
    {
        // UUIDの取得
        $uuidv4 = $this->getUuidv4();

        // サブスクライブエントリデータ
        $w_ret =
        [
            "header" =>
            [
                "version" => 1, // プロトコルのバージョンを指定。現時点では1で問題ない
                "requestId" => $uuidv4, // UUIDv4を指定
                "messageType" => "commandRequest",  // "commandRequest" を指定
                "messagePurpose" => "subscribe", // "subscribe" を指定
            ],
            "body" =>
            [
                "eventName" => $p_eve // イベント名を指定。
            ]
        ];

        return $w_ret;
    }

    /**
     * マインクラフトへサブスクライブデータを送信
     * 
     */
    public function sendSubscribesData()
    {
        $types = config('minecraft.subscribe_types');

        foreach($types as $type)
        {
            // 送信データの設定
            $w_ret = $this->getSubscribeData($type);
            $subscribe_entry =
            [
                "data" => $w_ret
            ];
            $this->setSendStack($subscribe_entry);
        }
    }

    /**
     * マインクラフトへ送信するコマンドデータを取得
     * 
     * @param string $p_cmd コマンド文字列
     * @param string $p_typ 処理タイプ文字列（'response'コマンドで利用）
     * @return array 送信データ
     */
    protected function getCommandData(string $p_cmd, string $p_typ = null): array
    {
        // UUIDの取得
        $uuidv4 = $this->getUuidv4();

        // サブスクライブエントリデータ
        $w_ret =
        [
            "header" =>
            [
                "version" => 1,
                "requestId" => $uuidv4, // UUIDv4を生成して指定
                "messageType" => "commandRequest", // commandRequestを指定
                "messagePurpose" => "commandRequest", // commandRequestを指定
            ],
            "body" =>
            [
                "origin" =>
                [
                    "type" => "player" // 誰がコマンドを実行するかを指定（ただし、Player以外にどの値が利用可能かは要調査）
                ],
                "version" => 1,
                "commandLine" => $p_cmd, // マイクラで実行したいコマンドを指定
            ]
        ];

        // 待ち受けるレスポンス情報を設定
        $this->setAwaitResponse($uuidv4, $p_typ);

        return $w_ret;
    }

    /**
     * マインクラフトへ送信するメッセージコマンドデータを取得
     * 
     * @param string $p_typ 処理タイプ文字列
     * @param string $p_usr ユーザー名
     * @param string $p_cmt コメント
     * @param string $p_preposition 前置詞
     * @return array 送信データ
     */
    public function getCommandDataForMessage(string $p_typ, string $p_usr, string $p_cmt, string $p_preposition = 'by'): array
    {
        $cmd = "say {$p_cmt}[{$p_preposition} {$p_usr}]";
        $w_ret = $this->getCommandData($cmd, $p_typ);
        return $w_ret;
    }

    /**
     * マインクラフトへ送信するサブタイトルコマンドデータを取得
     * 
     * @param string $p_typ 処理タイプ文字列
     * @param string $p_usr ユーザー名
     * @param string $p_preposition 前置詞
     * @return array 送信データ
     */
    public function getCommandDataForSubTitle(string $p_typ, string $p_usr, string $p_preposition = 'by'): array
    {
        $cmd = "title @s subtitle §o§7{$p_preposition} {$p_usr}";
        $w_ret = $this->getCommandData($cmd, $p_typ);
        return $w_ret;
    }

    /**
     * マインクラフトへ送信するタイトルコマンドデータを取得
     * 
     * @param string $p_typ 処理タイプ文字列
     * @param string $p_cmt コメント
     * @param string $p_preposition 前置詞
     * @return array 送信データ
     */
    public function getCommandDataForTitle(string $p_typ, string $p_cmt): array
    {
        $cmd = "title @s title §e{$p_cmt}";
        $w_ret = $this->getCommandData($cmd, $p_typ);
        return $w_ret;
    }

    /**
     * マインクラフトへ送信するプライベートコメントコマンドデータを取得
     * 
     * @param string $p_typ 処理タイプ文字列
     * @param string $p_susr 送信元ユーザー名
     * @param string $p_dusr 送信先ユーザー名
     * @param string $p_cmt コメント
     * @return array 送信データ
     */
    public function getCommandDataForPrivate(string $p_typ, string $p_susr, string $p_dusr = null, string $p_cmt): array
    {
        $cmd = "msg @s {$p_cmt}[by {$p_susr}]";
        $w_ret = $this->getCommandData($cmd, $p_typ);
        return $w_ret;
    }

    /**
     * 待ち受けるレスポンス情報の設定
     * 
     * @param ?string $p_rid リクエストID
     * @param ?string $p_typ 処理タイプ文字列
     */
    public function setAwaitResponse(?string $p_rid, ?string $p_typ)
    {
        $this->setTempBuff(
            [
                'requestId' => $p_rid,
                'type' => $p_typ
            ]
        );
    }

    /**
     * 待ち受けるレスポンス情報の取得
     * 
     * @return array ['requestId' => <リクエストID>, 'type' => <実行するコマンド>]
     */
    public function getAwaitResponse()
    {
        $w_ret = $this->getTempBuff(['requestId', 'type']);
        return $w_ret;
    }


    //--------------------------------------------------------------------------
    // サーバー間通信リクエスト用
    //--------------------------------------------------------------------------

    /**
     * サーバー間通信でプライベートコメントを送信
     * 
     * @param string $p_suser 送信元ユーザー名
     * @param string $p_duser 送信先ユーザー名
     * @param string $p_comment 送信コメント
     */
    public function searchSendPrivateComment(string $p_suser, string $p_duser, string $p_comment)
    {
        $cid = $this->getConnectionId();

        $server = $this->server();
        if($server !== null)
        {
            $this->server()->requestPrivateComment($cid, $p_suser, $p_duser, $p_comment, null);
            return;
        }

        // サーバー間通信連携がない場合は結果NGで返信する
        $data =
        [
            'data' =>
            [
                'cmd' => CommandQueueEnumForMinecraft::PRIVATE_RESULT->value,
                'user' => $p_duser,
                'rno' => null,
                'result' => false
            ]
        ];
        $this->setRecvStack($data);

        return;
    }


    //--------------------------------------------------------------------------
    // サーバー間通信用パラメータクラスの連携用
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // その他
    //--------------------------------------------------------------------------

    /**
     * メッセージコマンド配信
     * 
     * @param array $p_msg メッセージコマンドデータ
     */
    public function sendMessage(array $p_msg)
    {
        $p_msg['result'] = true;

        // HTML変換
        $p_msg['comment'] = htmlspecialchars($p_msg['comment']);

        $minecraft = $this->isMinecraft();

        // 全ブラウザへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, $minecraft, function(ParameterForMinecraft $p_param)
        {
            return !$p_param->isMinecraft();
        }, $this);

        // 自身がマインクラフトなら抜ける
        if($minecraft === true)
        {
            return;
        }

        // 全マインクラフトへ配信
        $cmd_data = $this->getCommandDataForSubtitle('subtitle', $p_msg['user']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->setSendStackAll($data, $minecraft, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this);

        // 全マインクラフトへ配信
        $cmd_data = $this->getCommandDataForTitle('title', $p_msg['comment']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->setSendStackAll($data, $minecraft, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this);
    }

    /**
     * 退室コマンド配信
     * 
     * @param array $p_msg 退室コマンドデータ
     */
    public function sendExit(array $p_msg)
    {
        // 全ブラウザへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, true, function(ParameterForMinecraft $p_param)
        {
            return !$p_param->isMinecraft();
        }, $this);

        // 全マインクラフトへ配信
        $cmd_data = $this->getCommandDataForPrivate('exit', $p_msg['user'], null, $p_msg['comment']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->setSendStackAll($data, false, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this);

        // 自身の接続がマインクラフトかどうか
        $minecraft = $this->isMinecraft();

        if($minecraft === false)
        {
            // 切断パラメータを設定
            $close_param =
            [
                // 切断コード
                'code' => ParameterForMinecraft::CHAT_SELF_CLOSE_CODE,
                // シリアライズ対象データ
                'data' =>
                [
                    // 切断時パラメータ（現在日時）
                    'datetime' => $p_msg['datetime']
                ]
            ];

            // 自身を切断
            $this->close($close_param);
        }
    }

    /**
     * 切断コマンド配信
     * 
     * @param array $p_msg 退室コマンドデータ
     */
    public function sendClose(array $p_msg)
    {
        // 自身の接続がマインクラフトかどうか
        $minecraft = $this->isMinecraft();

        // 全ブラウザへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, $minecraft, function(ParameterForMinecraft $p_param)
        {
            return !$p_param->isMinecraft();
        }, $this);

        // 全マインクラフトへ配信
        $cmd_data = $this->getCommandDataForPrivate('private', self::CHAT_ADMIN_USER, null, $p_msg['comment']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->setSendStackAll($data, !$minecraft, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this);

        // 受信データを取得
        $w_ret = $this->getRecvData();

        // 自身の切断シーケンス開始
        $close_param =
        [
            // 切断コード
            'code' => $w_ret['close_code'],
            // シリアライズ対象データ
            'data' => $w_ret['data']
        ];
        $this->close($close_param);
    }

    /**
     * プライベートコメントを送信
     * 
     * @param string $p_src 送信元ユーザー名
     * @param string $p_dst 送信先ユーザー名
     * @param string $p_comment コメント
     * @return bool true（成功） or false（送信先ユーザーが存在しない）
     */
    public function sendPrivate(string $p_src, string $p_dst, string $p_comment)
    {
        $match_cid = null;
        foreach($this->user_list as $cid => $name)
        {
            // 送信先ユーザー名が一致
            if($p_dst === $name)
            {
                $match_cid = $cid;
            }
        }

        // 送信先ユーザーがいない
        if($match_cid === null)
        {
            return false;
        }

        $manager = $this->getSocketManager();

        // 送信データを作成
        $datetime = date(self::CHAT_DATETIME_FORMAT);
        $data =
        [
            'data' =>
            [
                'cmd' => CommandQueueEnumForMinecraft::PRIVATE->value,
                'datetime' => $datetime,
                'user_count' => $this->getClientCount(),
                'user' => $p_src,
                'comment' => $p_comment,
            ]
        ];

        // 自身の接続がマインクラフトかどうか
        $minecraft = $this->isMinecraft($match_cid);
        if($minecraft === true)
        {
            $minecraft_data = $this->getCommandDataForPrivate('private', $p_src, $p_dst, $p_comment);
            $data =
            [
                'data' => $minecraft_data
            ];
        }

        // 送信データをエントリ
        $manager->setSendStack($match_cid, $data);

        // チャットをログに残す
        $this->privateLogWriter($datetime, $p_src, $p_dst, $p_comment, true);

        return true;
    }

    /**
     * プライベートコメント返信
     * 
     * @param string $p_usr 宛先ユーザー名
     * @param array $p_cmts コメント
     * @param bool $p_res 結果
     */
    public function sendPrivateReply(string $p_usr, array $p_cmts, bool $p_res)
    {
        // 自身の接続がマインクラフトかどうか
        $minecraft = $this->isMinecraft();

        // コメント生成
        $cmt_join = '';
        foreach($p_cmts as $cmt)
        {
            if(strlen($cmt_join) > 0)
            {
                if($minecraft === true)
                {
                    $cmt_join .= '。';
                }
                else
                {
                    $cmt_join .= '<br />';
                }
            }
            $cmt_join .= $cmt;
        }

        // 送信データ生成
        $data = [];
        if($minecraft === true)
        {
            $minecraft_data = $this->getCommandDataForPrivate('private-reply', self::CHAT_ADMIN_USER, null, $cmt_join);
            $data =
            [
                'data' => $minecraft_data
            ];
        }
        else
        {
            $data =
            [
                'data' =>
                [
                    'cmd' => 'private-reply',   // クライアントへ返すコマンド
                    'user' => $p_usr,
                    'result' => $p_res,
                    'comment' => $cmt_join
                ]
            ];
        }

        // 送信
        $this->setSendStack($data);
    }

    /**
     * 入室時返信
     * 
     * ユーザー名重複なし時の返信
     * 
     * @param array $p_msg 入室コマンドデータ
     */
    public function sendEntranceReply(array $p_msg)
    {
        // マインクラフト接続かどうか
        $minecraft = $this->isMinecraft();

        // 全ブラウザへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, true, function(ParameterForMinecraft $p_param)
        {
            return !$p_param->isMinecraft();
        }, $this);

        // 全マインクラフトへ配信
        $cmd_data = $this->getCommandDataForPrivate('usersearch-result', $p_msg['user'], null, $p_msg['comment']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->setSendStackAll($data, !$minecraft, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this);

        // マインクラフトでない場合
        if($minecraft !== true)
        {
            // オプションデータを設定
            $p_msg['opts'] = $this->getOptions();
            $data =
            [
                'data' => $p_msg
            ];
            // 自身へメッセージ配信
            $this->setSendStack($data);
        }
        else
        {
            /**
             * サブスクライブのエントリ
             */

             $this->sendSubscribesData();
        }
    }

    /**
     * コメントなし時のレスポンス配信
     * 
     * @param array $p_msg レスポンスデータ
     */
    public function responseNoComment(array $p_msg)
    {
        $minecraft = $this->isMinecraft();
        if($minecraft === true)
        {
            // マインクラフトへ配信
            $cmd_data = $this->getCommandDataForPrivate('no-comment', self::CHAT_ADMIN_USER, null, $this->options['no_comment']);
            $data =
            [
                'data' => $cmd_data
            ];
            $this->setSendStack($data);
        }
        else
        {
            // レスポンス配信
            $data =
            [
                'data' => $p_msg
            ];
            $this->setSendStack($data);
        }
    }

    /**
     * ユーザー名重複時の切断処理
     * 
     * @return ?string ステータス名 or null
     */
    public function closeUserDuplication(): ?string
    {
        $minecraft = $this->isMinecraft();
        if($minecraft === true)
        {
            // マインクラフトへ配信
            $cmd_data = $this->getCommandDataForPrivate('user-duplication', self::CHAT_ADMIN_USER, null, $this->options['duplication_comment']);
            $data =
            [
                'data' => $cmd_data
            ];
            $this->setSendStack($data);
            return CommandStatusEnumForMinecraft::SENDING->value;
        }
        else
        {
            $close_param =
            [
                // 切断コード
                'code' => ParameterForWebsocket::CHAT_DUPLICATION_CLOSE_CODE,
                // シリアライズ対象データ
                'data' =>
                [
                    // 切断時パラメータ（現在日時）
                    'datetime' => date(ParameterForWebsocket::CHAT_DATETIME_FORMAT)
                ]
            ];
            $this->close($close_param);
            return null;
        }
    }

    /**
     * ユーザー名なし時の切断処理
     */
    public function closeNoUser()
    {
        $minecraft = $this->isMinecraft();
        if($minecraft === true)
        {
            // マインクラフトへ配信
            $cmd_data = $this->getCommandDataForPrivate('no-user', self::CHAT_ADMIN_USER, null, $this->options['no_user_comment']);
            $data =
            [
                'data' => $cmd_data
            ];
            $this->setSendStack($data);
        }
        else
        {
            $close_param =
            [
                // 切断コード
                'code' => ParameterForWebsocket::CHAT_NO_USER_CLOSE_CODE,
                // シリアライズ対象データ
                'data' =>
                [
                    // 切断時パラメータ（現在日時）
                    'datetime' => date(ParameterForWebsocket::CHAT_DATETIME_FORMAT)
                ]
            ];
            $this->close($close_param);
        }
    }

    /**
     * クライアントからの強制切断時のコールバック
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     */
    public function forcedCloseFromClient(ParameterForWebsocket $p_param)
    {
        // クラスのキャストのため代入
        $this->param = $p_param;

        $this->param->logWriter('debug', [__METHOD__ => '緊急切断', 'minecraft' => "flag[{$this->param->isMinecraft()}]"]);

        $msg = [];

        // コマンドを設定
        $msg['cmd'] = CommandQueueEnumForMinecraft::CLOSE->value;

        // 現在日時を設定
        $msg['datetime'] = date(ParameterForMinecraft::CHAT_DATETIME_FORMAT);

        // 現在のユーザー数を設定
        $msg['count'] = $this->param->getClientCount() - 1;

        // 自身のユーザー名を設定
        $msg['user'] = $this->param->getUserName();
        if($msg['user'] === null)
        {
            return;
            $msg['user'] = $this->options['unknown_user'];
        }

        // 退室コメントを設定
        $opts = $this->param->getOptions();
        $msg['comment'] = $opts['forced_close_comment'];

        // ユーザー名をリストから削除
        $this->param->delUserName();

        // ユーザーリストを設定
        $msg['user_list'] = $this->param->getUserList();

        // 全ブラウザへ配信
        $data =
        [
            'data' => $msg
        ];
        $this->param->setSendStackAll($data, true, function(ParameterForMinecraft $p_param)
        {
            return !$p_param->isMinecraft();
        }, $this->param);

        // 全マインクラフトへ配信
        $cmd_data = $this->param->getCommandDataForPrivate('forced-close', $msg['user'], null, $msg['comment']);
        $data =
        [
            'data' => $cmd_data
        ];
        $this->param->setSendStackAll($data, true, function(ParameterForMinecraft $p_param)
        {
            return $p_param->isMinecraft();
        }, $this->param);

        // チャットをログに残す
        $this->param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);

        return;
    }
}
