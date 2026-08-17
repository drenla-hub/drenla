/**
 * Dark WYSIWYG editor — Tiptap + custom toolbar, styled to match the admin theme.
 * Attached to every element with [data-wysiwyg].
 */
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'

function initEditors() {
    document.querySelectorAll('[data-wysiwyg]').forEach(wrapper => {
        const textarea  = wrapper.querySelector('textarea[data-body]')
        const editorEl  = wrapper.querySelector('[data-editor]')
        if (!textarea || !editorEl) return

        const editor = new Editor({
            element: editorEl,
            extensions: [
                StarterKit.configure({
                    heading: { levels: [2, 3, 4] },
                }),
                Underline,
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: { rel: 'noopener noreferrer' },
                }),
            ],
            content: textarea.value || '',
            editorProps: {
                attributes: {
                    class: 'wysiwyg-body',
                    spellcheck: 'true',
                },
            },
            onUpdate({ editor }) {
                textarea.value = editor.getHTML()
            },
        })

        // ── Toolbar actions ────────────────────────────────────────────────
        wrapper.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('mousedown', e => {
                e.preventDefault() // keep focus inside editor
                const action = btn.dataset.action
                const level  = parseInt(btn.dataset.level ?? '0')
                const chain  = editor.chain().focus()

                switch (action) {
                    case 'bold':        chain.toggleBold().run(); break
                    case 'italic':      chain.toggleItalic().run(); break
                    case 'underline':   chain.toggleUnderline().run(); break
                    case 'strike':      chain.toggleStrike().run(); break
                    case 'heading':     chain.toggleHeading({ level }).run(); break
                    case 'bullet':      chain.toggleBulletList().run(); break
                    case 'ordered':     chain.toggleOrderedList().run(); break
                    case 'blockquote':  chain.toggleBlockquote().run(); break
                    case 'code':        chain.toggleCode().run(); break
                    case 'codeblock':   chain.toggleCodeBlock().run(); break
                    case 'hr':          chain.setHorizontalRule().run(); break
                    case 'link': {
                        const prev = editor.getAttributes('link').href ?? ''
                        const url  = prompt('Enter URL', prev)
                        if (url === null) break
                        url ? chain.setLink({ href: url }).run()
                            : chain.unsetLink().run()
                        break
                    }
                    case 'unlink':      chain.unsetLink().run(); break
                    case 'undo':        chain.undo().run(); break
                    case 'redo':        chain.redo().run(); break
                    case 'clear':       chain.selectAll().clearNodes().unsetAllMarks().run(); break
                }

                syncActive(wrapper, editor)
            })
        })

        editor.on('selectionUpdate', () => syncActive(wrapper, editor))
        editor.on('update',          () => syncActive(wrapper, editor))
        syncActive(wrapper, editor)
    })
}

function syncActive(wrapper, editor) {
    wrapper.querySelectorAll('[data-action]').forEach(btn => {
        const action = btn.dataset.action
        const level  = parseInt(btn.dataset.level ?? '0')
        let active = false

        switch (action) {
            case 'bold':       active = editor.isActive('bold'); break
            case 'italic':     active = editor.isActive('italic'); break
            case 'underline':  active = editor.isActive('underline'); break
            case 'strike':     active = editor.isActive('strike'); break
            case 'heading':    active = editor.isActive('heading', { level }); break
            case 'bullet':     active = editor.isActive('bulletList'); break
            case 'ordered':    active = editor.isActive('orderedList'); break
            case 'blockquote': active = editor.isActive('blockquote'); break
            case 'code':       active = editor.isActive('code'); break
            case 'codeblock':  active = editor.isActive('codeBlock'); break
            case 'link':       active = editor.isActive('link'); break
        }

        btn.classList.toggle('is-active', active)
        btn.setAttribute('aria-pressed', active ? 'true' : 'false')
    })
}

// Init on DOMContentLoaded (or immediately if already loaded)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditors)
} else {
    initEditors()
}
