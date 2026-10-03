

// Gate → formA (has voter's card, includes Ward + Polling Unit)



//      → formB (no voter's card, no Ward / Polling Unit)
// Both forms live in the same place in the markup; only one is display:block at a time.
var gate = document.getElementById('voterGate');
var formA = document.getElementById('formA');
var formB = document.getElementById('formB');

function showForm(which) {
    gate.hidden = true;
    if (which === 'yes') {
        formA.style.display = 'block';
        formB.style.display = 'none';
    } else {
        formA.style.display = 'none';
        formB.style.display = 'block';
    }
}

function showGate() {
    formA.style.display = 'none';
    formB.style.display = 'none';
    formA.reset();
    formB.reset();
    document.querySelectorAll('.toggle-option.active').forEach(function (o) { o.classList.remove('active'); });
    gate.hidden = false;
}

document.querySelectorAll('.change-answer').forEach(function (btn) {
    btn.addEventListener('click', showGate);
});

// [formA, formB].forEach(function (form) {
//     form.addEventListener('submit', function (e) {
//         e.preventDefault();
//         alert('Form captured — this will POST to ' + form.getAttribute('action') + ' once your Laravel backend endpoint is live.');
//     });
// });

// Toggle-pill groups (voter's-card gate + each form's contact-consent question)
document.querySelectorAll('.toggle-group').forEach(function (group) {
    var options = group.querySelectorAll('.toggle-option');
    options.forEach(function (opt) {
        opt.addEventListener('click', function () {
            options.forEach(function (o) { o.classList.remove('active'); });
            opt.classList.add('active');
            opt.querySelector('input').checked = true;

            if (group.dataset.toggleGroup === 'voterGate') {
                showForm(opt.dataset.value);
            }
        });
    });
});

// Keep one navigation handler; CSS controls the mobile layout.
var toggle = document.querySelector('.nav-toggle');
var links = document.querySelector('.nav-links');
toggle.addEventListener('click', function () {
    var isOpen = links.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
});



$('#aLga').on('change', function () {

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
        url: '/new/wards/' + lgaId,
        type: 'GET',
        success: function (wards) {

            $('#aWard')
                .html('<option value="">Select Ward</option>')
                .prop('disabled', false);

            $.each(wards, function (index, ward) {
                $('#aWard').append(
                    '<option value="' + ward.id + '">' +
                    ward.code + ' - ' + ward.name +
                    '</option>'
                );
            });
        }
    });

});




$(document).ready(function () {

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



    // 3. Your dynamic change listener
    $('#aWard').on('change', function () {
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
            url: '/new/pollingunits/' + wardId,
            type: 'GET',
            success: function (pollingunits) {

                // Clear and reset the native select elements
                $('#aPollingUnit')
                    .html('<option value="">Select Polling Units</option>')
                    .prop('disabled', false);

                // Loop through and append with custom 'data-code' attribute
                $.each(pollingunits, function (index, pu) {
                    $('#aPollingUnit').append(
                        $('<option>', {
                            value: pu.id,
                            text: `${pu.name} ${ '(' + pu.delimitation_code + ')' }`
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
