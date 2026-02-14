/* global window, document */
(function () {
    'use strict';

    var submitLock = false;

    function isLoginScreenActive() {
        return !!document.querySelector('.chroma-login-screen');
    }

    function getPinButtons() {
        return Array.prototype.slice.call(document.querySelectorAll('.pin-grid button'));
    }

    function findButtonByText(text) {
        var buttons = getPinButtons();
        for (var i = 0; i < buttons.length; i++) {
            if ((buttons[i].textContent || '').trim() === text) {
                return buttons[i];
            }
        }
        return null;
    }

    function clickButton(text) {
        var button = findButtonByText(text);
        if (button && !button.disabled) {
            button.click();
            return true;
        }
        return false;
    }

    function getPinProgress() {
        var slots = document.querySelectorAll('.pin-display .digit');
        var active = document.querySelectorAll('.pin-display .digit.active');
        return {
            total: slots.length,
            active: active.length
        };
    }

    function submitIfComplete() {
        if (submitLock || !isLoginScreenActive()) {
            return;
        }

        var progress = getPinProgress();
        if (!progress.total || progress.active < progress.total) {
            return;
        }

        var submitButton = document.querySelector('.portal-btn');
        if (!submitButton || submitButton.disabled) {
            return;
        }

        submitLock = true;
        submitButton.click();
        window.setTimeout(function () {
            submitLock = false;
        }, 800);
    }

    function bindKeyboardSupport() {
        document.addEventListener('keydown', function (event) {
            if (!isLoginScreenActive()) {
                return;
            }

            var key = event.key;

            if (/^[0-9]$/.test(key)) {
                event.preventDefault();
                clickButton(key);
                submitIfComplete();
                return;
            }

            if (key === 'Backspace') {
                event.preventDefault();
                clickButton('\u2190');
                return;
            }

            if (key === 'Delete' || key === 'Escape') {
                event.preventDefault();
                clickButton('C');
                return;
            }

            if (key === 'Enter') {
                event.preventDefault();
                var submitButton = document.querySelector('.portal-btn');
                if (submitButton && !submitButton.disabled) {
                    submitButton.click();
                }
            }
        });
    }

    function watchPinDisplay() {
        var observer = new MutationObserver(function () {
            submitIfComplete();
        });

        observer.observe(document.documentElement, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['class']
        });
    }

    bindKeyboardSupport();
    watchPinDisplay();
})();
