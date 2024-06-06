<?php
/**
 * UNITパラメータクラスのファイル
 * 
 * TCPマルチサーバー用
 */

namespace App\UnitParameter;

use SocketManager\Library\SocketManagerParameter;

use App\CommandUnits\CommandQueueEnumForTcpMulti;
use App\UnitParameter\ParameterForWebsocket;


/**
 * UNITパラメータクラス
 * 
 * UNITパラメータクラスのSocketManagerParameterをオーバーライドする
 */
class ParameterForTcpMulti extends SocketManagerParameter implements IChatParameter
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * リクエストNo
     */
    private int $request_no = 0;

    /**
     * リクエストキュー
     * 
     *     'rno' => サーバーリクエスト時のリクエストNo
     *     'cmd' => リクエストしたコマンド
     *     'suser' => 送信元ユーザー名
     *     'duser' => 送信先ユーザー名
     *     'cid' => リクエスト元接続ID
     *     'server' => サーバーリクエストフラグ
     *     'max' => リクエスト件数
     *     'cnt' => リクエスト処理数
     *     'result' => リクエスト結果
     */
    private array $request_queue = [];

    /**
     * Websocket用UNITパラメータインスタンス
     */
    private ?ParameterForWebsocket $websocket = null;


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


    //--------------------------------------------------------------------------
    // サーバー間通信時のリクエストキュー処理
    //--------------------------------------------------------------------------

    /**
     * リクエストキューの取得
     * 
     * @param string $p_rno リクエストNo
     * @return array リクエストキューデータ
     */
    public function getRequestQueue(string $p_rno): array
    {
        $w_ret = $this->request_queue[$p_rno];
        return $w_ret;
    }

    /**
     * リクエストキューの設定
     * 
     * @param string $p_rno リクエストNo
     * @return array リクエストキューデータ
     */
    public function setRequestQueue(string $p_rno, array $p_que)
    {
        $this->request_queue[$p_rno] = $p_que;
        return;
    }

    /**
     * リクエストキューの削除
     * 
     * @param string $p_rno リクエストNo
     */
    public function delRequestQueue(string $p_rno)
    {
        unset($this->request_queue[$p_rno]);
        return;
    }


    //--------------------------------------------------------------------------
    // チャットサーバーからのサーバー間通信リクエスト処理
    //--------------------------------------------------------------------------

    /**
     * コマンドブロードキャスト依頼（プライベートコメント用）
     * 
     * @param string $p_cid 依頼元の接続ID
     * @param string $p_suser 送信元ユーザー名
     * @param string $p_duser 送信先ユーザー名
     * @param string $p_comment 送信するプライベートコメント
     * @param bool $p_server サーバーリクエスト時のリクエストNo
     */
    public function requestPrivateComment(string $p_cid, string $p_suser, string $p_duser, string $p_comment, string $p_rno = null)
    {
        // サーバーリクエストフラグの設定
        $server = false;
        if($p_rno !== null)
        {
            $server = true;
        }

        // クライアント数を取得
        $client_count = $this->getClientCount();

        // リクエスト先のサーバーがない場合
        if($client_count <= 0 || ($client_count <= 1 && $server))
        {
            $rno = null;
            if($server)
            {
                $rno = $p_rno;
            }

            // 返信データを設定
            $data =
            [
                'data' =>
                [
                    'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value,
                    'user' => $p_duser,
                    'rno' => $rno,
                    'result' => false
                ]
            ];

            // サーバーからのリクエストの場合
            if($server)
            {
                // サーバーへ返信
                $this->setSendStack($data['data']);
            }
            // 接続ユーザーからのリクエストの場合
            else
            {
                // 受信スタックへ格納
                $this->websocket()->setRecvStack($data, true, $p_cid);
            }
            return;
        }

        // サーバーからのリクエストの場合、リクエスト元のサーバー１件分はクライアント数に数えない
        $max = $client_count;
        if($server === true)
        {
            $max--;
        }

        // リクエストデータ設定
        $rno = '#'.$this->request_no;
        $req =
        [
            'rno' => $p_rno,
            'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_SEARCH->value,
            'suser' => $p_suser,
            'duser' => $p_duser,
            'cid' => $p_cid,
            'server' => $server,
            'max' => $max,
            'cnt' => 0,
            'result' => null
        ];
        $this->request_queue[$rno] = $req;

        // 送信データ作成
        $data =
        [
            'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_SEARCH->value,
            'suser' => $p_suser,
            'duser' => $p_duser,
            'comment' => $p_comment,
            'rno' => $rno
        ];

        // 待ち受け用ディスクリプタを除く全接続へ送信予約
        $this->setSendStackAll($data, $server);

        // リクエストNoをインクリメント
        $this->request_no++;
    }

    /**
     * サーバー間通信でのプライベートコメント送信結果を返す
     * 
     * @param string $p_rno リクエストキューのリクエストNo
     * @return bool true（成功） or false（失敗）
     */
    public function resultRecvPrivateComment(string $p_rno): bool
    {
        // リクエストキューを取得
        $que = $this->getRequestQueue($p_rno);

        // サーバーリクエストフラグ
        $server = $que['server'];

        $rno = $p_rno;
        if($server === true)
        {
            $rno = $que['rno'];
        }

        // 受信バッファのデータを設定
        $data =
        [
            'data' =>
            [
                'cmd' => CommandQueueEnumForTcpMulti::PRIVATE_RESULT->value,
                'user' => $que['duser'],
                'rno' => $rno,
                'result' => $que['result']
            ]
        ];

        // 受信スタックへ格納
        if($server === true)
        {
            $this->setSendStack($data['data'], $que['cid']);
        }
        else{
            $this->websocket()->setRecvStack($data, true, $que['cid']);
        }

        return true;
    }

    /**
     * コマンドブロードキャスト依頼（ユーザー検索用）
     * 
     * @param string $p_cid 依頼元の接続ID
     * @param string $p_suser ユーザー名
     * @param bool $p_server サーバーリクエスト時のリクエストNo
     */
    public function requestUserSearch(string $p_cid, string $p_user, string $p_rno = null)
    {
        // サーバーリクエストフラグの設定
        $server = false;
        if($p_rno !== null)
        {
            $server = true;
        }

        // クライアント数を取得
        $client_count = $this->getClientCount();

        // リクエスト先のサーバーがない場合
        if($client_count <= 0 || ($client_count <= 1 && $server))
        {
            $rno = null;
            if($server)
            {
                $rno = $p_rno;
            }

            // 返信データを設定
            $data =
            [
                'data' =>
                [
                    'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value,
                    'user' => $p_user,
                    'rno' => $rno,
                    'result' => false
                ]
            ];

            // サーバーからのリクエストの場合
            if($server)
            {
                // サーバーへ返信
                $this->setSendStack($data['data']);
            }
            // 接続ユーザーからのリクエストの場合
            else
            {
                // ユーザーへ返信
                $this->websocket()->setRecvStack($data);
            }
            return;
        }

        // サーバーからのリクエストの場合、リクエスト元のサーバー１件分はクライアント数に数えない
        $max = $client_count;
        if($server === true)
        {
            $max--;
        }

        // リクエストデータ設定
        $rno = '#'.$this->request_no;
        $req =
        [
            'rno' => $p_rno,
            'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH->value,
            'user' => $p_user,
            'cid' => $p_cid,
            'server' => $server,
            'max' => $max,
            'cnt' => 0,
            'result' => null
        ];
        $this->request_queue[$rno] = $req;

        // 送信データ作成
        $data =
        [
            'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH->value,
            'user' => $p_user,
            'rno' => $rno
        ];

        // 待ち受け用ディスクリプタを除く全接続へ送信予約
        $this->setSendStackAll($data, $server);

        // リクエストNoをインクリメント
        $this->request_no++;
    }

    /**
     * サーバー間通信でのユーザー検索結果を返す
     * 
     * @param string $p_rno リクエストキューのリクエストNo
     * @return bool true（成功） or false（失敗）
     */
    public function resultRecvUserSearch(string $p_rno): bool
    {
        // リクエストキューを取得
        $que = $this->getRequestQueue($p_rno);

        // サーバーリクエストフラグ
        $server = $que['server'];

        $rno = $p_rno;
        if($server === true)
        {
            $rno = $que['rno'];
        }

        // 受信バッファのデータを設定
        $data =
        [
            'data' =>
            [
                'cmd' => CommandQueueEnumForTcpMulti::USERSEARCH_RESULT->value,
                'user' => $que['user'],
                'rno' => $rno,
                'result' => $que['result']
            ]
        ];

        // 受信スタックへ格納
        if($server === true)
        {
            $this->setSendStack($data['data'], $que['cid']);
        }
        else{
            $this->websocket()->setRecvStack($data, true, $que['cid']);
        }

        return true;
    }

    //--------------------------------------------------------------------------
    // チャットサーバーパラメータクラスの連携用
    //--------------------------------------------------------------------------

    /**
     * Websocketサーバー用のUNITパラメータクラスを設定
     * 
     * @param ChatParameter Websocketサーバー用のUNITパラメータクラスインスタンス
     */
    public function setChatParameterForWebsocket(ParameterForWebsocket $p_param)
    {
        $this->websocket = $p_param;
        return;
    }

    /**
     * Websocket用のUNITパラメータの取得
     * 
     * @return ParameterForWebsocket Websocket用のUNITパラメータ
     */
    public function websocket(): ParameterForWebsocket
    {
        $w_ret = $this->websocket;
        return $w_ret;
    }

}
