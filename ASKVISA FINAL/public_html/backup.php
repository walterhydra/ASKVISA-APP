<?php
session_start();

$visa_configs = [
    "thailand" => [
        "display_name" => "Thailand 🇹🇭",
        "questions" => [
            ["label" => "First name", "type" => "text"],
            ["label" => "Last name", "type" => "text"],
            ["label" => "Passport Number", "type" => "text"],
            ["label" => "Passport Front", "type" => "image"], 
            ["label" => "Passport Back", "type" => "image"],  
            ["label" => "Passport from", "type" => "text"], 
            ["label" => "Passport issue on", "type" => "text"], 
            ["label" => "Passport valid till", "type" => "text"], 
            ["label" => "Place of birth", "type" => "text"], 
            ["label" => "Date of birth", "type" => "text"], 
            ["label" => "Gender", "type" => "text"], 
            ["label" => "Arrival flight number", "type" => "text"], 
            ["label" => "Thailand arrival date", "type" => "text"], 
            ["label" => "Hotel name", "type" => "text"], 
            ["label" => "Hotel city", "type" => "text"],
            ["label" => "Applicant Email", "type" => "text"],
            ["label" => "Applicant Phone", "type" => "text"]
        ]
    ]
];

if (isset($_GET['ajax'])) {
    $msg = htmlspecialchars(trim($_POST['message'] ?? ''));
    $response = "";
    $all_data = null;
    $img_path = "";
    $progress = 0;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . $_FILES['image']['name'];
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) $img_path = $target;
    }

    if ($msg !== '' || $img_path !== '') {
        $p_num = $_SESSION['current_person_num'] ?? 1;
        $is_pdf = ($img_path && strtolower(pathinfo($img_path, PATHINFO_EXTENSION)) === 'pdf');

        if ($img_path !== '') {
            $_SESSION['messages'][] = ['role' => 'user', 'text' => $is_pdf ? "Uploaded PDF" : "Uploaded Image", 'img' => $img_path, 'is_pdf' => $is_pdf];
        } else {
            $_SESSION['messages'][] = ['role' => 'user', 'text' => $msg];
        }

        switch ($_SESSION['step']) {
            case 'country':
                $input = strtolower($msg);
                if (array_key_exists($input, $visa_configs)) {
                    $_SESSION['selected_country'] = $input;
                    $_SESSION['step'] = 'how_many';
                    $response = "Selected: **" . $visa_configs[$input]['display_name'] . "**. How many applicants?";
                } else { $response = "Currently, we only support **Thailand**. Please type 'Thailand'."; }
                break;

            case 'how_many':
                if (is_numeric($msg) && (int)$msg > 0) {
                    $_SESSION['total_people'] = (int)$msg;
                    $_SESSION['current_person_num'] = 1; $_SESSION['q_idx'] = 0; $_SESSION['step'] = 'details';
                    $response = "Applicant #1. **" . $visa_configs[$_SESSION['selected_country']]['questions'][0]['label'] . "?**";
                } else { $response = "Please enter a valid number."; }
                break;

            case 'details':
                $config = $visa_configs[$_SESSION['selected_country']];
                $current_q = $config['questions'][$_SESSION['q_idx']];

                if ($current_q['type'] === 'image' && !$img_path) {
                    $response = "I need a file for: **" . $current_q['label'] . "**. Please use the 📎 icon.";
                } else {
                    $_SESSION['collected_info']["applicant_$p_num"][$current_q['label']] = $img_path ?: $msg;
                    $_SESSION['q_idx']++;

                    if ($_SESSION['q_idx'] < count($config['questions'])) {
                        $next_q = $config['questions'][$_SESSION['q_idx']];
                        $response = "Next for Applicant #$p_num: **" . $next_q['label'] . "?**";
                    } else {
                        if ($_SESSION['current_person_num'] < $_SESSION['total_people']) {
                            $_SESSION['current_person_num']++;
                            $_SESSION['q_idx'] = 0;
                            $response = "Applicant #".($_SESSION['current_person_num']-1)." complete. Applicant #".$_SESSION['current_person_num'].": **" . $config['questions'][0]['label'] . "?**";
                        } else {
                            $_SESSION['step'] = 'order_email';
                            $response = "All applicants details collected! Now, please provide the **Primary Contact Email** for this order.";
                        }
                    }
                }
                break;

            case 'order_email':
                $_SESSION['collected_info']['order_contact_email'] = $msg;
                $_SESSION['step'] = 'order_phone';
                $response = "Thank you. Finally, what is the **Primary Contact Phone Number** for this order?";
                break;

            case 'order_phone':
                $_SESSION['collected_info']['order_contact_phone'] = $msg;
                $_SESSION['step'] = 'finish';
                $response = "All done! ✅ Your application has been finalized and logged to the console.";
                $all_data = $_SESSION['collected_info'];
                break;
        }

        $allow_upload = false;
        if ($_SESSION['step'] === 'details') {
            $config = $visa_configs[$_SESSION['selected_country']];
            $progress = round(($_SESSION['q_idx'] / count($config['questions'])) * 100);
            if (isset($config['questions'][$_SESSION['q_idx']])) {
                $allow_upload = ($config['questions'][$_SESSION['q_idx']]['type'] === 'image');
            }
        }

        $_SESSION['messages'][] = ['role' => 'bot', 'text' => $response];
        echo json_encode(['text' => $response, 'is_finished' => ($_SESSION['step'] === 'finish'), 'payload' => $all_data, 'progress' => $progress, 'allow_upload' => $allow_upload]);
    }
    exit;
}

