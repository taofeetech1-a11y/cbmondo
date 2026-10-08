// ---- date ----
const dateEl = document.getElementById('todayDate');
if (dateEl) {
  dateEl.textContent =
    new Date().toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }) +
    ' | ' +
    new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

// ---- sidebar toggle (mobile) ----
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('backdrop');
const hamburger = document.getElementById('hamburger');
const sidebarClose = document.getElementById('sidebarClose');

function openSidebar() {
  hamburger?.setAttribute('aria-expanded', 'true');
  sidebar?.classList.add('open');
  backdrop?.classList.add('show');
}
function closeSidebar() {
  hamburger?.setAttribute('aria-expanded', 'false');
  sidebar?.classList.remove('open');
  backdrop?.classList.remove('show');
}
hamburger?.addEventListener('click', openSidebar);
sidebarClose?.addEventListener('click', closeSidebar);
backdrop?.addEventListener('click', closeSidebar);
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && sidebar?.classList.contains('open')) {
    closeSidebar();
    hamburger?.focus();
  }
});

// ---- Charts ----
// window.dashboardData is set by an inline @push('scripts') block in
// resources/views/membership/index.blade.php -- see that file for the
// server-side data it's built from. Member rows and the LGA breakdown
// bars are rendered directly by Blade now, so there's nothing to build
// here for those two.
const dashboardData = window.dashboardData || {
  trend: { labels: [], data: [] },
  gender: { male: 0, female: 0 },
};

const trendCanvas = document.getElementById('trendChart');
if (trendCanvas && window.Chart && !Chart.getChart(trendCanvas)) {
  new Chart(trendCanvas, {
    type: 'line',
    data: {
      labels: dashboardData.trend.labels,
      datasets: [
        {
          data: dashboardData.trend.data,
          borderColor: '#1a7a3d',
          backgroundColor: 'rgba(26,122,61,0.08)',
          borderWidth: 2.5,
          pointRadius: 3,
          pointBackgroundColor: '#1a7a3d',
          tension: 0.35,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { color: '#5c6b85', font: { size: 11 } }, grid: { color: '#eef0f5' } },
        x: { ticks: { color: '#5c6b85', font: { size: 11 } }, grid: { display: false } },
      },
    },
  });
}

const genderCanvas = document.getElementById('genderChart');
if (genderCanvas && window.Chart && !Chart.getChart(genderCanvas)) {
  new Chart(genderCanvas, {
    type: 'doughnut',
    data: {
      labels: ['Male', 'Female'],
      datasets: [
        {
          data: [dashboardData.gender.male, dashboardData.gender.female],
          backgroundColor: ['#2563eb', '#e0447c'],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: { legend: { display: false }, tooltip: { enabled: true } },
    },
  });
}

document.querySelectorAll('[data-member-actions]').forEach((button) => {
  const menu = document.getElementById(button.getAttribute('popovertarget'));
  const positionMenu = () => {
    const rect = button.getBoundingClientRect();
    const width = Math.min(224, window.innerWidth - 24);
    menu.style.width = `${width}px`;
    menu.style.left = `${Math.max(12, Math.min(rect.right - width, window.innerWidth - width - 12))}px`;
    menu.style.top = `${Math.max(12, Math.min(rect.bottom + 6, window.innerHeight - menu.offsetHeight - 12))}px`;
  };
  menu.addEventListener('toggle', (event) => {
    if (event.newState === 'open') positionMenu();
  });
  window.addEventListener('resize', () => { if (menu.matches(':popover-open')) positionMenu(); });
  document.addEventListener('scroll', (event) => { if (!menu.contains(event.target) && menu.matches(':popover-open')) menu.hidePopover(); }, true);
});

const directory = document.getElementById('member-directory');
if (directory) {
  const controls = [...document.querySelectorAll('[data-directory-column]')];
  const storageKey = 'cbm.directory.hidden-columns.v1';
  let hiddenColumns = [];
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
    if (Array.isArray(saved)) hiddenColumns = saved;
  } catch (_) {}
  const applyColumns = () => {
    controls.forEach(control => {
      const column = Number(control.dataset.directoryColumn);
      directory.querySelectorAll('tr').forEach(row => {
        if (row.cells.length === 15) row.cells[column].hidden = !control.checked;
        else if (row.cells.length === 1) row.cells[0].colSpan = 15 - controls.filter(item => !item.checked).length;
      });
    });
    try { localStorage.setItem(storageKey, JSON.stringify(controls.filter(control => !control.checked).map(control => Number(control.dataset.directoryColumn)))); } catch (_) {}
  };
  controls.forEach(control => {
    control.checked = !hiddenColumns.includes(Number(control.dataset.directoryColumn));
    control.addEventListener('change', applyColumns);
  });
  document.getElementById('reset-directory-columns').addEventListener('click', () => {
    controls.forEach(control => { control.checked = true; });
    applyColumns();
  });
  applyColumns();
}

const cardForm = document.getElementById('bulk-card-form');
if (cardForm) {
  const boxes = [...document.querySelectorAll('[data-card-member]')];
  const scope = document.getElementById('card-scope');
  const selectPage = document.getElementById('select-page-cards');
  const storageKey = 'cbm.card-selection.v1';
  const filterKey = JSON.stringify(Object.entries(JSON.parse(cardForm.dataset.selectionKey)).filter(([, value]) => value !== '').sort());
  let selected = new Set();
  try {
    const saved = JSON.parse(sessionStorage.getItem(storageKey));
    if (saved?.filterKey === filterKey && Array.isArray(saved.ids)) selected = new Set(saved.ids.map(String));
  } catch (_) {}
  const refreshSelection = () => {
    boxes.forEach(box => { box.checked = selected.has(box.value); });
    selectPage.checked = boxes.length > 0 && boxes.every(box => box.checked);
    selectPage.indeterminate = boxes.some(box => box.checked) && !selectPage.checked;
    document.getElementById('card-selection-count').textContent = `${selected.size} members selected across pages`;
    try { sessionStorage.setItem(storageKey, JSON.stringify({filterKey, ids: [...selected]})); } catch (_) {}
  };
  boxes.forEach(box => box.addEventListener('change', () => {
    if (box.checked) selected.add(box.value); else selected.delete(box.value);
    refreshSelection();
  }));
  selectPage.addEventListener('change', () => {
    boxes.forEach(box => { if (selectPage.checked) selected.add(box.value); else selected.delete(box.value); });
    refreshSelection();
  });
  document.getElementById('clear-card-selection').addEventListener('click', () => { selected.clear(); refreshSelection(); });
  cardForm.addEventListener('submit', event => {
    const message = document.getElementById('card-download-message');
    if (scope.value === 'selected' && selected.size === 0) {
      event.preventDefault(); message.textContent = 'Select at least one member or choose all matching members.'; return;
    }
    if (scope.value === 'selected' && selected.size > 500) {
      event.preventDefault(); message.textContent = 'Select up to 500 members per download, or use all matching members for a larger ZIP.'; return;
    }
    const hidden = document.getElementById('offpage-card-selection'); hidden.replaceChildren();
    if (scope.value === 'selected') {
      const visible = new Set(boxes.map(box => box.value));
      selected.forEach(id => {
        if (!visible.has(id)) { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; hidden.append(input); }
      });
    }
    message.textContent = 'Preparing your cards. The browser will download the file when it is ready. Avoid submitting again while it is being prepared.';
  });
  refreshSelection();
}
