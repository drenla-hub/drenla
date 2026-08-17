function initJournal() {
    const shell = document.querySelector('[data-journal-shell]');
    const log = document.querySelector('[data-journal-log]');
    const form = document.querySelector('[data-journal-form]');
    if (!log || !form) {
        return;
    }

    function fitShellHeight() {
        if (!shell) {
            return;
        }
        const top = shell.getBoundingClientRect().top;
        const available = window.innerHeight - top - 32;
        shell.style.height = `${Math.max(available, 420)}px`;
    }
    fitShellHeight();
    window.addEventListener('resize', fitShellHeight);

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const input = form.querySelector('[data-journal-input]');
    const submitButton = form.querySelector('[data-journal-submit]');
    const status = form.querySelector('[data-journal-status]');
    const renameInput = document.querySelector('[data-rename-input]');
    const attachButton = form.querySelector('[data-journal-attach]');
    const fileInput = form.querySelector('[data-journal-file-input]');
    const filePreviews = form.querySelector('[data-journal-file-previews]');

    let loading = false;
    let selectedFiles = [];
    const MAX_FILES = 4;

    function autosizeInput() {
        input.style.height = 'auto';
        const maxHeight = parseFloat(getComputedStyle(input).maxHeight) || Infinity;
        input.style.height = `${Math.min(input.scrollHeight, maxHeight)}px`;
    }
    input?.addEventListener('input', autosizeInput);
    autosizeInput();

    function humanFileSize(bytes) {
        if (bytes < 1024) {
            return `${bytes} B`;
        }
        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    const fileIconSvg = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>';

    // Shared shape so a locally-selected File (pre-upload) and a server
    // attachment payload render identically: { isImage, url, name, size }.
    function renderAttachmentNode(attachment, onRemove) {
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';

        if (attachment.isImage) {
            const link = document.createElement('a');
            link.href = attachment.url;
            link.target = '_blank';
            const img = document.createElement('img');
            img.src = attachment.url;
            img.alt = attachment.name;
            img.className = 'h-16 w-16 rounded-md object-cover';
            link.appendChild(img);
            wrapper.appendChild(link);
        } else {
            const link = document.createElement('a');
            link.href = attachment.url;
            link.target = '_blank';
            link.className = 'flex items-center gap-2 rounded-md bg-[#242424] px-3 py-2 text-[12px] text-[#ccc] transition-colors hover:text-white';
            link.innerHTML = `${fileIconSvg}<span class="max-w-[140px] truncate">${attachment.name}</span><span class="shrink-0 text-[#666]">${attachment.size}</span>`;
            wrapper.appendChild(link);
        }

        if (onRemove) {
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute -right-1.5 -top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-black text-[10px] text-[#999] hover:text-white';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', onRemove);
            wrapper.appendChild(removeBtn);
        }

        return wrapper;
    }

    function renderFilePreviews() {
        filePreviews.innerHTML = '';
        filePreviews.style.display = selectedFiles.length ? 'flex' : 'none';

        selectedFiles.forEach((file, index) => {
            const attachment = {
                isImage: file.type.startsWith('image/'),
                url: URL.createObjectURL(file),
                name: file.name,
                size: humanFileSize(file.size),
            };
            filePreviews.appendChild(renderAttachmentNode(attachment, () => {
                selectedFiles.splice(index, 1);
                renderFilePreviews();
            }));
        });
    }

    attachButton?.addEventListener('click', () => fileInput.click());

    fileInput?.addEventListener('change', () => {
        const room = MAX_FILES - selectedFiles.length;
        selectedFiles = selectedFiles.concat(Array.from(fileInput.files).slice(0, Math.max(room, 0)));
        fileInput.value = '';
        renderFilePreviews();
    });

    function appendUserMessage(content, files = []) {
        const empty = log.querySelector('[data-journal-empty]');
        if (empty) {
            empty.remove();
        }

        const row = document.createElement('div');
        row.className = 'flex flex-col items-end gap-1.5';

        if (files.length) {
            const attachmentsRow = document.createElement('div');
            attachmentsRow.className = 'flex max-w-[75%] flex-wrap justify-end gap-1.5';
            files.forEach((file) => {
                attachmentsRow.appendChild(renderAttachmentNode({
                    isImage: file.type.startsWith('image/'),
                    url: URL.createObjectURL(file),
                    name: file.name,
                    size: humanFileSize(file.size),
                }));
            });
            row.appendChild(attachmentsRow);
        }

        if (content) {
            const bubble = document.createElement('div');
            bubble.className = 'w-fit max-w-[75%] rounded-md bg-[#242424] px-3.5 py-2 text-[14px] leading-relaxed whitespace-pre-wrap text-white';
            bubble.textContent = content;
            row.appendChild(bubble);
        }

        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
    }

    function appendAssistantMessage(content, contentHtml) {
        const empty = log.querySelector('[data-journal-empty]');
        if (empty) {
            empty.remove();
        }

        const row = document.createElement('div');
        row.className = 'group max-w-[85%]';

        const body = document.createElement('div');
        body.className = 'journal-markdown text-[14px] leading-relaxed text-[#e5e5e5]';
        body.innerHTML = contentHtml;
        row.appendChild(body);

        const toolbar = document.createElement('div');
        toolbar.className = 'mt-1 flex items-center opacity-0 transition-opacity group-hover:opacity-100';
        toolbar.innerHTML = `
            <button type="button" data-copy-btn title="Copy" class="text-[#555] transition-colors hover:text-white">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="13" height="13" x="9" y="9" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
        `;
        const source = document.createElement('textarea');
        source.setAttribute('data-copy-source', '');
        source.className = 'hidden';
        source.value = content;
        toolbar.appendChild(source);
        row.appendChild(toolbar);

        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
    }

    log.addEventListener('click', (event) => {
        const button = event.target.closest('[data-copy-btn]');
        if (!button) {
            return;
        }
        const source = button.parentElement.querySelector('[data-copy-source]');
        if (!source) {
            return;
        }
        navigator.clipboard.writeText(source.value).then(() => {
            const original = button.title;
            button.title = 'Copied!';
            setTimeout(() => { button.title = original; }, 1200);
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const prompt = input.value.trim();
        const files = selectedFiles;
        if ((!prompt && !files.length) || loading) {
            return;
        }

        appendUserMessage(prompt, files);
        input.value = '';
        selectedFiles = [];
        renderFilePreviews();
        autosizeInput();
        loading = true;
        submitButton.disabled = true;
        status.textContent = 'Thinking…';

        try {
            const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf };
            let body;
            if (files.length) {
                body = new FormData();
                body.append('prompt', prompt);
                files.forEach((file) => body.append('files[]', file));
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({ prompt });
            }

            const response = await fetch(form.action, {
                method: 'POST',
                headers,
                body,
            });

            const data = await response.json();

            if (!response.ok) {
                status.textContent = data.message || `Request failed (${response.status}).`;
                return;
            }

            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            appendAssistantMessage(data.message?.content || 'No response.', data.message?.content_html || 'No response.');
            status.textContent = '';

            if (renameInput && !renameInput.value && data.title) {
                renameInput.value = data.title;
            }
        } catch (error) {
            status.textContent = `Network error: ${error.message}`;
        } finally {
            loading = false;
            submitButton.disabled = false;
        }
    });

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    // Rename on blur — quiet inline edit, no separate "save" button.
    renameInput?.addEventListener('blur', () => {
        const renameForm = renameInput.closest('form');
        if (renameForm && renameInput.value.trim()) {
            renameForm.requestSubmit();
        }
    });
    renameInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            renameInput.blur();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initJournal);
} else {
    initJournal();
}
