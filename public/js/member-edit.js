const card = document.getElementById('member-card');
const lga = document.getElementById('member-lga');
const ward = document.getElementById('member-ward');
const unit = document.getElementById('member-pu');
const address = document.getElementById('member-same_address');
const feedback = document.getElementById('location-feedback');
const requests = new Map();

function resetOptions(select, label) {
  requests.get(select)?.abort();
  requests.delete(select);
  select.replaceChildren(new Option(label, ''));
  select.removeAttribute('aria-busy');
}
function syncCardFields() {
  const hasCard = card.value === 'yes';
  [ward, unit, address].forEach((select) => {
    select.disabled = !hasCard;
    select.required = hasCard;
    select.closest('.field').hidden = !hasCard;
  });
  if (!hasCard) feedback.textContent = '';
}
async function loadOptions(select, parentId, label) {
  resetOptions(select, label);
  if (!parentId) return;
  const controller = new AbortController();
  requests.set(select, controller);
  select.setAttribute('aria-busy', 'true');
  feedback.textContent = 'Loading locations…';
  try {
    const response = await fetch(`${select.dataset.url}/${encodeURIComponent(parentId)}`, {signal: controller.signal, headers: {Accept: 'application/json'}});
    if (!response.ok) throw new Error('Location request failed');
    const options = await response.json();
    if (controller.signal.aborted) return;
    options.forEach((option) => select.add(new Option(option.name, option.id)));
    feedback.textContent = options.length ? '' : 'No locations available for this selection.';
  } catch (error) {
    if (error.name !== 'AbortError') feedback.textContent = 'Locations could not be loaded. Select the parent location again to retry.';
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
card.addEventListener('change', syncCardFields);
syncCardFields();
