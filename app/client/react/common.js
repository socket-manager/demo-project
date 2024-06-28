//--------------------------------------------------
// データ型のJSDoc定義
//--------------------------------------------------

/**
 * @typedef {Object} article - 記事データ
 * @property {Object.<string, string>} datetime - 日時
 * @property {Object.<string, string>} user - ユーザー名
 * @property {Object.<string, string>} comment - コメント
 */

/**
 * @typedef {Object} article_row - 記事データ（１行分）
 * @property {Object.<string, article>} left - 記事エリア（左）
 * @property {Object.<string, article>} right - 記事エリア（右）
 */

/**
 * constデータ定義
 * 
 * @namespace
 */
const const_data =
{
    /**
     * 自身による退室
     * 
     * @type {number}
     */
    CHAT_SELF_CLOSE_CODE: 10,

    /**
     * サーバーからの切断
     * 
     * @type {number}
     */
    CHAT_SERVER_CLOSE_CODE: 20,

    /**
     * サーバーからの切断（ユーザー名重複）
     * 
     * @type {number}
     */
    CHAT_DUPLICATION_CLOSE_CODE: 30,

    /**
     * サーバーからの切断（ユーザー名なし）
     * 
     * @type {number}
     */
    CHAT_NO_USER_CLOSE_CODE: 40,

    /**
     * クライアントからの切断
     * 
     * @type {number}
     */
    CHAT_CLIENT_CLOSE_CODE: 3010,
};

/**
 * globalデータ定義
 * 
 * @namespace
 */
const global_data =
{
    /**
     * Websocketインスタンス
     * 
     * @type {Object}
     */
    websocket: null,

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
    opts:
    {
        user_list: [],
        unknown_datetime: null,
        unknown_user: null,
        admin_user: React.createElement('b', {}, '運営チーム'),
        exit_comment: null,
        server_close_comment: null,
        forced_close_comment: null,
        unexpected_close_comment: null,
        error_comment: 'エラーが発生しました',
        duplication_comment: 'そのユーザー名は既に使用されています',
        no_user_comment: 'ユーザー名を入力してください',
        no_comment: null
    },

    /**
     * 初回入室のフラグ - true（初回） or false（初回以降）
     * 
     * @type {boolean}
     */
    flg_first_entrance: true,

    /**
     * エラー発生時のフラグ - true（エラー） or false（正常）
     * 
     * @type {boolean}
     */
    flg_error: false,

    /**
     * 退出時の処理の選択 - true（closeコマンド） or false（exitコマンド）
     * 
     * @type {boolean}
     */
    flg_cycle: false,

    //--------------------------------------------------
    // フック用
    //--------------------------------------------------

    /**
     * コネクションフォーム用
     * 
     * @namespace
     */
    connection:
    {
        /**
         * URIフォームの値
         * 
         * @type {string}
         */
        uri: null,

        /**
         * ユーザー名フォームの値
         * 
         * @type {string}
         */
        user: null,

        /**
         * フォームのdisabled切り替え
         * 
         * @type {boolean}
         */
        disabled: false,

        /**
         * ボタンフラグ（0:参加する、1:退室する）
         * 
         * @type {boolean}
         */
        button_flg: 0
    },

    /**
     * チャット履歴フォーム用
     * 
     * @namespace
     */
    history:
    {
        /**
         * 記事一覧
         * 
         * @type {Array.<article_row>}
         */
        articles: [],

        /**
         * 参加人数
         * 
         * @type {number | string}
         */
        count: '--',

        /**
         * ユーザーリスト
         * 
         * @type {Array.<string>}
         */
        user_list: []
    },

    /**
     * コメント入力フォーム用
     * 
     * @namespace
     */
    comment:
    {
        /**
         * コメント
         * 
         * @type {string}
         */
        comment: null,

        /**
         * メッセージガイド
         * 
         * @type {string}
         */
        guide: null,

        /**
         * フォームのdisabled切り替え
         * 
         * @type {boolean}
         */
        disabled: false
    },

    /**
     * プライベートコメント入力フォーム用
     * 
     * @namespace
     */
    private:
    {
        /**
         * コメント
         * 
         * @type {string}
         */
        comment: null,

        /**
         * 宛先ユーザー
         * 
         * @type {string}
         */
        user: null,

        /**
         * メッセージガイド
         * 
         * @type {string}
         */
        guide: null,

        /**
         * メッセージガイドのCSSクラス名
         * 
         * @type {string}
         */
        guide_class: null,

        /**
         * フォームのdisabled切り替え
         * 
         * @type {boolean}
         */
        disabled: false
    }
};

