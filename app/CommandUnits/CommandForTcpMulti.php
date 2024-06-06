<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetCommandUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\CommandUnits;

use SocketManager\Library\IEntryUnits;
use App\UnitParameter\ParameterForTcpMulti;


/**
 * コマンドUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class CommandForTcpMulti implements IEntryUnits
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    // キューリスト
    protected const QUEUE_LIST = [
        CommandQueueEnumForTcpMulti::PRIVATE_SEARCH->value,	    // private-searchコマンドを処理するキュー
        CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value,	    // private-resultコマンドを処理するキュー
        CommandQueueEnumForTcpMulti::USERSEARCH->value,	        // user-searchコマンドを処理するキュー
        CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value,  // usersearch-resultコマンドを処理するキュー
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

        if($p_que === CommandQueueEnumForTcpMulti::PRIVATE_SEARCH->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForTcpMulti::START->value,
                'unit' => $this->getPrivateSearchStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForTcpMulti::START->value,
                'unit' => $this->getPrivateResultStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForTcpMulti::USERSEARCH->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForTcpMulti::START->value,
                'unit' => $this->getUserSearchStart()
            ];
        }
        else
        if($p_que === CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value)
        {
            $ret[] = [
                'status' => CommandStatusEnumForTcpMulti::START->value,
                'unit' => $this->getUserSearchResultStart()
            ];
        }

        return $ret;
    }

    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"PRIVATE_SEARCH"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：プライベートコメント検索処理開始
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getPrivateSearchStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            // 受信データを取得
            $msg = $p_param->getRecvData();
            $p_param->logWriter('debug', ['search request recv data' => $msg]);
    
            // 相手先へプライベートコメントを送信
            $w_ret = $p_param->websocket()->sendPrivate($msg['suser'], $msg['duser'], $msg['comment']);

            $p_param->logWriter('debug', ['after send private' => $w_ret]);
            // レスポンスデータを作成
            $response_data = [];
            if($w_ret === true)
            {
                $response_data =
                [
                    'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value,
                    'user' => $msg['duser'],
                    'rno' => $msg['rno'],
                    'result' => true
                ];
            }
            else
            {
                $cid = $p_param->getAwaitConnectionId();
                $p_param->logWriter('debug', ['cid after send private' => $cid]);

                // 親サーバー
                if($cid !== null)
                {
                    // リクエストデータを生成して子サーバーへブロードキャスト
                    $cid = $p_param->getConnectionId();
                    $p_param->requestPrivateComment($cid, $msg['suser'], $msg['duser'], $msg['comment'], $msg['rno']);
                    return null;
                }
                // 子サーバー
                else
                {
                    $response_data =
                    [
                        'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value,
                        'user' => $msg['duser'],
                        'rno' => $msg['rno'],
                        'result' => false
                    ];
                }
            }

            // 送信データを設定
            $p_param->setSendStack($response_data);

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
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getPrivateResultStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            // 受信データを取得
            $msg = $p_param->getRecvData();
    
            // リクエストキューを取得
            $que = $p_param->getRequestQueue($msg['rno']);

            // 成否を格納
            if($que['result'] !== true)
            {
                if($msg['result'] === true)
                {
                    $que['result'] = true;
                }
                else
                {
                    $que['result'] = false;
                }
            }
            $que['cnt']++;

            // リクエストキューへ設定
            $p_param->setRequestQueue($msg['rno'], $que);

            // 成功だったら
            if($msg['result'] === true)
            {
                // Websocket側の受信コマンドとして登録する
                $p_param->resultRecvPrivateComment($msg['rno']);
            }

            // 検索結果が出揃った
            if($que['cnt'] >= $que['max'])
            {
                // 失敗だったら
                if($que['result'] === false)
                {
                    // Websocket側の受信コマンドとして登録する
                    $p_param->resultRecvPrivateComment($msg['rno']);
                }

                // キューの後始末
                $p_param->delRequestQueue($msg['rno']);
            }
    
            return null;
        };
    }

    //--------------------------------------------------------------------------
    // 以降はステータスUNITの定義（"USERSEARCH"キュー）
    //--------------------------------------------------------------------------

    /**
     * ステータス名： START
     * 
     * 処理名：ユーザー検索処理開始
     * 
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getUserSearchStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            // 受信データを取得
            $msg = $p_param->getRecvData();
            $p_param->logWriter('debug', ['search request recv data' => $msg]);
    
            // ユーザー検索
            $w_ret = $p_param->websocket()->searchUser($msg['user']);

            $p_param->logWriter('debug', ['after user search' => $w_ret]);
            // レスポンスデータを作成
            $response_data = [];
            if($w_ret === true)
            {
                $response_data =
                [
                    'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value,
                    'user' => $msg['user'],
                    'rno' => $msg['rno'],
                    'result' => true
                ];
            }
            else
            {
                $cid = $p_param->getAwaitConnectionId();
                $p_param->logWriter('debug', ['cid after user search' => $cid]);

                // 親サーバー
                if($cid !== null)
                {
                    // リクエストデータを生成して子サーバーへブロードキャスト
                    $cid = $p_param->getConnectionId();
                    $p_param->requestUserSearch($cid, $msg['user'], $msg['rno']);
                    return null;
                }
                // 子サーバー
                else
                {
                    $response_data =
                    [
                        'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value,
                        'user' => $msg['user'],
                        'rno' => $msg['rno'],
                        'result' => false
                    ];
                }
            }

            // 送信データを設定
            $p_param->setSendStack($response_data);

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
     * @param ParameterForTcpMulti $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    private function getUserSearchResultStart()
    {
        return function(ParameterForTcpMulti $p_param): ?string
        {
            // 受信データを取得
            $msg = $p_param->getRecvData();
    
            // リクエストキューを取得
            $que = $p_param->getRequestQueue($msg['rno']);

            // 成否を格納
            if($que['result'] !== true)
            {
                if($msg['result'] === true)
                {
                    $que['result'] = true;
                }
                else
                {
                    $que['result'] = false;
                }
            }
            $que['cnt']++;

            // リクエストキューへ設定
            $p_param->setRequestQueue($msg['rno'], $que);

            // ユーザーが存在すれば
            if($msg['result'] === true)
            {
                // Websocket側の受信コマンドとして登録する
                $p_param->resultRecvUserSearch($msg['rno']);
            }

            // 検索結果が出揃った
            if($que['cnt'] >= $que['max'])
            {
                // ユーザーが存在しなければ
                if($que['result'] === false)
                {
                    // Websocket側の受信コマンドとして登録する
                    $p_param->resultRecvUserSearch($msg['rno']);
                }

                // キューの後始末
                $p_param->delRequestQueue($msg['rno']);
            }
    
            return null;
        };
    }

}
