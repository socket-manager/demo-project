<?php
/**
 * UNITパラメータクラスのファイル
 * 
 * Websocket版
 */

namespace App\UnitParameter;


use App\UnitParameter\IChatParameter;
use App\CommandUnits\CommandQueueEnumForWebsocket;
use SocketManager\Library\SocketManagerParameter;


/**
 * UNITパラメータクラス
 * 
 * UNITパラメータクラスのSocketManagerParameterをオーバーライドする
 */
class ParameterForWebsocket extends SocketManagerParameter
{
    //--------------------------------------------------------------------------
    // 定数（first byte）
    //--------------------------------------------------------------------------

    /**
     * 最後の断片
     */
    public const CHAT_FIN_BIT_MASK = 0x80;

    /**
     * テキストフレーム
     */
    public const CHAT_OPCODE_TEXT_MASK = 0x01;

    /**
     * 切断フレーム
     */
    public const CHAT_OPCODE_CLOSE_MASK = 0x08;

    /**
     * pingフレーム
     */
    public const CHAT_OPCODE_PING_MASK = 0x09;

    /**
     * pongフレーム
     */
    public const CHAT_OPCODE_PONG_MASK = 0x0A;


    //--------------------------------------------------------------------------
    // 定数（second byte）
    //--------------------------------------------------------------------------

    /**
     * データ長マスク
     */
    public const CHAT_PAYLOAD_LEN_MASK = 0x7f;

    /**
     * データ長サイズコード（2 byte）
     */
    public const CHAT_PAYLOAD_LEN_CODE_2 = 126;

    /**
     * データ長サイズコード（8 byte）
     */
    public const CHAT_PAYLOAD_LEN_CODE_8 = 127;


    //--------------------------------------------------------------------------
    // 定数（切断コード）
    //--------------------------------------------------------------------------

    /**
     * 自身による退室
     */
    public const CHAT_SELF_CLOSE_CODE = 10;

    /**
     * サーバーからの切断
     */
    public const CHAT_SERVER_CLOSE_CODE = 20;

    /**
     * サーバーからの切断（ユーザー名重複）
     */
    public const CHAT_DUPLICATION_CLOSE_CODE = 30;

    /**
     * サーバーからの切断（ユーザー名なし）
     */
    public const CHAT_NO_USER_CLOSE_CODE = 40;

    /**
     * クライアントからの切断
     */
    public const CHAT_CLIENT_CLOSE_CODE = 3010;


    //--------------------------------------------------------------------------
    // 定数（その他）
    //--------------------------------------------------------------------------

    /**
     * 対応プロトコルバージョン
     */
    public const CHAT_PROTOCOL_VERSION = 13;

    /**
     * openingハンドシェイクのリトライ件数
     */
    public const CHAT_HANDSHAKE_RETRY = 3;

    /**
     * 受信空振り時のリトライ回数
     */
    public const CHAT_RECEIVE_EMPTY_RETRY = 10;

    /**
     * 入室コメント
     */
    public const CHAT_ENTRANCE_COMMENT = '入室しました';

    /**
     * 退室コメント
     */
    public const CHAT_EXIT_COMMENT = '退室しました';

    /**
     * 日時フォーマット
     */
    public const CHAT_DATETIME_FORMAT = 'Y/m/d H:i:s';

    /**
     * プライベートコメント送信成功時
     */
    public const CHAT_PRIVATE_OK = ':nameさんへ送信されました';

    /**
     * プライベートコメント送信失敗時
     */
    public const CHAT_PRIVATE_NG = ':nameさんは見つかりませんでした';

    /**
     * チャットログのパス
     */
    public const CHAT_LOG_PATH = '.'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'chat-log'.DIRECTORY_SEPARATOR;

    /**
     * ログファイル名日時形式
     */
    public const CHAT_LOG_FILENAME_DATETIME = 'Ymd';

    /**
     * ユーザー名MAX長
     */
    public const CHAT_USER_NAME_MAX_LENGTH = 8;

    /**
     * コメントMAX長
     */
    public const CHAT_COMMENT_MAX_LENGTH = 34;


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * TLSフラグ
     */
    protected bool $tls = false;

