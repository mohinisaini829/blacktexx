# E-Mail Image

Wie das Bild in der Bestellbestätigungsmail angepasst werden muss,
damit das Designer-Bild dargestellt wird:

Originalstelle:
```<td>{% if nestedItem.cover is defined and nestedItem.cover is not null %}<img src="{{ nestedItem.cover.url }}" width="75" height="auto"/>{% endif %}</td>```

durch dies hier ersetzen:

```
<td>
    {% if 
        nestedItem.payload.lumise_tmp_key is defined and
        nestedItem.payload.lumise_tmp_key is not null and
        nestedItem.payload.lumise_tmp_cart_id is defined and
        nestedItem.payload.lumise_tmp_cart_id is not null
    %}
        <img src="{{ rawUrl('frontend.myfav.zweideh.mail.preview.image', {'key': nestedItem.payload.lumise_tmp_key, 'tmp_cart_id': nestedItem.payload.lumise_tmp_cart_id }, salesChannel.domains|first.url) }}" width="75" height="auto" />
    {% elseif 
        nestedItem.cover is defined and nestedItem.cover is not null
    %}
        <img src="{{ nestedItem.cover.url }}" width="75" height="auto"/>
    {% endif %}
</td>
```