if (isset($_POST['reset'])) { session_unset(); session_destroy(); header('Location: '.$_SERVER['PHP_SELF']); exit; }
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [['role'=>'bot','text'=>'Hello! 👋 Which country are you applying for?']];
    $_SESSION['step'] = 'country';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visa Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #ffffff; --sidebar-bg: #f8fafd; --text: #1f1f1f; 
            --user-bubble: #e8f0fe; --bot-bubble: #f1f3f4; --input-bg: #f1f3f4; 
            --border: #dee2e6; --accent: #1a73e8; --progress-bg: #e0e0e0;
            --scrollbar-thumb: #c1c1c1;
        }
        body.dark { 
            --bg: #131314; --sidebar-bg: #1e1f20; --text: #e3e3e3; 
            --user-bubble: #2b2c2e; --bot-bubble: #1e1f20; --input-bg: #1e1f20; 
            --border: #444746; --accent: #8ab4f8; --progress-bg: #333;
            --scrollbar-thumb: #444746;
        }

        /* Integrated Scrollbar Styling */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: var(--scrollbar-thumb); 
            border-radius: 10px;
            border: 2px solid var(--bg); /* Creates a 'floating' effect */
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* Firefox support */
        * { scrollbar-width: thin; scrollbar-color: var(--scrollbar-thumb) transparent; }

        body { margin: 0; font-family: 'Google Sans', sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; overflow: hidden; }
        .sidebar { width: 260px; background: var(--sidebar-bg); padding: 24px 16px; border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        
        .chat-container { 
            flex: 1; 
            overflow-y: auto; 
            padding: 40px 10% 160px 10%; 
            scroll-behavior: smooth;
            /* Mask to make text fade out at the very top scroll edge */
            mask-image: linear-gradient(to bottom, transparent, black 40px, black calc(100% - 100px), transparent);
        }

        .message-row { display: flex; margin-bottom: 24px; }
        .message-content { padding: 12px 18px; border-radius: 18px; max-width: 75%; font-size: 15px; }
        .bot-content { background: var(--bot-bubble); border-top-left-radius: 4px; }
        .user-row { flex-direction: row-reverse; }
        .user-content { background: var(--user-bubble); border-bottom-right-radius: 4px; }
        .msg-img { max-width: 240px; border-radius: 8px; margin-top: 10px; cursor: zoom-in; border: 1px solid var(--border); }
        
        #previewTray { display: none; position: absolute; bottom: 100px; left: 10%; background: var(--sidebar-bg); border: 1px solid var(--border); padding: 8px 12px; border-radius: 12px; align-items: center; gap: 12px; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        #previewImg { height: 50px; width: 50px; object-fit: cover; border-radius: 6px; }

        #lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; }
        #lbContainer { width: 85%; height: 85%; display: flex; justify-content: center; }
        #lbImg { max-width: 100%; max-height: 100%; border-radius: 8px; display: none; }
        #lbPdf { width: 100%; height: 100%; background: white; border-radius: 8px; display: none; border: none; }

        .pdf-card { cursor: pointer; display: flex; align-items: center; gap: 10px; background: var(--bg); border: 1px solid var(--border); padding: 10px; border-radius: 12px; margin-top: 10px; }

        .progress-container { width: 100%; height: 6px; background: var(--progress-bg); border-radius: 3px; margin: 10px 0 20px 0; overflow: hidden; }
        .progress-bar { width: 0%; height: 100%; background: var(--accent); transition: 0.5s; }

        #confirmOverlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .confirm-card { background: var(--bg); padding: 24px; border-radius: 16px; width: 300px; text-align: center; border: 1px solid var(--border); }

        .input-area { position: absolute; bottom: 0; left: 0; right: 0; padding: 20px 10% 40px 10%; background: linear-gradient(transparent, var(--bg) 40%); pointer-events: none; }
        .input-wrapper { background: var(--input-bg); border-radius: 32px; display: flex; align-items: center; padding: 4px 20px; border: 1px solid var(--border); pointer-events: auto; }
        .input-wrapper input[type="text"] { flex: 1; background: transparent; border: none; outline: none; padding: 14px 0; color: var(--text); font-size: 16px; }
        .attach-label { cursor: not-allowed; padding: 8px; font-size: 20px; opacity: 0.3; transition: 0.3s; }
        .attach-label.active { cursor: pointer; opacity: 1; color: var(--accent); }

        .theme-container { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.05); padding: 12px; border-radius: 20px; margin-top: 10px; }
        .switch { position: relative; width: 48px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; inset: 0; background-color: #dee2e6; cursor: pointer; border-radius: 34px; transition: .4s; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: #ffca28; border-radius: 50%; transition: .4s; }
        input:checked + .slider { background-color: #2c3e50; }
        input:checked + .slider:before { transform: translateX(24px); background-color: #f1f3f4; box-shadow: inset -5px 0px 0px 0px #2c3e50; }
    </style>
</head>
<body>
<div id="lightbox" onclick="closeLightbox()">
    <div id="lbContainer" onclick="event.stopPropagation()">
        <img id="lbImg" src="">
        <iframe id="lbPdf" src=""></iframe>
    </div>
</div>

<div id="confirmOverlay">
    <div class="confirm-card">
        <h3>Reset Data?</h3>
        <p>All progress will be lost.</p>
        <button onclick="toggleConfirm(false)" style="padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:none; color:var(--text); cursor:pointer;">Cancel</button>
        <form method="POST" style="display:inline;"><button name="reset" style="padding:8px 16px; border-radius:8px; border:none; background:#ea4335; color:white; cursor:pointer;">Confirm</button></form>
    </div>
</div>

<div class="sidebar">
    <h2>VisaPortal</h2>
    <div class="progress-container"><div id="pBar" class="progress-bar"></div></div>
    <div style="margin-top:auto">
        <button onclick="toggleConfirm(true)" style="width:100%; padding:10px; border-radius:20px; border:1px solid var(--border); background:none; color:var(--text); cursor:pointer; margin-bottom:10px;">New Application</button>
        <div class="theme-container">
            <span style="font-size:13px">Dark Mode</span>
            <label class="switch"><input type="checkbox" id="themeToggle"><span class="slider"></span></label>
        </div>
    </div>
</div>

<div style="flex:1; display:flex; flex-direction:column; position:relative;">
    <div class="chat-container" id="chat">
        <?php foreach($_SESSION['messages'] as $m): ?>
            <div class="message-row <?= $m['role'] === 'user' ? 'user-row' : '' ?>">
                <div class="message-content <?= $m['role'] === 'bot' ? 'bot-content' : 'user-content' ?>">
                    <?= $m['text'] ?>
                    <?php if(isset($m['img'])): ?>
                        <?php if(isset($m['is_pdf']) && $m['is_pdf']): ?>
                            <div class="pdf-card" onclick="openLightbox('<?= $m['img'] ?>')">
                                <span style="font-size: 24px;">📄</span> <span>Preview PDF</span>
                            </div>
                        <?php else: ?>
                            <br><img src="<?= $m['img'] ?>" class="msg-img" onclick="openLightbox(this.src)">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="previewTray">
        <img id="previewImg" src="">
        <span id="pdfPrevName" style="display:none; font-size:12px;">PDF Selected</span>
        <span onclick="clearPreview()" style="cursor:pointer; font-weight:bold;">✕</span>
    </div>
    
    <div class="input-area">
        <div class="input-wrapper">
            <label id="attachBtn" class="attach-label">📎<input type="file" id="fileInput" hidden accept="image/*,application/pdf" disabled onchange="handlePreview(this)"></label>
            <input type="text" id="msgInput" placeholder="Type here..." autocomplete="off">
            <button onclick="sendMessage()" style="background:none; border:none; color:var(--accent); font-weight:bold; cursor:pointer;">Send</button>
        </div>
    </div>
</div>

<script>
    const chat = document.getElementById('chat');
    const msgInput = document.getElementById('msgInput');
    const fileInput = document.getElementById('fileInput');
    const attachBtn = document.getElementById('attachBtn');
    const previewTray = document.getElementById('previewTray');
    const previewImg = document.getElementById('previewImg');
    const pdfPrevName = document.getElementById('pdfPrevName');
    const pdfIcon = "https://cdn-icons-png.flaticon.com/512/337/337946.png";
    let isProcessing = false;

    function openLightbox(src) { 
        const isPdf = src.toLowerCase().endsWith('.pdf') || src.startsWith('blob:');
        document.getElementById('lbImg').style.display = isPdf ? 'none' : 'block';
        document.getElementById('lbPdf').style.display = isPdf ? 'block' : 'none';
        if(isPdf) document.getElementById('lbPdf').src = src; else document.getElementById('lbImg').src = src;
        document.getElementById('lightbox').style.display = 'flex'; 
    }
    function closeLightbox() { document.getElementById('lightbox').style.display = 'none'; document.getElementById('lbPdf').src = ''; }
    function toggleConfirm(show) { document.getElementById('confirmOverlay').style.display = show ? 'flex' : 'none'; }

    function handlePreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = (e) => { 
                if(file.type === "application/pdf") {
                    previewImg.src = pdfIcon;
                    pdfPrevName.style.display = 'block';
                    pdfPrevName.innerText = file.name.substring(0,12) + "...";
                } else {
                    previewImg.src = e.target.result;
                    pdfPrevName.style.display = 'none';
                }
                previewTray.style.display = 'flex'; 
            };
            reader.readAsDataURL(file);
        }
    }
    function clearPreview() { fileInput.value = ""; previewTray.style.display = 'none'; }

    async function sendMessage() {
        if (isProcessing) return;
        const text = msgInput.value.trim();
        const file = fileInput.files[0];
        if (!text && !file) return;

        isProcessing = true;
        const formData = new FormData();
        formData.append('message', text);
        if (file) formData.append('image', file);

        const localUrl = file ? URL.createObjectURL(file) : null;
        appendMessage('user', text || (file.type === "application/pdf" ? "Sent PDF" : "Sent Image"), localUrl, file && file.type === "application/pdf");
        
        msgInput.value = ''; clearPreview();

        const response = await fetch('?ajax=1', { method: 'POST', body: formData });
        const data = await response.json();

        setTimeout(() => {
            appendMessage('bot', data.text);
            document.getElementById('pBar').style.width = data.progress + "%";
            attachBtn.classList.toggle('active', !!data.allow_upload);
            fileInput.disabled = !data.allow_upload;
            
            if (data.is_finished) {
                console.log("%c FINAL ORDER SUBMISSION ", "background:#1a73e8;color:#fff;font-weight:bold;padding:4px;");
                console.table(data.payload);
                msgInput.disabled = true;
                msgInput.placeholder = "Data stored to console.";
            }

            isProcessing = false;
            msgInput.focus();
        }, 400);
    }

    function appendMessage(role, text, imgSrc = null, isPdf = false) {
        const row = document.createElement('div');
        row.className = `message-row ${role === 'user' ? 'user-row' : ''}`;
        let content = `<div class="message-content ${role === 'bot' ? 'bot-content' : 'user-content'}">${text}`;
        if (imgSrc) {
            if(isPdf) {
                content += `<div class="pdf-card" onclick="openLightbox('${imgSrc}')"><img src="${pdfIcon}" width="24"> Preview PDF</div>`;
            } else {
                content += `<br><img src="${imgSrc}" class="msg-img" onclick="openLightbox(this.src)">`;
            }
        }
        content += `</div>`;
        row.innerHTML = content;
        chat.appendChild(row); chat.scrollTop = chat.scrollHeight;
    }

    document.getElementById('themeToggle').addEventListener('change', () => {
        document.body.classList.toggle('dark');
        localStorage.theme = document.body.classList.contains('dark') ? 'dark' : 'light';
    });

    msgInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendMessage(); });
    window.onload = () => { 
        if(localStorage.theme === 'dark') { document.body.classList.add('dark'); document.getElementById('themeToggle').checked = true; }
        chat.scrollTop = chat.scrollHeight;
    };
</script>
</body>
</html>