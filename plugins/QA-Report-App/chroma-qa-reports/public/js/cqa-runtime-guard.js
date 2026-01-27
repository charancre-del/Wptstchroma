(() => {
  const appRootId = 'cqa-react-app';
  const debugFlag = (() => {
    try {
      if (window?.cqaData?.debug) return true;
      return window.localStorage.getItem('cqaDebug') === 'true';
    } catch (error) {
      return false;
    }
  })();

  const showFallback = (error, source) => {
    const root = document.getElementById(appRootId);
    if (!root) return;

    root.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:center;min-height:400px;padding:24px;">
        <div style="max-width:640px;background:#fff;border:1px solid #f3f4f6;border-radius:16px;padding:24px;box-shadow:0 10px 25px rgba(0,0,0,0.08);">
          <h2 style="margin:0 0 12px;font-size:20px;color:#111827;">QA Reports hit a runtime error</h2>
          <p style="margin:0 0 12px;color:#6b7280;font-size:14px;">Please refresh the page. If the issue persists, contact support with the error details below.</p>
          <pre style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;font-size:12px;color:#111827;white-space:pre-wrap;">${source}: ${error?.message || error}</pre>
        </div>
      </div>
    `;
  };

  const logDebugError = (label, error, event) => {
    if (!debugFlag) return;
    const errorMessage = error?.message || error;
    const errorStack = error?.stack || event?.error?.stack || event?.stack;
    if (errorStack) {
      console.error(label, errorMessage, errorStack, event);
      return;
    }
    console.error(label, errorMessage, event);
  };

  window.addEventListener('error', (event) => {
    logDebugError('[CQA Runtime Error]', event.error || event.message, event);
    showFallback(event.error || event.message, 'window.error');
  });

  window.addEventListener('unhandledrejection', (event) => {
    logDebugError('[CQA Unhandled Rejection]', event.reason, event);
    showFallback(event.reason || 'Unhandled promise rejection', 'unhandledrejection');
  });

  const redactHeaders = (headers) => {
    if (!headers) return undefined;
    const sanitized = {};
    const sensitiveKeys = ['authorization', 'cookie', 'x-wp-nonce', 'x-xsrf-token'];

    const assignHeader = (key, value) => {
      const lowerKey = key.toLowerCase();
      sanitized[key] = sensitiveKeys.includes(lowerKey) ? '[REDACTED]' : value;
    };

    if (headers instanceof Headers) {
      headers.forEach((value, key) => assignHeader(key, value));
      return sanitized;
    }

    if (Array.isArray(headers)) {
      headers.forEach(([key, value]) => assignHeader(key, value));
      return sanitized;
    }

    Object.entries(headers).forEach(([key, value]) => assignHeader(key, value));
    return sanitized;
  };

  const redactSensitiveFields = (value) => {
    const sensitiveKeys = ['password', 'token', 'secret', 'nonce', 'authorization'];
    if (Array.isArray(value)) {
      return value.map((entry) => redactSensitiveFields(entry));
    }
    if (value && typeof value === 'object') {
      return Object.entries(value).reduce((acc, [key, entry]) => {
        acc[key] = sensitiveKeys.includes(key.toLowerCase())
          ? '[REDACTED]'
          : redactSensitiveFields(entry);
        return acc;
      }, {});
    }
    return value;
  };

  const redactBody = (body) => {
    if (!body) return undefined;
    if (typeof body === 'string') {
      const trimmed = body.trim();
      if (trimmed.length === 0) return '';
      if (trimmed.length > 2000) {
        return `[String body length: ${trimmed.length}]`;
      }
      if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
        try {
          const parsed = JSON.parse(trimmed);
          return redactSensitiveFields(parsed);
        } catch (error) {
          return trimmed;
        }
      }
      return trimmed;
    }
    if (body instanceof FormData) {
      const entries = {};
      body.forEach((value, key) => {
        entries[key] = value instanceof File ? `[File: ${value.name}]` : value;
      });
      return entries;
    }
    return `[Body type: ${typeof body}]`;
  };

  if (!window.__cqaFetchPatched) {
    const originalFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
      const [input, init] = args;
      const url = typeof input === 'string' ? input : input?.url;
      const shouldLog = debugFlag && url && window?.cqaData?.restUrl && url.includes(window.cqaData.restUrl);

      if (shouldLog) {
        console.debug('[CQA API Request]', {
          url,
          method: init?.method || 'GET',
          headers: redactHeaders(init?.headers),
          body: redactBody(init?.body),
        });
      }

      const response = await originalFetch(...args);

      if (shouldLog) {
        console.debug('[CQA API Response]', {
          url,
          status: response.status,
          ok: response.ok,
        });
      }

      return response;
    };
    window.__cqaFetchPatched = true;
  }

  const clampWizardStep = () => {
    try {
      const raw = window.localStorage.getItem('cqa-wizard-draft');
      if (!raw) return;
      const data = JSON.parse(raw);
      if (!data?.state) return;
      const maxStep = 6;
      const currentStep = Number(data.state.currentStep);
      if (!Number.isFinite(currentStep) || currentStep < 1 || currentStep > maxStep) {
        data.state.currentStep = 1;
        window.localStorage.setItem('cqa-wizard-draft', JSON.stringify(data));
      }
    } catch (error) {
      if (debugFlag) {
        console.warn('[CQA] Failed to validate wizard state', error);
      }
    }
  };

  clampWizardStep();

  if (window.location.hash === '') {
    window.location.hash = '#/';
  }
})();
