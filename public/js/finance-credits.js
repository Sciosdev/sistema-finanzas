(function () {
    'use strict';

    var loads = new WeakMap();

    function rememberCredit(content) {
        var url = new URL(window.location.href);
        url.searchParams.set('credit', content.dataset.creditId);
        window.history.replaceState(null, '', url);
    }

    function loadDetails(details) {
        var content = details.querySelector('[data-credit-detail-content]');
        if (!content) { return Promise.resolve(false); }
        rememberCredit(content);
        if (content.dataset.loaded === '1') { return Promise.resolve(true); }
        if (loads.has(details)) { return loads.get(details); }

        content.setAttribute('aria-busy', 'true');
        content.innerHTML = '<div class="card-body text-muted" role="status">Cargando mensualidades…</div>';

        var promise = fetch(content.dataset.url, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok || response.headers.get('X-Finance-Credit-Details') !== content.dataset.creditId) {
                throw new Error('No se pudo consultar el crédito.');
            }
            return response.text();
        }).then(function (html) {
            content.innerHTML = html;
            content.dataset.loaded = '1';
            content.dispatchEvent(new CustomEvent('finance:credit-details-loaded', { bubbles: true }));
            if (window.lucide) { window.lucide.createIcons(); }
            return true;
        }).catch(function () {
            content.innerHTML = '<div class="card-body"><p role="alert">No se pudieron cargar las mensualidades. Vuelve a intentarlo o abre el crédito en una página completa.</p><button type="button" class="btn btn-outline-primary me-2" data-credit-retry>Reintentar</button><a class="btn btn-outline-secondary" data-credit-fallback>Abrir crédito</a></div>';
            var fallback = new URL(window.location.href);
            fallback.searchParams.set('credit', content.dataset.creditId);
            fallback.hash = 'credit-' + content.dataset.creditId;
            content.querySelector('[data-credit-fallback]').href = fallback.href;
            return false;
        }).finally(function () {
            content.removeAttribute('aria-busy');
            loads.delete(details);
        });
        loads.set(details, promise);
        return promise;
    }

    function reveal(hash, scroll) {
        var match = /^#(?:credit|free-payments)-(\d+)$/.exec(hash || '');
        if (!match) { return; }
        var card = document.getElementById('credit-' + match[1]);
        if (!card) { return; }
        var details = card.querySelector('[data-credit-details]');
        card.dispatchEvent(new CustomEvent('finance:credit-reveal', { bubbles: true }));
        details.open = true;
        loadDetails(details).then(function (loaded) {
            var target = loaded ? document.getElementById(hash.slice(1)) : card;
            if (scroll && target) { target.scrollIntoView({ block: 'start' }); }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-credit-details]').forEach(function (details) {
            details.addEventListener('toggle', function () {
                if (details.open) { loadDetails(details); }
            });
        });
        reveal(window.location.hash, true);
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('[data-credit-open]');
        if (link) {
            event.preventDefault();
            var hash = link.getAttribute('href');
            reveal(hash, true);
            window.history.replaceState(null, '', hash);
        }
        var retry = event.target.closest('[data-credit-retry]');
        if (retry) { loadDetails(retry.closest('[data-credit-details]')); }
    });

    window.addEventListener('hashchange', function () { reveal(window.location.hash, true); });
})();
