function initAdminAssistant() {
    const shell = document.querySelector('[data-admin-shell]');
    if (!shell) {
        return;
    }

    const endpoint = shell.dataset.aiAssistantEndpoint;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const launcher = document.getElementById('oile-launcher');
    const panel = document.getElementById('oile-panel');
    const closeButton = document.getElementById('oile-close');
    const form = document.getElementById('oile-form');
    const input = document.getElementById('oile-input');
    const log = document.getElementById('oile-log');
    const status = document.getElementById('oile-status');

    if (!launcher || !panel || !closeButton || !form || !input || !log || !status || !endpoint) {
        return;
    }

    const state = {
        open: false,
        loading: false,
        history: [],
    };
    const pendingIntentKey = 'drenla-oile-pending-intent';

    renderHistory();
    autoResize();
    resumePendingIntent();

    launcher.addEventListener('click', () => {
        state.open = !state.open;
        panel.classList.toggle('hidden', !state.open);
        launcher.setAttribute('aria-expanded', state.open ? 'true' : 'false');
        if (state.open) {
            input.focus();
        }
    });

    closeButton.addEventListener('click', () => {
        state.open = false;
        panel.classList.add('hidden');
        launcher.setAttribute('aria-expanded', 'false');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const prompt = input.value.trim();
        if (!prompt || state.loading) {
            return;
        }

        pushMessage('admin', prompt);
        input.value = '';
        setLoading(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    prompt,
                    page: window.location.pathname,
                    page_context: scanPage(),
                    history: state.history.slice(-12).map((message) => ({
                        role: message.role,
                        content: message.content,
                    })),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                pushMessage('assistant', data.message || data.error || `Request failed with ${response.status}.`);
                return;
            }

            pushMessage('assistant', data.message || 'Done.');

            if (Array.isArray(data.actions) && data.actions.length > 0) {
                await executeActions(data.actions);
            }
        } catch (error) {
            pushMessage('assistant', `Network error: ${error.message}`);
        } finally {
            setLoading(false);
        }
    });

    input.addEventListener('input', () => {
        autoResize();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    function pushMessage(role, content) {
        state.history.push({ role, content });
        renderHistory();
    }

    function renderHistory() {
        log.innerHTML = '';

        if (state.history.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-[11px] leading-5 text-white/35';
            empty.textContent = 'Ask Oile to review the current admin page, fill fields, click actions, or navigate to another admin screen.';
            log.appendChild(empty);
            return;
        }

        state.history.slice(-20).forEach((message) => {
            const row = document.createElement('div');
            row.className = message.role === 'admin'
                ? 'ml-8 border border-[#1f1f1f] bg-white px-3 py-2 text-[12px] font-medium leading-5 text-black'
                : 'mr-6 border border-[#1f1f1f] bg-[#101010] px-3 py-2 text-[12px] font-medium leading-5 text-white';
            row.textContent = message.content;
            log.appendChild(row);
        });

        log.scrollTop = log.scrollHeight;
    }

    function setLoading(value) {
        state.loading = value;
        status.textContent = value ? 'Thinking…' : 'Ready';
        input.disabled = value;
    }

    function autoResize() {
        input.style.height = '44px';
        input.style.height = `${Math.min(input.scrollHeight, 108)}px`;
    }

    function labelFor(element) {
        if (!element) {
            return '';
        }

        if (element.id) {
            const label = document.querySelector(`label[for="${CSS.escape(element.id)}"]`);
            if (label) {
                return cleanText(label.textContent);
            }
        }

        const wrappedLabel = element.closest('label');
        if (wrappedLabel) {
            return cleanText(wrappedLabel.textContent);
        }

        return element.getAttribute('aria-label')
            || element.placeholder
            || element.name
            || element.id
            || '';
    }

    function cleanText(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    }

    function buildSelector(element) {
        if (element.id) {
            return `#${CSS.escape(element.id)}`;
        }

        if (element.name) {
            return `${element.tagName.toLowerCase()}[name="${CSS.escape(element.name)}"]`;
        }

        const label = labelFor(element);
        if (label) {
            return `${element.tagName.toLowerCase()}[aria-label="${label}"]`;
        }

        return element.tagName.toLowerCase();
    }

    function scanPage() {
        const context = {
            inputs: [],
            buttons: [],
            selects: [],
            links: [],
            sections: [],
            meta: {
                title: document.title,
                path: window.location.pathname,
            },
        };

        document.querySelectorAll('input:not([type="hidden"]), textarea').forEach((element) => {
            context.inputs.push({
                selector: buildSelector(element),
                label: labelFor(element),
                name: element.name || '',
                type: element.type || element.tagName.toLowerCase(),
                value: typeof element.value === 'string' ? element.value.slice(0, 500) : '',
            });
        });

        document.querySelectorAll('select').forEach((element) => {
            context.selects.push({
                selector: buildSelector(element),
                label: labelFor(element),
                name: element.name || '',
                value: element.value,
                options: Array.from(element.options).map((option) => option.textContent.trim()).slice(0, 40),
            });
        });

        document.querySelectorAll('button, input[type="submit"], input[type="button"]').forEach((element) => {
            const text = cleanText(element.textContent || element.value || '');
            if (!text) {
                return;
            }

            context.buttons.push({
                selector: buildSelector(element),
                text,
            });
        });

        document.querySelectorAll('a[href]').forEach((element) => {
            const text = cleanText(element.textContent);
            if (!text) {
                return;
            }

            context.links.push({
                selector: buildSelector(element),
                text,
                href: (element.getAttribute('href') || '').trim(),
            });
        });

        document.querySelectorAll('main section, main article, main [data-assistant-section]').forEach((element) => {
            const heading = cleanText(
                element.querySelector('h1, h2, h3, [data-section-title]')?.textContent
                || ''
            );
            const items = Array.from(element.querySelectorAll('p, li, dt, dd'))
                .map((node) => cleanText(node.textContent))
                .filter(Boolean)
                .slice(0, 8);

            if (heading || items.length > 0) {
                context.sections.push({
                    heading,
                    items,
                });
            }
        });

        context.inputs = context.inputs.slice(0, 80);
        context.selects = context.selects.slice(0, 40);
        context.buttons = context.buttons.slice(0, 40);
        context.links = context.links.slice(0, 40);
        context.sections = context.sections.slice(0, 20);

        return context;
    }

    async function executeActions(actions) {
        for (const action of actions) {
            if (!action || typeof action !== 'object') {
                continue;
            }

            if (action.type === 'navigate' && action.url) {
                const lastAdminMessage = [...state.history].reverse().find((message) => message.role === 'admin');
                if (lastAdminMessage) {
                    window.sessionStorage.setItem(pendingIntentKey, JSON.stringify({
                        prompt: lastAdminMessage.content,
                        from: window.location.pathname,
                        history: state.history.slice(-8),
                    }));
                }
                window.location.href = action.url;
                return;
            }

            if (action.type === 'fill') {
                const element = await findElement(action.selector, action.label);
                if (!element) {
                    continue;
                }

                element.focus();
                element.value = action.value || '';
                element.dispatchEvent(new Event('input', { bubbles: true }));
                element.dispatchEvent(new Event('change', { bubbles: true }));
                continue;
            }

            if (action.type === 'select') {
                const element = await findElement(action.selector, action.label);
                if (!element || element.tagName !== 'SELECT') {
                    continue;
                }

                element.value = action.value || '';
                element.dispatchEvent(new Event('change', { bubbles: true }));
                continue;
            }

            if (action.type === 'click') {
                const element = await findElement(action.selector, action.label, true);
                if (!element) {
                    continue;
                }

                element.click();
            }
        }
    }

    async function findElement(selector, label, includeButtons = false) {
        const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

        for (let index = 0; index < 5; index += 1) {
            const direct = queryVisible(selector);
            if (direct) {
                return direct;
            }

            const byLabel = label ? queryByLabel(label, includeButtons) : null;
            if (byLabel) {
                return byLabel;
            }

            await wait(120);
        }

        return null;
    }

    function queryVisible(selector) {
        if (!selector) {
            return null;
        }

        const matches = document.querySelectorAll(selector);
        if (matches.length === 0) {
            return null;
        }

        return Array.from(matches).find((element) => element.offsetParent !== null) || matches[0];
    }

    function queryByLabel(label, includeButtons = false) {
        const needle = cleanText(label).toLowerCase();
        if (!needle) {
            return null;
        }

        const fields = Array.from(document.querySelectorAll('input:not([type="hidden"]), textarea, select'));
        const buttons = includeButtons
            ? Array.from(document.querySelectorAll('button, a, [role="button"], input[type="submit"]'))
            : [];

        const candidates = [...fields, ...buttons];

        return candidates.find((element) => {
            const text = cleanText(labelFor(element) || element.textContent || element.value || '').toLowerCase();
            return text.includes(needle);
        }) || null;
    }

    async function resumePendingIntent() {
        let pendingIntent;

        try {
            const raw = window.sessionStorage.getItem(pendingIntentKey);
            pendingIntent = raw ? JSON.parse(raw) : null;
        } catch {
            pendingIntent = null;
        }

        if (!pendingIntent || !pendingIntent.prompt) {
            return;
        }

        window.sessionStorage.removeItem(pendingIntentKey);

        if (pendingIntent.from === window.location.pathname) {
            return;
        }

        state.open = true;
        panel.classList.remove('hidden');
        launcher.setAttribute('aria-expanded', 'true');

        if (Array.isArray(pendingIntent.history) && pendingIntent.history.length > 0) {
            state.history = pendingIntent.history.filter((message) => (
                message
                && typeof message.role === 'string'
                && typeof message.content === 'string'
            )).slice(-8);
            renderHistory();
        }

        setLoading(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    prompt: `[Navigated from ${pendingIntent.from} to ${window.location.pathname} while completing: "${pendingIntent.prompt}". Continue and complete the task on this page.]`,
                    page: window.location.pathname,
                    page_context: scanPage(),
                    history: state.history.slice(-12).map((message) => ({
                        role: message.role,
                        content: message.content,
                    })),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                pushMessage('assistant', data.message || data.error || `Resume failed with ${response.status}.`);
                return;
            }

            pushMessage('assistant', data.message || 'Continuing.');

            if (Array.isArray(data.actions) && data.actions.length > 0) {
                await executeActions(data.actions);
            }
        } catch (error) {
            pushMessage('assistant', `Resume error: ${error.message}`);
        } finally {
            setLoading(false);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminAssistant);
} else {
    initAdminAssistant();
}
