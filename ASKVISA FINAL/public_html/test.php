// ... [Previous PHP code remains exactly the same] ...

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ask Visa Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --success-light: #34d399;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #0ea5e9;
            --dark: #1f2937;
            --dark-light: #374151;
            --light: #f9fafb;
            --gray: #9ca3af;
            --gray-light: #e5e7eb;
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --border-radius-xs: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --box-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --box-shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark {
            --light: #111827;
            --dark: #f9fafb;
            --gray-light: #1f2937;
            --gray: #9ca3af;
            --dark-light: #374151;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            --box-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.25);
            --box-shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.35);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
            line-height: 1.6;
            height: 100vh;
            overflow: hidden;
            transition: var(--transition);
        }

        body.dark {
            background: linear-gradient(135deg, var(--light) 0%, #1e1b4b 100%);
        }

        .app-container {
            display: flex;
            height: 100vh;
            max-width: 1800px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }

        /* Sidebar Styles - UPDATED TO MATCH IMAGE */
        .sidebar {
            width: 350px;
            background: linear-gradient(165deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            color: white;
            box-shadow: var(--box-shadow-xl);
            z-index: 10;
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
            pointer-events: none;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        .logo-icon:hover {
            transform: rotate(15deg) scale(1.1);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        .logo-text h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .logo-text p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 300;
        }

        /* NEW: Sidebar Stats Grid - MATCHING THE IMAGE */
        .sidebar-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: var(--border-radius-sm);
            padding: 24px 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
            display: block;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #ffffff, rgba(255, 255, 255, 0.8));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Progress Section - UPDATED */
        .progress-section {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: var(--border-radius);
            padding: 28px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .progress-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .step-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .step-label {
            font-weight: 600;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step-count {
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .progress-container {
            height: 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--success-light), #22d3ee, var(--primary-light));
            border-radius: 5px;
            width: 0%;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(34, 211, 238, 0.4);
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
            animation: shimmer 2.5s infinite;
        }

        /* Stats removed from progress section since we have sidebar-stats */

        .sidebar-actions {
            margin-top: auto;
            position: relative;
            z-index: 2;
        }

        .action-btn {
            width: 100%;
            padding: 16px;
            border-radius: var(--border-radius-sm);
            border: none;
            background: rgba(255, 255, 255, 0.18);
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
            transition: var(--transition);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .action-btn.danger {
            background: rgba(239, 68, 68, 0.2);
        }

        .action-btn.danger:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .action-btn.success {
            background: rgba(16, 185, 129, 0.2);
        }

        .action-btn.success:hover {
            background: rgba(16, 185, 129, 0.3);
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px 20px;
            border-radius: var(--border-radius-sm);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            margin-top: 20px;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .theme-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 500;
        }

        .switch {
            position: relative;
            width: 56px;
            height: 30px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            border-radius: 34px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Chat Container - REMOVED TYPING INDICATOR STYLES */
        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: var(--light);
            box-shadow: inset 20px 0 60px rgba(0, 0, 0, 0.03);
        }

        .chat-header {
            padding: 25px 40px;
            background: var(--light);
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            z-index: 5;
            position: relative;
        }

        .chat-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--info));
            opacity: 0.8;
        }

        .chat-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .chat-title p {
            font-size: 14px;
            color: var(--gray);
            margin-top: 5px;
            font-weight: 400;
        }

        .chat-status {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: var(--success);
            font-weight: 500;
            padding: 10px 20px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: var(--border-radius-xs);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px var(--success);
        }

        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 40px;
            scroll-behavior: smooth;
            background: url('data:image/svg+xml,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M0,50 Q25,40 50,50 T100,50" fill="none" stroke="rgba(99,102,241,0.03)" stroke-width="2"/></svg>');
        }

        .chat-container::-webkit-scrollbar {
            width: 8px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary-light), var(--primary-dark));
            border-radius: 4px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(var(--primary), var(--primary-dark));
        }

        .message-row {
            display: flex;
            margin-bottom: 30px;
            animation: messageSlide 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-row.bot {
            justify-content: flex-start;
        }

        .message-row.user {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px 15px 0 0;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
            transition: var(--transition);
        }

        .message-row.user .message-avatar {
            margin: 8px 0 0 15px;
            background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .message-content {
            max-width: 70%;
            padding: 22px 28px;
            border-radius: var(--border-radius);
            position: relative;
            box-shadow: var(--box-shadow-lg);
            line-height: 1.7;
            word-wrap: break-word;
            transition: var(--transition);
        }

        .message-row.bot .message-content {
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-top-left-radius: 4px;
            color: var(--dark);
            animation: messageFloat 3s ease-in-out infinite;
        }

        .message-row.user .message-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-bottom-right-radius: 4px;
            animation: messageFloatUser 3s ease-in-out infinite;
        }

        .message-text {
            font-size: 16px;
            line-height: 1.7;
        }

        .message-text b {
            font-weight: 700;
            color: inherit;
            position: relative;
        }

        .message-row.bot .message-text b {
            color: var(--primary-dark);
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .message-time {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 10px;
            text-align: right;
            font-weight: 500;
        }

        .message-attachment {
            margin-top: 15px;
        }

        .msg-img {
            max-width: 280px;
            border-radius: var(--border-radius-sm);
            margin-top: 12px;
            cursor: pointer;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .msg-img:hover {
            transform: scale(1.05) rotate(1deg);
            border-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }

        .pdf-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
            padding: 18px 22px;
            border-radius: var(--border-radius-sm);
            margin-top: 15px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
        }

        .message-row.bot .pdf-card {
            background: var(--gray-light);
            border: 1px solid var(--gray-light);
        }

        .pdf-card:hover {
            background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.1) 100%);
            transform: translateX(8px) translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .pdf-icon {
            font-size: 28px;
            color: var(--danger);
            transition: var(--transition);
        }

        .pdf-card:hover .pdf-icon {
            transform: scale(1.2) rotate(-5deg);
        }

        .pdf-info h4 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .pdf-info p {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Input Area */
        .input-section {
            padding: 25px 40px;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
            box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.08);
            z-index: 5;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--light);
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 10px;
            box-shadow: var(--box-shadow-xl);
            transition: var(--transition);
            position: relative;
        }

        .input-wrapper::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--info));
            border-radius: var(--border-radius);
            z-index: -1;
            opacity: 0;
            transition: var(--transition);
        }

        .input-wrapper:focus-within {
            border-color: transparent;
            transform: translateY(-3px);
        }

        .input-wrapper:focus-within::before {
            opacity: 1;
        }

        .file-upload-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .file-upload-btn.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .file-upload-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .file-upload-btn:hover:not(.disabled) {
            transform: translateY(-4px) rotate(10deg);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .input-field {
            flex: 1;
            border: none;
            background: transparent;
            padding: 16px 0;
            font-size: 17px;
            color: var(--dark);
            font-family: 'Poppins', sans-serif;
            outline: none;
            font-weight: 400;
        }

        .input-field::placeholder {
            color: var(--gray);
            font-weight: 400;
        }

        .send-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-4px) scale(1.1);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.5);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Preview Tray */
        #previewTray {
            position: absolute;
            bottom: 110px;
            left: 40px;
            right: 40px;
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 20px 25px;
            display: none;
            align-items: center;
            gap: 20px;
            box-shadow: var(--box-shadow-xl);
            border: 2px solid var(--primary-light);
            z-index: 100;
            animation: previewSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border-left: 6px solid var(--primary);
        }

        #previewImg {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: var(--border-radius-sm);
            border: 3px solid var(--primary-light);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        #previewImg:hover {
            transform: rotate(5deg) scale(1.05);
        }

        .preview-info {
            flex: 1;
        }

        .preview-info h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--primary-dark);
        }

        .preview-info p {
            font-size: 13px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preview-close {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .preview-close:hover {
            background: var(--danger);
            color: white;
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        /* Lightbox */
        #lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.97);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(15px);
            animation: lightboxFade 0.3s ease-out;
        }

        #lbContainer {
            width: 90%;
            height: 90%;
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            animation: lightboxZoom 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #lbImg, #lbPdf {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }

        .lightbox-close {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 28px;
            color: white;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 2001;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        /* Confirmation Modal */
        #confirmOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            animation: overlayFade 0.3s ease-out;
        }

        .confirm-card {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 40px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: var(--box-shadow-xl);
            border: 2px solid var(--gray-light);
            animation: modalBounce 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .confirm-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--danger), var(--warning));
        }

        .confirm-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: var(--danger);
            font-size: 40px;
            animation: iconPulse 2s infinite;
        }

        .confirm-card h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark);
            background: linear-gradient(90deg, var(--danger), var(--warning));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .confirm-card p {
            color: var(--gray);
            margin-bottom: 35px;
            line-height: 1.7;
            font-size: 16px;
        }

        .confirm-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .confirm-btn {
            padding: 15px 35px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            min-width: 150px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .confirm-btn.cancel {
            background: var(--gray-light);
            color: var(--dark);
        }

        .confirm-btn.cancel:hover {
            background: var(--gray);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .confirm-btn.danger {
            background: linear-gradient(135deg, var(--danger) 0%, #f97316 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .confirm-btn.danger:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.4);
        }

        /* Completion State */
        .completion-state {
            text-align: center;
            padding: 50px 30px;
            background: var(--light);
            border-radius: var(--border-radius);
            margin: 30px auto;
            max-width: 600px;
            box-shadow: var(--box-shadow-xl);
            border: 3px solid transparent;
               background: linear-gradient(var(--light), var(--light)) padding-box,
                        linear-gradient(135deg, var(--success), var(--info), var(--primary)) border-box;
            animation: completionGlow 3s ease-in-out infinite;
        }

        .completion-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: var(--success);
            font-size: 48px;
            animation: successBounce 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
        }

        .completion-state h3 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(90deg, var(--success), var(--info), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 1px;
        }

        .completion-state p {
            color: var(--gray);
            margin-bottom: 25px;
            font-size: 17px;
            line-height: 1.7;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .order-id {
            font-size: 42px;
            font-weight: 900;
            color: var(--primary-dark);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(79, 70, 229, 0.1) 100%);
            padding: 20px 40px;
            border-radius: var(--border-radius-sm);
            display: inline-block;
            margin: 20px 0;
            letter-spacing: 3px;
            border: 2px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
            transition: var(--transition);
            cursor: pointer;
        }

        .order-id:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .completion-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .completion-btn {
            padding: 16px 32px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            justify-content: center;
        }

        .completion-btn.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .completion-btn.primary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
        }

        .completion-btn.secondary {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%);
            color: white;
        }

        .completion-btn.secondary:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }

        .completion-btn.download {
            background: linear-gradient(135deg, var(--info) 0%, #06b6d4 100%);
            color: white;
        }

        .completion-btn.download:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 15px 35px rgba(14, 165, 233, 0.4);
        }

        /* Animations */
        @keyframes messageSlide {
            from { 
                opacity: 0; 
                transform: translateY(20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        @keyframes messageFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes messageFloatUser {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes previewSlide {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.9); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        @keyframes lightboxFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes lightboxZoom {
            from { 
                opacity: 0; 
                transform: scale(0.8) rotate(-5deg); 
            }
            to { 
                opacity: 1; 
                transform: scale(1) rotate(0); 
            }
        }

        @keyframes overlayFade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalBounce {
            0% { 
                opacity: 0; 
                transform: translateY(-50px) scale(0.8); 
            }
            70% { 
                opacity: 1; 
                transform: translateY(10px) scale(1.05); 
            }
            100% { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        @keyframes pulse {
            0% { 
                opacity: 1; 
                transform: scale(1); 
            }
            50% { 
                opacity: 0.7; 
                transform: scale(1.1); 
            }
            100% { 
                opacity: 1; 
                transform: scale(1); 
            }
        }

        @keyframes iconPulse {
            0%, 100% { 
                transform: scale(1); 
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            50% { 
                transform: scale(1.05); 
                box-shadow: 0 0 0 15px rgba(239, 68, 68, 0);
            }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes successBounce {
            0%, 100% { 
                transform: translateY(0) scale(1); 
            }
            50% { 
                transform: translateY(-20px) scale(1.1); 
            }
        }

        @keyframes completionGlow {
            0%, 100% { 
                box-shadow: 0 20px 60px rgba(16, 185, 129, 0.2);
            }
            50% { 
                box-shadow: 0 30px 80px rgba(14, 165, 233, 0.3);
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 320px;
            }
            
            .message-content {
                max-width: 80%;
            }
            
            .completion-btn {
                min-width: 180px;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 300px;
            }
            
            .chat-header {
                padding: 20px 30px;
            }
            
            .chat-container {
                padding: 30px;
            }
            
            .input-section {
                padding: 20px 30px;
            }
            
            #previewTray {
                left: 30px;
                right: 30px;
                bottom: 100px;
            }
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                padding: 20px;
            }
            
            .logo {
                margin-bottom: 25px;
            }
            
            .sidebar-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .progress-section {
                margin-bottom: 25px;
            }
            
            .chat-header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .chat-container {
                padding: 20px;
            }
            
            .input-section {
                padding: 15px 20px;
            }
            
            #previewTray {
                left: 20px;
                right: 20px;
                bottom: 90px;
            }
            
            .message-content {
                max-width: 85%;
            }
            
            .confirm-actions {
                flex-direction: column;
            }
            
            .confirm-btn {
                min-width: 100%;
            }
            
            .completion-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .completion-btn {
                min-width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .sidebar-stats {
                grid-template-columns: 1fr;
            }
            
            .order-id {
                font-size: 32px;
                padding: 15px 25px;
            }
            
            .completion-state h3 {
                font-size: 26px;
            }
            
            .message-content {
                max-width: 90%;
                padding: 18px 22px;
            }
            
            .completion-btn {
                padding: 14px 25px;
            }
        }
    </style>
</head>
<body id="body">
<div class="app-container">

    <!-- Sidebar - UPDATED WITH IMAGE LAYOUT -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-passport"></i>
            </div>
            <div class="logo-text">
                <h1>Ask Visa</h1>
                <p>Intelligent Application Assistant</p>
            </div>
        </div>
        
        <!-- NEW: Sidebar Stats Grid -->
        <div class="sidebar-stats">
            <div class="stat-card">
                <span class="stat-number" id="currentApplicant">1</span>
                <span class="stat-label">Current Applicant</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="totalApplicants">1</span>
                <span class="stat-label">Total Applicants</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="currentDocument">0</span>
                <span class="stat-label">Current Document</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="totalDocuments">0</span>
                <span class="stat-label">Total Documents</span>
            </div>
        </div>
        
        <!-- Progress Section (kept but updated) -->
        <div class="progress-section">
            <div class="step-info">
                <span class="step-label" id="stepLabel">Country Selection</span>
                <span class="step-count" id="stepCount">Step 1</span>
            </div>
            <div class="progress-container">
                <div id="pBar" class="progress-bar"></div>
            </div>
        </div>
        
        <!-- Sidebar Actions -->
        <div class="sidebar-actions">
            <button class="action-btn" onclick="toggleConfirm(true)">
                <i class="fas fa-plus-circle"></i>
                New Application
            </button>
            <button class="action-btn success" onclick="downloadSummary()">
                <i class="fas fa-download"></i>
                Download Summary
            </button>
            <button class="action-btn" onclick="exportApplication()" id="exportBtn" style="display:none;">
                <i class="fas fa-file-export"></i>
                Export Application
            </button>
            
            <div class="theme-toggle">
                <div class="theme-label">
                    <i class="fas fa-moon"></i>
                    Dark Mode
                </div>
                <label class="switch">
                    <input type="checkbox" id="themeToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>

    <!-- Main Chat Section -->
    <div class="chat-section">
        <div class="chat-header">
            <div class="chat-title">
                <h2>Visa Application Assistant</h2>
                <p>Your personal guide through the visa application process</p>
            </div>
            <div class="chat-status">
                <div class="status-dot"></div>
                <span>Assistant is online</span>
            </div>
        </div>
        
        <div class="chat-container" id="chat">
            <?php foreach($_SESSION['messages'] as $index => $m): ?>
                <div class="message-row <?= $m['role'] === 'user' ? 'user' : 'bot' ?>">
                    <?php if($m['role'] === 'bot'): ?>
                        <div class="message-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="message-content">
                        <div class="message-text">
                            <?= formatBold($m['text']) ?>
                        </div>
                        
                        <?php if(isset($m['img'])): ?>
                            <div class="message-attachment">
                                <?php if(isset($m['is_pdf']) && $m['is_pdf']): ?>
                                    <div class="pdf-card" onclick="openLightbox('<?= $m['img'] ?>')">
                                        <div class="pdf-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="pdf-info">
                                            <h4>Uploaded Document</h4>
                                            <p>Click to preview PDF</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= $m['img'] ?>" class="msg-img" onclick="openLightbox(this.src)">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-time">
                            <?= date('H:i') ?>
                        </div>
                    </div>
                    
                    <?php if($m['role'] === 'user'): ?>
                        <div class="message-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- REMOVED: Typing Indicator -->
            
            <!-- Completion State (hidden by default) -->
            <div id="completionState" style="display: none;">
                <div class="completion-state">
                    <div class="completion-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Application Complete!</h3>
                    <p>Your visa application has been successfully submitted. You will receive a confirmation email shortly with further instructions.</p>
                    <div class="order-id" id="finalOrderId" onclick="copyOrderId()" title="Click to copy">#0000</div>
                    <p>Keep this Order ID for future reference</p>
                    
                    <div class="completion-actions">
                        <button class="completion-btn download" onclick="downloadApplicationPDF()">
                            <i class="fas fa-file-pdf"></i>
                            Download PDF
                        </button>
                        <button class="completion-btn secondary" onclick="shareApplication()">
                            <i class="fas fa-share-alt"></i>
                            Share Application
                        </button>
                        <button class="completion-btn primary" onclick="startNewApplication()">
                            <i class="fas fa-plus-circle"></i>
                            New Application
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preview Tray -->
        <div id="previewTray">
            <img id="previewImg" src="">
            <div class="preview-info">
                <h4 id="previewFileName">File Preview</h4>
                <p id="previewFileSize">Ready to upload</p>
            </div>
            <div class="preview-close" onclick="clearPreview()">
                <i class="fas fa-times"></i>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="input-section">
            <div class="input-wrapper">
                <label id="attachBtn" class="file-upload-btn disabled">
                    <i class="fas fa-paperclip"></i>
                    <input type="file" id="fileInput" hidden accept="image/*,application/pdf" disabled onchange="handlePreview(this)">
                </label>
                <input type="text" id="msgInput" class="input-field" placeholder="Type your response here..." autocomplete="off">
                <button id="sendBtn" class="send-btn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox()">
    <div class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </div>
    <div id="lbContainer" onclick="event.stopPropagation()">
        <img id="lbImg">
        <iframe id="lbPdf"></iframe>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmOverlay">
    <div class="confirm-card">
        <div class="confirm-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Reset Application?</h3>
        <p>This will clear all current progress and start a new application. This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="confirm-btn cancel" onclick="toggleConfirm(false)">Cancel</button>
            <form method="POST" style="display:inline;">
                <button type="submit" name="reset" class="confirm-btn danger">Reset Application</button>
            </form>
        </div>
    </div>
</div>

<script>
    const chat = document.getElementById('chat');
    const msgInput = document.getElementById('msgInput');
    const fileInput = document.getElementById('fileInput');
    const attachBtn = document.getElementById('attachBtn');
    const sendBtn = document.getElementById('sendBtn');
    const pBar = document.getElementById('pBar');
    const stepLabel = document.getElementById('stepLabel');
    const stepCount = document.getElementById('stepCount');
    const themeToggle = document.getElementById('themeToggle');
    const completionState = document.getElementById('completionState');
    const finalOrderId = document.getElementById('finalOrderId');
    const exportBtn = document.getElementById('exportBtn');
    
    // NEW: Sidebar stats elements
    const currentApplicant = document.getElementById('currentApplicant');
    const totalApplicants = document.getElementById('totalApplicants');
    const currentDocument = document.getElementById('currentDocument');
    const totalDocuments = document.getElementById('totalDocuments');
    
    let isProcessing = false;
    let currentOrderId = null;

    // Lightbox Logic
    function openLightbox(src) { 
        const isPdf = src.toLowerCase().includes('pdf') || src.startsWith('blob:');
        document.getElementById('lbImg').style.display = isPdf ? 'none' : 'block';
        document.getElementById('lbPdf').style.display = isPdf ? 'block' : 'none';
        if(isPdf) {
            document.getElementById('lbPdf').src = src + "#toolbar=0";
        } else {
            document.getElementById('lbImg').src = src;
        }
        document.getElementById('lightbox').style.display = 'flex'; 
    }
    
    function closeLightbox() { 
        document.getElementById('lightbox').style.display = 'none'; 
        document.getElementById('lbPdf').src = ''; 
    }
    
    function toggleConfirm(show) { 
        document.getElementById('confirmOverlay').style.display = show ? 'flex' : 'none'; 
    }

    // File Preview
    function handlePreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = (e) => { 
                const isPdf = file.type === "application/pdf";
                document.getElementById('previewImg').src = isPdf 
                    ? "https://cdn-icons-png.flaticon.com/512/337/337946.png" 
                    : e.target.result;
                
                document.getElementById('previewFileName').textContent = file.name;
                document.getElementById('previewFileSize').textContent = 
                    `${(file.size / 1024 / 1024).toFixed(2)} MB • ${isPdf ? 'PDF Document' : 'Image'}`;
                
                document.getElementById('previewTray').style.display = 'flex'; 
            };
            reader.readAsDataURL(file);
        }
    }
    
    function clearPreview() { 
        fileInput.value = ""; 
        document.getElementById('previewTray').style.display = 'none'; 
    }

    // Update sidebar stats based on session data
    function updateSidebarStats(data) {
        // Update current applicant and total applicants
        if (data.current_person && data.total_people) {
            currentApplicant.textContent = data.current_person;
            totalApplicants.textContent = data.total_people;
        }
        
        // Update current document and total documents
        if (data.current_question && data.total_questions) {
            currentDocument.textContent = data.current_question;
            totalDocuments.textContent = data.total_questions;
        } else {
            // Default values or based on step
            if (data.step === 'country') {
                currentDocument.textContent = '0';
                totalDocuments.textContent = '0';
            }
        }
    }

    // Update progress display
    function updateProgressDisplay(data) {
        // Update progress bar
        pBar.style.transition = 'width 1s cubic-bezier(0.4, 0, 0.2, 1)';
        pBar.style.width = data.progress + '%';
        
        // Update step label
        if (data.step_label) {
            stepLabel.textContent = data.step_label;
        }
        
        // Update step count
        if (data.step === 'details' && data.total_questions && data.current_question) {
            stepCount.textContent = `Step ${data.current_question}/${data.total_questions}`;
        } else {
            stepCount.textContent = data.step_label || 'Step 1';
        }
        
        // Update sidebar stats
        updateSidebarStats(data);
    }

    // Message Sending
    async function sendMessage() {
        const file = fileInput.files[0];
        const text = msgInput.value.trim();
        
        if (isProcessing || (!text && !file)) return;
        
        isProcessing = true;
        msgInput.disabled = true;
        sendBtn.disabled = true;

        const formData = new FormData();
        formData.append('message', text);
        if (file) formData.append('image', file);

        // Add user message to UI
        if (text || file) {
            const userRow = document.createElement('div');
            userRow.className = 'message-row user';
            userRow.style.opacity = '0';
            userRow.style.transform = 'translateY(20px) scale(0.95)';
            
            userRow.innerHTML = `
                <div class="message-content">
                    <div class="message-text">${text || (file.type === "application/pdf" ? "Uploaded PDF document" : "Uploaded image")}</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
            `;
            chat.appendChild(userRow);
            
            // Animate in
            setTimeout(() => {
                userRow.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                userRow.style.opacity = '1';
                userRow.style.transform = 'translateY(0) scale(1)';
            }, 10);
        }

        msgInput.value = ''; 
        clearPreview();
        chat.scrollTop = chat.scrollHeight;

        try {
            const response = await fetch('?ajax=1', { method: 'POST', body: formData });
            const data = await response.json();

            // Update progress display
            updateProgressDisplay(data);

            // Add bot response to UI
            const botRow = document.createElement('div');
            botRow.className = 'message-row bot';
            botRow.style.opacity = '0';
            botRow.style.transform = 'translateY(20px) scale(0.95)';
            
            const formattedText = data.text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
            
            let attachmentHtml = '';
            if (data.img_path) {
                const isPdf = data.img_path.toLowerCase().includes('pdf');
                if (isPdf) {
                    attachmentHtml = `
                        <div class="message-attachment">
                            <div class="pdf-card" onclick="openLightbox('${data.img_path}')">
                                <div class="pdf-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="pdf-info">
                                    <h4>Uploaded Document</h4>
                                    <p>Click to preview PDF</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    attachmentHtml = `
                        <div class="message-attachment">
                            <img src="${data.img_path}" class="msg-img" onclick="openLightbox(this.src)">
                        </div>
                    `;
                }
            }
            
            botRow.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">${formattedText}</div>
                    ${attachmentHtml}
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
            `;
            
            chat.appendChild(botRow);

            // Animate in
            setTimeout(() => {
                botRow.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                botRow.style.opacity = '1';
                botRow.style.transform = 'translateY(0) scale(1)';
            }, 10);

            // Update file upload button
            if (data.allow_upload) {
                attachBtn.classList.remove('disabled');
                attachBtn.classList.add('active');
                fileInput.disabled = false;
            } else {
                attachBtn.classList.remove('active');
                attachBtn.classList.add('disabled');
                fileInput.disabled = true;
            }

            // Handle finish state
            if (data.is_finished) {
                msgInput.placeholder = "Application Complete";
                msgInput.disabled = true;
                sendBtn.disabled = true;
                
                // Extract order ID from response
                const orderMatch = data.text.match(/Order ID:\s*\*\*(\d+)\*\*/);
                if (orderMatch) {
                    currentOrderId = orderMatch[1];
                    finalOrderId.textContent = `#${currentOrderId}`;
                    
                    // Show export button
                    exportBtn.style.display = 'block';
                    
                    // Show completion state
                    setTimeout(() => {
                        completionState.style.display = 'block';
                        completionState.style.opacity = '0';
                        completionState.style.transform = 'translateY(50px) scale(0.9)';
                        
                        setTimeout(() => {
                            completionState.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                            completionState.style.opacity = '1';
                            completionState.style.transform = 'translateY(0) scale(1)';
                        }, 100);
                        
                        chat.scrollTop = chat.scrollHeight;
                    }, 800);
                }
            }

        } catch (error) {
            console.error("Error sending message:", error);
            
            // Show error message
            const errorRow = document.createElement('div');
            errorRow.className = 'message-row bot';
            errorRow.style.opacity = '0';
            errorRow.style.transform = 'translateY(20px) scale(0.95)';
            
            errorRow.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">Sorry, an error occurred. Please try again.</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
            `;
            chat.appendChild(errorRow);
            
            // Animate in
            setTimeout(() => {
                errorRow.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                errorRow.style.opacity = '1';
                errorRow.style.transform = 'translateY(0) scale(1)';
            }, 10);
        } finally {
            isProcessing = false;
            msgInput.disabled = false;
            sendBtn.disabled = false;
            msgInput.focus();
            chat.scrollTop = chat.scrollHeight;
        }
    }

    // Theme Toggle Handler
    themeToggle.addEventListener('change', () => {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });

    // Enter key to send message
    msgInput.addEventListener('keypress', (e) => { 
        if(e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Copy Order ID
    function copyOrderId() {
        if (!currentOrderId) return;
        
        navigator.clipboard.writeText(currentOrderId).then(() => {
            const originalText = finalOrderId.textContent;
            finalOrderId.textContent = 'Copied!';
            finalOrderId.style.background = 'linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.3) 100%)';
            
            setTimeout(() => {
                finalOrderId.textContent = originalText;
                finalOrderId.style.background = 'linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(79, 70, 229, 0.1) 100%)';
            }, 2000);
        });
    }

    // Download Application PDF
    function downloadApplicationPDF() {
        if (!currentOrderId) {
            alert('No application data available to download.');
            return;
        }
        
        // Show loading animation
        const downloadBtn = document.querySelector('.completion-btn.download');
        const originalHTML = downloadBtn.innerHTML;
        downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
        downloadBtn.disabled = true;
        
        // Simulate PDF generation
        setTimeout(() => {
            const pdfContent = `
                VISA APPLICATION SUMMARY
                =========================
                
                Order ID: ${currentOrderId}
                Date: ${new Date().toLocaleDateString()}
                Status: Submitted Successfully
                
                Thank you for using Ask Visa Application Assistant.
                Your application has been processed successfully.
                
                Application Details:
                - Country: ${stepLabel.textContent}
                - Applicants: ${currentApplicant.textContent}/${totalApplicants.textContent}
                - Submission Time: ${new Date().toLocaleString()}
                
                This is a confirmation of your visa application submission.
                You will receive further updates via email.
            `;
            
            const blob = new Blob([pdfContent], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `visa-application-${currentOrderId}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            // Restore button
            downloadBtn.innerHTML = originalHTML;
            downloadBtn.disabled = false;
            
            alert('PDF downloaded successfully!');
        }, 1500);
    }

    // Share Application
    function shareApplication() {
        if (!currentOrderId) {
            alert('No application to share.');
            return;
        }
        
        const shareData = {
            title: 'Visa Application',
            text: `I just completed my visa application using Ask Visa Assistant. Order ID: ${currentOrderId}`,
            url: window.location.href
        };
        
        if (navigator.share) {
            navigator.share(shareData);
        } else {
            navigator.clipboard.writeText(`Visa Application - Order ID: ${currentOrderId}\n${window.location.href}`).then(() => {
                alert('Application link copied to clipboard!');
            });
        }
    }

    // Start New Application
    function startNewApplication() {
        toggleConfirm(true);
    }

    // Export Application
    function exportApplication() {
        if (!currentOrderId) {
            alert('No application to export.');
            return;
        }
        
        // Create export data
        const exportData = {
            orderId: currentOrderId,
            timestamp: new Date().toISOString(),
            applicationData: {
                steps: stepLabel.textContent,
                applicants: `${currentApplicant.textContent}/${totalApplicants.textContent}`,
                documents: `${currentDocument.textContent}/${totalDocuments.textContent}`
            }
        };
        
        const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `visa-application-${currentOrderId}-export.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Download summary function
    function downloadSummary() {
        if (!currentOrderId) {
            alert('No application data available to download.');
            return;
        }
        
        // Create a summary
        const summary = `
            VISA APPLICATION SUMMARY
            ========================
            
            Order ID: ${currentOrderId || 'Not yet assigned'}
            Date: ${new Date().toLocaleDateString()}
            Time: ${new Date().toLocaleTimeString()}
            Status: In Progress
            
            Current Step: ${stepLabel.textContent}
            Current Applicant: ${currentApplicant.textContent}/${totalApplicants.textContent}
            Current Document: ${currentDocument.textContent}/${totalDocuments.textContent}
            
            ---
            
            This summary was generated by Ask Visa Assistant.
            Save this information for your records.
        `;
        
        const blob = new Blob([summary], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `visa-application-summary-${currentOrderId || 'temp'}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Initialize on load
    window.onload = () => { 
        // Restore Theme
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            themeToggle.checked = true;
        }

        // Add initial animation to sidebar
        document.querySelector('.sidebar').style.opacity = '0';
        document.querySelector('.sidebar').style.transform = 'translateX(-20px)';
        setTimeout(() => {
            document.querySelector('.sidebar').style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            document.querySelector('.sidebar').style.opacity = '1';
            document.querySelector('.sidebar').style.transform = 'translateX(0)';
        }, 100);

        chat.scrollTop = chat.scrollHeight;
        
        // Initial Fetch for Progress and Upload status
        fetch('?ajax=1', { method: 'POST', body: new FormData() })
            .then(r => r.json())
            .then(data => {
                updateProgressDisplay(data);
                if (data.allow_upload) {
                    attachBtn.classList.remove('disabled');
                    attachBtn.classList.add('active');
                    fileInput.disabled = false;
                }
            });
            
        msgInput.focus();
    };
</script>
</body>
</html>