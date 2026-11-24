import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'messages', 'welcome', 'loading', 'error', 'send'];
    static values = { endpoint: String };

    connect() {
        this.pending = false;
    }

    enter(event) {
        if (event.target !== this.inputTarget || event.shiftKey || event.isComposing) return;
        event.preventDefault();
        this.send(event);
    }

    async send(event) {
        event.preventDefault();
        const question = this.inputTarget.value.trim();
        if (!question || this.pending) return;

        this.pending = true;
        this.errorTarget.hidden = true;
        this.welcomeTarget.hidden = true;
        this.appendMessage('user', question);
        this.inputTarget.value = '';
        this.loadingTarget.hidden = false;
        this.sendTarget.disabled = true;
        this.scrollToLatest();

        try {
            const response = await fetch(this.endpointValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ question }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || `Le serveur a répondu avec le statut ${response.status}.`);
            if (typeof result.answer !== 'string') throw new Error('La réponse du serveur est invalide.');
            this.appendMessage('assistant', result.answer);
        } catch (error) {
            this.errorTarget.textContent = error instanceof TypeError
                ? 'Impossible de joindre le serveur. Vérifiez que Symfony et Ollama sont démarrés.'
                : error.message;
            this.errorTarget.hidden = false;
        } finally {
            this.pending = false;
            this.loadingTarget.hidden = true;
            this.sendTarget.disabled = false;
            this.inputTarget.focus();
            this.scrollToLatest();
        }
    }

    newConversation() {
        this.messagesTarget.replaceChildren();
        this.errorTarget.hidden = true;
        this.welcomeTarget.hidden = false;
        this.inputTarget.value = '';
        this.inputTarget.focus();
    }

    appendMessage(role, text) {
        const row = document.createElement('article');
        row.className = `message ${role}`;
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.setAttribute('aria-hidden', 'true');
        avatar.textContent = role === 'user' ? 'VO' : 'AI';
        const content = document.createElement('div');
        content.className = 'content';
        if (role === 'assistant') this.renderMarkdown(content, text);
        else {
            const paragraph = document.createElement('p');
            paragraph.textContent = text;
            content.append(paragraph);
        }
        row.append(avatar, content);
        this.messagesTarget.append(row);
    }

    // A deliberately small Markdown renderer. It builds DOM nodes and never assigns HTML.
    renderMarkdown(container, markdown) {
        const lines = markdown.split(/\r?\n/);
        let list = null;
        let code = null;
        let codeLines = [];
        const flushList = () => { if (list) { container.append(list); list = null; } };
        for (const line of lines) {
            if (line.trim().startsWith('```')) {
                flushList();
                if (code) {
                    const pre = document.createElement('pre');
                    const codeNode = document.createElement('code');
                    codeNode.textContent = codeLines.join('\n');
                    pre.append(codeNode); container.append(pre); code = null; codeLines = [];
                } else code = true;
                continue;
            }
            if (code) { codeLines.push(line); continue; }
            const heading = line.match(/^(#{1,6})\s+(.+)$/);
            if (heading) {
                flushList(); const node = document.createElement(`h${heading[1].length}`);
                this.appendInline(node, heading[2]); container.append(node); continue;
            }
            const item = line.match(/^\s*[-*+]\s+(.+)$/);
            if (item) {
                if (!list) list = document.createElement('ul');
                const li = document.createElement('li'); this.appendInline(li, item[1]); list.append(li); continue;
            }
            flushList();
            if (!line.trim()) continue;
            const p = document.createElement('p'); this.appendInline(p, line); container.append(p);
        }
        flushList();
        if (code) { const pre = document.createElement('pre'); const node = document.createElement('code'); node.textContent = codeLines.join('\n'); pre.append(node); container.append(pre); }
    }

    appendInline(parent, text) {
        const pattern = /(`[^`]+`|\*\*[^*]+\*\*|\*[^*]+\*)/g;
        let start = 0;
        for (const match of text.matchAll(pattern)) {
            parent.append(document.createTextNode(text.slice(start, match.index)));
            const token = match[0];
            const node = token.startsWith('`') ? document.createElement('code')
                : token.startsWith('**') ? document.createElement('strong') : document.createElement('em');
            node.textContent = token.startsWith('**') ? token.slice(2, -2) : token.slice(1, -1);
            parent.append(node);
            start = match.index + token.length;
        }
        parent.append(document.createTextNode(text.slice(start)));
    }

    scrollToLatest() {
        requestAnimationFrame(() => window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }));
    }
}
