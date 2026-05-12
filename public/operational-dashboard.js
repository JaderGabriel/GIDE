(function () {
    var el = document.getElementById('od-live');
    if (!el) return;

    var url = el.dataset.pollUrl;
    if (!url) return;

    var tsEl = document.getElementById('od-live-ts');
    var pendingEl = document.getElementById('od-total-pending');
    var failedEl = document.getElementById('od-total-failed');
    var throughputEl = document.getElementById('od-total-throughput');
    var ingressEl = document.getElementById('od-total-ingress');
    var rowsEl = document.getElementById('od-live-rows');

    function dotClass(q) {
        if (q.failed > 0 && q.pending > 10) return 'od-live__row-dot--bad';
        if (q.pending > 5 || q.failed > 0) return 'od-live__row-dot--warn';
        return 'od-live__row-dot--ok';
    }

    function valColor(v, type) {
        if (type === 'pending') return v > 10 ? 'color:#dc2626' : v > 0 ? 'color:#d97706' : '';
        if (type === 'failed') return v > 0 ? 'color:#dc2626' : '';
        if (type === 'processed') return v > 0 ? 'color:#059669' : '';
        if (type === 'ingress') return v > 0 ? 'color:#0284c7' : '';
        return '';
    }

    function animateValue(el, newVal) {
        var old = el.textContent;
        el.textContent = newVal.toLocaleString('pt-BR');
        if (old !== '-' && old !== el.textContent) {
            el.style.transition = 'none';
            el.style.transform = 'scale(1.15)';
            el.offsetHeight; // force reflow
            el.style.transition = 'transform .3s ease-out';
            el.style.transform = 'scale(1)';
        }
    }

    function poll() {
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (d) {
                if (!d) return;

                tsEl.textContent = d.timestamp;
                animateValue(pendingEl, d.total_pending);
                animateValue(failedEl, d.total_failed);
                animateValue(throughputEl, d.throughput_5min);
                animateValue(ingressEl, d.ingress_5min);

                var html = '';
                for (var i = 0; i < d.queues.length; i++) {
                    var q = d.queues[i];
                    html += '<div class="od-live__row">'
                        + '<div class="od-live__row-name"><div class="od-live__row-dot ' + dotClass(q) + '"></div>' + q.name + '</div>'
                        + '<div class="od-live__row-val" style="' + valColor(q.pending, 'pending') + '">' + q.pending + '</div>'
                        + '<div class="od-live__row-val" style="' + valColor(q.failed, 'failed') + '">' + q.failed + '</div>'
                        + '<div class="od-live__row-val" style="' + valColor(q.ingress, 'ingress') + '">' + q.ingress + '</div>'
                        + '<div class="od-live__row-val" style="' + valColor(q.processed, 'processed') + '">' + q.processed + '</div>'
                        + '</div>';
                }
                rowsEl.innerHTML = html;
            })
            .catch(function () { /* retry on next cycle */ });
    }

    poll();
    setInterval(poll, 10000);
})();
