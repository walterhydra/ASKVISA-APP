<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <!-- Primary Meta Tags -->
    <title>Ask Visa – Visa made simple, approved fast</title>
    <meta name="title" content="Ask Visa – Visa assistance with 99.3% approval">
    <meta name="description" content="Get tourist eVisas for Thailand, Dubai, Singapore and more. 99.3% approval rate, average 3 day processing. Apply online in minutes.">
    <meta name="keywords" content="visa online, e-visa, Thailand visa, Dubai visa, Singapore visa, visa assistance">
    <meta name="author" content="Ask Visa">
    <!-- Favicon (simple inline for demo) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90' fill='%23C62828'>✈️</text></svg>">
    <!-- Font Awesome 6 (free) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ---------- GLOBAL RESET & VARIABLES ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #C62828;        /* Deep red – brand core */
            --primary-dark: #8B1E1E;   /* Darker for hover */
            --primary-light: #FFCDD2;  /* Tint for backgrounds */
            --secondary: #1E1E1E;      /* Almost black */
            --gray-100: #F8F9FC;
            --gray-200: #EDF0F5;
            --gray-300: #DCE1E8;
            --gray-600: #5E6F8C;
            --gray-800: #2A3A4B;
            --white: #FFFFFF;
            --shadow-sm: 0 8px 20px rgba(0,0,0,0.02), 0 4px 12px rgba(0,0,0,0.02);
            --shadow-md: 0 16px 32px -12px rgba(0,0,0,0.08);
            --shadow-lg: 0 24px 48px -16px rgba(198,40,40,0.12);
            --radius-md: 24px;
            --radius-lg: 32px;
            --radius-full: 999px;
            --transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--white);
            color: var(--secondary);
            line-height: 1.5;
            overflow-x: hidden;
        }

        a, button {
            cursor: pointer;
            transition: var(--transition);
        }

        /* ---------- TYPOGRAPHY & UTILITIES ---------- */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            color: var(--secondary);
        }

        .section-subhead {
            font-size: 1.125rem;
            color: var(--gray-600);
            margin-bottom: 48px;
            max-width: 600px;
        }

        /* ---------- NAVIGATION (clean, sticky) ---------- */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 32px;
            max-width: 1280px;
            margin: 0 auto;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 80px;
            margin-top: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.6);
            position: sticky;
            top: 16px;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .logo img {
            height: 100px;
            width: auto;
            display: block;
        }

        .nav-links {
            display: flex;
            gap: 40px;
            align-items: center;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .nav-links a:hover {
            color: var(--primary);
        }
        .nav-cta {
            background: var(--secondary);
            color: white !important;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            border: none;
        }
        .nav-cta:hover {
            background: var(--primary);
            color: white !important;
        }

        /* ---------- HERO SECTION (elevated) ---------- */
        .hero {
            padding: 64px 32px 80px;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .hero-badge {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 24px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(198,40,40,0.15);
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            max-width: 900px;
            margin-bottom: 24px;
            color: var(--secondary);
        }

        .hero .highlight {
            color: var(--primary);
            background: linear-gradient(145deg, rgba(198,40,40,0.1), transparent 70%);
            padding: 0 12px;
            position: relative;
            display: inline-block;
        }

        .hero-subhead {
            font-size: 1.25rem;
            color: var(--gray-600);
            max-width: 600px;
            margin-bottom: 40px;
        }

        /* ---------- SEARCH / DESTINATION SELECT ---------- */
        .search-container {
            background: white;
            border-radius: 80px;
            box-shadow: var(--shadow-md);
            padding: 8px 8px 8px 24px;
            display: inline-flex;
            align-items: center;
            width: 100%;
            max-width: 720px;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }
        .search-container:focus-within {
            box-shadow: 0 0 0 4px rgba(198,40,40,0.12), var(--shadow-md);
            border-color: var(--primary);
        }
        .search-icon {
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-right: 8px;
        }
        .search-container select {
            flex: 1;
            border: none;
            padding: 16px 8px 16px 0;
            font-size: 1rem;
            background: transparent;
            color: var(--secondary);
            font-weight: 500;
            outline: none;
            cursor: pointer;
        }
        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 36px;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 10px 20px -8px rgba(198,40,40,0.3);
        }
        .search-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
            box-shadow: 0 16px 24px -8px rgba(198,40,40,0.4);
        }
        .search-btn i {
            font-size: 0.9rem;
        }

        /* trust indicators */
        .trust-strip {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 56px;
            flex-wrap: wrap;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray-700);
            font-weight: 500;
            background: rgba(255,255,255,0.6);
            padding: 8px 20px;
            border-radius: 50px;
            backdrop-filter: blur(4px);
        }
        .trust-item i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* ---------- TRENDING CARDS SECTION ---------- */
        .trending {
            background: var(--gray-100);
            border-radius: 48px 48px 0 0;
            padding: 80px 32px;
            margin-top: 40px;
        }
        .trending .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 32px;
            margin-top: 24px;
        }

        .card {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--gray-200);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .card-img-container {
            height: 200px;
            position: relative;
            overflow: hidden;
        }
        .card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(0.15,0.75,0.4,1);
        }
        .card:hover .card-img {
            transform: scale(1.08);
        }

        .badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: white;
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
        }
        .badge i {
            color: #FFD700;
        }

        .card-content {
            padding: 24px 24px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .country-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }
        .visa-info {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.5;
            flex: 1;
        }
        .processing-time {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray-800);
            font-weight: 600;
            border-top: 1px solid var(--gray-200);
            padding-top: 16px;
            margin-top: auto;
        }
        .processing-time i {
            color: #2E7D32;
        }

        /* ---------- HOW IT WORKS + WHY US ---------- */
        .how-it-works {
            padding: 80px 32px;
            background: white;
        }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 40px;
            margin-top: 40px;
        }
        .step-item {
            text-align: center;
            padding: 0 16px;
        }
        .step-icon {
            background: var(--primary-light);
            width: 80px;
            height: 80px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--primary);
            font-size: 2rem;
            transform: rotate(0deg);
            transition: var(--transition);
        }
        .step-item:hover .step-icon {
            transform: rotate(-6deg) scale(1.05);
            background: var(--primary);
            color: white;
        }
        .step-item h3 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .step-item p {
            color: var(--gray-600);
        }

        /* ---------- TESTIMONIALS ---------- */
        .testimonials {
            background: var(--gray-100);
            padding: 80px 32px;
            border-radius: 48px;
        }
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-top: 40px;
        }
        .testimonial-card {
            background: white;
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--shadow-sm);
            border: 1px solid white;
            transition: var(--transition);
        }
        .testimonial-card:hover {
            border-color: var(--primary-light);
            box-shadow: var(--shadow-md);
        }
        .testimonial-card i {
            color: var(--primary);
            font-size: 1.8rem;
            opacity: 0.4;
            margin-bottom: 16px;
        }
        .testimonial-text {
            font-size: 1rem;
            color: var(--gray-800);
            margin-bottom: 24px;
            font-style: italic;
        }
        .customer {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .customer-avatar {
            background: var(--gray-200);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
        }

        /* ---------- CONTACT SECTION (elevated & consistent) ---------- */
        .contact-section {
            padding: 80px 32px;
            background: white;
        }
        .contact-wrapper {
            display: flex;
            background: var(--gray-100);
            border-radius: 48px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 60px;
        }
        .contact-form-container {
            flex: 1;
            padding: 56px 48px;
        }
        .contact-form-container h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .contact-form-container p {
            color: var(--gray-600);
            margin-bottom: 32px;
            font-size: 1.1rem;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-800);
        }
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid var(--gray-200);
            border-radius: 18px;
            font-size: 0.95rem;
            background: white;
            transition: var(--transition);
        }
        .form-group input:focus, 
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(198,40,40,0.08);
            outline: none;
        }
        .form-group textarea {
            height: 130px;
            resize: vertical;
        }
        .send-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px 38px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            border: 1px solid transparent;
        }
        .send-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 16px 24px -8px rgba(198,40,40,0.25);
        }

        .contact-image-container {
            flex: 0.9;
            background: linear-gradient(145deg, #C62828 0%, #A52424 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            position: relative;
        }
        .contact-image-container i {
            font-size: 6rem;
            opacity: 0.2;
            position: absolute;
        }
        .contact-quote {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            z-index: 2;
        }
        .contact-quote span {
            display: block;
            font-size: 1.1rem;
            font-weight: 400;
            opacity: 0.9;
            margin-top: 16px;
        }

        .contact-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .contact-card {
            background: white;
            padding: 40px 24px;
            border-radius: 32px;
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }
        .contact-card:hover {
            border-color: var(--primary);
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }
        .icon-box {
            background: var(--primary-light);
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--primary);
            font-size: 1.8rem;
        }
        .contact-card h3 {
            margin-bottom: 16px;
            font-weight: 700;
        }
        .contact-card p {
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        .social-icons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 20px;
        }
        .social-icons a {
            width: 44px;
            height: 44px;
            background: var(--gray-100);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-800);
            transition: var(--transition);
        }
        .social-icons a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-4px);
        }

        /* ---------- MODAL (refined) ---------- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(6px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 560px;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            transform: translateY(30px);
            transition: transform 0.3s cubic-bezier(0.2,0.9,0.3,1);
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        .close-modal {
            position: absolute;
            top: 20px; right: 20px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 40px; height: 40px;
            border-radius: 50%;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 20;
            backdrop-filter: blur(4px);
            transition: all 0.2s;
        }
        .close-modal:hover {
            background: var(--primary);
            transform: scale(1.05);
        }
        .modal-hero-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .modal-body {
            padding: 32px;
        }
        .modal-body h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .modal-info-row {
            display: flex;
            gap: 24px;
            margin-bottom: 28px;
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        .docs-list {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin: 20px 0 32px;
        }
        .docs-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: var(--gray-800);
        }
        .docs-list li i {
            color: var(--primary);
            font-size: 0.8rem;
        }
        .apply-btn {
            display: block;
            background: var(--primary);
            color: white;
            padding: 16px;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            text-align: center;
            transition: all 0.2s;
        }
        .apply-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 20px -6px rgba(198,40,40,0.35);
        }

        /* ---------- FOOTER (logo as image) ---------- */
        footer {
            background: var(--secondary);
            color: white;
            padding: 48px 32px 32px;
        }
        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 40px;
        }
        .footer-logo {
            display: flex;
            align-items: center;
        }
        .footer-logo img {
            height: 48px;
            width: auto;
            display: block;
            filter: brightness(1) invert(0); /* ensures red/white logo stays visible on dark bg */
        }
        .footer-links {
            display: flex;
            gap: 60px;
        }
        .footer-col a {
            color: #BBB;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        .footer-col a:hover {
            color: white;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 48px;
            color: #888;
            font-size: 0.9rem;
            border-top: 1px solid #333;
            margin-top: 48px;
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 1000px) {
            .contact-wrapper { flex-direction: column; }
            .contact-image-container { padding: 60px 20px; }
            .steps-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2.6rem; }
        }
        @media (max-width: 720px) {
            nav { flex-direction: column; gap: 16px; border-radius: 32px; }
            .nav-links { width: 100%; justify-content: center; flex-wrap: wrap; gap: 24px; }
            .hero h1 { font-size: 2rem; }
            .search-container { flex-wrap: wrap; border-radius: 40px; padding: 8px; }
            .search-btn { width: 100%; justify-content: center; margin-top: 8px; }
            .trust-strip { gap: 20px; }
            .cards-grid { grid-template-columns: 1fr; }
            .contact-cards { grid-template-columns: 1fr; }
            .footer-content { flex-direction: column; }
            .footer-links { flex-direction: column; gap: 30px; }
        }
        @media (max-width: 480px) {
            .form-row { flex-direction: column; gap: 0; }
        }
        .animate-up {
            animation: fadeUp 0.7s cubic-bezier(0.2,0.9,0.3,1) forwards;
            opacity: 0;
        }
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- ========== NAVIGATION with LOGO from assets/ask-visa-logo-final.png ========== -->
<nav>
    <a href="#" class="logo">
        <img src="assets/ask-visa-logo-final.png" alt="Ask Visa Logo">
    </a>
    <div class="nav-links">
        <a href="#">Home</a>
        <a href="#">Services</a>
        <a href="#contact">Contact</a>
        <a href="#" class="nav-cta">Log in</a>
    </div>
