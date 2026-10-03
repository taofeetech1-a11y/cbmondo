<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <x-seo page="new.homepage" />
    <link rel="icon" href="{{ asset('assets/logo-cityboy.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('assets/m.css') }}">
</head>

<body>

    <div class="hero">
        <div class="hero-bg-flag" aria-hidden="true"></div>
        <div class="hero-bg-crowd" aria-hidden="true"></div>

        <div class="wrap">
            <nav class="nav">
                <div class="brand">
                    <div class="brand-mark" aria-hidden="true">
                        <!-- <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12 12c2.2 0 4-1.8 4-4s-1.8-4-4-4-4 1.8-4 4 1.8 4 4 4zm-7 8c0-3 3-5.5 7-5.5s7 2.5 7 5.5"
                                stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg> -->
                        <img src="{{ asset('assets/logo-cityboy.png') }}" alt="" srcset="">
                    </div>
                    <div class="brand-text">
                        <div class="line1">CITY BOY</div>
                        <div class="line2">MOVEMENT</div>
                        <div class="line3">ONDO STATE</div>
                        <div class="brand-underline"></div>
                    </div>
                </div>

                <div class="nav-links" id="homepage-navigation">
                    <a href="#" class="active">Home</a>
                    <a href="{{ route('about.page') }}">About</a>
                    <a href="{{ route('contact.page') }}">Contact</a>
                    <a href="{{ route('updates.page') }}">Updates</a>
                    <a href="{{ route('support.page') }}">Support</a>
                    <a href="#reg">Register</a>
                </div>

                <div class="nav-secondary">
                    <a href="#">Inform</a><span class="dot">|</span>
                    <a href="#">Engage</a><span class="dot">|</span>
                    <a href="#">Empower</a>
                </div>

                <button class="nav-toggle" aria-label="Open menu" aria-controls="homepage-navigation" aria-expanded="false"><span></span><span></span><span></span></button>
            </nav>

            <div class="hero-inner">
                <div class="hero-copy">
                    <h1 class="hero-title">JOIN<br>CITY BOY MOVEMENT<br><span class="accent">ONDO STATE</span></h1>
                    <p class="hero-sub">RENEWED HOPE FOR A <br> BETTER NIGERIA </p>
                    <div class="hero-cta">
                        Register Today!
                        <svg viewBox="0 0 150 16">
                            <path d="M2 12c30-14 100-14 146 0" stroke="#c0182c" stroke-width="2.5" fill="none"
                                stroke-linecap="round" />
                        </svg>
                    </div>
                </div>

                <div class="hero-figure">
                    <div class="hero-portrait">
                        <!-- <svg viewBox="0 0 200 240" aria-hidden="true">
                            <circle cx="100" cy="92" r="52" fill="#4a5b83" />
                            <path d="M30 240c0-55 32-96 70-96s70 41 70 96z" fill="#5c6d94" />
                        </svg> -->
                        <img src="{{ asset('assets/grand-patron-tinubu-760w.jpg') }}" alt="" height="100%">
                    </div>
                    <!-- <div class="hero-slogan">A STRONGER ONDO<br>A BRIGHTER NIGERIA</div> -->
                    <!-- <div class="party-tag">MOVEMENT</div> -->
                </div>
            </div>
        </div>
    </div>

    <div class="wrap panel-wrap" id="reg">
        <div class="panel">
            <div class="form-side">
                <div class="eyebrow-line"></div>
                <h2 class="section-title">Register Now</h2>
                <p class="section-desc">Register as a City Boy Movement member in Ondo State, with or without a voter card. Select your local government area and enter your details to receive your CBM membership ID and download your card.</p>

                <div class="gate-block" id="voterGate">
                    <div class="field">
                        <label>Do You Have a Voter's Card?<span class="req">*</span></label>
                        <p class="gate-note">Your answer determines which registration form you'll fill in next.</p>
                        <div class="toggle-group" data-toggle-group="voterGate">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="voterGate" value="yes">
                                <span></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="voterGate" value="no">
                                <span></span> No
                            </label>
                        </div>
                    </div>
                </div>

                <form class="field-grid form-panel" id="formA" action="{{ route('cbm.register') }}" method="POST">
                    @csrf
                    <button type="button" class="change-answer">&larr; Change answer</button>

                    <div class="field">
                        <label>Full Name<span class="req">*</span></label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"
                                    stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>
                        @error('name')
                            <small style="color: red">{{ $message }}</small>
                        @enderror

                    </div>

                    <div class="field">
                        <label>Phone Number<span class="req">*</span></label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"
                                    stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                            </svg>
                            <input type="tel" name="phone" placeholder="Phone Number" required max='15'>
                        </div>
                        @error('phone')
                            <small style="color: red">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label>Email (Optional)</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.6"
                                    stroke-linejoin="round" />
                                <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="email" name="email" placeholder="Email (Optional)">
                        </div>
                        @error('email')
                            <small style="color: red">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field-row-2">
                        <div class="field">
                            <label>Age Range<span class="req">*</span></label>
                            <div class="input-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="5" width="17" height="15" rx="2"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <path d="M3.5 9.5h17M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6"
                                        stroke-linecap="round" />
                                </svg>
                                <select name="ageRange" id="">
                                    <option value="" selected>Select Age Range</option>
                                    <option value="18-24">18–24</option>
                                    <option value="25-34">25–34</option>
                                    <option value="35-44">35–44</option>
                                    <option value="45-54">45–54</option>
                                    <option value="55-64">55–64</option>
                                    <option value="65+">65+</option>
                                </select>
                            </div>
                            @error('ageRange')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="field">
                            <label>Gender<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="9" cy="8" r="3.2" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="M3.5 20c0-3 2.5-5.5 5.5-5.5S14.5 17 14.5 20" stroke="currentColor"
                                        stroke-width="1.6" stroke-linecap="round" />
                                    <circle cx="17" cy="8" r="3.2" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="M13.5 14.7c.6-.15 1.3-.2 2-.2 3 0 5.5 2.5 5.5 5.5" stroke="currentColor"
                                        stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <select name="gender" required>
                                    <option value="" selected disabled>Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            @error('name')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="field-row-2">
                        <div class="field">
                            <label>State<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 22s7-7.2 7-12.5A7 7 0 005 9.5C5 14.8 12 22 12 22z"
                                        stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                    <circle cx="12" cy="9.5" r="2.4" stroke="currentColor"
                                        stroke-width="1.6" />
                                </svg>
                                <select name="state">
                                    <option selected value="28">Ondo State</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label>Local Government<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 21V9l8-5 8 5v12" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                    <path d="M9 21v-6h6v6" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                </svg>
                                <select id="aLga" name="lga" required>
                                    <option value="" selected disabled>Select LGA</option>
                                    @foreach ($lgas as $lga)
                                        <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                    @endforeach

                                </select>
                            </div>
                            @error('lga')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="field-row-2">
                        <div class="field">
                            <label>Ward<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M3 10l9-7 9 7" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                    <path d="M5 9v11h14V9" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                </svg>
                                <select id="aWard" name="ward" required>
                                    <option value="" selected disabled>Select Ward</option>

                                </select>
                            </div>
                            @error('ward')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="field">
                            <label>Polling Unit<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="4" width="7" height="7" rx="1.2"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <rect x="13" y="4" width="7" height="7" rx="1.2"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <rect x="4" y="13" width="7" height="7" rx="1.2"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <rect x="13" y="13" width="7" height="7" rx="1.2"
                                        stroke="currentColor" stroke-width="1.6" />
                                </svg>
                                <select id="aPollingUnit" name="polling_unit" required>
                                    <option value="" selected disabled>Select Polling Unit</option>
                                    {{--
                                     --}}
                                </select>
                            </div>
                            @error('polling_unit')
                                <small style="color: red">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label>Would You Like To Be Contacted By City Boy?<span class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsentA">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="contact_consent" value="yes" required>
                                <span></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="contact_consent" value="no">
                                <span></span> No
                            </label>
                        </div>
                        @error('contact_consent')
                            <small style="color: red">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="field">
                        <label>Is your PVC registered in the same Ward/Polling Unit where you currently live?<span
                                class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsent">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="same_address" value="yes" required>
                                <span></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="same_address" value="no">
                                <span></span> No
                            </label>
                        </div>
                        @error('same_address')
                            <small style="color: red">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="field">
                        <label>Interested in supporting our programmes and community initiatives?<span
                                class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsent">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="support" value="yes" required>
                                <span></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="support" value="no">
                                <span></span> No
                            </label>
                        </div>
                        @error('support')
                            <small style="color: red">{{ $message }}</small>
                        @enderror
                    </div>

                    <input type="hidden" name="has_voters_card" value="yes">

                    <button type="submit" class="submit-btn">
                        Submit &amp; Get My Card
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>

                <form class="field-grid form-panel" id="formB" action="{{ route('nc.register') }}"
                    method="POST">
                    @csrf
                    <button type="button" class="change-answer">&larr; Change answer</button>

                    <div class="field">
                        <label>Full Name<span class="req">*</span></label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"
                                    stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>Phone Number<span class="req">*</span></label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"
                                    stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                            </svg>
                            <input type="tel" name="phone" placeholder="Phone Number" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>Email (Optional)</label>
                        <div class="input-shell">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.6"
                                    stroke-linejoin="round" />
                                <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6"
                                    stroke-linejoin="round" />
                            </svg>
                            <input type="email" name="email" placeholder="Email (Optional)">
                        </div>
                    </div>

                    <div class="field-row-2">
                        <div class="field">
                            <label>Age Range<span class="req">*</span></label>
                            <div class="input-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="5" width="17" height="15" rx="2"
                                        stroke="currentColor" stroke-width="1.6" />
                                    <path d="M3.5 9.5h17M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6"
                                        stroke-linecap="round" />
                                </svg>
                                <select name="ageRange" id="">
                                    <option value="" selected>Select Age Range</option>
                                    <option value="18-24">18–24</option>
                                    <option value="25-34">25–34</option>
                                    <option value="35-44">35–44</option>
                                    <option value="45-54">45–54</option>
                                    <option value="55-64">55–64</option>
                                    <option value="65+">65+</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label>Gender<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="9" cy="8" r="3.2" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="M3.5 20c0-3 2.5-5.5 5.5-5.5S14.5 17 14.5 20" stroke="currentColor"
                                        stroke-width="1.6" stroke-linecap="round" />
                                    <circle cx="17" cy="8" r="3.2" stroke="currentColor"
                                        stroke-width="1.6" />
                                    <path d="M13.5 14.7c.6-.15 1.3-.2 2-.2 3 0 5.5 2.5 5.5 5.5" stroke="currentColor"
                                        stroke-width="1.6" stroke-linecap="round" />
                                </svg>
                                <select name="gender" required>
                                    <option value="" selected disabled>Select</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field-row-2">
                        <div class="field">
                            <label>State<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 22s7-7.2 7-12.5A7 7 0 005 9.5C5 14.8 12 22 12 22z"
                                        stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                                    <circle cx="12" cy="9.5" r="2.4" stroke="currentColor"
                                        stroke-width="1.6" />
                                </svg>
                                <select name="state">
                                    <option selected>Ondo State</option>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label>Local Government<span class="req">*</span></label>
                            <div class="input-shell select-shell">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 21V9l8-5 8 5v12" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                    <path d="M9 21v-6h6v6" stroke="currentColor" stroke-width="1.6"
                                        stroke-linejoin="round" />
                                </svg>
                                <select name="lga" required>
                                    <option value="" selected disabled>Select LGA</option>
                                    @foreach ($lgas as $lga)
                                        <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Would You Like To Be Contacted By City Boy?<span class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsentB">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="contact_consent" value="yes" required>
                                <span class="dot"></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="contact_consent" value="no">
                                <span class="dot"></span> No
                            </label>
                        </div>
                    </div>

                    {{-- <div class="field">
                        <label>Is your PVC registered in the same Ward/Polling Unit where you currently live?<span
                                class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsent">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="contactConsent" value="yes" required>
                                <span class="dot"></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="contactConsent" value="no">
                                <span class="dot"></span> No
                            </label>
                        </div>
                    </div> --}}

                    <div class="field">
                        <label>Interested in supporting our programmes and community initiatives?<span
                                class="req">*</span></label>
                        <div class="toggle-group" data-toggle-group="contactConsent">
                            <label class="toggle-option" data-value="yes">
                                <input type="radio" name="support" value="yes" required>
                                <span class="dot"></span> Yes
                            </label>
                            <label class="toggle-option" data-value="no">
                                <input type="radio" name="support" value="no">
                                <span class="dot"></span> No
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="has_voters_card" value="no">

                    <button type="submit" class="submit-btn">
                        Submit &amp; Get My Card
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>


                <div class="safe-note">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l8 4v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V6l8-4z" stroke="currentColor"
                            stroke-width="1.6" stroke-linejoin="round" />
                        <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Your information is safe and will only be used for City Boy Movement.
                </div>
            </div>

            <div class="card-side">
                <div class="eyebrow-line" style="background:var(--sky)"></div>
                <h2 class="section-title" style="font-size:23px;">Get Your Membership Card</h2>
                <p class="section-desc">After submitting, your card will be generated instantly.</p>

                <div class="id-card" id="idCard">
                    <div class="id-card-top">
                        <div class="id-card-brand">
                            <div class="mark">
                                <img src="{{ asset('assets/logo-cityboy.png') }}" alt="">
                            </div>
                            <div class="txt">
                                <b>CITY BOY MOVEMENT · ONDO STATE</b>
                            </div>
                        </div>
                    </div>
                        <div class="id-card-body">
                            <div class="id-info">
                                <p class="id-name"><strong>Name: </strong>Member Name</p>
                                <p class="id-role">Member</p>
                            </div>
                            <div class="id-qr" aria-hidden="true"></div>
                        </div>

                        <div class="id-card-footer">
                            <span class="id-serial"><strong>CBM ID: </strong>CBM-OS-0001234</span>
                            <span class="id-motto">Inform . Engage . Empower</span>
                        </div>
                    <div class="id-card-strip"></div>

                </div>

                <button type="button" 
                    style="border: 1px solid red; border-radius: 5px; background-color: transparent; cursor: pointer; background: #c0182c; padding: 8px; font-weight: 700; color: #e3e7f0;">
                    Download ID Card
                </button>

                <div class="steps">
                    <div class="step">
                        <div class="step-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="5" y="3" width="14" height="18" rx="2" stroke="#fff"
                                    stroke-width="1.7" />
                                <path d="M9 8h6M9 12h6M9 16h3" stroke="#fff" stroke-width="1.7"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="step-label">1. Fill Details</div>
                    </div>
                    <div class="step-arrow">&rarr;</div>
                    <div class="step">
                        <div class="step-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M5 13l4 4 10-10" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                        <div class="step-label">2. Submit</div>
                    </div>
                    <div class="step-arrow">&rarr;</div>
                    <div class="step">
                        <div class="step-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="3" y="6" width="18" height="12" rx="2" stroke="#fff"
                                    stroke-width="1.7" />
                                <path d="M3 10h18" stroke="#fff" stroke-width="1.7" />
                            </svg>
                        </div>
                        <div class="step-label">3. Get Your Card</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer id="contact">
        <div class="wrap footer-inner" style="padding-top: 8px;">
            <div class="footer-brand">
                <div class="mark">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M12 12c2.2 0 4-1.8 4-4s-1.8-4-4-4-4 1.8-4 4 1.8 4 4 4zm-7 8c0-3 3-5.5 7-5.5s7 2.5 7 5.5"
                            stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <img src="{{ asset('assets/logo-cityboy.png') }}" alt="" srcset="">
                </div>
                <div class="txt">
                    <b>CITY BOY &nbsp; ONDO STATE</b>
                    <span>MOVEMENT</span>
                </div>
            </div>

            <div class="footer-motto" style=" display: flex;">
                <img src="{{ asset('assets/logo-apc.png') }}" alt="" width="100px">
                <img src="{{ asset('assets/vote-thumb.png') }}" alt="" width="100px">
            </div>

            <div class="footer-contact">
                <div class="label">Need Help?</div>
                <div>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                        <path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"
                            stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                    </svg>
                    +234 813 093 0238
                </div>
                <div>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                        <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                    </svg>
                    info@cbmondo.org
                </div>
                <div>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                        <path d="M12 21s7-7.5 7-12a7 7 0 1 0-14 0c0 4.5 7 12 7 12z" stroke="currentColor"
                            stroke-width="1.6" stroke-linejoin="round" />
                        <circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.6" />
                    </svg>

                </div>
            </div>

            <div class="footer-social">
                <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none">
                        <path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14V9.4c0-.3.2-.4.5-.4z"
                            fill="#fff" />
                    </svg></a>
                <a href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 5l14 14M19 5L5 19" stroke="#fff" stroke-width="1.8" stroke-linecap="round" />
                    </svg></a>
                <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none">
                        <rect x="4" y="4" width="16" height="16" rx="5" stroke="#fff"
                            stroke-width="1.6" />
                        <circle cx="12" cy="12" r="3.4" stroke="#fff" stroke-width="1.6" />
                        <circle cx="17" cy="7" r="1" fill="#fff" />
                    </svg></a>
                <a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="6" width="18" height="12" rx="3" stroke="#fff"
                            stroke-width="1.6" />
                        <path d="M10.5 9.5l5 2.5-5 2.5z" fill="#fff" />
                    </svg></a>
            </div>
        </div>
    </footer>



	@if (session('data'))
    <div class="modal">

        <div class="card-side">
            <div class="eyebrow-line" style="background:var(--sky)"></div>
            <h2 class="section-title" style="font-size:23px;">Get Your Membership Card</h2>
            <p class="section-desc">Download Your Membership Card</p>

            <div class="id-card" id="idCard">
                <div class="id-card-top">
                    <div class="id-card-brand">
                        <div class="mark">
                            <img src="{{ asset('assets/logo-cityboy.png') }}" alt="">
                        </div>
                        <div class="txt">
                            <b>CITY BOY MOVEMENT · ONDO STATE</b>
                        </div>
                    </div>
                </div>
                <div class="id-card-body">
                    <div class="id-info">
                        <p class="id-name"><strong>Name: </strong>{{ strtoupper(session('data')['name']) }}
                        </p>
                        <p class="id-role">Member</p>
                        <p class="id-name"><strong>Member ID:
                            </strong>{{ strtoupper(session('data')['mem_id']) }}</p>
                    </div>
                    <div class="id-qr" id="memberQr" aria-hidden="true"></div>
                </div>

                <div class="id-card-footer">
                    <span class="id-serial"><strong>CBM ID: </strong>{{ session('data')['cbm_id'] }}</span>
                    <span class="id-motto">Inform . Engage . Empower</span>
                </div>
                <div class="id-card-strip"></div>

            </div>

            <button type="button" id="downloadIdCard"
                style="border: 1px solid red; border-radius: 5px; background-color: transparent; cursor: pointer; background: #c0182c; padding: 8px; font-weight: 700; color: #e3e7f0;">
                Download ID Card
            </button>

        </div>

    </div>
