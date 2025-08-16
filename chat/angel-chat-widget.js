(function() {
  // Inject styles
  const style = document.createElement('style');
  style.textContent = `
    .angel-chat-btn {
      position: fixed; bottom: 24px; right: 24px; z-index: 9999;
      width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg,#2c3e50,#3498db);
      color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      cursor: pointer; font-size: 2rem; border: none;
    }
    .angel-chat-widget {
      position: fixed; bottom: 90px; right: 24px; z-index: 9999;
      width: 340px; max-width: 95vw; height: 440px; max-height: 80vh;
      background: #fff; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      display: flex; flex-direction: column; overflow: hidden; font-family: inherit;
      opacity: 0; pointer-events: none; transition: opacity 0.2s;
    }
    .angel-chat-widget.active { opacity: 1; pointer-events: auto; }
    .angel-chat-header {
      background: linear-gradient(135deg,#2c3e50,#3498db); color: #fff;
      padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;
    }
    .angel-chat-header button { background: none; border: none; color: #fff; font-size: 1.2rem; cursor: pointer; }
    .angel-chat-body { flex: 1; padding: 16px; overflow-y: auto; background: #f8f9fa; }
    .angel-chat-footer { padding: 12px; border-top: 1px solid #eee; background: #fff; }
    .angel-chat-input { width: 100%; padding: 8px 12px; border-radius: 18px; border: 1px solid #ddd; }
    .angel-chat-send { background: #3498db; color: #fff; border: none; border-radius: 18px; padding: 8px 18px; margin-left: 8px; cursor: pointer; }
    .angel-chat-msg { margin: 8px 0; }
    .angel-chat-msg.visitor { text-align: right; }
    .angel-chat-msg.agent { text-align: left; }
    .angel-chat-bubble {
      display: inline-block; padding: 8px 14px; border-radius: 16px; max-width: 80%; font-size: 0.97rem;
    }
    .angel-chat-msg.visitor .angel-chat-bubble { background: #3498db; color: #fff; border-bottom-right-radius: 4px; }
    .angel-chat-msg.agent .angel-chat-bubble { background: #fff; color: #333; border: 1px solid #eee; border-bottom-left-radius: 4px; }
    .angel-chat-form-group { margin-bottom: 10px; }
    .angel-chat-form-group label { display: block; margin-bottom: 3px; font-size: 0.95em; }
    .angel-chat-form-group input { width: 100%; padding: 7px 10px; border-radius: 6px; border: 1px solid #ccc; }
    .angel-chat-welcome { text-align: center; color: #2c3e50; margin-bottom: 10px; }
  `;
  document.head.appendChild(style);

  // Create chat button
  const btn = document.createElement('button');
  btn.className = 'angel-chat-btn';
  btn.innerHTML = '<i class="fa fa-comment-dots"></i>';
  document.body.appendChild(btn);

  // Create chat widget
  const widget = document.createElement('div');
  widget.className = 'angel-chat-widget';
  widget.innerHTML = `
    <div class="angel-chat-header">
      <span>Chat with us</span>
      <button title="Close" type="button">&times;</button>
    </div>
    <div class="angel-chat-body">
      <div class="angel-chat-welcome">
        <div style="font-size:2.2rem;margin-bottom:8px;"><i class="fa fa-comments"></i></div>
        <div style="font-weight:600;">Welcome to Angel Stones</div>
        <div style="font-size:0.97em;color:#666;margin-bottom:10px;">Please enter your details to start chatting.</div>
      </div>
      <form id="angel-chat-user-form">
        <div class="angel-chat-form-group">
          <label for="angel-chat-name">Your Name *</label>
          <input type="text" id="angel-chat-name" required autocomplete="name">
        </div>
        <div class="angel-chat-form-group">
          <label for="angel-chat-email">Email</label>
          <input type="email" id="angel-chat-email" autocomplete="email">
        </div>
        <div class="angel-chat-form-group">
          <label for="angel-chat-phone">Phone</label>
          <input type="tel" id="angel-chat-phone" autocomplete="tel">
        </div>
        <button type="submit" class="angel-chat-send" style="width:100%;">Start Chat</button>
      </form>
      <div id="angel-chat-messages" style="display:none;"></div>
    </div>
    <div class="angel-chat-footer" style="display:none;">
      <form id="angel-chat-msg-form" style="display:flex;">
        <input type="text" class="angel-chat-input" id="angel-chat-input" placeholder="Type your message..." autocomplete="off">
        <button type="submit" class="angel-chat-send"><i class="fa fa-paper-plane"></i></button>
      </form>
    </div>
  `;
  document.body.appendChild(widget);

  // Elements
  const closeBtn = widget.querySelector('.angel-chat-header button');
  const userForm = widget.querySelector('#angel-chat-user-form');
  const nameInput = widget.querySelector('#angel-chat-name');
  const emailInput = widget.querySelector('#angel-chat-email');
  const phoneInput = widget.querySelector('#angel-chat-phone');
  const welcome = widget.querySelector('.angel-chat-welcome');
  const messagesDiv = widget.querySelector('#angel-chat-messages');
  const footer = widget.querySelector('.angel-chat-footer');
  const msgForm = widget.querySelector('#angel-chat-msg-form');
  const msgInput = widget.querySelector('#angel-chat-input');
  let chatStarted = false;
  let customerName = '';
  let chatId = localStorage.getItem('angel_team_chat_id');

  // Show/hide widget
  btn.onclick = () => { widget.classList.add('active'); };
  closeBtn.onclick = () => { widget.classList.remove('active'); };

  // Start chat
  userForm.onsubmit = async function(e) {
    e.preventDefault();
    customerName = nameInput.value.trim();
    if (!customerName) { nameInput.focus(); return; }
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();
    userForm.style.display = 'none';
    welcome.style.display = 'none';
    messagesDiv.style.display = '';
    footer.style.display = '';
    addSystemMsg(`Welcome, ${customerName}! How can we help you today?`);
    // Create chat on backend
    if (!chatId) {
      try {
        const res = await fetch('/chat/team_chat.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'create_chat',
            customer_name: customerName,
            customer_email: email,
            customer_phone: phone
          })
        });
        const data = await res.json();
        if (data.success && data.data && data.data.id) {
          chatId = data.data.id;
          localStorage.setItem('angel_team_chat_id', chatId);
        } else {
          addSystemMsg('Could not connect to support. Please try again later.');
        }
      } catch {
        addSystemMsg('Could not connect to support. Please try again later.');
      }
    }
    chatStarted = true;
  };

  // Send message
  msgForm.onsubmit = async function(e) {
    e.preventDefault();
    if (!chatStarted) return;
    const msg = msgInput.value.trim();
    if (!msg) return;
    addMsg(msg, 'visitor');
    msgInput.value = '';
    if (chatId) {
      try {
        await fetch('/chat/team_chat.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'send_message',
            chat_id: chatId,
            message: msg,
            sender_name: customerName,
            is_system: false
          })
        });
      } catch {
        addSystemMsg('Message not sent. Please try again.');
      }
    }
  };

  // Add message to UI
  function addMsg(text, type) {
    const div = document.createElement('div');
    div.className = 'angel-chat-msg ' + (type === 'visitor' ? 'visitor' : 'agent');
    div.innerHTML = `<span class="angel-chat-bubble">${text}</span>`;
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }
  function addSystemMsg(text) {
    const div = document.createElement('div');
    div.className = 'angel-chat-msg agent';
    div.innerHTML = `<span class="angel-chat-bubble" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;">${text}</span>`;
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  }
})();