<?php
/**
 * SocketManager初期化クラスのファイル
 * 
 * SocketManagerのsetInitSocketManagerメソッドへ引き渡される初期化クラスのファイル
 */

namespace App\InitClass;

use SocketManager\Library\IInitSocketManager;
use SocketManager\Library\SocketManagerParameter;

use App\UnitParameter\ParameterForWebsocket;


/**
 * SocketManager初期化クラス
 * 
 * IInitSocketManagerインタフェースをインプリメントする
 */
class InitForWebsocket implements IInitSocketManager
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * UNITパラメータインスタンス
     */
    protected ?SocketManagerParameter $param = null;

    /**
     * ポート番号
     */
    protected ?int $port = null;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @param int $p_port ポート番号
     */
    public function __construct(SocketManagerParameter $p_param, int $p_port)
    {
        $this->param = $p_param;
        $this->port = $p_port;
    }

    /**
     * ログライターの取得
     * 
     * nullを返す場合は無効化（但し、ライブラリ内部で出力されているエラーメッセージも出力されない）
     * 
     * @return mixed "function(string $p_level, array $p_param): void" or null（ログ出力なし）
     */
    public function getLogWriter()
    {
        return function(string $p_level, array $p_param)
        {
            $filename = date('Ymd');
            $now = date('Y-m-d H:i:s');
            $log = $now." {$p_level} ".print_r($p_param, true)."\n";
            error_log($log, 3, "./logs/socket-manager-log/{$filename}_W{$this->port}.log");
        };
    }

    /**
     * シリアライザーの取得
     * 
     * nullを返す場合は無効化となる。
     * エラー発生時はUnitExceptionクラスで例外をスローして切断する。
     * 
     * @return mixed "function(mixed $p_data): mixed" or null（変更なし）
     */
    public function getSerializer()
    {
        return function($p_data)
        {
            $p_data['data'] = json_encode($p_data['data']);
            return $p_data;
        };
    }

    /**
     * アンシリアライザーの取得
     * 
     * nullを返す場合は無効化となる。
     * エラー発生時はUnitExceptionクラスで例外をスローして切断する。
     * 
     * @return mixed "function(mixed $p_data): mixed" or null（変更なし）
     */
    public function getUnserializer()
    {
        return function($p_data)
        {
            $p_data['data'] = json_decode($p_data['data'], true);
            return $p_data;
        };
    }

    /**
     * コマンドディスパッチャーの取得
     * 
     * 受信データからコマンドを解析して返す
     * 
     * コマンドUNIT実行中に受信データが溜まっていた場合でもコマンドUNITの処理が完了するまで
     * 待ってから起動されるため処理競合の調停役を兼ねる
     * 
     * nullを返す場合は無効化となる。エラー発生時はUnitExceptionクラスで例外をスローして切断する。
     * 
     * @return mixed "function(SocketManagerParameter $p_param, mixed $p_dat): ?string" or null（変更なし）
     */
    public function getCommandDispatcher()
    {
        return function(ParameterForWebsocket $p_param, $p_dat): ?string
        {
            return $p_dat['data']['cmd'];
        };
    }

    /**
     * 緊急停止時のコールバックの取得
     * 
     * 例外等の緊急切断時に実行される。nullを返す場合は無効化となる。
     * 
     * @return mixed "function(SocketManagerParameter $p_param)"
     */
    public function getEmergencyCallback()
    {
        return function(ParameterForWebsocket $p_param)
        {
            $p_param->forcedCloseFromClient($p_param);
        };
    }

    /**
     * UNITパラメータインスタンスの取得
     * 
     * nullの場合はSocketManagerParameterのインスタンスが適用される
     * 
     * @return ?SocketManagerParameter SocketManagerParameterクラスのインスタンス（※1）
     * @see:RETURN （※1）当該クラス、あるいは当該クラスを継承したクラスも指定可
     */
    public function getUnitParameter(): ?SocketManagerParameter
    {
        return $this->param;
    }
}
