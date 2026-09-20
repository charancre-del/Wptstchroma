const fs = require('fs');
const path = require('path');
const vm = require('vm');

function loadServiceWorker() {
    const handlers = {};
    const cache = {
        addAll: jest.fn(() => Promise.resolve()),
        put: jest.fn(() => Promise.resolve())
    };
    const caches = {
        open: jest.fn(() => Promise.resolve(cache)),
        match: jest.fn(() => Promise.resolve(undefined)),
        keys: jest.fn(() => Promise.resolve(['cqa-static-v1', 'cqa-dynamic-v1', 'unrelated-cache'])),
        delete: jest.fn(() => Promise.resolve(true))
    };
    const fetch = jest.fn(() => Promise.resolve({ ok: true, type: 'basic', clone: jest.fn(() => ({ clone: true })) }));
    const self = {
        addEventListener: jest.fn((event, handler) => { handlers[event] = handler; }),
        skipWaiting: jest.fn(() => Promise.resolve()),
        clients: { claim: jest.fn(() => Promise.resolve()), matchAll: jest.fn(() => Promise.resolve([])) },
        registration: { showNotification: jest.fn(() => Promise.resolve()) }
    };
    const Response = function Response(body, options) { return { body, ...options }; };
    const source = fs.readFileSync(path.join(__dirname, '../../service-worker.js'), 'utf8');

    vm.runInNewContext(source, {
        self,
        caches,
        fetch,
        URL,
        location: { origin: 'https://qa.invalid' },
        Response,
        FormData: function FormData() {},
        indexedDB: { open: jest.fn() },
        console
    });

    return { handlers, cache, caches, fetch };
}

function dispatchFetch(handler, url, accept = '*/*') {
    let response;
    const request = {
        method: 'GET',
        url,
        headers: { get: jest.fn(name => name === 'accept' ? accept : null) }
    };
    handler({ request, respondWith: promise => { response = promise; } });
    return { request, response };
}

describe('service worker cache boundary', () => {
    test.each([
        ['API', 'https://qa.invalid/wp-json/cqa/v1/reports/7', 'application/json'],
        ['HTML', 'https://qa.invalid/qa-reports/report/7', 'text/html'],
        ['report PDF', 'https://qa.invalid/wp-json/cqa/v1/reports/7/pdf', 'application/pdf'],
        ['photo', 'https://qa.invalid/wp-content/uploads/private-child-photo.jpg', 'image/jpeg']
    ])('%s responses are network-only and never written to Cache Storage', async (_label, url, accept) => {
        const harness = loadServiceWorker();
        const { request, response } = dispatchFetch(harness.handlers.fetch, url, accept);
        await response;

        expect(harness.fetch).toHaveBeenCalledWith(request, { cache: 'no-store' });
        expect(harness.cache.put).not.toHaveBeenCalled();
        expect(harness.caches.match).not.toHaveBeenCalled();
    });

    test('only an exact allowlisted plugin asset is cached', async () => {
        const harness = loadServiceWorker();
        const { request, response } = dispatchFetch(
            harness.handlers.fetch,
            'https://qa.invalid/wp-content/plugins/chroma-qa-reports/admin/css/admin-styles.css',
            'text/css'
        );
        await response;

        expect(harness.caches.match).toHaveBeenCalledWith(request);
        expect(harness.cache.put).toHaveBeenCalledTimes(1);
    });

    test('activation purges old QA caches but not unrelated caches', async () => {
        const harness = loadServiceWorker();
        let activation;
        harness.handlers.activate({ waitUntil: promise => { activation = promise; } });
        await activation;

        expect(harness.caches.delete).toHaveBeenCalledWith('cqa-static-v1');
        expect(harness.caches.delete).toHaveBeenCalledWith('cqa-dynamic-v1');
        expect(harness.caches.delete).not.toHaveBeenCalledWith('unrelated-cache');
    });
});
