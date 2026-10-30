import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['conversation', 'input', 'submitButton', 'cartButton'];
    static values = {
        apiUrl: String
    };

    connect() {
        this.conversationTarget.innerHTML = '';
    }

    async sendMessage(event) {
        event.preventDefault();

        const message = this.inputTarget.value.trim();
        if (!message) {
            return;
        }

        // Disable input and button during request
        this.inputTarget.disabled = true;
        this.submitButtonTarget.disabled = true;

        // Add user message to conversation
        this.addMessage('user', message);

        // Clear input
        this.inputTarget.value = '';

        try {
            const response = await fetch(this.apiUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message }),
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            // Add assistant response to conversation
            this.addMessage('assistant', data.message);

            // Show cart button if tools were called
            if (data.tools && data.tools.length > 0) {
                this.cartButtonTarget.style.display = 'block';
            }
        } catch (error) {
            console.error('Error:', error);
            this.addMessage('error', 'Une erreur est survenue. Veuillez réessayer.');
        } finally {
            // Re-enable input and button
            this.inputTarget.disabled = false;
            this.submitButtonTarget.disabled = false;
            this.inputTarget.focus();
        }
    }

    addMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `copilot-message copilot-message--${role}`;
        messageDiv.textContent = content;
        this.conversationTarget.appendChild(messageDiv);

        // Scroll to bottom
        this.conversationTarget.scrollTop = this.conversationTarget.scrollHeight;
    }
}
