# DEMO-PROJECT：フレームワークのデモ環境プロジェクト
Minecraftと連携できる軽量WebSocketサーバーのデモプロジェクトです。  
リアルタイムチャットのサンプル（ブラウザ／Minecraftクライアント対応）と、Laravel連携のサンプルを含みます。

---

## 【 概要 】
このリポジトリは、SOCKET-MANAGERフレームワーク上で動作するWebSocketサーバーのデモです。  
Minecraftと連携してチャットを行うサンプルや、ブラウザクライアント（jQuery / React）を提供し、リアルタイム通信の実装例として利用できます。Laravel連携のサンプルも用意しています。

---

## 【 特徴 】
- WebSocketベースのリアルタイムチャット（ブラウザ / Minecraft）
- Minecraftクライアントからの接続サンプル（チャット連携）
- jQuery / React のクライアントサンプルを同梱
- Laravelとの連携方法をドキュメントで提供

---

## 【 クイックスタート（サーバー起動） 】
プロジェクトルートで以下のコマンドを実行してサーバーを起動します。

```bash
php worker app:minecraft-chat-server <ポート番号>
```

例）
```bash
php worker app:minecraft-chat-server 10000
```

起動後、指定したポートでWebSocketサーバーが待ち受けます。

---

## 【 クライアントの使い方 】

### ブラウザ（jQuery / React）
同梱のクライアントHTMLをブラウザで開いて接続できます。任意のブラウザでファイルを開くか、静的ファイルサーバーに配置してアクセスしてください。

- jQuery版: /app/client/jquery/chat.html  
- React版: /app/client/react/chat.html

これらのファイルは、WebSocket経由でチャットメッセージを送受信する動作サンプルです。

### Minecraftから接続する方法
Minecraftのチャット画面から以下のコマンドを実行して接続します。

```
/wsserver <ホスト>:<ポート>/<ユーザー名>
```

例:
```
/wsserver localhost:10000/Player01
```

接続後は通常のチャットと同様にメッセージを送れます。特定ユーザーへのプライベートメッセージは以下のフォーマットで送れます。

```
<メッセージ>#<宛先ユーザー名>
```

---

## 【 Windows (UWP) のループバック許可 】
Minecraft（UWP版）を使う場合、ローカルのWebSocketサーバーにアクセスするためにループバックを許可する必要があります。管理者権限のコマンドプロンプトで以下を実行してください。

```powershell
CheckNetIsolation.exe LoopbackExempt -a -n="Microsoft.MinecraftUWP_8wekyb3d8bbwe"
```

---

## 【 補足とリンク 】
このプロジェクトには複数のサーバー例を用意しています。より詳しい使い方や追加のデモは以下を参照してください。  
- 追加デモ: https://socket-manager.github.io/document/extra-demo.html  
- Laravel連携: https://socket-manager.github.io/document/laravel.html

＜デモ画面＞  
<img src="https://socket-manager.github.io/document/img/index/demo.gif" alt="DEMO: Minecraft と WebSocket の接続例" />

---

## 【 Contact Us 】
バグ報告やご要望などは<a href="mailto:lib.tech.engineer@gmail.com">`こちら`</a>から受け付けております。

---

## 【 License 】
MIT, see <a href="https://github.com/socket-manager/demo-project/blob/main/LICENSE">LICENSE file</a>.