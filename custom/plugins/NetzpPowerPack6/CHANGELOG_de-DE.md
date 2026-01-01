# 3.0.3
- Fix: Die Zusatzeinstellungen eines CMS-Blocks (Sichtbarkeit, Farben etc.) wurden nicht immer korrekt angezeigt.

# 3.0.2
- Fix: Fehler im Zusammenhang mit Zeitzonen behoben, der in seltenen Fällen aufgetreten ist.

# 3.0.1
- Fix: Google Maps-Element: Sonderzeichen wie Anführungszeichen/HTML-Entities im Inhalt werden korrekt im Karten-Popup dargestellt

# 3.0.0
- Support für SW 6.6

# 2.2.0
- Change: die Elemente "Akkordion" und "Tabs" haben jetzt einen optionalen Titel
- Fix: nicht vorhandene bzw. gelöschte CMS-Seiten lösen keinen Fehler mehr aus (Header, Footer, Bestellabschluss)

# 2.1.2
- Fix: das Akkordion-Element wurde für Bootstrap 5 angepasst

# 2.1.1
- Fix: Asset-Pfade korrigiert für SW >= 6.5.4

# 2.1.0
- Change: Bilder / responsive Thumbnail-Größen optimiert

# 2.0.0
- Support für SW 6.5
- !!!Wichtig!!! der Auswahlschalter für die responsive Darstellung wurde mittlerweile direkt in den Shopware 6-Standard integriert. Daher werden die responsiven Darstellungsoptionen im PowerPack in der nächsten Plugin-Version entfallen. Bitte migrieren Sie Ihre Einstellungen schnellstmöglich und verwenden ausschließlich den Shopware-Standard unter Sichtbarkeit / Anzeigegeräte.
* +++ ACHTUNG +++ **Aktualisierung auf SW 6.5**
* Deaktivieren Sie zunächst alle Plugins (nicht deinstallieren!)
* Aktualisieren Sie dann den Shop auf SW 6.5
* Aktualisieren Sie dann die Plugins auf die jeweils kompatible Version für SW 6.5
* Aktivieren Sie alle Plugins wieder
* Führen Sie das Update für jedes Plugin einzeln durch (klick auf die Versionsnummer des jeweiligen Plugins)
* Shopware hat in der Version 6.5 erhebliche Änderungen vorgenommen. Die Anpassung unserer Plugins war hier sehr aufwändig und hat viel Zeit beansprucht.
* Sollte etwas nicht wie bisher funktionieren, kontaktieren Sie bitte unseren Plugin-Support unter https://plugins.netzperfekt.de/support

# 1.6.0
- Komponente "Google maps": Opt-In / die Karten werden (optional) erst nach vorheriger Einwilligung geladen

# 1.5.4
- Komponente "Google maps": callback eingefügt, um Google API-Hinweis zu vermeiden

# 1.5.3
- Komponente "Infobar": CSS-Styles korrigiert

# 1.5.2
- Für Bilder werden (wo möglich) die kleineren Auflösungen der Shopware-Medienverwaltung verwendet.

# 1.5.1
- Das Parallax-Element wird auf mobilen Endgeräten aus technischen Gründen leider nicht unterstützt, dort werden die Bilder jetzt aber korrekt skaliert.

# 1.5.0
- Change: das Counter-Element kann mit Platzhaltern verwendet werden ({counter} {start} {end})
- Fix: Die Slots werden in Kategorien/Tab Layout in der korrekten Reihenfolge ausgegeben

# 1.4.0
- Change: CMS-Blöcke und Sektionen: Sichtbarkeit kann über eine Rule Builder-Regel gesteuert werden
- Change: CMS-Blöcke und Sektionen: Sichtbarkeit kann nach Datum gesteuert werden (Anzeigen von - bis)  

# 1.3.0
- Change: Aktionskarte / neuer Typ "statisch": vollflächig klickbares Element
- Change: Grid-Element 2spaltig 4/8: der Umbruch für Tablets wurde auf col-md-6/6 (anstelle von col-md-4/8) geändert
- Fix: Admin / HTML/CSS-Element: minimale Höhe, um Bearbeitung der Einstellungen bei leeren Elementen zu ermöglichen
- Fix: Font Awesome / Facebook-Icon entfernt (Interferenz mit unserem Plugin NetzpShariff6)