    /**
     * ユーザーリスト
     */
    protected array $user_list = [];

    /**
     * オプションデータ
     * 
     * ※入室時にサーバーから送信するデータ
     */
    protected array $options =
    [
        /**
         * 不明な日付文字列
         */
        'unknown_datetime' => '----/--/-- --:--:--',

        /**
         * 不明なユーザー名
         */
        'unknown_user' => '-----',

        /**
         * 運営サイドのユーザー名
         */
        'admin_user' => '運営チーム',

        /**
         * 退室コメント
         */
        'exit_comment' => self::CHAT_EXIT_COMMENT,

        /**
         * サーバーからの切断コメント
         */
        'server_close_comment' => 'サーバーから切断されました',

        /**
         * 強制切断コメント
         */
        'forced_close_comment' => '切断されました',

        /**
         * 予期しない切断コメント
         */
        'unexpected_close_comment' => '予期せず切断されました',

        /**
         * エラーコメント
         */
        'error_comment' => 'エラーが発生しました',

        /**
         * ユーザー名重複コメント
         */
        'duplication_comment' => 'そのユーザー名は既に使用されています',

        /**
         * ユーザー名なしコメント
         */
        'no_user_comment' => 'ユーザー名を入力してください',

        /**
         * コメントなし
         */
        'no_comment' => 'コメントを入力してください'
    ];

    protected ?IChatParameter $server = null;


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
        parent::__construct();

