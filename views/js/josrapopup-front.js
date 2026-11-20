/**
 * Josra Popup Manager - Front logic
 * No depende de jQuery para poder cargarse cuanto antes.
 */
(function () {
    'use strict';

    var STORAGE_PREFIX = 'josrapopup_';
    var queue = window.josraPopupQueue || [];
    var shownThisPageLoad = {};

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    /* ---------------- Cookies / almacenamiento de frecuencia (invitados) ---------------- */

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function canShowByFrequency(popup) {
        var key = STORAGE_PREFIX + popup.id;

        switch (popup.frequency_type) {
            case 'always':
                return true;

            case 'session':
                return sessionStorage.getItem(key) === null;

            case 'days':
                var last = getCookie(key);
                if (!last) {
                    return true;
                }
                var diffDays = (Date.now() - parseInt(last, 10)) / 86400000;
                return diffDays >= popup.frequency_days;

            case 'max_times':
                var count = parseInt(getCookie(key + '_count') || '0', 10);
                return count < popup.frequency_max_times;

            default:
                return true;
        }
    }

    function registerShown(popup) {
        var key = STORAGE_PREFIX + popup.id;

        switch (popup.frequency_type) {
            case 'session':
                sessionStorage.setItem(key, '1');
                break;
            case 'days':
                setCookie(key, Date.now().toString(), popup.frequency_days + 1);
                break;
            case 'max_times':
                var count = parseInt(getCookie(key + '_count') || '0', 10) + 1;
                setCookie(key + '_count', count.toString(), 365);
                break;
        }

        // Si el cliente está logueado, además registramos la impresión en BBDD
        // (para reglas 'days' / 'max_times' consistentes entre dispositivos).
        if (popup.is_customer_logged && popup.ajax_log_url) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', popup.ajax_log_url + '&id_popup=' + popup.id, true);
            xhr.send();
        }
    }

    /* ---------------- Render del popup ---------------- */

    function buildPopupElement(popup) {
        var overlay = document.createElement('div');
        overlay.className = 'josrapopup-overlay josrapopup-position-' + popup.popup_position;
        if (!popup.overlay) {
            overlay.classList.add('josrapopup-no-overlay');
        }

        var box = document.createElement('div');
        box.className = 'josrapopup-box';
        box.style.maxWidth = popup.popup_width + 'px';

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'josrapopup-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Cerrar');
        box.appendChild(closeBtn);

        var inner = document.createElement('div');
        inner.className = 'josrapopup-inner';

        if (popup.content_type === 'image' && popup.image) {
            var imgWrapper = document.createElement(popup.image.link ? 'a' : 'div');
            if (popup.image.link) {
                imgWrapper.href = popup.image.link;
            }
            var img = document.createElement('img');
            img.src = popup.image.url;
            img.alt = popup.title || '';
            if (popup.image.responsive) {
                img.className = 'josrapopup-img-responsive';
            }
            if (popup.image.width) {
                img.style.width = popup.image.width + 'px';
            }
            if (popup.image.height) {
                img.style.height = popup.image.height + 'px';
            }
            imgWrapper.appendChild(img);
            inner.appendChild(imgWrapper);
        } else {
            if (popup.title) {
                var titleEl = document.createElement('h3');
                titleEl.className = 'josrapopup-title';
                titleEl.textContent = popup.title;
                inner.appendChild(titleEl);
            }
            var contentEl = document.createElement('div');
            contentEl.className = 'josrapopup-content';
            contentEl.innerHTML = popup.content || '';
            inner.appendChild(contentEl);
        }

        if (popup.button_text && popup.button_link) {
            var btn = document.createElement('a');
            btn.href = popup.button_link;
            btn.className = 'josrapopup-btn';
            btn.textContent = popup.button_text;
            inner.appendChild(btn);
        }

        box.appendChild(inner);
        overlay.appendChild(box);

        function closePopup() {
            overlay.classList.remove('josrapopup-visible');
            setTimeout(function () {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
            }, 250);
        }

        closeBtn.addEventListener('click', closePopup);
        if (popup.closable_outside_click) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    closePopup();
                }
            });
        }
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                closePopup();
                document.removeEventListener('keydown', escHandler);
            }
        });

        return overlay;
    }

    function showPopup(popup) {
        if (shownThisPageLoad[popup.id]) {
            return;
        }
        shownThisPageLoad[popup.id] = true;

        var el = buildPopupElement(popup);
        document.body.appendChild(el);
        // Forzar reflow para que la transición CSS se aplique
        void el.offsetWidth;
        el.classList.add('josrapopup-visible');

        registerShown(popup);
    }

    /* ---------------- Triggers ---------------- */

    function attachTrigger(popup) {
        if (!canShowByFrequency(popup)) {
            return;
        }

        switch (popup.trigger_type) {
            case 'load':
                if (popup.trigger_delay > 0) {
                    setTimeout(function () {
                        showPopup(popup);
                    }, popup.trigger_delay * 1000);
                } else {
                    showPopup(popup);
                }
                break;

            case 'exit_intent':
                document.addEventListener('mouseout', function handler(e) {
                    if (!e.relatedTarget && !e.toElement && e.clientY < 10) {
                        showPopup(popup);
                        document.removeEventListener('mouseout', handler);
                    }
                });
                break;

            case 'scroll':
                window.addEventListener('scroll', function handler() {
                    var scrollPercent = (window.scrollY /
                        (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                    if (scrollPercent >= popup.trigger_scroll_percent) {
                        showPopup(popup);
                        window.removeEventListener('scroll', handler);
                    }
                });
                break;

            case 'click':
                if (popup.trigger_click_selector) {
                    document.addEventListener('click', function (e) {
                        if (e.target.closest && e.target.closest(popup.trigger_click_selector)) {
                            e.preventDefault();
                            showPopup(popup);
                        }
                    });
                }
                break;
        }
    }

    ready(function () {
        // Ordena por 'priority' ya viene resuelto desde el backend (orden de inserción en la cola)
        queue.forEach(function (popups) {
            popups.forEach(attachTrigger);
        });

        // Por si algún hook se pinta después de que este script ya se ejecutó
        window.josraPopupQueue = {
            push: function (popups) {
                popups.forEach(attachTrigger);
            }
        };
    });
})();
