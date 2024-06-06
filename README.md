# DEMO-PROJECT on SOCKET-MANAGER Framework
マインクラフトと連携できるWebsocketサーバーのデモ環境です。

<img src="https://socket-manager.github.io/document/img/index/demo.gif" />

## サーバーの起動
上記の例の場合はプロジェクトルートディレクトリで以下のコマンドを実行すればサーバーを起動できます。

<pre>
> php worker app:minecraft-chat-server <ポート番号>
</pre>

## クライアントの起動
### ブラウザの場合
以下のディレクトリにHTMLファイルが入っていますのでブラウザにドラッグ＆ドロップしてください（Webサーバーを起動する必要はありません）。

/app/client/chat.html

### マインクラフトの場合
マインクラフトのチャット画面で以下のコマンドを実行すれば接続できます。

<pre>
> /wsserver localhost:10000/<ユーザー名>
</pre>

サーバーへ接続後は普通にチャットできます。<br />
以下のフォーマットで入力すれば特定のユーザーへプライベートコメントが送信できます。

<pre>
> <メッセージ>@<宛先ユーザー名>
</pre>

※マインクラフトはUWPアプリのため以下のコマンドを実行してループバックアドレスへのアクセスを許可しておく必要があります。

<pre>
> CheckNetIsolation.exe LoopbackExempt -a -n="Microsoft.MinecraftUWP_8wekyb3d8bbwe"
</pre>

## 補足
このプロジェクトには６種類のサーバーをご用意しています。<br />
詳しい使い方は<a href="https://socket-manager.github.io/document/extra-demo.html">こちら</a>をご覧ください。

このプロジェクトはLaravelと連携できます。<br />
詳しい連携方法は<a href="https://socket-manager.github.io/document/laravel.html">こちら</a>をご覧ください。

## Contact Us

バグ報告やご要望などは<a href="mailto:lib.tech.engineer@gmail.com">`こちら`</a>から受け付けております。
