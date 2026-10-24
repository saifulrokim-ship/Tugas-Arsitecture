const NODE_W = 210;
const NODE_H = 116;
const GAP_X = 70;
const GAP_Y = 24;
const PAD = 24;

const PRIORITY_ACCENT = {
    low: '#6366f1',
    medium: '#0ea5e9',
    high: '#f97316',
};

function svgElement(tag, attributes = {}) {
    const node = document.createElementNS('http://www.w3.org/2000/svg', tag);

    Object.entries(attributes).forEach(([key, value]) => node.setAttribute(key, value));

    return node;
}

function text(content, attributes = {}) {
    const node = svgElement('text', {
        'font-size': '11',
        'font-family': 'inherit',
        fill: '#334155',
        ...attributes,
    });

    node.textContent = content;

    return node;
}

/**
 * Menentukan kolom (layer) tiap node dengan topological sort berbasis dependensi,
 * sehingga urutan tugas dan panah ketergantungan terlihat jelas.
 */
function layout(data, visibleIds) {
    const predecessors = new Map();
    const indegree = new Map();
    const children = new Map();

    visibleIds.forEach((id) => {
        predecessors.set(id, []);
        indegree.set(id, 0);
        children.set(id, []);
    });

    data.edges.forEach((edge) => {
        if (!visibleIds.has(edge.predecessor_id) || !visibleIds.has(edge.successor_id)) {
            return;
        }

        predecessors.get(edge.successor_id).push(edge);
        children.get(edge.predecessor_id).push(edge);
        indegree.set(edge.successor_id, indegree.get(edge.successor_id) + 1);
    });

    const layer = new Map();
    const queue = [];

    visibleIds.forEach((id) => {
        if (indegree.get(id) === 0) {
            layer.set(id, 0);
            queue.push(id);
        }
    });

    const visited = new Set();

    while (queue.length > 0) {
        const current = queue.shift();

        if (visited.has(current)) {
            continue;
        }

        visited.add(current);

        children.get(current).forEach((edge) => {
            const candidate = (layer.get(current) ?? 0) + 1;
            layer.set(edge.successor_id, Math.max(layer.get(edge.successor_id) ?? 0, candidate));
            indegree.set(edge.successor_id, indegree.get(edge.successor_id) - 1);

            if (indegree.get(edge.successor_id) === 0) {
                queue.push(edge.successor_id);
            }
        });
    }

    // Node yang tidak tersentuh (mis. terpisah dari graf) tetap masuk kolom pertama.
    visibleIds.forEach((id) => {
        if (!layer.has(id)) {
            layer.set(id, 0);
        }
    });

    const columns = new Map();

    data.tasks.forEach((task) => {
        if (!visibleIds.has(task.id)) {
            return;
        }

        const column = layer.get(task.id) ?? 0;

        if (!columns.has(column)) {
            columns.set(column, []);
        }

        columns.get(column).push(task);
    });

    const position = new Map();

    [...columns.keys()].sort((a, b) => a - b).forEach((column) => {
        columns.get(column).forEach((task, index) => {
            position.set(task.id, {
                x: PAD + column * (NODE_W + GAP_X),
                y: PAD + index * (NODE_H + GAP_Y),
            });
        });
    });

    return { position, columns };
}

