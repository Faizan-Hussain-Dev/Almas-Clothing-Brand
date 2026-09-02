<?php
/**
 * chatbot_embed.php
 * Include this file in any PHP page to add the Almas AI chatbot.
 *
 * Usage — add this ONE line before </body> in any page:
 *   <?php include('chatbot_embed.php'); ?>
 *
 * Adjust $chatbot_base_path to match where you placed the frontend/ folder.
 */

$chatbot_base_path = "/Clothing Brand/Almas_Chatbot";
?>

<!-- ═══════ ALMAS AI CHATBOT WIDGET START ═══════ -->
<link rel="stylesheet" href="<?= $chatbot_base_path ?>/chatbot.css">

<!-- Floating Toggle Button -->
<button id="almas-chat-toggle" aria-label="Open AI Assistant">
  <svg class="icon-chat" viewBox="0 0 24 24"><path d="M20 2H4C2.9 2 2 2.9 2 4v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 10H6v-2h12v2zm0-3H6V7h12v2z"/></svg>
  <svg class="icon-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
</button>

<!-- Chat Widget -->
<div id="almas-chat-widget" role="dialog" aria-label="Almas Clothing AI Assistant">
  <div id="almas-chat-header">
    <div class="almas-avatar">A</div>
    <div class="almas-header-info">
      <h4>Almas Assistant</h4>
      <span><span class="status-dot"></span> Online — Ready to help</span>
    </div>
  </div>
  <div id="almas-chat-messages" aria-live="polite"></div>
  <div id="almas-quick-replies" class="almas-quick-replies"></div>
  <div id="almas-chat-footer">
    <div class="almas-input-row">
      <textarea id="almas-input" placeholder="Ask about products, sizes, shipping..." rows="1" aria-label="Type your message"></textarea>
      <button id="almas-send-btn" aria-label="Send">
        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
      </button>
    </div>
    <div class="almas-footer-note">Powered by Almas AI · Answers may not always be perfect</div>
  </div>
</div>

<script src="<?= $chatbot_base_path ?>/chatbot.js"></script>
<!-- ═══════ ALMAS AI CHATBOT WIDGET END ═══════ -->