@endif

	@if (session('dataTwo'))
    <div class="modal">

        <div class="card-side">
            <div class="eyebrow-line" style="background:var(--sky)"></div>
            <h2 class="section-title" style="font-size:23px;">Get Your Membership Card</h2>
            <p class="section-desc">Download Your Membership Card</p>

            <div class="id-card" id="idCard">
                <div class="id-card-top">
                    <div class="id-card-brand">
                        <div class="mark">
                            <img src="{{ asset('assets/logo-cityboy.png') }}" alt="">
                        </div>
                        <div class="txt">
                            <b>CITY BOY MOVEMENT · ONDO STATE</b>
                        </div>
                    </div>
                </div>
                <div class="id-card-body">
                    <div class="id-info">
                        <p class="id-name"><strong>Name: </strong>{{ strtoupper(session('dataTwo')['name']) }}
                        </p>
                        <p class="id-role">Member</p>
                    </div>
                    <div class="id-qr" id="memberQr" aria-hidden="true"></div>
                </div>

                <div class="id-card-footer">
                    <span class="id-serial"><strong>CBM ID: </strong>{{ session('dataTwo')['cbm_id'] }}</span>
                    <span class="id-motto">Inform . Engage . Empower</span>
                </div>

                <div class="id-card-strip"></div>

            </div>

            <button type="button" id="downloadIdCard"
                style="border: 1px solid red; border-radius: 5px; background-color: transparent; cursor: pointer; background: #c0182c; padding: 8px; font-weight: 700; color: #e3e7f0;">
                Download ID Card
            </button>

        </div>

    </div>
