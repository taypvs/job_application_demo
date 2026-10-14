(function () {
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const button = event.submitter;
        if (!(button instanceof HTMLButtonElement) || !button.hasAttribute('data-loading')) {
            return;
        }

        if (button.classList.contains('is-loading')) {
            event.preventDefault();
            return;
        }

        button.dataset.originalLabel = button.textContent.trim();
        button.textContent = button.getAttribute('data-loading') || '処理中…';
        button.classList.add('is-loading');
        button.setAttribute('aria-busy', 'true');

        window.setTimeout(function () {
            button.disabled = true;
        }, 0);
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button.is-loading').forEach(function (button) {
            if (button.dataset.originalLabel) {
                button.textContent = button.dataset.originalLabel;
            }
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            button.disabled = false;
        });
    });
})();
