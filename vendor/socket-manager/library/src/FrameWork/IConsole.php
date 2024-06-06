<?php
/**
 * ライブラリファイル
 * 
 * コマンド実行用のインタフェースのファイル
 */

namespace SocketManager\Library\FrameWork;


/**
 * コマンド実行用のインタフェース
 * 
 * フレームワーク上のコマンド実行用のインタフェース
 */
interface IConsole
{
    /**
     * 調整済み識別子の取得
     * 
     * @return string 調整済み識別子
     */
    public function getIdentifer(): string;

    /**
     * コマンド説明の取得
     * 
     * @return string コマンド説明
     */
    public function getDescription(): string;

    /**
     * 引数の取得
     * 
     * @param string $p_name 引数名
     * @return string 引数の値 or null（引数なし） or false（取得失敗）
     */
    public function getParameter(string $p_name);

    /**
     * エラーメッセージの取得
     * 
     * @return ?FailureEnum エラーメッセージ or null（エラーなし）
     */
    public function getErrorMessage(): ?FailureEnum;

    /**
     * コマンド実行メソッド
     * 
     */
    public function exec();
}
