const defaultStagePresentations = () => ({
    analysis: {
        label: 'Análise',
        badge_classes: 'bg-sky-100 text-sky-800',
    },
    'implementation:backend': {
        label: 'Implementação Backend',
        badge_classes: 'bg-emerald-100 text-emerald-800',
    },
    'implementation:frontend': {
        label: 'Implementação Frontend',
        badge_classes: 'bg-amber-100 text-amber-800',
    },
    'implementation:infra': {
        label: 'Implementação Infra',
        badge_classes: 'bg-slate-200 text-slate-800',
    },
});

const escapeHtml = (value) => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll('\'', '&#039;');

const routeFor = (template, taskId) => {
    if (typeof template !== 'string') {
        return '#';
    }

    return template.replace('__TASK__', encodeURIComponent(String(taskId)));
};

export const renderIndexRowMarkup = (eventPayload, runtimeConfig = {}) => {
    const task = eventPayload.task || {};
    const presentation = eventPayload.presentation || {};
    const stagePresentations = runtimeConfig.stagePresentations || defaultStagePresentations();
    const status = presentation.status || {
        label: task.status || '—',
        badge_classes: 'bg-slate-100 text-slate-700',
    };
    const stage = stagePresentations[task.current_stage] || {
        label: task.current_stage || '—',
        badge_classes: 'bg-slate-100 text-slate-700',
    };
    const reviewStatus = presentation.review_status || null;
    const showUrl = routeFor(runtimeConfig.routes?.show, task.id);
    const editUrl = routeFor(runtimeConfig.routes?.edit, task.id);
    const creatorName = presentation.creator_name || task.creator?.name || '—';
    const workerMarkup = presentation.worker
        ? `<span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">${escapeHtml(presentation.worker)}</span>`
        : '<span class="text-xs text-slate-400">—</span>';
    const reviewMarkup = reviewStatus
        ? `<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${escapeHtml(reviewStatus.badge_classes)}">${escapeHtml(reviewStatus.label)}</span>`
        : '—';
    const lastReviewMarkup = presentation.last_reviewed_at
        ? `<span class="text-xs">${escapeHtml(presentation.last_reviewed_at)}</span>${presentation.last_reviewer_name ? `<span class="block text-xs text-slate-400">${escapeHtml(presentation.last_reviewer_name)}</span>` : ''}`
        : '<span class="text-xs text-slate-400">—</span>';

    return `
        <tr data-task-row data-task-id="${escapeHtml(String(task.id || ''))}">
            <td class="px-4 py-3 text-sm font-medium text-slate-950">${escapeHtml(task.title || '—')}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(task.project?.name || '—')}</td>
            <td class="px-4 py-3 text-sm text-slate-600">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${escapeHtml(status.badge_classes)}">
                    ${escapeHtml(status.label)}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${escapeHtml(stage.badge_classes)}">
                    ${escapeHtml(stage.label)}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                    ${escapeHtml(task.implementation_type || '—')}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">${reviewMarkup}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(String(task.revision_count ?? 0))}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${lastReviewMarkup}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(task.priority || '—')}</td>
            <td class="px-4 py-3 text-sm text-slate-600">${workerMarkup}</td>
            <td class="px-4 py-3 text-sm text-slate-600">
                <span class="font-mono text-xs text-slate-800">${escapeHtml(presentation.attempts || `${task.attempts ?? 0} / ${task.max_attempts ?? 0}`)}</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">${escapeHtml(creatorName)}</td>
            <td class="px-4 py-3 text-right text-sm">
                <a href="${escapeHtml(showUrl)}" class="font-semibold text-sky-700 hover:underline">Ver</a>
                <span class="text-slate-300">·</span>
                <a href="${escapeHtml(editUrl)}" class="font-semibold text-sky-700 hover:underline">Editar</a>
            </td>
        </tr>
    `;
};

export const emptyRowMarkup = () => `
    <tr>
        <td colspan="13" class="px-4 py-10 text-center text-sm text-slate-500">
            Nenhuma tarefa cadastrada.
        </td>
    </tr>
`;

const configElement = typeof document === 'undefined' ? null : document.getElementById('task-stream-config');

