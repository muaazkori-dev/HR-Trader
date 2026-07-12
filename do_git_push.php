<?php
/**
 * HR Traders - One-Click Git Deployment Center
 * Accessible locally at: http://127.0.0.1/HR%20Traders/do_git_push.php
 */

if (isset($_GET['action']) && $_GET['action'] === 'push') {
    header('Content-Type: application/json; charset=utf-8');
    
    $commitMessage = isset($_POST['message']) && trim($_POST['message']) !== '' 
        ? trim($_POST['message']) 
        : 'Update storefront layout & features';
        
    $escapedMessage = escapeshellarg($commitMessage);
    
    chdir(__DIR__);
    
    $steps = [];
    
    // Step 1: Config
    $out1 = []; $out2 = [];
    exec('git config user.email "muaazkori-dev@users.noreply.github.com" 2>&1', $out1, $code1);
    exec('git config user.name "muaazkori-dev" 2>&1', $out2, $code2);
    $steps[] = [
        'step' => 'Configure Git Author Identity',
        'success' => ($code1 === 0 && $code2 === 0),
        'log' => implode("\n", array_merge($out1, $out2)) ?: "Git author configuration initialized."
    ];
    
    // Step 2: Add
    $out3 = [];
    exec('git add . 2>&1', $out3, $code3);
    $steps[] = [
        'step' => 'Stage all modified files (git add .)',
        'success' => ($code3 === 0),
        'log' => implode("\n", $out3) ?: "All modified and new files staged."
    ];
    
    // Step 3: Commit
    $out4 = [];
    exec("git commit -m $escapedMessage 2>&1", $out4, $code4);
    $commitLog = implode("\n", $out4);
    $commitSuccess = ($code4 === 0 || strpos($commitLog, 'nothing to commit') !== false || strpos($commitLog, 'clean') !== false);
    $steps[] = [
        'step' => 'Create Git Commit',
        'success' => $commitSuccess,
        'log' => $commitLog ?: "Working tree clean, no new changes committed."
    ];
    
    // Step 4: Push
    $pushOutput = [];
    exec('git push origin main 2>&1', $pushOutput, $code5);
    $pushLog = implode("\n", $pushOutput);
    $pushSuccess = ($code5 === 0);
    
    // Fallback force push if initial rejected
    if (!$pushSuccess && (strpos($pushLog, 'Rejected') !== false || strpos($pushLog, 'failed') !== false || strpos($pushLog, 'error') !== false)) {
        $pushOutput2 = [];
        exec('git push -f origin main 2>&1', $pushOutput2, $code6);
        $pushLog .= "\n\n[Attempting Force Push Recovery]:\n" . implode("\n", $pushOutput2);
        $pushSuccess = ($code6 === 0);
    }
    
    $steps[] = [
        'step' => 'Push code to GitHub (git push)',
        'success' => $pushSuccess,
        'log' => $pushLog ?: "Code push completed successfully."
    ];
    
    // Overall status
    $allSuccess = true;
    foreach ($steps as $s) {
        if (!$s['success']) {
            $allSuccess = false;
        }
    }
    
    echo json_encode([
        'success' => $allSuccess,
        'steps' => $steps
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Traders - Deployment Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-hover: #059669;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f1f5f9;
            padding: 1.5rem;
        }
        
        .container {
            width: 100%;
            max-width: 650px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 2rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .logo {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        
        .title {
            text-align: left;
        }
        
        .title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: white;
        }
        
        .title p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.6; }
        }
        
        .form-group {
            text-align: left;
            margin-bottom: 1.75rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 0.85rem 1.25rem;
            color: white;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            background: rgba(15, 23, 42, 0.8);
        }
        
        .btn-deploy {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 1.25rem;
            padding: 1.1rem;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .btn-deploy:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 15px 20px -3px rgba(16, 185, 129, 0.3);
        }
        
        .btn-deploy:active {
            transform: translateY(1px);
        }
        
        .btn-deploy:disabled {
            background: #475569;
            color: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .terminal-container {
            text-align: left;
            display: none;
        }
        
        .terminal-header {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .terminal {
            background: #020617;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            color: #34d399;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.6);
        }
        
        .step-item {
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .step-item:last-child {
            border: none;
            margin: 0;
            padding: 0;
        }
        
        .step-title {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .step-title.success {
            color: #10b981;
        }
        
        .step-title.error {
            color: #f43f5e;
        }
        
        .step-title.pending {
            color: #f59e0b;
        }
        
        .step-log {
            color: #94a3b8;
            padding-left: 1.25rem;
            margin-top: 0.25rem;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .loader {
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            animation: rotation 1s linear infinite;
        }
        
        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">HR</div>
            <div class="title">
                <h1>HR Traders</h1>
                <p>Deployment Center</p>
            </div>
        </div>
        
        <!-- Status -->
        <div class="status-badge">
            <div class="status-dot"></div>
            <span>Connected & Ready</span>
        </div>
        
        <!-- Form -->
        <div class="form-group">
            <label for="commit-msg">Commit / Deploy Message</label>
            <input 
                type="text" 
                id="commit-msg" 
                class="form-control" 
                placeholder="Describe your changes (e.g., Update grocery price, fix footer layouts)" 
                value="Update storefront layout & features"
            >
        </div>
        
        <!-- Action Button -->
        <button id="btn-deploy-action" class="btn-deploy" onclick="startDeployment()">
            <span>PUSH & DEPLOY TO LIVE SITE</span>
        </button>
        
        <!-- Terminal Logs -->
        <div class="terminal-container" id="logs-container">
            <div class="terminal-header">
                <span>Execution Logs</span>
                <span style="color: var(--primary);" id="build-status-text">Processing...</span>
            </div>
            <div class="terminal" id="terminal-screen">
                Initializing execution stack...
            </div>
        </div>
    </div>

    <script>
        function startDeployment() {
            const btn = document.getElementById('btn-deploy-action');
            const msgInput = document.getElementById('commit-msg');
            const logsContainer = document.getElementById('logs-container');
            const terminal = document.getElementById('terminal-screen');
            const statusText = document.getElementById('build-status-text');
            
            // UI state updates
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span> <span>Pushing changes...</span>';
            logsContainer.style.display = 'block';
            terminal.innerHTML = '<div class="step-item"><div class="step-title pending">⌛ Connecting to Git subsystem...</div></div>';
            statusText.innerText = 'Initializing...';
            statusText.style.color = '#f59e0b';
            
            const msg = msgInput.value;
            const formData = new FormData();
            formData.append('message', msg);
            
            // Trigger PHP API
            fetch('?action=push', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                terminal.innerHTML = '';
                
                data.steps.forEach(step => {
                    const item = document.createElement('div');
                    item.className = 'step-item';
                    
                    const title = document.createElement('div');
                    title.className = `step-title ${step.success ? 'success' : 'error'}`;
                    title.innerHTML = `${step.success ? '✓' : '✗'} ${step.step}`;
                    
                    const log = document.createElement('div');
                    log.className = 'step-log';
                    log.innerText = step.log;
                    
                    item.appendChild(title);
                    item.appendChild(log);
                    terminal.appendChild(item);
                });
                
                // Scroll terminal to bottom
                terminal.scrollTop = terminal.scrollHeight;
                
                if (data.success) {
                    btn.innerHTML = '✓ DEPLOYED SUCCESSFULLY';
                    btn.style.background = '#059669';
                    statusText.innerText = 'Completed';
                    statusText.style.color = '#10b981';
                } else {
                    btn.disabled = false;
                    btn.innerHTML = 'DEPLOYMENT FAILED (TRY AGAIN)';
                    btn.style.background = '#e11d48';
                    statusText.innerText = 'Failed';
                    statusText.style.color = '#f43f5e';
                }
            })
            .catch(err => {
                terminal.innerHTML += `<div class="step-item"><div class="step-title error">✗ Network Error</div><div class="step-log">${err}</div></div>`;
                btn.disabled = false;
                btn.innerHTML = 'DEPLOYMENT ERROR';
                btn.style.background = '#e11d48';
                statusText.innerText = 'Error';
                statusText.style.color = '#f43f5e';
            });
        }
    </script>
</body>
</html>
