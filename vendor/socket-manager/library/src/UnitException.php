<?php
/**
 * Exceptionクラスのファイル
 * 
 * UNIT用の例外
 */
namespace SocketManager\Library;

use Exception;


/**
 * Exceptionクラス
 * 
 * UNIT用の例外
 */
class UnitException extends Exception
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    // UNITパラメータ
    private SocketManagerParameter $param;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     * @param string $p_msg メッセージ
     * @param int $p_cod コード
     * @param SocketManagerParameter $p_param UNITパラメータ
     */
    public function __construct(string $p_msg = '', int $p_cod = 0, SocketManagerParameter $p_param)
    {
        parent::__construct($p_msg, $p_cod);
        $this->param = $p_param;
    }

    /**
     * 例外識別子の取得
     * 
     * @return string 例外識別子
     */
    public function getIdentifier(): string
    {
        return 'UNIT:Exception';
    }

    /**
     * 例外メッセージ配列の取得
     * 
     * @return array 例外メッセージ配列
     */
    public function getArrayMessage(): array
    {
        $ret =
        [
            'cod' => $this->getCode(),
            'msg' => $this->getMessage(),
            'knd' => $this->param->getKindString(),
            'que' => $this->param->getQueueName(),
            'sta' => $this->param->getStatusName(),
            'cid' => $this->param->getConnectionId(),
            'trace' => $this->getTraceAsString()
        ];

        return $ret;
    }
}
