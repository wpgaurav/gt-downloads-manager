(function () {
    'use strict';

    if (!window.gtdmFrontend || !window.fetch || !window.URLSearchParams) {
        return;
    }

    var QUERY_KEYS = ['gtdm_s', 'gtdm_cat', 'gtdm_tag', 'gtdm_sort', 'gtdm_page'];

    function buildContextUrl(queryParams) {
        var contextUrl = new URL(window.location.href);

        QUERY_KEYS.forEach(function (key) {
            contextUrl.searchParams.delete(key);
        });

        QUERY_KEYS.forEach(function (key) {
            var value = queryParams.get(key);
            if (value !== null && value !== '') {
                contextUrl.searchParams.set(key, value);
            }
        });

        return contextUrl.toString();
    }

    function toApiParams(queryParams, root) {
        var params = new URLSearchParams();

        if (queryParams.get('gtdm_s')) {
            params.set('search', queryParams.get('gtdm_s'));
        }

        if (queryParams.get('gtdm_cat')) {
            params.set('category', queryParams.get('gtdm_cat'));
        }

        if (queryParams.get('gtdm_tag')) {
            params.set('tag', queryParams.get('gtdm_tag'));
        }

        if (queryParams.get('gtdm_sort')) {
            params.set('sort', queryParams.get('gtdm_sort'));
        }

        if (queryParams.get('gtdm_page')) {
            params.set('page', queryParams.get('gtdm_page'));
        }

        if (root.dataset.perPage) {
            params.set('per_page', root.dataset.perPage);
        }

        if (root.dataset.layout) {
            params.set('layout', root.dataset.layout);
        }

        params.set('context_url', buildContextUrl(queryParams));

        return params;
    }

    function readFormQuery(form) {
        var formData = new FormData(form);
        var query = new URLSearchParams();

        QUERY_KEYS.forEach(function (key) {
            var value = formData.get(key);
            if (typeof value === 'string' && value !== '') {
                query.set(key, value);
            }
        });

        if (!query.get('gtdm_page')) {
            query.set('gtdm_page', '1');
        }

        return query;
    }

    function applyQueryToForm(form, query) {
        QUERY_KEYS.forEach(function (key) {
            var field = form.querySelector('[name="' + key + '"]');
            if (!field) {
                return;
            }

            var value = query.get(key);
            if (value !== null) {
                field.value = value;
            }
        });
    }

    function pushUrlState(query, replace) {
        var nextUrl = new URL(window.location.href);

        QUERY_KEYS.forEach(function (key) {
            nextUrl.searchParams.delete(key);
        });

        QUERY_KEYS.forEach(function (key) {
            var value = query.get(key);
            if (value !== null && value !== '') {
                nextUrl.searchParams.set(key, value);
            }
        });

        if (replace) {
            window.history.replaceState({}, '', nextUrl.toString());
        } else {
            window.history.pushState({}, '', nextUrl.toString());
        }
    }

    function renderResults(root, html) {
        var container = root.querySelector('[data-gtdm-results]');
        if (container) {
            container.innerHTML = html;
        }
    }

    function fetchResults(root, query, updateHistory) {
        var endpoint = new URL(window.gtdmFrontend.restBase, window.location.origin);
        var params = toApiParams(query, root);
        endpoint.search = params.toString();

        root.classList.add('gtdm-loading');

        return window.fetch(endpoint.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            })
            .then(function (payload) {
                if (payload && typeof payload.html === 'string') {
                    renderResults(root, payload.html);
                }

                if (updateHistory) {
                    pushUrlState(query, false);
                }
            })
            .catch(function () {
                root.classList.add('gtdm-error');
            })
            .finally(function () {
                root.classList.remove('gtdm-loading');
            });
    }

    function bindRoot(root) {
        var form = root.querySelector('[data-gtdm-filters]');

        if (!form) {
            return;
        }

        var initialUrlQuery = new URLSearchParams(window.location.search);
        applyQueryToForm(form, initialUrlQuery);

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var query = readFormQuery(form);
            fetchResults(root, query, true);
        });

        form.addEventListener('change', function () {
            var pageField = form.querySelector('[name="gtdm_page"]');
            if (pageField) {
                pageField.value = '1';
            }
            var query = readFormQuery(form);
            fetchResults(root, query, true);
        });

        root.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            var link = target.closest('.gtdm-pagination a');
            if (!link) {
                return;
            }

            event.preventDefault();

            var href = link.getAttribute('href');
            if (!href) {
                return;
            }

            var parsed = new URL(href, window.location.origin);
            var query = new URLSearchParams(parsed.search);
            applyQueryToForm(form, query);
            fetchResults(root, query, true);
        });

        window.addEventListener('popstate', function () {
            var urlQuery = new URLSearchParams(window.location.search);
            applyQueryToForm(form, urlQuery);
            fetchResults(root, readFormQuery(form), false);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var roots = document.querySelectorAll('[data-gtdm-root]');
        roots.forEach(bindRoot);
    });
})();