        if($p_tls !== null)
        {
            $this->tls = $p_tls;
        }
    }


    //--------------------------------------------------------------------------
    // プロパティアクセス用
    //--------------------------------------------------------------------------

    /**
     * TLSフラグの取得
     * 
     * @return bool TLSフラグ
     */
    public function getTls()
    {
        $w_ret = $this->tls;
        return $w_ret;
    }

    /**
     * 入室時のオプションデータを取得
     * 
     * @return array オプションデータ
     */
    public function getOptions()
    {
        $w_ret = $this->options;
        return $w_ret;
    }

    /**
     * オプションデータを取得
     * 
     * @param string $p_key オプションキー
     * @return string オプションデータ
     */
    public function getOption(string $p_key)
    {
        $w_ret = $this->options[$p_key];
        return $w_ret;
    }

    /**
     * 受信空振り時のリトライ回数取得
     * 
     * @return int リトライ回数
     */
    public function getRecvRetry()
    {
        $w_ret = $this->getTempBuff(['recv_retry']);
        return $w_ret['recv_retry'];
    }

    /**
     * 受信空振り時のリトライ回数設定
     * 
     * @param int $p_cnt リトライ回数
     */
    public function setRecvRetry(int $p_cnt)
    {
        $this->setTempBuff(['recv_retry' => $p_cnt]);
        return;
    }


    //--------------------------------------------------------------------------
    // ログ出力関係
    //--------------------------------------------------------------------------

    /**
     * チャットログの書き込み
     * 
     * @param string $p_datetime 日時
     * @param string $p_user コメ主ユーザー
     * @param string $p_comment コメント
     */
    public function chatLogWriter(string $p_datetime, string $p_user, string $p_comment)
    {
        $port = $this->getAwaitPort();
        $filename = date(self::CHAT_LOG_FILENAME_DATETIME);
        $log = $p_datetime." [{$p_user}] {$p_comment}\n";
        error_log($log, 3, self::CHAT_LOG_PATH."{$filename}_W{$port}.log");
    }

    /**
     * プライベートチャットログの書き込み
     * 
     * @param string $p_datetime 日時
     * @param string $p_suser 送信元ユーザー
     * @param string $p_duser 送信先ユーザー
     * @param string $p_comment コメント
     * @param bool $p_result 送信結果
     */
    public function privateLogWriter(string $p_datetime, string $p_suser, string $p_duser, string $p_comment, bool $p_result)
    {
        $port = $this->getAwaitPort();
        $result = 'ng';
        if($p_result === true)
        {
            $result = 'ok';
        }
        $filename = date(self::CHAT_LOG_FILENAME_DATETIME);
        $log = $p_datetime." [{$p_suser}⇒{$p_duser}(private {$result})] {$p_comment}\n";
        error_log($log, 3, self::CHAT_LOG_PATH."{$filename}_W{$port}.log");
    }


    //--------------------------------------------------------------------------
    // openinngハンドシェイク時のヘッダ情報管理用
    //--------------------------------------------------------------------------

    /**
     * ハンドシェイク時のヘッダ情報の取得
     * 
     * @param string $p_cid 接続ID
     * @return ?array ヘッダ情報
     */
    public function getHeaders(string $p_cid = null): ?array
    {
        $cid = null;
        if($p_cid !== null)
        {
            $cid = $p_cid;
        }
        $w_ret = null;

        // ユーザープロパティの取得
        $w_ret = $this->getTempBuff(['headers'], $cid);
        if($w_ret === null)
        {
            return null;
        }

        return $w_ret['headers'];
    }

    /**
     * ハンドシェイク時のヘッダ情報の設定
     * 
     * @param array $p_prop プロパティのリスト
     */
    public function setHeaders(array $p_prop)
    {
        // ユーザープロパティの設定
        $this->setTempBuff(['headers' => $p_prop]);
        return;
    }


    //--------------------------------------------------------------------------
    // ユーザーリスト管理用
    //--------------------------------------------------------------------------

    /**
     * ユーザー名重複チェック
     * 
     * @param string $p_user ユーザー名
     * @return bool true（重複なし） or false（重複あり）
     */
    public function checkUserName(string $p_user): bool
    {
        $check = true;
        foreach($this->user_list as $cid => $user)
        {
            // 重複している
            if($user === $p_user)
            {
                $check = false;
            }
        }

        return $check;
    }

    /**
     * ユーザーリストの取得
     * 
     * @return array ユーザーリスト
     */
    public function getUserList(): array
    {
        $w_ret = array_values($this->user_list);
        return $w_ret;
    }

    /**
     * 自身のユーザー名取得
     * 
     * @return ?string ユーザー名 or null（空）
     */
    public function getUserName(): ?string
    {
        $cid = $this->getConnectionId();
        $w_ret = null;
        if(isset($this->user_list[$cid]))
        {
            $w_ret = $this->user_list[$cid];
        }
        return $w_ret;
    }

    /**
     * ユーザー名をリストへ追加
     * 
     * @param string $p_name ユーザー名
     */
    public function addUserName(string $p_name)
    {
        $cid = $this->getConnectionId();
        $this->user_list[$cid] = $p_name;
    }

    /**
     * ユーザー名をリストから削除
     * 
     */
    public function delUserName()
    {
        $cid = $this->getConnectionId();
        unset($this->user_list[$cid]);
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
                'cmd' => CommandQueueEnumForWebsocket::PRIVATE_RESULT->value,
                'user' => $p_duser,
                'rno' => null,
                'result' => false
            ]
        ];
        $this->setRecvStack($data);

        return;
    }

    /**
     * サーバー間通信でユーザー検索を送信
     * 
     * @param string $p_user 検索ユーザー名
     * @return bool true（サーバー間通信実施） or false（見つからない）
     */
    public function searchSendUserSearch(string $p_user)
    {
        $cid = $this->getConnectionId();

        $server = $this->server();
        if($server !== null)
        {
            $this->server()->requestUserSearch($cid, $p_user, null);
            return true;
        }

        return false;
    }


    //--------------------------------------------------------------------------
    // サーバー間通信用パラメータクラスの連携用
    //--------------------------------------------------------------------------

    /**
     * サーバー間通信用のUNITパラメータクラスを設定
     * 
     * @param IChatParameter サーバー間通信用のUNITパラメータクラスインスタンス
     */
    public function setChatParameterForServer(IChatParameter $p_param)
    {
        $this->server = $p_param;
        return;
    }

    /**
     * サーバー間通信用のUNITパラメータの取得
     * 
     * @return ?IChatParameter サーバー間通信用のUNITパラメータ
     */
    public function server(): ?IChatParameter
    {
        $w_ret = $this->server;
        return $w_ret;
    }


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

        // 全コネクションへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data);
    }

    /**
     * 退室コマンド配信
     * 
     * @param array $p_msg 退室コマンドデータ
     */
    public function sendExit(array $p_msg)
    {
        // 自身を除く全コネクションへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, true);

        // 切断パラメータを設定
        $close_param =
        [
            // 切断コード
            'code' => ParameterForWebsocket::CHAT_SELF_CLOSE_CODE,
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

    /**
     * 切断コマンド配信
     * 
     * @param array $p_msg 退室コマンドデータ
     */
    public function sendClose(array $p_msg)
    {
        // 自身を除く全コネクションへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, true);

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
        $data =
        [
            'data' =>
            [
                'cmd' => CommandQueueEnumForWebsocket::PRIVATE->value,
                'datetime' => date(self::CHAT_DATETIME_FORMAT),
                'count' => $this->getClientCount(),
                'user' => $p_src,
                'comment' => $p_comment,
            ]
        ];

        // 送信データをエントリ
        $manager->setSendStack($match_cid, $data);

        // チャットをログに残す
        $this->privateLogWriter($data['data']['datetime'], $p_src, $p_dst, $p_comment, true);

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
        $cmt_join = '';
        foreach($p_cmts as $cmt)
        {
            if(strlen($cmt_join) > 0)
            {
                $cmt_join .= '<br />';
            }
            $cmt_join .= $cmt;
        }

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
        // 自身の接続を除く全コネクションへ配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStackAll($data, true);

        // オプションデータを設定
        $p_msg['opts'] = $this->getOptions();
        $data =
        [
            'data' => $p_msg
        ];
        // 自身へメッセージ配信
        $this->setSendStack($data);
    }

    /**
     * ユーザー検索
     * 
     * @param string $p_user 検索ユーザー名
     * @return bool true（ユーザー名が存在する） or false（ユーザー名が存在しない）
     */
    public function searchUser(string $p_user)
    {
        $match_cid = null;
        foreach($this->user_list as $cid => $name)
        {
            // 送信先ユーザー名が一致
            if($p_user === $name)
            {
                $match_cid = $cid;
            }
        }

        // 送信先ユーザーがいない
        if($match_cid === null)
        {
            return false;
        }

        return true;
    }

    /**
     * コメントなし時のレスポンス配信
     * 
     * @param array $p_msg レスポンスデータ
     */
    public function responseNoComment(array $p_msg)
    {
        // レスポンス配信
        $data =
        [
            'data' => $p_msg
        ];
        $this->setSendStack($data);
    }

    /**
     * ユーザー名重複時の切断処理
     * 
     * @return ?string ステータス名 or null
     */
    public function closeUserDuplication(): ?string
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

    /**
     * ユーザー名なし時の切断処理
     */
    public function closeNoUser()
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

    /**
     * 非表示文字の切り捨て
     * 
     * @param string $p_str 加工対象文字列
     * @return string 加工後文字列
     */
    public function chopHiddenCharacters(string $p_str)
    {
        $p_str = preg_replace('/^[ 　]+/u', '', $p_str);
        $p_str = preg_replace('/[ 　]+$/u', '', $p_str);
        return $p_str;
    }

    /**
     * クライアントからの強制切断時のコールバック
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     */
    public function forcedCloseFromClient(ParameterForWebsocket $p_param)
    {
        $msg = [];

        // コマンドを設定
        $msg['cmd'] = CommandQueueEnumForWebsocket::CLOSE->value;

        // 現在日時を設定
        $msg['datetime'] = date(self::CHAT_DATETIME_FORMAT);

        // 現在のユーザー数を設定
        $msg['count'] = $p_param->getClientCount() - 1;

        // 自身のユーザー名を設定
        $msg['user'] = $p_param->getUserName();

        // 退室コメントを設定
        $msg['comment'] = $this->options['forced_close_comment'];

        // ユーザー名をリストから削除
        $p_param->delUserName();

        // ユーザーリストを設定
        $msg['user_list'] = $p_param->getUserList();

        // 自身を除く全コネクションへ配信
        $data =
        [
            'data' => $msg
        ];
        $p_param->setSendStackAll($data, true);

        // チャットをログに残す
        $p_param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);
    }

}
