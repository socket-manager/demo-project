<?php
/**
 * ライブラリファイル
 * 
 * 処理対象の接続IDをラッピングしたUNITパラメータ用ライブラリのファイル
 */

namespace SocketManager\Library;


/**
 * UNITパラメータの基底クラス
 * 
 * 周期ドリブンマネージャーへ引き渡すパラメータの管理と制御を行う
 */
class SocketManagerParameter implements IUnitParameter
{
    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * ソケットマネージャー
     */
    private ?SocketManager $manager = null;

    /**
     * 周期ドリブンマネージャーの種別
     * 'protocol_names' or 'command_names'
     */
    private ?string $kind = null;

    /**
     * 接続ID
     */
    private ?string $cid = null;

    /**
     * 言語設定
     * 
     * デフォルト：'ja'
     */
    private string $lang = 'ja';

    /**
     * プロトコルUNITパラメータ
     * 
     */
    private ?ProtocolParameter $protocol = null;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     * @param string $p_lang 言語コード
     */
    public function __construct(string $p_lang = null)
    {
        // 言語設定
        if($p_lang !== null)
        {
            $this->lang = $p_lang;
        }

        // プロトコルUNITパラメータクラスのインスタンスを設定
        $this->protocol = new ProtocolParameter();
    }


    //--------------------------------------------------------------------------
    // インタフェース（IUnitParameter）実装
    //--------------------------------------------------------------------------

    /**
     * キュー名の取得
     * 
     * @return ?string キュー名 or null（なし）
     */
    final public function getQueueName(): ?string
    {
        $w_ret = $this->manager->getQueueName($this->kind, $this->cid);
        return $w_ret;
    }

    /**
     * ステータス名の取得
     * 
     * @return ?string ステータス名 or null（なし）
     */
    final public function getStatusName(): ?string
    {
        $w_ret = $this->manager->getStatusName($this->kind, $this->cid);
        return $w_ret;
    }

    /**
     * ステータス名の設定
     * 
     * @param ?string ステータス名 or null（なし）
     */
    final public function setStatusName(?string $p_name)
    {
        // ディスクリプタが存在する時のみ実行
        $w_ret = $this->manager->isExistDescriptor($this->cid);
        if($w_ret === true)
        {
            $this->manager->setStatusName($this->kind, $this->cid, $p_name);
        }
        return;
    }


    //--------------------------------------------------------------------------
    // 送受信バッファ制御
    //--------------------------------------------------------------------------

