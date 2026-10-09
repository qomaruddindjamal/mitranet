<?php
/*
 * diag_terminal.php - MitraNet DIRECT: Terminal
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

// Handle AJAX terminal execution
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
    header('Content-Type: application/json');
    $cmd = trim($_POST['cmd'] ?? '');
    $cwd = trim($_POST['cwd'] ?? '');

    if ($cmd === '__INIT__') {
        $res = MitraNetApi::execCommand('', '');
        echo json_encode([
            'success' => true,
            'output' => '',
            'cwd' => $res['data']['cwd'] ?? '/home/admin',
            'user' => $res['data']['user'] ?? 'admin'
        ]);
        exit;
    }

    if ($cmd === '') {
        echo json_encode(['success' => true, 'output' => '', 'cwd' => $cwd]);
        exit;
    }

    if ($cmd === 'clear') {
        echo json_encode(['success' => true, 'output' => '__CLEAR__', 'cwd' => $cwd]);
        exit;
    }

    $res = MitraNetApi::execCommand($cmd, $cwd);
    if ($res['status'] === 200 && isset($res['data'])) {
        echo json_encode([
            'success' => $res['data']['success'] ?? false,
            'output' => $res['data']['output'] ?? '',
            'cwd' => $res['data']['cwd'] ?? $cwd,
            'user' => $res['data']['user'] ?? 'admin'
        ]);
    } else {
        $err = $res['data']['error'] ?? 'Gagal mengeksekusi perintah pada shell Linux.';
        echo json_encode(['success' => false, 'output' => "Error: {$err}\n", 'cwd' => $cwd]);
    }
    exit;
}

$pgtitle = array("DIRECT", "Terminal");
$selected_menu = "direct";
require_once(__DIR__ . '/../includes/head.inc');
?>

<style>
/* Reset and Seamless Layout Alignment */
body {
    background-color: #0b0d11 !important;
}

/* Full Terminal Container */
.terminal-container {
    width: 100%;
    min-height: 520px;
    height: calc(100vh - 180px);
    display: flex;
    flex-direction: column;
    background-color: #0b0d11;
    color: #c9d1d9;
    font-family: "Fira Code", "Cascadia Code", "JetBrains Mono", Menlo, Consolas, "Liberation Mono", Courier, monospace;
    font-size: 13.5px;
    line-height: 1.5;
    box-sizing: border-box;
    border-radius: 6px;
    border: 1px solid #21262d;
    overflow: hidden;
    margin-bottom: 20px;
}

/* Console Header Bar */
.terminal-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #161b22;
    border-bottom: 1px solid #30363d;
    padding: 6px 14px;
}

.terminal-tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
}

.terminal-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 4px 11px;
    background-color: #0b0d11;
    color: #58a6ff;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    border: 1px solid #283754;
}

.terminal-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.term-btn {
    background-color: #21262d;
    color: #8b949e;
    border: 1px solid #30363d;
    padding: 3px 9px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s ease;
}

.term-btn:hover {
    background-color: #30363d;
    color: #f0f6fc;
}

/* Interactive Terminal Viewport */
.terminal-viewport {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px 24px 20px;
    background-color: #0b0d11;
    cursor: text;
}

.terminal-viewport::-webkit-scrollbar {
    width: 8px;
}
.terminal-viewport::-webkit-scrollbar-thumb {
    background: #21262d;
    border-radius: 4px;
}

/* Terminal Content Stream */
.terminal-content {
    white-space: pre-wrap;
    word-break: break-all;
    margin-bottom: 4px;
}

.term-history-entry {
    margin-bottom: 2px;
}

.term-cmd-echo {
    color: #56d364;
    font-weight: 600;
    display: block;
}

.term-cmd-output {
    color: #c9d1d9;
    margin: 2px 0 6px 0;
    display: block;
}

.term-cmd-error {
    color: #f85149;
    font-weight: 500;
}

