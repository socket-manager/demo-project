<?php
/**
 * ライブラリファイル
 * 
 * ステータスUNIT登録のインタフェースのファイル
 */

namespace SocketManager\Library;


/**
 * ステータスUNIT登録のインタフェース
 * 
 * ステータスUNIT郡をインプリメントしてsetProtocolUnits、あるいはsetCommandUnitsメソッドへ渡すための定義
 * 
 * ※グローバル関数名指定も可
 */
interface IEntryUnits
{
    /**
     * キューリストの取得
     * 
     * @return array キュー名のリスト
     */
    public function getQueueList(): array;

    /**
     * ステータスUNITリストの取得
     * 
     * @param string $p_que キュー名
     * @return array
     * ― キュー名に対応するUNITリスト
     * 
     * ― UNITリストフォーマット⇒[['status' => ステータス名,'unit' => ステータスUNITの関数],...]
     * 
     *----------------------------------------------------------------------------------------------------
     * 【ステータスUNIT関数仕様（※1）】
     * 
     * 引数1：SocketManagerParameter（※2） $p_param UNITパラメータ
     * 
     * 戻り値：string 遷移先のステータス名 or null（処理終了）
     * 
     * （※1）エラー発生時はSocketManagerParameterクラスの下記メソッドを使って処理を中断、あるいは接続を切断できる
     * 
     * ― throwBreakメソッド（現行UNITの処理を中断）
     * 
     * ― emergencyShutdownメソッド（即時切断）
     * 
     * （※2）当該クラス、あるいは当該クラスを継承したクラスも指定可
     * 
     *----------------------------------------------------------------------------------------------------
     */
    public function getUnitList(string $p_que): array;

}