if (!configElement) {
    // No-op outside task pages.
} else {
    const config = JSON.parse(configElement.textContent || '{}');
    const stateByTaskId = new Map();
    const subscriptions = Array.isArray(config.subscriptions) ? config.subscriptions : [];
    const indexSubscriptions = subscriptions.filter((subscription) => subscription.scope === 'index');
    const indexBody = document.querySelector('[data-task-list-body]');
    let socket = null;
    let reconnectTimeoutId = null;
    let indexResyncTimeoutId = null;

    const buildUrl = () => {
        const scheme = window.location.protocol === 'https:' ? 'wss' : 'ws';
        const url = new URL(config.path || '/ws/tasks', `${scheme}://${window.location.host}`);

        url.searchParams.set('token', config.token);

        return url.toString();
    };

    const connect = () => {
        socket = new WebSocket(buildUrl());

        socket.addEventListener('open', () => {
            subscribe(subscriptions);
        });

        socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);

            if (message.type === 'subscription.synced') {
                applySyncedTasks(message.tasks || []);

                return;
            }

            if (typeof message.type === 'string' && message.type.startsWith('task.')) {
                applyTaskEvent(message);
            }
        });

        socket.addEventListener('close', () => {
            if (reconnectTimeoutId !== null) {
                window.clearTimeout(reconnectTimeoutId);
            }

            reconnectTimeoutId = window.setTimeout(connect, 1500);
        });
    };

    const subscribe = (requestedSubscriptions) => {
        if (!socket || socket.readyState !== WebSocket.OPEN || !Array.isArray(requestedSubscriptions) || requestedSubscriptions.length === 0) {
            return;
        }

        socket.send(JSON.stringify({
            type: 'subscribe',
            subscriptions: requestedSubscriptions,
        }));
    };

    const applySyncedTasks = (tasks) => {
        const snapshots = Array.isArray(tasks) ? tasks : [];

        snapshots.forEach((snapshot) => {
            const taskId = Number(snapshot.task_id);
            const occurredAt = Date.parse(snapshot.occurred_at || '');

            if (Number.isFinite(taskId) && Number.isFinite(occurredAt)) {
                stateByTaskId.set(taskId, occurredAt);
            }
        });

        if (indexBody) {
            renderIndexRows(snapshots.filter((snapshot) => snapshot?.task));
        }

        snapshots.forEach((snapshot) => {
            updateShowPage(Number(snapshot.task_id), snapshot);
        });
    };

    const applyTaskEvent = (eventPayload) => {
        const taskId = Number(eventPayload.task_id);
        const changedAt = Date.parse(eventPayload.occurred_at || '');
        const lastChangedAt = stateByTaskId.get(taskId);

        if (Number.isFinite(lastChangedAt) && Number.isFinite(changedAt) && changedAt < lastChangedAt) {
            return;
        }

        if (Number.isFinite(changedAt)) {
            stateByTaskId.set(taskId, changedAt);
        }

        updateShowPage(taskId, eventPayload);
        scheduleIndexResync();
    };

    const scheduleIndexResync = () => {
        if (!indexBody || indexSubscriptions.length === 0) {
            return;
        }

        if (indexResyncTimeoutId !== null) {
            window.clearTimeout(indexResyncTimeoutId);
        }

        indexResyncTimeoutId = window.setTimeout(() => {
            subscribe(indexSubscriptions);
        }, 120);
    };

    const updateShowPage = (taskId, eventPayload) => {
        const container = document.querySelector(`[data-task-show][data-task-id="${taskId}"]`);

        if (!container) {
            return;
        }

        if (eventPayload.type === 'task.deleted') {
            if (config.routes?.index) {
                window.location.assign(config.routes.index);
            }

            return;
        }

        const presentation = eventPayload.presentation || {};
        const task = eventPayload.task || {};
        const reviewStatus = presentation.review_status || null;
        const lastReviewText = presentation.last_reviewed_at
            ? `${presentation.last_reviewed_at}${presentation.last_reviewer_name ? ` · ${presentation.last_reviewer_name}` : ''}`
            : '—';

        setText(container, 'status-label', presentation.status?.label || task.status || '—');
        setClass(container, 'status-badge', presentation.status?.badge_classes || 'bg-slate-100 text-slate-700');
        setText(container, 'review-status-label', reviewStatus?.label || '—');
        setClass(container, 'review-status-badge', reviewStatus?.badge_classes || 'bg-slate-100 text-slate-700');
        setText(container, 'revision-count', String(task.revision_count ?? 0));
        setText(container, 'priority', presentation.priority || task.priority || '—');
        setText(container, 'last-review', lastReviewText);
        setText(container, 'worker', presentation.worker || '—');
        setText(container, 'description', task.description || '—');
        setOptionalText(container, 'deliverables', task.deliverables);
        setOptionalText(container, 'constraints', task.constraints);
        document.title = task.title || document.title;
    };

    const renderIndexRows = (snapshots) => {
        if (!indexBody) {
            return;
        }

        if (snapshots.length === 0) {
            indexBody.innerHTML = emptyRowMarkup();

            return;
        }

        indexBody.innerHTML = snapshots.map((snapshot) => renderIndexRowMarkup(snapshot, config)).join('');
    };

    const setText = (container, key, value) => {
        const element = container.querySelector(`[data-task-field="${key}"]`);

        if (element) {
            element.textContent = value;
        }
    };

    const setClass = (container, key, classes) => {
        const element = container.querySelector(`[data-task-field="${key}"]`);

        if (!element) {
            return;
        }

        element.className = `${element.dataset.baseClass} ${classes}`.trim();
    };

    const setOptionalText = (container, key, value) => {
        const wrapper = container.querySelector(`[data-task-optional="${key}"]`);
        const element = container.querySelector(`[data-task-field="${key}"]`);

        if (!wrapper || !element) {
            return;
        }

        if (value) {
            wrapper.classList.remove('hidden');
            element.textContent = value;

            return;
        }

        wrapper.classList.add('hidden');
        element.textContent = '';
    };

    connect();
}
