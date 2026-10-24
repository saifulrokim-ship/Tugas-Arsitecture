const ZOOMS = {
    hari: { colWidth: 30, showDayNumber: true, showWeekday: true },
    minggu: { colWidth: 17, showDayNumber: true, showWeekday: false },
    bulan: { colWidth: 8, showDayNumber: false, showWeekday: false },
};

const LEFT_W = 340;
const ROW_H = 34;
const MONTH_H = 22;
const DAY_H = 26;
const HEADER_H = MONTH_H + DAY_H;

const FALLBACK_LABELS = {
    task_name: 'Task name',
    empty: 'No tasks to display.',
    critical: 'CRITICAL',
    critical_short: 'CRIT',
    milestone: 'Milestone',
    percent: 'Complete',
    schedule: 'Schedule: :from to :to',
    duration: 'Duration: :count workdays',
    slack: 'Slack: :count days',
    assignees: 'Assignees',
    unassigned: 'Not assigned',
    conflict: 'Leave conflict',
    months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    weekdays: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
};

const PRIORITY_COLORS = {
    low: '#6366f1',
    medium: '#0ea5e9',
    high: '#f97316',
    critical: '#d946ef',
};

function labels(data) {
    return { ...FALLBACK_LABELS, ...(data.labels ?? {}) };
}

function fill(template, params) {
    return Object.entries(params).reduce(
        (text, [key, value]) => text.replace(`:${key}`, String(value)),
        template,
    );
}

function element(tag, className, style, text) {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    if (style) {
        Object.assign(node.style, style);
    }

    if (text !== undefined && text !== null) {
        node.textContent = text;
    }

    return node;
}

function weekdayIndex(dateString) {
    const date = new Date(`${dateString}T00:00:00`);

    return (date.getDay() + 6) % 7;
}

function depthMap(tasks) {
    const depth = {};

    tasks.forEach((task) => {
        depth[task.id] = task.parent_id && depth[task.parent_id] !== undefined
            ? depth[task.parent_id] + 1
            : 0;
    });

    return depth;
}

function assigneeLabel(task, i18n) {
    if (!task.assignees || task.assignees.length === 0) {
        return i18n.unassigned;
    }

    return task.assignees.map((assignee) => assignee.name).join(', ');
}

function barTooltip(task, i18n) {
    const lines = [
        task.name,
        fill(i18n.schedule, { from: task.start, to: task.finish }),
        fill(i18n.duration, { count: task.duration }),
        `LS: ${task.latest_start} · LF: ${task.latest_finish}`,
        `${fill(i18n.slack, { count: task.slack })}${task.critical ? ` (${i18n.critical})` : ''}`,
        `${i18n.assignees}: ${assigneeLabel(task, i18n)}`,
        `${i18n.percent}: ${task.percent_complete}%`,
    ];

    if (task.is_milestone) {
        lines.push(i18n.milestone);
    }

    if (task.leave_conflict) {
        lines.push(`⏰ ${i18n.conflict}`);
    }

    return lines.join('\n');
}

function arrowPath(x1, y1, x2, y2) {
    const stub = 10;

    if (x2 >= x1 + stub + 8) {
        return `M ${x1} ${y1} H ${x1 + stub} V ${y2} H ${x2 - 5}`;
    }

    const midY = (y1 + y2) / 2;
    const outX = x1 + stub;
    const inX = Math.max(4, x2 - stub - 8);

    return `M ${x1} ${y1} H ${outX} V ${midY} H ${inX} V ${y2} H ${x2 - 5}`;
}

