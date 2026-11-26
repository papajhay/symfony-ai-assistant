import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'messages', 'welcome', 'loading', 'error', 'send', 'history'];
    static values = { endpoint: String, conversations: String };

    connect() {
        this.pending = false;
        this.conversationId = null;
        this.refreshHistory();
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
                body: JSON.stringify({ question, conversationId: this.conversationId }),
            });
            const result = await response.json().catch(() => ({}));
            if (Number.isInteger(result.conversationId)) this.conversationId = result.conversationId;
            if (!response.ok) throw new Error(result.error || `Le serveur a répondu avec le statut ${response.status}.`);
            if (typeof result.answer !== 'string') throw new Error('La réponse du serveur est invalide.');
            this.conversationId = result.conversationId;
            this.appendMessage('assistant', result.answer);
            await this.refreshHistory();
        } catch (error) {
            if (this.conversationId !== null) await this.refreshHistory();
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
        this.conversationId = null;
        this.messagesTarget.replaceChildren();
        this.errorTarget.hidden = true;
        this.welcomeTarget.hidden = false;
        this.inputTarget.value = '';
        this.inputTarget.focus();
        this.refreshHistory();
    }

    async refreshHistory() {
        try {
            const response = await fetch(this.conversationsValue, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const { conversations = [] } = await response.json();
            this.historyTarget.replaceChildren();
            if (!conversations.length) {
                const empty = document.createElement('div'); empty.className = 'history-empty'; empty.textContent = 'Aucune conversation'; this.historyTarget.append(empty); return;
            }
            for (const conversation of conversations) {
                const row = document.createElement('div'); row.className = `history-item${conversation.id === this.conversationId ? ' active' : ''}`;
                const open = document.createElement('button'); open.type = 'button'; open.className = 'history-open'; open.textContent = conversation.title; open.title = conversation.title;
                open.addEventListener('click', () => this.openConversation(conversation.id));
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'history-delete'; remove.textContent = '×'; remove.title = 'Supprimer la conversation'; remove.setAttribute('aria-label', `Supprimer ${conversation.title}`);
                remove.addEventListener('click', () => this.deleteConversation(conversation.id));
                row.append(open, remove); this.historyTarget.append(row);
            }
        } catch { /* Keep the chat usable if history cannot be refreshed. */ }
    }

    async openConversation(id) {
        if (this.pending) return;
        this.errorTarget.hidden = true;
        try {
            const response = await fetch(`${this.conversationsValue}/${id}`, { headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'Impossible de charger cette conversation.');
            this.conversationId = result.id;
            this.messagesTarget.replaceChildren();
            for (const message of result.messages) this.appendMessage(message.role, message.content);
            this.welcomeTarget.hidden = result.messages.length > 0;
            this.inputTarget.value = '';
            await this.refreshHistory();
            this.scrollToLatest();
        } catch (error) { this.errorTarget.textContent = error.message; this.errorTarget.hidden = false; }
    }

    async deleteConversation(id) {
        if (!window.confirm('Supprimer cette conversation et tous ses messages ?')) return;
        try {
            const response = await fetch(`${this.conversationsValue}/${id}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.error || 'Impossible de supprimer cette conversation.');
            if (id === this.conversationId) this.newConversation();
            await this.refreshHistory();
        } catch (error) { this.errorTarget.textContent = error.message; this.errorTarget.hidden = false; }
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
