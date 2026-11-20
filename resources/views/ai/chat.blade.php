<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Assistant - Apotek</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

       body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            background: #0077b6;
            color: white;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        .back-button {
            color: white;
            font-size: 20px;
            text-decoration: none;
        }

        .header-info h2 {
            margin: 0;
            font-size: 18px;
        }

        .header-info p {
            margin: 4px 0 0;
            font-size: 12px;
        }

       .chat-container {
            width: calc(100% - 30px);
            max-width: 900px;
            flex: 1;
            min-height: 0;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
            overflow: hidden;
        }

       .chat-box {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 0 0 15px;
            background: transparent;
        }

        .message {
            margin-bottom: 18px;
            display: flex;
        }

        .bot-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: #0077b6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 17px;
        }

        .message.user {
            justify-content: flex-end;
        }

       
        .bubble {
            display: inline-block;
            width: fit-content;
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            text-align: left;
        }

        .bot .bubble {
            background: #e0f2fe;
            color: #164e63;
            text-align: left;
        }

        .user .bubble {
            background: #0077b6;
            color: white;
            text-align: left;
        }


        .chat-form {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            flex-shrink: 0;
            border-top: 1px solid #e2e8f0;
            background: white;
        }

        .chat-form input {
            flex: 1;
            min-width: 0;
            padding: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            text-align: center;
        }

        .chat-form input::placeholder {
            text-align: center;
        }

        .chat-form button {
            border: none;
            background: #0077b6;
            color: white;
            padding: 0 22px;
            border-radius: 10px;
            cursor: pointer;
        }

        .chat-form button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        @media (max-width: 600px) {

        
        .chat-container {
            width: calc(100% - 20px);
            flex: 1;
            min-height: 0;
            margin: 0 auto;
            padding: 12px;
            border-radius: 12px;
        }

        .chat-box {
            min-height: 0;
            padding: 0 0 12px;
        }

        .bubble {
            max-width: 90%;
        }

        .chat-form button {
            padding: 0 16px;
        }

    }

    .ai-stream-cursor {
        display: inline-block;
        width: 6px;
        height: 1em;
        background: #0077b6;
        margin-left: 3px;
        vertical-align: text-bottom;
        border-radius: 2px;
        animation: aiCursorBlink 0.7s infinite;
    }

    @keyframes aiCursorBlink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    </style>
</head>
<body>

    <header class="header">
        <a href="#" class="back-button" onclick="goBack(event)">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="header-info">
            <h2>AI Assistant Apotek</h2>
            <p>Asisten informasi kesehatan</p>
        </div>
    </header>

        <main class="chat-container">

       <!-- AREA PERCAKAPAN -->
        <div class="chat-box" id="chatBox">
        </div>

        <!-- FORM KIRIM PESAN -->
        <form class="chat-form" id="chatForm">

            <input
                type="text"
                id="messageInput"
                placeholder="Tulis pesan..."
                maxlength="2000"
                required
                autocomplete="off"
            >

            <button type="submit" id="sendButton">
                <i class="fas fa-paper-plane"></i>
            </button>

        </form>

    </main>

    <script>
        function goBack(event) {
            event.preventDefault();

            if (document.referrer &&
                new URL(document.referrer).origin === window.location.origin) {
                window.history.back();
            } else {
                window.location.href = "{{ auth()->user()->role === 'pemilik' ? route('pemilik.dashboard') : route('admin.dashboard') }}";
            }
        }

        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const chatBox = document.getElementById('chatBox');
        const sendButton = document.getElementById('sendButton');

        function formatMarkdown(text) {
            if (!text) return '';
            let s = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
            s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            s = s.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
            s = s.replace(/\n/g, '<br>');
            return s;
        }

       function addMessage(message, sender) {
            const wrapper = document.createElement('div');
            wrapper.className = 'message ' + sender;

            if (sender === 'bot') {
                const icon = document.createElement('div');
                icon.className = 'bot-icon';
                icon.innerHTML = '<i class="fas fa-robot"></i>';
                wrapper.appendChild(icon);
            }

            const bubble = document.createElement('div');
            bubble.className = 'bubble';
            if (sender === 'bot') {
                bubble.innerHTML = formatMarkdown(message);
            } else {
                bubble.textContent = message;
            }

            wrapper.appendChild(bubble);
            chatBox.appendChild(wrapper);

            chatBox.scrollTop = chatBox.scrollHeight;

            return bubble;
        }

        async function loadHistory() {
            try {
                const response = await fetch("{{ route('ai.history') }}", {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Gagal mengambil riwayat chat.');
                }

                const data = await response.json();

                chatBox.innerHTML = '';

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        addMessage(
                            message.content,
                            message.role === 'user' ? 'user' : 'bot'
                        );
                    });
                } else {
                    addMessage(
                        'Halo! Saya AI Assistant Apotek. Ada yang bisa saya bantu?',
                        'bot'
                    );
                }

            } catch (error) {
                console.error(error);
                addMessage('Maaf, riwayat percakapan gagal dimuat.', 'bot');
            }
        }

        let currentSessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);

        function streamReplyToBubble(bubble, fullText, scrollEl) {
            return new Promise((resolve) => {
                if (!fullText) {
                    bubble.innerHTML = '';
                    resolve();
                    return;
                }

                const tokens = fullText.split(/(\s+)/);
                let currentIndex = 0;
                let accumulated = '';
                const totalTokens = tokens.length;
                const step = totalTokens > 160 ? 3 : (totalTokens > 80 ? 2 : 1);
                const intervalMs = 22;

                function tick() {
                    if (currentIndex >= totalTokens) {
                        bubble.innerHTML = formatMarkdown(fullText);
                        if (scrollEl) scrollEl.scrollTop = scrollEl.scrollHeight;
                        resolve();
                        return;
                    }

                    const end = Math.min(currentIndex + step, totalTokens);
                    for (let i = currentIndex; i < end; i++) {
                        accumulated += tokens[i];
                    }
                    currentIndex = end;

                    let preview = accumulated;
                    const boldMatches = preview.match(/\*\*/g);
                    if (boldMatches && boldMatches.length % 2 !== 0) {
                        preview += '**';
                    }

                    bubble.innerHTML = formatMarkdown(preview) + '<span class="ai-stream-cursor"></span>';
                    if (scrollEl) {
                        scrollEl.scrollTop = scrollEl.scrollHeight;
                    }

                    setTimeout(tick, intervalMs);
                }

                tick();
            });
        }

        loadHistory();

        chatForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const message = messageInput.value.trim();

            if (!message) return;

            addMessage(message, 'user');

            messageInput.value = '';
            sendButton.disabled = true;

            const loadingBubble = addMessage('AI sedang berpikir...', 'bot');

            try {
                const response = await fetch("{{ route('ai.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: currentSessionId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Terjadi kesalahan.');
                }

                if (data.session_id) {
                    currentSessionId = data.session_id;
                }

                const replyText = data.reply || 'AI tidak memberikan jawaban.';
                await streamReplyToBubble(loadingBubble, replyText, chatBox);
            } catch (error) {
                loadingBubble.textContent = error.message || 'Gagal menghubungi AI.';
            } finally {
                sendButton.disabled = false;
                messageInput.focus();
            }
        });
    </script>

</body>
</html>