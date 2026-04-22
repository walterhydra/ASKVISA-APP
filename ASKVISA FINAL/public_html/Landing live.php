<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Ask Visa – Visa made simple, approved fast</title>
    <link rel="icon" href="assets/ask-visa-logo-final red.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern reset & Variables (White, Red, Black Theme) */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #dc2626;
            --primary-hover: #b91c1c;
            --primary-light: rgba(220, 38, 38, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-light: rgba(255, 255, 255, 0.7);
            --bg-alt: rgba(248, 250, 252, 0.5);
            --surface: rgba(255, 255, 255, 0.85);
            --border: rgba(226, 232, 240, 0.8);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 24px;
            --radius-full: 9999px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: transparent;
            color: var(--text-main);
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
        }

        /* Fading Background Slideshow */
        .sky-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            overflow: hidden; /* Prevent any minor bleed */
            background: #000; /* Fallback black */
        }

        .bg-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            image-rendering: -webkit-optimize-contrast;
            opacity: 0;
            animation: slideFade 40s infinite; /* Slower 40s cycle (8s per slide) */
        }

        /* Overlay to maintain clarity and contrast */
        .sky-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.25); /* Reduced opacity from 0.45 to 0.25 for much clearer images */
            z-index: 10; /* Above images, below content */
            pointer-events: none;
        }

        /* Staggered starts for seamless, slower transitions */
        .bg-slide:nth-child(1) { background-image: url('assets/Background.jpeg');  animation-delay: 0s; }
        .bg-slide:nth-child(2) { background-image: url('assets/Background2.jpeg'); animation-delay: 8s; }
        .bg-slide:nth-child(3) { background-image: url('assets/Background3.jpeg'); animation-delay: 16s; }
        .bg-slide:nth-child(4) { background-image: url('assets/Background4.jpeg'); animation-delay: 24s; }
        .bg-slide:nth-child(5) { background-image: url('assets/Background5.jpeg'); animation-delay: 32s; }

        @keyframes slideFade {
            0%   { opacity: 0; }
            8%   { opacity: 1; }  /* Fade in slowly over 3.2s */
            20%  { opacity: 1; }  /* Total time active is 8s */
            28%  { opacity: 0; }  /* Fade out slowly over 3.2s */
            100% { opacity: 0; }
        }

        a, button {
            cursor: pointer;
            transition: var(--transition);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
            color: var(--text-main);
        }

        .section-subhead {
            font-size: 1.125rem;
            color: var(--text-muted);
            margin-bottom: 48px;
            max-width: 600px;
        }

        /* Nav */
        nav {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 32px);
            max-width: 1280px;
            height: 72px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .logo img {
            height: 38px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-links a.desktop-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.95rem;
            position: relative;
        }

        .nav-links a.desktop-link:hover {
            color: var(--primary);
        }

        .hamburger-icon {
            display: none;
            font-size: 1.5rem;
            color: var(--text-main);
            cursor: pointer;
        }
        /* Hero */
        .hero {
            padding: 220px 24px 100px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }


        .hero > * {
            z-index: 1;
        }

        .hero > * {
            z-index: 1;
        }

        .hero-badge {
            background: var(--bg-light);
            color: var(--primary);
            border: 1px solid var(--primary-light);
            padding: 8px 20px;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 24px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .hero h1 {
            font-size: 2.8rem; /* Balanced big font that fits long phrases */
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin-bottom: 24px;
            max-width: 95vw; /* Use viewport width to prevent cutting on any screen */
            width: 100%;
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero h1 .highlight {
            color: var(--primary);
        }

        /* Vertical Rolling Carousel Looping Perfected - Forward Only */
        .headline-carousel {
            height: 1.2em; /* Fixed height relative to font size */
            line-height: 1.2em;
            overflow: hidden;
            position: relative;
            margin-top: 8px;
            width: 100%;
        }

        .carousel-track {
            display: flex;
            flex-direction: column;
            animation: rollHeadlineInfinite 12s cubic-bezier(0.85, 0, 0.15, 1) infinite;
        }

        .carousel-track > div {
            height: 1.2em;
            display: flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            padding: 0 40px; /* Increased padding for safer side clearance */
        }

        @keyframes rollHeadlineInfinite {
            0%, 25% { transform: translateY(0); }
            30%, 55% { transform: translateY(-25%); }
            60%, 85% { transform: translateY(-50%); }
            90%, 100% { transform: translateY(-75%); }
        }

        @keyframes rollHeadline {
            0%, 20% { transform: translateY(0); }
            25%, 45% { transform: translateY(-25%); }
            50%, 70% { transform: translateY(-50%); }
            75%, 95% { transform: translateY(-75%); }
            100% { transform: translateY(0); }
        }

        @media (max-width: 1200px) {
            .hero h1 { font-size: 3rem; }
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.22rem; }
            .headline-carousel { height: 2.4em; line-height: 1.2em; }
            .carousel-track > div { height: 2.4em; white-space: normal; }
            @keyframes rollHeadlineInfinite {
                0%, 25% { transform: translateY(0); }
                30%, 55% { transform: translateY(-25%); }
                60%, 85% { transform: translateY(-50%); }
                90%, 100% { transform: translateY(-75%); }
            }
        }

        .hero-subhead {
            font-size: 1.25rem;
            color: #000; /* Dark black as requested */
            font-family: 'Plus Jakarta Sans', sans-serif; /* Professional premium font */
            font-weight: 700; /* Bold for maximum readability */
            letter-spacing: -0.01em; /* Slight tightening for professional look */
            max-width: 580px; /* Slight increase to accommodate new font structure */
            margin-bottom: 48px;
        }

        /* Modern Search */
        .search-container {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: var(--radius-full);
            padding: 8px 8px 8px 24px;
            display: inline-flex;
            align-items: center;
            width: 100%;
            max-width: 640px;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
        }

        .search-container:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .search-icon {
            color: var(--text-muted);
            font-size: 1.2rem;
            margin-right: 12px;
        }

        .search-container select {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-main);
            font-size: 1.05rem;
            font-weight: 500;
            outline: none;
            appearance: none;
            cursor: pointer;
            padding: 12px 0;
        }

        .search-btn {
            background: var(--text-main);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            background: #000;
            box-shadow: var(--shadow-md);
        }

        .trust-strip {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 64px;
            flex-wrap: wrap;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-main);
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--bg-light);
            border: 1px solid var(--border);
            padding: 10px 24px;
            border-radius: var(--radius-full);
            box-shadow: var(--shadow-sm);
        }

        .trust-item i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Trending section */
        .trending {
            padding: 100px 0;
            background: var(--bg-light);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 32px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: var(--border);
            box-shadow: var(--shadow-lg);
        }

        .card-img-container {
            height: 220px;
            position: relative;
            overflow: hidden;
        }

        .card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .card:hover .card-img {
            transform: scale(1.08);
        }

        .badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--bg-light);
            color: var(--text-main);
            padding: 6px 12px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: var(--shadow-sm);
        }

        .badge i {
            color: var(--primary);
        }

        .card-content {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .country-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .visa-info {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 24px;
            flex: 1;
        }

        .processing-time {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-main);
            font-weight: 700;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .processing-time i {
            color: var(--primary);
        }

        /* How it works */
        .how-it-works {
            padding: 100px 0;
            background: var(--bg-alt);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .step-item {
            background: var(--bg-light);
            border: 1px solid var(--border);
            padding: 40px 32px;
            border-radius: var(--radius-lg);
            text-align: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .step-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .step-icon {
            width: 72px;
            height: 72px;
            background: var(--primary-light);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 24px;
            transition: var(--transition);
        }

        .step-item:hover .step-icon {
            transform: scale(1.1) rotate(-5deg);
            background: var(--primary);
            color: white;
        }

        .step-item h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--text-main);
        }

        .step-item p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Testimonials */
        .testimonials {
            padding: 100px 0;
            background: var(--bg-light);
            overflow: hidden;
            position: relative;
        }

        .testimonial-slider {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            min-height: 280px;
        }

        .testimonial-slide {
            position: absolute;
            top: 0; left: 0; width: 100%;
            opacity: 0;
            transform: translateX(40px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 40px;
            background: var(--bg-alt);
            border: 1px solid var(--border);
            padding: 48px;
            border-radius: var(--radius-lg);
            pointer-events: none;
        }

        .testimonial-slide.active {
            opacity: 1;
            transform: translateX(0);
            pointer-events: all;
            position: relative;
        }

        .slide-content {
            flex: 1;
        }

        .quote-icon {
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.2;
            margin-bottom: 20px;
        }

        .review-text {
            font-size: 1.25rem;
            font-style: italic;
            margin-bottom: 32px;
            color: var(--text-main);
            font-weight: 500;
        }

        .reviewer {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .reviewer-info strong {
            display: block;
            font-size: 1.05rem;
            color: var(--text-main);
        }

        .reviewer-info span {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .stamp-wrapper {
            flex: 0 0 160px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .stamp {
            width: 140px;
            height: 140px;
            border: 3px solid var(--primary);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            transform: rotate(-15deg);
        }

        .stamp-inner {
            border: 1.5px dashed var(--primary);
            width: 85%;
            height: 85%;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* Contact Section */
        .contact-section {
            padding: 100px 0;
            background: var(--bg-alt);
        }

        .contact-wrapper {
            display: flex;
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .contact-form {
            flex: 1.5;
            padding: 56px;
        }

        .contact-form h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .contact-form p {
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        .form-row {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .form-group input, .form-group textarea {
            width: 100%;
            background: var(--bg-alt);
            border: 1px solid var(--border);
            padding: 14px 16px;
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
            transition: var(--transition);
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
            background: var(--bg-light);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .contact-info {
            flex: 1;
            background: var(--text-main);
            color: white;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .contact-info::before {
            content: '';
            position: absolute;
            background: var(--primary);
            width: 300px;
            height: 300px;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            bottom: -100px;
            right: -100px;
        }

        .contact-info h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 32px;
            line-height: 1.2;
            z-index: 1;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            font-size: 1.05rem;
            z-index: 1;
        }

        .info-item i {
            font-size: 1.2rem;
            color: var(--primary);
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        /* Footer */
        footer {
            background: var(--bg-light);
            border-top: 1px solid var(--border);
            padding: 64px 24px 32px;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 48px;
        }

        .footer-logo img {
            height: 44px;
        }

        .footer-links {
            display: flex;
            gap: 80px;
        }

        .footer-col h4 {
            margin-bottom: 24px;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .footer-col a {
            display: block;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .footer-col a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            color: var(--text-muted);
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--bg-light);
            border: 1px solid var(--border);
            width: 90%;
            max-width: 560px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            transform: translateY(30px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0) scale(1);
        }

        .modal-hero {
            height: 220px;
            position: relative;
        }

        .modal-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .close-modal {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--bg-light);
            border: transparent;
            color: var(--text-main);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            z-index: 10;
            box-shadow: var(--shadow-sm);
        }

        .close-modal:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 32px;
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--text-main);
        }

        .modal-meta {
            display: flex;
            gap: 24px;
            color: var(--text-muted);
            margin-bottom: 24px;
            font-weight: 600;
        }

        .modal-alert {
            background: var(--primary-light);
            color: var(--primary-hover);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .modal-alert i {
            font-size: 1.2rem;
        }

        .docs-list {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
            margin-bottom: 32px;
        }

        .docs-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: var(--text-main);
        }

        .docs-list i {
            color: var(--primary);
        }

        .apply-btn {
            display: block;
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 16px;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .apply-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .animate-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .animate-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ORIGINAL ENTRANCE STYLE PRESERVED */
        #entrance-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.8s ease-in-out;
        }

        .entrance-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        #preloader-logo {
            height: 120px;
            width: auto;
            margin-bottom: 24px;
            transform-origin: center center;
            transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
        }

        #welcome-text {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.8s ease-out;
            margin-top: 20px;
            text-align: center;
            white-space: nowrap;
        }

        /* Mobile */
        @media (max-width: 900px) {
            .contact-wrapper { flex-direction: column; }
            .steps-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 3.5rem; }
        }

        @media (max-width: 768px) {
            nav { width: calc(100% - 32px); padding: 0 16px; }
            .desktop-link { display: none; }
            .hamburger-icon { display: block; }
            .hero h1 { font-size: 2.5rem; }
            .search-container { flex-direction: column; border-radius: 24px; padding: 16px; gap: 16px; }
            .search-btn { width: 100%; justify-content: center; }
            .form-row { flex-direction: column; }
            .footer-links { flex-direction: column; gap: 32px; }
        }
    </style>
</head>

<body>
    <div class="sky-container">
        <div class="bg-slide"></div>
        <div class="bg-slide"></div>
        <div class="bg-slide"></div>
        <div class="bg-slide"></div>
        <div class="bg-slide"></div>
    </div>
    <!-- ORIGINAL ENTRANCE ANIMATION HTML PRESERVED -->
    <div id="entrance-overlay">
        <div class="entrance-content">
            <img src="assets/ask-visa-logo-final.png" id="preloader-logo" alt="Ask Visa">
            <h1 id="welcome-text">Welcome to Ask Visa</h1>
        </div>
    </div>

    <!-- Nav -->
    <nav>
        <a href="#" class="logo">
            <img src="assets/ask-visa-logo-final.png" alt="Ask Visa Logo" id="nav-logo-real" style="opacity: 0;">
        </a>
        <div class="nav-links">
            <a href="#" class="desktop-link">Home</a>
            <a href="#trending" class="desktop-link">Services</a>
            <a href="#contact" class="desktop-link">Contact</a>
            <i class="fas fa-bars hamburger-icon" id="hamburger-btn"></i>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" style="display:none; position:fixed; inset:0; background:var(--bg-light); z-index:2000; flex-direction:column; align-items:center; justify-content:center; gap:32px; font-size:1.5rem; font-weight:700;">
        <i class="fas fa-times" id="close-mobile" style="position:absolute; top:32px; right:32px; cursor:pointer; color:var(--text-main);"></i>
        <a href="#" style="color:var(--text-main); text-decoration:none;">Home</a>
        <a href="#trending" style="color:var(--text-main); text-decoration:none;">Services</a>
        <a href="#contact" style="color:var(--text-main); text-decoration:none;">Contact</a>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-badge animate-up">
            <i class="fas fa-shield-alt"></i> 99.3% Approval Rate
        </div>
        <h1 class="animate-up" style="animation-delay: 0.1s;">
            <div class="headline-carousel">
                <div class="carousel-track">
                    <div>World travel: <span class="highlight" style="margin-left: 8px;">Made simple. Made possible.</span></div>
                    <div>Navigating visas, <span class="highlight" style="margin-left: 8px;">so you can navigate the world.</span></div>
                    <div>We speak visa, <span class="highlight" style="margin-left: 8px;">so you can speak hello in any language.</span></div>
                    <div>World travel: <span class="highlight" style="margin-left: 8px;">Made simple. Made possible.</span></div>
                </div>
            </div>
        </h1>
        <p class="hero-subhead animate-up" style="animation-delay: 0.2s;">
            Premium e-visa processing for global destinations.<br>Upload your documents securely and get approved in days.
        </p>

        <form action="index.php" method="GET" class="search-container animate-up" style="animation-delay: 0.3s;">
            <i class="fas fa-plane search-icon"></i>
            <select name="country" required>
                <option value="" disabled selected>Where are you flying to?</option>
                <option value="Thailand">🇹🇭 Thailand</option>
                <option value="Singapore">🇸🇬 Singapore</option>
                <option value="Malaysia">🇲🇾 Malaysia</option>
                <option value="Vietnam">🇻🇳 Vietnam</option>
                <option value="Dubai (UAE)">🇦🇪 Dubai (UAE)</option>
            </select>
            <button type="submit" class="search-btn">Explore <i class="fas fa-arrow-right"></i></button>
        </form>

        <div class="trust-strip animate-up" style="animation-delay: 0.4s;">
            <div class="trust-item"><i class="fas fa-bolt"></i> Fast 2-5 days</div>
            <div class="trust-item"><i class="fas fa-circle-check"></i> Hassel-free process</div>
            <div class="trust-item"><i class="fas fa-headset"></i> 24/7 Expert support</div>
        </div>
    </section>

    <!-- Trending -->
    <section class="trending" id="trending">
        <div class="container">
            <h2 class="section-title animate-up">Trending Destinations</h2>
            <p class="section-subhead animate-up">Seamless entry to the world's top locations</p>

            <div class="cards-grid">
                <!-- Card 1 -->
                <a href="javascript:void(0)" class="card animate-up" 
                    onclick="openCountryModal('Thailand', 'Tourist Visa & VOA', '4 days', 'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport scan', 'Passport photo', 'Return ticket', 'Hotel booking'])">
                    <div class="card-img-container">
                        <img src="https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Thailand">
                        <div class="badge"><i class="fas fa-fire"></i> Most Popular</div>
                    </div>
                    <div class="card-content">
                        <h3 class="country-name">Thailand</h3>
                        <p class="visa-info">Express e-Visa • Multi & Single Entry</p>
                        <div class="processing-time"><i class="fas fa-clock"></i> 4 Days Processing</div>
                    </div>
                </a>

                <!-- Card 2 -->
                <a href="javascript:void(0)" class="card animate-up" style="transition-delay:0.1s"
                    onclick="openCountryModal('Dubai (UAE)', '30/60 Days Tourist', '2 days', 'https://images.unsplash.com/photo-1546412414-e1885259563a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport scan', 'Passport photo', 'Insurance'])">
                    <div class="card-img-container">
                        <img src="https://images.unsplash.com/photo-1546412414-e1885259563a?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Dubai">
                    </div>
                    <div class="card-content">
                        <h3 class="country-name">Dubai (UAE)</h3>
                        <p class="visa-info">30/60 Days • Express Available</p>
                        <div class="processing-time"><i class="fas fa-clock"></i> 2 Days Processing</div>
                    </div>
                </a>

                <!-- Card 3 -->
                <a href="javascript:void(0)" class="card animate-up" style="transition-delay:0.2s"
                    onclick="openCountryModal('Singapore', 'e-Visa Application', '5 days', 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport scan', 'Passport photo', 'Hotel booking', 'Flight itinerary'])">
                    <div class="card-img-container">
                        <img src="https://images.unsplash.com/photo-1525625293386-3f8f99389edd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Singapore">
                    </div>
                    <div class="card-content">
                        <h3 class="country-name">Singapore</h3>
                        <p class="visa-info">Fully Online Digital Processing</p>
                        <div class="processing-time"><i class="fas fa-clock"></i> 5 Days Processing</div>
                    </div>
                </a>
                
                <!-- Card 4 -->
                <a href="javascript:void(0)" class="card animate-up" style="transition-delay:0.3s"
                    onclick="openCountryModal('Vietnam', '30 Days Tourist', '3 days', 'https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80', ['Passport scan', 'Passport photo', 'Entry Date'])">
                    <div class="card-img-container">
                        <img src="https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="card-img" alt="Vietnam">
                    </div>
                    <div class="card-content">
                        <h3 class="country-name">Vietnam</h3>
                        <p class="visa-info">30 Days Single Entry</p>
                        <div class="processing-time"><i class="fas fa-clock"></i> 3 Days Processing</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title animate-up">Streamlined Process</h2>
            <p class="section-subhead animate-up">Three steps to your digital visa</p>

            <div class="steps-grid">
                <div class="step-item animate-up">
                    <div class="step-icon"><i class="fas fa-map-location-dot"></i></div>
                    <h3>1. Select Destination</h3>
                    <p>Choose your country and visa type. We will give you a dynamic checklist of required documents.</p>
                </div>
                <div class="step-item animate-up" style="transition-delay:0.1s">
                    <div class="step-icon"><i class="fas fa-file-arrow-up"></i></div>
                    <h3>2. Secure Upload</h3>
                    <p>Upload your passport and photos to our bank-grade secure server for instant verification.</p>
                </div>
                <div class="step-item animate-up" style="transition-delay:0.2s">
                    <div class="step-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <h3>3. Receive e-Visa</h3>
                    <p>Your approved e-visa arrives directly in your inbox, ready to print or scan at the border.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title animate-up" style="text-align:center;">Client Stories</h2>
            <p class="section-subhead animate-up" style="text-align:center; margin: 0 auto 48px;">Join thousands of satisfied global travelers</p>

            <div class="testimonial-slider animate-up">
                <div class="testimonial-slide active">
                    <div class="slide-content">
                        <i class="fas fa-quote-left quote-icon"></i>
                        <p class="review-text">"Flawless execution. Uploaded my Thailand documents on Monday, received the verified e-visa on Wednesday. The interface is stunning and easy to use."</p>
                        <div class="reviewer">
                            <div class="avatar">RK</div>
                            <div class="reviewer-info">
                                <strong>Ravi K.</strong>
                                <span>Design Director, Bengaluru</span>
                            </div>
                        </div>
                    </div>
                    <div class="stamp-wrapper">
                        <div class="stamp">
                            <div class="stamp-inner">
                                <span>Verified</span>
                                <i class="fas fa-check" style="margin-top:4px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="contact-wrapper animate-up">
                <div class="contact-form">
                    <h2>Get in Touch</h2>
                    <p>Priority support for all your visa inquiries.</p>
                    <form action="#" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" placeholder="john@example.com">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Destination</label>
                                <input type="text" placeholder="e.g., Dubai">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" placeholder="+1 234 567 890">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea placeholder="How can we help you today?"></textarea>
                        </div>
                        <button class="search-btn" style="margin-top:16px;">Send Inquiry <i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
                <div class="contact-info">
                    <h3>We're here to help you travel.</h3>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong style="display:block;">Email Us</strong>
                            <span>support@askvisa.com</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong style="display:block;">Call Support</strong>
                            <span>+91 78807 89486</span>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fab fa-whatsapp"></i>
                        <div>
                            <strong style="display:block;">WhatsApp</strong>
                            <span>+91 78629 92570</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="assets/ask-visa-logo-final.png" alt="Ask Visa">
                <p style="color:var(--text-muted); margin-top:16px; max-width:250px; font-weight:500;">Premium digital visa processing for the modern global traveler.</p>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="#">About Us</a>
                    <a href="#">Careers</a>
                    <a href="#">Security</a>
                </div>
                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="privacy_policy.php">Privacy Policy</a>
                    <a href="terms_of_use.php">Terms of Service</a>
                    <a href="#">Refund Policy</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2026 Ask Visa. All rights reserved. Building better borders.
        </div>
    </footer>

    <!-- Modal -->
    <div class="modal-overlay" id="countryModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeCountryModal()"><i class="fas fa-times"></i></button>
            <div class="modal-hero">
                <img id="modalImg" src="" alt="Destination">
            </div>
            <div class="modal-body">
                <h2 class="modal-title" id="modalTitle">Country</h2>
                <div class="modal-meta">
                    <span id="modalVisaType"><i class="fas fa-passport"></i> Tourist Visa</span>
                    <span id="modalTime"><i class="fas fa-clock"></i> 4 days</span>
                </div>
                <div class="modal-alert">
                    <i class="fas fa-shield-alt"></i>
                    <span><strong>99.3% approval rate</strong> for applications matching our checklist.</span>
                </div>
                <h3 style="margin-bottom:8px;font-size:1.1rem;font-weight:700;">Required Documents</h3>
                <ul class="docs-list" id="modalDocs"></ul>
                <a href="#" id="applyBtn" class="apply-btn">Start Application <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- JS Logic -->
    <script>
        // Modal Logic
        function openCountryModal(country, visaInfo, time, imgSrc, docs) {
            document.getElementById('modalTitle').textContent = country;
            document.getElementById('modalVisaType').innerHTML = '<i class="fas fa-passport"></i> ' + visaInfo;
            document.getElementById('modalTime').innerHTML = '<i class="fas fa-clock"></i> ' + time;
            document.getElementById('modalImg').src = imgSrc;
            document.getElementById('applyBtn').href = 'index.php?country=' + encodeURIComponent(country);

            const docsList = document.getElementById('modalDocs');
            docsList.innerHTML = '';
            docs.forEach(doc => {
                const li = document.createElement('li');
                li.innerHTML = '<i class="fas fa-check"></i> ' + doc;
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
            }, 300);
        }

        document.getElementById('countryModal').addEventListener('click', e => {
            if (e.target === document.getElementById('countryModal')) closeCountryModal();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeCountryModal();
        });

        // ORIGINAL ENTRANCE ANIMATION LOGIC PRESERVED
        window.addEventListener('load', () => {
            const overlay = document.getElementById('entrance-overlay');
            const preloaderLogo = document.getElementById('preloader-logo');
            const welcomeText = document.getElementById('welcome-text');
            const navLogo = document.getElementById('nav-logo-real');

            // 1. Initial Setup: Lock scroll and hide nav logo
            document.body.style.overflow = 'hidden';
            navLogo.style.opacity = '0';

            // 2. Timeline
            setTimeout(() => {
                // Show Welcome Text
                welcomeText.style.opacity = '1';
                welcomeText.style.transform = 'translateY(0)';
            }, 300);

            setTimeout(() => {
                // Hide Welcome Text
                welcomeText.style.opacity = '0';
                welcomeText.style.transform = 'translateY(-20px)';
            }, 2000);

            setTimeout(() => {
                // 3. FLIP Animation for Logo
                const startRect = preloaderLogo.getBoundingClientRect();
                const endRect = navLogo.getBoundingClientRect();

                // Calculate scales
                const scaleX = endRect.width / startRect.width;
                const scaleY = endRect.height / startRect.height;
                const scale = Math.min(scaleX, scaleY); 

                const startCenterX = startRect.left + startRect.width / 2;
                const startCenterY = startRect.top + startRect.height / 2;
                const endCenterX = endRect.left + endRect.width / 2;
                const endCenterY = endRect.top + endRect.height / 2;

                const translateX = endCenterX - startCenterX;
                const translateY = endCenterY - startCenterY;

                preloaderLogo.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
                overlay.style.backgroundColor = 'rgba(255,255,255,0)';

            }, 2800);

            setTimeout(() => {
                // 4. Swap Logos and Cleanup
                navLogo.style.transition = 'opacity 0.3s ease';
                navLogo.style.opacity = '1';
                preloaderLogo.style.opacity = '0';

                document.body.style.overflow = '';
                overlay.style.pointerEvents = 'none';

                setTimeout(() => {
                    overlay.remove();
                }, 1000);
            }, 3800); 
        });

        // Scroll Observer
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-up').forEach(el => observer.observe(el));
        
        // Mobile Menu
        const menuBtn = document.getElementById('hamburger-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeBtn = document.getElementById('close-mobile');
        
        if (menuBtn && mobileMenu && closeBtn) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.style.display = 'flex';
            });
            
            closeBtn.addEventListener('click', () => {
                mobileMenu.style.display = 'none';
            });

            mobileMenu.addEventListener('click', (e) => {
                if(e.target.tagName === 'A') mobileMenu.style.display = 'none';
            });
        }
    </script>
</body>
</html>