/**
 * global関数定義
 * 
 * @namespace
 */
const global_func =
{
    /**
     * Open時のWebsocketイベントの定義
     * 
     * @param {string} uri - 入力されたURI
     * @returns {void}
     */
    setOpenWebsocket: function(uri)
    {
        /**
         * Websocket接続のインスタンス
         * 
         * @type {Object}
         */
        let websocket = new WebSocket(uri);

        /**
         * 接続完了時のイベントハンドラ
         * 
         * @param {Object} event - イベント情報
         * @returns {void}
         */
        websocket.onopen = function(event)
        {
            global_data.flg_error = false;

            let data =
            {
                  'cmd': 'entrance'
                , 'user': global_data.connection.user
            };
            global_data.websocket.send(JSON.stringify(data));
        };

        /**
         * 切断検知時のイベントハンドラ
         * 
         * @param {Object} event - イベント情報
         * @returns {void}
         */
        websocket.onclose = function(event)
        {
            console.log(`Websocket切断情報[code=${event.code} reason=${event.reason}]`);

            // エラー発生後は処理しない
            if(global_data.flg_error === true)
            {
                return;
            }

            // 記事データ初期化
            let article =
            {
                datetime: null,
                user: null,
                comment: null
            };

            // 日時エレメントの設定
            article.datetime = React.createElement
            (
                'p',
                {
                    className: 'datetime'
                },
                article.datetime
            );

            if(event.wasClean)
            {
                let data = JSON.parse(event.reason);

                // 日時を取得
                article.datetime = data.datetime;

                // 自身の退室による切断
                if(
                    event.code === const_data.CHAT_SELF_CLOSE_CODE
                ||  event.code === const_data.CHAT_CLIENT_CLOSE_CODE
                )
                {
                    //--------------------------------------------------
                    // ユーザー名のエレメント設定
                    //--------------------------------------------------

                    // 空の時のフェイルセーフ
                    article.user = React.createElement
                    (
                        'p',
                        {
                            className: 'noname'
                        },
                        'no_name'
                    );

                    // 入力されているユーザー名の設定
                    if(global_data.connection.user.length > 0)
                    {
                        article.user = React.createElement
                        (
                            'p',
                            {
                                className: 'user-self'
                            },
                            global_data.connection.user
                        );
                    }

                    //--------------------------------------------------
                    // コメントのエレメント設定
                    //--------------------------------------------------

                    article.comment = React.createElement
                    (
                        'p',
                        {
                            className: 'exit'
                        },
                        global_data.opts.exit_comment
                    );
                }
                // サーバーからの切断
                else
                if(event.code === const_data.CHAT_SERVER_CLOSE_CODE)
                {
                    // ユーザー名設定
                    article.user = global_data.opts.admin_user;

                    // コメントのエレメント設定
                    article.comment = React.createElement
                    (
                        `p`,
                        {
                            className: 'close'
                        },
                        global_data.opts.server_close_comment
                    );
                }
                // サーバーからの切断（ユーザー名重複）
                else
                if(event.code === const_data.CHAT_DUPLICATION_CLOSE_CODE)
                {
                    // ユーザー名の設定
                    article.user = global_data.opts.admin_user;

                    // コメントのエレメント設定
                    article.comment = React.createElement
                    (
                        'p',
                        {
                            className: 'close'
                        },
                        global_data.opts.duplication_comment
                    );
                }
                // サーバーからの切断（ユーザー名なし）
                else
                if(event.code === const_data.CHAT_NO_USER_CLOSE_CODE)
                {
                    // ユーザー名の設定
                    article.user = global_data.opts.admin_user;

                    // コメントのエレメント設定
                    article.comment = React.createElement
                    (
                        'p',
                        {
                            className: 'close'
                        },
                        global_data.opts.no_user_comment
                    );
                }
                // 不明
                else
                {
                    // 日時の設定
                    article.datetime = global_data.opts.unknown_datetime;

                    // ユーザー名の設定
                    article.user = global_data.opts.unknown_user;
                    
                    // コメントのエレメント設定
                    article.comment = React.createElement
                    (
                        `p`,
                        {
                            className: 'close'
                        },
                        global_data.opts.unexpected_close_comment
                    );
                }
            }
            else
            {
                // 日時の設定
                article.datetime = global_data.opts.unknown_datetime;

                // ユーザー名の設定
                article.user = global_data.opts.unknown_user;

                // コメントのエレメント設定
                article.comment = React.createElement
                (
                    `p`,
                    {
                        className: 'close'
                    },
                    global_data.opts.forced_close_comment
                );
            }

            // 記事投稿
            global_func.postComment(article, true);

            // システム初期化
            global_func.systemInit();
        };

        /**
         * エラー検知のイベント
         * 
         * @param {Object} event - イベント情報
         * @returns {void}
         */
        websocket.onerror = function(error)
        {
            global_data.flg_error = true;

            let error_message = '';
            if(typeof(error.message) !== 'undefined')
            {
                error_message = error.message;
            }
            console.log(`エラー発生[${error_message}]`);

            // コメントのエレメント生成
            let comment = React.createElement
            (
                'p',
                {
                    className: 'close'
                },
                global_data.opts.error_comment
            );

            // 記事データ設定
            let article =
            {
                datetime: null,
                user: null,
                comment: comment
            };

            // 記事投稿
            global_func.postComment(article, false);

            // システム初期化
            global_func.systemInit();
        };

        /**
         * データ受信イベント
         * 
         * @param {Object} event - イベント情報
         * @returns {void}
         */
        websocket.onmessage = function(event)
        {
            let data = JSON.parse(event.data);

            console.log('↓コマンドデータ');
            console.dir(data);

            // ユーザー数の設定
            if(typeof(data.count) !== 'undefined' && data.count !== null)
            {
                global_func.history.setCount(data.count);
            }

            /**
             * 記事データ初期設定
             * 
             * @type {article}
             */
            let article =
            {
                datetime: null,
                user: null,
                comment: null
            };

            // 日時のエレメント生成
            if(typeof(data.datetime) !== 'undefined' && data.datetime !== null)
            {
                article.datetime = React.createElement
                (
                    'p',
                    {
                        className: 'datetime'
                    },
                    data.datetime
                );
            }

            // ユーザー名がない場合のフェイルセーフ
            article.user = React.createElement
            (
                'p',
                {
                    className: 'noname'
                },
                'no name'
            );

            // ユーザー名の設定
            let flg_self = false;
            if(typeof(data.user) !== 'undefined' && data.user !== null)
            {
                if(data.user.length > 0)
                {
                    article.user = data.user;
                }

                // 自身のユーザー名であればCSSを振り直す
                if(data.user === global_data.connection.user)
                {
                    flg_self = true;
    
                    // ユーザー名のエレメント生成
                    article.user = React.createElement
                    (
                        'p',
                        {
                            className: 'user-self'
                        },
                        data.user
                    );
                }
            }

            // 入室コマンド
            if(data.cmd === 'entrance')
            {
                // ユーザー名を再設定
                if(global_data.flg_first_entrance === true)
                {
                    global_func.connection.setUser(data.user);
                    global_data.flg_first_entrance = false;
                }

                // コメントのエレメント生成
                article.comment = React.createElement
                (
                    'p',
                    {
                        className: 'entrance'
                    },
                    data.comment
                );

                // オプションデータを退避
                if(typeof(data.opts) !== 'undefined')
                {
                    global_data.opts = data.opts;
                    global_data.opts.admin_user = React.createElement('b', {}, global_data.opts.admin_user);
                }

                // 参加者一覧の反映
                global_func.history.setUserList(data.user_list);
            }
            // 退室コマンド
            else
            if(data.cmd === 'exit')
            {
                // コメントのエレメント生成
                article.comment = React.createElement
                (
                    'p',
                    {
                        className: 'exit'
                    },
                    data.comment
                );

                // 参加者一覧の反映
                global_func.history.setUserList(data.user_list);
            }
            // 切断コマンド
            else
            if(data.cmd === 'close')
            {
                // コメントのエレメント生成
                article.comment = React.createElement
                (
                    'p',
                    {
                        className: 'close'
                    },
                    data.comment
                );

                // 参加者一覧の反映
                global_func.history.setUserList(data.user_list);
            }
            // メッセージコマンド
            else
            if(data.cmd === 'message')
            {
                global_func.comment.setGuide('');
                if(data.result === false)
                {
                    global_func.comment.setGuide(data.comment);
                    return;
                }
                else
                {
                    global_func.comment.setComment('');
                }
                article.comment = data.comment;
            }
            // プライベートコメントコマンド
            else
            if(data.cmd === 'private')
            {
                // ユーザー名のエレメント生成
                article.user = React.createElement
                (
                    'p',
                    {
                        className: 'private'
                    },
                    data.user
                );

                // コメントのエレメント生成
                article.comment = React.createElement
                (
                    'p',
                    {
                        className: 'private'
                    },
                    data.comment
                );
            }
            // プライベートコメント送信結果
            else
            if(data.cmd === 'private-reply')
            {
                if(data.result === true)
                {
                    // 宛先ユーザー名のクリア
                    global_func.private.setUser('');

                    // コメントのクリア
                    global_func.private.setComment('');

                    // メッセージガイドCSSのクラス名設定
                    global_func.private.setGuideClass('private-guide-ok');
                }
                else
                {
                    // メッセージガイドCSSのクラス名設定
                    global_func.private.setGuideClass('private-guide-ng');
                }

                // ガイドメッセージの生成
                let guide = global_func.createElementWithBr(data.comment);

                // メッセージガイドの設定
                global_func.private.setGuide(guide);
                return;
            }

            // 記事投稿
            global_func.postComment(article, flg_self);
        };

        // Websocketインスタンスを設定
        global_data.websocket = websocket;
    },

    /**
     * BRタグ入りの文字列をReactエレメントで生成
     * 
     * @param {string} text - テキスト文字列
     * @returns {Array.<Object>}
     */
    createElementWithBr: function(text)
    {
        let str_ary = text.split('<br />');
        let br = React.createElement('br', {});
        let ret = [];
        str_ary.forEach(function(val, idx)
        {
            if(ret.length > 0)
            {
                ret.push(br);
            }
            let font = React.createElement('font', {}, val);
            ret.push(font);
        });
        return ret;
    },

    /**
     * システム初期化
     * 
     * @returns {void}
     */
    systemInit: function()
    {
        // 初回入室フラグの戻し
        global_data.flg_first_entrance = true;

        // 参加人数のクリア
        global_func.history.setCount('--');

        // ユーザーリストのクリア
        global_func.history.setUserList([]);

        // 「参加する」ボタンへ変更
        global_func.connection.setButtonFlg(0);

        // コメント入力フォームのガイドメッセージをクリア
        global_func.comment.setGuide('');

        // プライベートコメント入力フォームのガイドメッセージをクリア
        global_func.private.setGuide('');

        // コネクションフォームを入力可能にする
        global_func.connection.setDisabled(false);

        // コメント入力フォームを入力不可にする
        global_func.comment.setDisabled(true);

        // プライベートコメント入力フォームを入力不可にする
        global_func.private.setDisabled(true);

        // Websocketインスタンスをクリア
        global_data.websocket = null;
    },

    /**
     * 記事投稿
     * 
     * @param {article} article - 記事データ
     * @param {boolean} self - true（本人） or false（本人以外）
     * @returns {void}
     */
    postComment: function(article, flg_self)
    {
        // 記事データ１行分初期化
        let article_row =
        {
            left: {datetime: null, user: null, comment: null},
            right: {datetime: null, user: null, comment: null}
        };

        // 記事データの設定
        if(flg_self === true)
        {
            article_row.right = article;
        }
        else
        {
            article_row.left = article;
        }

        let articles = global_data.history.articles;
        global_func.history.setArticles([article_row, ...articles]);
    },

    /**
     * 現在の日時文字列を取得
     * 
     * @returns {string} 日時文字列（"Y/m/d H:i:s"形式）
     */
    getDatetimeString: function()
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
    },

    //--------------------------------------------------
    // フック用
    //--------------------------------------------------

    /**
     * フック関数
     * 
     * @type {function}
     */
    useState: React.useState,

    //--------------------------------------------------
    // フック用セッター
    //--------------------------------------------------

    /**
     * コネクションフォーム用
     * 
     * @namespace
     */
    connection:
    {
        /**
         * URIの設定
         * 
         * @param {string} state - 入力されたURI
         * @returns {void}
         */
        setUri: function(){},

        /**
         * ユーザー名の設定
         * 
         * @param {string} state - 入力されたユーザー名
         * @returns {void}
         */
        setUser: function(){},

        /**
         * disabled化の設定
         * 
         * @param {boolean} state - 非アクティブフラグ - true（非アクティブ） or false（アクティブ）
         * @returns {void}
         */
        setDisabled: function(){},

        /**
         * ボタン状態フラグの設定
         * 
         * @param {number} state - ボタン状態フラグ - 0:参加する or 1:退室する
         * @returns {void}
         */
        setButtonFlg: function(){}
    },

    /**
     * チャット履歴フォーム用
     * 
     * @namespace
     */
    history:
    {
        /**
         * チャット履歴の設定
         * 
         * @param {Array.<article_row>} state - チャット履歴
         * @returns {void}
         */
        setArticles: function(){},

        /**
         * 参加人数の設定
         * 
         * @param {number} state - 参加人数
         * @returns {void}
         */
        setCount: function(){},

        /**
         * ユーザーリストの設定
         * 
         * @param {Array.<string>} state - ユーザーリスト
         * @returns {void}
         */
        setUserList: function(){},
    },

    /**
     * コメント入力フォーム用
     * 
     * @namespace
     */
    comment:
    {
        /**
         * コメントの設定
         * 
         * @param {string} state - 入力されたコメント
         * @returns {void}
         */
        setComment: function(){},

        /**
         * メッセージガイドの設定
         * 
         * @param {string} state - ガイドメッセージ
         * @returns {void}
         */
        setGuide: function(){},

        /**
         * disabled化の設定
         * 
         * @param {boolean} state - 非アクティブフラグ - true（非アクティブ） or false（アクティブ）
         * @returns {void}
         */
        setDisabled: function(){}
    },

    /**
     * プライベートコメント入力フォーム用
     * 
     * @namespace
     */
    private:
    {
        /**
         * コメントの設定
         * 
         * @param {string} state - 入力されたコメント
         * @returns {void}
         */
        setComment: function(){},

        /**
         * 宛先ユーザーの設定
         * 
         * @param {string} state - 入力された宛先ユーザー
         * @returns {void}
         */
        setUser: function(){},

        /**
         * メッセージガイドの設定
         * 
         * @param {string} state - ガイドメッセージ
         * @returns {void}
         */
        setGuide: function(){},

        /**
         * メッセージガイドCSSのクラス名設定
         * 
         * @param {string} state - CSSのクラス名
         * @returns {void}
         */
        setGuideClass: function(){},

        /**
         * disabled化の設定
         * 
         * @param {boolean} state - 非アクティブフラグ - true（非アクティブ） or false（アクティブ）
         * @returns {void}
         */
        setDisabled: function(){}
    }
};