export function renderPert(container, data) {
    container.innerHTML = '';

    const summaryIds = new Set(data.tasks.filter((task) => task.is_summary).map((task) => task.id));
    const visibleIds = new Set(data.tasks.filter((task) => !summaryIds.has(task.id)).map((task) => task.id));
    const { position } = layout(data, visibleIds);
    const tasks = new Map(data.tasks.map((task) => [task.id, task]));

    const positions = [...position.values()];
    const width = Math.max(...positions.map((p) => p.x), 0) + NODE_W + PAD * 2;
    const height = Math.max(...positions.map((p) => p.y), 0) + NODE_H + PAD * 2;

    const svg = svgElement('svg', {
        width,
        height,
        viewBox: `0 0 ${width} ${height}`,
        class: 'block',
    });

    const wrapper = document.createElement('div');
    wrapper.className = 'overflow-auto rounded-b-xl bg-white p-4';
    wrapper.style.maxHeight = '72vh';
    wrapper.appendChild(svg);
    container.appendChild(wrapper);

    const defs = svgElement('defs');
    defs.innerHTML = `
        <marker id="pert-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#475569"></path>
        </marker>
        <marker id="pert-arrow-critical" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#e11d48"></path>
        </marker>
    `;
    svg.appendChild(defs);

    // Panah digambar lebih dulu agar berada di belakang node.
    data.edges.forEach((edge) => {
        if (!position.has(edge.predecessor_id) || !position.has(edge.successor_id)) {
            return;
        }

        const from = position.get(edge.predecessor_id);
        const to = position.get(edge.successor_id);
        const x1 = from.x + NODE_W;
        const y1 = from.y + NODE_H / 2;
        const x2 = to.x;
        const y2 = to.y + NODE_H / 2;
        const midX = x1 + (x2 - x1) / 2;

        const path = svgElement('path', {
            d: `M ${x1} ${y1} H ${midX} V ${y2} H ${x2 - 6}`,
            fill: 'none',
            stroke: edge.critical ? '#e11d48' : '#475569',
            'stroke-width': edge.critical ? '2.25' : '1.25',
            'marker-end': edge.critical ? 'url(#pert-arrow-critical)' : 'url(#pert-arrow)',
        });

        svg.appendChild(path);

        const label = text(`${edge.type}${edge.lag > 0 ? `+${edge.lag}` : ''}`, {
            x: midX + 4,
            y: (y1 + y2) / 2 - 4,
            'font-size': '9',
            fill: edge.critical ? '#e11d48' : '#94a3b8',
        });

        svg.appendChild(label);
    });

    visibleIds.forEach((id) => {
        const task = tasks.get(id);
        const at = position.get(id);

        if (!task || !at) {
            return;
        }

        const group = svgElement('g');
        const stroke = task.critical ? '#e11d48' : '#e2e8f0';
        const headerFill = task.critical ? '#fff1f2' : '#f8fafc';

        group.appendChild(svgElement('rect', {
            x: at.x,
            y: at.y,
            width: NODE_W,
            height: NODE_H,
            rx: 8,
            fill: '#ffffff',
            stroke,
            'stroke-width': task.critical ? 2.5 : 1.5,
        }));

        group.appendChild(svgElement('path', {
            d: `M ${at.x} ${at.y + 8} a 8 8 0 0 1 8 -8 h ${NODE_W - 16} a 8 8 0 0 1 8 8 v 26 h ${-NODE_W} z`,
            fill: headerFill,
        }));

        group.appendChild(svgElement('rect', {
            x: at.x,
            y: at.y,
            width: 4,
            height: NODE_H,
            rx: 2,
            fill: PRIORITY_ACCENT[task.priority] ?? '#6366f1',
        }));

        const title = text(task.name, {
            x: at.x + 12,
            y: at.y + 18,
            'font-size': '11.5',
            'font-weight': '600',
            fill: '#0f172a',
        });
        title.textContent = task.name.length > 26 ? `${task.name.slice(0, 25)}…` : task.name;
        const titleNode = svgElement('title');
        titleNode.textContent = task.name;
        title.appendChild(titleNode);
        group.appendChild(title);

        group.appendChild(text(`Durasi ${task.duration} hari`, {
            x: at.x + 12,
            y: at.y + 31,
            'font-size': '9.5',
            fill: '#64748b',
        }));

        const cells = [
            ['ES', task.start.slice(5).replace('-', '/')],
            ['EF', task.finish.slice(5).replace('-', '/')],
            ['LS', task.latest_start.slice(5).replace('-', '/')],
            ['LF', task.latest_finish.slice(5).replace('-', '/')],
        ];

        cells.forEach(([label, value], index) => {
            const column = index % 2;
            const row = Math.floor(index / 2);
            const x = at.x + 12 + column * 96;
            const y = at.y + 54 + row * 24;

            group.appendChild(text(label, { x, y, 'font-size': '9', fill: '#94a3b8' }));
            group.appendChild(text(value, { x: x + 20, y, 'font-size': '10.5', 'font-weight': '600', fill: '#334155' }));
        });

        group.appendChild(text(`Slack ${task.slack} hari`, {
            x: at.x + 12,
            y: at.y + 104,
            'font-size': '10',
            'font-weight': '600',
            fill: task.critical ? '#e11d48' : '#475569',
        }));

        if (task.critical) {
            group.appendChild(text('KRITIS', {
                x: at.x + NODE_W - 54,
                y: at.y + 104,
                'font-size': '9.5',
                'font-weight': '700',
                fill: '#e11d48',
            }));
        }

        svg.appendChild(group);
    });

    if (visibleIds.size === 0) {
        container.appendChild(Object.assign(document.createElement('div'), {
            className: 'p-8 text-center text-sm text-slate-500',
            textContent: 'Belum ada tugas untuk ditampilkan pada PERT Chart.',
        }));
    }
}