</nav>

<!-- ========== HERO SECTION ========== -->
<header class="hero">
    <div class="hero-badge animate-up">
        <i class="fas fa-shield-alt"></i> 99.3% approval · 2,30,000+ visas delivered
    </div>
    <h1 class="animate-up" style="animation-delay: 0.1s;">
        Get <span class="highlight">visa online</span><br>without the runaround
    </h1>
    <p class="hero-subhead animate-up" style="animation-delay: 0.15s;">
        From Thailand to Dubai – your e-visa, simplified. Average 3 day processing.
    </p>
    
    <!-- Search / destination select (refined) -->
    <form action="index.php" method="GET" class="search-container animate-up" style="animation-delay: 0.2s;">
        <i class="fas fa-search search-icon"></i>
        <select name="country" required>
            <option value="" disabled selected>Where are you flying to?</option>
            <option value="Thailand">🇹🇭 Thailand</option>
            <option value="Dubai (UAE)">🇦🇪 Dubai (UAE)</option>
            <option value="Singapore">🇸🇬 Singapore</option>
            <option value="Malaysia">🇲🇾 Malaysia</option>
            <option value="Vietnam">🇻🇳 Vietnam</option>
            <option value="Sri Lanka">🇱🇰 Sri Lanka</option>
            <option value="USA">🇺🇸 USA</option>
            <option value="UK">🇬🇧 United Kingdom</option>
            <option value="Australia">🇦🇺 Australia</option>
        </select>
        <button type="submit" class="search-btn">Explore <i class="fas fa-arrow-right"></i></button>
    </form>

    <!-- trust indicators -->
    <div class="trust-strip animate-up" style="animation-delay: 0.3s;">
        <div class="trust-item"><i class="fas fa-bolt"></i> 2–5 days</div>
        <div class="trust-item"><i class="fas fa-passport"></i> 99.3% approved</div>
        <div class="trust-item"><i class="fas fa-headset"></i> 24/7 support</div>
    </div>
