import { renderGantt } from './gantt.js';
import { renderPert } from './pert.js';

function readJson(nodeId) {
    const node = document.getElementById(nodeId);

    if (!node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent);
    } catch (error) {
        console.error('Data visualisasi tidak valid', error);

        return null;
    }
}

function initFlashDismiss() {
    document.querySelectorAll('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('[data-flash]');

            if (flash) {
                flash.remove();
            }
        });
    });
}

function initTaskToggles() {
    const rows = [...document.querySelectorAll('[data-task-row]')];

    if (rows.length === 0) {
        return;
    }

    const rowById = new Map(rows.map((row) => [row.dataset.taskId, row]));
    const collapsed = new Set();

    const apply = () => {
        rows.forEach((row) => {
            let parentId = row.dataset.parentId;
            let visible = true;
            let guard = 0;

            while (parentId && guard++ < 100) {
                if (collapsed.has(parentId)) {
                    visible = false;
                    break;
                }

                const parent = rowById.get(parentId);
                parentId = parent ? parent.dataset.parentId : '';
            }

            row.classList.toggle('hidden', !visible);
        });
    };

    document.querySelectorAll('.js-task-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.taskId;

            if (collapsed.has(id)) {
                collapsed.delete(id);
                button.textContent = '▾';
                button.setAttribute('aria-expanded', 'true');
            } else {
                collapsed.add(id);
                button.textContent = '▸';
                button.setAttribute('aria-expanded', 'false');
            }

            apply();
        });
    });
}

function initGantt() {
    const container = document.getElementById('gantt-chart');
    const data = readJson('gantt-data');

    if (!container || !data) {
        return;
    }

    let zoom = 'minggu';

    const paint = () => {
        renderGantt(container, data, zoom);

        document.querySelectorAll('[data-zoom]').forEach((button) => {
            const active = button.dataset.zoom === zoom;
            button.classList.toggle('bg-indigo-600', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-slate-600', !active);
        });
    };

    document.querySelectorAll('[data-zoom]').forEach((button) => {
        button.addEventListener('click', () => {
            zoom = button.dataset.zoom;
            paint();
        });
    });

    paint();
}

function initPert() {
    const container = document.getElementById('pert-chart');
    const data = readJson('pert-data');

    if (!container || !data) {
        return;
    }

    renderPert(container, data);
}

document.addEventListener('DOMContentLoaded', () => {
    initFlashDismiss();
    initTaskToggles();
    initGantt();
    initPert();
});
