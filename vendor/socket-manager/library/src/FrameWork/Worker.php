<?php
/**
 * ライブラリファイル
 * 
 * workerコマンド用クラスのファイル
 */

namespace SocketManager\Library\FrameWork;

use Exception;


/**
 * workerコマンド用クラス
 * 
 * フレームワーク上の制御コマンド
 */
class Worker
{
    //--------------------------------------------------------------------------
    // プロパティ
    //--------------------------------------------------------------------------

    /**
     * @var string $path workerコマンドのカレントディレクトリ
     */
    private string $path;

    /**
     * @var array $params コマンドライン引数
     */
    private array $params = [];

    /**
     * @var string $laravel_command Laravelコマンド名
     */
    private string $laravel_command = 'artisan';

    /**
     * @var bool $is_laravel Laravelフラグ
     */
    private bool $is_laravel = false;

    /**
     * @var array $consoles コンソール継承クラスのインスタンスリスト
     */
    private array $consoles = [];

    /**
     * @var IConsole $console コンソールクラスのインタフェース
     */
    private ?IConsole $console = null;

    /**
     * @var CraftEnum $craft_enum CraftEnumのキャスト用
     */
    private CraftEnum $craft_enum;

    /**
     * @var LaravelEnum $laravel_enum LaravelEnumのキャスト用
     */
    private LaravelEnum $laravel_enum;

    /**
     * @var SuccessEnum $success_enum SuccessEnumのキャスト用
     */
    private SuccessEnum $success_enum;

    /**
     * @var SuccessEnum $success_sub_enum SuccessEnumのキャスト用（キュー／ステータス名用）
     */
    private SuccessEnum $success_sub_enum;

    //--------------------------------------------------------------------------
    // メソッド
    //--------------------------------------------------------------------------

    /**
     * コンストラクタ
     * 
     * @param string $p_path workerコマンドのカレントディレクトリ
     * @param array $p_params コマンドライン引数
     */
    public function __construct(string $p_path, array $p_params)
    {
        $this->path = $p_path;
        $this->params = $p_params;

        if(file_exists($this->path.DIRECTORY_SEPARATOR.$this->laravel_command))
        {
            $this->is_laravel = true;
        }
    }

    /**
     * workerのメイン処理
     * 
     * @return bool true（成功） or false（失敗）
     */
    public function working(): bool
    {
        // Usage表示
        if(count($this->params) < 2)
        {
            $usage = UsageEnum::HEADER->message();

            // Laravel環境の場合
            if($this->is_laravel === true)
            {
                $usage .= UsageEnum::CRAFT->message();
                $usage .= UsageEnum::LARAVEL->message();
                $usage .= UsageEnum::SEPARATOR->message();
                printf($usage);
                require_once($this->path.'/artisan');
                return false;
            }

            // メインクラスのリストを設定
            $this->setMainClassList();

            // メインクラスのUsageを生成
            $usage .= UsageEnum::MAIN->message();
            foreach($this->consoles as $console)
            {
                // コンソールクラスの設定
                $this->console = $console;

                // 識別子判定
                $identifer = $this->console->getIdentifer();
                if(strlen($identifer) <= 0)
                {
                    continue;
                }

                // コマンド説明の取得
                $description = $this->console->getDescription();

                // Usage文字列の生成
                $w_usage = UsageEnum::MAIN_IDENTIFER->replace($identifer);
                $w_usage_len = 50 - (strlen($w_usage) - 8);
                $w_usage .= str_pad('', $w_usage_len, ' ');
                $usage .= "{$w_usage}{$description}\n";
            }
            if(count($this->consoles) <= 0)
            {
                $usage .= UsageEnum::MAIN_EMPTY->message();
            }

            // Usage表示
            $usage .= UsageEnum::CRAFT->message();
            $usage .= UsageEnum::LARAVEL->message();
            printf($usage);
            return false;
        }

        // コロンセパレータの判定
        $parts = explode(':', $this->params[1]);
        if(count($parts) !== 2)
        {
            goto laravel_check;
        }

        // コマンド判定
        $cmd_nm = null;
        $cmds = CommandEnum::cases();
        foreach($cmds as $cmd)
        {
            if($cmd->name() === $parts[0])
            {
                $cmd_nm = $cmd->name();
            }
        }
        
        // 該当するコマンドがなかった時
        if($cmd_nm === null)
        {
            goto laravel_check;
        }

        // コマンドの実行
        $w_ret = null;
        switch($cmd_nm)
        {
            // クラフトの実行
            case CommandEnum::CRAFT->value:
                $w_ret = $this->craftExecution($parts[1]);
                break;
            // Laravel操作の実行
            case CommandEnum::LARAVEL->value:
                $w_ret = $this->laravelExecution($parts[1]);
                break;
            default:
                goto laravel_check;
                break;
        }
        if($w_ret === false)
        {
            return false;
        }

        return true;

laravel_check:
        if($this->is_laravel === true)
        {
            require_once($this->path.'/artisan');
            return true;
        }

        //--------------------------------------------------------------------------
        // MainClassの実行
        //--------------------------------------------------------------------------

        $this->setMainClassList();

        foreach($this->consoles as $console)
        {
            // 識別子の一致確認
            $w_ret = $console->getIdentifer();
            if($w_ret === $this->params[1])
            {
                $this->console = $console;
            }
            $console = null;
        }

        // MainClass実行
        if($this->console !== null)
        {
            $msg = $this->console->getErrorMessage();
            if($msg !== null)
            {
                $msg->display();
                return false;
            }
            $this->console->exec();
            return true;
        }

        FailureEnum::COMMAND_FAIL->display();
        return false;
    }

