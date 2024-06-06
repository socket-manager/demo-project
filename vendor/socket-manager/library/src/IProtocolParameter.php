<?php
/**
 * ライブラリファイル
 * 
 * プロトコルパラメータインタフェースのファイル
 */

 namespace SocketManager\Library;


/**
 * プロトコルパラメータインタフェース
 * 
 * 周期ドリブンマネージャーへ引き渡すパラメータの管理と制御を行う
 */
interface IProtocolParameter
{
    /**
     * データ受信サイズの設定
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param int $p_size 受信サイズ
     */
    public function setReceivingSize(int $p_size);

    /**
     * データ受信
     * 
     * setReceivingSizeで設定されたサイズ分を受信するまで続ける
     * 
     * ※プロトコルUNITで使用 
     * 
     * @return mixed 受信データ or null（受信中）
     */
    public function receiving();

    /**
     * データ受信
     * 
     * 受信バッファサイズ分を受信する
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param mixed &$p_recv 受信エリア
     * @param int $p_size 受信サイズ
     * @return int 受信したサイズ
     */
    public function recv(&$p_recv, int $p_size = null): int;

    /**
     * 送信データの設定
     * 
     * ※プロトコルUNITで使用
     * 
     * @param string $p_data 送信データ
     */
    public function setSendingData(string $p_data);

    /**
     * データ送信
     * 
     * setSendingDataで設定されたデータを送信するまで続ける
     * 
     * ※プロトコルUNITで使用 
     * 
     * @return mixed true（成功） or null（送信中）
     */
    public function sending();

    /**
     * 処理対象の送信データを取得
     * 
     * ※プロトコルUNITで使用
     * 
     * @return mixed 送信データ or null（データなし）
     */
    public function getSendData();
}