    /**
     * 処理対象の受信データを取得
     * 
     * @return mixed 受信データ or null（データなし）
     */
    final public function getRecvData()
    {
        // ユーザープロパティの取得
        $w_ret = $this->manager->getProperties($this->cid, ['receive_buffer']);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_PICKUP_RECEIVE_DATA_GET_FAIL->message(),
                UnitExceptionEnum::ECODE_PICKUP_RECEIVE_DATA_GET_FAIL->value,
                $this
            );
        }

        return $w_ret['receive_buffer'];
    }

    /**
     * 処理対象の受信データを設定
     * 
     * @param mixed 受信データ or null（データなし）
     */
    final public function setRecvData($p_dat)
    {
        // ユーザープロパティの設定
        $w_ret = $this->manager->setProperties($this->cid, ['receive_buffer' => $p_dat]);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_PICKUP_RECEIVE_DATA_SET_FAIL->message(),
                UnitExceptionEnum::ECODE_PICKUP_RECEIVE_DATA_SET_FAIL->value,
                $this
            );
        }

        return;
    }

    /**
     * 受信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param mixed $p_data 設定するデータ
     * @param ?bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― null（指定なし）の場合は自動判別（プロトコルUNITでの実行：false、コマンドUNITでの実行：true）
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @param string $p_cid 設定したい接続ID
     */
    final public function setRecvStack($p_data = null, ?bool $p_convert = null, string $p_cid = null)
    {
        // 対象の接続IDの取得
        $cid = $this->cid;
        if($p_cid !== null)
        {
            $cid = $p_cid;
        }

        // フラグの設定
        $convert = false;
        if($p_convert !== null)
        {
            $convert = $p_convert;
        }
        else
        {
            if($this->kind === 'protocol_names')
            {
                $convert = false;
            }
            else
            {
                $convert = true;
            }
        }

        // 受信データ設定
        $w_ret = $this->manager->setRecvStack($cid, $p_data, $convert);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_RECEIVE_DATA_STACK_FAIL->message(),
                UnitExceptionEnum::ECODE_RECEIVE_DATA_STACK_FAIL->value,
                $this
            );
        }
    }

    /**
     * 送信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param mixed $p_data 設定するデータ
     * @param string $p_cid 設定したい接続ID
     */
    final public function setSendStack($p_data = null, string $p_cid = null)
    {
        $cid = $this->cid;
        if($p_cid !== null)
        {
            $cid = $p_cid;
        }

        // 送信データ設定
        $w_ret = $this->manager->setSendStack($cid, $p_data);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_SEND_DATA_STACK_FAIL->message(),
                UnitExceptionEnum::ECODE_SEND_DATA_STACK_FAIL->value,
                $this
            );
        }
    }

    /**
     * 全接続の送信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param mixed $p_data 送信データ
     * @param bool $p_self_remove 自身のディスクリプタの除外フラグ
     * @param mixed $p_fnc 処理対象の接続ID評価コールバック
     * @param mixed $p_param コールバックのパラメータ
     */
    final public function setSendStackAll($p_data, bool $p_self_remove = false, $p_fnc = null, $p_param = null)
    {
        if($p_fnc === null)
        {
            $w_ret = $this->manager->setSendStackAll($this->cid, $p_data, $p_self_remove);
            if($w_ret === false)
            {
                throw new UnitException(
                    UnitExceptionEnum::ECODE_SEND_DATA_STACK_FAIL->message(),
                    UnitExceptionEnum::ECODE_SEND_DATA_STACK_FAIL->value,
                    $this
                );
            }
        }
        else
        {
            $param = null;
            if($p_param !== null)
            {
                $param = clone $p_param;
            }
            $cid = null;
            if($p_self_remove === true)
            {
                $cid = $this->cid;
            }
            $cids = $this->manager->getConnectionIdAll($cid);
            foreach($cids as $cid)
            {
                if($param !== null)
                {
                    $param->setConnectionId($cid);
                    $param->setKindString($this->getKindString());
                }
                $w_ret = $p_fnc($param);
                if($w_ret === false)
                {
                    continue;
                }
                $this->setSendStack($p_data, $cid);
            }
        }
    }


    //--------------------------------------------------------------------------
    // 切断や緊急停止系
    //--------------------------------------------------------------------------

    /**
     * 切断シーケンス開始（プロトコルユニットの'CLOSE'キューを実行）
     * 
     * プロトコルUNIT実行中に呼ばれた場合は例外を投げて現在の処理を中断する
     * 
     * ※基本的に切断情報バッファ内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param mixed $p_param 切断時パラメータ
     * @param ?bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― null（指定なし）の場合は自動判別（プロトコルUNITでの実行：false、コマンドUNITでの実行：true）
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     */
    final public function close($p_param, ?bool $p_convert = null)
    {
        // フラグの設定
        $convert = false;
        if($p_convert !== null)
        {
            $convert = $p_convert;
        }
        else
        {
            if($this->kind === 'protocol_names')
            {
                $convert = false;
            }
            else
            {
                $convert = true;
            }
        }

        // 切断シーケンス開始
        $w_ret = $this->manager->close($this->cid, $p_param, $convert);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_CLOSE_FAIL->message(),
                UnitExceptionEnum::ECODE_CLOSE_FAIL->value,
                $this
            );
        }
    }

    /**
     * 切断パラメータ（切断シーケンスによって登録されたパラメータ）の取得
     * 
     * ※基本的に切断情報バッファ内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param ?bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― null（指定なし）の場合は自動判別（プロトコルUNITでの実行：false、コマンドUNITでの実行：true）
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @return mixed 切断パラメータ or null（空）
     */
    final public function getCloseParameter(?bool $p_convert = null)
    {
        // プロパティの取得
        $w_ret = $this->manager->getProperties($this->cid, ['close_buffer']);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_PROPERTY_GET_FAIL->message(),
                UnitExceptionEnum::ECODE_PROPERTY_GET_FAIL->value,
                $this
            );
        }
        if($w_ret === null)
        {
            return null;
        }

        // フラグの設定
        $convert = false;
        if($p_convert !== null)
        {
            $convert = $p_convert;
        }
        else
        {
            if($this->kind === 'protocol_names')
            {
                $convert = false;
            }
            else
            {
                $convert = true;
            }
        }

        // アンシリアライズ化
        if($convert === true)
        {
            // アンシリアライザーの取得
            $unserializer = $this->manager->getUnserializer();
            if($unserializer !== null)
            {
                $w_ret['close_buffer'] = $unserializer($w_ret['close_buffer']);
            }
        }

        return $w_ret['close_buffer'];
    }

    /**
     * プロトコルUNIT処理を中断する
     * 
     * 実行されると例外キャッチ時に切断処理は無視されて処理を継続する
     */
    final public function throwBreak()
    {
        $this->manager->throwBreak();
    }

    /**
     * 緊急停止（即時切断）
     */
    final public function emergencyShutdown()
    {
        // 例外発行
        throw new UnitException(
            UnitExceptionEnum::ECODE_EMERGENCY_SHUTDOWN->message($this->lang),
            UnitExceptionEnum::ECODE_EMERGENCY_SHUTDOWN->value,
            $this
        );
    }


    //--------------------------------------------------------------------------
    // その他
    //--------------------------------------------------------------------------

    /**
     * ログライター
     * 
     * SocketManagerで使用しているログライターと同じ
     * 
     * @param string $p_level ログレベル
     * @param array $p_param ログパラメータ
     */
    final public function logWriter(string $p_level, array $p_param)
    {
        $this->manager->logWriter($p_level, $p_param);
    }

    /**
     * キューの実行状況を検査
     * 
     * @param string $p_que キュー名
     * @return bool true（実行中） or false（停止中）
     */
    final public function isExecutedQueue(string $p_que): bool
    {
        $protocol = $this->manager->isExecutedQueue($this->cid, 'protocol_names', $p_que);
        if($protocol === false)
        {
            $command = $this->manager->isExecutedQueue($this->cid, 'command_names', $p_que);
            return $command;
        }
        return true;
    }

    /**
     * アライブチェックを行う
     * 
     * 任意のタイミングで一時的に実行したい時に利用する
     * 
     * ※既に設定済みの場合は何もせずに終了する
     * 
     * ※実行後スローブレイクすることに注意
     * 
     * @param int $p_tout — アライブチェックタイムアウト（秒）
     */
    final public function aliveCheck(int $p_tout)
    {
        $w_ret = $this->manager->aliveCheck($this->kind, $this->cid, $p_tout);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_ALIVE_CHECK_FAIL->message(),
                UnitExceptionEnum::ECODE_ALIVE_CHECK_FAIL->value,
                $this
            );
        }

        throw new UnitException(
            UnitExceptionEnum::ECODE_THROW_BREAK->message(),
            UnitExceptionEnum::ECODE_THROW_BREAK->value,
            $this
        );
    }

    /**
     * 待ち受けポートの接続IDの取得
     * 
     * @return ?string 待ち受けポートの接続ID
     */
    final public function getAwaitConnectionId(): ?string
    {
        // 待ち受けポートの接続IDの取得
        $w_ret = $this->manager->getAwaitConnectionId();
        return $w_ret;
    }

    /**
     * 現在のユーザー数を取得
     * 
     * @return int ユーザー数
     */
    final public function getClientCount(): int
    {
        $w_ret = $this->manager->getClientCount();
        return $w_ret;
    }

    /**
     * テンポラリバッファの取得
     * 
     * @param array $p_prop プロパティ（キー）のリスト
     * @param string $p_cid 接続ID
     * @return mixed バッファデータ or null（空）
     */
    final public function getTempBuff(array $p_prop, string $p_cid = null)
    {
        $cid = $this->cid;
        if($p_cid !== null)
        {
            $cid = $p_cid;
        }

        // ユーザープロパティの取得
        $w_ret = $this->manager->getUserProperties($cid, $p_prop);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_USER_PROPERTY_GET_FAIL->message(),
                UnitExceptionEnum::ECODE_USER_PROPERTY_GET_FAIL->value,
                $this
            );
        }

        return $w_ret;
    }

    /**
     * テンポラリバッファの設定
     * 
     * @param array $p_prop プロパティのリスト
     */
    final public function setTempBuff(array $p_prop)
    {
        // ユーザープロパティの設定
        $w_ret = $this->manager->setUserProperties($this->cid, $p_prop);
        if($w_ret === false)
        {
            throw new UnitException(
                UnitExceptionEnum::ECODE_USER_PROPERTY_SET_FAIL->message(),
                UnitExceptionEnum::ECODE_USER_PROPERTY_SET_FAIL->value,
                $this
            );
        }

        return;
    }

    /**
     * 待ち受けホスト名を取得
     * 
     * @return ?string 待ち受けホスト名
     */
    final public function getAwaitHost(): ?string
    {
        $w_ret = $this->manager->getAwaitHost();
        return $w_ret;
    }

    /**
     * 待ち受けポート番号を取得
     * 
     * @return ?int 待ち受けポート番号
     */
    final public function getAwaitPort(): ?int
    {
        $w_ret = $this->manager->getAwaitPort();
        return $w_ret;
    }

    /**
     * データ受信中の検査
     * 
     * @return bool true（受信中） or false（受信中ではない）
     */
    final public function isReceiving(): bool
    {
        $w_ret = $this->manager->isReceiving($this->cid);

        return $w_ret;
    }

    /**
     * データ送信中の検査
     * 
     * @return bool true（送信中） or false（送信中ではない）
     */
    final public function isSending(): bool
    {
        $w_ret = $this->manager->isSending($this->cid);

        return $w_ret;
    }

    /**
     * 言語コードの取得
     * 
     * @return string 言語コード
     */
    final public function getLanguage(): string
    {
        $w_ret = $this->lang;
        return $w_ret;
    }

    /**
     * 言語コードの設定
     * 
     * @param string 言語コード
     */
    final public function setLanguage(string $p_lang)
    {
        $this->lang = $p_lang;
        return;
    }

    /**
     * ソケットマネージャーの取得
     * 
     * @return ?SocketManager ソケットマネージャーのインスタンス
     */
    final public function getSocketManager(): ?SocketManager
    {
        $w_ret = $this->manager;
        return $w_ret;
    }

    /**
     * ソケットマネージャーの設定
     * 
     * @param SocketManager $p_mng ソケットマネージャーのインスタンス
     */
    final public function setSocketManager(SocketManager $p_mng)
    {
        $this->manager = $p_mng;
        $this->protocol->setSocketManagerParameter($this);
        return;
    }

    /**
     * 周期ドリブンマネージャーの種別取得
     * 
     * @return string 種別文字列（'protocol_names' or 'command_names'）
     */
    final public function getKindString(): string
    {
        $w_ret = $this->kind;
        return $w_ret;
    }

    /**
     * 周期ドリブンマネージャーの種別設定
     * 
     * @param string $p_kind 種別文字列（'protocol_names' or 'command_names'）
     */
    final public function setKindString(string $p_kind)
    {
        $this->kind = $p_kind;
        return;
    }

    /**
     * 接続IDの取得
     * 
     * @return string 接続ID
     */
    final public function getConnectionId(): string
    {
        $w_ret = $this->cid;
        return $w_ret;
    }

    /**
     * 接続IDの設定
     * 
     * @param string $p_cid 接続ID
     */
    final public function setConnectionId(string $p_cid)
    {
        $this->cid = $p_cid;
        return;
    }

    /**
     * IProtocolParameterインタフェースの取得
     * 
     * @return IProtocolParameter
     */
    final public function protocol(): IProtocolParameter
    {
        if($this->kind === 'protocol_names' && $this->protocol !== null)
        {
            return $this->protocol;
        }
        throw new UnitException(
            UnitExceptionEnum::ECODE_METHOD_CALL_FAIL->message(),
            UnitExceptionEnum::ECODE_METHOD_CALL_FAIL->value,
            $this
        );
    }

    /**
     * getterメソッドの呼び出し
     * 
     * @return mixed 
     */
    final public function __get($p_name)
    {
        $kind = null;
        if($p_name === 'protocol')
        {
            $kind = 'protocol_names';
        }
        if($kind === $this->kind)
        {
            return $this->$p_name();
        }
        throw new UnitException(
            UnitExceptionEnum::ECODE_METHOD_CALL_FAIL->message(),
            UnitExceptionEnum::ECODE_METHOD_CALL_FAIL->value,
            $this
        );
    }

}
