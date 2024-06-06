<?php
/**
 * ライブラリファイル
 * 
 * 周期ドリブンマパラメータインタフェースのファイル
 */

 namespace SocketManager\Library;


/**
 * UNITパラメータインタフェース
 * 
 * 周期ドリブンマネージャーへ引き渡すパラメータの管理と制御を行う
 */
interface IUnitParameter
{
    /**
     * キュー名の取得
     * 
     * @return ?string キュー名 or null（なし）
     */
    public function getQueueName(): ?string;

    /**
     * ステータス名の取得
     * 
     * @return ?string ステータス名 or null（なし）
     */
    public function getStatusName(): ?string;

    /**
     * ステータス名の設定
     * 
     * @param ?string ステータス名 or null（なし）
     */
    public function setStatusName(?string $p_name);

}
