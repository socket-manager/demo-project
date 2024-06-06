<?php
/**
 * SocketManager初期化クラスのファイル
 * 
 * SocketManagerのsetInitSocketManagerメソッドへ引き渡される初期化クラスのファイル
 */

namespace App\InitClass;


use SocketManager\Library\IInitSocketManager;
use SocketManager\Library\SocketManagerParameter;


/**
 * SocketManager初期化クラス
 * 
 * IInitSocketManagerインタフェースをインプリメントする
 */
class InitForTemplate implements IInitSocketManager
{
    /**
     * コンストラクタ
     * 
     */
    public function __construct()
    {
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
        return null;
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
        return null;
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
        return null;
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
        return null;
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
        return null;
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
        return null;
    }
}
