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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css"
        integrity="sha512-x9WwyMYBnlXMNQ6kQ/Lyzu1NqIhLQKL5Oq6xByfXuRj7s9CskyCbLv/1IjqzJmXwFXWr0ov6jBV7Qbc0hh9nHg=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('assets/register-event.css') }}">
</head>

<body>

    {{-- @if (session()->has('data'))
        @php
            $guest = session('data');
        @endphp
    @endif --}}
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

                <!-- Gate Pass Preview Card -->
                <div class="card pass-card" id="passgate">
                    <div class="card-head">
                        <span class="card-icon card-icon--pink">
                            <i class="fa-solid fa-ticket" style="color: #d6336c;"></i>
                        </span>
                        <div style="display: flex; justify-content: space-between;">
                            <div>
                                <h2>Your Digital Gate Pass</h2>
                                <p>Here's your gate pass</p>
                            </div>
                            <button type="button" id="downloadPass" class="btn btn--primary" style="padding: 5px">
                                <i class="fa-solid fa-download"></i>
                                Download Gate Pass
                            </button>
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

                        <div class="pass-banner">GATE PASS</div>

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
                                <p class="attendee-name ">{{ $guest->name }}</p>
                            </div>

                            <div class="pass-qr">
                                <div class="qr-box" id="qrcode"></div>
                                <span>Scan at Entrance</span>
                            </div>
                        </div>

                        <p class="pass-id ">Pass ID: {{ $guest->pass_id }}</p>
                        <p class="pass-id ">Category: <strong> {{ $guest->category }} </strong></p>
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
            <p class="footer-tagline">PEOPLE&nbsp; | &nbsp;PROGRESS&nbsp; | &nbsp;A GREATER ONDO</p>
            <p class="footer-slogan">Together for a Greater Ondo</p>
        </div>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        document.getElementById('downloadPass').addEventListener('click', async function() {

            const pass = document.querySelector('.gate-pass');

            if (!pass) {
                alert('Gate pass could not be found.');
                return;
            }

            try {
                const canvas = await html2canvas(pass, {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                });

                const link = document.createElement('a');

                link.download = 'CBM-Ondo-Gate-Pass-{{ $guest->pass_id }}.png';

                link.href = canvas.toDataURL('image/png');

                link.click();

            } catch (error) {
                console.error('Failed to generate gate pass:', error);
                alert('Unable to download the gate pass. Please try again.');
            }
        });
    </script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "{{ $guest->pass_id }}",
            width: 150,
            height: 150
        });
    </script>
    <script>
    window.addEventListener('load', function () {
        const registerSection = document.getElementById('passgate');

        if (registerSection) {
            registerSection.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
</script>
</body>

</html>
