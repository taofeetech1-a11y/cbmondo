<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-seo page="support.page" />
    <link rel="shortcut icon" href="{{ asset('assets/logo-cityboy.png') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css"
        integrity="sha512-x9WwyMYBnlXMNQ6kQ/Lyzu1NqIhLQKL5Oq6xByfXuRj7s9CskyCbLv/1IjqzJmXwFXWr0ov6jBV7Qbc0hh9nHg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        :root {
            --navy: #0b2a52;
            --navy-dark: #081f3d;
            --red: #e11d2a;
            --yellow: #ffc72c;
            --green: #1c8a4b;
            --grey-bg: #eef1f4;
            --text-dark: #122238;
            --text-muted: #5a6b80;
            --border: #dde3ea;
            --radius: 10px;
            font-size: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, Arial, Helvetica, sans-serif;
            color: var(--text-dark);
            background: var(--grey-bg);
            line-height: 1.5;
        }

        img {
            max-width: 100%;
            display: block;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* ---------- Header ---------- */
        header.site-header {
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 32px;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
            position: relative;
            z-index: 20;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .brand-mark2 {
            width: 75px;
            height: 75px;
        }

        .brand-mark svg {
            width: 34px;
            height: 34px;
        }


        .brand-mark2 img {
            width: 75px;
            height: 75px;
        }

        .brand-text {
            line-height: 1.15;
        }

        .brand-text .name {
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--navy);
            letter-spacing: .5px;
        }

        .brand-text .sub {
            display: inline-block;
            background: var(--red);
            color: #fff;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 1px 8px;
            border-radius: 3px;
            margin: 2px 0;
        }

        .brand-text .dept {
            display: block;
            font-size: .6rem;
            color: var(--text-muted);
            letter-spacing: .5px;
        }

        nav.main-nav {
            display: flex;
            gap: 28px;
            font-weight: 600;
            font-size: .92rem;
            color: var(--navy);
        }

        nav.main-nav a {
            position: relative;
            padding: 6px 2px;
        }

        nav.main-nav a:hover {
            color: var(--red);
        }

        .join-btn {
            background: var(--red);
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
            padding: 11px 22px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: background .15s ease;
        }

        .join-btn:hover {
            background: #c81622;
        }

        .join-btn-sm {
            background: var(--red);
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
            padding: 11px 22px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: background .15s ease;
            display: none;
        }

        .join-btn-sm:hover {
            background: #c81622;
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.6rem;
            color: var(--navy);
            cursor: pointer;
        }

        /* ---------- Hero ---------- */
        .hero {
            position: relative;
            background: url('{{ asset('assets/banner.jpeg') }}');
            /* background:
                linear-gradient(100deg, rgba(8, 31, 61, .92) 0%, rgba(11, 42, 82, .72) 45%, rgba(11, 42, 82, .35) 75%),
                linear-gradient(#4a5f74, #4a5f74); */
            background-size: 100%;
            background-repeat: no-repeat;
            background-position: center;
            color: #fff;
            padding: 56px 32px 64px;
            overflow: hidden;
        }

        .hero-overlay {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            background:
                linear-gradient(100deg, rgba(8, 31, 61, .92) 0%, rgba(11, 42, 82, .72) 45%, rgba(11, 42, 82, .35) 75%),
                linear-gradient(#4a5f74, #4a5f74);
            opacity: .7;
            z-index: 1;
        }


        /* .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 60% 40%, rgba(255, 255, 255, .06), transparent 60%);
            pointer-events: none;
        } */

        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 999999999999999999;
        }

        .hero h1 {
            font-size: 2.6rem;
            line-height: 1.15;
            font-style: italic;
            font-weight: 800;
            margin: 0 0 6px;
        }

        .hero h1 .accent {
            color: var(--yellow);
        }

        .hero-tagline {
            margin-top: 18px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: .5px;
        }

        .hero-tagline span {
            margin: 0 10px;
            opacity: .55;
        }

        .hero-tagline span:first-child {
            margin-left: 0;
        }

        .hero-rule {
            width: 70px;
            height: 3px;
            background: var(--yellow);
            margin: 14px 0 0;
        }

        .hero-side {
            position: absolute;
            right: 32px;
            top: 36px;
            font-style: italic;
            font-size: 1.4rem;
            font-weight: 600;
            text-align: right;
            line-height: 1.2;
        }

        .hero-side .rule {
            width: 60px;
            height: 3px;
            background: var(--yellow);
            margin-left: auto;
            margin-top: 8px;
        }

        /* ---------- Main partner section ---------- */
        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 32px;
        }

        .partner-card {
            display: grid;
            grid-template-columns: minmax(300px, 420px) 1fr;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(11, 42, 82, .08);
        }

        /* left panel */
        .partner-left {
            background: linear-gradient(165deg, var(--navy) 0%, var(--navy-dark) 65%, var(--green) 150%);
            color: #fff;
            padding: 40px 36px 0;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .pill {
            display: inline-block;
            background: var(--red);
            color: #fff;
            font-weight: 700;
            font-size: .72rem;
            letter-spacing: .5px;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 22px;
        }

        .partner-left h2 {
            font-size: 2.3rem;
            line-height: 1.1;
            margin: 0 0 20px;
            font-weight: 800;
        }

        .partner-left h2 .hl {
            color: var(--yellow);
        }

        .partner-left p.lead {
            font-size: 1rem;
            color: #dfe7f0;
            max-width: 340px;
            margin-bottom: 26px;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .feature-icon {
            flex: 0 0 40px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .feature-icon.blue {
            background: #2a6db5;
        }

        .feature-icon.green {
            background: var(--green);
        }

        .feature-icon.red {
            background: var(--red);
        }

        .feature-icon.yellow {
            background: var(--yellow);
            color: var(--navy-dark);
        }

        .feature-item strong {
            display: block;
            font-size: .98rem;
            margin-bottom: 2px;
        }

        .feature-item span {
            font-size: .82rem;
            color: #cfdaea;
        }

        .partner-photo {
            margin-top: auto;
            position: relative;
            border-radius: 12px 12px 0 0;
            overflow: hidden;
        }

        .partner-photo img {
            width: 100%;
            height: 290px;
            object-fit: cover;
            display: block;

            object-position: top;
        }

        .partner-photo .caption {
            position: absolute;
            bottom: 14px;
            left: 18px;
            font-style: italic;
            font-weight: 700;
            font-size: 1.05rem;
            color: #fff;
            text-shadow: 0 1px 4px rgba(0, 0, 0, .5);
        }

        /* right panel: form */
        .partner-right {
            padding: 40px 44px 44px;
        }

        .partner-right h2 {
            color: var(--navy);
            font-size: 1.6rem;
            margin: 0 0 8px;
        }

        .partner-right p.intro {
            color: var(--text-muted);
            font-size: .95rem;
            margin: 0 0 26px;
            max-width: 600px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .field label {
            display: block;
            font-weight: 700;
            font-size: .88rem;
            color: var(--navy);
            margin-bottom: 7px;
        }

        .field label .req {
            color: var(--red);
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: .9rem;
            font-family: inherit;
            color: var(--text-dark);
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .field input::placeholder,
        .field textarea::placeholder {
            color: #9aa7b6;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(11, 42, 82, .12);
        }

        .field select {
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'><path d='M1 1l6 6 6-6' stroke='%235a6b80' stroke-width='2' fill='none' fill-rule='evenodd'/></svg>");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }

        .field textarea {
            resize: vertical;
            min-height: 90px;
        }

        .radio-row {
            display: flex;
            gap: 28px;
            margin-top: 4px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .92rem;
            color: var(--text-dark);
            cursor: pointer;
        }

        .radio-option input {
            width: 18px;
            height: 18px;
            accent-color: var(--navy);
            cursor: pointer;
        }

        .submit-btn {
            background: var(--red);
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            padding: 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            margin-top: 6px;
            transition: background .15s ease, transform .1s ease;
        }

        .submit-btn:hover {
            background: #c81622;
        }

        .submit-btn:active {
            transform: translateY(1px);
        }

        .secure-note {
            text-align: center;
            font-size: .8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* ---------- Footer ---------- */
        footer {
            background: #e7ebef;
            padding: 44px 32px 0;
        }

        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 32px;
            padding-bottom: 34px;
			display: flex;
			align-items: center;
			justify-content:center;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .footer-brand .fb-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-brand .fb-mark {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }

        .footer-brand .fb-mark svg {
            width: 34px;
            height: 34px;
        }

        .footer-brand .fb-text .name {
            font-weight: 800;
            color: var(--navy);
            font-size: 1rem;
        }

        .footer-brand .fb-text .sub {
            display: inline-block;
            background: var(--red);
            color: #fff;
            font-size: .58rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 3px;
            margin: 2px 0;
        }

        .footer-brand .fb-text .dept {
            display: block;
            font-size: .58rem;
            color: var(--text-muted);
        }

        .footer-quote {
            font-style: italic;
            font-weight: 600;
            color: var(--navy);
            font-size: .98rem;
            line-height: 1.4;
            max-width: 230px;
        }

        .footer-quote .rule {
            width: 50px;
            height: 3px;
            background: var(--yellow);
            margin-top: 10px;
        }

        footer h4 {
            color: var(--navy);
            font-size: 1rem;
            margin: 0 0 16px;
        }

        footer ul li {
            margin-bottom: 10px;
        }

        footer ul li a {
            color: var(--text-muted);
            font-size: .9rem;
        }

        footer ul li a:hover {
            color: var(--red);
        }

        .social-icons {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .social-icons a {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .9rem;
        }

        .social-icons a:nth-child(1) {
            background: #0b2a52;
        }

        .social-icons a:nth-child(2) {
            background: #111;
        }

        .social-icons a:nth-child(3) {
            background: linear-gradient(45deg, #f58529, #dd2a7b, #8134af);
        }

        .social-icons a:nth-child(4) {
            background: #e11d2a;
        }

        .footer-follow p {
            font-size: .85rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        .footer-map {
            display: flex;
            /* flex-direction: column; */
            /* align-items: flex-end; */
            text-align: right;
			justify-content: center;
			align-items: center;
        }

        .footer-map svg {
            width: 100px;
            height: 110px;
            opacity: .4;
        }

        .footer-map .tag {
            font-weight: 800;
            color: var(--navy);
            font-size: 1.05rem;
            line-height: 1.2;
            margin-top: 6px;
        }

        .footer-map .tag .hl {
            color: var(--red);
        }

        .footer-map .rule {
            width: 50px;
            height: 3px;
            background: var(--yellow);
            margin-top: 8px;
        }

        .footer-bottom {
            background: var(--navy-dark);
            color: #cfd8e3;
            font-size: .82rem;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .footer-bottom .tags span {
            margin-left: 14px;
        }

        .footer-bottom .tags span:first-child {
            margin-left: 0;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 980px) {
            .partner-card {
                grid-template-columns: 1fr;
            }

            .partner-left {
                padding-bottom: 0;
            }

            .hero-side {
                display: none;
            }

            .footer-inner {
                grid-template-columns: 1fr 1fr;
            }

            .footer-map {
                grid-column: span 2;
                align-items: flex-start;
                text-align: left;
            }
        }

        @media (max-width: 760px) {
            nav.main-nav {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fff;
                flex-direction: column;
                padding: 16px 24px;
                gap: 14px;
                box-shadow: 0 8px 16px rgba(0, 0, 0, .08);
                display: none;
            }

            .hero {
                position: relative;
                background: url('{{ asset('assets/logo-cityboy.png') }}');
                /* background:
                linear-gradient(100deg, rgba(8, 31, 61, .92) 0%, rgba(11, 42, 82, .72) 45%, rgba(11, 42, 82, .35) 75%),
                linear-gradient(#4a5f74, #4a5f74); */
                background-size: 50%;
                background-repeat: no-repeat;
                background-position: center;
                color: #fff;
                padding: 56px 32px 64px;
                overflow: hidden;
            }

            nav.main-nav.open {
                display: flex;
            }

            .nav-toggle {
                display: block;
            }

            .join-btn span.label {
                display: none;
            }

            .join-btn {
                padding: 11px 14px;
            }

            .hero h1 {
                font-size: 1.9rem;
            }

            .hero-tagline {
                font-size: .85rem;
            }

            main {
                padding: 0 18px;
                margin: 28px auto;
            }

            .partner-right {
                padding: 32px 22px;
            }

            .partner-left {
                padding: 32px 22px 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            footer {
                padding: 32px 18px 0;
            }

            .footer-inner {
                grid-template-columns: 1fr;
                padding-bottom: 24px;
            }

            .footer-map {
                grid-column: auto;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }

            .footer-bottom .tags span {
                margin: 0 7px;
            }

            .join-btn-sm {
                display: block;
            }
        }

        @media (max-width: 420px) {
            .hero h1 {
                font-size: 1.6rem;
            }

            .partner-left h2 {
                font-size: 1.8rem;
            }
        }

        /* ---------- Success Modal ---------- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(8, 31, 61, .55);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity .2s ease;
        }

        .modal-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .modal-card {
            background: #fff;
            width: 100%;
            max-width: 440px;
            border-radius: 16px;
            padding: 40px 36px 32px;
            text-align: center;
            position: relative;
            box-shadow: 0 25px 60px rgba(8, 31, 61, .35);
            transform: translateY(14px) scale(.97);
            transition: transform .25s ease;
        }

        .modal-overlay.open .modal-card {
            transform: translateY(0) scale(1);
        }

        .modal-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border: none;
            background: var(--grey-bg);
            color: var(--text-muted);
            border-radius: 50%;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease, color .15s ease;
        }

        .modal-close:hover {
            background: var(--border);
            color: var(--navy);
        }

        .modal-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 22px;
            border-radius: 50%;
            background: #e7f6ec;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-icon svg {
            width: 38px;
            height: 38px;
        }

        .modal-card h3 {
            color: var(--navy);
            font-size: 1.4rem;
            margin: 0 0 10px;
        }

        .modal-card p {
            color: var(--text-muted);
            font-size: .95rem;
            line-height: 1.55;
            margin: 0 0 26px;
        }

        .modal-card p strong {
            color: var(--text-dark);
        }

        .modal-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .modal-btn-primary {
            background: var(--red);
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: .95rem;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background .15s ease;
        }

        .modal-btn-primary:hover {
            background: #c81622;
        }

        .modal-btn-secondary {
            background: none;
            border: none;
            color: var(--navy);
            font-weight: 600;
            font-size: .9rem;
            padding: 6px;
            cursor: pointer;
        }

        .modal-btn-secondary:hover {
            color: var(--red);
        }

        .modal-ref {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px dashed var(--border);
            font-size: .8rem;
            color: var(--text-muted);
        }

        .modal-ref span {
            color: var(--navy);
            font-weight: 700;
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="brand">
            <a style="display: flex" href="{{ route('new.homepage') }}">
                <div class="brand-mark">
                    <img src="{{ asset('assets/logo-cityboy.png') }}" alt="city boy Movement logo">
                </div>
                <div class="brand-mark">
                    <img src="{{ asset('assets/Seal_of_Ondo_State_logo.png') }}" alt="ondo state logo">
                </div>
            </a>
            <div class="brand-text">
                <span class="name">CITY BOY<br>MOVEMENT</span>
                <span class="sub">ONDO STATE</span>
                {{-- <span class="dept">ICT/DATA DEPARTMENT</span> --}}
            </div>
        </div>

    </header>

    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-inner">
            <h1>Stronger Youth<br><span class="accent">A Brighter Ondo</span></h1>
            <div class="hero-rule"></div>
            <div class="hero-tagline">
                Inform <span>|</span> Engage <span>|</span> Empower
            </div>
        </div>
        <div class="hero-side">
            Together<br>We Build
            <div class="rule"></div>
        </div>
    </section>

    <main id="partner">
        <div class="partner-card">

            <div class="partner-left">
                <span class="pill">PARTNER WITH US</span>
                <h2>Support<br><span class="hl">Our Programmes</span></h2>
                <p class="lead">Join us in empowering young people, strengthening communities, and building a better
                    Ondo State.</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon blue"><i class="fa-solid fa-graduation-cap"></i></div>
                        <div>
                            <strong>Youth Development</strong>
                            <span>Skills, training and opportunities</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon green"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <strong>Community Initiatives</strong>
                            <span>Stronger and safer communities</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon red"><i class="fa-solid fa-chart-simple"></i></div>
                        <div>
                            <strong>Innovation &amp; Technology</strong>
                            <span>Data-driven solutions for impact</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon yellow"><i class="fa-solid fa-heart"></i></div>
                        <div>
                            <strong>Social Impact</strong>
                            <span>A brighter future for Ondo</span>
                        </div>
                    </div>
                </div>

                <div class="partner-photo">
                    <img src="{{ asset('assets/grand-patron-tinubu-760w.jpg') }}"
                        alt="City Boy Movement volunteers together">
                    <span class="caption">Inform &middot; Engage &middot; Empower</span>
                </div>
            </div>

            <div class="partner-right">
                <h2>Partner With City Boy Movement</h2>
                <p class="intro">Interested in supporting our programmes and community initiatives? Tell us how you
                    would like to contribute.</p>

                <form action="{{ route('register.submit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="f_type" value="support">
                    <div class="form-row">
                        <div class="field">
                            <label for="fullname">Full Name / Organisation Name <span class="req">*</span></label>
                            <input type="text" id="fullname" placeholder="Enter your full name or organisation name"
                                name="name" required>
                        </div>
                        <div class="field">
                            <label for="registerAs">Are you registering as? <span class="req">*</span></label>
                            <select id="registerAs" name="register_as" required>
                                <option value="" selected disabled>Select an option</option>
                                <option>Individual</option>
                                <option>Organisation</option>
                                <option>Government Agency</option>
                                <option>Corporate Sponsor</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="phone">Phone Number <span class="req">*</span></label>
                            <input type="tel" id="phone" placeholder="Enter your phone number" name="phone"
                                required>
                        </div>
                        <div class="field">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email"
                                placeholder="Enter your email address (optional)">
                        </div>
                    </div>

                    <div class="field">
                        <label for="lga">Local Government / Location <span class="req">*</span></label>
                        <select id="lga" name="lga" required>
                            <option value="" selected disabled>Select Local Government</option>
                            @foreach ($lgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                            @endforeach

                        </select>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="supportArea">What would you like to support? <span
                                    class="req">*</span></label>
                            <select id="supportArea" name="support_area" required>
                                <option value="" selected disabled>Select an option</option>
                                <option>Youth Development</option>
                                <option>Community Initiatives</option>
                                <option>Innovation &amp; Technology</option>
                                <option>Social Impact</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="supportType">Type of Support <span class="req">*</span></label>
                            <select name="support_type" id="supportType" required>
                                <option value="" selected disabled>Select an option</option>
                                <option>Financial Support</option>
                                <option>Equipment</option>
                                <option>Training</option>
                                <option>Venue</option>
                                <option>Professional Services</option>
                                <option>Media Support</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="message">Interested in supporting our programmes and community initiatives? Tell
                            us how you would like to contribute. <span class="req">*</span></label>
                        <textarea name="contribution_mssg" id="message"
                            placeholder="E.g. financial support, equipment, training, venue, professional services, media support, etc."
                            required></textarea>
                    </div>

                    <div class="field">
                        <label>Would you like a City Boy representative to contact you? <span
                                class="req">*</span></label>
                        <div class="radio-row">
                            <label class="radio-option">
                                <input type="radio" name="contact" value="yes" checked> Yes
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="contact" value="no"> No
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn"><i class="fa-solid fa-paper-plane"></i> Submit
                        Interest</button>
                    <p class="secure-note"><i class="fa-solid fa-lock"></i> Your information is secure and will be
                        used only for City Boy
                        Movement purposes.</p>
                </form>
            </div>

        </div>
    </main>

    <footer>
        <div class="footer-inner">
            <!-- <div class="footer-brand">
                <div class="fb-top">
                    <div class="fb-mark">
                        {{-- <svg viewBox="0 0 48 48" fill="none">
                            <circle cx="14" cy="16" r="6" fill="#1c8a4b" />
                            <path d="M4 34c0-7 6-12 10-12s10 5 10 12" fill="#1c8a4b" />
                            <circle cx="24" cy="13" r="6.5" fill="#0b2a52" />
                            <path d="M13 34c0-7.5 6.5-13 11-13s11 5.5 11 13" fill="#0b2a52" />
                            <circle cx="34" cy="16" r="6" fill="#e11d2a" />
                            <path d="M24 34c0-7 6-12 10-12s10 5 10 12" fill="#e11d2a" />
                        </svg> --}}
                        <img src="{{ asset('assets/logo-cityboy.png') }}" alt="city boy Movement logo">
                    </div>
                    <div class="fb-text">
                        <span class="name">CITY Boy<br>MOVEMENT</span>
                        <span class="sub">ONDO STATE</span>
                        {{-- <span class="dept">ICT/DATA DEPARTMENT</span> --}}
                    </div>
                </div>
                <p class="footer-quote">
                    &ldquo;Inform Engage Empower.&rdquo;
                <div class="rule"></div>
                </p>
            </div> -->

            {{-- <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Programmes</a></li>
                    <li><a href="#">Get Involved</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div> --}}
            {{--
            <div class="footer-follow">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook">f</a>
                    <a href="#" aria-label="X (Twitter)">X</a>
                    <a href="#" aria-label="Instagram">&#128247;</a>
                    <a href="#" aria-label="YouTube">&#9654;</a>
                </div>
                <p>City Boy Movement, Ondo State<br>ICT/Data Department</p>
            </div> --}}

            <div class="footer-map">
                <!-- <svg viewBox="0 0 100 110" fill="none">
                    <path d="M20 5 L70 10 L85 40 L75 70 L60 100 L30 95 L10 60 L15 25 Z" fill="#0b2a52" />
                </svg> -->
				<img src="{{ asset('assets/logo-apc.png') }}" width="100">
				<img src="{{ asset('assets/vote-thumb.png') }}" width="100">
				<img src="{{ asset('assets/logo-cityboy.png') }}" width="100">
                <!-- <div class="tag">A BRIGHTER<br><span class="hl">ONDO</span> TOGETHER</div> -->
                <!-- <div class="rule"></div> -->
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; 2024 City Boy Movement, Ondo State. All rights reserved.</span>
            <span class="tags">
                <span>People</span> <span>Ideas</span> <span>Action</span> <span>A Better Ondo</span>
            </span>
        </div>
    </footer>
    @if (session('mssg'))
        <div class="modal-overlay" id="successModal" role="dialog" aria-modal="true"
            aria-labelledby="successModalTitle">
            <div class="modal-card">
                <button class="modal-close" id="modalCloseBtn" aria-label="Close">&times;</button>

                <div class="modal-icon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="11" fill="#1c8a4b" />
                        <path d="M7 12.5l3 3 7-7" stroke="#fff" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

                <h3 id="successModalTitle">Thank You for Partnering With Us!</h3>
                <p>Your interest in supporting City Boy Movement has been received. A representative from our team will
                    reach out to you shortly to discuss next steps.</p>

                <div class="modal-actions">
                    <button class="modal-btn-primary" id="modalDoneBtn">Done</button>
                    <button class="modal-btn-secondary" id="modalAnotherBtn">Submit another response</button>
                </div>

                <div class="modal-ref">Reference ID: <span id="modalRefId">CBM-000000</span></div>
            </div>
        </div>
    @endif


    <script>
        (function() {
            var form = document.querySelector('#partner form');
            var overlay = document.getElementById('successModal');
            var closeBtn = document.getElementById('modalCloseBtn');
            var doneBtn = document.getElementById('modalDoneBtn');
            var anotherBtn = document.getElementById('modalAnotherBtn');
            var refId = document.getElementById('modalRefId');

            function openModal() {
                refId.textContent = 'CBM-' + Math.floor(100000 + Math.random() * 900000);
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
                doneBtn.focus();
            }

            function closeModal() {
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }

            openModal();

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // TODO: replace with real submission (fetch/AJAX to backend) before showing the modal
                    openModal();
                });
            }

            closeBtn.addEventListener('click', closeModal);
            doneBtn.addEventListener('click', function() {
                closeModal();
                if (form) form.reset();
            });
            anotherBtn.addEventListener('click', function() {
                closeModal();
                if (form) form.reset();
            });
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
            });
        })();
    </script>

</body>

</html>
