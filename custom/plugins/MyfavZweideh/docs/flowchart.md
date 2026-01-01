# Programmfluss

# 1. Designer-Daten

Die Designer-Daten werden an zwei Stellen im Template geladen.

a) Artikel-Detail
b) Basis-Template

---

## 1.a) Artikel-Detail

Datei: ```MyfavZweideh/src/Resources/views/storefront/page/product-detail/buy-widget.html.twig```

Im Artikel-Detail wird ein Button zum Öffnen des Designers bereitgestellt. Die Kaufen-Taste wird ausgeblendet, wenn der der Designer zur Verfügung steht.

---

## 1.b) Basis-Template

Datei: ```MyfavZweideh/src/Resources/views/storefront/page/base.html.twig```

Der eigentliche Designer wird im Basis-Template geladen.

Er muss mit diesen Daten konfiguriert werden. Die einzelnen JSON-Bestandteile werden hier zunächst aufgeschlüsselt, und nachfolgend dann noch einmal im Detail erläutert.

```
I) langJSON: // Sprachdateien
II) productsJSON: // Die eigentlichen Designer-Daten für das Produkt
III) designsJSON: // Grafikvorlagen, also Grafiken, die oben übers Menü mit zur Verfügung gestellt werden.
IV) templatesDirectory: // Layout für den Aufbau des Designers.
```

Alle JSON Daten und das TemplateDirectory sind im Plugin selbst hinterlegt. Sie wurden im Ordner ```MyfavZweideh/src/Resources/public/``` hinterlegt. Anschließend wurden sie mit dem Befehl

```
bin/console assets:install
```

installiert. Das ist wichtig, damit sie über die ```{{ asset('path') }}``` Anweisung geladen werden können.

Die Pfade sehen dabei dann so aus:

```{{ asset('bundles/myfavzweideh/lang/default.json', 'asset') }}```

, wobei ```bundles/myfavzweideh``` der Pfad zum Plugin-Asset-Folder ist.

---

### 1.b. III) designsJSON

Diese Anweisung lädt die inhaltlichen Produkt-Daten für den Designer.

Sie ruft dabei einen Shopware-Controller auf, der die Daten im JSON-Format zur Verfügung stellt. Eine Designer-Vorlage kann dabei aus mehreren zusammen-gruppierten Artikeln bestehen.

Der Controller ist ```Myfav\Zweideh\Storefront\Controller\FancyDesignerProductsCategoriesController```.



