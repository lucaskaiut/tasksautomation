import test from 'node:test';
import assert from 'node:assert/strict';

import { emptyRowMarkup, renderIndexRowMarkup } from '../../resources/js/task-status-stream.js';

test('renderIndexRowMarkup keeps the task list column order used by the blade table', () => {
    const markup = renderIndexRowMarkup({
        task: {
            id: 42,
            title: 'Corrigir websocket',
            project: { name: 'TasksAutomation' },
            implementation_type: 'fix',
            current_stage: 'implementation:infra',
            revision_count: 3,
            priority: 'high',
            attempts: 1,
            max_attempts: 5,
            creator: { name: 'Kaiut' },
        },
        presentation: {
            status: {
                label: 'Bloqueada',
                badge_classes: 'bg-red-100 text-red-800',
            },
            review_status: {
                label: 'Precisa de ajustes',
                badge_classes: 'bg-orange-100 text-orange-800',
            },
            last_reviewed_at: '19/04/2026 10:30',
            last_reviewer_name: 'QA',
            worker: 'worker-01',
            attempts: '1 / 5',
            creator_name: 'Kaiut',
        },
    }, {
        routes: {
            show: '/tasks/__TASK__',
            edit: '/tasks/__TASK__/edit',
        },
    });

    assert.equal(markup.match(/<td\b/g)?.length, 13);
    assert.notEqual(markup.indexOf('Implementação Infra'), -1);
    assert.ok(markup.indexOf('Bloqueada') < markup.indexOf('Implementação Infra'));
    assert.ok(markup.indexOf('Implementação Infra') < markup.indexOf('fix'));
    assert.ok(markup.indexOf('Precisa de ajustes') < markup.indexOf('19/04/2026 10:30'));
    assert.match(markup, /href="\/tasks\/42"/);
    assert.match(markup, /href="\/tasks\/42\/edit"/);
});

test('emptyRowMarkup spans all task list columns', () => {
    assert.match(emptyRowMarkup(), /colspan="13"/);
});