    /**
     * クラフトコマンドの実行
     * 
     * @param string $p_typ クラフトタイプ
     * @param string $p_sep ディレクトリセパレータ
     * @return bool true（成功） or false（失敗）
     */
    private function craftExecution(string $p_typ, string $p_sep = DIRECTORY_SEPARATOR)
    {
        // クラス名引数の存在チェック
        if(count($this->params) < 3)
        {
            FailureEnum::NO_CLASS_NAME->display();
            return false;
        }

        // 一致するEnum値を取得
        $craft_enum = null;
        $typs = CraftEnum::cases();
        foreach($typs as $typ)
        {
            if($typ->value === $p_typ)
            {
                $craft_enum = $typ;
                break;
            }
        }
        if($craft_enum === null)
        {
            FailureEnum::COMMAND_FAIL->display();
            return false;
        }
        $this->craft_enum = $craft_enum;

        // パスの生成
        $dir = $this->craft_enum->directory();
        $full_path = $this->path.$p_sep.'app'.$p_sep.$dir;

        // ファイル存在チェック
        $create_file = $full_path.$p_sep.$this->params[2].'.php';
        if(file_exists($create_file))
        {
            FailureEnum::EXISTING_CLASS->display($this->params[2]);
            return false;
        }

        // ディレクトリ生成
        if(is_dir($full_path) === false)
        {
            mkdir($full_path);
        }

        // テンプレートのパスを取得
        $class = $this->craft_enum->class();
        $template_path = $this->path.$p_sep.'vendor'.$p_sep.'socket-manager'.$p_sep.'library'.$p_sep.'src'.$p_sep.'FrameWork'.$p_sep.'Template'.$p_sep.$dir.$p_sep;

        // ファイル作成
        $file_data = file_get_contents($template_path.$class.'.php');
        $file_data = str_replace($class, $this->params[2], $file_data);
        if($this->craft_enum->name === 'MAIN')
        {
            // 識別子の変換
            $app_name = strtolower($this->params[2][0]);
            $cnt = strlen($this->params[2]);
            for($i = 1; $i < $cnt; $i++)
            {
                if(ctype_upper($this->params[2][$i]))
                {
                    $app_name .= '-'.strtolower($this->params[2][$i]);
                }
                else
                {
                    $app_name .= $this->params[2][$i];
                }
            }
            $file_data = str_replace('template-server', $app_name, $file_data);
        }
        else
        if($this->craft_enum->name === 'PROTOCOL')
        {
            // ステータス用のEnum名を変換
            $class = $this->craft_enum->enumStatus();
            $file_data = str_replace($class, $this->params[2].'StatusEnum', $file_data);
        }
        file_put_contents($create_file, $file_data);

        // 成功メッセージ
        $success_enum = null;
        $typs = SuccessEnum::cases();
        foreach($typs as $typ)
        {
            if($typ->value === $this->craft_enum->value)
            {
                $success_enum = $typ;
                break;
            }
        }
        $this->success_enum = $success_enum;
        $this->success_enum->display($this->params[2]);

        // Enumファイル作成（キュー名）
        if($this->craft_enum->name === 'PROTOCOL' || $this->craft_enum->name === 'COMMAND')
        {
            // ファイル存在チェック
            $create_file = $full_path.$p_sep.$this->params[2].'QueueEnum.php';
            if(file_exists($create_file))
            {
                FailureEnum::EXISTING_ENUM->display($this->params[2].'QueueEnum');
                return false;
            }

            // ファイル作成
            $class = $this->craft_enum->enumQueue();
            $file_data = file_get_contents($template_path.$class.'.php');
            $file_data = str_replace($class, $this->params[2].'QueueEnum', $file_data);
            file_put_contents($create_file, $file_data);

            // 成功メッセージ
            $success_enum = null;
            $typs = SuccessEnum::cases();
            foreach($typs as $typ)
            {
                if($typ->value === $this->success_enum->value.'_queue_enum')
                {
                    $success_enum = $typ;
                    break;
                }
            }
            $this->success_sub_enum = $success_enum;
            $this->success_sub_enum->display($this->params[2].'QueueEnum');
        }

        // Enumファイル作成（ステータス名）
        if($this->craft_enum->name === 'PROTOCOL' || $this->craft_enum->name === 'COMMAND')
        {
            // ファイル存在チェック
            $create_file = $full_path.$p_sep.$this->params[2].'StatusEnum.php';
            if(file_exists($create_file))
            {
                FailureEnum::EXISTING_ENUM->display($this->params[2].'StatusEnum');
                return false;
            }

            // ファイル作成
            $class = $this->craft_enum->enumStatus();
            $file_data = file_get_contents($template_path.$class.'.php');
            $file_data = str_replace($class, $this->params[2].'StatusEnum', $file_data);
            file_put_contents($create_file, $file_data);

            // 成功メッセージ
            $success_enum = null;
            $typs = SuccessEnum::cases();
            foreach($typs as $typ)
            {
                if($typ->value === $this->success_enum->value.'_status_enum')
                {
                    $success_enum = $typ;
                    break;
                }
            }
            $this->success_sub_enum = $success_enum;
            $this->success_sub_enum->display($this->params[2].'StatusEnum');
        }
        return true;
    }

