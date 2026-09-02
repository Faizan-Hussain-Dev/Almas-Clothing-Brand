
(function () {
  "use strict";

  // ─── Configuration ────────────────────────────────────────────────────────
  const API_URL      = "http://localhost:8000/chat";
  const FETCH_TIMEOUT_MS = 35000;    // ⚡ FIX A — was 15s; phi3:mini cold = up to 25s
  const SLOW_MSG_AFTER_MS = 8000;   // show "Still thinking…" after 8s

  const QUICK_REPLIES = [
    "What is your return policy?",
    "How we can Track my order",
    "Do you offer cash on delivery",
    "Which Men sizes are available?",
    "What is the price of Sherwani?",
  ];

  const WELCOME_MSG =
    "Assalamu Alaikum! 👋 I'm your Almas Clothing assistant. " +
    "Ask me about products, sizes, shipping, or our return policy.";

  // ─── State ────────────────────────────────────────────────────────────────
  let isOpen    = false;
  let isLoading = false;
  const localCache = {};       // JS-side cache — instant for repeated questions

  // ─── DOM ─────────────────────────────────────────────────────────────────
  const toggle       = document.getElementById("almas-chat-toggle");
  const widget       = document.getElementById("almas-chat-widget");
  const messagesEl   = document.getElementById("almas-chat-messages");
  const inputEl      = document.getElementById("almas-input");
  const sendBtn      = document.getElementById("almas-send-btn");
  const quickRepliesEl = document.getElementById("almas-quick-replies");

  // ─── Init ─────────────────────────────────────────────────────────────────
  function init() {
    renderQuickReplies();
    appendBotMessage(WELCOME_MSG);
    bindEvents();
  }

  function renderQuickReplies() {
    quickRepliesEl.innerHTML = "";
    QUICK_REPLIES.forEach((q) => {
      const btn = document.createElement("button");
      btn.className   = "almas-quick-btn";
      btn.textContent = q;
      btn.addEventListener("click", () => { if (!isLoading) sendMessage(q); });
      quickRepliesEl.appendChild(btn);
    });
  }

  function bindEvents() {
    toggle.addEventListener("click", toggleWidget);
    sendBtn.addEventListener("click", handleSend);
    inputEl.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); handleSend(); }
    });
    inputEl.addEventListener("input", () => {
      inputEl.style.height = "auto";
      inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + "px";
    });
    document.addEventListener("click", (e) => {
      if (isOpen && !widget.contains(e.target) && !toggle.contains(e.target))
        closeWidget();
    });
  }

  // ─── Widget toggle ────────────────────────────────────────────────────────
  function toggleWidget() { isOpen ? closeWidget() : openWidget(); }

  function openWidget() {
    isOpen = true;
    widget.classList.add("open");
    toggle.classList.add("open");
    inputEl.focus();
    scrollToBottom();
  }

  function closeWidget() {
    isOpen = false;
    widget.classList.remove("open");
    toggle.classList.remove("open");
  }

  // ─── Send ─────────────────────────────────────────────────────────────────
  function handleSend() {
    const q = inputEl.value.trim();
    // ⚡ FIX B — guard against double-send (button clicked twice quickly)
    if (!q || isLoading) return;
    sendMessage(q);
  }

  async function sendMessage(question) {
    if (!question || isLoading) return;

    inputEl.value = "";
    inputEl.style.height = "auto";
    appendUserMessage(question);
    scrollToBottom();

    // JS-side cache → zero latency for repeated questions
    const cacheKey = question.toLowerCase().trim();
    if (localCache[cacheKey]) {
      appendBotMessage(localCache[cacheKey]);
      scrollToBottom();
      return;
    }

    setLoading(true);
    const typingId = showTypingIndicator();

    // ⚡ FIX C — "Still working…" nudge so user doesn't think it crashed
    const slowTimer = setTimeout(() => {
      updateTypingText(typingId, "Still thinking… (AI is processing)");
    }, SLOW_MSG_AFTER_MS);

    try {
      // ⚡ FIX D — 35s timeout instead of 15s
      const controller = new AbortController();
      const timeoutId  = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);

      const t0       = performance.now();
      const response = await fetch(API_URL, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ question }),
        signal:  controller.signal,
      });
      clearTimeout(timeoutId);

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data      = await response.json();
      const answer    = data.answer || "Sorry, I couldn't get a response.";
      const elapsed   = data.elapsed_ms ? ` (${(data.elapsed_ms/1000).toFixed(1)}s)` : "";

      localCache[cacheKey] = answer;

      clearTimeout(slowTimer);
      removeTypingIndicator(typingId);
      appendBotMessage(answer, elapsed);

    } catch (err) {
      clearTimeout(slowTimer);
      removeTypingIndicator(typingId);

      let msg;
      if (err.name === "AbortError") {
        // ⚡ FIX E — better timeout message with actionable advice
        msg = "The AI is taking longer than usual on your device. "
            + "Try a simpler question, or check that Ollama is running (ollama serve).";
      } else if (!navigator.onLine) {
        msg = "You appear to be offline. Please check your internet connection.";
      } else {
        msg = "Cannot reach the chatbot server. "
            + "Make sure api.py is running: python api.py";
      }
      appendBotMessage(msg);
    } finally {
      setLoading(false);
      scrollToBottom();
    }
  }

  // ─── Message rendering ────────────────────────────────────────────────────
  function appendUserMessage(text) {
    messagesEl.appendChild(createMessageEl("user", text));
  }

  function appendBotMessage(text, note = "") {
    messagesEl.appendChild(createMessageEl("bot", text, note));
  }

  function createMessageEl(role, text, note = "") {
    const div = document.createElement("div");
    div.className = `almas-msg ${role}`;

    const bubble = document.createElement("div");
    bubble.className   = "almas-bubble";
    bubble.textContent = text;

    const meta = document.createElement("div");
    meta.className   = "almas-time";
    meta.textContent = getTime() + note;

    div.appendChild(bubble);
    div.appendChild(meta);
    return div;
  }

  // ─── Typing indicator ─────────────────────────────────────────────────────
  function showTypingIndicator() {
    const id  = "typing-" + Date.now();
    const div = document.createElement("div");
    div.className = "almas-msg bot";
    div.id        = id;

    const typing = document.createElement("div");
    typing.className = "almas-typing";
    typing.innerHTML = "<span></span><span></span><span></span>"
                     + "<span class='typing-label' style='margin-left:6px;font-size:12px;color:#9A8866'></span>";

    div.appendChild(typing);
    messagesEl.appendChild(div);
    scrollToBottom();
    return id;
  }

  function updateTypingText(id, text) {
    const el = document.getElementById(id);
    if (!el) return;
    const label = el.querySelector(".typing-label");
    if (label) label.textContent = text;
  }

  function removeTypingIndicator(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  // ─── Utilities ────────────────────────────────────────────────────────────
  function setLoading(state) {
    isLoading        = state;
    sendBtn.disabled = state;
    inputEl.disabled = state;
    // ⚡ FIX F — grey out quick reply buttons while loading
    document.querySelectorAll(".almas-quick-btn").forEach(b => b.disabled = state);
  }

  function scrollToBottom() {
    requestAnimationFrame(() => { messagesEl.scrollTop = messagesEl.scrollHeight; });
  }

  function getTime() {
    return new Date().toLocaleTimeString("en-PK", { hour: "2-digit", minute: "2-digit" });
  }

  // ─── Boot ─────────────────────────────────────────────────────────────────
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
