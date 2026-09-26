<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Portal</title>
    <link rel="shortcut icon" href="{{ asset('assets/logo-cityboy.png') }}" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">


    <style>
        /* 1. Style the main closed select box */
        .field .select2-container--default .select2-selection--single {
            background-color: #fff;
            /* Background color */
            border: 1px solid #ccc;
            /* Border styling */
            border-radius: 6px;
            /* Rounded corners */
            height: 42px;
            /* Custom height matching modern inputs */
            padding: 6px 12px;
            transition: all 0.2s ease-in-out;
        }

        /* 2. Style the placeholder or text inside the selection box */
        .field .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #333;
            font-size: 15px;
            line-height: 28px;
            /* Centers the text vertically */
            padding-left: 0;
        }

        /* 3. Adjust the positioning of the dropdown arrow */
        .field .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 10px;
        }

        /* 4. Style the input box when it is active / clicked (Focus state) */
        .field .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #007bff;
            /* Changes border to blue on click */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            /* Adds a subtle glowing ring */
            background-color: #ffffff;
        }

        /* ==========================================
   OPTIONAL: Styling the floating dropdown menu list
   ========================================== */

        /* 5. Style the individual options in the list */
        .select2-results__option {
            padding: 8px 12px;
            font-size: 15px;
        }

        /* 6. Style the option you are currently hovering over */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #007bff !important;
            /* Blue background on hover */
            color: #ffffff !important;
            /* White text on hover */
        }

        /* 7. Style the option that is currently selected inside the list */
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e9ecef;
            color: #333;
        }
    </style>
</head>