    /**
     * Laravel操作コマンドの実行
     * 
     * @param string $p_typ Laravelコマンドタイプ
     * @param string $p_sep ディレクトリセパレータ
     * @return bool true（成功） or false（失敗）
     */
    private function laravelExecution(string $p_typ, string $p_sep = DIRECTORY_SEPARATOR)
    {
        // クラス名引数の存在チェック
        if(count($this->params) < 3)
        {
            FailureEnum::NO_CLASS_NAME->display();
            return false;
        }

        // 一致するEnum値を取得
        $laravel_enum = null;
        $typs = LaravelEnum::cases();
        foreach($typs as $typ)
        {
            if($typ->value === $p_typ)
            {
                $laravel_enum = $typ;
                break;
            }
        }
        if($laravel_enum === null)
        {
            FailureEnum::COMMAND_FAIL->display();
            return false;
        }
        $this->laravel_enum = $laravel_enum;

        // 取得元ディレクトリのフルパス
        $dir = $this->laravel_enum->srcDirectory();
        $src_path = $this->path.$p_sep.'app'.$p_sep.$dir;

        // 取得元クラスの存在チェック
        $src_file = $src_path.$p_sep.$this->params[2].'.php';
        if(!file_exists($src_file))
        {
            $this->craftExecution('main');
        }

        // 出力先ディレクトリのフルパス
        $dir = $this->laravel_enum->dstDirectory();
        $dst_path = $this->path.$p_sep.'app'.$p_sep.$dir;

        // 出力先クラスの存在チェック
        $dst_file = $dst_path.$p_sep.$this->params[2].'.php';
        if(file_exists($dst_file))
        {
            FailureEnum::EXISTING_CLASS->display($this->params[2]);
            return false;
        }

        // ディレクトリ作成
        if(is_dir($dst_path) === false)
        {
            mkdir($dst_path);
        }

        // ファイル作成
        $file_data = file_get_contents($src_file);
        $file_data = preg_replace('/(namespace[ ]+)App\\\MainClass/', '$1App\\\Console\\\Commands', $file_data);
        $file_data = preg_replace('/(use[ ]+)SocketManager\\\Library\\\FrameWork\\\Console/', '$1Illuminate\\\Console\\\Command', $file_data);
        $file_data = preg_replace('/string[ ]+\$identifer/', '\$signature', $file_data);
        $file_data = preg_replace('/string[ ]+\$description/', '\$description', $file_data);
        $file_data = str_replace('$this->identifer', '$this->signature', $file_data);
        $file_data = preg_replace('/(extends[ ]+)Console/', '$1Command', $file_data);
        $file_data = str_replace('$this->getParameter', '$this->argument', $file_data);
        $file_data = str_replace('exec', 'handle', $file_data);
        file_put_contents($dst_file, $file_data);

        // 成功メッセージ
        $success_enum = null;
        $typs = SuccessEnum::cases();
        foreach($typs as $typ)
        {
            if($typ->value === $this->laravel_enum->alias())
            {
                $success_enum = $typ;
                break;
            }
        }
        $this->success_enum = $success_enum;
        $this->success_enum->display($this->params[2]);

        return true;
    }

    /**
     * メインクラスをリストへ登録
     * 
     * @param string $p_sep ディレクトリセパレータ
     */
    private function setMainClassList(string $p_sep = DIRECTORY_SEPARATOR)
    {
        // メインクラスのリストを取得
        $main_class = CraftEnum::MAIN->directory();
        $file_list = glob("{$this->path}{$p_sep}app{$p_sep}{$main_class}{$p_sep}*.php");

        // メインクラス登録のループ
        foreach($file_list as $file)
        {
            // クラス名の取得
            $pattern = "@\\{$p_sep}([^\\{$p_sep}]+).php$@";
            preg_match($pattern, $file, $matches);
            $class = "App\\{$main_class}\\{$matches[1]}";

            // インスタンスリストへ追加
            $this->consoles[] = new $class($this->params);
        }
    }
}