</header>

<!-- ========== TRENDING DESTINATIONS ========== -->
<section class="trending">
    <div class="container">
        <h2 class="section-title animate-up">Trending destinations</h2>
        <p class="section-subhead animate-up">Most travellers this month are heading here</p>
        
        <div class="cards-grid">
            <!-- Thailand -->
            <div class="card animate-up" style="animation-delay: 0.1s;" onclick="openCountryModal('Thailand', 'Tourist Visa & Visa On Arrival', '4 days', 'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport front & back', 'Passport size photo', 'Return flight ticket', 'Hotel confirmation'])">
                <div class="card-img-container">
                    <img src="https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Thailand" loading="lazy">
                    <div class="badge"><i class="fas fa-check-circle"></i> 200K+ processed</div>
                </div>
                <div class="card-content">
                    <div class="country-name">🇹🇭 Thailand</div>
                    <p class="visa-info">Tourist & Visa on Arrival • e-visa ready</p>
                    <div class="processing-time"><i class="far fa-calendar-check"></i> Get visa in 4 days</div>
                </div>
            </div>
            <!-- Dubai -->
            <div class="card animate-up" style="animation-delay: 0.2s;" onclick="openCountryModal('Dubai (UAE)', '30/60 Days Tourist Visa', '2 days', 'https://images.unsplash.com/photo-1546412414-e1885259563a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport front & back', 'Passport size photo', 'Travel insurance (optional)'])">
                <div class="card-img-container">
                    <img src="https://images.unsplash.com/photo-1546412414-e1885259563a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Dubai" loading="lazy">
                    <div class="badge"><i class="fas fa-check-circle"></i> 50K+ processed</div>
                </div>
                <div class="card-content">
                    <div class="country-name">🇦🇪 Dubai (UAE)</div>
                    <p class="visa-info">30/60 days • express available</p>
                    <div class="processing-time"><i class="far fa-calendar-check"></i> Get visa in 2 days</div>
                </div>
            </div>
            <!-- Singapore -->
            <div class="card animate-up" style="animation-delay: 0.3s;" onclick="openCountryModal('Singapore', 'Electronic Visa (e-Visa)', '5 days', 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport front & back', 'Passport photo', 'Hotel booking', 'Flight itinerary'])">
                <div class="card-img-container">
                    <img src="https://images.unsplash.com/photo-1525625293386-3f8f99389edd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Singapore" loading="lazy">
                    <div class="badge"><i class="fas fa-check-circle"></i> 84K+ processed</div>
                </div>
                <div class="card-content">
                    <div class="country-name">🇸🇬 Singapore</div>
                    <p class="visa-info">e-Visa • fully online</p>
                    <div class="processing-time"><i class="far fa-calendar-check"></i> Get visa in 5 days</div>
                </div>
            </div>
            <!-- Vietnam -->
            <div class="card animate-up" style="animation-delay: 0.4s;" onclick="openCountryModal('Vietnam', '30 Days Tourist Visa', '3 days', 'https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport front & back', 'Passport photo', 'Entry date'])">
                <div class="card-img-container">
                    <img src="https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Vietnam" loading="lazy">
                    <div class="badge"><i class="fas fa-check-circle"></i> 74K+ processed</div>
                </div>
                <div class="card-content">
                    <div class="country-name">🇻🇳 Vietnam</div>
                    <p class="visa-info">30 days single entry</p>
                    <div class="processing-time"><i class="far fa-calendar-check"></i> Get visa in 3 days</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== HOW IT WORKS ========== -->
