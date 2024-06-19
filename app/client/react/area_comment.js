/**
 * コメント関連コンポーネント
 * 
 * @namespace
 */
let comment =
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
                  'cmd': 'message'
                , 'user': global_data.connection.user
                , 'comment': global_data.comment.comment
            };
            global_data.websocket.send(JSON.stringify(data));
        }
    
        // ボタンエレメント生成
        let ret = React.createElement
        (
            "button",
            {
                id: "send_button",
                disabled: global_data.comment.disabled,
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
        [global_data.comment.disabled, global_func.comment.setDisabled] = global_func.useState(true);

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
            [global_data.comment.comment, global_func.comment.setComment] = global_func.useState('');

            /**
             * 変更時のイベントハンドラ
             * 
             * @param {Object} e - イベント情報
             * @returns {void} なし
             */
            function OnChange(e)
            {
                global_func.comment.setComment(e.target.value);
            }

            // コメント入力のエレメント生成
            let ret = React.createElement
            (
                "input",
                {
                    className: "comment normal-comment",
                    type: "text",
                    name: "comment",
                    disabled: global_data.comment.disabled,
                    onChange: OnChange,
                    defaultValue: global_data.comment.comment,
                    value: global_data.comment.comment,
                    placeholder: "コメント",
                    maxLength: 34
                }
            );
            return ret;
        }
    
        // 入力フォームブロックのエレメント生成
        let ret = React.createElement
        (
            "div",
            {
                className: "comment-div",
            },
            InputComment(),
            comment.Button()
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
        [global_data.comment.guide, global_func.comment.setGuide] = global_func.useState('');

        // メッセージガイドブロックのエレメント生成
        let ret = React.createElement
        (
            "p",
            {
                id: "message_reply",
                className: "comment-guide"
            },
            global_data.comment.guide
        );
        return ret;
    }
};