@endif


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"
        integrity="sha512-8LENNbXmzI/Gbj+OwXmqR6V4QaUAw0/porPzy1+dQoJqC0JPHedWoe0DDOTL2uHA5XXJyIsPtiMHH86pVlay6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        // document.getElementById('regForm').addEventListener('submit', function (e) {
        //     e.preventDefault();
        //     alert('Form captured — connect this to your Laravel backend endpoint to generate the membership card.');
        // });


    </script>
    <script src="{{ asset('assets/k.js') }}"></script>

    @if (session('data'))
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

        <script>
            new QRCode(document.getElementById("memberQr"), {
                text: @json(session('data')['mem_id']),
                width: 150,
                height: 150
            });
        </script>
    @endif

    @if (session('dataTwo'))
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

        <script>
            new QRCode(document.getElementById("memberQr"), {
                text: @json(session('dataTwo')['cbm_id']),
                width: 150,
                height: 150
            });
        </script>
    @endif


    <script>
        document.getElementById('downloadIdCard').addEventListener('click', function() {
            const card = document.getElementById('idCard');

            html2canvas(card, {
                scale: 3,
                useCORS: true,
                backgroundColor: null
            }).then(canvas => {
                const link = document.createElement('a');

                link.download = 'city-boy-id-card.png';
                link.href = canvas.toDataURL('image/png');

                link.click();
            });
        });
    </script>
</body>

</html>
