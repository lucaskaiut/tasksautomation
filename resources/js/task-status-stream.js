const configElement = document.getElementById('task-stream-config');

if (!configElement) {
    // No-op outside task pages.
} else {
    const config = JSON.parse(configElement.textContent || '{}');
    const stateByTaskId = new Map();
    let socket = null;
    let reconnectTimeoutId = null;

    const buildUrl = () => {
        const scheme = window.location.protocol === 'https:' ? 'wss' : 'ws';
        const url = new URL(config.path || '/ws/tasks', `${scheme}://${window.location.host}`);

        url.searchParams.set('token', config.token);

        return url.toString();
    };

    const connect = () => {
        socket = new WebSocket(buildUrl());

        socket.addEventListener('open', () => {
            socket.send(JSON.stringify({
                type: 'subscribe',
                subscriptions: config.subscriptions || [],
            }));
        });

        socket.addEventListener('message', (event) => {
            const message = JSON.parse(event.data);

            if (message.type === 'subscription.synced') {
                (message.tasks || []).forEach(applyTaskEvent);

                return;
            }

            if (message.type === 'task.status.changed') {
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

    const applyTaskEvent = (eventPayload) => {
        const taskId = Number(eventPayload.task_id);
        const changedAt = Date.parse(eventPayload.changed_at || '');
        const lastChangedAt = stateByTaskId.get(taskId);

        if (Number.isFinite(lastChangedAt) && Number.isFinite(changedAt) && changedAt < lastChangedAt) {
            return;
        }

        if (Number.isFinite(changedAt)) {
            stateByTaskId.set(taskId, changedAt);
        }

        updateIndexRow(taskId, eventPayload);
        updateShowPage(taskId, eventPayload);
    };

    const updateIndexRow = (taskId, eventPayload) => {
        const row = document.querySelector(`[data-task-row][data-task-id="${taskId}"]`);

        if (!row) {
            return;
        }

        const presentation = eventPayload.presentation || {};
        const task = eventPayload.task || {};
        const lastReviewText = presentation.last_reviewed_at
            ? `${presentation.last_reviewed_at}${presentation.last_reviewer_name ? ` · ${presentation.last_reviewer_name}` : ''}`
            : '—';

        setText(row, 'status-label', presentation.status?.label || task.status || '—');
        setClass(row, 'status-badge', presentation.status?.badge_classes || 'bg-slate-100 text-slate-700');
        setText(row, 'review-status', presentation.review_status || '—');
        setText(row, 'worker', presentation.worker || '—');
        setText(row, 'attempts', presentation.attempts || `${task.attempts ?? 0} / ${task.max_attempts ?? 0}`);
        setText(row, 'last-review', lastReviewText);
    };

    const updateShowPage = (taskId, eventPayload) => {
        const container = document.querySelector(`[data-task-show][data-task-id="${taskId}"]`);

        if (!container) {
            return;
        }

        const presentation = eventPayload.presentation || {};
        const task = eventPayload.task || {};
        const lastReviewText = presentation.last_reviewed_at
            ? `${presentation.last_reviewed_at}${presentation.last_reviewer_name ? ` · ${presentation.last_reviewer_name}` : ''}`
            : '—';

        setText(container, 'status-label', presentation.status?.label || task.status || '—');
        setClass(container, 'status-badge', presentation.status?.badge_classes || 'bg-slate-100 text-slate-700');
        setText(container, 'review-status', presentation.review_status || '—');
        setText(container, 'revision-count', String(task.revision_count ?? 0));
        setText(container, 'priority', presentation.priority || task.priority || '—');
        setText(container, 'last-review', lastReviewText);
        setText(container, 'worker', presentation.worker || '—');
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

    connect();
}
