import Plugin from 'src/plugin-system/plugin.class';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from 'src/service/http-client.service';

export default class ShippingAddressPlugin extends Plugin {
    static options = {
        selectors: {
            checkmark: '.address-checkmark',
        },
        blockEnterSelector: '.block-enter-key',
        attributeAction: 'data-action',
        debounceMs: 0, // ggf. 250
    };

    init() {
        this.client = new HttpClient();
        this.isSubmitting = false;
        this.bound = [];

        this.$emitter.publish('beforeInitShippingAddressPlugin');

        // click
        this._on(this.el, 'click', (ev) => {
            const el = ev.currentTarget;
            if (el.getAttribute('type') === 'button') {
                this._process(el);
            }
        }, { passive: true });

        // change
        const onChange = (ev) => this._process(ev.currentTarget);
        this._on(this.el, 'change', this.options.debounceMs ? this._debounce(onChange, this.options.debounceMs) : onChange);

        // keydown (Enter blocken nur bei Inputs mit block-enter-key)
        this._on(this.el, 'keydown', (ev) => {
            const target = ev.target;
            const isTextish = ['text', 'number', 'email', 'tel', 'search'].includes(target?.type);
            if (isTextish && target.classList?.contains(this.options.blockEnterSelector.replace('.', '')) && ev.key === 'Enter') {
                ev.preventDefault();
            }
        });
    }

    destroy() {
        // alle Listener entfernen
        this.bound.forEach(({ el, type, handler, options }) => el.removeEventListener(type, handler, options));
        this.bound = [];
        super.destroy();
    }

    _on(el, type, handler, options) {
        el.addEventListener(type, handler, options);
        this.bound.push({ el, type, handler, options });
    }

    _debounce(fn, wait) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    _process(triggerEl) {
        // Mehrfach-Submit verhindern
        if (this.isSubmitting) return;

        const form = triggerEl.closest('form');
        if (!form) return;

        const requestUrl = form.getAttribute(this.options.attributeAction);
        if (!requestUrl) {
            console.warn(`[ShippingAddressPlugin] Missing ${this.options.attributeAction} on form.`);
            return;
        }

        const formData = FormSerializeUtil.serialize(form);
        this.$emitter.publish('beforeSaveShippingAddressSendPostRequest', formData);

        this.isSubmitting = true;
        // optional UI-Block
        triggerEl.setAttribute('disabled', 'disabled');
        form.classList.add('is-loading');

        this.client.post(
            requestUrl,
            formData,
            (responseText) => {
                // Erfolgs-UI updaten
                this._toggleCheckmarks(form, triggerEl);

                // Optional: Response prüfen / Toast anzeigen
                // try { const json = JSON.parse(responseText); ... } catch {}

                this._finish(triggerEl, form, true);
            },
            (error) => {
                console.error('[ShippingAddressPlugin] POST failed', error);
                // Optional: Fehlerhinweis im UI
                form.setAttribute('data-error', '1');
                this._finish(triggerEl, form, false);
            }
        );
    }

    _toggleCheckmarks(form, triggerEl) {
        // nur innerhalb dieses Formulars
        form.querySelectorAll(this.options.selectors.checkmark).forEach(el => { el.style.display = 'none'; });
        const checkmark = triggerEl.nextElementSibling;
        if (checkmark && checkmark.matches(this.options.selectors.checkmark)) {
            checkmark.style.display = 'inline-block';
            // A11y optional:
            checkmark.setAttribute('aria-live', 'polite');
        }
    }

    _finish(triggerEl, form, ok) {
        this.$emitter.publish('afterSaveShippingAddressSendPostRequest', { ok });
        this.isSubmitting = false;
        triggerEl.removeAttribute('disabled');
        form.classList.remove('is-loading');
    }
}