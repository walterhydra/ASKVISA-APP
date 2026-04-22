<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - Ask Visa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #dc2626; /* Red */
            --secondary: #0f172a; /* Black */
            --text-dark: #0f172a;
            --text-gray: #64748b;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Sharp HD Original Background Image with Overlay */
        .sky-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
            background-image: url('assets/Background.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            image-rendering: -webkit-optimize-contrast;
        }

        .sky-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.45);
            z-index: -1;
        }

        /* Navbar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo img {
            height: 40px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            margin-left: 20px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 40px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        h1 {
            color: var(--secondary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .last-updated {
            text-align: center;
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 40px;
        }

        h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        p {
            margin-bottom: 15px;
            color: var(--text-gray);
            text-align: justify;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 15px;
            color: var(--text-gray);
        }

        li {
            margin-bottom: 10px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: var(--text-gray);
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            margin-top: 40px;
            background: var(--white);
        }
    </style>
</head>
<body>
    <div class="sky-container"></div>
    <nav>
        <a href="landing.php" class="logo">
            <img src="assets/ask-visa-logo-final.png" alt="Ask Visa Logo">
        </a>
        <div class="nav-links">
            <a href="landing.php">Home</a>
            <a href="index.php">Apply for Visa</a>
        </div>
    </nav>

    <div class="container">
        <h1>Terms of Use</h1>
        <p class="last-updated">Last Updated: <?php echo date('F d, Y'); ?></p>

        <p>Welcome to Ask Visa! By accessing or using our website, services, and tools, you agree to comply with and be bound by the following terms and conditions. Please read them carefully.</p>

        <h2>1. Acceptance of Terms</h2>
        <p>By using our services, you confirm that you are at least 18 years old and legally capable of entering into binding contracts. If you do not agree to these terms, you may not use our services.</p>

        <h2>2. Services Provided</h2>
        <p>Ask Visa provides visa processing assistance, travel documentation support, and related services. We act as an intermediary between you and relevant government authorities. We do not guarantee visa approval, as the final decision lies with the respective government bodies.</p>

        <h2>3. User Responsibilities</h2>
        <ul>
            <li>You must provide accurate, complete, and up-to-date information for your application.</li>
            <li>You are responsible for ensuring your passport generally has at least 6 months of validity beyond your travel dates.</li>
            <li>You must comply with all laws and regulations of the destination country.</li>
            <li>You agree not to use our platform for any fraudulent or illegal activities.</li>
        </ul>

        <h2>4. Fees and Payments</h2>
        <p>All service fees must be paid in full before we process your application. Government visa fees are separate and are subject to change without notice. Our service fees are non-refundable once the processing has begun, as outlined in our Refund Policy.</p>

        <h2>5. Limitation of Liability</h2>
        <p>Ask Visa shall not be liable for any indirect, incidental, special, or consequential damages resulting from your use of our services. We are not responsible for delays, rejections, or any other actions taken by government authorities/embassies.</p>
        <p>In no event shall our total liability exceed the amount of service fees paid by you to us.</p>

        <h2>6. Intellectual Property</h2>
        <p>All content on this website, including text, graphics, logos, and software, is the property of Ask Visa and is protected by copyright laws. You may not reproduce, distribute, or create derivative works without our express written permission.</p>

        <h2>7. Governing Law</h2>
        <p>These terms and conditions are governed by the laws of India. Any disputes arising out of or in connection with these terms shall be subject to the exclusive jurisdiction of the courts in India.</p>

        <h2>8. Changes to Terms</h2>
        <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting to the website. Your continued use of the site constitutes your acceptance of the revised terms.</p>

        <a href="landing.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Ask Visa. All rights reserved.</p>
    </footer>

</body>
</html>