<section class="how-it-works">
    <div class="container">
        <h2 class="section-title animate-up">Visa in 3 steps</h2>
        <p class="section-subhead animate-up">We’ve made it ridiculously simple</p>
        <div class="steps-grid">
            <div class="step-item animate-up" style="animation-delay: 0.1s;">
                <div class="step-icon"><i class="fas fa-globe"></i></div>
                <h3>1. Choose country</h3>
                <p>Pick your destination and visa type. We show exactly what you need.</p>
            </div>
            <div class="step-item animate-up" style="animation-delay: 0.15s;">
                <div class="step-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                <h3>2. Upload documents</h3>
                <p>Passport scan, photo – that’s it. We verify them instantly.</p>
            </div>
            <div class="step-item animate-up" style="animation-delay: 0.2s;">
                <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                <h3>3. Get visa by email</h3>
                <p>We process and send your e-visa directly. No courier needed.</p>
            </div>
        </div>
    </div>
</section>

<!-- ========== TESTIMONIALS (social proof) ========== -->
<section class="testimonials">
    <div class="container">
        <h2 class="section-title animate-up">Trusted by frequent flyers</h2>
        <p class="section-subhead animate-up">Real travellers, real 5‑star experiences</p>
        <div class="testimonial-grid">
            <div class="testimonial-card animate-up" style="animation-delay: 0.1s;">
                <i class="fas fa-quote-right"></i>
                <div class="testimonial-text">“Applied for Thailand visa on Monday, got it on Wednesday. The approval rate is no joke – 5/5.”</div>
                <div class="customer">
                    <div class="customer-avatar">RK</div>
                    <div><strong>Ravi K.</strong><br>Bengaluru</div>
                </div>
            </div>
            <div class="testimonial-card animate-up" style="animation-delay: 0.2s;">
                <i class="fas fa-quote-right"></i>
                <div class="testimonial-text">“Dubai 60 days visa arrived in 38 hours. Support answered my call at 11pm. Lifesavers!”</div>
                <div class="customer">
                    <div class="customer-avatar">PJ</div>
                    <div><strong>Priya J.</strong><br>Mumbai</div>
                </div>
            </div>
            <div class="testimonial-card animate-up" style="animation-delay: 0.3s;">
                <i class="fas fa-quote-right"></i>
                <div class="testimonial-text">“The document checklist was spot on. No rejections, no back‑and‑forth. My go‑to visa service.”</div>
                <div class="customer">
                    <div class="customer-avatar">AS</div>
                    <div><strong>Arjun S.</strong><br>Delhi</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== CONTACT US (refined) ========== -->
