<?php
// ============================================
// home.php — Public Homepage
// Processes the "Book a Service" form and
// saves it directly into the complaints table
// ============================================
require_once 'db.php';

$form_success = false;
$form_error   = '';
$new_complaint_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {
    $name        = clean($conn, $_POST['name']         ?? '');
    $phone       = clean($conn, $_POST['phone']        ?? '');
    $service_type= clean($conn, $_POST['service_type'] ?? '');
    $problem     = clean($conn, $_POST['problem']      ?? '');
    $address     = clean($conn, $_POST['address']      ?? '');

    // Strip any spaces or dashes the user may have typed
    $phone = preg_replace('/\D/', '', $phone);

    if (empty($name) || empty($phone) || empty($problem)) {
        $form_error = "Please fill in your name, phone, and problem description.";
    } elseif (strlen($phone) !== 10) {
        $form_error = "Phone number must be exactly 10 digits. You entered " . strlen($phone) . " digit(s).";
    } else {
        // 1. Check if customer already exists by phone
        $chk = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
        $chk->bind_param("s", $phone);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $chk->bind_result($customer_id);
            $chk->fetch();
        } else {
            // Create a new customer record
            $addr = !empty($address) ? $address : 'Not provided';
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, address, ac_type) VALUES (?, ?, ?, 'Split')");
            $stmt->bind_param("sss", $name, $phone, $addr);
            $stmt->execute();
            $customer_id = $conn->insert_id;
            $stmt->close();
        }
        $chk->close();

        // 2. Build the problem text (include service type in it)
        $full_problem = "[Service Request: $service_type]\n$problem";

        // 3. Insert complaint — status open, no technician yet
        $stmt = $conn->prepare("INSERT INTO complaints (customer_id, problem, priority, status) VALUES (?, ?, 'normal', 'open')");
        $stmt->bind_param("is", $customer_id, $full_problem);
        $stmt->execute();
        $new_complaint_id = $conn->insert_id;
        $stmt->close();

        // 4. Log it
        $action = "Service request submitted from public website by $name";
        $stmt = $conn->prepare("INSERT INTO complaint_log (complaint_id, action, done_by) VALUES (?, ?, 'Customer (Website)')");
        $stmt->bind_param("is", $new_complaint_id, $action);
        $stmt->execute();
        $stmt->close();

        $form_success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FrostLine AC Services — Cooling You Can Count On</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sky:    #0ea5e9;
            --deep:   #0369a1;
            --dark:   #0c1a2e;
            --darker: #071020;
            --text:   #1e3a5f;
            --muted:  #64748b;
            --white:  #ffffff;
            --accent: #38bdf8;
        }

        html { scroll-behavior: smooth; }
        body { font-family: 'Outfit', sans-serif; background: var(--white); color: var(--text); overflow-x: hidden; }

        /* ─── NAVBAR ─── */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 999;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 60px; height: 72px;
            background: rgba(7,16,32,0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(56,189,248,0.1);
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--sky), var(--accent));
            border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .nav-logo-text { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 1px; color: var(--white); }
        .nav-logo-text span { color: var(--accent); }
        .nav-links { display: flex; align-items: center; gap: 32px; list-style: none; }
        .nav-links a { text-decoration: none; color: rgba(255,255,255,0.7); font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover { color: var(--accent); }
        .nav-cta { display: flex; align-items: center; gap: 12px; }
        .btn-nav-admin {
            padding: 9px 20px; border: 1px solid rgba(56,189,248,0.4); border-radius: 8px;
            color: var(--accent); text-decoration: none; font-size: 13px; font-weight: 600;
            transition: background 0.2s;
        }
        .btn-nav-admin:hover { background: rgba(56,189,248,0.1); }
        .btn-nav-call {
            padding: 9px 20px; background: var(--sky); border-radius: 8px;
            color: #fff; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.2s;
        }
        .btn-nav-call:hover { background: var(--deep); }

        /* ─── HERO ─── */
        .hero {
            min-height: 100vh; background: var(--darker);
            display: flex; align-items: center; overflow: hidden; position: relative;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background-image: linear-gradient(rgba(14,165,233,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(14,165,233,0.06) 1px, transparent 1px);
            background-size: 50px 50px; animation: gridMove 20s linear infinite;
        }
        @keyframes gridMove { to { transform: translateY(50px); } }
        .hero-orb1 {
            position: absolute; width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(14,165,233,0.15) 0%, transparent 70%);
            top: -100px; right: -100px; border-radius: 50%;
            animation: orbPulse 6s ease-in-out infinite;
        }
        .hero-orb2 {
            position: absolute; width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(56,189,248,0.08) 0%, transparent 70%);
            bottom: 0; left: 200px; border-radius: 50%;
            animation: orbPulse 8s ease-in-out infinite reverse;
        }
        @keyframes orbPulse { 0%,100%{transform:scale(1);opacity:0.7} 50%{transform:scale(1.15);opacity:1} }

        .hero-content {
            position: relative; z-index: 2; max-width: 1200px; margin: 0 auto;
            padding: 120px 60px 80px; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; width: 100%;
        }
        .hero-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(14,165,233,0.12); border: 1px solid rgba(14,165,233,0.25);
            border-radius: 100px; padding: 6px 14px; font-size: 12px; font-weight: 600;
            color: var(--accent); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 24px;
        }
        .hero-tag::before { content:''; width:6px;height:6px;background:var(--accent);border-radius:50%;animation:blink 1.5s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }
        .hero h1 {
            font-family: 'Bebas Neue', sans-serif; font-size: clamp(52px, 6vw, 80px);
            line-height: 1; color: var(--white); margin-bottom: 20px; letter-spacing: 1px;
        }
        .hero h1 span { background: linear-gradient(135deg, var(--sky), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-desc { color: rgba(255,255,255,0.6); font-size: 17px; line-height: 1.75; margin-bottom: 36px; max-width: 480px; }
        .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
        .btn-primary {
            padding: 14px 32px; background: linear-gradient(135deg, var(--deep), var(--sky));
            border-radius: 10px; color: #fff; text-decoration: none; font-size: 15px; font-weight: 600;
            box-shadow: 0 8px 24px rgba(14,165,233,0.4); transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(14,165,233,0.5); }
        .btn-outline {
            padding: 14px 32px; border: 1.5px solid rgba(255,255,255,0.2); border-radius: 10px;
            color: rgba(255,255,255,0.8); text-decoration: none; font-size: 15px; font-weight: 500; transition: border-color 0.2s, color 0.2s;
        }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }
        .hero-stats { display: flex; gap: 32px; margin-top: 48px; padding-top: 36px; border-top: 1px solid rgba(255,255,255,0.08); }
        .hero-stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 40px; color: var(--white); line-height: 1; }
        .hero-stat-num span { color: var(--accent); }
        .hero-stat-label { font-size: 13px; color: rgba(255,255,255,0.45); margin-top: 4px; }

        /* Hero right cards */
        .hero-right { position: relative; display: flex; align-items: center; justify-content: center; }
        .hero-card-main {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(56,189,248,0.15);
            border-radius: 24px; padding: 32px; width: 300px; backdrop-filter: blur(12px);
            animation: float 5s ease-in-out infinite;
        }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-14px)} }
        .hero-card-main .card-icon { font-size: 40px; margin-bottom: 14px; }
        .hero-card-main h3 { font-size: 18px; font-weight: 700; color: var(--white); margin-bottom: 8px; }
        .hero-card-main p { font-size: 13px; color: rgba(255,255,255,0.5); line-height: 1.6; }
        .hero-card-small {
            position: absolute; background: rgba(14,165,233,0.12); border: 1px solid rgba(14,165,233,0.2);
            border-radius: 16px; padding: 16px 20px; backdrop-filter: blur(12px); white-space: nowrap;
        }
        .hero-card-small.top-right { top: 20px; right: -30px; animation: float 4s ease-in-out infinite 1s; }
        .hero-card-small.bot-left  { bottom: 20px; left: -30px; animation: float 6s ease-in-out infinite 0.5s; }
        .hero-card-small .small-label { font-size: 11px; color: var(--accent); font-weight: 600; margin-bottom: 4px; }
        .hero-card-small .small-val   { font-size: 20px; font-weight: 700; color: var(--white); }

        /* ─── SECTION BASE ─── */
        .section-inner { max-width: 1200px; margin: 0 auto; padding: 100px 60px; }
        .section-tag   { font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--sky); margin-bottom: 12px; }
        .section-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(36px,4vw,52px); line-height: 1.05; color: var(--dark); margin-bottom: 16px; }
        .section-sub   { font-size: 16px; color: var(--muted); line-height: 1.75; max-width: 560px; }

        /* ─── SERVICES ─── */
        .services-bg { background: #f8fafc; }
        .services-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 56px; flex-wrap: wrap; gap: 20px; }
        .services-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; }
        .service-card {
            background: var(--white); border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px;
            transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s; position: relative; overflow: hidden;
        }
        .service-card::before {
            content:''; position:absolute; top:0;left:0;right:0; height:3px;
            background: linear-gradient(90deg,var(--sky),var(--accent)); transform:scaleX(0); transform-origin:left; transition:transform 0.3s;
        }
        .service-card:hover { transform:translateY(-6px); border-color:var(--sky); box-shadow:0 20px 48px rgba(14,165,233,0.12); }
        .service-card:hover::before { transform:scaleX(1); }
        .service-icon {
            width:56px;height:56px; background:linear-gradient(135deg,rgba(14,165,233,0.1),rgba(56,189,248,0.15));
            border-radius:14px; display:flex;align-items:center;justify-content:center; font-size:24px; margin-bottom:20px;
        }
        .service-card h3 { font-size:17px;font-weight:700;color:var(--dark);margin-bottom:10px; }
        .service-card p  { font-size:14px;color:var(--muted);line-height:1.7; }

        /* ─── ABOUT ─── */
        .about-grid { display:grid; grid-template-columns:1fr 1fr; gap:80px; align-items:center; }
        .about-img-bg {
            width:100%; aspect-ratio:4/3;
            background:linear-gradient(135deg,var(--dark),var(--deep));
            border-radius:24px; display:flex; align-items:center; justify-content:center; font-size:100px;
            position:relative; overflow:hidden;
        }
        .about-img-bg::before {
            content:'';position:absolute;inset:0;
            background-image:linear-gradient(rgba(56,189,248,0.08) 1px,transparent 1px),linear-gradient(90deg,rgba(56,189,248,0.08) 1px,transparent 1px);
            background-size:30px 30px;
        }
        .about-img-wrap { position:relative; }
        .about-badge {
            position:absolute; bottom:-20px;right:-20px;
            background:var(--sky); border-radius:16px; padding:20px 24px; text-align:center;
            box-shadow:0 12px 32px rgba(14,165,233,0.4);
        }
        .about-badge-num   { font-family:'Bebas Neue',sans-serif;font-size:36px;color:#fff;line-height:1; }
        .about-badge-label { font-size:12px;color:rgba(255,255,255,0.8);margin-top:2px; }
        .about-features { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:32px; }
        .about-feature {
            display:flex;align-items:flex-start;gap:12px;padding:16px;
            background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;
        }
        .about-feature-icon { font-size:20px;flex-shrink:0;margin-top:2px; }
        .about-feature h4 { font-size:14px;font-weight:600;color:var(--dark);margin-bottom:2px; }
        .about-feature p  { font-size:12px;color:var(--muted); }

        /* ─── PROJECTS ─── */
        .projects-bg { background:var(--darker); }
        .projects-bg .section-title { color:var(--white); }
        .projects-bg .section-tag   { color:var(--accent); }
        .projects-bg .section-sub   { color:rgba(255,255,255,0.5); }
        .projects-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px; }
        .project-card {
            background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);
            border-radius:20px; overflow:hidden; transition:transform 0.25s,border-color 0.25s;
        }
        .project-card:hover { transform:translateY(-6px); border-color:rgba(56,189,248,0.3); }
        .project-thumb { height:180px;display:flex;align-items:center;justify-content:center;font-size:56px;position:relative; }
        .project-body  { padding:24px; }
        .project-type  { font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--accent);margin-bottom:8px; }
        .project-body h3 { font-size:18px;font-weight:700;color:var(--white);margin-bottom:10px; }
        .project-body p  { font-size:13px;color:rgba(255,255,255,0.5);line-height:1.7; }
        .project-tags    { display:flex;gap:8px;flex-wrap:wrap;margin-top:16px; }
        .project-tag {
            padding:4px 10px; background:rgba(14,165,233,0.12); border:1px solid rgba(14,165,233,0.2);
            border-radius:100px; font-size:11px; color:var(--accent); font-weight:500;
        }

        /* ─── TESTIMONIALS ─── */
        .testimonials-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px; }
        .testi-card {
            background:var(--white);border:1px solid #e2e8f0;border-radius:20px;padding:28px;
            transition:box-shadow 0.25s,transform 0.25s;
        }
        .testi-card:hover { transform:translateY(-4px);box-shadow:0 16px 40px rgba(14,165,233,0.1); }
        .testi-stars  { color:#f59e0b;font-size:14px;margin-bottom:14px;letter-spacing:2px; }
        .testi-text   { font-size:14px;color:var(--muted);line-height:1.75;font-style:italic;margin-bottom:20px; }
        .testi-author { display:flex;align-items:center;gap:12px; }
        .testi-avatar {
            width:42px;height:42px;border-radius:50%;
            background:linear-gradient(135deg,var(--sky),var(--accent));
            display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0;
        }
        .testi-name { font-size:14px;font-weight:700;color:var(--dark); }
        .testi-role { font-size:12px;color:var(--muted); }

        /* ─── CONTACT ─── */
        .contact-bg { background:#f8fafc; }
        .contact-grid { display:grid;grid-template-columns:1fr 1.4fr;gap:60px;margin-top:56px;align-items:start; }
        .contact-info { display:flex;flex-direction:column;gap:20px; }
        .contact-item { display:flex;align-items:flex-start;gap:16px;padding:20px;background:var(--white);border:1px solid #e2e8f0;border-radius:14px; }
        .contact-item-icon {
            width:44px;height:44px;background:linear-gradient(135deg,rgba(14,165,233,0.12),rgba(56,189,248,0.18));
            border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;
        }
        .contact-item-label { font-size:12px;color:var(--muted);margin-bottom:2px; }
        .contact-item-val   { font-size:15px;font-weight:600;color:var(--dark); }
        .contact-form { background:var(--white);border:1px solid #e2e8f0;border-radius:24px;padding:36px; }
        .contact-form h3 { font-size:20px;font-weight:700;color:var(--dark);margin-bottom:24px; }
        .form-row { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
        .cf-field { margin-bottom:16px; }
        .cf-field label { display:block;font-size:12px;font-weight:600;color:var(--muted);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px; }
        .cf-field input,.cf-field textarea,.cf-field select {
            width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;
            font-family:'Outfit',sans-serif;font-size:14px;color:var(--dark);background:#f8fafc;
            outline:none;transition:border-color 0.2s,box-shadow 0.2s;appearance:none;
        }
        .cf-field input:focus,.cf-field textarea:focus,.cf-field select:focus {
            border-color:var(--sky);box-shadow:0 0 0 3px rgba(14,165,233,0.08);background:var(--white);
        }
        .cf-field textarea { min-height:100px;resize:vertical; }
        .btn-submit {
            width:100%;padding:14px;background:linear-gradient(135deg,var(--deep),var(--sky));
            border:none;border-radius:10px;color:#fff;font-family:'Outfit',sans-serif;font-size:15px;font-weight:600;
            cursor:pointer;box-shadow:0 6px 20px rgba(14,165,233,0.35);transition:transform 0.2s,box-shadow 0.2s;
        }
        .btn-submit:hover { transform:translateY(-1px);box-shadow:0 10px 28px rgba(14,165,233,0.45); }
        .form-success {
            display:none;background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.25);
            border-radius:10px;padding:14px;text-align:center;color:var(--sky);font-size:14px;font-weight:600;margin-top:12px;
        }

        /* ─── FOOTER ─── */
        footer { background:var(--darker);border-top:1px solid rgba(255,255,255,0.06);padding:60px; }
        .footer-inner { max-width:1200px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px; }
        .footer-brand p { color:rgba(255,255,255,0.4);font-size:14px;line-height:1.75;margin-top:16px;max-width:280px; }
        .footer-col h4  { font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:rgba(255,255,255,0.5);margin-bottom:16px; }
        .footer-col ul  { list-style:none;display:flex;flex-direction:column;gap:10px; }
        .footer-col ul a{ text-decoration:none;color:rgba(255,255,255,0.4);font-size:14px;transition:color 0.2s; }
        .footer-col ul a:hover { color:var(--accent); }
        .footer-bottom {
            max-width:1200px;margin:40px auto 0;padding-top:24px;
            border-top:1px solid rgba(255,255,255,0.06);
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
        }
        .footer-bottom p { color:rgba(255,255,255,0.3);font-size:13px; }
        .footer-bottom a { color:var(--accent);text-decoration:none; }

        /* Scroll to top */
        .scroll-top {
            position:fixed;bottom:32px;right:32px;width:44px;height:44px;
            background:var(--sky);border-radius:12px;display:flex;align-items:center;justify-content:center;
            color:#fff;font-size:18px;cursor:pointer;text-decoration:none;
            box-shadow:0 6px 20px rgba(14,165,233,0.4);transition:transform 0.2s,opacity 0.3s;opacity:0;z-index:100;
        }
        .scroll-top.show { opacity:1; }
        .scroll-top:hover { transform:translateY(-3px); }

        /* ─── RESPONSIVE ─── */
        @media (max-width:900px) {
            .nav { padding:0 24px; }
            .nav-links { display:none; }
            .section-inner { padding:70px 24px; }
            .hero-content { grid-template-columns:1fr;padding:120px 24px 60px; }
            .hero-right { display:none; }
            .services-grid,.projects-grid,.testimonials-grid { grid-template-columns:1fr; }
            .about-grid,.contact-grid { grid-template-columns:1fr; }
            .footer-inner { grid-template-columns:1fr 1fr; }
            footer { padding:40px 24px; }
            .form-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav" id="navbar">
    <a href="#" class="nav-logo">
        <div class="nav-logo-icon">❄️</div>
        <span class="nav-logo-text">Frost<span>Line</span></span>
    </a>
    <ul class="nav-links">
        <li><a href="#services">Services</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#projects">Projects</a></li>
        <li><a href="#testimonials">Reviews</a></li>
        <li><a href="#contact">Contact</a></li>
    </ul>
    <div class="nav-cta">
        <a href="index.php" class="btn-nav-admin">Admin Login</a>
        <a href="tel:+919876543210" class="btn-nav-call">📞 Call Now</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-orb1"></div>
    <div class="hero-orb2"></div>
    <div class="hero-content">
        <div>
            <div class="hero-tag">Trusted AC Service Centre</div>
            <h1>Your Complete <span>Cooling</span> Solution</h1>
            <p class="hero-desc">Expert air conditioner installation, repair, and annual maintenance for homes, offices, hotels, and industries. Fast, reliable, and affordable service you can count on.</p>
            <div class="hero-btns">
                <a href="#contact" class="btn-primary">Book a Service</a>
                <a href="#projects" class="btn-outline">Our Work →</a>
            </div>
            <div class="hero-stats">
                <div><div class="hero-stat-num">500<span>+</span></div><div class="hero-stat-label">Happy Customers</div></div>
                <div><div class="hero-stat-num">10<span>+</span></div><div class="hero-stat-label">Years Experience</div></div>
                <div><div class="hero-stat-num">24<span>/7</span></div><div class="hero-stat-label">Support Available</div></div>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-card-main">
                <div class="card-icon">🌡️</div>
                <h3>Smart Complaint Tracking</h3>
                <p>Our AI-powered CRM logs every complaint, assigns technicians, and tracks resolution in real time.</p>
            </div>
            <div class="hero-card-small top-right">
                <div class="small-label">Avg Response Time</div>
                <div class="small-val">2 hrs</div>
            </div>
            <div class="hero-card-small bot-left">
                <div class="small-label">Complaints Resolved</div>
                <div class="small-val">98%</div>
            </div>
        </div>
    </div>
</div>

<!-- SERVICES -->
<div class="services-bg" id="services">
    <div class="section-inner">
        <div class="services-header">
            <div>
                <div class="section-tag">What We Do</div>
                <div class="section-title">Our Services</div>
                <p class="section-sub">Complete end-to-end AC care — from new installations to emergency repairs and annual contracts.</p>
            </div>
        </div>
        <div class="services-grid">
            <div class="service-card"><div class="service-icon">🔧</div><h3>AC Installation</h3><p>Professional installation of split, window, cassette, and tower ACs for homes and commercial spaces.</p></div>
            <div class="service-card"><div class="service-icon">🛠️</div><h3>Repair & Service</h3><p>Fast diagnosis and repair of all AC faults — gas leaks, compressor issues, electrical faults, and more.</p></div>
            <div class="service-card"><div class="service-icon">🧹</div><h3>Deep Cleaning</h3><p>Full unit deep cleaning including filter wash, coil cleaning, and drain flushing for peak efficiency.</p></div>
            <div class="service-card"><div class="service-icon">📋</div><h3>Annual Maintenance</h3><p>AMC contracts for businesses — scheduled visits, priority service, and detailed service reports.</p></div>
            <div class="service-card"><div class="service-icon">❄️</div><h3>Gas Refilling</h3><p>Refrigerant top-up and leak detection for all AC brands using industry-standard equipment.</p></div>
            <div class="service-card"><div class="service-icon">🏭</div><h3>Industrial Cooling</h3><p>Large-scale cooling solutions for factories, warehouses, and commercial complexes.</p></div>
        </div>
    </div>
</div>

<!-- ABOUT -->
<div id="about">
    <div class="section-inner">
        <div class="about-grid">
            <div class="about-img-wrap">
                <div class="about-img-bg">❄️</div>
                <div class="about-badge">
                    <div class="about-badge-num">10+</div>
                    <div class="about-badge-label">Years of Service</div>
                </div>
            </div>
            <div>
                <div class="section-tag">Who We Are</div>
                <div class="section-title">Built on Trust, Driven by Quality</div>
                <p class="section-sub">A dedicated AC service centre serving residential and commercial clients. From a single room unit to a full industrial cooling setup, our certified technicians handle it all.</p>
                <div class="about-features">
                    <div class="about-feature"><div class="about-feature-icon">✅</div><div><h4>Certified Technicians</h4><p>Trained and verified professionals</p></div></div>
                    <div class="about-feature"><div class="about-feature-icon">⚡</div><div><h4>Fast Response</h4><p>Same-day service for urgent calls</p></div></div>
                    <div class="about-feature"><div class="about-feature-icon">🔒</div><div><h4>Service Guarantee</h4><p>30-day warranty on all repairs</p></div></div>
                    <div class="about-feature"><div class="about-feature-icon">📱</div><div><h4>AI-Powered CRM</h4><p>Smart complaint tracking system</p></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROJECTS -->
<div class="projects-bg" id="projects">
    <div class="section-inner">
        <div class="section-tag">Our Work</div>
        <div class="section-title">Featured Projects</div>
        <p class="section-sub">Trusted by hotels, industries, and businesses across the region.</p>
        <div class="projects-grid">
            <div class="project-card">
                <div class="project-thumb" style="background:linear-gradient(135deg,#1e3a5f,#0369a1);">🏨</div>
                <div class="project-body">
                    <div class="project-type">Hospitality</div>
                    <h3>Grand Palace Hotel</h3>
                    <p>Full HVAC installation across 80 rooms, lobby, and banquet hall. Includes ongoing annual maintenance.</p>
                    <div class="project-tags"><span class="project-tag">80 Units</span><span class="project-tag">AMC</span><span class="project-tag">Cassette AC</span></div>
                </div>
            </div>
            <div class="project-card">
                <div class="project-thumb" style="background:linear-gradient(135deg,#134e4a,#0f766e);">🏭</div>
                <div class="project-body">
                    <div class="project-type">Industrial</div>
                    <h3>Thakur Industries</h3>
                    <p>Heavy-duty industrial cooling for a 15,000 sq ft manufacturing floor with custom duct layout.</p>
                    <div class="project-tags"><span class="project-tag">Industrial</span><span class="project-tag">Ducted</span><span class="project-tag">24/7 Support</span></div>
                </div>
            </div>
            <div class="project-card">
                <div class="project-thumb" style="background:linear-gradient(135deg,#3b0764,#7c3aed);">🏢</div>
                <div class="project-body">
                    <div class="project-type">Commercial</div>
                    <h3>TechPark Office</h3>
                    <p>Central AC system with zone-wise control for a 3-floor IT office with 200+ workstations.</p>
                    <div class="project-tags"><span class="project-tag">Central AC</span><span class="project-tag">Zoned</span><span class="project-tag">Energy Efficient</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TESTIMONIALS -->
<div id="testimonials">
    <div class="section-inner">
        <div class="section-tag">Reviews</div>
        <div class="section-title">What Our Clients Say</div>
        <p class="section-sub">Real feedback from the businesses and homes we've served.</p>
        <div class="testimonials-grid">
            <div class="testi-card">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">"The team installed all 80 ACs across our hotel in just 4 days with zero disruption to guests. Their AMC team is always a call away."</p>
                <div class="testi-author"><div class="testi-avatar">R</div><div><div class="testi-name">Rajan Mehta</div><div class="testi-role">Manager, Grand Palace Hotel</div></div></div>
            </div>
            <div class="testi-card">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">"Our factory floor was unbearable before. FrostLine designed the perfect cooling layout and the execution was absolutely flawless."</p>
                <div class="testi-author"><div class="testi-avatar">S</div><div><div class="testi-name">Suresh Thakur</div><div class="testi-role">Operations Head, Thakur Industries</div></div></div>
            </div>
            <div class="testi-card">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">"Quick response, genuine parts, and honest pricing. My home AC has been running perfectly ever since. Won't go anywhere else."</p>
                <div class="testi-author"><div class="testi-avatar">P</div><div><div class="testi-name">Priya Nair</div><div class="testi-role">Homeowner</div></div></div>
            </div>
        </div>
    </div>
</div>

<!-- CONTACT -->
<div class="contact-bg" id="contact">
    <div class="section-inner">
        <div class="section-tag">Get In Touch</div>
        <div class="section-title">Book a Service</div>
        <p class="section-sub">Fill the form and our team will call you back within 2 hours.</p>
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-item"><div class="contact-item-icon">📞</div><div><div class="contact-item-label">Phone</div><div class="contact-item-val">+91 98765 43210</div></div></div>
                <div class="contact-item"><div class="contact-item-icon">📧</div><div><div class="contact-item-label">Email</div><div class="contact-item-val"><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="8dfee8fffbe4eee8cdebffe2fef9e1e4e3e8eceea3e4e3">[email&#160;protected]</a></div></div></div>
                <div class="contact-item"><div class="contact-item-icon">📍</div><div><div class="contact-item-label">Address</div><div class="contact-item-val">123 Service Road, Bengaluru, Karnataka - 560001</div></div></div>
                <div class="contact-item"><div class="contact-item-icon">⏰</div><div><div class="contact-item-label">Working Hours</div><div class="contact-item-val">Mon–Sat: 8 AM – 8 PM</div></div></div>
            </div>
            <div class="contact-form">
                <h3>Send a Service Request</h3>

                <?php if ($form_success): ?>
                <div style="background:rgba(14,165,233,0.08);border:1px solid rgba(14,165,233,0.25);border-radius:12px;padding:24px;text-align:center;">
                    <div style="font-size:36px;margin-bottom:12px;">✅</div>
                    <div style="font-size:16px;font-weight:700;color:#0369a1;margin-bottom:6px;">Request Submitted!</div>
                    <div style="font-size:14px;color:#64748b;margin-bottom:12px;">
                        Your complaint ID is <strong style="color:#0ea5e9;">#<?= str_pad($new_complaint_id, 4, '0', STR_PAD_LEFT) ?></strong>.
                        Our team will call you back within 2 hours.
                    </div>
                    <a href="home.php#contact" style="font-size:13px;color:#0ea5e9;text-decoration:none;">← Submit another request</a>
                </div>

                <?php elseif ($form_error): ?>
                <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:10px;padding:14px;color:#dc2626;font-size:14px;margin-bottom:16px;">
                    ⚠️ <?= htmlspecialchars($form_error) ?>
                </div>
                <?php endif; ?>

                <?php if (!$form_success): ?>
                <form method="POST" action="home.php#contact">
                    <div class="form-row">
                        <div class="cf-field">
                            <label>Your Name *</label>
                            <input type="text" name="name" placeholder="Ramesh Kumar" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="cf-field">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone" placeholder="10-digit mobile number" required
                                   pattern="[0-9]{10}" maxlength="10" minlength="10"
                                   title="Enter exactly 10 digits, no spaces or dashes"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="cf-field">
                        <label>Your Address</label>
                        <input type="text" name="address" placeholder="e.g. 12th Main, Koramangala, Bangalore"
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                    </div>
                    <div class="cf-field">
                        <label>Service Type</label>
                        <select name="service_type">
                            <?php
                            $selected_service = $_POST['service_type'] ?? '';
                            $service_opts = ['AC Repair','AC Installation','Deep Cleaning','Gas Refilling','Annual Maintenance','Other'];
                            foreach ($service_opts as $opt) {
                                $sel = ($selected_service === $opt) ? 'selected' : '';
                                echo "<option $sel>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="cf-field">
                        <label>Describe the Issue *</label>
                        <textarea name="problem" placeholder="E.g. AC not cooling for 2 days, making noise..." required><?= htmlspecialchars($_POST['problem'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="book_service" class="btn-submit">Send Request →</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="nav-logo"><div class="nav-logo-icon">❄️</div><span class="nav-logo-text">Frost<span>Line</span></span></div>
            <p>Your complete AC service partner — sales, installation, repair, and maintenance for homes and businesses.</p>
        </div>
        <div class="footer-col">
            <h4>Services</h4>
            <ul>
                <li><a href="#services">AC Installation</a></li>
                <li><a href="#services">Repair & Service</a></li>
                <li><a href="#services">Deep Cleaning</a></li>
                <li><a href="#services">Gas Refilling</a></li>
                <li><a href="#services">AMC Contracts</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="#about">About Us</a></li>
                <li><a href="#projects">Projects</a></li>
                <li><a href="#testimonials">Reviews</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="index.php">Admin Login</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <ul>
                <li><a href="tel:+919876543210">+91 98765 43210</a></li>
                <li><a href="/cdn-cgi/l/email-protection#fd8e988f8b949e98bd9b8f928e89919493989c9ed39493"><span class="__cf_email__" data-cfemail="8dfee8fffbe4eee8cdebffe2fef9e1e4e3e8eceea3e4e3">[email&#160;protected]</span></a></li>
                <li><a href="#contact">Bengaluru, Karnataka</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> FrostLine AC Services. All rights reserved.</p>
        <p>Built with PHP + MySQL — <a href="index.php">Admin Portal →</a></p>
    </div>
</footer>

<a href="#" class="scroll-top" id="scrollTop">↑</a>

<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script>
    const scrollBtn = document.getElementById('scrollTop');
    window.addEventListener('scroll', () => {
        scrollBtn.classList.toggle('show', window.scrollY > 400);
        document.getElementById('navbar').style.background =
            window.scrollY > 60 ? 'rgba(7,16,32,0.98)' : 'rgba(7,16,32,0.92)';
    });

    // Scroll-in animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.style.opacity='1'; e.target.style.transform='transl