<button id="chat-toggle" class="chat-toggle" type="button" aria-expanded="false" aria-controls="chat-panel">相談</button>
<section id="chat-panel" class="chat-panel" hidden>
    <header>
        <div>
            <strong>採用アシスタント</strong>
            <p>登録資料をもとに質問できます。</p>
        </div>
        <button id="chat-close" class="chat-close" type="button" aria-label="閉じる">×</button>
    </header>
    <div id="chat-log" class="chat-log"></div>
    <form id="chat-form" class="chat-form">
        <input id="chat-input" type="text" maxlength="2000" placeholder="質問を入力" autocomplete="off">
        <button type="submit">送信</button>
    </form>
</section>
<script src="{{ asset('js/chat.js') }}"></script>
