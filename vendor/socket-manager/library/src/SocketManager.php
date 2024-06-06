<?php
/**
 * ライブラリファイル
 * 
 * ソケットマネージャークラスのファイル
 */

namespace SocketManager\Library;


use Socket;
use Exception;


/**
 * ソケットマネージャークラス
 * 
 * ソケットリソースの管理と周期ドリブンの制御を行う
 * 
 * 周期ドリブンマネージャーを「プロトコル」部と「コマンド」部に分類して管理する
 */
class SocketManager
{
    //--------------------------------------------------------------------------
    // 定数（ソケット関連エラーコード）
    //--------------------------------------------------------------------------

    /**
     * ソケット操作を完了できなかった
     */
    private const SOCKET_ERROR_COULDNT_COMPLETED = 10035;

    /**
     * 相手先による切断
     */
    private const SOCKET_ERROR_PEER_SHUTDOWN = [10053, 10054, 104];


    //--------------------------------------------------------------------------
    // 定数（その他）
    //--------------------------------------------------------------------------

    /**
     * EXCEPTIONクラス名（UNIT処理用）
     */
    private const E_CLASS_NAME_FOR_UNIT = 'SocketManager\Library\UnitException';

    /**
     * UDP接続識別データ
     */
    private const UDP_CONNECTION_IDENTIFY = '';


    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * 待ち受け用ホスト
     */
    private string $await_host = '127.0.0.1';

    /**
     * 待ち受け用ポート
     */
    private int $await_port = 10000;

    /**
     * 待ち受け用ソケットの接続ID
     */
    private ?string $await_connection_id = null;

    /**
     * 受信サイズ（recvメソッドのデフォルト受信サイズ）
     */
    private int $receive_buffer_size = 1024;

    /**
     * 【ディスクリプタのリスト】
     * 
     * 内訳は以下の通り
     *------------------------------------------------------------------
     * 接続ID（<'#' + 番号>形式）
     *
     * 'connection_id' => 接続ID（string）,	
     *
     *------------------------------------------------------------------
     * 送信バッファスタック
     * 
     * 基本的にはコマンドUNITでスタックされプロトコルUNITでスタックされたデータを送信する
     *
     * 'send_buffers' => 送信データ配列（array）,
     *
     *------------------------------------------------------------------
     * 受信バッファスタック
     * 
     * 基本的にはプロトコルUNITでスタックされコマンドUNITで抽出される
     * 
     * 'recv_buffers' => 受信データ配列（array）,
     *
     *------------------------------------------------------------------
     * 受信バッファ
     * 
     * 基本的にはプロトコルUNITで受信中のデータを扱う
     * 
     * 'receiving_buffer' => [
     * 
     *		'size' => 受信サイズ（int）,
     *
     *		'data' => 受信データ（string）,
     *
     *		'receiving_size' => 受信中のサイズ（int）
     *
     * ]
     *
     *------------------------------------------------------------------
     * 送信バッファ
     * 
     * 基本的にはプロトコルUNITで送信中のデータを扱う
     * 
     * 'sending_buffer' => [
     * 
     *		'data' => 送信データ（string）,
     *
     * ]
     *
     *------------------------------------------------------------------
     * ピックアップ受信バッファ
     * 
     * コマンドディスパッチャー用にピックアップしてからコマンドUNITで使用する
     * 
     * 'receive_buffer' => 受信データ（ペイロード部）
     * 
     *------------------------------------------------------------------
     * ピックアップ送信バッファ
     * 
     * プロトコルUNITの送信処理前にピックアップされるデータ
     * 
     * 'send_buffer' => 送信データ（ペイロード部）
     * 
     *------------------------------------------------------------------
     * プロトコルUNITで利用される名称
     * 
     * 'protocol_names' => [
     * 
     *		'queue_name' => キュー名（string）,
     *
     * 		'status_name' => ステータス名（string）
     * 
     * ],
     *
     *------------------------------------------------------------------
     * コマンドUNITで利用される名称
     * 
     * 'command_names' => [
     * 
     *		'queue_name' => キュー名（string）,
     *
     * 		'status_name' => ステータス名（string）
     * 
     * ],
     *
     *------------------------------------------------------------------
     * 最終アクセス日時
     * 
     * 'last_access_timestamp' => タイムスタンプ（int）,
     * 
     *------------------------------------------------------------------
     * ユーザープロパティ（自由定義）
     * 
     * 'user_property' => プロパティ配列（array）,
     * 
     */
    private array $descriptors = [];

    /**
     * ソケットリソースのリスト
     */
    private array $sockets = [];

    /**
     * 前回のSELECT状態が格納される
     */
    private $changed_descriptors = [];

    /**
     * 周期ドリブンマネージャー（プロトコルUNIT用）
     */
    private CycleDrivenManager $cycle_driven_for_protocol;

    /**
     * 周期ドリブンマネージャー（コマンドUNIT用）
     */
    private CycleDrivenManager $cycle_driven_for_command;

    /**
     * SocketManager用として扱うUNITパラメータ
     */
    private ?SocketManagerParameter $unit_parameter = null;

    /**
     * ログライター
     * 
     */
    private $log_writer = null;

    /**
     * シリアライザー
     * 
     * ペイロード部のシリアライズ処理
     */
    private $serializer = null;

    /**
     * アンシリアライザー
     * 
     * ペイロード部のアンシリアライズ処理
     */
    private $unserializer = null;

    /**
     * コマンドディスパッチャー
     * 
     * 受信バッファスタックにデータがある場合実行される
     */
    private $command_dispatcher = null;

    /**
     * 緊急停止時のコールバック
     * 
     * 例外等の緊急切断時に実行される
     */
    private $emergency_callback = null;

    /**
     * NEXT接続ID
     * 
     */
    private int $next_connection_id = 0;

    /**
     * 言語設定
     * 
     * デフォルト：'ja'
     */
    private string $lang = 'ja';

    /**
     * 制限接続数
     * 
     */
    private int $limit_connection = 10;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     * @param string $p_host 待ち受け用ホスト名
     * @param int $p_port 待ち受け用ポート番号
     * @param int $p_size 受信サイズ（recvメソッドのデフォルト受信サイズ）
     * @param string $p_lang 言語コード
     */
    public function __construct
    (
        string $p_host = null,
        int $p_port = null,
        int $p_size = null,
        string $p_lang = null,
        int $p_limit = null
    )
    {
        // ホスト名の設定
        if($p_host !== null)
        {
            $this->await_host = $p_host;
        }

        // ポート番号の設定
        if($p_port !== null)
        {
            $this->await_port = $p_port;
        }

        // 受信バッファサイズの設定
        if($p_size !== null)
        {
            $this->receive_buffer_size = $p_size;
        }

        // 言語コードの設定
        if($p_lang !== null)
        {
            $this->lang = $p_lang;
        }

        if($this->lang === 'ja')
        {
            date_default_timezone_set('Asia/Tokyo');
        }

        // 制限接続数の設定
        if($p_limit !== null)
        {
            $this->limit_connection = $p_limit;
        }

        // UNITパラメータの設定
        $this->unit_parameter = new SocketManagerParameter();
        $this->unit_parameter->setSocketManager($this);

        // 周期ドリブンマネージャーの設定
        $this->cycle_driven_for_protocol = new CycleDrivenManager();
        $this->cycle_driven_for_command = new CycleDrivenManager();

        //--------------------------------------------------------------------------
        // 出力バッファの初期化
        //--------------------------------------------------------------------------

        ob_implicit_flush(true);
        while(ob_get_level() > 0)
        {
            ob_end_flush();
        }

        return;
    }

