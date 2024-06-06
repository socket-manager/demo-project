<?php
/**
 * ライブラリファイル
 * 
 * 周期ドリブンマネージャークラスのファイル
 * 
 * 周期ドリブンマトリクスを構成する最小単位を「ステータスUNIT」、または「UNIT」と呼称する
 * 
 * 連続するUNITを束ねる単位を「キュー」と呼称する
 */

namespace SocketManager\Library;


/**
 * 周期ドリブンマネージャークラス
 * 
 * 周期ドリブンマトリクスの管理と制御を行う
 */
class CycleDrivenManager
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * キューリスト
     * 
     * 各キュー毎のステータスUNITを管理する
     * ステータスUNIT⇒ステータス名をキーとしたコールバック関数とのセットを示す
     */
    private array $queues = [];


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
     * キューの設定を検査
     * 
     * @param string $p_que_nm キュー名
     * @param string $p_sta_nm ステータス名
     * @return bool true（設定あり） or false（設定なし）
     */
    public function isSetQueue(string $p_que_nm, string $p_sta_nm)
    {
        return isset($this->queues[$p_que_nm][$p_sta_nm]);
    }

    /**
     * ステータスUNITの追加
     * 
     * @param string $p_que_nm キュー名
     * @param string $p_sta_nm ステータス名
     * @param mixed $p_fnc 関数
     */
    public function addStatusUnit(string $p_que_nm, string $p_sta_nm, $p_fnc)
    {
        $this->queues[$p_que_nm][$p_sta_nm] = $p_fnc;
    }

    /**
     * キュー名のリスト取得
     * 
     * @return array キュー名のリスト
     */
    public function getQueueNameList()
    {
        $w_ret = array_keys($this->queues);
        return $w_ret;
    }

    /**
     * ステータス名のリスト取得
     * 
     * @param string $p_que_nm キュー名
     * @return array ステータス名のリスト
     */
    public function getStatusNameList(string $p_que_nm)
    {
        $w_ret = array_keys($this->queues[$p_que_nm]);
        return $w_ret;
    }

    /**
     * 周期ドリブン処理の実行
     * 
     * @param IUnitParameter $p_param UNITパラメータ
     * @return bool true（成功） or false（失敗：登録UNITがない）
     */
    public function cycleDriven(IUnitParameter $p_param)
    {
        // キュー名の取得
        $que = $p_param->getQueueName();

        // ステータス名の取得
        $sta = $p_param->getStatusName();

        // 実行エントリがある場合のみ実行する
        if($que !== null && $sta !== null)
        {
            if(!isset($this->queues[$que][$sta]))
            {
                return false;
            }
            $w_ret = $this->queues[$que][$sta]($p_param);
            $p_param->setStatusName($w_ret);
        }

        return true;
    }

}
