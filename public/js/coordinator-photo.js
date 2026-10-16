(() => {
    const form = document.getElementById('coordinator-photo-form');
    if (!form) return;
    const photo = document.getElementById('photo');
    const selfie = document.getElementById('selfie');
    const preview = document.getElementById('photo-preview');
    const previewWrap = document.getElementById('photo-preview-wrap');
    const feedback = document.getElementById('photo-feedback');
    const save = form.querySelector('[type="submit"]');
    const camera = document.getElementById('take-selfie');
    let previewUrl;
    let revision = 0;
    let preparing = false;

    // Leave the ordinary upload usable if file replacement is unsupported.
    try {
        photo.files = new DataTransfer().files;
    } catch (_) {
        return;
    }
    document.getElementById('photo-camera-controls').hidden = false;
    camera.addEventListener('click', () => selfie.click());

    const clearPreview = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = undefined;
        preview.removeAttribute('src');
        previewWrap.hidden = true;
    };
    const reset = () => {
        revision++;
        photo.value = '';
        selfie.value = '';
        clearPreview();
        feedback.textContent = '';
        feedback.dataset.error = 'false';
        preparing = false;
        save.disabled = false;
    };
    document.getElementById('clear-photo').addEventListener('click', reset);

    async function prepare(input) {
        const file = input.files[0];
        if (!file) return;
        const currentRevision = ++revision;
        clearPreview();
        photo.value = '';
        selfie.value = '';
        preparing = true;
        save.disabled = true;
        feedback.dataset.error = 'false';
        feedback.textContent = 'Preparing your photo…';
        let sourceUrl;
        try {
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                throw new Error('Please choose a JPG, PNG or WebP photo. If your camera uses HEIC, export it as JPG first.');
            }
            if (file.size > 25 * 1024 * 1024) throw new Error('Please choose a picture smaller than 25 MB.');
            sourceUrl = URL.createObjectURL(file);
            const image = new Image();
            await new Promise((resolve, reject) => {
                image.onload = resolve;
                image.onerror = () => reject(new Error('This picture could not be opened. Please choose another.'));
                image.src = sourceUrl;
            });
            const scale = Math.min(1, 1600 / Math.max(image.naturalWidth, image.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
            canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .85));
            if (!blob || blob.size > 2 * 1024 * 1024) throw new Error('This photo is too large. Please choose a smaller picture.');
            if (revision !== currentRevision) return;
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'profile-photo.jpg', {type: 'image/jpeg'}));
            photo.files = transfer.files;
            previewUrl = URL.createObjectURL(blob);
            preview.src = previewUrl;
            previewWrap.hidden = false;
            feedback.textContent = 'Photo ready. Tap Save photo to upload it.';
        } catch (error) {
            if (revision !== currentRevision) return;
            feedback.textContent = error.message || 'Unable to prepare your photo. Please try another picture.';
            feedback.dataset.error = 'true';
        } finally {
            if (sourceUrl) URL.revokeObjectURL(sourceUrl);
            if (revision === currentRevision) {
                preparing = false;
                save.disabled = false;
            }
        }
    }
    photo.addEventListener('change', () => prepare(photo));
    selfie.addEventListener('change', () => prepare(selfie));
    form.addEventListener('submit', event => {
        if (preparing) event.preventDefault();
    });
    window.addEventListener('pagehide', () => { if (previewUrl) URL.revokeObjectURL(previewUrl); });
})();