export function renderGantt(container, data, zoomKey) {
    const i18n = labels(data);
    const zoom = ZOOMS[zoomKey] ?? ZOOMS.minggu;
    const colWidth = zoom.colWidth;
    const days = data.days ?? [];
    const dayIndex = new Map(days.map((day, index) => [day.date, index]));
    const timelineWidth = Math.max(days.length * colWidth, 1);
    const rows = tasksOrder(data);
    const rowsHeight = rows.length * ROW_H;

    container.innerHTML = '';
    container.className = 'gantt-scroll relative overflow-auto rounded-b-xl bg-white';
    container.style.maxHeight = '72vh';

    const inner = element('div', 'gantt-inner relative');
    inner.style.width = `${LEFT_W + timelineWidth}px`;
    container.appendChild(inner);

    // Latar hari non-kerja di area timeline.
    const gridLayer = element('div', 'absolute z-0');
    Object.assign(gridLayer.style, {
        left: `${LEFT_W}px`,
        top: `${HEADER_H}px`,
        width: `${timelineWidth}px`,
        height: `${rowsHeight}px`,
    });

    days.forEach((day, index) => {
        if (day.working) {
            return;
        }

        gridLayer.appendChild(element('div', 'absolute', {
            left: `${index * colWidth}px`,
            top: 0,
            width: `${colWidth}px`,
            height: `${rowsHeight}px`,
            background: 'rgba(148,163,184,0.22)',
        }));
    });

    inner.appendChild(gridLayer);

    // Header timeline: baris bulan + baris hari.
    const header = element('div', 'sticky top-0 z-30 flex bg-white');
    header.style.height = `${HEADER_H}px`;
    header.style.boxShadow = '0 1px 0 rgba(15,23,42,0.08)';

    const headerTitle = element('div', 'sticky left-0 z-40 flex items-center border-r border-slate-200 bg-white px-4 text-xs font-semibold uppercase tracking-wide text-slate-500');
    headerTitle.style.width = `${LEFT_W}px`;
    headerTitle.style.height = `${HEADER_H}px`;
    headerTitle.textContent = i18n.task_name;
    header.appendChild(headerTitle);

    const headerTimeline = element('div', 'relative');
    headerTimeline.style.width = `${timelineWidth}px`;

    const monthRow = element('div', 'flex border-b border-slate-100');
    monthRow.style.height = `${MONTH_H}px`;

    let monthStart = 0;

    for (let index = 1; index <= days.length; index++) {
        const isLast = index === days.length;
        const currentMonth = days[index]?.date?.slice(0, 7);
        const previousMonth = days[index - 1]?.date?.slice(0, 7);

        if (isLast || currentMonth !== previousMonth) {
            const monthNumber = Number(days[monthStart].date.slice(5, 7)) - 1;
            const label = `${i18n.months[monthNumber]} ${days[monthStart].date.slice(0, 4)}`;
            const cell = element('div', 'flex items-center overflow-hidden whitespace-nowrap border-r border-slate-200 px-2 text-[11px] font-semibold text-slate-600', {
                width: `${(index - monthStart) * colWidth}px`,
            }, (index - monthStart) * colWidth > 44 ? label : '');
            monthRow.appendChild(cell);
            monthStart = index;
        }
    }

    headerTimeline.appendChild(monthRow);

    const dayRow = element('div', 'flex');
    dayRow.style.height = `${DAY_H}px`;

    days.forEach((day) => {
        const cell = element('div', 'flex flex-col items-center justify-center border-r border-slate-100 text-[10px] leading-none', {
            width: `${colWidth}px`,
            height: `${DAY_H}px`,
            background: day.working ? '#ffffff' : 'rgba(148,163,184,0.28)',
            color: day.working ? '#475569' : '#94a3b8',
        });

        if (zoom.showWeekday) {
            cell.appendChild(element('span', 'font-semibold', null, i18n.weekdays[weekdayIndex(day.date)]));
        }

        if (zoom.showDayNumber) {
            cell.appendChild(element('span', 'mt-0.5', null, day.date.slice(8, 10)));
        }

        dayRow.appendChild(cell);
    });

    headerTimeline.appendChild(dayRow);
    header.appendChild(headerTimeline);
    inner.appendChild(header);

    // Baris tugas.
    const rowsLayer = element('div', 'relative z-10');
    inner.appendChild(rowsLayer);

    const positionOf = new Map();

    rows.forEach((task, rowIndex) => {
        const row = element('div', 'flex border-b border-slate-100');
        row.style.height = `${ROW_H}px`;

        const left = element('div', 'sticky left-0 z-20 flex items-center gap-2 border-r border-slate-200 bg-white px-3', {
            width: `${LEFT_W}px`,
            paddingLeft: `${12 + task.depth * 16}px`,
        });

        if (task.is_milestone) {
            // Milestone: belah ketupat ◆ (TSK-5).
            const diamond = element('span', 'shrink-0', {
                width: '9px',
                height: '9px',
                background: '#4f46e5',
                transform: 'rotate(45deg)',
                borderRadius: '1px',
            });
            diamond.title = i18n.milestone;
            left.appendChild(diamond);
        } else {
            const marker = element('span', 'h-2 w-2 shrink-0 rounded-full', {
                background: task.critical ? '#e11d48' : (task.is_summary ? '#334155' : PRIORITY_COLORS[task.priority] ?? '#6366f1'),
            });
            left.appendChild(marker);
        }

        const label = element('span', 'truncate text-xs text-slate-800', null, task.name);
        left.appendChild(label);

        if (task.critical) {
            // Jalur kritis tidak hanya dibedakan warna: ada penanda ⚠ (CPM-2).
            const badge = element('span', 'shrink-0 rounded bg-rose-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-rose-700', null, `⚠ ${i18n.critical_short}`);
            badge.title = i18n.critical;
            left.appendChild(badge);
        }

        if (task.leave_conflict) {
            const conflict = element('span', 'shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-700', null, '⏰');
            conflict.title = i18n.conflict;
            left.appendChild(conflict);
        }

        if (task.percent_complete > 0 && task.percent_complete < 100) {
            left.appendChild(element('span', 'shrink-0 text-[10px] font-semibold text-slate-400', null, `${task.percent_complete}%`));
        }

        if (task.assignees && task.assignees.length) {
            left.appendChild(element('span', 'shrink-0 truncate text-[10px] text-slate-400', null, `· ${task.assignees.map((a) => a.name).join(', ')}`));
        }

        row.appendChild(left);

        const timelineCell = element('div', 'relative');
        timelineCell.style.width = `${timelineWidth}px`;

        const startIndex = dayIndex.get(task.start) ?? 0;
        const finishIndex = dayIndex.get(task.finish) ?? startIndex;
        const barLeft = startIndex * colWidth;
        const barWidth = Math.max((finishIndex - startIndex + 1) * colWidth, 6);

        positionOf.set(task.id, { left: barLeft, right: barLeft + barWidth, y: HEADER_H + rowIndex * ROW_H + ROW_H / 2 });

        if (task.is_milestone) {
            // Milestone digambar sebagai belah ketupat pada titik mulainya (GNT-4).
            const size = 13;
            const diamond = element('div', 'absolute', {
                left: `${barLeft + Math.max(colWidth, 6) / 2 - size / 2}px`,
                top: `${ROW_H / 2 - size / 2}px`,
                width: `${size}px`,
                height: `${size}px`,
                background: task.critical ? '#e11d48' : '#4f46e5',
                transform: 'rotate(45deg)',
                borderRadius: '2px',
                border: '1px solid #ffffff',
                boxShadow: '0 1px 2px rgba(15,23,42,0.3)',
            });
            diamond.title = barTooltip(task, i18n);
            timelineCell.appendChild(diamond);
        } else {
            const bar = element('div', 'absolute flex items-center overflow-hidden rounded-sm shadow-sm', {
                left: `${barLeft}px`,
                width: `${barWidth}px`,
                top: '7px',
                height: '20px',
                background: task.critical ? '#e11d48' : (task.is_summary ? '#1e293b' : (PRIORITY_COLORS[task.priority] ?? '#6366f1')),
                opacity: task.is_summary ? '0.85' : '1',
            });
            bar.title = barTooltip(task, i18n);

            if (task.is_summary) {
                bar.style.border = '1px solid #0f172a';
            }

            // Overlay percent complete pada bar (TSK-6).
            const percent = Math.max(0, Math.min(100, task.percent_complete ?? 0));

            if (percent > 0) {
                const overlay = element('div', 'absolute inset-y-0 left-0', {
                    width: `${percent}%`,
                    background: task.critical ? 'rgba(255,255,255,0.35)' : 'rgba(15,23,42,0.30)',
                    borderRight: percent < 100 ? '1px dashed rgba(255,255,255,0.85)' : 'none',
                });
                bar.appendChild(overlay);
            }

            if (task.leave_conflict) {
                const conflictMark = element('span', 'absolute right-0.5 top-0 text-[10px] leading-none', null, '⏰');
                bar.appendChild(conflictMark);
            }

            timelineCell.appendChild(bar);
        }

        const barLabel = element('span', 'absolute whitespace-nowrap text-[10px] text-slate-500', {
            left: `${barLeft + barWidth + 6}px`,
            top: '11px',
        }, task.is_milestone ? `◆ ${task.name}` : task.name);

        timelineCell.appendChild(barLabel);
        row.appendChild(timelineCell);
        rowsLayer.appendChild(row);
    });

    // Garis penanda hari ini.
    const todayIndex = dayIndex.get(data.today);

    if (todayIndex !== undefined) {
        const todayLine = element('div', 'absolute z-20', {
            left: `${LEFT_W + todayIndex * colWidth + colWidth / 2}px`,
            top: `${HEADER_H}px`,
            width: '2px',
            height: `${rowsHeight}px`,
            background: 'rgba(225,29,72,0.55)',
        });
        inner.appendChild(todayLine);
    }

    if (rows.length === 0) {
        container.appendChild(element('div', 'p-8 text-center text-sm text-slate-500', null, i18n.empty));
        return;
    }

    // Panah ketergantungan.
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', timelineWidth);
    svg.setAttribute('height', rowsHeight);
    svg.setAttribute('class', 'absolute z-20 pointer-events-none');
    svg.style.left = `${LEFT_W}px`;
    svg.style.top = `${HEADER_H}px`;
    svg.style.overflow = 'visible';

    svg.innerHTML = `
        <defs>
            <marker id="gantt-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                <path d="M 0 0 L 10 5 L 0 10 z" fill="#475569"></path>
            </marker>
            <marker id="gantt-arrow-critical" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                <path d="M 0 0 L 10 5 L 0 10 z" fill="#e11d48"></path>
            </marker>
        </defs>
    `;

    data.edges.forEach((edge) => {
        const from = positionOf.get(edge.predecessor_id);
        const to = positionOf.get(edge.successor_id);

        if (!from || !to) {
            return;
        }

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', arrowPath(from.right, from.y, to.left, to.y));
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', edge.critical ? '#e11d48' : '#475569');
        path.setAttribute('stroke-width', edge.critical ? '2' : '1.25');
        path.setAttribute('marker-end', edge.critical ? 'url(#gantt-arrow-critical)' : 'url(#gantt-arrow)');
        svg.appendChild(path);
    });

    inner.appendChild(svg);
}

function tasksOrder(data) {
    if (data._orderedRows) {
        return data._orderedRows;
    }

    const depth = depthMap(data.tasks);
    const rows = data.tasks.map((task) => ({ ...task, depth: depth[task.id] }));
    data._orderedRows = rows;

    return rows;
}