    /**
     * IInitSocketManagerによる初期化
     * 
     * @param IInitSocketManager $p_init IInitSocketManagerのインスタンス
     */
    public function setInitSocketManager(IInitSocketManager $p_init)
    {
        // ログライターの登録
        $w_ret = $p_init->getLogWriter();
        if($w_ret !== null)
        {
            $this->log_writer = $w_ret;
        }

        // シリアライザーの登録
        $w_ret = $p_init->getSerializer();
        if($w_ret !== null)
        {
            $this->serializer = $w_ret;
        }

        // アンシリアライザーの登録
        $w_ret = $p_init->getUnserializer();
        if($w_ret !== null)
        {
            $this->unserializer = $w_ret;
        }

        // コマンドディスパッチャーの登録
        $w_ret = $p_init->getCommandDispatcher();
        if($w_ret !== null)
        {
            $this->command_dispatcher = $w_ret;
        }

        // 緊急停止時のコールバックの登録
        $w_ret = $p_init->getEmergencyCallback();
        if($w_ret !== null)
        {
            $this->emergency_callback = $w_ret;
        }

        // UNITパラメータの設定
        $w_ret = $p_init->getUnitParameter();
        if($w_ret !== null)
        {
            $this->unit_parameter = $w_ret;
            $this->unit_parameter->setSocketManager($this);
            $this->unit_parameter->setLanguage($this->lang);
        }
    }

    /**
     * IEntryUnitsによるUNIT登録（プロトコル用）
     * 
     * @param IEntryUnits $p_entry IEntryUnitsのインスタンス
     */
    public function setProtocolUnits(IEntryUnits $p_entry)
    {
        // キューリストの取得
        $ques = $p_entry->getQueueList();

        foreach($ques as $que)
        {
            // UNITリストの取得
            $units = $p_entry->getUnitList($que);
            foreach($units as $unit)
            {
                $this->cycle_driven_for_protocol->addStatusUnit($que, $unit['status'], $unit['unit']);
            }
        }
    }

    /**
     * IEntryUnitsによるUNIT登録（コマンド用）
     * 
     * @param IEntryUnits $p_entry IEntryUnitsのインスタンス
     */
    public function setCommandUnits(IEntryUnits $p_entry)
    {
        // キューリストの取得
        $ques = $p_entry->getQueueList();
        foreach($ques as $que)
        {
            // UNITリストの取得
            $units = $p_entry->getUnitList($que);
            foreach($units as $unit)
            {
                $this->cycle_driven_for_command->addStatusUnit($que, $unit['status'], $unit['unit']);
            }
        }
    }