<section id="contact" class="contact-section">
    <div class="container">
        <div class="contact-wrapper animate-up">
            <!-- Left: form -->
            <div class="contact-form-container">
                <h2>Drop us a line</h2>
                <p>We reply within 2 hours, usually sooner.</p>
                <form action="#" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Your name</label>
                            <input type="text" name="name" placeholder="e.g. Alex Chen" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="alex@example.com" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone (with country code)</label>
                            <input type="tel" name="phone" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" placeholder="Visa enquiry" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Your question</label>
                        <textarea name="message" placeholder="I need help with..." required></textarea>
                    </div>
                    <button type="submit" class="send-btn">Send message <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
            <!-- Right: elegant red panel with quote -->
            <div class="contact-image-container">
                <i class="fas fa-comment-dots"></i>
                <div class="contact-quote">
                    “Don’t hesitate<br>just ask”
                    <span>— we’re here 24/7</span>
                </div>
            </div>
        </div>

        <!-- contact cards -->
        <div class="contact-cards">
            <div class="contact-card animate-up" style="animation-delay: 0.1s;">
                <div class="icon-box"><i class="fas fa-envelope"></i></div>
                <h3>Email</h3>
                <p>hello@askvisa.com</p>
                <p>support@askvisa.com</p>
            </div>
            <div class="contact-card animate-up" style="animation-delay: 0.2s;">
                <div class="icon-box"><i class="fas fa-phone-alt"></i></div>
                <h3>Call / WhatsApp</h3>
                <p>+91 78807 89486</p>
                <p>+91 78629 92570</p>
            </div>
            <div class="contact-card animate-up" style="animation-delay: 0.3s;">
                <div class="icon-box"><i class="fas fa-share-nodes"></i></div>
                <h3>Social</h3>
                <p>Let’s connect · travel tips</p>
                <div class="social-icons">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== MODAL (Country detail) ========== -->
