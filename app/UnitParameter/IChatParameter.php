<?php
/**
 * マルチサーバー用UNITパラメータインタフェースファイル
 * 
 * TCP/UDP通信で共用
 */

namespace App\UnitParameter;


use App\UnitParameter\ParameterForWebsocket;


/**
 * マルチサーバー用UNITパラメータインタフェース
 * 
 * ParameterForTcpMulti、ParameterForUdpMultiクラスでインプリメントする
 */
interface IChatParameter
{
    /**
     * Websocket用のUNITパラメータの取得
     * 
     * @return ParameterForWebsocket Websocket用のUNITパラメータ
     */
    public function websocket(): ParameterForWebsocket;

    /**
     * リクエストキューの取得
     * 
     * @param string $p_rno リクエストNo
     * @return array リクエストキューデータ
     */
    public function getRequestQueue(string $p_rno): array;

    /**
     * リクエストキューの設定
     * 
     * @param string $p_rno リクエストNo
     * @param array $p_que 設定するキューデータ
     */
    public function setRequestQueue(string $p_rno, array $p_que);

    /**
     * リクエストキューの削除
     * 
     * @param string $p_rno リクエストNo
     */
    public function delRequestQueue(string $p_rno);

    /**
     * コマンドブロードキャスト依頼
     * 
     * @param string $p_cid 依頼元の接続ID
     * @param string $p_suser 送信元ユーザー名
     * @param string $p_duser 送信先ユーザー名
     * @param string $p_comment 送信するプライベートコメント
     * @param string $p_rno サーバーリクエスト時のリクエストNo
     */
    public function requestPrivateComment(string $p_cid, string $p_suser, string $p_duser, string $p_comment, string $p_rno = null);

    /**
     * サーバー間通信でのプライベートコメント送信結果を返す
     * 
     * @param string $p_rno リクエストキューのリクエストNo
     * @return bool true（成功） or false（失敗）
     */
    public function resultRecvPrivateComment(string $p_rno): bool;

    /**
     * コマンドブロードキャスト依頼（ユーザー検索用）
     * 
     * @param string $p_cid 依頼元の接続ID
     * @param string $p_suser ユーザー名
     * @param bool $p_server サーバーリクエスト時のリクエストNo
     */
    public function requestUserSearch(string $p_cid, string $p_user, string $p_rno = null);

    /**
     * サーバー間通信でのユーザー検索結果を返す
     * 
     * @param string $p_rno リクエストキューのリクエストNo
     * @return bool true（成功） or false（失敗）
     */
    public function resultRecvUserSearch(string $p_rno): bool;
}
