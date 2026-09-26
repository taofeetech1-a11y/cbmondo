<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ondo State Executive Inauguration | City Boy Movement</title>
    <link rel="shortcut icon" href="{{ asset('assets/logo-cityboy.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css"
        integrity="sha512-x9WwyMYBnlXMNQ6kQ/Lyzu1NqIhLQKL5Oq6xByfXuRj7s9CskyCbLv/1IjqzJmXwFXWr0ov6jBV7Qbc0hh9nHg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('assets/register-event.css') }}">
</head>

<body>

    <!-- ============ DECORATIVE FLAG BACKGROUND ============ -->
    <div class="flag-bg" aria-hidden="true"></div>

    <!-- ============ HEADER ============ -->
    <header class="site-header">
        <div class="header-inner">
            <div class="brand-group">
                <div class="logo cbm-logo">
                    <img src="{{ asset('assets/logo-cityboy.png') }}" alt="" width="50px">
                </div>
                <span class="brand-divider"></span>
                <div class="apc-mark">
                    <img src="{{ asset('assets/Seal_of_Ondo_State_logo.png') }}" alt="">
                </div>
                <span class="brand-divider"></span>
                <div class="logo apc-logo">
                    <div class="apc-mark">
                        <img src="{{ asset('assets/logo-apc.png') }}" alt="">
                    </div>
                    <div class="apc-text">
                        <span class="apc-name">APC</span>
                        <span class="apc-sub">ONDO STATE</span>
                    </div>
                </div>
            </div>

            <a href="#register" class="btn btn--primary btn--nav">
                Register Now
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </a>

        </div>
    </header>

    <main>
        <!-- ============ HERO ============ -->
        <section class="hero" id="home">
            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                            <path d="M12 7V12L15 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        </svg>
                        City Boy Movement
                    </span>

                    <h1 class="hero-title">Ondo State<br>Executive Inauguration</h1>
                    <p class="hero-subtitle">Register to attend and get your digital gate pass.</p>

                    <ul class="event-meta">
                        <li>
                            <span class="meta-icon meta-icon--green">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="16" rx="2" stroke="white"
                                        stroke-width="1.7" />
                                    <path d="M3 9H21" stroke="white" stroke-width="1.7" />
                                    <path d="M8 3V6M16 3V6" stroke="white" stroke-width="1.7" stroke-linecap="round" />
                                </svg>
                            </span>
                            <span>Wednesday,<br><strong>16 September 2026</strong></span>
                        </li>
                        <li>
                            <span class="meta-icon meta-icon--green">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="white" stroke-width="1.7" />
                                    <path d="M12 7V12L15.5 14" stroke="white" stroke-width="1.7"
                                        stroke-linecap="round" />
                                </svg>
                            </span>
                            <span>10:00 AM<br><strong>Prompt</strong></span>
                        </li>
                        <li>
                            <span class="meta-icon meta-icon--red">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 22C12 22 19 15.5 19 10A7 7 0 1 0 5 10C5 15.5 12 22 12 22Z"
                                        stroke="white" stroke-width="1.7" />
                                    <circle cx="12" cy="10" r="2.4" stroke="white"
                                        stroke-width="1.7" />
                                </svg>
                            </span>
                            <span>The Dome,<br><strong>Alagbaka, Akure</strong></span>
                        </li>
                    </ul>

                    <p class="hero-tagline">PEOPLE&nbsp; | &nbsp;PROGRESS&nbsp; | &nbsp;A GREATER ONDO</p>
                </div>

                <div class="hero-portrait">
                    <div class="portrait-frame">
                        <!-- <div class="portrait-photo" role="img"
                            aria-label="Portrait placeholder — replace with the dignitary's photo"></div> -->
                        <img src="{{ asset('assets/grand-patron-tinubu-760w.jpg') }}" alt="">
                    </div>
                    <p class="portrait-slogan">A GREATER<br>ONDO<br>IS POSSIBLE</p>
                    <div class="portrait-caption">
                        <strong><big>Asiwaju</big><br /> Bola Ahmed Tinubu</strong>
                        <span>PRESIDENT,<br>FEDERAL REPUBLIC OF NIGERIA</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ REGISTRATION + GATE PASS ============ -->
        <section class="registration" id="register">
            <div class="registration-inner">

                <!-- Registration Form Card -->
                <div class="card form-card">
                    <div class="card-head">
                        <span class="card-icon card-icon--green">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="8" r="3.4" stroke="#1B6B3C" stroke-width="1.7" />
                                <path d="M5 20C5 16.5 8 14 12 14C16 14 19 16.5 19 20" stroke="#1B6B3C"
                                    stroke-width="1.7" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <h2>Event Registration</h2>
                            <p>Fill in your details below to register for the City Boy Movement Ondo State Executive
                                Inauguration and
                                get your digital gate pass.</p>
                        </div>
                    </div>
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Whoops! Something went wrong.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
                    <form class="reg-form" id="regForm" method="POST" action="{{ route('register.submit') }}">
                        @csrf
                        <input type="hidden" name="f_type" value="event">
                        <div class="form-row">
                            <div class="field">
                                <label for="fullName">Full Name <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none">
                                        <circle cx="12" cy="8" r="3.4" stroke="#9CA3AF"
                                            stroke-width="1.6" />
                                        <path d="M5 20C5 16.5 8 14 12 14C16 14 19 16.5 19 20" stroke="#9CA3AF"
                                            stroke-width="1.6" stroke-linecap="round" />
                                    </svg>
                                    <input type="text" id="fullName" name="fullName"
                                        placeholder="Enter your full name" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="phone">Phone Number <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none">
                                        <path
                                            d="M5 4H9L11 9L8.5 10.5C9.5 12.8 11.2 14.5 13.5 15.5L15 13L20 15V19C20 19.55 19.55 20 19 20C11.27 20 5 13.73 5 6C5 5.45 4.9 4.9 5 4Z"
                                            stroke="#9CA3AF" stroke-width="1.5" stroke-linejoin="round" />
                                    </svg>
                                    <input type="tel" id="phone" name="phone"
                                        placeholder="Enter your phone number" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="field">
                                <label for="email">Email Address <span class="opt">(Optional)</span></label>
                                <div class="input-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none">
                                        <rect x="3" y="5" width="18" height="14" rx="2"
                                            stroke="#9CA3AF" stroke-width="1.6" />
                                        <path d="M4 6.5L12 12.5L20 6.5" stroke="#9CA3AF" stroke-width="1.6"
                                            stroke-linecap="round" />
                                    </svg>
                                    <input type="email" id="email" name="email"
                                        placeholder="Enter your email address">
                                </div>
                            </div>
                            <div class="field">
                                <label for="lga">Local Government Area <small>(Select Others if your not from ondo state)</small> <span class="req">*</span></label>
                                <div class="input-wrap select-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none">
                                        <path d="M5 21V8L12 3L19 8V21" stroke="#9CA3AF" stroke-width="1.6"
                                            stroke-linejoin="round" />
                                        <path d="M9 21V13H15V21" stroke="#9CA3AF" stroke-width="1.6" />
                                    </svg>
                                    <select id="lga" name="lga" required>
                                        <option value="" disabled selected>Select LGA </option>
                                        @foreach ($lgas as $lga)
                                            <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                        @endforeach
											<option value="others">Others</option>

                                    </select>
                                    <svg class="chevron" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none">
                                        <path d="M6 9L12 15L18 9" stroke="#6B7280" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">

                            <div class="field">
                                <label for="category">Category of Attendee <span class="req">*</span></label>
                                <div class="input-wrap select-wrap">
                                    <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24"
                                        fill="none">
                                        <circle cx="12" cy="8" r="3.4" stroke="#9CA3AF"
                                            stroke-width="1.6" />
                                        <path d="M5 20C5 16.5 8 14 12 14C16 14 19 16.5 19 20" stroke="#9CA3AF"
                                            stroke-width="1.6" stroke-linecap="round" />
                                    </svg>
                                    <select id="category" name="category" required>
                                        <option value="" disabled selected>Select Category</option>
                                        <option value="Special Guest / VIP">Special Guest / VIP</option>
                                        <option value="City Boy Movement Executive / Member">City Boy Movement
                                            Executive / Member</option>
                                        <option value="Media / Event Support">Media / Event Support</option>
                                        <option value="Security / Protocol">Security / Protocol</option>
                                        <option value="Others">Others</option>
                                    </select>
                                    <svg class="chevron" width="14" height="14" viewBox="0 0 24 24"
                                        fill="none">
                                        <path d="M6 9L12 15L18 9" stroke="#6B7280" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="contact-toggle">
                            <span class="card-icon card-icon--green small">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M5 4H9L11 9L8.5 10.5C9.5 12.8 11.2 14.5 13.5 15.5L15 13L20 15V19C20 19.55 19.55 20 19 20C11.27 20 5 13.73 5 6C5 5.45 4.9 4.9 5 4Z"
                                        stroke="#1B6B3C" stroke-width="1.5" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <p>Would you like a City Boy representative to contact you? <span class="req">*</span>
                            </p>
                            <div class="radio-group">
                                <label class="radio"><input type="radio" name="allow_contact" value="yes"
                                        checked><span class="radio-dot"></span>Yes</label>
                                <label class="radio"><input type="radio" name="allow_contact" value="no"><span
                                        class="radio-dot"></span>No</label>
                            </div>
                        </div>

                        <label class="agree">
                            <input type="checkbox" name="consent" required>
                            <span class="check-box"></span>
                            I agree that the information provided is correct and may be used for event communication by
                            the City Boy
                            Movement Ondo State.
                        </label>

                        <button disabled type="submit" class="btn btn--primary btn--block">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 12L8 17L9.5 7L21 4L15.5 20L11 15" stroke="currentColor" stroke-width="1.7"
                                    stroke-linejoin="round" />
                            </svg>
                            Register &amp; Get Gate Pass
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="1.6"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <p class="secure-note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="11" width="14" height="9" rx="2" stroke="#6B7280"
                                    stroke-width="1.6" />
                                <path d="M8 11V8A4 4 0 0 1 16 8V11" stroke="#6B7280" stroke-width="1.6" />
                            </svg>
                            Your information is secure and will only be used for this event.
                        </p>
                    </form>
                </div>

                <!-- Gate Pass Preview Card -->
                <div class="card pass-card ">
                    <div class="card-head">
                        <span class="card-icon card-icon--pink">
                            <i class="fa-solid fa-ticket" style="color: #d6336c;"></i>
                        </span>
                        <div>
                            <h2>Your Digital Gate Pass</h2>
                            <p>Here's a preview of what your gate pass will look like after registration.</p>
                        </div>
                    </div>

                    <div class="gate-pass ">
                        <div class="pass-top">
                            <div class="pass-brand">
                                <div class="logo cbm-logo">
                                    <img src="{{ asset('assets/logo-cityboy.png') }}" width="50px"
                                        alt="city boy logo" srcset="">
                                </div>
                                <div class="apc-mark">
                                    <img src="{{ asset('assets/logo-apc.png') }}" alt="apc logo" srcset="">
                                </div>
                                <div class="apc-mark">
                                    <img src="{{ asset('assets/Seal_of_Ondo_State_logo.png') }}" alt="apc logo"
                                        srcset="">
                                </div>
                            </div>
                            <span class="pass-tagline">PEOPLE<br>PROGRESS<br>A GREATER ONDO</span>
                        </div>

                        <div class="pass-banner">EVENT GATE PASS</div>

                        <div class="pass-body">
                            <div class="pass-details">
                                <h3>Ondo State<br>Executive Inauguration</h3>
                                <ul>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="5" width="18" height="16" rx="2"
                                                stroke="#1B6B3C" stroke-width="1.8" />
                                            <path d="M3 9H21" stroke="#1B6B3C" stroke-width="1.8" />
                                        </svg>
                                        Wednesday, 16 September 2026
                                    </li>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="9" stroke="#1B6B3C"
                                                stroke-width="1.8" />
                                            <path d="M12 7V12L15.5 14" stroke="#1B6B3C" stroke-width="1.8"
                                                stroke-linecap="round" />
                                        </svg>
                                        10:00 AM Prompt
                                    </li>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 22C12 22 19 15.5 19 10A7 7 0 1 0 5 10C5 15.5 12 22 12 22Z"
                                                stroke="#1B6B3C" stroke-width="1.8" />
                                        </svg>
                                        The Dome, Alagbaka, Akure
                                    </li>
                                </ul>
                                <p class="attendee-label">Attendee</p>
                                <p class="attendee-name ">********</p>
                            </div>

                            <div class="pass-qr">
                                <div class="qr-box " aria-label="QR code placeholder"></div>
                                <span>Scan at Entrance</span>
                            </div>
                        </div>

                        <p class="pass-id ">Pass ID: CBM-ONDO-******</p>
                        <p class="pass-footer-slogan">A GREATER ONDO<br>IS POSSIBLE</p>
                    </div>

                    <p class="pass-hint">Your unique gate pass will be generated instantly after successful
                        registration
                        and also
                        sent to your email (if provided).</p>
                </div>

            </div>

            <!-- Category strip -->
            <div class="category-strip">
                <div class="category-item">
                    <span class="cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="9" cy="8" r="3" stroke="#1B6B3C" stroke-width="1.6" />
                            <circle cx="17" cy="9" r="2.4" stroke="#1B6B3C" stroke-width="1.6" />
                            <path d="M3 20C3 16.5 5.7 14 9 14C12.3 14 15 16.5 15 20" stroke="#1B6B3C"
                                stroke-width="1.6" stroke-linecap="round" />
                            <path d="M15 15.3C17.8 15.7 20 17.6 20 20" stroke="#1B6B3C" stroke-width="1.6"
                                stroke-linecap="round" />
                        </svg></span>
                    <div><strong>Guest</strong>
                        <p>For invited guests and the general public</p>
                    </div>
                </div>
                <div class="category-item">
                    <span class="cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="3.4" stroke="#1B6B3C" stroke-width="1.6" />
                            <path d="M5 20C5 16.5 8 14 12 14C16 14 19 16.5 19 20" stroke="#1B6B3C" stroke-width="1.6"
                                stroke-linecap="round" />
                        </svg></span>
                    <div><strong>Executive</strong>
                        <p>State, LGA and ward executives</p>
                    </div>
                </div>
                <div class="category-item">
                    <span class="cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12 21C12 21 4 14.6 4 9.2C4 6.3 6.2 4 9 4C10.5 4 11.6 4.7 12 5.6C12.4 4.7 13.5 4 15 4C17.8 4 20 6.3 20 9.2C20 14.6 12 21 12 21Z"
                                stroke="#1B6B3C" stroke-width="1.6" stroke-linejoin="round" />
                        </svg></span>
                    <div><strong>Supporter</strong>
                        <p>For dedicated movement supporters</p>
                    </div>
                </div>
                <div class="category-item">
                    <span class="cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12 21C12 21 4 14.6 4 9.2C4 6.3 6.2 4 9 4C10.5 4 11.6 4.7 12 5.6C12.4 4.7 13.5 4 15 4C17.8 4 20 6.3 20 9.2C20 14.6 12 21 12 21Z"
                                stroke="#1B6B3C" stroke-width="1.6" stroke-linejoin="round" />
                        </svg></span>
                    <div><strong>Volunteer</strong>
                        <p>For event volunteers and team members</p>
                    </div>
                </div>
                <div class="category-item">
                    <span class="cat-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="7" width="18" height="13" rx="2" stroke="#1B6B3C"
                                stroke-width="1.6" />
                            <circle cx="12" cy="13.5" r="3.5" stroke="#1B6B3C" stroke-width="1.6" />
                            <path d="M8 7L9.5 4.5H14.5L16 7" stroke="#1B6B3C" stroke-width="1.6"
                                stroke-linejoin="round" />
                        </svg></span>
                    <div><strong>Media</strong>
                        <p>For accredited media and press</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="logo cbm-logo cbm-logo--light">
                <span class="logo-word">City</span>
                <span class="logo-word logo-word--bold">Boy</span>
                <span class="logo-sub">MOVEMENT</span>
            </div>
            <p class="footer-tagline">INFORM&nbsp; | &nbsp;ENGAGE&nbsp; | &nbsp;EMPOWER</p>
            <p class="footer-slogan">Together for a Greater Ondo</p>
        </div>
    </footer>

</body>

</html>
