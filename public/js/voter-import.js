const lga = document.getElementById('import-lga');
const ward = document.getElementById('import-ward');
const unit = document.getElementById('import-polling-unit');
const feedback = document.getElementById('import-location-feedback');
const requests = new Map();

function resetOptions(select, label) {
    requests.get(select)?.abort();
    requests.delete(select);
    select.replaceChildren(new Option(label, ''));
    select.disabled = true;
    select.removeAttribute('aria-busy');
}

async function loadOptions(select, parentId, label) {
    resetOptions(select, label);
    feedback.textContent = '';
    if (!parentId) return;

    const controller = new AbortController();
    requests.set(select, controller);
    select.setAttribute('aria-busy', 'true');
    feedback.textContent = 'Loading locations…';
    try {
        const response = await fetch(select.dataset.url.replace('__id__', encodeURIComponent(parentId)), {
            signal: controller.signal,
            headers: {Accept: 'application/json'},
        });
        if (!response.ok) throw new Error('Location request failed');
        const options = await response.json();
        if (controller.signal.aborted) return;
        options.forEach((option) => {
            const code = option.delimitation_code ?? option.code;
            select.add(new Option(code ? `${code} — ${option.name}` : option.name, option.id));
        });
        select.disabled = options.length === 0;
        feedback.textContent = options.length ? '' : 'No locations available for this selection.';
    } catch (error) {
        if (error.name !== 'AbortError') {
            feedback.textContent = 'Locations could not be loaded. Select the parent location again to retry.';
        }
    } finally {
        if (requests.get(select) === controller) {
            requests.delete(select);
            select.removeAttribute('aria-busy');
        }
    }
}

lga.addEventListener('change', () => {
    resetOptions(unit, 'Select polling unit');
    loadOptions(ward, lga.value, 'Select ward');
});
ward.addEventListener('change', () => loadOptions(unit, ward.value, 'Select polling unit'));
