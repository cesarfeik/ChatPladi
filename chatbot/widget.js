/**
 * PLADIEX Chatbot Widget
 * Integración: <script src="/chatbot/widget.js" data-api="/chatbot/api/chat.php"></script>
 */
(function () {
  'use strict';

  const scriptTag = document.currentScript;
  const API_URL = scriptTag?.dataset?.api || '/api/chat.php';
  const LOGO_URL = scriptTag?.dataset?.logo || '';

  // ── Accesos rápidos con colores por categoría ───────────────────────────
  const QUICK_OPTIONS = [
    { label: 'PRE-DIAGNÓSTICO', value: 'Quiero hacer un pre-diagnóstico de mis síntomas', color: '#E05C8A' },
    { label: 'TIENDA', value: 'Quiero ir a la tienda de PLADIEX', color: '#E7BA11' },
    { label: 'CITAS', value: 'Quiero agendar o consultar una cita médica', color: '#5CB3C1' },
    { label: 'PRÉSTAMOS', value: 'Quiero información sobre préstamos o financiamiento', color: '#01587A' },
    { label: 'PREGUNTAS FRECUENTES', value: '¿Cuáles son las preguntas frecuentes de PLADIEX?', color: '#6b8fa0' },
  ];

  // ── Mapa de accesos directos para respuestas del bot ───────────────────
  const NAV_MAP = [
    { keywords: ['tienda', 'comprar', 'producto', 'mall', 'productos'],
      label: 'Ir a la Tienda', sub: 'Ver productos y ofertas',
      icon: '🛍️', url: 'https://pladiex.com/mall/', color: '#E7BA11' },
    { keywords: ['cita', 'consulta', 'agendar', 'médico', 'medicos'],
      label: 'Ver Médicos / Citas', sub: 'Agendar o consultar una cita',
      icon: '📅', url: 'https://pladiex.com/sistema/index.html', color: '#5CB3C1' },
    { keywords: ['perfil', 'mi cuenta', 'iniciar sesión'],
      label: 'Mi Perfil', sub: 'Accede a tu cuenta PLADIEX',
      icon: '👤', url: 'https://pladiex.com/sistema/index.html', color: '#01587A' },
    { keywords: ['préstamo', 'financiamiento', 'crédito'],
      label: 'Financiamiento', sub: 'Crédito médico desde $1,000 MXN',
      icon: '💳', url: 'https://pladiex.com/landing-registro/', color: '#E7BA11' },
    { keywords: ['capacitación', 'curso', 'formación', 'cursos'],
      label: 'Capacitación', sub: 'Cursos y formación en salud',
      icon: '🎓', url: 'https://pladiex.com/plataforma/cursos.html', color: '#5CB3C1' },
    { keywords: ['contacto', 'whatsapp', 'llamar'],
      label: 'Contactar por WhatsApp', sub: 'Atención lun–vie 8:00–20:00',
      icon: '💬', url: 'https://api.whatsapp.com/send?phone=+525632311545&text=¡Bienvenido%20a%20PLADIEX!%20¿En%20qué%20podemos%20ayudarte?', color: '#01587A' },
  ];

  let isOpen = false;
  let quickPanelOpen = true;
  let history = [];
  let isLoading = false;

  // ── Poppins ─────────────────────────────────────────────────────────────
  if (!document.querySelector('link[href*="Poppins"]')) {
    const f = document.createElement('link');
    f.rel = 'stylesheet';
    f.href = 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap';
    document.head.appendChild(f);
  }

  // ── CSS ──────────────────────────────────────────────────────────────────
  const cssLink = document.createElement('link');
  cssLink.rel = 'stylesheet';
  cssLink.href = scriptTag
    ? scriptTag.src.replace('widget.js', 'widget.css')
    : '/chatbot/widget.css';
  document.head.appendChild(cssLink);

  // ── Construir DOM ─────────────────────────────────────────────────────────
  function buildWidget() {
    // Botón flotante
    const btn = document.createElement('button');
    btn.id = 'plx-chat-btn';
    btn.className = 'plx-chat-btn';
    btn.setAttribute('aria-label', 'Abrir asistente PLADIEX');
    btn.innerHTML = `
      <svg class="plx-icon-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <svg class="plx-icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
      <span class="plx-badge" id="plx-badge" style="display:none">1</span>
    `;

    // Panel de chat
    const panel = document.createElement('div');
    panel.id = 'plx-chat-panel';
    panel.className = 'plx-chat-panel plx-hidden';
    panel.setAttribute('role', 'dialog');
    panel.innerHTML = `
      <div class="plx-header">
        <div class="plx-header-left">
          ${LOGO_URL
        ? `<img src="${LOGO_URL}" class="plx-header-logo" alt="PLADIEX" />`
        : `<div class="plx-avatar">A</div>`
      }
          <div>
            <div class="plx-header-name">Alex Ciencia</div>
            <div class="plx-header-sub">
              <span class="plx-dot"></span> ASISTENTE VIRTUAL · PLADIEX
            </div>
          </div>
        </div>
        <div class="plx-header-actions">
          <button class="plx-hdr-btn" id="plx-clear-btn" title="Reiniciar conversación">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
              <polyline points="1 4 1 10 7 10"/>
              <polyline points="23 20 23 14 17 14"/>
              <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
            </svg>
          </button>
          <button class="plx-hdr-btn" id="plx-close-btn" title="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15">
              <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="plx-messages" id="plx-messages"></div>

      <div class="plx-quick-section" id="plx-quick-section">
        <div class="plx-quick-label">ACCESOS RÁPIDOS</div>
        <div class="plx-quick-chips" id="plx-quick-chips"></div>
      </div>

      <div class="plx-input-area">
        <button class="plx-toggle-quick" id="plx-toggle-quick" title="Mostrar/ocultar accesos rápidos">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <input type="text" id="plx-input" class="plx-input"
          placeholder="Escribe tu mensaje..." maxlength="500" autocomplete="off" />
        <button class="plx-send-btn" id="plx-send-btn" aria-label="Enviar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>
        </button>
      </div>
      <div class="plx-footer-note">Impulsado por IA · No reemplaza consulta médica</div>
    `;

    document.body.appendChild(btn);
    document.body.appendChild(panel);

    // Listeners
    btn.addEventListener('click', toggleChat);
    document.getElementById('plx-close-btn').addEventListener('click', toggleChat);
    document.getElementById('plx-clear-btn').addEventListener('click', clearChat);
    document.getElementById('plx-toggle-quick').addEventListener('click', toggleQuickPanel);
    document.getElementById('plx-send-btn').addEventListener('click', handleSend);
    document.getElementById('plx-input').addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
    });

    renderQuickChips();
    addBotMessage('Hola, soy **Alex Ciencia**, tu asistente virtual de PLADIEX. ¿En qué te puedo ayudar hoy? Puedes elegir una opción o escribirme directamente.', []);
    document.getElementById('plx-badge').style.display = 'flex';
  }

  // ── Abrir / cerrar ───────────────────────────────────────────────────────
  function toggleChat() {
    isOpen = !isOpen;
    const panel = document.getElementById('plx-chat-panel');
    const iconChat = document.querySelector('.plx-icon-chat');
    const iconClose = document.querySelector('.plx-icon-close');

    if (isOpen) {
      panel.classList.replace('plx-hidden', 'plx-visible');
      iconChat.style.display = 'none';
      iconClose.style.display = 'block';
      document.getElementById('plx-badge').style.display = 'none';
      setTimeout(() => document.getElementById('plx-input')?.focus(), 280);
    } else {
      panel.classList.replace('plx-visible', 'plx-hidden');
      iconChat.style.display = 'block';
      iconClose.style.display = 'none';
    }
  }

  // ── Limpiar / reiniciar ──────────────────────────────────────────────────
  function clearChat() {
    history = [];
    document.getElementById('plx-messages').innerHTML = '';
    addBotMessage('Hola, soy **Alex Ciencia**, tu asistente virtual de PLADIEX. ¿En qué te puedo ayudar hoy? Puedes elegir una opción o escribirme directamente.', []);
    if (!quickPanelOpen) {
      quickPanelOpen = false;
      toggleQuickPanel();
    }
  }

  // ── Mostrar / ocultar accesos rápidos ────────────────────────────────────
  function toggleQuickPanel() {
    quickPanelOpen = !quickPanelOpen;
    const section = document.getElementById('plx-quick-section');
    const btn = document.getElementById('plx-toggle-quick');
    section.style.display = quickPanelOpen ? 'block' : 'none';
    btn.classList.toggle('plx-toggle-active', quickPanelOpen);
  }

  // ── Chips ────────────────────────────────────────────────────────────────
  function renderQuickChips() {
    const c = document.getElementById('plx-quick-chips');
    if (!c) return;
    c.innerHTML = '';
    QUICK_OPTIONS.forEach(({ label, value, color }) => {
      const chip = document.createElement('button');
      chip.className = 'plx-chip';
      chip.textContent = label;
      chip.style.background = color;
      chip.addEventListener('click', () => {
        document.getElementById('plx-input').value = value;
        handleSend();
      });
      c.appendChild(chip);
    });
  }

  // ── Enviar ───────────────────────────────────────────────────────────────
  async function handleSend() {
    if (isLoading) return;
    const input = document.getElementById('plx-input');
    const message = input.value.trim();
    if (!message) return;

    input.value = '';
    addUserMessage(message);
    history.push({ role: 'user', content: message });

    showTyping();
    isLoading = true;
    setInputDisabled(true);

    try {
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, history }),
      });
      const data = await res.json();
      removeTyping();

      const reply = data.reply || 'Lo siento, no pude obtener una respuesta.';
      // Usar links del backend; si no hay, detectar en el texto
      const links = (data.links?.length ? data.links : detectLinks(message + ' ' + reply));

      addBotMessage(reply, links);
      history.push({ role: 'assistant', content: reply });
    } catch {
      removeTyping();
      addBotMessage('Hubo un problema de conexión. Por favor intenta de nuevo.', []);
    } finally {
      isLoading = false;
      setInputDisabled(false);
    }
  }

  // ── Detectar links en el texto ───────────────────────────────────────────
  function detectLinks(text) {
    const lower = text.toLowerCase();
    const links = [];
    NAV_MAP.forEach(({ keywords, label, sub, icon, url, color }) => {
      if (keywords.some(k => lower.includes(k))) links.push({ label, sub, icon, url, color });
    });
    return links;
  }

  // ── Mensajes ─────────────────────────────────────────────────────────────
  function addUserMessage(text) {
    const c = document.getElementById('plx-messages');
    const div = document.createElement('div');
    div.className = 'plx-msg plx-msg-user';
    div.innerHTML = `<span class="plx-bubble plx-bubble-user">${escHtml(text)}</span>`;
    c.appendChild(div);
    scrollBottom();
  }

  function addBotMessage(text, links) {
    const c = document.getElementById('plx-messages');
    const div = document.createElement('div');
    div.className = 'plx-msg plx-msg-bot';

    const linksHtml = links?.length
      ? `<div class="plx-nav-links">${links.map(l =>
        `<a href="${escHtml(l.url)}" class="plx-nav-btn" target="_blank" rel="noopener">
          <span class="plx-nav-btn-icon" style="background:${l.color}22">${l.icon || '→'}</span>
          <span class="plx-nav-btn-text">
            <span class="plx-nav-btn-title">${escHtml(l.label)}</span>
            ${l.sub ? `<span class="plx-nav-btn-sub">${escHtml(l.sub)}</span>` : ''}
          </span>
          <span class="plx-nav-btn-arrow">›</span>
        </a>`
      ).join('')}</div>`
      : '';

    div.innerHTML = `
      <div class="plx-bot-row">
        <div class="plx-bot-icon">A</div>
        <div class="plx-bot-content">
          <div class="plx-bubble plx-bubble-bot">${formatText(text)}</div>
          ${linksHtml}
        </div>
      </div>`;
    c.appendChild(div);
    scrollBottom();
  }

  function showTyping() {
    const c = document.getElementById('plx-messages');
    const div = document.createElement('div');
    div.id = 'plx-typing';
    div.className = 'plx-msg plx-msg-bot';
    div.innerHTML = `
      <div class="plx-bot-row">
        <div class="plx-bot-icon">A</div>
        <span class="plx-bubble plx-bubble-bot plx-typing">
          <span></span><span></span><span></span>
        </span>
      </div>`;
    c.appendChild(div);
    scrollBottom();
  }

  function removeTyping() { document.getElementById('plx-typing')?.remove(); }
  function scrollBottom() { const e = document.getElementById('plx-messages'); if (e) e.scrollTop = e.scrollHeight; }
  function setInputDisabled(v) {
    const b = document.getElementById('plx-send-btn');
    const i = document.getElementById('plx-input');
    if (b) b.disabled = v;
    if (i) i.disabled = v;
  }
  function escHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function formatText(s) {
    return escHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', buildWidget);
  else buildWidget();
})();
