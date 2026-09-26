
// ---------------------------------------------------------------------
// NOTE: This is a frontend-only UI demonstration.
// No data is sent anywhere, no backend is called, and no registration
// ID is generated here — the ID shown in the success modal is a static
// placeholder that a Laravel backend will later replace.
// ---------------------------------------------------------------------

(function () {
    var cardOptionYes = document.getElementById('cardOptionYes');
    var cardOptionNo = document.getElementById('cardOptionNo');
    var radioYes = document.getElementById('hasCardYes');
    var radioNo = document.getElementById('hasCardNo');

    var formA = document.getElementById('formA');
    var formB = document.getElementById('formB');

    var progressStep2 = document.getElementById('progressStep2');
    var progressStep3 = document.getElementById('progressStep3');

    function selectVoterCardOption(hasCard) {
        if (hasCard === 'yes') {
            cardOptionYes.classList.add('is-selected');
            cardOptionNo.classList.remove('is-selected');
            formA.classList.add('is-visible');
            formB.classList.remove('is-visible');
        } else {
            cardOptionNo.classList.add('is-selected');
            cardOptionYes.classList.remove('is-selected');
            formB.classList.add('is-visible');
            formA.classList.remove('is-visible');
        }
        progressStep2.classList.add('is-active');
    }

    radioYes.addEventListener('change', function () {
        if (radioYes.checked) selectVoterCardOption('yes');
    });

    radioNo.addEventListener('change', function () {
        if (radioNo.checked) selectVoterCardOption('no');
    });

    // ---------------- Success modal ----------------

    var modal = document.getElementById('successModal');
    var doneBtn = document.getElementById('modalDoneBtn');
    var copyBtn = document.getElementById('copyIdBtn');
    var lastFocusedElement = null;

    function openModal() {
        lastFocusedElement = document.activeElement;
        modal.classList.add('is-open');
        progressStep3.classList.add('is-active');
        doneBtn.focus();
        document.addEventListener('keydown', handleModalKeydown);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        document.removeEventListener('keydown', handleModalKeydown);
        if (lastFocusedElement) lastFocusedElement.focus();
    }

    function handleModalKeydown(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    }

    // Demonstration only: submitting either form shows the success modal.
    // No fetch, AJAX, or backend call is performed.
    function handleDemoSubmit(event) {
        event.preventDefault();
        openModal();
    }

    openModal();

    // formA.addEventListener('submit', handleDemoSubmit);
    // formB.addEventListener('submit', handleDemoSubmit);

    doneBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    copyBtn.addEventListener('click', function () {
        var idText = document.getElementById('registration-id').textContent;
        var originalLabel = copyBtn.textContent;

        function showCopied() {
            copyBtn.textContent = 'Copied!';
            setTimeout(function () {
                copyBtn.textContent = originalLabel;
            }, 1800);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(idText).then(showCopied).catch(function () {
                fallbackCopy(idText, showCopied);
            });
        } else {
            fallbackCopy(idText, showCopied);
        }
    });

    function fallbackCopy(text, onDone) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            onDone();
        } catch (e) {
            // Copy not supported in this environment; no further action needed.
        }
        document.body.removeChild(textarea);
    }
})();
