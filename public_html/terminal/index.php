<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Interactive Web Terminal
 * Modern White Enterprise Design System
 */
$pageTitle = 'Interactive Web Terminal';
$pageSubtitle = 'Enterprise Command-Line Interface (CLI) & Diagnostic Shell Execution';

?>

        <section id="tab-terminal" class="tab-pane active">
          <div class="terminal-wrapper card">
            <div class="terminal-toolbar">
              <div class="terminal-titlebar">
                <span class="term-dot dot-red"></span>
                <span class="term-dot dot-yellow"></span>
                <span class="term-dot dot-green"></span>
                <span class="term-session-title" id="terminal-session-title">admin@mitranet-router:~$ (Non-Root
                  Session)</span>
              </div>
              <div class="terminal-quick-actions">
                <span class="quick-label">Quick Commands:</span>
                <button class="btn btn-xs btn-outline" onclick="runQuickCmd('mitranet')">mitranet</button>
                <button class="btn btn-xs btn-outline" onclick="runQuickCmd('ip a')">ip a</button>
                <button class="btn btn-xs btn-outline" onclick="runQuickCmd('network eoip list')">eoip list</button>
                <button class="btn btn-xs btn-outline" onclick="runQuickCmd('sudo su')">sudo su</button>
                <button class="btn btn-xs btn-outline" onclick="runQuickCmd('exit')">exit</button>
                <button class="btn btn-xs btn-outline" onclick="clearTerminal()">Clear</button>
              </div>
            </div>

            <div class="terminal-screen" id="terminal-screen">
              <div class="terminal-history" id="terminal-history">
                <div class="term-line term-banner">
                  <pre class="banner-art">
   __  __ _ _             _   _      _   
  |  \/  (_) |_ _ __ __ _| \ | | ___| |_ 
  | |\/| | | __| '__/ _` |  \| |/ _ \ __|
  | |  | | | |_| | | (_| | |\  |  __/ |_ 
  |_|  |_|_|\__|_|  \__,_|_| \_|\___|\__|
</pre>
                  <div class="term-welcome-text">
                    <span class="ansi-emerald" style="color:#10b981;font-weight:700;">MitraNet Rinjani Network OS v1.0.0-LTS (Debian GNU/Linux 13 Trixie)</span><br>
                    <span class="ansi-muted" style="color:var(--text-muted);">Wire-Speed Edge Routing • BBRv2 • Smart CAKE • EoIP • Vether • FastPath</span><br><br>
                    <span class="ansi-yellow" style="color:#f59e0b;">• Default WebUI Session: <b style="color:var(--text-main);">admin</b> (UID 1000)</span><br>
                    <span class="ansi-yellow" style="color:#f59e0b;">• Privilege Elevation: Type <b style="color:var(--accent-primary);">sudo su</b> to enter root shell.</span><br>
                    <span class="ansi-yellow" style="color:#f59e0b;">• Root Password: <b style="color:var(--text-main);">mitranet</b></span><br>
                    <span class="ansi-muted" style="color:var(--text-muted);">• Use Up / Down arrows for command history.</span>
                  </div>
                </div>
              </div>

              <div class="terminal-input-line">
                <span class="terminal-prompt-badge" id="terminal-prompt-badge">admin@mitranet-router:~$</span>
                <input type="text" id="terminal-cmd-input" class="terminal-cmd-input" autocomplete="off"
                  spellcheck="false" placeholder="Enter shell command (e.g. mitranet, sudo su)...">
                <button class="btn btn-sm btn-primary terminal-send-btn" id="terminal-send-btn"
                  onclick="submitTerminalCmd()">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                  Execute
                </button>
              </div>
            </div>

            <div class="terminal-footer-info">
              <div class="term-status-chip">
                <span class="status-dot pulsing" id="term-status-dot"></span>
                <span id="term-status-text">Active User: admin (Non-Root) • sudo privileges available</span>
              </div>
              <div class="term-hint">
                Interactive Web Terminal • Default user: <code>admin</code> • Switch to root: <code>sudo su</code>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  initTerminal();
});
</script>


