<x-layout title="API Playground" wide>
    <x-demo-banner lesson="first-api-routes" :files="[
        'routes/api.php',
        'app/Http/Controllers/Api/AuthController.php',
        'app/Http/Controllers/Api/V1/BookController.php',
        'app/Http/Resources/V1/BookResource.php',
        'app/Policies/BookPolicy.php',
        'bootstrap/app.php',
    ]">
        Send real requests to this app's API and inspect the status, headers and JSON. Try the presets top to bottom:
        log in, then create, update and delete a book. Seeded user: <code>reader@example.com</code> / <code>password</code>.
    </x-demo-banner>

    <div class="pg">
        <aside class="pg-presets panel">
            <h3 style="margin-top:0">Presets</h3>
            <div class="pg-list" id="presets"></div>
        </aside>

        <section>
            <form id="pg-form" class="panel">
                <div class="row">
                    <select id="method" style="width:auto" aria-label="HTTP method">
                        @foreach (['GET', 'POST', 'PATCH', 'PUT', 'DELETE'] as $m)
                            <option>{{ $m }}</option>
                        @endforeach
                    </select>
                    <input id="url" class="grow" value="/api/v1/books" aria-label="URL" spellcheck="false">
                    <button class="btn btn-primary" id="send">Send ▶</button>
                </div>
                <div class="field" style="margin-top:12px">
                    <label for="token">Bearer token <small class="muted">(filled automatically after login)</small></label>
                    <div class="row">
                        <input id="token" class="grow" placeholder="none: you're a guest" spellcheck="false">
                        <button type="button" class="btn btn-sm" id="clear-token">Clear</button>
                    </div>
                </div>
                <div class="field">
                    <label for="body">JSON body</label>
                    <textarea id="body" rows="7" spellcheck="false" style="font-family: 'JetBrains Mono', monospace; font-size:.88rem"></textarea>
                </div>
            </form>

            <div class="panel">
                <div class="row" style="justify-content: space-between">
                    <h3 style="margin:0">Response</h3>
                    <span id="status" class="badge badge-muted">not sent yet</span>
                </div>
                <p id="hint" class="muted" style="margin:.4em 0 0"></p>
                <pre id="headers" class="pg-pre pg-headers"></pre>
                <pre id="response" class="pg-pre">Press Send to call the API.</pre>
            </div>
        </section>
    </div>

    <style>
        .pg { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 18px; align-items: start; }
        .pg-list { display: grid; gap: 6px; }
        .pg-list button { text-align: left; border: 1px solid var(--border); background: var(--surface); color: var(--text);
            border-radius: 10px; padding: 8px 10px; cursor: pointer; font: inherit; font-size: .9rem; }
        .pg-list button:hover { border-color: var(--accent); }
        .pg-list b { display: inline-block; min-width: 54px; font-size: .75rem; color: var(--accent); }
        .pg-pre { background: var(--code-bg); color: #e6edf3; border-radius: 12px; padding: 14px; overflow: auto; max-height: 520px;
            font: .85rem/1.5 "JetBrains Mono", ui-monospace, monospace; margin: 12px 0 0; white-space: pre-wrap; overflow-wrap: anywhere; }
        .pg-headers { color: #8b949e; max-height: 140px; }
        @media (max-width: 900px) { .pg { grid-template-columns: 1fr; } }
    </style>

    <script>
    (() => {
        const $ = (id) => document.getElementById(id);
        const store = { get: (k) => { try { return localStorage.getItem(k) || ''; } catch (e) { return ''; } },
                        set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };
        $('token').value = store.get('pg-token');
        let lastBookId = Number(store.get('pg-book')) || 1;

        const presets = [
            ['GET', '/api/v1/ping', null, 'Health check: no auth needed.'],
            ['GET', '/api/v1/books', null, 'Paginated list with data, links and meta.'],
            ['GET', '/api/v1/books?search=adichie&sort=-published_year&per_page=3', null, 'Search + sort + page size.'],
            ['GET', '/api/v1/books?sort=password', null, 'Sort is whitelisted, so this gives a 422.'],
            ['GET', '/api/v1/books/1', null, 'One book (route model binding).'],
            ['GET', '/api/v1/books/99999', null, 'Missing book: our custom JSON 404.'],
            ['POST', '/api/v1/books', { title: 'No token' }, 'Write without a token: 401.'],
            ['POST', '/api/v1/auth/login', { email: 'reader@example.com', password: 'password', device_name: 'playground' }, 'Log in: the token is saved automatically.'],
            ['POST', '/api/v1/auth/login', { email: 'reader@example.com', password: 'password', device_name: 'playground', read_only: true }, 'Read-only token (books:read only).'],
            ['GET', '/api/v1/auth/me', null, 'Who am I? Shows the token abilities.'],
            ['POST', '/api/v1/books', { title: 'My First API Book', author: 'You', genre: 'tech', published_year: 2026 }, 'Create (needs books:write). A read-only token gets 403.'],
            ['POST', '/api/v1/books', { title: '', genre: 'romance' }, 'Validation errors: 422.'],
            ['PATCH', () => `/api/v1/books/${lastBookId}`, { title: 'Updated with PATCH' }, 'Update the book you created (owner only).'],
            ['PATCH', '/api/v1/books/11', { title: 'Not mine' }, "Someone else's book: 403 (policy)."],
            ['DELETE', () => `/api/v1/books/${lastBookId}`, null, 'Delete it: 204 No Content.'],
            ['GET', '/api/v1/stats', null, 'Cached stats (Cache::remember).'],
            ['GET', '/api/v2/books', null, 'v2: a different JSON shape + cursor pagination.'],
            ['POST', '/api/v1/auth/logout', null, 'Revoke the current token.'],
            ['POST', '/api/v1/auth/register', { name: 'New Learner', email: `learner${Date.now() % 100000}@example.com`, password: 'password123', device_name: 'playground' }, 'Register a new account.'],
        ];

        presets.forEach(([method, url, body, hint]) => {
            const b = document.createElement('button');
            b.type = 'button';
            const label = typeof url === 'function' ? url() : url;
            b.innerHTML = `<b>${method}</b> ${label.replace('/api', '')}`;
            b.title = hint;
            b.addEventListener('click', () => {
                $('method').value = method;
                $('url').value = typeof url === 'function' ? url() : url;
                $('body').value = body ? JSON.stringify(body, null, 2) : '';
                $('hint').textContent = hint;
                send();
            });
            $('presets').appendChild(b);
        });

        $('clear-token').addEventListener('click', () => { $('token').value = ''; store.set('pg-token', ''); });
        $('pg-form').addEventListener('submit', (e) => { e.preventDefault(); send(); });

        async function send() {
            const method = $('method').value;
            const headers = { Accept: 'application/json' };
            const token = $('token').value.trim();
            if (token) headers.Authorization = `Bearer ${token}`;
            let body;
            if ($('body').value.trim() && method !== 'GET') {
                headers['Content-Type'] = 'application/json';
                body = $('body').value;
            }
            $('status').textContent = 'sending…';
            const started = performance.now();
            try {
                const res = await fetch($('url').value, { method, headers, body });
                const ms = Math.round(performance.now() - started);
                const text = await res.text();
                let json = null;
                try { json = text ? JSON.parse(text) : null; } catch (e) {}

                $('status').textContent = `${res.status} ${res.statusText} · ${ms} ms`;
                $('status').className = 'badge ' + (res.ok ? 'badge-success' : res.status >= 500 ? 'badge-advanced' : 'badge-intermediate');
                $('headers').textContent = ['content-type', 'x-ratelimit-limit', 'x-ratelimit-remaining', 'retry-after']
                    .filter((h) => res.headers.get(h)).map((h) => `${h}: ${res.headers.get(h)}`).join('\n');
                $('response').textContent = json !== null ? JSON.stringify(json, null, 2) : (text || '(empty body)');

                if (json?.token) { $('token').value = json.token; store.set('pg-token', json.token); }
                if (method === 'POST' && res.status === 201 && json?.data?.id) { lastBookId = json.data.id; store.set('pg-book', lastBookId); }
                if (method === 'POST' && $('url').value.endsWith('/logout') && res.ok) { $('token').value = ''; store.set('pg-token', ''); }
            } catch (e) {
                $('status').textContent = 'network error';
                $('response').textContent = String(e);
            }
        }
    })();
    </script>
</x-layout>
