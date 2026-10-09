/* Rotate only after a successful response; network retries retain the original key. */
(function () {
    if (!window.jQuery) return;
    jQuery(document).ajaxSuccess(function (_event, xhr) {
        const key = xhr.responseJSON?.next_idempotency_key;
        if (!key) return;
        document.querySelectorAll('input[name="idempotency_key"]').forEach(function (input) {
            input.value = key;
            input.defaultValue = key;
        });
    });
})();