<div id="countryModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-modal" onclick="closeCountryModal()">&times;</button>
        <div class="modal-header">
            <img id="modalImg" src="" alt="destination preview" class="modal-hero-img">
        </div>
        <div class="modal-body">
            <h2 id="modalTitle">Country</h2>
            <div class="modal-info-row">
                <span id="modalVisaType"><i class="fas fa-passport"></i> Visa type</span>
                <span id="modalTime"><i class="far fa-clock"></i> Processing</span>
            </div>
            <div style="background: #FEF2F2; border-radius: 16px; padding: 12px 20px; margin-bottom: 16px;">
                <i class="fas fa-shield-alt" style="color: var(--primary);"></i> 
                <span style="font-weight:600;">99.3% approval</span> — we only ask for exactly what’s needed.
            </div>
            <div class="modal-section">
                <h3 style="font-size: 1.2rem; margin-bottom: 16px;"><i class="fas fa-list-check"></i> Documents required</h3>
                <ul id="modalDocs" class="docs-list"></ul>
            </div>
            <div class="modal-actions">
                <a id="applyBtn" href="#" class="apply-btn">
                    Apply now <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ========== FOOTER with LOGO from assets/ask-visa-logo-final.png ========== -->
<footer>
    <div class="footer-content">
        <div class="footer-logo">
            <img src="assets/ask-visa-logo-final.png" alt="Ask Visa Logo">
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4 style="color:white; margin-bottom: 20px;">Company</h4>
                <a href="#">About us</a>
                <a href="#">Careers</a>
                <a href="#">Blog</a>
            </div>
            <div class="footer-col">
                <h4 style="color:white; margin-bottom: 20px;">Support</h4>
                <a href="#contact">Contact</a>
                <a href="#">FAQs</a>
                <a href='privacy_policy.php'>Privacy policy</a>
                <a href="#">Terms of use</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Ask Visa. All rights reserved. | ✈️ Visa assistance simplified.</p>
    </div>
</footer>

<!-- ========== MODAL JAVASCRIPT ========== -->
<script>
    function openCountryModal(country, visaInfo, time, imgSrc, docs) {
        document.getElementById('modalTitle').textContent = country;
        document.getElementById('modalVisaType').innerHTML = '<i class="fas fa-passport"></i> ' + visaInfo;
        document.getElementById('modalTime').innerHTML = '<i class="far fa-clock"></i> ' + time;
        document.getElementById('modalImg').src = imgSrc;
        document.getElementById('applyBtn').href = 'index.php?country=' + encodeURIComponent(country);
        
        const docsList = document.getElementById('modalDocs');
        docsList.innerHTML = '';
        docs.forEach(doc => {
            const li = document.createElement('li');
            li.innerHTML = '<i class="fas fa-check-circle"></i> ' + doc;
            docsList.appendChild(li);
        });

        const modal = document.getElementById('countryModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
        
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = (window.innerWidth - document.documentElement.clientWidth) + 'px';
    }

    function closeCountryModal() {
        const modal = document.getElementById('countryModal');
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 250);
    }

    // close on overlay click
    document.getElementById('countryModal').addEventListener('click', function(e) {
        if (e.target === this) closeCountryModal();
    });

    // close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('countryModal');
            if (modal.classList.contains('active')) closeCountryModal();
        }
    });
</script>
</body>
</html>