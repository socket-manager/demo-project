<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetCommandUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\CommandUnits;

use SocketManager\Library\IEntryUnits;
use App\UnitParameter\ParameterForWebsocket;


/**
 * コマンドUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class CommandForWebsocket implements IEntryUnits
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        CommandQueueEnumForWebsocket::ENTRANCE->value,         // entranceコマンドを処理するキュー
        CommandQueueEnumForWebsocket::MESSAGE->value,          // messageコマンドを処理するキュー
        CommandQueueEnumForWebsocket::EXIT->value,             // exitコマンドを処理するキュー
        CommandQueueEnumForWebsocket::CLOSE->value,            // closeコマンドを処理するキュー
        CommandQueueEnumForWebsocket::PRIVATE->value,          // privateコマンドを処理するキュー
        CommandQueueEnumForWebsocket::PRIVATE_RESULT->value,   // private-resultコマンドを処理するキュー
        CommandQueueEnumForWebsocket::USERSEARCH_RESULT->value // usersearch-resultコマンドを処理するキュー
    ];


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    // UNITパラメータ（キャスト用）
    private ParameterForWebsocket $param;


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

        if($p_que === CommandQueueEnumForWebsocket::ENTRANCE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getEntranceStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::MESSAGE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getMessageStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::EXIT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getExitStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::CLOSE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getCloseStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::PRIVATE->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getPrivateStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::PRIVATE_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getPrivateResultStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForWebsocket::USERSEARCH_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForWebsocket::START->value,
                'unit' => $this->getUserSearchResultStart()
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
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getEntranceStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $p_param->logWriter('debug', ['WEBSOCKET ENTRANCE:START' => 'START']);

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

                    // 自身の接続を除く全コネクションへ配信
                    $data =
                    [
                        'data' => $msg
                    ];
                    $p_param->setSendStackAll($data, true);
            
                    // オプションデータを設定
                    $msg['opts'] = $p_param->getOptions();
                    $data =
                    [
                        'data' => $msg
                    ];
                    // 自身へメッセージ配信
                    $p_param->setSendStack($data);
            
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
    // 以降はステータスUNITの定義（"MESSAGE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：メッセージ送信開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getMessageStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $this->param = $p_param;
            $this->param->logWriter('debug', ['WEBSOCKET MESSAGE:START' => 'START']);

            // 受信データを取得
            $w_ret = $this->param->getRecvData();
            $msg = $w_ret['data'];

            // ユーザー名MAX長調整
            $msg['user'] = mb_substr($msg['user'], 0, ParameterForWebsocket::CHAT_USER_NAME_MAX_LENGTH);

            // コメントMAX長調整
            $msg['comment'] = mb_substr($msg['comment'], 0, ParameterForWebsocket::CHAT_COMMENT_MAX_LENGTH);

            // 現在日時を設定
            $msg['datetime'] = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
    
            // 現在のユーザー数を設定
            $msg['count'] = $this->param->getClientCount();

            // ユーザー名を設定
            $msg['user'] = $this->param->getUserName();

            $msg['comment'] = $this->param->chopHiddenCharacters($msg['comment']);
            if($msg['comment'] == '')
            {
                $msg['comment'] = $this->param->getOption('no_comment');
                $msg['result'] = false;
                $this->param->responseNoComment($msg);
                return null;
            }

            // メッセージ配信
            $this->param->sendMessage($msg);

            // チャットをログに残す
            $this->param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);
    
            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"EXIT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：退室開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getExitStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $this->param = $p_param;
            $this->param->logWriter('debug', ['WEBSOCKET EXIT:START' => 'START']);

            // 受信データを取得
            $w_ret = $this->param->getRecvData();
            $msg = $w_ret['data'];

            // 現在日時を設定
            $msg['datetime'] = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);

            // 現在のユーザー数を設定
            $msg['count'] = $this->param->getClientCount() - 1;

            // 自身のユーザー名を設定
            $msg['user'] = $this->param->getUserName();

            // 退室コメントを設定
            $msg['comment'] = ParameterForWebsocket::CHAT_EXIT_COMMENT;

            // ユーザー名をリストから削除
            $this->param->delUserName();

            // ユーザーリストを設定
            $msg['user_list'] = $this->param->getUserList();

            // 退室コマンド配信
            $this->param->sendExit($msg);

            // チャットをログに残す
            $this->param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);

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
            $this->param = $p_param;
            $p_param->logWriter('debug', ['WEBSOCKET CLOSE:START' => 'START']);

            $msg = [];

            // コマンドを設定
            $msg['cmd'] = CommandQueueEnumForWebsocket::EXIT->value;
    
            // 現在日時を設定
            $msg['datetime'] = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
    
            // 現在のユーザー数を設定
            $msg['count'] = $this->param->getClientCount() - 1;
    
            // 自身のユーザー名を設定
            $msg['user'] = $this->param->getUserName();
    
            // 退室コメントを設定
            $msg['comment'] = ParameterForWebsocket::CHAT_EXIT_COMMENT;
    
            // ユーザー名をリストから削除
            $this->param->delUserName();
    
            // ユーザーリストを設定
            $msg['user_list'] = $this->param->getUserList();

            // 切断コマンド配信
            $this->param->sendClose($msg);

            // チャットをログに残す
            $this->param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"PRIVATE"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：プライベートコメント処理開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getPrivateStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $this->param = $p_param;
            $p_param->logWriter('debug', ['WEBSOCKET PRIVATE:START' => 'START']);

            // 受信データを取得
            $w_ret = $this->param->getRecvData();
            $msg = $w_ret['data'];

            // ユーザー名MAX長調整
            $msg['user'] = mb_substr($msg['user'], 0, ParameterForWebsocket::CHAT_USER_NAME_MAX_LENGTH);

            // コメントMAX長調整
            $msg['comment'] = mb_substr($msg['comment'], 0, ParameterForWebsocket::CHAT_COMMENT_MAX_LENGTH);

            $result = true;
            $comment = [];

            // 宛先ユーザー名のチェック
            $msg['user'] = $this->param->chopHiddenCharacters($msg['user']);
            if($msg['user'] == '')
            {
                $result = false;
                $comment[] = $this->param->getOption('no_user_comment');
            }

            // コメントのチェック
            $msg['comment'] = $this->param->chopHiddenCharacters($msg['comment']);
            if($msg['comment'] == '')
            {
                $result = false;
                $comment[] = $this->param->getOption('no_comment');
            }

            // NGの場合
            if($result === false)
            {
                $this->param->sendPrivateReply($msg['user'], $comment, $result);
                return null;
            }

            // HTML変換
            $msg['user'] = htmlspecialchars($msg['user']);
            $msg['comment'] = htmlspecialchars($msg['comment']);

            // プライベートコメントの送信
            $w_ret = $this->param->sendPrivate($this->param->getUserName(), $msg['user'], $msg['comment']);
            if($w_ret === false)
            {
                // ローカルで見つからない場合はサーバー間通信を使う
                $this->param->searchSendPrivateComment($this->param->getUserName(), $msg['user'], $msg['comment']);
                return null;
            }

            // 送信データ作成
            $comment = ParameterForWebsocket::CHAT_PRIVATE_OK;
            $comment = str_replace(':name', $msg['user'], $comment);

            // 返信
            $this->param->sendPrivateReply($msg['user'], [$comment], true);

            // チャットをログに残す
            $src_user = $this->param->getUserName();
            $datetime = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
            $this->param->privateLogWriter($datetime, $src_user, $msg['user'], $comment, true);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"PRIVATE_RESULT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：プライベートコメント検索結果処理開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getPrivateResultStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $this->param = $p_param;
            $this->param->logWriter('debug', ['WEBSOCKET PRIVATE-RESULT:START' => 'START']);

            // 受信データを取得
            $w_ret = $this->param->getRecvData();
            $msg = $w_ret['data'];

            // 送信データ作成
            $comment = ParameterForWebsocket::CHAT_PRIVATE_NG;
            if($msg['result'] === true)
            {
                $comment = ParameterForWebsocket::CHAT_PRIVATE_OK;
            }
            $comment = str_replace(':name', $msg['user'], $comment);

            // 送信結果を返信
            $this->param->sendPrivateReply($msg['user'], [$comment], $msg['result']);

            // チャットをログに残す
            $src_user = $this->param->getUserName();
            $datetime = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
            $this->param->privateLogWriter($datetime, $src_user, $msg['user'], $comment, $msg['result']);

            return null;
        };
    }


    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"USERSEARCH_RESULT"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：ユーザー検索結果処理開始
     * 
     * @param ParameterForWebsocket $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getUserSearchResultStart()
    {
        return function(ParameterForWebsocket $p_param): ?string
        {
            $this->param = $p_param;
            $this->param->logWriter('debug', ['WEBSOCKET USERSEARCH-RESULT:START' => 'START']);

            // 受信データを取得
            $w_ret = $this->param->getRecvData();
            $res = $w_ret['data'];

            // ユーザーが見つかった（重複していた）時
            if($res['result'] === true)
            {
                // 自身の切断シーケンス開始
                $sta = $this->param->closeUserDuplication();
                return $sta;
            }

            $msg = ['cmd' => CommandQueueEnumForWebsocket::ENTRANCE->value];

            // 現在日時を設定
            $msg['datetime'] = date(ParameterForWebsocket::CHAT_DATETIME_FORMAT);
    
            // 現在のユーザー数を設定
            $msg['count'] = $this->param->getClientCount();

            // ユーザー名を設定
            $msg['user'] = $res['user'];
    
            // 入室コメントを設定
            $msg['comment'] = ParameterForWebsocket::CHAT_ENTRANCE_COMMENT;
    
            // ユーザー名をリストへ追加
            $this->param->addUserName($res['user']);

            // ユーザーリストを設定
            $msg['user_list'] = $this->param->getUserList();

            // 入室時返信
            $this->param->sendEntranceReply($msg);

            // チャットをログに残す
            $this->param->chatLogWriter($msg['datetime'], $msg['user'], $msg['comment']);
    
            return null;
        };
    }

}
