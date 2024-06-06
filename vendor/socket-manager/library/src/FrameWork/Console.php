<?php
/**
 * ライブラリファイル
 * 
 * コマンド実行の基底クラスのファイル
 */

namespace SocketManager\Library\FrameWork;

use Exception;


/**
 * コマンド実行の基底クラス
 * 
 * フレームワーク上のコマンド実行の基底クラス
 */
abstract class Console implements IConsole
{
    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * 以下の形式で設定
     * 
     * "<識別子> {<引数名>?} ..."
     * 
     * ※引数名の最後に"?"が付いていれば該当のパラメータ指定がない場合にnullが返される
     * 
     * @var string コマンド処理の識別子
     */
    protected string $identifer = '';

    /**
     * @var string コマンド説明
     */
    protected string $description = '';

    /**
     * @var string $adjust_identifer 調整済みの識別子
     */
    private string $adjust_identifer;

    /**
     * @var array $params 調整済みの引数リスト
     */
    private array $params = [];

    /**
     * @var ?FailureEnum $error_message エラーメッセージ
     */
    private ?FailureEnum $error_message = null;


    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     */
    public function __construct(array $p_params)
    {
        // 識別子の設定
        $w_ret = strtok($this->identifer, '{');
        $this->adjust_identifer = trim($w_ret);

        // 引数名の設定
        while(true)
        {
            $w_ret = strtok('}');
            if($w_ret === false)
            {
                break;
            }
            $w_ret = trim($w_ret);
            if($w_ret[0] === '{')
            {
                $w_ret = trim(substr($w_ret, 1));
            }
            $len = strlen($w_ret);
            $flg = false;
            if($w_ret[$len - 1] === '?')
            {
                $w_ret = trim(substr($w_ret, 0, $len - 1));
                $flg = true;
            }
            $this->params[] = [
                'name' => $w_ret,
                'null' => $flg,
                'value' => null
            ];
        }

        // "?"指定のチェック
        $flg = false;
        foreach($this->params as $param)
        {
            if($flg === true && $param['null'] === false)
            {
                $this->error_message = FailureEnum::ARGUMENT_QUESTION_FAIL;
                return;
            }

            if($param['null'] === true)
            {
                $flg = true;
            }
        }

        // 引数値の設定
        $params = array_slice($p_params, 2);
        $cnt = count($params);
        for($i = 0; $i < $cnt; $i++)
        {
            $this->params[$i]['value'] = $params[$i];
        }

        return;
    }

    /**
     * 調整済み識別子の取得
     * 
     * @return string 調整済み識別子
     */
    final public function getIdentifer(): string
    {
        return $this->adjust_identifer;
    }

    /**
     * コマンド説明の取得
     * 
     * @return string コマンド説明
     */
    final public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 引数の取得
     * 
     * @param string $p_name 引数名
     * @return string 引数の値 or null（引数なし） or false（取得失敗）
     */
    final public function getParameter(string $p_name)
    {
        foreach($this->params as $param)
        {
            if($param['name'] === $p_name)
            {
                if($param['null'] === false)
                {
                    if($param['value'] === null)
                    {
                        return false;
                    }
                }
                return $param['value'];
            }
        }
        return false;
    }

    /**
     * エラーメッセージの取得
     * 
     */
    final public function getErrorMessage(): ?FailureEnum
    {
        return $this->error_message;
    }

    /**
     * コマンド実行メソッド
     * 
     */
    abstract public function exec();
}
