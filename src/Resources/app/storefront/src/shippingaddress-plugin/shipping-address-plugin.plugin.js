import Plugin from 'src/plugin-system/plugin.class';
import FormSerializeUtil from 'src/utility/form/form-serialize.util';
import HttpClient from 'src/service/http-client.service';

function saveShippingAddressEventListener (element, eventType, handler) {
    if (element.addEventListener) {
        element.addEventListener(eventType, handler, false);
    } else if (element.attachEvent) {
        element.attachEvent('on' + eventType, handler);
    }
}

function processShippingAddressForm() {
    this.client = new HttpClient();

    const form = this.closest('form'),
        requestUrl = form.getAttribute('data-action'),
        formData = FormSerializeUtil.serialize(form);

    this.$emitter.publish('beforeSaveShippingAddressSendPostRequest', formData);

    this.client.post(requestUrl, formData, callback => {
        this.$emitter.publish('afterSaveShippingAddressSendPostRequest');
    }, error => {
        this.$emitter.publish('afterSaveShippingAddressSendPostRequest');
    });
}

export default class ShippingAddressPlugin extends Plugin {
    init() {
        this.$emitter.publish('beforeInitShippingAddressPlugin');

        saveShippingAddressEventListener(this.el, 'change', processShippingAddressForm);
        //saveShippingAddressEventListener(this.el, 'click', processShippingAddressForm);
        saveShippingAddressEventListener(this.el, 'keydown', (event) => {
            let element = this.el;

            if (((element.getAttribute('type') === 'text') || (element.getAttribute('type') === 'number')) && element.classList.contains('block-enter-key')) {
                if (event.which === 13 || event.keyCode === 13) {
                    event.preventDefault();
                    return false;
                }
            }
        });
    }
}
