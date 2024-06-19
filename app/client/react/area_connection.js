/**
 * コネクション関連コンポーネント
 * 
 * @namespace
 */
let connection =
{
    /**
     * 参加する／退室するボタン
     * 
     * @returns {Object}
     */
    Button: function()
    {
        /**
         * ボタンの表示切り替えのフック
         * 
         * @type {Array.<Object>}
         */
        [global_data.connection.button_flg, global_func.connection.setButtonFlg] = global_func.useState(0);

        /**
         * ボタン表示の文字列（0:参加する、1:退出する）
         * 
         * @type {Array}
         */
        const button_name = ['参加する', '退出する'];

        /**
         * クリック時のイベントハンドラ
         * 
         * @returns {void} なし
         */
        function ClickButton()
        {
            if(global_data.websocket === null)
            {
                // チャット履歴をクリア
                global_func.history.setArticles([]);

                // 「退出する」ボタンへ変更
                global_func.connection.setButtonFlg(1);

                // コネクションフォームを入力不可にする
                global_func.connection.setDisabled(true);

                // コメント入力フォームを入力可能にする
                global_func.comment.setDisabled(false);

                // プライベートコメント入力フォームを入力可能にする
                global_func.private.setDisabled(false);

                // Websocketを開く
                global_func.setOpenWebsocket(global_data.connection.uri);
            }
            else
            {
                if(global_data.flg_cycle === false)
                {
                    // 退出コマンドを送信
                    let data =
                    {
                        'cmd': 'exit'
                    };
                    global_data.websocket.send(JSON.stringify(data));
                    global_data.flg_cycle = true;
                }
                else
                {
                    // 切断要求を送信
                    let param =
                    {
                        'cmd': 'close',
                        'code': const_data.CHAT_CLIENT_CLOSE_CODE,
                        'datetime': global_func.getDatetimeString()
                    };
                    global_data.websocket.close(const_data.CHAT_CLIENT_CLOSE_CODE, JSON.stringify(param));
                    global_data.flg_cycle = false;
                }
            }
        }
    
        // ボタンエレメント生成
        let ret = React.createElement
        (
            "button",
            {
                id: "connect_button",
                onClick: ClickButton
            },
            button_name[global_data.connection.button_flg]
        );
        return ret;
    },
    /**
     * 入力フォーム
     * 
     * @returns {Object}
     */
    Form: function()
    {
        /**
         * disabled切り替えのフック
         * 
         * @type {Array.<Object>}
         */
        [global_data.connection.disabled, global_func.connection.setDisabled] = global_func.useState(false);

        /**
         * URI入力フィールド
         * 
         * @returns {Object}
         */
        function InputUri()
        {
            /**
             * URIのフック
             * 
             * @type {Array.<Object>}
             */
            [global_data.connection.uri, global_func.connection.setUri] = global_func.useState('ws://localhost:10000');

            /**
             * 変更時のイベントハンドラ
             * 
             * @param {Object} e - イベント情報
             * @returns {void} なし
             */
            function OnChange(e)
            {
                global_func.connection.setUri(e.target.value);
            }

            // URI入力のエレメント生成
            let ret = React.createElement
            (
                "input",
                {
                    className: "uri",
                    type: "text",
                    name: "uri",
                    disabled: global_data.connection.disabled,
                    onChange: OnChange,
                    defaultValue: global_data.connection.uri,
                    placeholder: "接続先"
                }
            );
            return ret;
        }

        /**
         * ユーザー名入力フィールド
         * 
         * @returns {Object}
         */
        function InputUser()
        {
            /**
             * ユーザー名のフック
             * 
             * @type {Array.<Object>}
             */
            [global_data.connection.user, global_func.connection.setUser] = global_func.useState('');

            /**
             * 変更時のイベントハンドラ
             * 
             * @param {Object} e - イベント情報
             * @returns {void} なし
             */
            function OnChange(e)
            {
                global_func.connection.setUser(e.target.value);
            }

            // ユーザー名入力のエレメント生成
            let ret = React.createElement
            (
                "input",
                {
                    className: "user",
                    type: "text",
                    name: "user",
                    disabled: global_data.connection.disabled,
                    onChange: OnChange,
                    defaultValue: global_data.connection.user,
                    placeholder: "ユーザー名",
                    maxLength: 8
                }
            );
            return ret;
        }
    
        // 入力フォームブロックのエレメント生成
        let ret = React.createElement
        (
            "div",
            {
                className: "user-div",
            },
            InputUri(),
            InputUser(),
            connection.Button()
        );
        return ret;
    }
};
