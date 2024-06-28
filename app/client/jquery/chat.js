$(window).on('load', function()
{
});

$(function()
{
    //--------------------------------------------------------------------------
    // キャッシュ対応
    //--------------------------------------------------------------------------

    $('script').each(function(index, element) {
        const src = $(element).attr('src');
        $(element).attr('src', src + '?' + new Date().getTime());
    });

    $('link').each(function(index, element) {
        const src = $(element).attr('href');
        $(element).attr('href', src + '?' + new Date().getTime());
    });


    //--------------------------------------------------------------------------
    // 定数定義
    //--------------------------------------------------------------------------

    /**
     * 自身による退室
     * 
     * @type {number}
     */
    const CHAT_SELF_CLOSE_CODE = 10;

    /**
     * サーバーからの切断
     * 
     * @type {number}
     */
    const CHAT_SERVER_CLOSE_CODE = 20;

    /**
     * サーバーからの切断（ユーザー名重複）
     * 
     * @type {number}
     */
    const CHAT_DUPLICATION_CLOSE_CODE = 30;

    /**
     * サーバーからの切断（ユーザー名なし）
     * 
     * @type {number}
     */
    const CHAT_NO_USER_CLOSE_CODE = 40;

    /**
     * クライアントからの切断
     * 
     * @type {number}
     */
    const CHAT_CLIENT_CLOSE_CODE = 3010;


    //--------------------------------------------------------------------------
    // 変数の初期設定
    //--------------------------------------------------------------------------

    /**
     * Websocketのインスタンス
     * 
     * @type {Object}
     */
    let websocket = null;

    /**
     * @typedef {Object} opts - オプションデータ（入室時にサーバーから取得）
     * @property {Array.<string>} user_list - ユーザー名のリスト
     * @property {string} unknown_datetime - 不明な日付文字列
     * @property {string} unknown_user - 不明なユーザー名
     * @property {string} admin_user - 運営サイドのユーザー名
     * @property {string} exit_comment - 退室コメント
     * @property {string} server_close_comment - サーバーからの切断コメント
     * @property {string} forced_close_comment - 強制切断コメント
     * @property {string} unexpected_close_comment - 予期しない切断コメント
     * @property {string} error_comment - エラーコメント
     * @property {string} duplication_comment - ユーザー名重複時のコメント
     * @property {string} no_user_comment - ユーザー名なし時のコメント
     * @property {string} no_comment - コメントなし時のコメント
     */
    /** @type {opts} */
    let opts =
    {
        user_list: [],
        unknown_datetime: null,
        unknown_user: null,
        admin_user: '<b>運営チーム</b>',
        exit_comment: null,
        server_close_comment: null,
        forced_close_comment: null,
        unexpected_close_comment: null,
        error_comment: 'エラーが発生しました',
        duplication_comment: 'そのユーザー名は既に使用されています',
        no_user_comment: 'ユーザー名を入力してください',
        no_comment: null
    };

    /**
     * 初回入室のフラグ
     * 
     * @type {boolean}
     */
    let flg_first_entrance = true;

    /**
     * エラー発生時のフラグ
     * 
     * @type {boolean}
     */
    let flg_error = false;

    /**
     * 周期フラグ
     * 
     * @type {boolean}
     */
    let flg_cycle = false;


    //--------------------------------------------------------------------------
    // DOMの初期設定
    //--------------------------------------------------------------------------

    // コメント入力欄を禁止
    $('input[name="comment"]').prop('disabled', true);
    $('#send_button').prop('disabled', true);

    // プライベートコメント入力欄を禁止
    $('input[name="private-comment"]').prop('disabled', true);
    $('input[name="private-user"]').prop('disabled', true);
    $('#private_send_button').prop('disabled', true);


    //--------------------------------------------------------------------------
    // イベント定義
    //--------------------------------------------------------------------------

    // 参加する／退出するボタン
    $(document).on('click', '#connect_button', function()
    {
        if(websocket === null)
        {
            // 履歴を消しておく  
            $('#history').html('');

            // ボタン名変更
            $('#connect_button').text('退出する');

            // URI入力を禁止
            $('input[name="uri"]').prop('disabled', true);

            // ユーザー名入力を禁止
            $('input[name="user"]').prop('disabled', true);

            // コメント入力欄を許可
            $('input[name="comment"]').prop('disabled', false);
            $('#send_button').prop('disabled', false);

            // プライベートコメント入力欄を許可
            $('input[name="private-comment"]').prop('disabled', false);
            $('input[name="private-user"]').prop('disabled', false);
            $('#private_send_button').prop('disabled', false);

            // Websocketを開く
            setOpenWebsocket();
        }
        else
        {
            if(flg_cycle === false)
            {
                // 退出コマンドを送信
                let data =
                {
                    'cmd': 'exit'
                };
                websocket.send(JSON.stringify(data));
                flg_cycle = true;
            }
            else
            {
                // 切断要求を送信
                let param =
                {
                    'cmd': 'close',
                    'code': CHAT_CLIENT_CLOSE_CODE,
                    'datetime': getDatetimeString()
                };
                websocket.close(CHAT_CLIENT_CLOSE_CODE, JSON.stringify(param));
                flg_cycle = false;
            }
        }
    });

    // ポチるボタン
    $(document).on('click', '#send_button', function()
    {
        let data =
        {
              'cmd': 'message'
            , 'user': $('input[name="user"]').val()
            , 'comment': $('input[name="comment"]').val()
        };
        websocket.send(JSON.stringify(data));
    });

    // ポチるボタン（プライベート用）
    $(document).on('click', '#private_send_button', function()
    {
        let data =
        {
              'cmd': 'private'
            , 'user': $('input[name="private-user"]').val()
            , 'comment': $('input[name="private-comment"]').val()
        };
        websocket.send(JSON.stringify(data));
        $('#private_reply').text('');
    });


    //--------------------------------------------------------------------------
    // 関数定義
    //--------------------------------------------------------------------------

    /**
     * Websocketイベントの定義
     * 
     * @returns {void}
     */
    function setOpenWebsocket()
    {
        let uri = $('input[name="uri"]').val();

        // Websocket接続
        websocket = new WebSocket(uri);

        /**
         * 接続完了イベント
         * 
         * @param {*} event イベントインスタンス
         * @returns 
         */
        websocket.onopen = function(event)
        {
            flg_error = false;

            let data =
            {
                  'cmd': 'entrance'
                , 'user': $('input[name="user"]').val()
            };
            websocket.send(JSON.stringify(data));
        };
    
        /**
         * データ受信イベント
         * 
         * @param {*} event イベントインスタンス
         * @returns 
         */
        websocket.onmessage = function(event)
        {
            let data = JSON.parse(event.data);

            console.log('↓コマンドデータ');
            console.dir(data);

            // ユーザー数の設定
            if(data.count !== null)
            {
                $('#count-user').text(data.count);
            }

            // ユーザー名の設定
            let user = '<p class="noname">no name</p>';
            if(data.user.length > 0)
            {
                user = data.user;
            }

            // コメント変数の初期化
            let comment = null;

            // 入室コマンド
            if(data.cmd === 'entrance')
            {
                // ユーザー名を再設定
                if(flg_first_entrance === true)
                {
                    $('input[name="user"]').val(data.user);
                    flg_first_entrance = false;
                }

                // コメントの設定
                comment = `<p class="entrance">${data.comment}</p>`;

                if(typeof(data.opts) !== 'undefined')
                {
                    // オプションデータを退避
                    opts = data.opts;
                    opts.admin_user = `<b>${opts.admin_user}</b>`
                }

                // 参加者一覧の反映
                setUserList(data.user_list);
            }
            // メッセージコマンド
            else
            if(data.cmd === 'message')
            {
                $('#message_reply').text('');
                if(data.result === false)
                {
                    $('#message_reply').text(data.comment);
                    return;
                }
                else
                {
                    $('input[name="comment"]').val('');
                }
                comment = data.comment;
            }
            // 退室コマンド
            else
            if(data.cmd === 'exit')
            {
                comment = `<p class="exit">${data.comment}</p>`;

                // 参加者一覧の反映
                setUserList(data.user_list);
            }
            // 切断コマンド
            else
            if(data.cmd === 'close')
            {
                comment = `<p class="close">${data.comment}</p>`;

                // 参加者一覧の反映
                setUserList(data.user_list);
            }
            // プライベートコメントコマンド
            else
            if(data.cmd === 'private')
            {
                user = `<p class="private">${data.user}</p>`
                comment = `<p class="private">${data.comment}</p>`;
            }
            // プライベートコメント送信結果
            else
            if(data.cmd === 'private-reply')
            {
                if(data.result === true)
                {
                    $('input[name="private-user"]').val('');
                    $('input[name="private-comment"]').val('');
                    $('#private_reply').removeClass('private-guide-ng');
                    $('#private_reply').addClass('private-guide-ok');
                }
                else
                {
                    $('#private_reply').removeClass('private-guide-ok');
                    $('#private_reply').addClass('private-guide-ng');
                }
                $('#private_reply').html(data.comment);
                return;
            }

            let flg_self = false;

            // 自身のユーザー名であればCSSを振り直す
            let user_inp = $('input[name="user"]').val();
            if(data.user === user_inp)
            {
                user = `<p class="user-self">${data.user}</p>`;
                flg_self = true;
            }

            // 日時の設定
            let datetime = `<p class="datetime">${data.datetime}</p>`;

            // コメント履歴へ投稿
            postComment(datetime, user, comment, flg_self);
        };

        /**
         * 切断検知のイベント
         * 
         * @param {*} event イベントインスタンス
         * @returns 
         */
        websocket.onclose = function(event)
        {
            console.log(`Websocket切断情報[code=${event.code} reason=${event.reason}]`);

            if(flg_error === true)
            {
                return;
            }

            // 変数の初期化
            let user = null;
            let comment = null;
            let datetime = null;

            if(event.wasClean)
            {
                let data = JSON.parse(event.reason);

                // 変数の初期化
                datetime = data.datetime;

                // 自身の退室による切断
                if(
                    event.code === CHAT_SELF_CLOSE_CODE
                ||  event.code === CHAT_CLIENT_CLOSE_CODE
                )
                {
                    // ユーザー名の設定
                    user = '<p class="noname">no name</p>';
                    let input_user = $('input[name="user"]').val();
                    if(input_user.length > 0)
                    {
                        user = `<p class="user-self">${input_user}</p>`;
                    }
                    comment = `<p class="exit">${opts.exit_comment}</p>`;
                }
                // サーバーからの切断
                else
                if(event.code === CHAT_SERVER_CLOSE_CODE)
                {
                    user = opts.admin_user;
                    comment = `<p class="close">${opts.server_close_comment}</p>`;
                }
                // サーバーからの切断（ユーザー名重複）
                else
                if(event.code === CHAT_DUPLICATION_CLOSE_CODE)
                {
                    user = opts.admin_user;
                    comment = `<p class="close">${opts.duplication_comment}</p>`;
                }
                // サーバーからの切断（ユーザー名なし）
                else
                if(event.code === CHAT_NO_USER_CLOSE_CODE)
                {
                    user = opts.admin_user;
                    comment = `<p class="close">${opts.no_user_comment}</p>`;
                }
                // 不明
                else
                {
                    datetime = opts.unknown_datetime;
                    user = opts.unknown_user;
                    comment = `<p class="close">${opts.unexpected_close_comment}</p>`;
                }
            }
            else
            {
                console.dir(event);
                datetime = opts.unknown_datetime;
                user = opts.unknown_user;
                comment = `<p class="close">${opts.forced_close_comment}</p>`;
            }

            // 日時の設定
            datetime = `<p class="datetime">${datetime}</p>`;

            // コメント履歴へ投稿
            postComment(datetime, user, comment, true);

            // システム初期化
            systemInit();
        };
    
        /**
         * エラー検知のイベント
         * 
         * @param {*} error エラーインスタンス
         */
        websocket.onerror = function(error)
        {
            flg_error = true;

            let error_message = '';
            if(typeof(error.message) !== 'undefined')
            {
                error_message = error.message;
            }
            console.log(`エラー発生[${error_message}]`);

            let comment = `<p class="close">${opts.error_comment}</p>`;

            // 日時の設定
            datetime = null;

            // ユーザー名の設定
            user = null;

            // コメント履歴へ投稿
            postComment(datetime, user, comment, false);

            // システム初期化
            systemInit();
        };
    }

    /**
     * コメント履歴へ投稿
     * 
     * @param {string} datetime - 日時
     * @param {string} user - ユーザー
     * @param {string} comment - コメント
     * @param {boolean} self - 自身の記事フラグ
     * @returns {void}
     */
    function postComment(datetime, user, comment, self)
    {
        let direction_self = 'left';
        let direction_other = 'right';
        if(self === true)
        {
            direction_self = 'right';
            direction_other = 'left';
        }

        // テンプレートへ値を設定してアペンド
        $(`#template .datetime-${direction_self}`).html(datetime);
        $(`#template .user-${direction_self}`).html(user);
        $(`#template .comment-${direction_self}`).html(comment);
        $(`#template .datetime-${direction_other}`).html('');
        $(`#template .user-${direction_other}`).html('');
        $(`#template .comment-${direction_other}`).html('');
        let html = $('#template').html();
        $('#history').prepend(html);
    }

    /**
     * 参加者一覧の反映
     * 
     * @param {Array.<string>} list 参加者一覧リストデータ
     * @returns {void}
     */
    function setUserList(list)
    {
        $('.user-box').html('');

        let user = $('input[name="user"]').val();
        let css = null;

        let len = list.length;
        for(let i = 0; i < len; i++)
        {
            css = '';
            if(list[i] === user)
            {
                css = ' list-user-self';
            }
            $('.user-box').append(`<div class="list-user${css}">${list[i]}</div>`);
        }
    }

    /**
     * 現在の日時文字列を取得
     * 
     * @returns {string} 日時文字列（"Y/m/d H:i:s"形式）
     */
    function getDatetimeString()
    {
        let ins = new Date();
        let y = ins.getFullYear();
        y = y.toString().padStart(4, '0');
        let m = ins.getMonth() + 1;
        m = m.toString().padStart(2, '0');
        let d = ins.getDate();
        d = d.toString().padStart(2, '0');
        let h = ins.getHours();
        h = h.toString().padStart(2, '0');
        let i = ins.getMinutes();
        i = i.toString().padStart(2, '0');
        let s = ins.getSeconds();
        s = s.toString().padStart(2, '0');

        return `${y}/${m}/${d} ${h}:${i}:${s}`;
    }

    /**
     * システム初期化
     * 
     * @returns {void}
     */
    function systemInit()
    {
        // 参加人数のクリア
        $('#count-user').text('--');

        // ユーザーリストのクリア
        $('.user-box').html('');

        // 「参加する」ボタンへ変更
        $('#connect_button').text('参加する');

        // コメント入力フォームのガイドメッセージをクリア
        $('#message_reply').text('');

        // プライベートコメント入力フォームのガイドメッセージをクリア
        $('#private_reply').text('');

        // ユーザー名入力を許可
        $('input[name="user"]').prop('disabled', false);

        // URI入力を許可
        $('input[name="uri"]').prop('disabled', false);

        // コメント入力欄を禁止
        $('input[name="comment"]').prop('disabled', true);
        $('#send_button').prop('disabled', true);

        // プライベートコメント入力欄を禁止
        $('input[name="private-comment"]').prop('disabled', true);
        $('input[name="private-user"]').prop('disabled', true);
        $('#private_send_button').prop('disabled', true);

        // 初回入室フラグの戻し
        flg_first_entrance = true;

        // Websocketインスタンスをクリア
        websocket = null;
    }
});
