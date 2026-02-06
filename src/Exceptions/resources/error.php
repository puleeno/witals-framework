<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status ?> | <?= htmlspecialchars($message) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --danger: #ef4444;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .background-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(60px);
        }

        .blob-1 { top: -100px; right: -100px; }
        .blob-2 { bottom: -100px; left: -100px; }

        .container {
            max-width: 900px;
            width: 100%;
            z-index: 10;
        }

        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .status-badge {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .message-box {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .message-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: var(--danger);
        }

        .error-message {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.95rem;
            color: #fb7185;
            word-break: break-all;
        }

        .details-section {
            margin-top: 2rem;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .trace-container {
            margin-top: 2rem;
        }

        .trace-header {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .trace-list {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
        }

        .trace-item {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }

        .trace-item:last-child {
            border-bottom: none;
        }

        .trace-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .trace-file {
            color: var(--primary);
            font-weight: 600;
        }

        .trace-line {
            color: var(--text-dim);
        }

        .trace-class {
            color: #38bdf8;
        }

        .trace-function {
            color: #fbbf24;
        }

        .footer {
            margin-top: 3rem;
            text-align: center;
            color: var(--text-dim);
            font-size: 0.85rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .btn-outline {
            border: 1px solid var(--border);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-2px);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 640px) {
            .error-card {
                padding: 1.5rem;
            }
            .title {
                font-size: 1.5rem;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="background-blob blob-1"></div>
    <div class="background-blob blob-2"></div>

    <div class="container">
        <div class="error-card">
            <div class="header">
                <div class="status-badge"><?= $status ?></div>
                <h1 class="title"><?= $debug ? 'Server Error' : 'Something went wrong' ?></h1>
            </div>

            <div class="message-box">
                <div class="error-message">
                    <?= htmlspecialchars($message) ?>
                </div>
            </div>

            <?php if ($debug): ?>
                <div class="details-section">
                    <div class="file-info">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span><?= htmlspecialchars($file) ?>:<?= $line ?></span>
                    </div>

                    <?php if (isset($exception)): ?>
                        <div class="file-info" style="margin-top: -0.5rem">
                            <span style="color: #6366f1; font-weight: 600;"><?= htmlspecialchars($exception) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="trace-container">
                        <div class="trace-header">
                            <span>Stack Trace</span>
                            <span style="font-size: 0.75rem; font-weight: normal; opacity: 0.6;"><?= count($trace) ?> steps</span>
                        </div>
                        <div class="trace-list">
                            <?php foreach ($trace as $i => $step): ?>
                                <div class="trace-item">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                        <span style="opacity: 0.5;">#<?= $i ?></span>
                                        <span class="trace-line"><?= $step['line'] ?? '?' ?></span>
                                    </div>
                                    <div class="trace-file"><?= htmlspecialchars($step['file'] ?? '[internal]') ?></div>
                                    <div style="margin-top: 4px;">
                                        <?php if (isset($step['class'])): ?>
                                            <span class="trace-class"><?= htmlspecialchars($step['class']) ?></span><?= $step['type'] ?>
                                        <?php endif; ?>
                                        <span class="trace-function"><?= htmlspecialchars($step['function']) ?>()</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: var(--text-dim);">
                    We've encountered an unexpected error. Our team has been notified and we're working to fix it.
                </p>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="/" class="btn btn-primary">
                    <svg style="margin-right: 8px" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Return Home
                </a>
                <a href="javascript:location.reload()" class="btn btn-outline">
                    <svg style="margin-right: 8px" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Try Again
                </a>
            </div>
        </div>

        <div class="footer">
            Powered by <strong>Witals Framework</strong>
        </div>
    </div>
</body>
</html>
