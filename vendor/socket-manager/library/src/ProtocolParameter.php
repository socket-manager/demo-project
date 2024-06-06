<?php
/**
 * プロトコルUNITパラメータクラスのファイル
 * 
 * プロトコルUNITでのみ使えるパラメータクラス
 */
namespace SocketManager\Library;


/**
 * プロトコルUNITパラメータクラス
 * 
 * プロトコルUNITでのみ使えるパラメータクラス
 */
class ProtocolParameter implements IProtocolParameter
{
    //--------------------------------------------------------------------------
    // 定数
    //--------------------------------------------------------------------------

    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    // ソケットマネージャー
    private SocketManager $manager;

    // UNITパラメータ
    private SocketManagerParameter $param;


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
     * SocketManagerParameterインスタンスの設定
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     */
    final public function setSocketManagerParameter(SocketManagerParameter $p_param)
    {
        $this->param = $p_param;
        $this->manager = $p_param->getSocketManager();
    }

    /**
     * データ受信サイズの設定
     * 
     * @param int $p_size 受信サイズ
     */
    final public function setReceivingSize(int $p_size)
    {
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->setReceivingSize($cid, $p_size);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_RECEIVING_SIZE_SET_FAIL->message(),
                UnitExceptionEnum::ECODE_RECEIVING_SIZE_SET_FAIL->value,
                $this->param
            );
        }
    }

    /**
     * データ受信
     * 
     * setReceivingSizeで設定されたサイズ分を受信するまで続ける
     * 
     * @return mixed 受信データ or null（受信中）
     */
    final public function receiving()
    {
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->receiving($cid);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_RECEIVING_FAIL->message(),
                UnitExceptionEnum::ECODE_RECEIVING_FAIL->value,
                $this->param
            );
        }

        return $w_ret;
    }

    /**
     * データ受信
     * 
     * 受信バッファサイズ分を受信する
     * 
     * @param mixed &$p_recv 受信エリア
     * @param int $p_size 受信サイズ
     * @return int 受信したサイズ
     */
    final public function recv(&$p_recv, int $p_size = null): int
    {
        // データ受信
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->recv($cid, $p_recv, $p_size);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_RECEIVING_FAIL->message(),
                UnitExceptionEnum::ECODE_RECEIVING_FAIL->value,
                $this->param
            );
        }
        else
        if($w_ret === null)
        {
            return 0;
        }
        
        return $w_ret;
    }

    /**
     * 送信データの設定
     * 
     * @param string $p_data 送信データ
     */
    final public function setSendingData(string $p_data)
    {
        // 送信データの設定
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->setSendingData($cid, $p_data);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_SENDING_DATA_SET_FAIL->message(),
                UnitExceptionEnum::ECODE_SENDING_DATA_SET_FAIL->value,
                $this->param
            );
        }
    }

    /**
     * データ送信
     * 
     * setSendingDataで設定されたデータを送信するまで続ける
     * 
     * @return mixed true（成功） or null（送信中）
     */
    final public function sending()
    {
        // データ送信
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->sending($cid);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_SENDING_FAIL->message(),
                UnitExceptionEnum::ECODE_SENDING_FAIL->value,
                $this->param
            );
        }

        return $w_ret;
    }

    /**
     * 処理対象の送信データを取得
     * 
     * @return mixed 送信データ or null（データなし）
     */
    final public function getSendData()
    {
        // ユーザープロパティの取得
        $cid = $this->param->getConnectionId();
        $w_ret = $this->manager->getProperties($cid, ['send_buffer']);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_PICKUP_SEND_DATA_GET_FAIL->message(),
                UnitExceptionEnum::ECODE_PICKUP_SEND_DATA_GET_FAIL->value,
                $this->param
            );
        }

        return $w_ret['send_buffer'];
    }

}
