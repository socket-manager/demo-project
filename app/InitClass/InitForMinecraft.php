<?php
/**
 * SocketManager初期化クラスのファイル
 * 
 * SocketManagerのsetInitSocketManagerメソッドへ引き渡される初期化クラスのファイル
 */

namespace App\InitClass;

use SocketManager\Library\SocketManagerParameter;

use App\CommandUnits\CommandQueueEnumForMinecraft;
use App\UnitParameter\ParameterForMinecraft;


/**
 * SocketManager初期化クラス
 * 
 * IInitSocketManagerインタフェースをインプリメントする
 */
class InitForMinecraft extends InitForWebsocket
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------


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
        parent::__construct($p_param, $p_port);
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
        return function(ParameterForMinecraft $p_param, $p_dat): ?string
        {
            $minecraft = $p_param->isMinecraft();
            if($minecraft === true)
            {
                // マインクラフトからのチャット送信の場合は受け入れる
                if(isset($p_dat['data']['body']['type']))
                {
                    if($p_dat['data']['body']['type'] === 'say')
                    {
                        // 先頭のユーザー名を省く
                        $w_ret = preg_match('/^\[(.*)\] (.*)$/', $p_dat['data']['body']['message'], $matches);
                        if($w_ret > 0)
                        {
                            $p_dat['data']['body']['message'] = $matches[2];
                            $p_param->setRecvData($p_dat);
                        }
                    }

                    if($p_dat['data']['body']['type'] === 'chat' || $p_dat['data']['body']['type'] === 'say')
                    {
                        // プライベートコメントかどうか
                        $msg = $p_dat['data']['body']['message'];
                        $w_ret = mb_strpos($msg, '#');
                        if($w_ret !== false)
                        {
                            $user_name = mb_substr($msg, $w_ret + 1);
                            $comment = mb_substr($msg, 0, $w_ret);
                            $cmd_data =
                            [
                                'data' =>
                                [
                                    'cmd' => CommandQueueEnumForMinecraft::PRIVATE->value,
                                    'user' => $user_name,
                                    'comment' => $comment
                                ]
                            ];
                            $p_param->setRecvData($cmd_data);
                            return CommandQueueEnumForMinecraft::PRIVATE->value;
                        }

                        // 退出要求かどうか
                        if($p_dat['data']['body']['message'] === '$exit')
                        {
                            $cmd_data =
                            [
                                'data' =>
                                [
                                    'cmd' => CommandQueueEnumForMinecraft::EXIT->value,
                                ]
                            ];
                            $p_param->setRecvData($cmd_data);
                            return CommandQueueEnumForMinecraft::EXIT->value;
                        }

                        // コマンドデータを設定
                        $p_dat['data']['cmd'] = CommandQueueEnumForMinecraft::MESSAGE->value;
                        $p_dat['data']['user'] = $p_param->getUserName();
                        $p_dat['data']['comment'] = $p_dat['data']['body']['message'];
                        $p_param->setRecvData($p_dat);

                        return CommandQueueEnumForMinecraft::MESSAGE->value;
                    }
                }

                // サーバーから送信したメッセージの返信はスルー
                if(isset($p_dat['data']['body']['sender']) && $p_dat['data']['body']['sender'] === '外部')
                {
                    return null;
                }

                // コマンド指定の場合は受け入れる
                if(isset($p_dat['data']['cmd']))
                {
                    return $p_dat['data']['cmd'];
                }

                // マインクラフトからのレスポンス
                if(isset($p_dat['data']['body']['statusCode']))
                {
                    return CommandQueueEnumForMinecraft::RESPONSE->value;
                }

                return null;
            }
            else
            {
                return $p_dat['data']['cmd'];
            }
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
        return function(ParameterForMinecraft $p_param)
        {
            $p_param->forcedCloseFromClient($p_param);
        };
    }

}
