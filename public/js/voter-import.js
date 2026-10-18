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

const importHistory = document.querySelector('[data-import-history]');
if (importHistory) {
    let keepPolling = true;
    const message = importHistory.querySelector('[data-import-refresh-status]');
    const active = () => [...importHistory.querySelectorAll('[data-import-status]')].some(row => ['queued', 'processing'].includes(row.dataset.importStatus));
    async function refreshImports() {
        try {
            if (document.hidden) return;
            const response = await fetch(importHistory.dataset.statusUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'});
            if (response.redirected || response.status === 401 || response.status === 403) {
                keepPolling = false;
                message.textContent = 'Your session has ended. Sign in again to check import status.';
                return;
            }
            if (!response.ok) throw new Error('Status unavailable');
            const result = await response.json();
            for (const item of result.imports) {
                const row = importHistory.querySelector(`[data-import-id="${Number(item.id)}"]`);
                if (!row) continue;
                row.dataset.importStatus = item.status;
                row.querySelector('[data-import-label]').textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                row.querySelector('[data-import-count]').textContent = item.voters_count === null ? '' : `${Number(item.voters_count).toLocaleString()} voters`;
                row.querySelector('[data-import-error]').textContent = item.error ?? '';
                row.querySelector('[data-import-retry]').hidden = !item.can_retry;
            }
            message.textContent = '';
        } catch {
            message.textContent = 'Live status is unavailable. Your upload continues; refresh this page to check again.';
        } finally {
            if (keepPolling && active()) window.setTimeout(refreshImports, 10000);
        }
    }
    if (active()) window.setTimeout(refreshImports, 5000);
}

const uploadForm = document.getElementById('voter-import-form');
uploadForm?.addEventListener('submit', () => {
    const submit = uploadForm.querySelector('[type="submit"]');
    submit.disabled = true;
    submit.textContent = 'Uploading PDF…';
});
window.addEventListener('pageshow', () => {
    const submit = uploadForm?.querySelector('[type="submit"]');
    if (submit) {
        submit.disabled = false;
        submit.textContent = 'Upload & queue →';
    }
});
