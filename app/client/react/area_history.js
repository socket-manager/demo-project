/**
 * チャット履歴関連コンポーネント
 * 
 * @namespace
 */
let history =
{
    /**
     * 親BOX
     * 
     * @returns {Object} BOXエレメント
     */
    ParentBox: function()
    {
        /**
         * チャット履歴BOXセット
         * 
         * @returns {Object} BOXエレメント
         */
        function HistorySetBox()
        {
            /**
             * ログラベル
             * 
             * @returns {Object} ラベルエレメント
             */
            function LogLabel()
            {
                // ラベルエレメント生成
                let ret = React.createElement
                (
                    "div",
                    {
                        className: "log-label",
                    },
                    '履歴'
                );
                return ret;
            }

            /**
             * table BOX
             * 
             * @returns {Object} BOXエレメント
             */
            function TableBox()
            {
                /**
                 * tableタグ
                 * 
                 * @returns {Object} tableエレメント
                 */
                function Table()
                {
                    /**
                     * tbodyタグ
                     * 
                     * @returns {Object} tbodyエレメント
                     */
                    function Tbody()
                    {
                        /**
                         * チャット履歴のフック
                         * 
                         * @type {Array.<Object>}
                         */
                        [global_data.history.articles, global_func.history.setArticles] = global_func.useState([]);

                        /**
                         * trタグ（パーティション用）
                         * 
                         * @returns {Object} trエレメント
                         */
                        function TrPartition()
                        {
                            /**
                             * tdタグ
                             * 
                             * @returns {Object} tdエレメント
                             */
                            function Td()
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'partition',
                                        colspan: 4
                                    },
                                    null
                                );
                                return ret;
                            }

                            // trエレメント生成
                            let ret = React.createElement
                            (
                                "tr",
                                {
                                },
                                Td()
                            );
                            return ret;
                        }

                        /**
                         * trタグ（ヘッダ部）
                         * 
                         * @param {article_row} row - 記事データ１行分
                         * @returns {Object} trエレメント
                         */
                        function TrHeading(row)
                        {
                            /**
                             * tdタグ（日時：左側）
                             * 
                             * @param {string} val - 日時
                             * @returns {Object} tdエレメント
                             */
                            function TdDatetimeLeft(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'datetime-left'
                                    },
                                    val
                                );
                                return ret;
                            }
                            /**
                             * tdタグ（ユーザー名：左側）
                             * 
                             * @param {string} val - ユーザー名
                             * @returns {Object} tdエレメント
                             */
                            function TdUserLeft(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'user-left'
                                    },
                                    val
                                );
                                return ret;
                            }

                            /**
                             * tdタグ（日時：右側）
                             * 
                             * @param {string} val - 日時
                             * @returns {Object} tdエレメント
                             */
                            function TdDatetimeRight(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'datetime-right'
                                    },
                                    val
                                );
                                return ret;
                            }
                            /**
                             * tdタグ（ユーザー名：右側）
                             * 
                             * @param {string} val - ユーザー名
                             * @returns {Object} tdエレメント
                             */
                            function TdUserRight(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'user-right'
                                    },
                                    val
                                );
                                return ret;
                            }

                            // trエレメント生成
                            let ret = React.createElement
                            (
                                "tr",
                                {
                                    className: 'heading'
                                },
                                TdDatetimeLeft(row.left.datetime),
                                TdUserLeft(row.left.user),
                                TdDatetimeRight(row.right.datetime),
                                TdUserRight(row.right.user)
                            );
                            return ret;
                        }

                        /**
                         * trタグ（コメント部）
                         * 
                         * @param {article_row} row - 記事データ１行分
                         * @returns {Object} trエレメント
                         */
                        function TrComment(row)
                        {
                            /**
                             * tdタグ（コメント：左側）
                             * 
                             * @param {string} val - コメント
                             * @returns {Object} tdエレメント
                             */
                            function TdLeft(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'comment-left',
                                        colspan: 2
                                    },
                                    val
                                );
                                return ret;
                            }

                            /**
                             * tdタグ（コメント：右側）
                             * 
                             * @param {string} val - コメント
                             * @returns {Object} tdエレメント
                             */
                            function TdRight(val)
                            {
                                // tdエレメント生成
                                let ret = React.createElement
                                (
                                    "td",
                                    {
                                        className: 'comment-right',
                                        colspan: 2
                                    },
                                    val
                                );
                                return ret;
                            }

                            // trエレメント生成
                            let ret = React.createElement
                            (
                                "tr",
                                {
                                },
                                TdLeft(row.left.comment),
                                TdRight(row.right.comment)
                            );
                            return ret;
                        }

                        let articles = [];
                        global_data.history.articles.forEach(function(val, idx)
                        {
                            articles.push(TrPartition());
                            articles.push(TrHeading(val));
                            articles.push(TrComment(val));
                        })

                        // tbodyエレメント生成
                        let ret = React.createElement
                        (
                            "tbody",
                            {
                                id: 'history'
                            },
                            articles
                        );
                        return ret;
                    }

                    // tableエレメント生成
                    let ret = React.createElement
                    (
                        "table",
                        {
                        },
                        Tbody()
                    );
                    return ret;
                }

                // BOXエレメント生成
                let ret = React.createElement
                (
                    "div",
                    {
                        className: "table-box",
                    },
                    Table()
                );
                return ret;
            }

            // BOXエレメント生成
            let ret = React.createElement
            (
                "div",
                {
                    className: "history-set-box",
                },
                LogLabel(),
                TableBox()
            );
            return ret;
        }

        /**
         * ユーザーリストBOXセット
         * 
         * @returns {Object} BOXエレメント
         */
        function UserSetBox()
        {
            /**
             * 参加人数見出し
             * 
             * @returns {Object} DIVエレメント
             */
            function CountDiv()
            {
                /**
                 * 人数ラベル
                 * 
                 * @returns {Object} DIVエレメント
                 */
                function CountLabel()
                {
                    // DIVエレメント生成
                    let ret = React.createElement
                    (
                        "div",
                        {
                            className: "count-label",
                        },
                        '人数：'
                    );
                    return ret;
                }

                /**
                 * 人数表示
                 * 
                 * @returns {Object} DIVエレメント
                 */
                function CountUser()
                {
                    /**
                     * 参加人数のフック
                     * 
                     * @type {Array.<Object>}
                     */
                    [global_data.history.count, global_func.history.setCount] = global_func.useState('--');

                    // DIVエレメント生成
                    let ret = React.createElement
                    (
                        "div",
                        {
                            className: "count-user",
                        },
                        global_data.history.count
                    );
                    return ret;
                }

                // DIVエレメント生成
                let ret = React.createElement
                (
                    "div",
                    {
                        className: "count-div",
                    },
                    CountLabel(),
                    CountUser()
                );
                return ret;
            }

            /**
             * 参加ユーザーリストBOX
             * 
             * @returns {Object} BOXエレメント
             */
            function UserBox()
            {
                /**
                 * ユーザーリストのフック
                 * 
                 * @type {Array.<Object>}
                 */
                [global_data.history.user_list, global_func.history.setUserList] = global_func.useState([]);

                function UserEntry(user, css)
                {
                    // DIVエレメント生成
                    let ret = React.createElement
                    (
                        "div",
                        {
                            className: "list-user" + css,
                        },
                        user
                    );
                    return ret;
                }

                // ユーザーリストの中身を生成
                let list = [];
                global_data.history.user_list.forEach(function(val, idx)
                {
                    let css = '';
            
                    // 本人か
                    if(val === global_data.connection.user)
                    {
                        css = ' list-user-self';
                    }

                    list.push(UserEntry(val, css));
                });

                // DIVエレメント生成
                let ret = React.createElement
                (
                    "div",
                    {
                        className: "user-box",
                    },
                    list
                );
                return ret;
            }

            // BOXエレメント生成
            let ret = React.createElement
            (
                "div",
                {
                    className: "user-set-box",
                },
                CountDiv(),
                UserBox()
            );
            return ret;
        }

        // BOXエレメント生成
        let ret = React.createElement
        (
            "div",
            {
                className: "parent-box",
            },
            HistorySetBox(),
            UserSetBox()
        );
        return ret;
    }
};