    /**
     * 周期ドリブン処理の実行
     * 
     * @param int $p_cycle_interval 周期インターバルタイム（マイクロ秒）
     * @param int $p_alive_interval アライブチェックインターバルタイム（秒）
     * @return bool true（成功） or false（失敗）
     */
    public function cycleDriven(int $p_cycle_interval = 2000, int $p_alive_interval = 0): bool
    {
        // 周期インターバル
        usleep($p_cycle_interval);

        // ソケットセレクト
        $w_ret = $this->select();
        if($w_ret === false)
        {
            return false;
        }

        // 待ち受けポートを除く
        $dess = $this->descriptors;
        unset($dess[$this->await_connection_id]);

        // ディスクリプタでループ
        foreach($dess as $cid => $des)
        {
            // SELECTイベントが入ったディスクリプタでループ
            $flg_changed = false;
            foreach($this->changed_descriptors as $chg)
            {
                // 接続IDが一致
                if($cid === $chg['connection_id'])
                {
                    $flg_changed = true;
                    break;
                }
            }

            // アライブチェックフラグ
            $alive_check = 0;

            // プロトコルUNITが処理中ではない
            $flg_exec = $this->isExecutingSequence('protocol_names', $cid);
            if($flg_exec === false)
            {
                // SELECTイベント対象のディスクリプタ
                if($flg_changed === true)
                {
                    // データ受信時のキューを実行する
                    $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::RECV->value, StatusEnum::START->value);
                    if($w_ret === true)
                    {
                        $this->setQueueNameForStart('protocol_names', $cid, ProtocolQueueEnum::RECV->value);
                    }
                }
                else
                {
                    // 一番古い送信データを取得
                    $w_ret = $this->getSendStack($cid);
                    if($w_ret === false)
                    {
                        return false;
                    }
                    $dat = $w_ret;

                    // 送信バッファにデータがあれば送信キューを設定
                    if($dat !== null)
                    {
                        // 送信データを退避
                        $this->setProperties($cid, ['send_buffer' => $dat]);

                        // キューの開始
                        $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::SEND->value, StatusEnum::START->value);
                        if($w_ret === true)
                        {
                            $this->setQueueNameForStart('protocol_names', $cid, ProtocolQueueEnum::SEND->value);
                        }
                    }
                    else
                    {
                        // インターバル指定あり、かつアライブキューが存在する
                        if
                        (
                                $p_alive_interval > 0
                            &&  $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::ALIVE->value, StatusEnum::START->value) === true
                        )
                        {
                            $alive_check = 1;
                        }            
                    }
                }
            }
            else
            {
                // アライブキューの実行中を検査
                $w_ret = $this->isExecutedQueue($cid, 'protocol_names', ProtocolQueueEnum::ALIVE->value);
                if($w_ret === true)
                {
                    $alive_check = 2;
                }
            }

            // 最終アクセスタイムスタンプを取得
            $w_ret = $this->getProperties($cid, ['last_access_timestamp']);
            if($w_ret === false)
            {
                return false;
            }
            $timestamp = $w_ret['last_access_timestamp'];

            // アライブチェック
            if($flg_exec === true)  // プロトコル部実行中のタイムアウトを検査
            {
                if($alive_check === 0 && $p_alive_interval > 0)
                {
                    // 実行中タイムアウト判定
                    $dif = time() - $timestamp;
                    if($dif > $p_alive_interval)
                    {
                        $this->logWriter('error', [__METHOD__ => LogMessageEnum::ALIVE_CHECK_TIMEOUT->message($this->lang), 'cid' => $cid, 'old_time' => $timestamp, 'now_time' => time()]);

                        // UNITパラメータをプロトコル種別として設定
                        $this->unit_parameter->setKindString('protocol_names');

                        // 緊急停止時コールバックを実行
                        $callback = $this->emergency_callback;
                        if($callback !== null)
                        {
                            $callback($this->unit_parameter);
                        }

                        // ソケット緊急切断
                        $this->shutdown($cid);
                        continue;
                    }
                }
            }
            else
            if($alive_check === 2)  // アライブチェック中のタイムアウトを検査
            {
                // タイムアウト値を設定
                $timeout = $p_alive_interval;
                $w_ret = $this->getProperties($cid, ['alive_adjust_timeout']);
                if($w_ret === false)
                {
                    return false;
                }
                else
                {
                    if($w_ret !== null)
                    {
                        $timeout = $w_ret['alive_adjust_timeout'];
                    }
                }

                // 実行中タイムアウト判定
                $dif = time() - $timestamp;
                if($dif > $timeout)
                {
                    $this->logWriter('error', [__METHOD__ => LogMessageEnum::ALIVE_CHECK_TIMEOUT->message($this->lang), 'cid' => $cid, 'old_time' => $timestamp, 'now_time' => time()]);

                    // UNITパラメータをプロトコル種別として設定
                    $this->unit_parameter->setKindString('protocol_names');

                    // 緊急停止時コールバックを実行
                    $callback = $this->emergency_callback;
                    if($callback !== null)
                    {
                        $callback($this->unit_parameter);
                    }

                    // ソケット緊急切断
                    $this->shutdown($cid);
                    continue;
                }
            }
            else
            if($alive_check === 1)  // アライブチェック開始までのタイムアウトを検査
            {
                // 一時的なタイムアウト値をクリア
                $w_ret = $this->setProperties($cid, ['alive_adjust_timeout' => null]);
                if($w_ret === false)
                {
                    return false;
                }

                // タイムアウト判定
                $dif = time() - $timestamp;
                if($dif > $p_alive_interval)
                {
                    $this->logWriter('notice', [__METHOD__ => "[{$cid}]".LogMessageEnum::ALIVE_CHECK_START_TIMEOUT->message($this->lang), 'difference' => $dif, 'interval' => $p_alive_interval]);

                    // 最終アクセスタイムスタンプの設定
                    $w_ret = $this->setProperties($cid, ['last_access_timestamp' => time()]);
                    if($w_ret === false)
                    {
                        return false;
                    }

                    // キューの開始
                    $this->setQueueNameForStart('protocol_names', $cid, ProtocolQueueEnum::ALIVE->value);
                }
            }

            // UNITパラメータへ接続IDを設定
            $this->unit_parameter->setConnectionId($des['connection_id']);

            // プロトコルUNITの実行
            $w_ret = $this->executeUnit($cid, 'protocol_names');
            if($w_ret === false)
            {
                continue;
            }

            // コマンドディスパッチャーの処理
            if($this->command_dispatcher !== null)
            {
                // コマンドUNIT実行中の検査
                $flg_exec = $this->isExecutingSequence('command_names', $cid);
                if($flg_exec === false)
                {
                    // 一番古い受信データを取得
                    $w_ret = $this->getRecvStack($cid);
                    if($w_ret === false)
                    {
                        return false;
                    }
                    $dat = $w_ret;
                    if($dat !== null)
                    {
                        // 受信データを退避
                        $this->setProperties($cid, ['receive_buffer' => $dat]);

                        // コマンドディスパッチャーのコール
                        $fnc = $this->command_dispatcher;
                        $w_ret = false;
                        try
                        {
                            $w_ret = $fnc($this->unit_parameter, $dat);
                        }
                        catch(UnitException | Exception $e)
                        {
                            $class = get_class($e);
                            if($class === self::E_CLASS_NAME_FOR_UNIT)
                            {
                                $this->logWriter('error', $e->getArrayMessage());
                            }
                            else
                            {
                                $this->logWriter('error', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                            }

                            // 緊急停止時コールバックを実行
                            $callback = $this->emergency_callback;
                            if($callback !== null)
                            {
                                $callback($this->unit_parameter);
                            }

                            $this->shutdown($cid);	// ソケット緊急切断
                            continue;
                        }
                        if($w_ret !== null)
                        {
                            $this->setQueueNameForStart('command_names', $cid, $w_ret);
                        }
                    }
                }
            }

            // コマンドUNITの実行
            $w_ret = $this->executeUnit($cid, 'command_names');
            if($w_ret === false)
            {
                continue;
            }
        }

        return true;
    }

    /**
     * 全接続IDを取得
     * 
     * @param string $p_cid 除外する接続ID
     * @return array 接続IDのリスト
     */
    public function getConnectionIdAll(string $p_cid = null): array
    {
        $dess = $this->descriptors;

        // 待ち受けポートを除く
        if($this->await_connection_id !== null)
        {
            unset($dess[$this->await_connection_id]);
        }

        // 指定された接続IDを除く
        if($p_cid !== null)
        {
            unset($dess[$p_cid]);
        }

        $w_ret = array_keys($dess);

        return $w_ret;
    }

    /**
     * キュー名の取得
     * 
     * @param string $p_kind 取得対象種別（'protocol_names' or 'command_names'）
     * @param string $p_cid 接続ID
     * @return ?string キュー名 or null（なし）
     */
    public function getQueueName(string $p_kind, string $p_cid): ?string
    {
        $w_ret = $this->descriptors[$p_cid][$p_kind]['queue_name'];
        return $w_ret;
    }

    /**
     * ステータス名の取得
     * 
     * @param string $p_kind 取得対象種別（'protocol_names' or 'command_names'）
     * @param string $p_cid 接続ID
     * @return ?string キュー名 or null（なし）
     */
    public function getStatusName(string $p_kind, string $p_cid): ?string
    {
        $w_ret = $this->descriptors[$p_cid][$p_kind]['status_name'];
        return $w_ret;
    }

    /**
     * ステータス名の設定
     * 
     * @param string $p_kind 設定対象種別（'protocol_names' or 'command_names'）
     * @param string $p_cid 接続ID
     * @param ?string $p_name キュー名 or null（なし）
     */
    public function setStatusName(string $p_kind, string $p_cid, ?string $p_name)
    {
        $this->descriptors[$p_cid][$p_kind]['status_name'] = $p_name;
        return;
    }

    /**
     * キューの実行状況を検査
     * 
     * @param string $p_cid 接続ID
     * @param string $p_kind UNIT種別（"protocol_names" or "command_names"）
     * @param string $p_que_nm キュー名
     * @return bool true（実行中） or false（停止中）
     */
    public function isExecutedQueue(string $p_cid, string $p_kind, string $p_que_nm): bool
    {
        $que = $this->getQueueName($p_kind, $p_cid);
        $sta = $this->getStatusName($p_kind, $p_cid);
        if($p_que_nm === $que && $sta !== null)
        {
            return true;
        }
        return false;
    }

    /**
     * アライブチェックを行う
     * 
     * 任意のタイミングで一時的に実行したい時に利用する
     * 
     * ※既に設定済みの場合は何もせずに終了する
     * 
     * @param string $p_kind UNIT種別（"protocol_names" or "command_names"）
     * @param string $p_cid 接続ID
     * @param int $p_tout アライブチェックタイムアウト（秒）
     * @return bool true（成功） or false（失敗）
     */
    public function aliveCheck(string $p_kind, string $p_cid, int $p_tout)
    {
        // キューの設定がない場合は抜ける
        $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::ALIVE->value, StatusEnum::START->value);
        if($w_ret === false)
        {
            return true;
        }

        // 実行中のキュー名を取得
        $nam = $this->getQueueName($p_kind, $p_cid);

        // 現在のタイムアウト値を取得
        $w_ret = $this->getProperties($p_cid, ['alive_adjust_timeout']);
        if($w_ret === false)
        {
            return false;
        }

        // 設定済みの場合は抜ける
        if($nam === ProtocolQueueEnum::ALIVE->value && $w_ret['alive_adjust_timeout'] !== null)
        {
            return true;
        }

        // アライブチェックを開始する
        $w_ret = $this->setQueueNameForStart($p_kind, $p_cid, ProtocolQueueEnum::ALIVE->value);
        if($w_ret === false)
        {
            return false;
        }

        // タイムアウトを設定
        $w_ret = $this->setProperties($p_cid, ['alive_adjust_timeout' => $p_tout]);
        if($w_ret === false)
        {
            return false;
        }

        return true;
    }

    /**
     * プロパティの取得（ディスクリプタ内）
     * 
     * @param string $p_cid 接続ID
     * @param array $p_prop プロパティ名のリスト
     * @return array プロパティのリスト or null（空） or false（失敗）
     */
    public function getProperties(string $p_cid, array $p_prop)
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // プロパティの取得
        $ret = [];
        foreach($p_prop as $key)
        {
            if(!isset($this->descriptors[$p_cid][$key]))
            {
                return null;
            }

            $ret[$key] = $this->descriptors[$p_cid][$key];
        }

        return $ret;
    }

    /**
     * プロパティの設定（ディスクリプタ内）
     * 
     * @param string $p_cid 接続ID
     * @param array $p_prop プロパティのリスト
     * @return bool true（成功） or false（失敗）
     */
    public function setProperties(string $p_cid, array $p_prop): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // プロパティの設定
        foreach($p_prop as $key => $val)
        {
            $this->descriptors[$p_cid][$key] = $val;
        }

        return true;
    }

    /**
     * ユーザープロパティの取得（ディスクリプタ内）
     * 
     * @param string $p_cid 接続ID
     * @param array $p_prop ユーザープロパティのリスト
     * @return mixed ユーザープロパティデータ or null（空） or false（失敗）
     */
    public function getUserProperties(string $p_cid, array $p_prop)
    {
        // ソケットディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        $ret = [];

        // ユーザープロパティの取得
        foreach($p_prop as $key)
        {
            if(!isset($this->descriptors[$p_cid]['user_property'][$key]))
            {
                return null;
            }

            $ret[$key] = $this->descriptors[$p_cid]['user_property'][$key];
        }

        return $ret;
    }

    /**
     * ユーザープロパティの設定（ディスクリプタ内）
     * 
     * @param string $p_cid 接続ID
     * @param array $p_prop ユーザープロパティのリスト
     * @return bool true（成功） or false（失敗）
     */
    public function setUserProperties(string $p_cid, array $p_prop): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // プロパティの設定
        foreach($p_prop as $key => $val)
        {
            $this->descriptors[$p_cid]['user_property'][$key] = $val;
        }

        return true;
    }

    /**
     * 待ち受けホスト名を取得
     * 
     * @return ?string 待ち受けホスト名
     */
    public function getAwaitHost(): ?string
    {
        $w_ret = $this->await_host;
        return $w_ret;
    }

    /**
     * 待ち受けポート番号を取得
     * 
     * @return ?int 待ち受けポート番号
     */
    public function getAwaitPort(): ?int
    {
        $w_ret = $this->await_port;
        return $w_ret;
    }

    /**
     * 待ち受けポートの接続IDを取得
     * 
     * @return ?string 待ち受けポートの接続ID
     */
    public function getAwaitConnectionId(): ?string
    {
        $w_ret = $this->await_connection_id;
        return $w_ret;
    }

    /**
     * シリアライザーの取得
     * 
     * 送信データ（ペイロード部）のシリアライズ処理
     * 
     * @return mixed 関数（あるいは関数名） or null（空）
     */
    private function getSerializer()
    {
        $w_ret = $this->serializer;
        return $w_ret;
    }

    /**
     * アンシリアライザーの取得
     * 
     * 受信データ（ペイロード部）のアンシリアライズ処理
     * 
     * @return mixed 関数（あるいは関数名） or null（空）
     */
    public function getUnserializer()
    {
        $w_ret = $this->unserializer;
        return $w_ret;
    }

    /**
     * ソケットディスクリプタの存在を検査
     * 
     * @param string $p_cid 接続ID
     * @return bool true（存在する） or false（存在しない）
     */
    public function isExistDescriptor(string $p_cid): bool
    {
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        return true;
    }

    /**
     * 現在のクライアント数を取得
     * 
     * @return int クライアント数
     */
    public function getClientCount(): int
    {
        $cnt = count($this->descriptors);

        // 待ち受けポートがある場合
        if($this->await_connection_id !== null)
        {
            $cnt--;
        }

        return $cnt;
    }

    /**
     * ログ出力
     * 
     * SocketManagerで使用しているチャンネル名と同じになる
     * 
     * @param string $p_level ログレベル
     * @param array $p_param ログパラメータ
     */
    public function logWriter(string $p_level, array $p_param)
    {
        $log_writer = $this->log_writer;
        if($log_writer !== null)
        {
            $log_writer($p_level, $p_param);
        }
    }


    //--------------------------------------------------------------------------
    // 切断処理関連
    //--------------------------------------------------------------------------

    /**
     * ソケット切断シーケンスの開始
     * 
     * プロトコルUNIT実行中に呼ばれた場合は例外を投げて現在の処理を中断する
     * 
     * @param string $p_cid 接続ID
     * @param mixed $p_param 切断パラメータ
     * @param bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @return bool true（成功） or false（失敗）
     */
    public function close(string $p_cid, $p_param = null, bool $p_convert = true): bool
    {
        // ディスクリプタが存在しない場合は抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // キューの設定がない場合は抜ける
        $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::CLOSE->value, StatusEnum::START->value);
        if($w_ret === false)
        {
            return true;
        }

        // シリアライザーの実行
        $param = $p_param;
        if($p_convert === true && $this->serializer !== null)
        {
            $serializer = $this->serializer;
            $param = $serializer($param);
        }

        // 切断パラメータの設定
        $w_ret = $this->setProperties($p_cid, ['close_buffer' => $param]);
        if($w_ret === false)
        {
            return false;
        }

        // 切断シーケンスを実行
        $this->descriptors[$p_cid]['protocol_names']['queue_name'] = ProtocolQueueEnum::CLOSE->value;
        $this->descriptors[$p_cid]['protocol_names']['status_name'] = StatusEnum::START->value;

        // プロトコルUNIT実行中は例外を投げて中断する
        $w_ret = $this->unit_parameter->getKindString();
        if($w_ret === 'protocol_names')
        {
            $this->throwBreak();
        }

        return true;
    }

    /**
     * プロトコルUNIT処理を中断する
     * 
     * 実行されると例外キャッチ時に切断処理は無視されて処理を継続する
     */
    public function throwBreak()
    {
        // 例外発行
        throw new UnitException(
            UnitExceptionEnum::ECODE_THROW_BREAK->message($this->lang),
            UnitExceptionEnum::ECODE_THROW_BREAK->value,
            $this->unit_parameter
        );
    }


    //--------------------------------------------------------------------------
    // システムコール関連
    //--------------------------------------------------------------------------

    /**
     * ソケット接続
     * 
     * @param string $p_host ホスト名
     * @param int $p_port ポート番号
     * @param bool $p_udp UDPフラグ true（UDP） or false（TCP）
     * @param int $p_retry リトライ回数（0：無限）
     * @param int $p_interval リトライ間隔（μs）
     * @return bool true（成功） or false（失敗）
     */
    public function connect(string $p_host, int $p_port, bool $p_udp = false, int $p_retry = 0, int $p_interval = 1000): bool
    {
        // retry count value error
        if($p_retry < 0)
        {
            return false;
        }

        // ソケットタイプ、プロトコルの設定
        $from = '';
        $port = 0;
        $type = SOCK_DGRAM;
        $protocol = SOL_UDP;
        if($p_udp !== true)
        {
            $type = SOCK_STREAM;
            $protocol = SOL_TCP;
        }

        // Create TCP/IP sream socket
        $w_ret = socket_create(AF_INET, $type, $protocol);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => 'socket_create', 'message' => LogMessageEnum::SOCKET_ERROR->socket()]);
            return false;
        }
        $soc = $w_ret;

        // connect to port
        $max = $p_retry;
        if($p_retry === 0)
        {
            $max = 1;
        }
        for($i = 0; $i < $max; )
        {
            if($p_udp === true)
            {
                $dat = self::UDP_CONNECTION_IDENTIFY;
                $len = strlen($dat);
                $w_ret = socket_sendto($soc, $dat, $len, 0, $p_host, $p_port);
                if($w_ret === false)
                {
                    $this->logWriter('error', [__METHOD__ => 'socket_sendto', 'message' => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
                    return false;
                }

                $buf = '';
                $w_ret = socket_recvfrom($soc, $buf, $this->receive_buffer_size, 0, $from, $port);
                if($w_ret === false)
                {
                    $w_ret = LogMessageEnum::SOCKET_ERROR->array($soc);
                    if($w_ret['code'] === 0)  // 接続中でない場合
                    {
                        continue;
                    }
                    $this->logWriter('error', [__METHOD__ => 'socket_recvfrom', 'message' => $w_ret['message']]);
                    return false;
                }
                $this->logWriter('notice', [__METHOD__ => 'recv complete', 'recv data' => $buf, 'host' => $from, 'port' => $port]);

                if($buf !== self::UDP_CONNECTION_IDENTIFY)
                {
                    return false;
                }
                break;
            }
            else
            {
                try
                {
                    $w_ret = socket_connect($soc, $p_host, $p_port);
                    if($w_ret === false)
                    {
                        $this->logWriter('error', [__METHOD__ => 'socket_connect', 'message' => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
                        return false;
                    }
                    break;
                }
                catch(\Exception $e)
                {
                    $this->logWriter('notice', [__METHOD__ => 'connect retry', 'code' => $e->getCode(), 'message' => $e->getMessage()]);
                }
            }
            usleep($p_interval);
            if($p_retry > 0)
            {
                $i++;
            }
        }
        if($i >= $max)
        {
            return false;
        }

        // ソケットディスクリプタの生成
        $w_ret = $this->createDescriptor($soc, $p_udp);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_CREATE_FAIL->message($this->lang)]);
            return false;
        }
        $des = $w_ret;

        // キューの設定がない場合は抜ける
        $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::CONNECT->value, StatusEnum::START->value);
        if($w_ret === true)
        {
            // 接続時のキュー名設定
            $w_ret = $this->setQueueNameForStart('protocol_names', $des['connection_id'], ProtocolQueueEnum::CONNECT->value);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => "[{$des['connection_id']}]".LogMessageEnum::QUEUE_START_FAIL->message($this->lang)]);
                return false;
            }
        }

        // UDPの場合は相手先を登録してから抜ける
        if($p_udp === true)
        {
            $prop =
            [
                'host' => $from,
                'port' => $port
            ];
            $this->setProperties($des['connection_id'], ['udp_peers' => $prop]);

            $this->logWriter('notice', [__METHOD__ => 'udp connect', 'connection id' => $des['connection_id']]);

            return true;
        }

        return true;
    }

    /**
     * ソケットリッスン（TCP用）
     * 
     * 引数のホスト名とポート番号の指定があれば、コンストラクタで設定された内容より優先されます
     * 
     * @param string $p_host ホスト名
     * @param int $p_port ポート番号
     * @return bool true（成功） or false（失敗）
     */
    public function listen(string $p_host = null, int $p_port = null): bool
    {
        //--------------------------------------------------------------------------
        // プロパティの設定
        //--------------------------------------------------------------------------
        if($p_host !== null)
        {
            $this->await_host = $p_host;
        }
        if($p_port !== null)
        {
            $this->await_port = $p_port;
        }

        //--------------------------------------------------------------------------
        // ソケットの初期化
        //--------------------------------------------------------------------------

        // Create TCP/IP sream socket
        $w_ret = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket()]);
            return false;
        }
        $soc = $w_ret;

        // reuseable port
        $w_ret = socket_set_option($soc, SOL_SOCKET, SO_REUSEADDR, 1);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_OPTION_SETTING_FAIL->message($this->lang)]);
            return false;
        }

        // bind socket to specified host
        $w_ret = socket_bind($soc, $this->await_host, $this->await_port);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
            return false;
        }

        // listen to port
        $w_ret = socket_listen($soc);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
            return false;
        }

        // ソケットディスクリプタの生成
        $w_ret = $this->createDescriptor($soc);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_CREATE_FAIL->message($this->lang)]);
            return false;
        }
        $des = $w_ret;

        // 待ち受けソケットの接続IDの設定
        $this->await_connection_id = $des['connection_id'];

        return true;
    }

    /**
     * ソケットバインド（UDP用）
     * 
     * 引数のホスト名とポート番号の指定があれば、コンストラクタで設定された内容より優先されます
     * 
     * @param string $p_host ホスト名
     * @param int $p_port ポート番号
     * @return bool true（成功） or false（失敗）
     */
    public function bind(string $p_host = null, int $p_port = null): bool
    {
        //--------------------------------------------------------------------------
        // プロパティの設定
        //--------------------------------------------------------------------------
        if($p_host !== null)
        {
            $this->await_host = $p_host;
        }
        if($p_port !== null)
        {
            $this->await_port = $p_port;
        }

        // Create TCP/IP sream socket
        $w_ret = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket()]);
            return false;
        }
        $soc = $w_ret;

        // reuseable port
        $w_ret = socket_set_option($soc, SOL_SOCKET, SO_REUSEADDR, 1);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_OPTION_SETTING_FAIL->message($this->lang)]);
            return false;
        }

        // bind socket to specified host
        $w_ret = socket_bind($soc, $this->await_host, $this->await_port);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
            return false;
        }

        // ソケットディスクリプタの生成
        $w_ret = $this->createDescriptor($soc, true);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_CREATE_FAIL->message($this->lang)]);
            return false;
        }
        $des = $w_ret;

        // 待ち受けソケットの接続IDを設定
        $this->await_connection_id = $des['connection_id'];

        return true;
    }

    /**
     * ソケットセレクト
     * 
     * @param int $p_utimer ブロッキングタイム（マイクロ秒）
     * @return bool true（成功） or false（失敗）
     */
    private function select($p_utimer = 0): bool
    {
        //--------------------------------------------------------------------------
        // 処理対象のソケットがない場合は抜ける
        //--------------------------------------------------------------------------

        $cnt = count($this->sockets);
        if($cnt <= 0)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_NO_COUNT->message($this->lang)]);
            return false;
        }

        //--------------------------------------------------------------------------
        // セレクト実行
        //--------------------------------------------------------------------------

        $nul = null;
        $chgs = $this->sockets;
        $exp = null;
        $w_ret = @socket_select($chgs, $nul, $exp, 0, $p_utimer);
        if($w_ret === false)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket()]);
            return false;
        }

        //--------------------------------------------------------------------------
        // 下記変数の設定
        // ・コネクトフラグ（$flg_accept） 0:コネクト要求ではない、1:TCPによるアクセプト、2:UDPによるコネクト
        // ・セレクトしたディスクリプタリスト（$this->changed_descriptors）
        //--------------------------------------------------------------------------

        $cid = null;
        $flg_connect = false;
        $this->changed_descriptors = array();
        foreach($chgs as $chg)
        {
            // ソケットの接続IDを取り出す
            foreach($this->sockets as $no => $soc)
            {
                if($chg == $soc)
                {
                    $cid = $no;
                }
            }
            if($cid == $this->await_connection_id)
            {
                $flg_connect = 1;
                $w_ret = $this->getProperties($cid, ['udp']);
                if($w_ret['udp'] === true)
                {
                    $flg_connect = 2;
                }
            }
            else
            {
                array_push($this->changed_descriptors, $this->descriptors[$cid]);
            }
        }

        //--------------------------------------------------------------------------
        // アクセプト
        //--------------------------------------------------------------------------

        $des = null;
        if($flg_connect === 1)
        {
            $soc = @socket_accept($this->sockets[$this->await_connection_id]);
            if($soc === false)
            {
                $w_soc = $this->sockets[$this->await_connection_id];
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket($w_soc)]);
                return false;
            }

            // 制限接続数の判定
            $cnt = $this->getClientCount();
            if($cnt >= $this->limit_connection)
            {
                @socket_close($soc);
                return true;
            }

            // ソケットディスクリプタの生成
            $w_ret = $this->createDescriptor($soc);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_CREATE_FAIL->message($this->lang)]);
                return false;
            }
            $des = $w_ret;
        }
        else
        if($flg_connect === 2)
        {
            $soc = $this->sockets[$cid];
            $buf = '';
            $from = '';
            $port = 0;
            $w_ret = socket_recvfrom($soc, $buf, $this->receive_buffer_size, 0, $from, $port);
            if($w_ret === false)
            {
                $this->logWriter('error', ['udp first recv' => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
                return false;
            }
            $this->logWriter('notice', ['udp first recv data' => $buf, 'host' => $from, 'port' => $port]);

            if($buf !== self::UDP_CONNECTION_IDENTIFY)
            {
                return false;
            }

            // Create UDP/IP sream socket
            $w_ret = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket()]);
                return false;
            }
            $soc = $w_ret;

            $dat = self::UDP_CONNECTION_IDENTIFY;
            $len = strlen($dat);
            $w_ret = socket_sendto($soc, $dat, $len, 0, $from, $port);
            if($w_ret === false)
            {
                $this->logWriter('error', ['udp first send' => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
                return false;
            }
            $this->logWriter('notice', ['udp first sending len' => $w_ret, 'udp first sending data' => '']);

            // 制限接続数の判定
            $cnt = $this->getClientCount();
            if($cnt >= $this->limit_connection)
            {
                @socket_close($soc);
                return true;
            }

            // ソケットディスクリプタの生成
            $w_ret = $this->createDescriptor($soc, true);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_CREATE_FAIL->message($this->lang)]);
                return false;
            }
            $des = $w_ret;

            $prop =
            [
                'host' => $from,
                'port' => $port
            ];
            $this->setProperties($des['connection_id'], ['udp_peers' => $prop]);

            $this->logWriter('notice', [__METHOD__ => 'udp select', 'connection id' => $des['connection_id']]);
        }

        if($des !== null)
        {
            // キューの設定がない場合は抜ける
            $w_ret = $this->cycle_driven_for_protocol->isSetQueue(ProtocolQueueEnum::ACCEPT->value, StatusEnum::START->value);
            if($w_ret === false)
            {
                return true;
            }

            // アクセプト時のキュー名設定
            $w_ret = $this->setQueueNameForStart('protocol_names', $des['connection_id'], ProtocolQueueEnum::ACCEPT->value);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::QUEUE_START_FAIL->message($this->lang)]);
                return false;
            }
        }

        return true;
    }

    /**
     * ソケットクローズ
     * 
     * @param string $p_cid 接続ID
     * @return bool true（成功） or false（失敗）
     */
    public function shutdown(string $p_cid): bool
    {
        // ディスクリプタが存在しない場合は抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // ソケットリソースの取得
        $soc = $this->sockets[$p_cid];

        // ソケットの読み込み／書き込みを停止
        @socket_shutdown($soc, 2);

        // ソケットリソースの解放
        @socket_close($soc);

        // マネージャーのエントリからはずす
        unset($this->sockets[$p_cid]);
        unset($this->descriptors[$p_cid]);

        return true;
    }

    /**
     * ソケット全クローズ
     * 
     * @return bool true（成功） or false（失敗）
     */
    public function shutdownAll(): bool
    {
        foreach($this->descriptors as $cid => $des)
        {
            $w_ret = $this->shutdown($cid);
            if($w_ret === false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * データ受信サイズの設定
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param string $p_cid 接続ID
     * @param int $p_size 受信サイズ
     * @return bool true（成功） or false(失敗)
     */
    public function setReceivingSize(string $p_cid, int $p_size): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // 受信バッファへ設定
        $this->descriptors[$p_cid]['receiving_buffer']['size'] = $p_size;
        $this->descriptors[$p_cid]['receiving_buffer']['data'] = '';
        $this->descriptors[$p_cid]['receiving_buffer']['receiving_size'] = 0;

        return true;
    }

    /**
     * データ受信
     * 
     * setReceivingSizeで設定されたサイズ分を受信するまで続ける
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param string $p_cid 接続ID
     * @return mixed 受信データ or null（受信中） or false（失敗）
     */
    public function receiving(string $p_cid)
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // データ受信サイズ設定がされていない場合は抜ける
        if
        (
                $this->descriptors[$p_cid]['receiving_buffer']['size'] === null
            &&	$this->descriptors[$p_cid]['receiving_buffer']['data'] === null
        )
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::RECEIVE_SIZE_NO_SETTING->message($this->lang)]);
            return false;
        }

        // 設定サイズの取得
        $setting_siz = $this->descriptors[$p_cid]['receiving_buffer']['size'];

        // 受信中サイズの取得
        $receiving_siz = $this->descriptors[$p_cid]['receiving_buffer']['receiving_size'];

        // 今回の受信サイズ
        $siz = $setting_siz - $receiving_siz;

        // データ受信
        $soc = $this->sockets[$p_cid];
        $prop = $this->getProperties($p_cid, ['udp']);
        $udp = $prop['udp'];
        if($udp === true)
        {
            $buf = '';
            $from = '';
            $port = 0;
            $w_ret = @socket_recvfrom($soc, $buf, $siz, 0, $from, $port);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => 'socket_recvfrom', "message" => LogMessageEnum::SOCKET_ERROR->socket($soc), 'connection id' => $p_cid]);
                return false;
            }
            $rcv = $buf;
            $rcv_siz = strlen($rcv);
        }
        else
        {
            $rcv = '';
            $w_ret = @socket_read($soc, $siz);
            if($w_ret === false)
            {
                $w_ret = LogMessageEnum::SOCKET_ERROR->array($soc);
                $this->logWriter('notice', [__METHOD__ => 'socket_read', "message" => $w_ret['message'], 'connection id' => $p_cid]);

                // ソケット操作を完了できなかった
                if($w_ret['code'] === self::SOCKET_ERROR_COULDNT_COMPLETED)
                {
                    return null;
                }
                // 相手からの切断
                else
                {
                    $shutdown = false;
                    foreach(self::SOCKET_ERROR_PEER_SHUTDOWN as $cod)
                    {
                        if($w_ret['code'] === $cod)
                        {
                            $shutdown = true;
                        }
                    }
                    if($shutdown === true)
                    {
                        // 緊急停止時コールバックを実行
                        $callback = $this->emergency_callback;
                        if($callback !== null)
                        {
                            $callback($this->unit_parameter);
                        }
                    }
                }
                return false;
            }
            $rcv = $w_ret;
            $rcv_siz = strlen($rcv);
        }

        // 最終アクセスタイムスタンプを設定
        if($rcv_siz > 0)
        {
            $w_ret = $this->setProperties($p_cid, ['last_access_timestamp' => time()]);
            if($w_ret === false)
            {
                return false;
            }
        }

        // 設定サイズ未満の場合は抜ける
        $receiving_siz += $rcv_siz;
        if($receiving_siz < $setting_siz)
        {
            return null;
        }

        // 受信データを設定
        $ret = $this->descriptors[$p_cid]['receiving_buffer']['data'] . $rcv;

        // 送信バッファを初期化
        $this->descriptors[$p_cid]['receiving_buffer']['size'] = null;
        $this->descriptors[$p_cid]['receiving_buffer']['data'] = null;
        $this->descriptors[$p_cid]['receiving_buffer']['receiving_size'] = 0;

        return $ret;
    }

    /**
     * （receivingメソッドによる）データ受信中の検査
     * 
     * @param string $p_cid 接続ID
     * @return bool true（受信中） or false（受信中ではない）
     */
    public function isReceiving(string $p_cid): bool
    {
        // 変数へ退避
        $siz = $this->descriptors[$p_cid]['receiving_buffer']['size'];
        $dat = $this->descriptors[$p_cid]['receiving_buffer']['data'];

        // 受信バッファが未設定か
        if($siz === null && $dat === null)
        {
            return false;
        }

        return true;
    }

    /**
     * データ受信
     * 
     * 受信バッファサイズ分を受信する
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param string $p_cid 接続ID
     * @param mixed &$p_recv 受信エリア
     * @param int $p_size 受信サイズ（指定があればデフォルトサイズより優先される）
     * @return int 受信したサイズ or false（失敗） or null（取得できるデータがない）
     */
    public function recv(string $p_cid, &$p_recv, int $p_size = null)
    {
        // 受信サイズ決定
        $size = $this->receive_buffer_size;
        if($p_size !== null)
        {
            $size = $p_size;
        }

        // ソケットリソースの取得
        $soc = $this->sockets[$p_cid];

        // データ受信
        $prop = $this->getProperties($p_cid, ['udp']);
        $udp = $prop['udp'];
        if($udp === true)
        {
            $buf = '';
            $from = '';
            $port = 0;
            $w_ret = @socket_recvfrom($soc, $buf, $size, 0, $from, $port);
            if($w_ret === false)
            {
                $this->logWriter('error', [__METHOD__ => LogMessageEnum::SOCKET_ERROR->socket($soc)]);
                return false;
            }

            $p_recv = $buf;
        }
        else
        {
            $w_ret = @socket_read($soc, $size);
            if($w_ret === false)
            {
                $w_ret = LogMessageEnum::SOCKET_ERROR->array($soc);
                $this->logWriter('notice', [__METHOD__ => $w_ret['message']]);

                // ソケット操作を完了できなかった
                if($w_ret['code'] === self::SOCKET_ERROR_COULDNT_COMPLETED)
                {
                    return null;
                }
                // 相手からの切断
                else
                {
                    $shutdown = false;
                    foreach(self::SOCKET_ERROR_PEER_SHUTDOWN as $cod)
                    {
                        if($w_ret['code'] === $cod)
                        {
                            $shutdown = true;
                        }
                    }
                    if($shutdown === true)
                    {
                        // 緊急停止時コールバックを実行
                        $callback = $this->emergency_callback;
                        if($callback !== null)
                        {
                            $callback($this->unit_parameter);
                        }
                    }
                }
                return false;
            }

            $p_recv = $w_ret;
        }

        $len = strlen($p_recv);
        
        // 最終アクセスタイムスタンプを設定
        if($len > 0)
        {
            $w_ret = $this->setProperties($p_cid, ['last_access_timestamp' => time()]);
            if($w_ret === false)
            {
                return false;
            }
        }

        return $len;
    }

    /**
     * 送信データの設定
     * 
     * ※プロトコルUNITで使用
     * 
     * @param string $p_cid 接続ID
     * @param string $p_data 送信データ
     * @return bool true（成功） or false（失敗）
     */
    public function setSendingData(string $p_cid, string $p_data): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        $this->descriptors[$p_cid]['sending_buffer']['data'] = $p_data;

        return true;
    }

    /**
     * データ送信
     * 
     * setSendingDataで設定されたデータを送信するまで続ける
     * 
     * ※プロトコルUNITで使用 
     * 
     * @param string $p_cid 接続ID
     * @return bool|null true（成功） or false（失敗） or null（送信中）
     */
    public function sending(string $p_cid): ?bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // 送信データが設定されていない場合は抜ける
        if($this->descriptors[$p_cid]['sending_buffer']['data'] === null)
        {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::SEND_DATA_NO_SETTING->message($this->lang), 'connection id' => $p_cid]);
            return false;
        }

        // ソケットリソースの取得
        $soc = $this->sockets[$p_cid];

        // 送信中データの取得
        $dat = $this->descriptors[$p_cid]['sending_buffer']['data'];

        // 送信処理
        $prop = $this->getProperties($p_cid, ['udp']);
        $udp = $prop['udp'];
        if($udp === true)
        {
            // 送信先を取得
            $prop = $this->getProperties($p_cid, ['udp_peers']);

            // データ送信
            $len = strlen($dat);
            $host = $prop['udp_peers']['host'];
            $port = $prop['udp_peers']['port'];
            $w_ret = @socket_sendto($soc, $dat, $len, 0, $host, $port);
            if($w_ret === false)
            {
                $w_ret = LogMessageEnum::SOCKET_ERROR->array($soc);
                $this->logWriter('error', [__METHOD__ => 'socket_sendto', "message" => $w_ret['message'], 'connection id' => $p_cid]);
                if($w_ret['code'] === self::SOCKET_ERROR_COULDNT_COMPLETED)
                {
                    return null;
                }
                return false;
            }
        }
        else
        {
            // データ送信
            $w_ret = @socket_write($soc, $dat, strlen($dat));
            if($w_ret === false)
            {
                $w_ret = LogMessageEnum::SOCKET_ERROR->array($soc);
                $this->logWriter('notice', [__METHOD__ => 'socket_write', "message" => $w_ret['message'], 'connection id' => $p_cid]);
                if($w_ret['code'] === self::SOCKET_ERROR_COULDNT_COMPLETED)
                {
                    return null;
                }
                return false;
            }
        }

        // 送信完了でない場合
        if($w_ret < strlen($dat))
        {
            // 送信バッファに次回送信分をセットする
            $dat = substr($dat, $w_ret);
            $this->descriptors[$p_cid]['sending_buffer']['data'] = $dat;
            return null;
        }

        // 送信バッファを初期化
        $this->descriptors[$p_cid]['sending_buffer']['data'] = null;

        return true;
    }

    /**
     * （sendingメソッドによる）データ送信中の検査
     * 
     * @param string $p_cid 接続ID
     * @return bool true（送信中） or false（送信中ではない）
     */
    public function isSending(string $p_cid): bool
    {
        // 変数へ退避
        $dat = $this->descriptors[$p_cid]['sending_buffer']['data'];

        // 送信バッファが未設定か
        if($dat === null)
        {
            return false;
        }

        return true;
    }


    //--------------------------------------------------------------------------
    // 送受信バッファ操作
    //--------------------------------------------------------------------------

    /**
     * 受信データスタックから取得
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param string $p_cid 接続ID
     * @param bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @return mixed 受信した最新のペイロードデータ or null（空） or false（失敗）
     */
    private function getRecvStack(string $p_cid, bool $p_convert = true)
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // １件もなければ抜ける
        $cnt = count($this->descriptors[$p_cid]['receive_buffers']);
        if($cnt <= 0)
        {
            return null;
        }

        // １件分取得
        $buf = array_shift($this->descriptors[$p_cid]['receive_buffers']);

        $dat = $buf;
        if($p_convert === true)
        {
            // アンシリアライザーの実行
            if($this->unserializer !== null)
            {
                $unserializer = $this->unserializer;
                $dat = $unserializer($buf);
            }
        }

        return $dat;
    }

    /**
     * 受信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param string $p_cid 接続ID
     * @param mixed $p_data 設定するデータ
     * @param bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @return bool true（成功） or false（失敗）
     */
    public function setRecvStack(string $p_cid, $p_data = null, bool $p_convert = false): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // シリアライザーの実行
        $data = $p_data;
        if($p_convert === true && $this->serializer !== null)
        {
            $serializer = $this->serializer;
            $data = $serializer($data);
        }

        // 最後尾に追加
        array_push($this->descriptors[$p_cid]['receive_buffers'], $data);

        return true;
    }

    /**
     * 送信データスタックから取得
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param string $p_cid 接続ID
     * @param bool $p_convert
     * ― 変換（シリアライズ／アンシリアライズの行使）フラグ
     * 
     * ― フラグ指定に関わらずシリアライザーが登録されていなければ変換はされない
     * 
     * @return mixed 送信データスタックエントリ or null（空） or false（失敗）
     */
    private function getSendStack(string $p_cid, bool $p_convert = false)
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // １件もなければ抜ける
        $cnt = count($this->descriptors[$p_cid]['send_buffers']);
        if($cnt <= 0)
        {
            return null;
        }

        // １件分取得
        $buf = array_shift($this->descriptors[$p_cid]['send_buffers']);

        // アンシリアライザーの実行
        $data = $buf;
        if($p_convert === true)
        {
            if($this->unserializer !== null)
            {
                $unserializer = $this->unserializer;
                $data = $unserializer($buf);
            }
        }

        return $data;
    }

    /**
     * 送信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param string $p_cid 接続ID
     * @param mixed $p_data 送信データ
     * @return bool true（成功） or false（失敗）
     */
    public function setSendStack(string $p_cid, $p_data): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // シリアライズ
        $data = $p_data;
        if($this->serializer !== null)
        {
            $serializer = $this->serializer;
            $data = $serializer($data);
        }

        // 送信データスタックへ追加
        array_push($this->descriptors[$p_cid]['send_buffers'], $data);

        return true;
    }

    /**
     * 全接続の送信データスタックへ設定
     * 
     * ※基本的に送受信スタック内のペイロードデータはシリアライズ化されている事を前提とする
     * 
     * @param string $p_cid 接続ID
     * @param mixed $p_data 送信データ
     * @param bool $p_self_remove 自身の接続の除外フラグ
     * @return bool true（成功） or false（失敗）
     */
    public function setSendStackAll(string $p_cid, $p_data, bool $p_self_remove = false): bool
    {
        // ディスクリプタが存在しなければ抜ける
        if(!isset($this->descriptors[$p_cid]))
        {
            return false;
        }

        // 全ディスクリプタを退避
        $dess = $this->descriptors;

        // Listenポートを除く
        unset($dess[$this->await_connection_id]);

        // 自身を除く
        if($p_self_remove === true)
        {
            unset($dess[$p_cid]);
        }

        // 全ディスクリプタでループ
        foreach($dess as $cid => $des)
        {
            // 送信データスタックへ追加
            $w_ret = $this->setSendStack($cid, $p_data);
            if($w_ret === false)
            {
                return false;
            }
        }

        return true;
    }


    //--------------------------------------------------------------------------
    // 内部処理
    //--------------------------------------------------------------------------

    /**
     * キュー名の設定（処理開始用）
     * 
     * @param string $p_kind 設定対象種別（'protocol_names' or 'command_names'）
     * @param string $p_cid 接続ID
     * @param ?string $p_name キュー名 or null（なし）
     * @return bool true（成功） or false (失敗)
     */
    private function setQueueNameForStart(string $p_kind, string $p_cid, ?string $p_name): bool
    {
        // キュー名の設定
        $this->descriptors[$p_cid][$p_kind]['queue_name'] = $p_name;

        // ステータス名の設定
        if($p_name === null)
        {
            $this->descriptors[$p_cid][$p_kind]['status_name'] = null;
        }
        else
        {
            $this->descriptors[$p_cid][$p_kind]['status_name'] = StatusEnum::START->value;
        }

        return true;
    }

    /**
     * UNIT実行中の検査
     * 
     * @param string $p_kind 調査対象種別（'protocol_names' or 'command_names'）
     * @param string $p_cid 接続ID
     * @return bool true（実行中） or false（未実行）
     */
    private function isExecutingSequence(string $p_kind, string $p_cid): bool
    {
        // キュー名の取得
        $que_nam = $this->descriptors[$p_cid][$p_kind]['queue_name'];

        // ステータス名の取得
        $sta_nam = $this->descriptors[$p_cid][$p_kind]['status_name'];

        // 実行中
        if(isset($que_nam) && isset($sta_nam))
        {
            return true;
        }

        return false;
    }

    /**
     * UNITの実行
     * 
     * @param string $p_cid 接続ID
     * @param string $p_kind UNIT種別（"protocol_names" or "command_names"）
     * @return bool true（成功） or false（切断）
     */
    private function executeUnit(string $p_cid, string $p_kind): bool
    {
        // 周期ドリブンマネージャーの取得
        $cycle_driven = $this->cycle_driven_for_protocol;
        if($p_kind !== 'protocol_names')
        {
            $cycle_driven = $this->cycle_driven_for_command;
        }

        // UNITの実行
        $this->unit_parameter->setKindString($p_kind);
        try
        {
            $w_ret = $cycle_driven->cycleDriven($this->unit_parameter);
            if($w_ret === false)
            {
                $que = $this->getQueueName($p_kind, $p_cid);
                $sta = $this->getStatusName($p_kind, $p_cid);
                $this->logWriter('warning', [__METHOD__ => LogMessageEnum::UNIT_NO_SETTING->message($this->lang)."[{$que}][{$sta}]"]);
            }
        }
        catch(UnitException | Exception $e)
        {
            $w_ret = get_class($e);
            if($w_ret === self::E_CLASS_NAME_FOR_UNIT)
            {
                if($e->getCode() !== UnitExceptionEnum::ECODE_THROW_BREAK->value)
                {
                    $this->logWriter('error', $e->getArrayMessage());
                    $this->shutdown($p_cid);	// ソケット緊急切断
                    return false;
                }
                else
                {
                    $this->logWriter('info', $e->getArrayMessage());
                }
            }
            else
            {
                $this->logWriter('error', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $this->shutdown($p_cid);	// ソケット緊急切断
                return false;
            }
        }

        return true;
    }

    /**
     * ソケットディスクリプタの生成
     * 
     * @param Socket $p_socket ソケットリソース
     * @param bool $p_udp UDPフラグ
     * @return array|bool ディスクリプタ or false（失敗）
     */
    private function createDescriptor(Socket $p_socket, bool $p_udp = false)
    {
        // ソケットの接続IDを生成
        $cid = '#'.$this->next_connection_id;

        // ソケット要素の反映
        $this->sockets[$cid] = $p_socket;

        $this->descriptors[$cid] = [];

        // 接続ID
        $this->descriptors[$cid]['connection_id'] = $cid;

        // UDPフラグ
        $this->descriptors[$cid]['udp'] = $p_udp;

        // UDPクライアントリスト
        $this->descriptors[$cid]['udp_peers'] = null;

        // 送信バッファスタック
        $this->descriptors[$cid]['send_buffers'] = [];

        // 受信バッファスタック
        $this->descriptors[$cid]['receive_buffers'] = [];

        // 受信バッファ
        $this->descriptors[$cid]['receiving_buffer'] = [
            'size' => null,
            'data' => null,
            'receiving_size' => 0
        ];

        // 送信バッファ
        $this->descriptors[$cid]['sending_buffer'] = [
            'data' => null,
        ];

        // ピックアップ受信バッファ（コマンドUNIT用）
        $this->descriptors[$cid]['receive_buffer'] = null;

        // ピックアップ送信バッファ（プロトコルUNIT用）
        $this->descriptors[$cid]['send_buffer'] = null;

        // 切断情報バッファ
        $this->descriptors[$cid]['close_buffer'] = null;

        // プロトコル用
        $this->descriptors[$cid]['protocol_names'] = [
              'queue_name' => null	// キュー名
            , 'status_name' => null	// ステータス名
        ];

        // コマンド用
        $this->descriptors[$cid]['command_names'] = [
              'queue_name' => null	// キュー名
            , 'status_name' => null	// ステータス名
        ];

        // 最終アクセス日時
        $this->descriptors[$cid]['last_access_timestamp'] = time();

        // アライブチェックタイムアウト調整用
        $this->descriptors[$cid]['alive_adjust_timeout'] = null;

        // ユーザープロパティ（自由定義）
        $this->descriptors[$cid]['user_property'] = [];

        // ノンブロッキングの設定
        $w_ret = socket_set_nonblock($p_socket);
        if($w_ret === false) {
            $this->logWriter('error', [__METHOD__ => LogMessageEnum::NONBLOCK_SETTING_FAIL->message($this->lang)]);
            return false;
        }

        // NEXT接続IDのカウントアップ
        $this->next_connection_id++;

        return $this->descriptors[$cid];
    }

}

