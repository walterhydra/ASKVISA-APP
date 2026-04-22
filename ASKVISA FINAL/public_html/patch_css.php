<?php
$file = 'indexstitch.php';
// We need to restore from backup first to ensure a clean slate, because the previous patch ran.
$backup = $file . '.bak';
if (file_exists($backup)) {
    $content = file_get_contents($backup);
} else {
    $content = file_get_contents($file);
    file_put_contents($backup, $content); // create backup if one doesn't exist
}

// 1. Replace CSS Variables with RED/WHITE/BLACK theme
$old_root = <<<EOD
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1b26;
            --dark-light: #24283b;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --box-shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark {
            --light: #1a1b26;
            --dark: #f8f9fa;
            --gray-light: #24283b;
            --gray: #a9b1d6;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            --box-shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
EOD;

$new_root = <<<EOD
        :root, body.dark {
            --primary: #dc2626; /* Crimson Red */
            --primary-light: #ef4444; /* Lighter Red */
            --success: #10b981;
            --danger: #991b1b;
            --warning: #f59e0b;
            --dark: #ffffff;
            --dark-light: #f3f4f6;
            --light: #09090b; /* Very dark grayish black */
            --gray: #9ca3af;
            --gray-light: rgba(255, 255, 255, 0.05);
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --box-shadow-lg: 0 10px 15px -3px rgba(220, 38, 38, 0.2);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --glass-bg: rgba(255, 255, 255, 0.03); /* Lighter glass */
            --glass-border: rgba(255, 255, 255, 0.08);
        }
EOD;

$content = str_replace($old_root, $new_root, $content);

// 2. Body Gradient Mesh (Red/Black)
$content = preg_replace(
    '/(body\s*\{.*?)background:\s*var\(--light\);(.*?\})/s',
    '$1background-color: #09090b; background-image: radial-gradient(at 0% 0%, rgba(220, 38, 38, 0.15) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(220, 38, 38, 0.1) 0px, transparent 50%);$2',
    $content
);

// 3. Sidebar Glassmorphism
$content = preg_replace(
    '/(.*?\.sidebar\s*\{.*?)background:\s*linear-gradient[^\;]+;(.*?\})/s',
    '$1background: var(--glass-bg); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-right: 1px solid var(--glass-border);$2',
    $content
);

// 4. Action buttons (Sidebar)
$old_action = <<<EOD
        .action-btn {
            width: 100%;
            padding: 14px;
            border-radius: var(--border-radius-sm);
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 16px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
EOD;
$new_action = str_replace('background: rgba(255, 255, 255, 0.15);', 'background: var(--glass-bg);', $old_action);
$content = str_replace($old_action, $new_action, $content);

// 5. Chat header
$content = preg_replace(
    '/(.*?\.chat-header\s*\{.*?)background:\s*var\(--light\);(.*?border-bottom:\s*1px\s*solid\s*)var\(--gray-light\);(.*?\})/s',
    '$1background: transparent; backdrop-filter: blur(12px); border-bottom: 1px solid var(--glass-border);$3',
    $content
);
$content = preg_replace(
    '/(.*?\.chat-title\s*h2\s*\{.*?)color:\s*var\(--dark\);(.*?\})/s',
    '$1color: var(--dark);$2',
    $content
);

// 6. Bot Message Content
$old_bot_msg = <<<EOD
        .message-row.bot .message-content {
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-top-left-radius: 4px;
            color: var(--dark);
        }
EOD;
$new_bot_msg = <<<EOD
        .message-row.bot .message-content {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-bottom-left-radius: 0;
            border-top-left-radius: var(--border-radius);
            color: var(--dark);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
EOD;
$content = str_replace($old_bot_msg, $new_bot_msg, $content);

// 7. User Message Content
$old_user_msg = <<<EOD
        .message-row.user .message-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
EOD;
$new_user_msg = <<<EOD
        .message-row.user .message-content {
            background: linear-gradient(to bottom right, var(--primary), var(--danger));
            color: white;
            border-bottom-right-radius: 0;
            box-shadow: var(--box-shadow-lg);
        }
EOD;
$content = str_replace($old_user_msg, $new_user_msg, $content);

// 8. Input Area
$old_input_section = <<<EOD
        .input-section {
            padding: 20px 30px;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
            z-index: 5;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 8px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }
EOD;
$new_input_section = <<<EOD
        .input-section {
            padding: 20px 30px;
            background: rgba(9, 9, 11, 0.8);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--glass-border);
            z-index: 5;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 8px 16px;
            transition: var(--transition);
            position: relative;
        }
EOD;
$content = str_replace($old_input_section, $new_input_section, $content);


// Write changes to file
file_put_contents($file, $content);
echo "New Red/White/Black Theme CSS overrides applied successfully.\n";
?>
