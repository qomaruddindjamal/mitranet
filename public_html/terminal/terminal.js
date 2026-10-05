// ==============================================================================
// MitraNet Network OS - Web Terminal Package JS
// Interactive CLI & Diagnostic Shell Execution
// ==============================================================================

let terminalUser = 'admin';
let terminalCwd = '/home/admin';
let terminalHistory = [];
let terminalHistoryIdx = -1;
let terminalInitialized = false;

function initTerminal() {
  const input = document.getElementById('terminal-cmd-input');
  if (input) {
    input.focus();
    if (!terminalInitialized) {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          submitTerminalCmd();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          navigateHistory(1);
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          navigateHistory(-1);
        }
      });
      terminalInitialized = true;
    }
  }
  updateTerminalPrompt();
}

function updateTerminalPrompt() {
  const badge = document.getElementById('terminal-prompt-badge');
  const title = document.getElementById('terminal-session-title');
  const statusText = document.getElementById('term-status-text');
  const statusDot = document.getElementById('term-status-dot');

  const symbol = terminalUser === 'root' ? '#' : '$';
  const displayCwd = terminalCwd.replace('/home/admin', '~').replace('/root', '~');
  const promptStr = `${terminalUser}@mitranet-router:${displayCwd}${symbol}`;

  if (badge) {
    badge.textContent = promptStr;
    if (terminalUser === 'root') {
      badge.classList.add('root');
    } else {
      badge.classList.remove('root');
    }
  }

  if (title) {
    if (terminalUser === 'root') {
      title.textContent = `root@mitranet-router:~# (Superuser Privileges Active)`;
      title.style.color = 'var(--accent-rose)';
    } else {
      title.textContent = `admin@mitranet-router:~$ (Non-Root Session)`;
      title.style.color = 'var(--text-muted)';
    }
  }

  if (statusText) {
    if (terminalUser === 'root') {
      statusText.innerHTML = '<span class="ansi-rose" style="color:#f43f5e;font-weight:700;">Active User: ROOT (Superuser)</span> • Elevated via sudo su';
    } else {
      statusText.innerHTML = 'Active User: <b>admin</b> (Non-Root) • sudo privileges available';
    }
  }

  if (statusDot) {
    if (terminalUser === 'root') {
      statusDot.style.background = '#f43f5e';
      statusDot.style.boxShadow = '0 0 8px rgba(244, 63, 94, 0.7)';
    } else {
      statusDot.style.background = '#10b981';
      statusDot.style.boxShadow = '0 0 8px rgba(16, 185, 129, 0.7)';
    }
  }
}

function navigateHistory(direction) {
  if (terminalHistory.length === 0) return;
  const input = document.getElementById('terminal-cmd-input');
  if (!input) return;

  if (direction === 1) {
    if (terminalHistoryIdx < terminalHistory.length - 1) {
      terminalHistoryIdx++;
    }
  } else if (direction === -1) {
    if (terminalHistoryIdx > 0) {
      terminalHistoryIdx--;
    } else {
      terminalHistoryIdx = -1;
      input.value = '';
      return;
    }
  }

  if (terminalHistoryIdx >= 0 && terminalHistoryIdx < terminalHistory.length) {
    input.value = terminalHistory[terminalHistory.length - 1 - terminalHistoryIdx];
  }
}

async function submitTerminalCmd() {
  const input = document.getElementById('terminal-cmd-input');
  const sendBtn = document.getElementById('terminal-send-btn');
  if (!input) return;

  const cmd = input.value.trim();
  if (!cmd) return;

  terminalHistory.push(cmd);
  terminalHistoryIdx = -1;
  input.value = '';

  const historyEl = document.getElementById('terminal-history');
  const screenEl = document.getElementById('terminal-screen');

  if (cmd === 'clear') {
    clearTerminal();
    return;
  }

  const entryDiv = document.createElement('div');
  entryDiv.className = 'term-cmd-entry';
  const promptClass = terminalUser === 'root' ? 'term-cmd-prompt-text root' : 'term-cmd-prompt-text';
  const symbol = terminalUser === 'root' ? '#' : '$';
  const displayCwd = terminalCwd.replace('/home/admin', '~').replace('/root', '~');
  entryDiv.innerHTML = `<span class="${promptClass}">${terminalUser}@mitranet-router:${displayCwd}${symbol}</span> <span class="term-cmd-exec">${escapeHtml(cmd)}</span>`;
  historyEl.appendChild(entryDiv);

  if (sendBtn) sendBtn.disabled = true;

  try {
    const res = await apiFetch('/api/terminal/exec', {
      method: 'POST',
      body: JSON.stringify({
        command: cmd,
        user: terminalUser,
        cwd: terminalCwd
      })
    });

    if (res) {
      if (res.user) terminalUser = res.user;
      if (res.cwd) terminalCwd = res.cwd;
      updateTerminalPrompt();

      if (res.output) {
        const outDiv = document.createElement('div');
        outDiv.className = 'term-cmd-output';
        outDiv.textContent = res.output;
        historyEl.appendChild(outDiv);
      }
    }
  } catch (err) {
    const errDiv = document.createElement('div');
    errDiv.className = 'term-cmd-output ansi-rose';
    errDiv.textContent = `Execution failed: ${err.message}`;
    historyEl.appendChild(errDiv);
  } finally {
    if (sendBtn) sendBtn.disabled = false;
    if (screenEl) {
      screenEl.scrollTop = screenEl.scrollHeight;
    }
    input.focus();
  }
}

function runQuickCmd(cmd) {
  const input = document.getElementById('terminal-cmd-input');
  if (input) {
    input.value = cmd;
    submitTerminalCmd();
  }
}

function clearTerminal() {
  const historyEl = document.getElementById('terminal-history');
  if (historyEl) {
    historyEl.innerHTML = '';
  }
  const input = document.getElementById('terminal-cmd-input');
  if (input) input.focus();
}
