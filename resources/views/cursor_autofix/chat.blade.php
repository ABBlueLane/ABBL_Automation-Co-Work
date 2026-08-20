@extends('api_clients.layout')

@section('title', 'ทดสอบ Cursor CLI')

@section('content')
    <div class="topbar">
        <div>
            <h1>ทดสอบ Cursor CLI</h1>
            <p>ทดสอบการเชื่อมต่อ และแชทกับ Cursor Agent เพื่อตรวจว่า CLI พร้อมใช้งาน</p>
        </div>
        <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
            <a class="button secondary" href="{{ route('cursor_autofix.index') }}">กลับรายการ Autofix</a>
            <form method="post" action="{{ route('cursor_autofix.chat.clear') }}">
                @csrf
                <button type="submit" class="button secondary">ล้างแชท</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <div class="panel" style="margin-bottom: 1rem;">
        <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between;">
            <div>
                <div><strong>Binary:</strong> <code>{{ $binary }}</code>
                    @if ($binaryFound)
                        <span class="badge active">พบแล้ว</span>
                    @else
                        <span class="badge inactive">ไม่พบ</span>
                    @endif
                </div>
                <div style="margin-top:.35rem;">
                    <strong>API Key:</strong>
                    @if ($apiKeyConfigured)
                        <span class="badge active">ตั้งค่าแล้ว</span>
                    @else
                        <span class="badge inactive">ยังไม่มี</span>
                    @endif
                </div>
                <div id="probeResult" style="margin-top:.75rem; color: var(--vz-secondary-color);"></div>
            </div>
            <button type="button" class="button" id="btnProbe">ทดสอบการเชื่อมต่อ CLI</button>
        </div>
        <div id="probeChecks" style="margin-top:1rem;"></div>
    </div>

    <div class="panel" style="margin-bottom: 1rem;">
        <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
            <div style="flex:1; min-width:220px;">
                <label for="repo_key">Repository workspace</label>
                <select id="repo_key" name="repo_key">
                    <option value="">ใช้ directory ของระบบ IMS (base_path)</option>
                    @foreach ($repos as $repo)
                        <option value="{{ $repo['key'] }}">{{ $repo['name'] }} ({{ $repo['key'] }})</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:180px;">
                <label for="mode">โหมด</label>
                <select id="mode" name="mode">
                    <option value="ask" selected>ask (อ่านอย่างเดียว — แนะนำ)</option>
                    <option value="plan">plan</option>
                    <option value="agent">agent (แก้ไฟล์ได้)</option>
                </select>
            </div>
        </div>

        <div id="chatLog" class="chat-log">
            @forelse ($messages as $message)
                <div class="chat-bubble {{ $message['role'] === 'user' ? 'user' : 'assistant' }}">
                    <div class="chat-meta">{{ $message['role'] === 'user' ? 'คุณ' : 'Cursor CLI' }} · {{ $message['at'] ?? '' }}</div>
                    <div class="chat-text">{{ $message['content'] }}</div>
                </div>
            @empty
                <div class="chat-empty" id="chatEmpty">ยังไม่มีข้อความ — พิมพ์เพื่อทดสอบ CLI</div>
            @endforelse
        </div>

        <form id="chatForm" style="margin-top:1rem; display:flex; gap:.75rem; align-items:flex-end;">
            <div style="flex:1;">
                <label for="message">ข้อความ</label>
                <textarea id="message" name="message" rows="3" required placeholder="เช่น สรุปโครงสร้างโปรเจกต์นี้สั้นๆ"></textarea>
            </div>
            <button type="submit" class="button" id="btnSend">ส่ง</button>
        </form>
        <div id="chatStatus" style="margin-top:.75rem; color: var(--vz-secondary-color);"></div>
    </div>

    <style>
        .chat-log {
            min-height: 280px;
            max-height: 520px;
            overflow: auto;
            border: 1px solid var(--vz-border-color);
            border-radius: .25rem;
            padding: 1rem;
            background: #f8fafc;
        }
        .chat-bubble {
            max-width: 85%;
            margin-bottom: .85rem;
            padding: .75rem .9rem;
            border-radius: .5rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .chat-bubble.user {
            margin-left: auto;
            background: #dbeafe;
            color: #1e3a8a;
        }
        .chat-bubble.assistant {
            margin-right: auto;
            background: #fff;
            border: 1px solid var(--vz-border-color);
        }
        .chat-meta {
            font-size: .75rem;
            opacity: .75;
            margin-bottom: .35rem;
        }
        .chat-empty {
            color: var(--vz-secondary-color);
            text-align: center;
            padding: 2rem 1rem;
        }
        .probe-check {
            display: flex;
            gap: .75rem;
            padding: .45rem 0;
            border-top: 1px solid var(--vz-border-color);
            font-size: .9rem;
        }
        .probe-check:first-child { border-top: 0; }
    </style>
@endsection

@section('script')
<script>
(() => {
    const csrf = @json(csrf_token());
    const probeUrl = @json(route('cursor_autofix.test_connection'));
    const chatUrl = @json(route('cursor_autofix.chat.send'));
    const chatLog = document.getElementById('chatLog');
    const chatEmpty = document.getElementById('chatEmpty');
    const probeResult = document.getElementById('probeResult');
    const probeChecks = document.getElementById('probeChecks');
    const chatStatus = document.getElementById('chatStatus');
    const btnProbe = document.getElementById('btnProbe');
    const btnSend = document.getElementById('btnSend');
    const form = document.getElementById('chatForm');

    function appendBubble(role, content, at) {
        if (chatEmpty) chatEmpty.remove();
        const wrap = document.createElement('div');
        wrap.className = 'chat-bubble ' + (role === 'user' ? 'user' : 'assistant');
        wrap.innerHTML = '<div class="chat-meta"></div><div class="chat-text"></div>';
        wrap.querySelector('.chat-meta').textContent = (role === 'user' ? 'คุณ' : 'Cursor CLI') + (at ? ' · ' + at : '');
        wrap.querySelector('.chat-text').textContent = content;
        chatLog.appendChild(wrap);
        chatLog.scrollTop = chatLog.scrollHeight;
    }

    async function probe() {
        btnProbe.disabled = true;
        probeResult.textContent = 'กำลังทดสอบ...';
        probeChecks.innerHTML = '';
        try {
            const res = await fetch(probeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    repo_key: document.getElementById('repo_key').value || null,
                }),
            });
            const data = await res.json();
            probeResult.textContent = data.message || (data.ok ? 'เชื่อมต่อได้' : 'เชื่อมต่อไม่ได้');
            probeResult.style.color = data.ok ? 'var(--vz-success)' : 'var(--vz-danger)';
            (data.checks || []).forEach((check) => {
                const row = document.createElement('div');
                row.className = 'probe-check';
                row.innerHTML = '<span class="badge ' + (check.ok ? 'active' : 'inactive') + '">' + (check.ok ? 'OK' : 'FAIL') + '</span><div><strong>' + check.name + '</strong><div style="color:var(--vz-secondary-color); white-space:pre-wrap;">' + (check.detail || '') + '</div></div>';
                probeChecks.appendChild(row);
            });
        } catch (e) {
            probeResult.textContent = 'ทดสอบไม่สำเร็จ: ' + e.message;
            probeResult.style.color = 'var(--vz-danger)';
        } finally {
            btnProbe.disabled = false;
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = document.getElementById('message').value.trim();
        if (!message) return;

        btnSend.disabled = true;
        chatStatus.textContent = 'กำลังรอคำตอบจาก Cursor CLI...';
        appendBubble('user', message, new Date().toISOString().slice(0, 19).replace('T', ' '));
        document.getElementById('message').value = '';

        try {
            const res = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    message,
                    repo_key: document.getElementById('repo_key').value || null,
                    mode: document.getElementById('mode').value,
                }),
            });
            const data = await res.json();
            appendBubble('assistant', data.reply || data.message || 'ไม่มีคำตอบ', new Date().toISOString().slice(0, 19).replace('T', ' '));
            chatStatus.textContent = data.ok
                ? 'ตอบกลับสำเร็จ'
                : ('ตอบกลับพร้อม error (exit ' + (data.exit_code ?? '-') + ')');
            chatStatus.style.color = data.ok ? 'var(--vz-success)' : 'var(--vz-danger)';
        } catch (e) {
            appendBubble('assistant', 'เกิดข้อผิดพลาด: ' + e.message);
            chatStatus.textContent = 'ส่งไม่สำเร็จ';
            chatStatus.style.color = 'var(--vz-danger)';
        } finally {
            btnSend.disabled = false;
        }
    });

    btnProbe.addEventListener('click', probe);
})();
</script>
@endsection
