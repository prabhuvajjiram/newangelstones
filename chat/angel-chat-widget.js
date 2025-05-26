// Angel Stones Chat Widget - Embeddable Chat Interface
// Version: 1.0.0

(function() {
    // Configuration
    const config = {
        apiBaseUrl: '/chat/api', // Update this with your actual API endpoint
        widgetTitle: 'Chat with us',
        welcomeMessage: 'Welcome to Angel Stones! How can we help you today?',
        primaryColor: '#2c3e50',
        accentColor: '#3498db',
        position: 'right', // 'left' or 'right'
        zIndex: 9999,
        showWelcomeScreen: true
    };

    // State
    let isOpen = false;
    let isInitialized = false;
    let unreadCount = 0;
    let sessionId = 'session_' + Math.random().toString(36).substring(2, 12);
    let messagePolling = null;
    let visitorInfo = {
        name: '',
        email: '',
        phone: ''
    };

    // DOM Elements
    let chatContainer, chatButton, chatWindow, chatHeader, chatBody, chatInput, messageForm, messageInput;

    // Initialize the widget
    function init() {
        if (isInitialized) return;
        
        // Create and inject the widget HTML
        const widgetHTML = `
            <div class="angel-chat-container" style="position: fixed; bottom: 25px; ${config.position}: 25px; z-index: ${config.zIndex};">
                <!-- Chat Button -->
                <button class="angel-chat-button" id="angelChatButton" aria-label="Open chat">
                    <i class="fas fa-comment-dots chat-icon"></i>
                    <i class="fas fa-times close-icon"></i>
                    <span class="angel-chat-badge">0</span>
                </button>

                <!-- Chat Window -->
                <div class="angel-chat-window" id="angelChatWindow">
                    <!-- Chat Header -->
                    <div class="angel-chat-header">
                        <h5>${config.widgetTitle}</h5>
                        <button class="angel-chat-close" id="angelChatClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <!-- Chat Body -->
                    <div class="angel-chat-body" id="angelChatBody">
                        <div class="angel-welcome-screen" id="angelWelcomeScreen">
                            <i class="fas fa-comment-alt"></i>
                            <h4>Welcome to Angel Stones</h4>
                            <p>How can we help you today?</p>
                            <button class="btn btn-primary" id="startChatBtn">Start Chat</button>
                        </div>
                        <div class="angel-messages" id="angelMessages"></div>
                    </div>
                    <!-- Chat Footer -->
                    <div class="angel-chat-footer">
                        <form id="angelMessageForm" class="angel-message-form">
                            <div class="input-group">
                                <input type="text" class="form-control" id="angelMessageInput" 
                                       placeholder="Type your message..." aria-label="Type your message">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Create a style element for the widget
        const style = document.createElement('style');
        style.textContent = `
            /* Base Styles */
            :root {
                --primary: ${config.primaryColor};
                --accent: ${config.accentColor};
                --white: #ffffff;
                --light: #f8f9fa;
                --dark: #343a40;
                --gray: #6c757d;
                --light-gray: #e9ecef;
                --danger: #dc3545;
                --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                --border-radius: 12px;
                --transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            }

            /* Chat Widget Styles */
            .angel-chat-button {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                color: white;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: var(--shadow);
                position: relative;
                transition: var(--transition);
                outline: none;
            }

            .angel-chat-button:hover {
                transform: translateY(-3px) scale(1.05);
                box-shadow: 0 6px 25px rgba(0, 0, 0, 0.25);
            }


            /* Rest of the styles from widget.html */
            ${getWidgetStyles()}
        `;

        // Append style and widget to the body
        document.head.appendChild(style);
        const div = document.createElement('div');
        div.innerHTML = widgetHTML;
        document.body.appendChild(div);

        // Initialize DOM references
        initializeElements();
        
        // Add event listeners
        setupEventListeners();
        
        // Start polling for messages
        startMessagePolling();
        
        isInitialized = true;
        
        // Show welcome screen if enabled
        if (config.showWelcomeScreen) {
            showWelcomeScreen();
        }
    }


    // Helper function to get widget styles
    function getWidgetStyles() {
        // This would contain all the CSS from widget.html
        // For brevity, I'm including a simplified version
        return `
            .angel-chat-window {
                position: fixed;
                width: 350px;
                max-width: 90%;
                height: 500px;
                max-height: 80vh;
                background: var(--white);
                border-radius: var(--border-radius);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px) scale(0.95);
                transition: var(--transition);
                z-index: 9999;
                bottom: 70px;
                ${config.position}: 10px;
            }

            /* Add more styles as needed */
        `;
    }

    // Initialize DOM elements
    function initializeElements() {
        chatContainer = document.querySelector('.angel-chat-container');
        chatButton = document.getElementById('angelChatButton');
        chatWindow = document.getElementById('angelChatWindow');
        chatHeader = document.querySelector('.angel-chat-header');
        chatBody = document.getElementById('angelChatBody');
        messageForm = document.getElementById('angelMessageForm');
        messageInput = document.getElementById('angelMessageInput');
    }

    // Set up event listeners
    function setupEventListeners() {
        // Toggle chat window
        chatButton.addEventListener('click', toggleChat);
        
        // Close button
        const closeButton = document.getElementById('angelChatClose');
        if (closeButton) {
            closeButton.addEventListener('click', closeChat);
        }
        
        // Start chat button
        const startChatBtn = document.getElementById('startChatBtn');
        if (startChatBtn) {
            startChatBtn.addEventListener('click', startChat);
        }
        
        // Message form submission
        if (messageForm) {
            messageForm.addEventListener('submit', handleMessageSubmit);
        }
    }

    // Toggle chat window
    function toggleChat() {
        isOpen = !isOpen;
        
        if (isOpen) {
            chatWindow.classList.add('active');
            document.querySelector('.chat-icon').style.display = 'none';
            document.querySelector('.close-icon').style.display = 'block';
            messageInput.focus();
            resetUnreadCount();
        } else {
            chatWindow.classList.remove('active');
            document.querySelector('.chat-icon').style.display = 'block';
            document.querySelector('.close-icon').style.display = 'none';
        }
    }

    // Close chat
    function closeChat() {
        isOpen = false;
        chatWindow.classList.remove('active');
        document.querySelector('.chat-icon').style.display = 'block';
        document.querySelector('.close-icon').style.display = 'none';
    }

    // Show welcome screen
    function showWelcomeScreen() {
        const welcomeScreen = document.getElementById('angelWelcomeScreen');
        const messagesContainer = document.getElementById('angelMessages');
        
        if (welcomeScreen) welcomeScreen.style.display = 'flex';
        if (messagesContainer) messagesContainer.style.display = 'none';
    }

    // Start chat
    function startChat() {
        const welcomeScreen = document.getElementById('angelWelcomeScreen');
        const messagesContainer = document.getElementById('angelMessages');
        
        if (welcomeScreen) welcomeScreen.style.display = 'none';
        if (messagesContainer) messagesContainer.style.display = 'block';
        
        // Add welcome message
        addSystemMessage(config.welcomeMessage);
        
        // Focus on input
        if (messageInput) messageInput.focus();
    }

    // Handle message submission
    function handleMessageSubmit(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Add message to UI
        addMessage(message, 'visitor');
        
        // Clear input
        messageInput.value = '';
        
        // Send message to server
        sendMessage(message);
    }

    // Add message to chat
    function addMessage(text, type, id = Date.now()) {
        const messagesContainer = document.getElementById('angelMessages');
        if (!messagesContainer) return;
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}-message`;
        messageElement.setAttribute('data-message-id', id);
        
        // Format message based on type
        if (type === 'visitor') {
            messageElement.innerHTML = `
                <div class="message-content">${escapeHtml(text)}</div>
                <div class="message-time">${formatTime()}</div>
            `;
        } else if (type === 'agent') {
            messageElement.innerHTML = `
                <div class="message-sender">Agent</div>
                <div class="message-content">${escapeHtml(text)}</div>
                <div class="message-time">${formatTime()}</div>
            `;
        } else if (type === 'system') {
            messageElement.className = 'system-message';
            messageElement.textContent = text;
        }
        
        messagesContainer.appendChild(messageElement);
        scrollToBottom();
        
        // Increment unread count if chat is closed
        if (!isOpen && type !== 'visitor') {
            incrementUnreadCount();
        }
    }

    // Add system message
    function addSystemMessage(text) {
        addMessage(text, 'system');
    }

    // Send message to server
    function sendMessage(message) {
        fetch(`${config.apiBaseUrl}/send_message.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                session_id: sessionId,
                message: message,
                visitor_name: visitorInfo.name,
                visitor_email: visitorInfo.email,
                visitor_phone: visitorInfo.phone
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Message sent:', data);
        })
        .catch(error => {
            console.error('Error sending message:', error);
            addSystemMessage('Error sending message. Please try again.');
        });
    }

    // Poll for new messages
    function startMessagePolling() {
        // Poll every 3 seconds
        messagePolling = setInterval(() => {
            if (!isInitialized) return;
            
            fetch(`${config.apiBaseUrl}/get_messages.php?session_id=${sessionId}&last_message_id=${getLastMessageId()}`)
                .then(response => response.json())
                .then(messages => {
                    if (messages && messages.length > 0) {
                        messages.forEach(msg => {
                            addMessage(msg.text, 'agent', msg.id);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error polling messages:', error);
                });
        }, 3000);
    }

    // Helper: Get last message ID
    function getLastMessageId() {
        const messages = document.querySelectorAll('[data-message-id]');
        return messages.length > 0 ? messages[messages.length - 1].getAttribute('data-message-id') : 0;
    }

    // Helper: Scroll to bottom of chat
    function scrollToBottom() {
        const messagesContainer = document.getElementById('angelMessages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // Helper: Format time
    function formatTime(date = new Date()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Helper: Escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Unread count functions
    function incrementUnreadCount() {
        unreadCount++;
        updateUnreadBadge();
    }

    function resetUnreadCount() {
        unreadCount = 0;
        updateUnreadBadge();
    }

    function updateUnreadBadge() {
        const badge = document.querySelector('.angel-chat-badge');
        if (!badge) return;
        
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Public API
    window.AngelChatWidget = {
        init: function(options = {}) {
            // Merge options with defaults
            Object.assign(config, options);
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        },
        
        // Public methods
        open: function() {
            if (!isOpen) toggleChat();
        },
        
        close: function() {
            if (isOpen) toggleChat();
        },
        
        // Update visitor info
        updateVisitorInfo: function(info) {
            Object.assign(visitorInfo, info);
            
            // Notify server about the update
            if (isInitialized) {
                sendMessage('Visitor information updated');
            }
        },
        
        // Destroy the widget
        destroy: function() {
            if (messagePolling) {
                clearInterval(messagePolling);
                messagePolling = null;
            }
            
            if (chatContainer && chatContainer.parentNode) {
                chatContainer.parentNode.removeChild(chatContainer);
            }
            
            isInitialized = false;
            isOpen = false;
        }
    };
})();

// Auto-initialize with default settings
window.AngelChatWidget.init();

// Add Font Awesome if not already loaded
(function() {
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        document.head.appendChild(link);
    }
})();