# 1.2.1
- Fix: Admin / kleinere Darstellungsprobleme behoben
- Fix: Unterstützung für die Plugin-Installation via composer 
- Fix: Aktions-Karte / Links in neuem Fenster öffnen funktioniert jetzt
- Change: Aktions-Karte / Blur: Der Text kann vom Blur-Effekt ausgenommen werden

# 1.2.0
**Das ist ein großes Update mit vielen neuen Funktionen und Elementen sowie einigen kleineren Fehlerbehebungen.**
Im Besonderen gibt es ein neues Element _CTA Flex_, das die Gestaltung von Buttons und Call-to-Actions vereinfacht und auf Basis von CSS-Flexbox umsetzt. Das alte CTA-Element wurde beibehalten, da die beiden Elemente nicht kompatibel sind. Für CTA / Buttons sollte jedoch ab sofort nur das neue _CTA Flex_ verwendet werden!

- Change: neues Element "CTA Flex" - grundlegend überarbeitetes Button und Call-to-Action-Element auf Basis von CSS-Flexbox
- Change: neues Element "Aktions-Karte mit Flip/Enthüllen/Unschärfe/Popup"
- Change: neues Element "Vorher/Nachher Bildvergleich/Slider"
- Change: neues Element "Animierter Zähler"
- Change: analog zum Standard-Shopware Element "3 Spalten, Bild & Text" gibt es zwei Elemente mit 2 und 4 Spalten, Bild & Text (unter "PowerPack - Layouts")
- Change: Das Shopware-Datenmapping wird für Bilder und Texte unterstützt, wo es sinnvoll ist (Alert, Card, CTA / CTA2, ImageCompare, Parallax, Testimonial)
- Change: Für alle Elemente (auch Showpare Standard-Elemente) können Farbverläufe definiert werden (Block-Einstellungen und Sektions-Einstellungen)
- Change: Admin / Aufteilung in die Block-Kategorien "PowerPack Layouts" und "PowerPack Elemente" für eine bessere Übersicht
- Fix: Admin / Tab-Element: die Bearbeitung bei vielen Tabs wurde verbessert, die maximale Zahl von Tabs wurde auf 20 erhöht
- Fix: Admin / Akkordion-Element: die maximale Zahl von Akkordion-Einträgen wurde auf 20 erhöht
- Fix: Admin / Testimonial-Element: die Darstellung wurde optimiert, wenn kein Name eingegeben wurde
- Fix: die Darstellung für in Grid-Layouts eingefügte Kategorie-Filter wurde optimiert, das Filter-Popup wird nicht mehr abgeschnitten

# 1.1.2
- Change: Plugin-Einstellungen / Font Awesome kann optional im Frontend ausgeschlossen werden

# 1.1.1
- Version: Kompatibilität mit SW 6.4.7

# 1.1.0
- ESLint problems fixed when building assets / compiling

# 1.0.9
- Version: compatibility with SW 6.4

# 1.0.8
- Fix: admin / CTA element: media handling fixed for images
- Change: toggle responsive state for cms sections and blocks

# 1.0.7
- Fix: snippets also replaced for ProductEntity (not only SalesChannelProductEntity)

# 1.0.6
- Fix: CTA / Optimized button display on mobile devices
- Change: Collapse-Element: minimum entries set to 1

# 1.0.5
- Fix: on some systems there was an error message during installation (ThemeCompiler) in connection with FontAwesome. This has been - now really ;-) fixed

# 1.0.4
- Fix: on some systems there was an error message during installation (ThemeCompiler) in connection with FontAwesome. This has been fixed
 
# 1.0.3
- snippets and other twig expressions can be used in article names, article descriptions and category descriptions, just type {{ "snippetname" | trans}} or {{ 40+2 }} or something
  (if you want html in your snippets use {{ "snippetname" | trans | raw }})

# 1.0.2
- sales channel: header cms block can be "sticky"
- new cms element: infobar/icon bar with 2 layouts
- new cms element: parallax image
- element counter: new layout "boxes"
- new cms grids 2 columns (4/8 and 8/4)

# 1.0.1
- optional cms layout on order finish page
- new cms element: google map
- new cms element: countdown

# 1.0.0
- initial version