<body>

    <header class="site-header">
        <div class="site-header__inner">
            <a href="#" class="brand" aria-label="Registration Portal home">
                <img src="{{ asset('assets/logo-cityboy.png') }}" class="brand-mark" alt="">
                <img src="{{ asset('assets/logo-apc.png') }}" class="brand-mark" alt="">
                <img src="{{ asset('assets/Seal_of_Ondo_State_logo.png') }}" class="brand-mark" alt="">
                <span class="brand-name">City Boy Movement Ondo State - Registration Portal</span>
            </a>
        </div>
    </header>

    <section class="hero">
        <div class="hero-overlay">

        </div>
        <div class="hero__inner">
            <h1 class="hero__title">Join City Boy Movement — Ondo State</h1>
            <p class="hero__text">This registration is for individuals joining City Boy, Ondo State chapter. Complete
                the form below to register.</p>
            <a class="hero__link" href="https://cbmnigeria.org/" target="_blank" rel="noopener noreferrer">Learn more
                about City Boy Movement</a>
            <a class="hero__link" href="{{ route('support.page') }}" target="_blank" rel="noopener noreferrer">Support City
                Boy Movement Ondo State</a>
        </div>
    </section>

    <main>

        <div class="intro">
            <h1>Registration</h1>
            <p>Please provide your information below to complete your registration. Make sure the details you enter are
                accurate before submitting.</p>
        </div>

        <nav class="progress" aria-label="Registration progress">
            <div class="progress__step is-active" id="progressStep1">
                <span class="progress__circle" aria-hidden="true">1</span>
                <span class="progress__label">Voter's card</span>
            </div>
            <span class="progress__connector" aria-hidden="true"></span>
            <div class="progress__step" id="progressStep2">
                <span class="progress__circle" aria-hidden="true">2</span>
                <span class="progress__label">Registration details</span>
            </div>
            <span class="progress__connector" aria-hidden="true"></span>
            <div class="progress__step" id="progressStep3">
                <span class="progress__circle" aria-hidden="true">3</span>
                <span class="progress__label">Confirmation</span>
            </div>
        </nav>

        <section class="question-block" aria-labelledby="voterCardQuestion">
            <h2 id="voterCardQuestion">Do you have a voter's card?</h2>

            <div class="option-grid" role="radiogroup" aria-labelledby="voterCardQuestion">

                <label class="option-card" for="hasCardYes" id="cardOptionYes">
                    <span class="option-card__indicator" aria-hidden="true">
                        <svg viewBox="0 0 14 14" fill="none">
                            <path d="M2 7L5.5 10.5L12 3.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                    <input type="radio" name="hasVoterCard" id="hasCardYes" value="yes" class="visually-hidden">
                    <span class="option-card__title">Yes</span>
                    <p class="option-card__desc">I have a voter's card</p>
                </label>

                <label class="option-card" for="hasCardNo" id="cardOptionNo">
                    <span class="option-card__indicator" aria-hidden="true">
                        <svg viewBox="0 0 14 14" fill="none">
                            <path d="M2 7L5.5 10.5L12 3.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                    <input type="radio" name="hasVoterCard" id="hasCardNo" value="no"
                        class="visually-hidden">
                    <span class="option-card__title">No</span>
                    <p class="option-card__desc">I don't have a voter's card</p>
                </label>

            </div>
        </section>

        <!-- Form A: user has a voter's card -->
        <form class="registration-form" id="formA" method="POST" action="{{ route('register.submit') }}">
            @csrf
            <input type="hidden" name="f_type" value="member">
            <fieldset class="form-section">
                <legend>Personal information</legend>
                <div class="field-grid">

                    <div class="field">
                        <label for="aName">Name</label>
                        <input type="text" id="aName" name="name" placeholder="Enter your full name"
                            required>
                    </div>

                    <div class="field">
                        <label for="aPhone">Phone Number</label>
                        <input type="tel" id="aPhone" name="phone" placeholder="Enter your phone number"
                            required>
                    </div>

                    <div class="field">
                        <label for="aEmail">Email <span class="optional-tag">(optional)</span></label>
                        <input type="email" id="aEmail" name="email"
                            placeholder="Enter your email address (optional)">
                    </div>

                    <div class="field">
                        <label for="aGender">Gender</label>
                        <select id="aGender" name="gender" required>
                            <option value="" selected disabled>Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <div class="field field--full">
                        <label for="aAgeRange">Age Range</label>
                        <select id="aAgeRange" name="ageRange" required>
                            <option value="" selected disabled>Select Age Range</option>
                            <option value="18-24">18–24</option>
                            <option value="25-34">25–34</option>
                            <option value="35-44">35–44</option>
                            <option value="45-54">45–54</option>
                            <option value="55-64">55–64</option>
                            <option value="65+">65+</option>
                        </select>
                    </div>

                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Location information</legend>
                <div class="field-grid">

                    <div class="field">
                        <label for="aState">State</label>
                        <select id="aState" name="state" required>
                            <option value="ondo state" selected disabled>Ondo State</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="aLga">LGA</label>
                        <select id="aLga" name="lga" required>
                            <option value="" selected disabled>Select LGA</option>
                            @foreach ($lgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                            @endforeach


                        </select>
                    </div>

                    <div class="field">
                        <label for="aWard">Ward</label>
                        <select id="aWard" name="ward" required>
                            <option value="" selected disabled>Select Ward</option>
                            {{-- <option value="ward-1">Ward 1</option>
                            <option value="ward-2">Ward 2</option>
                            <option value="ward-3">Ward 3</option>
                            <option value="ward-4">Ward 4</option> --}}
                        </select>
                    </div>

                    <div class="field">
                        <label for="aPollingUnit">Polling Unit</label>
                        <select id="aPollingUnit" name="pollingUnit" required>
                            <option value="" selected disabled>Select Polling Unit</option>
                            {{-- <option value="pu-001">PU 001 — Local Government Primary School</option>
                            <option value="pu-002">PU 002 — Community Hall</option>
                            <option value="pu-003">PU 003 — Township Stadium</option> --}}
                        </select>
                    </div>

                    <div class="field field--full">
                        <label id="aContactRepLabel">Is your PVC registered in the same Ward/Polling Unit where you
                            currently live?</label>
                        <div class="radio-inline-group" role="radiogroup" aria-labelledby="aContactRepLabel">
                            <label class="radio-inline" for="aContactRepYes">
                                <input type="radio" name="location_unit" id="aContactRepYes" value="yes">
                                Yes
                            </label>
                            <label class="radio-inline" for="aContactRepNo">
                                <input type="radio" name="location_unit" id="aContactRepNo" value="no">
                                No
                            </label>
                        </div>
                    </div>
                    <div class="field field--full">
                        <label id="aContactRepLabel">Would you like a City Boy representative to contact you?</label>
                        <div class="radio-inline-group" role="radiogroup" aria-labelledby="aContactRepLabel">
                            <label class="radio-inline" for="aContactRepYes">
                                <input type="radio" name="contact" id="aContactRepYes" value="yes">
                                Yes
                            </label>
                            <label class="radio-inline" for="aContactRepNo">
                                <input type="radio" name="contact" id="aContactRepNo" value="no">
                                No
                            </label>
                        </div>
                    </div>
                    <div class="field field--full">
                        <label id="aContactRepLabel">Interested in supporting our programmes and community
                            initiatives?</label>
                        <div class="radio-inline-group" role="radiogroup" aria-labelledby="aContactRepLabel">
                            <label class="radio-inline" for="aContactRepYes">
                                <input type="radio" name="support" id="aContactRepYes" value="yes">
                                Yes
                            </label>
                            <label class="radio-inline" for="aContactRepNo">
                                <input type="radio" name="support" id="aContactRepNo" value="no">
                                No
                            </label>
                        </div>
                    </div>

                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Complete Registration</button>
            </div>
        </form>

        <!-- Form B: user does not have a voter's card -->
        <form class="registration-form" id="formB" method="POST" action="{{ route('register.submit') }}">
            @csrf
            <input type="hidden" name="f_type" value="volunteer">
            <fieldset class="form-section">
                <legend>Personal information</legend>
                <div class="field-grid">

                    <div class="field">
                        <label for="bName">Name</label>
                        <input type="text" id="bName" name="name" placeholder="Enter your full name"
                            required>
                    </div>

                    <div class="field">
                        <label for="bPhone">Phone Number</label>
                        <input type="tel" id="bPhone" name="phone" placeholder="Enter your phone number"
                            required>
                    </div>

                    <div class="field">
                        <label for="bEmail">Email <span class="optional-tag">(optional)</span></label>
                        <input type="email" id="bEmail" name="email"
                            placeholder="Enter your email address (optional)">
                    </div>

                    <div class="field">
                        <label for="bGender">Gender</label>
                        <select id="bGender" name="gender" required>
                            <option value="" selected disabled>Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <div class="field field--full">
                        <label for="bOccupation">Occupation</label>
                        <select id="bOccupation" name="occupation" required>
                            <option value="" selected disabled>Select Occupation </option>
                            <option value="Student">Student</option>
                            <option value="Civil/Public Servant">Civil/Public Servant</option>
                            <option value="Private Sector Employee">Private Sector Employee</option>
                            <option value="Business Owner / Entrepreneur">Business Owner / Entrepreneur</option>
                            <option value="Self-Employed / Artisan">Self-Employed / Artisan</option>
                            <option value="Farmer / Agriculturist">Farmer / Agriculturist</option>
                            <option
                                value="Professional — ICT, Health, Legal, Finance, Engineering, Education,
                                etc.">
                                Professional — ICT, Health, Legal, Finance, Engineering, Education,
                                etc.</option>
                            <option value="Retired / Others">Retired / Others</option>
                        </select>
                    </div>

                    <div class="field field--full">
                        <label for="bAgeRange">Age Range</label>
                        <select id="bAgeRange" name="ageRange" required>
                            <option value="" selected disabled>Select Age Range</option>
                            <option value="18-24">18–24</option>
                            <option value="25-34">25–34</option>
                            <option value="35-44">35–44</option>
                            <option value="45-54">45–54</option>
                            <option value="55-64">55–64</option>
                            <option value="65+">65+</option>
                        </select>
                    </div>

                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Location information</legend>
                <div class="field-grid">

                    <div class="field">
                        <label for="bState">State</label>
                        <select id="bState" name="state">
                            <option value="" selected disabled>Ondo State</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="bLga">LGA</label>
                        <select id="bLga" name="lga" required>
                            <option value="" selected disabled>Select LGA</option>
                            @foreach ($lgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Complete Registration</button>
            </div>
        </form>

    </main>

    <section class="spon">
        <div class="sponsors">
            <img src="{{ asset('assets/logo-cityboy.png') }}" class="brand-mark" alt="">
            <img src="{{ asset('assets/vote-thumb.png') }}" class="brand-mark" alt="">
            <img src="{{ asset('assets/logo-apc.png') }}" class="brand-mark" alt="">
            <img src="{{ asset('assets/Seal_of_Ondo_State_logo.png') }}" class="brand-mark" alt="">
        </div>
    </section>

    <footer class="site-footer">
        &copy; 2026 City Boy Movement Ondo State
    </footer>

    @if (session('mssg'))
        <!-- Success / confirmation modal -->
        <div class="modal-overlay" id="successModal" role="dialog" aria-modal="true"
            aria-labelledby="successModalTitle">
            <div class="modal">
                <div class="modal__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="11" fill="#227A29" />
                        <path d="M7 12.5L10.2 15.7L17 8.5" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <h2 id="successModalTitle">Registration Successful!</h2>
                <p>Your registration has been completed successfully.</p>

                <div class="reg-id-box">
                    <p class="reg-id-box__label">Registration ID</p>
                    <!-- Placeholder only — the real ID will be generated by the backend -->
                    <p class="reg-id-box__value"><span id="registration-id">{{ session('mssg') }}</span></p>
                </div>
                <p class="reg-id-note">Please keep this registration ID safe. You will need it to verify your
                    registration
                    later.
                </p>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="copyIdBtn">Copy Registration ID</button>
                    <button type="button" class="btn btn-primary" id="modalDoneBtn">Done</button>
                </div>
            </div>
        </div>
    @endif



    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"
        integrity="sha512-8LENNbXmzI/Gbj+OwXmqR6V4QaUAw0/porPzy1+dQoJqC0JPHedWoe0DDOTL2uHA5XXJyIsPtiMHH86pVlay6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('assets/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>


    <script>
        $(document).ready(function() {
            $("select").select2();
        });



        $('#aLga').on('change', function() {

            let lgaId = $(this).val();

            $('#aWard')
                .prop('disabled', true)
                .html('<option value="">Loading wards...</option>');

            if (!lgaId) {
                $('#aWard')
                    .html('<option value="">Select Ward</option>')
                    .prop('disabled', true);

                return;
            }

            $.ajax({
                url: '/location/wards/' + lgaId,
                type: 'GET',
                success: function(wards) {

                    $('#aWard')
                        .html('<option value="">Select Ward</option>')
                        .prop('disabled', false);

                    $.each(wards, function(index, ward) {
                        $('#aWard').append(
                            '<option value="' + ward.id + '">' +
                            ward.code + ' - ' + ward.name +
                            '</option>'
                        );
                    });
                }
            });

        });


        // polling Units
        // $('#aWard').on('change', function() {

        //     let wardId = $(this).val();

        //     $('#aPollingUnit')
        //         .prop('disabled', true)
        //         .html('<option value="">Loading polling units...</option>');

        //     if (!wardId) {
        //         $('#aPollingUnit')
        //             .html('<option value="">Select Ward</option>')
        //             .prop('disabled', true);

        //         return;
        //     }

        //     $.ajax({
        //         url: '/location/pollingunits/' + wardId,
        //         type: 'GET',
        //         success: function(pollingunits) {

        //             $('#aPollingUnit')
        //                 .html('<option value="">Select Polling Units</option>')
        //                 .prop('disabled', false);

        //             $.each(pollingunits, function(index, pu) {
        //                 $('#aPollingUnit').append(
        //                     '<option value="' + pu.id + '">' +
        //                     pu.name + '<br>' + ' <small>' + pu.delimitation_code +
        //                     '</option>'
        //                 );
        //             });
        //         }
        //     });

        // });



        $(document).ready(function() {

            // 1. Define the layout template function for Select2
            function formatPollingUnit(state) {
                if (!state.id) {
                    return state.text;
                } // Return placeholder text as-is

                // Grab the code from the data attribute we added during the AJAX call
                var code = $(state.element).data('code');
                if (!code) {
                    return state.text;
                }

                // Return the structural HTML wrapper (This places code on a second line)
                var $result = $(
                    '<span>' +
                    '<span class="pu-name" style="display:block; font-weight: 500;">' + state.text + '</span>' +
                    '<span class="pu-code" style="display:block; font-size: 0.85em; color: #666; margin-top: 2px;">' +
                    code + '</span>' +
                    '</span>'
                );
                return $result;
            }

            // 2. Initialize Select2 on the Ward and Polling Unit selectors
            $('#aWard').select2();

            // Initialize Polling Unit with the template formatters
            $('#aPollingUnit').select2({
                templateResult: formatPollingUnit, // Formats options inside the dropdown menu
                templateSelection: formatPollingUnit, // Formats the selected item inside the box
                placeholder: "Select Polling Units"
            });

            // 3. Your dynamic change listener
            $('#aWard').on('change', function() {
                let wardId = $(this).val();

                $('#aPollingUnit')
                    .prop('disabled', true)
                    .html('<option value="">Loading polling units...</option>')
                    .trigger('change'); // Notify Select2 that content is loading

                if (!wardId) {
                    $('#aPollingUnit')
                        .html('<option value="">Select Ward</option>')
                        .prop('disabled', true)
                        .trigger('change');
                    return;
                }

                $.ajax({
                    url: '/location/pollingunits/' + wardId,
                    type: 'GET',
                    success: function(pollingunits) {

                        // Clear and reset the native select elements
                        $('#aPollingUnit')
                            .html('<option value="">Select Polling Units</option>')
                            .prop('disabled', false);

                        // Loop through and append with custom 'data-code' attribute
                        $.each(pollingunits, function(index, pu) {
                            $('#aPollingUnit').append(
                                $('<option>', {
                                    value: pu.id,
                                    text: pu.name
                                }).attr('data-code', pu
                                    .delimitation_code
                                ) // Store the code here safely
                            );
                        });

                        // IMPORTANT: Tell Select2 to destroy its cached view and re-render the new data
                        $('#aPollingUnit').trigger('change');
                    }
                });
            });
        });
    </script>

</body>

</html>
