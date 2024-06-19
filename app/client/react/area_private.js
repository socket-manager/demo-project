/**
 * プライベートコメント関連コンポーネント
 * 
 * @namespace
 */
let private_comment =
{
    /**
     * ポチるボタン
     * 
     * @returns {Object}
     */
    Button: function()
    {
        /**
         * クリック時のイベントハンドラ
         * 
         * @returns {void} なし
         */
        function ClickButton()
        {
            let data =
            {
                  'cmd': 'private'
                , 'user': global_data.private.user
                , 'comment': global_data.private.comment
            };
            global_data.websocket.send(JSON.stringify(data));
            global_func.private.setGuide('');
        }
    
        // ボタンエレメント生成
        let ret = React.createElement
        (
            "button",
            {
                id: "private_send_button",
                disabled: global_data.private.disabled,
                onClick: ClickButton
            },
            'ポチる'
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
        [global_data.private.disabled, global_func.private.setDisabled] = global_func.useState(true);

        /**
         * コメント入力フィールド
         * 
         * @returns {Object}
         */
        function InputComment()
        {
            /**
             * コメントのフック
             * 
             * @type {Array.<Object>}
             */
            [global_data.private.comment, global_func.private.setComment] = global_func.useState('');

            /**
             * 変更時のイベントハンドラ
             * 
             * @param {Object} e - イベント情報
             * @returns {void} なし
             */
            function OnChange(e)
            {
                global_func.private.setComment(e.target.value);
            }

            // コメント入力のエレメント生成
            let ret = React.createElement
            (
                "input",
                {
                    className: "comment private-comment",
                    type: "text",
                    name: "private-comment",
                    disabled: global_data.private.disabled,
                    onChange: OnChange,
                    defaultValue: global_data.private.comment,
                    value: global_data.private.comment,
                    placeholder: "プライベートコメント",
                    maxLength: 34
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
             * 宛先ユーザーのフック
             * 
             * @type {Array.<Object>}
             */
            [global_data.private.user, global_func.private.setUser] = global_func.useState('');

            /**
             * 変更時のイベントハンドラ
             * 
             * @param {Object} e - イベント情報
             * @returns {void} なし
             */
            function OnChange(e)
            {
                global_func.private.setUser(e.target.value);
            }

            // コメント入力のエレメント生成
            let ret = React.createElement
            (
                "input",
                {
                    className: "comment private-user",
                    type: "text",
                    name: "private-user",
                    disabled: global_data.private.disabled,
                    onChange: OnChange,
                    defaultValue: global_data.private.user,
                    value: global_data.private.user,
                    placeholder: "宛先",
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
                className: "private-div",
            },
            InputComment(),
            InputUser(),
            private_comment.Button()
        );
        return ret;
    },
    /**
     * メッセージガイド
     * 
     * @returns {Object}
     */
    Guide: function()
    {
        /**
         * メッセージガイドのフック
         * 
         * @type {Array.<Object>}
         */
        [global_data.private.guide, global_func.private.setGuide] = global_func.useState('');

        /**
         * メッセージガイドCSSのクラス名フック
         * 
         * @type {Array.<Object>}
         */
        [global_data.private.guide_class, global_func.private.setGuideClass] = global_func.useState('');

        // メッセージガイドブロックのエレメント生成
        let ret = React.createElement
        (
            "p",
            {
                id: "private_reply",
                className: global_data.private.guide_class
            },
            global_data.private.guide
        );
        return ret;
    }
};