/* Active Inline Input Prompt */
.terminal-input-line {
    display: flex;
    align-items: center;
    width: 100%;
    margin-top: 2px;
    line-height: 1.5;
}

.term-prompt {
    font-weight: 700;
    white-space: nowrap;
    user-select: none;
    margin-right: 6px;
}

.prompt-user {
    color: #56d364;
}

.prompt-cwd {
    color: #79c0ff;
}

.prompt-char {
    color: #56d364;
}

.term-input-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
}

.term-input {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    padding: 0;
    margin: 0;
    color: #f0f6fc;
    font-family: inherit;
    font-size: inherit;
    line-height: inherit;
    font-weight: 500;
    caret-color: #58a6ff;
}

.term-spinner {
    display: none;
    color: #e3b341;
    margin-left: 8px;
    font-size: 13px;
}
</style>

<div class="terminal-container" id="terminal-container">
    <!-- Slim Modern Console Bar -->
    <div class="terminal-header-bar">
        <div class="terminal-tabs-wrapper">
            <div class="terminal-tab">
                <i class="fa-solid fa-terminal"></i>
                <span id="term-tab-title">Terminal Shell</span>
            </div>
        </div>
        <div class="terminal-actions">
            <button type="button" class="term-btn" id="btn-font-dec" title="Kecilkan Font">A-</button>
            <button type="button" class="term-btn" id="btn-font-inc" title="Besarkan Font">A+</button>
            <button type="button" class="term-btn" id="btn-clear" title="Bersihkan Layar (Ctrl+L)">
                <i class="fa-solid fa-eraser"></i> Clear
            </button>
            <button type="button" class="term-btn" id="btn-reset" title="Reset Konsol">
                <i class="fa-solid fa-rotate-right"></i> Reset
            </button>
        </div>
    </div>

    <!-- Terminal Viewport Area -->
    <div class="terminal-viewport" id="term-viewport">
        <div class="terminal-content" id="term-content">
            <div class="terminal-title">MitraNet Rinjani 1.0.2 Management Shell (Debian 13 Trixie x86_64)</div>
            <div class="terminal-hint">Running as standard system user. Use <code>sudo &lt;command&gt;</code> for root privileges.</div>
        </div>

        <div class="terminal-input-line" id="term-input-line">
            <div class="term-prompt">
                <span class="prompt-user" id="prompt-user">admin@mitranet</span>:<span class="prompt-cwd" id="prompt-cwd">~</span><span class="prompt-char" id="prompt-char">$ </span>
            </div>
            <div class="term-input-wrapper">
                <input type="text" class="term-input" id="term-input" autocomplete="off" spellcheck="false" autofocus />
                <i class="fa-solid fa-spinner fa-spin term-spinner" id="term-spinner"></i>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
