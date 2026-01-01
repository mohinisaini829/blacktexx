import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class MyfavZweidehRequest extends Plugin {
    /**
     * Is called, when the page is loaded.
     **/
    init() {
        var element = document.getElementById('btn-myfav-request-send');
        
        if(element != null) {
            this._client = new HttpClient();
            element.addEventListener('click', this.onClick.bind(this));
        }
    }

    /**
     * Action-Handler: Wenn der Submit Button angeklickt wurde.
     */
    onClick(event) {
        event.preventDefault();
        event.stopPropagation();

        // Fehlermeldungen ausblenden
        var info_element_error = document.getElementById('info-myfav-request-error');
        info_element_error.style.display = 'none';
    
        // Werte abfragen
        var quantity = document.querySelector('.myfav-request-quantity').value;
        var freetext = document.getElementById('myfav-freetext').value;
        var tmp_cart_id = document.getElementById('myfav-zweideh-tmp-cart-id').value;
        var key = document.getElementById('myfav-zweideh-key').value;

        //Button verstecken -> Anzeige: Anfrage wird gesendet.
        var button_element = document.getElementById('btn-myfav-request-send');
        button_element.style.display = 'none';
        
        var info_element1 = document.getElementById('info-myfav-request-sending');
        info_element1.style.display = 'block';
        
        //Anfrage an den Server absenden.
        this.fetch(quantity, freetext, tmp_cart_id, key);
    }


    /**
    * Fetch the data from the server.
    */
    fetch(quantity, freetext, tmp_cart_id, key) {
        const postParams = new FormData();
        postParams.append('quantity', quantity);
        postParams.append('freetext', freetext);
        postParams.append('tmp_cart_id', tmp_cart_id);
        postParams.append('key', key);
        
        this._client.post(
            '/myfav-designer-request-form',
            postParams,
            (responseText) => {
                // Informations-Text, dass Anfrage übermittelt wird, ausblenden.
                let info_element1 = document.getElementById('info-myfav-request-sending');
                info_element1.style.display = 'none';
                
                /*this.el.outerHTML = responseText;*/
                try {
                    let responseJson = JSON.parse(responseText);

                    if(responseJson.status == 'success') {
                        //Bei Erfolg: Infomeldung anzeigen: Ihre Anfrage wurde erfolgreich versendet. Wir melden uns in Kürze zurück.
                        let info_element2 = document.getElementById('info-myfav-request-sent');
                        info_element2.style.display = 'block';
                        return;
                    }

                    console.log(responseJson);
                } catch(e) {
                    console.log(e);
                    console.log(responseText);
                }

                // Fehlertext anzeigen
                let info_element3 = document.getElementById('info-myfav-request-error');
                info_element3.style.display = 'block';

                // Button zum Absenden einblenden, damit man es noch einmal versuchen kann.
                var button_element = document.getElementById('btn-myfav-request-send');
                button_element.style.display = 'inline-block';
            },
            true
        );
    }
}