function initTerminal() {
    var currentUser = 'admin';
    var cwd = '';
    var homeDir = '';
    var history = [];
    var historyIdx = -1;
    var fontSize = 13.5;
    var isExecuting = false;

    function getPromptChar() {
        return (currentUser === 'root') ? '# ' : '$ ';
    }

    function getDisplayCwd() {
        if (!cwd || cwd === homeDir) return '~';
        if (currentUser === 'root' && cwd === '/root') return '~';
        return cwd;
    }

    function updatePromptDisplay() {
        var pChar = getPromptChar();
        var dispCwd = getDisplayCwd();
        $('#prompt-user').text(currentUser + '@mitranet');
        $('#prompt-cwd').text(dispCwd);
        $('#prompt-char').text(pChar);
        $('#term-tab-title').text(currentUser + '@mitranet: ' + (dispCwd === '~' ? (homeDir || '~') : dispCwd));
    }

    function scrollToBottom() {
        var vp = document.getElementById('term-viewport');
        if (vp) vp.scrollTop = vp.scrollHeight;
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }

    // Initialize session info from backend
    $.ajax({
        url: '/diag_terminal.php',
        type: 'POST',
        dataType: 'json',
        data: { ajax: '1', cmd: '__INIT__', cwd: '' },
        success: function(resp) {
            if (resp) {
                if (resp.user) currentUser = resp.user;
                if (resp.cwd) {
                    cwd = resp.cwd;
                    homeDir = resp.cwd;
                }
                updatePromptDisplay();
            }
        }
    });

    function executeCommand(cmd) {
        cmd = $.trim(cmd);
        if (!cmd) return;

        history.push(cmd);
        historyIdx = history.length;

        var displayCwd = getDisplayCwd();
        var promptEcho = '<div class="term-history-entry"><span class="term-cmd-echo"><span class="prompt-user">' + escapeHtml(currentUser) + '@mitranet</span>:<span class="prompt-cwd">' + escapeHtml(displayCwd) + '</span><span class="prompt-char">' + escapeHtml(getPromptChar()) + '</span>' + escapeHtml(cmd) + '</span></div>';
        $('#term-content').append(promptEcho);
        $('#term-input').val('');
        scrollToBottom();

        if (cmd === 'clear') {
            $('#term-content').empty();
            scrollToBottom();
            return;
        }

        if (cmd === 'reset') {
            $('#term-content').empty();
            cwd = homeDir;
            updatePromptDisplay();
            history = [];
            historyIdx = -1;
            scrollToBottom();
            return;
        }

        isExecuting = true;
        $('#term-spinner').show();
        $('#term-input').prop('disabled', true);

        $.ajax({
            url: '/diag_terminal.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: '1',
                cmd: cmd,
                cwd: cwd
            },
            success: function(resp) {
                isExecuting = false;
                $('#term-spinner').hide();
                $('#term-input').prop('disabled', false).focus();

                if (resp && resp.output === '__CLEAR__') {
                    $('#term-content').empty();
                } else if (resp && resp.output) {
                    var cls = (!resp.success) ? 'term-cmd-output term-cmd-error' : 'term-cmd-output';
                    var outHtml = '<span class="' + cls + '">' + escapeHtml(resp.output) + '</span>';
                    $('#term-content').append(outHtml);
                }

                if (resp && resp.cwd) {
                    cwd = resp.cwd;
                }
                if (resp && resp.user) {
                    currentUser = resp.user;
                }
                updatePromptDisplay();
                scrollToBottom();
            },
            error: function(xhr, status, err) {
                isExecuting = false;
                $('#term-spinner').hide();
                $('#term-input').prop('disabled', false).focus();
                var errHtml = '<span class="term-cmd-output term-cmd-error">Error: ' + escapeHtml(err || status) + "\n</span>";
                $('#term-content').append(errHtml);
                scrollToBottom();
            }
        });
    }

    $('#term-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            executeCommand($(this).val());
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (history.length > 0) {
                if (historyIdx > 0) historyIdx--;
                $(this).val(history[historyIdx] || '');
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (history.length > 0) {
                if (historyIdx < history.length - 1) {
                    historyIdx++;
                    $(this).val(history[historyIdx] || '');
                } else {
                    historyIdx = history.length;
                    $(this).val('');
                }
            }
        } else if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            $('#term-content').empty();
            scrollToBottom();
        }
    });

    $('#term-viewport').on('click', function() {
        $('#term-input').focus();
    });

    $('#btn-clear').on('click', function() {
        $('#term-content').empty();
        $('#term-input').focus();
    });

    $('#btn-reset').on('click', function() {
        $('#term-content').empty();
        cwd = '/root';
        $('#prompt-cwd').text('~');
        $('#term-input').val('').focus();
    });

    $('#btn-font-inc').on('click', function() {
        if (fontSize < 20) {
            fontSize += 1.5;
            $('#terminal-container').css('font-size', fontSize + 'px');
        }
    });

    $('#btn-font-dec').on('click', function() {
        if (fontSize > 10) {
            fontSize -= 1.5;
            $('#terminal-container').css('font-size', fontSize + 'px');
        }
    });
}

$(document).ready(initTerminal);
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
