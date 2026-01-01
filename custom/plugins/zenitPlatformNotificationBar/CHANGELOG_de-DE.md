# 5.0.1
- Barrierefreiheit: Entfernt `touchstart`-Events zugunsten von `click`-Events, um die Kompatibilität mit Tastatur- und Screenreader-Nutzung sicherzustellen.

# 5.0.0
- Kompatibilität: Kompatibilität mit Shopware 6.7.0.0 hergestellt.

# 4.3.1
- Optimierung: 'striptags' Filter zu `aria-label` hinzugefügt.

# 4.3.0
- Optimierung: Verbesserungen der Barrierefreiheit.

# 4.2.0
- Optimierung: HTML-Sytax wird nun in Textfeldern unterstützt.

# 4.1.1
- Bugfix: Ein Javasript-Fehler wurde behoben

# 4.1.0
- Feature: Neue Konfiguration hinzugefügt, um Kundengruppen auszuschließen.

# 4.0.1
- Bugfix: z-index Problem mit Sticky Header.

# 4.0.0
- Kompatibilität: Kompatibilität mit Shopware 6.6 hergestellt.

# 3.2.3
- Bugfix: z-index Problem mit Sticky Header.

# 3.2.2
- Optimierung: Scrollbar ausblenden.

# 3.2.1
- Optimierung: Overflow-Scroll wegen Scrollleiste geändert.

# 3.2.0
- Optimierung: Optimierung der mobilen Darstellung.
- Optimierung: Refaktoriert die Verwendung des systemConfigService.

# 3.1.1
- Optimierung: Javascript mit neuer Shopware-Version neu erstellen.

# 3.1.0
- Optimierung: Bannertext als Link anzeigen, wenn die Option Button anzeigen deaktiviert aber eine Link Url festgelegt wurde.

# 3.0.0
- Kompatibilität: Kompatibilität mit Shopware 6.5.0.0 hergestellt.
- Kompatibilität: Attribut `data-toggle` durch `data-bs-toggle` ersetzt.
- Kompatibilität: Attribut `data-target` durch `data-bs-target` ersetzt.
- Kompatibilität: Überarbeiten der Statemanager Klassen für Bootstrap v5.
- Kompatibilität: Migration von jQuery Implementierung zu Vanilla JavaScript.
- Kompatibilität: Pfad von `ThemeCompilerEnrichScssVariablesEvent` geändert.
- Optimierung: Entfernen des `data-btnCloseBanner` Attributes, weil es bereits in den Plugin-Optionen vorhanden ist.
- Optimierung: Entfernen des `role="button"` Attributes bei Buttons, weil es nicht notwendig ist.
- Optimierung: Deployment Server Abfrage zu ThemeVariablesSubscriber hinzugefügt.

# 2.5.0
- Optimierung: Standard-Medienordner hinzugefügt.
- Optimierung: Verbessert das initiale Laden des Text-Sliders.
- Optimierung: Registrieren des Cookies in der Komfort-Cookie-Gruppe aufgrund gesetzlicher Einschränkungen.
- Bugfix: Verhindert die Überlagerung durch nachfolgende slides bei der initialen Rotation.

# 2.4.0
- Optimierung: Niedrigeren Z-Index gesetzt wegen Sticky-Header.
- Optimierung: Übersetzung der benutzerdefinierten Konfigurations-Hinweise hinzugefügt.
- Optimierung: Veraltetes SessionInterface entfernt.
- Optimierung: Automatisches Laden von Storefront-Snippets.
- Optimierung: SCSS-Workaround aus Issue NEXT-7365 entfernt.
- Kompatibilität: Shopware 6.4.18

# 2.3.1
- Feature: Neue Konfiguration hinzugefügt, um die Laufzeit des Cookies zu ändern.

# 2.3.0
- Optimierung: Offener und geschlossener Status von Session Storage zu Cookies geändert.
- Optimierung: Status ist nun Cookie gesteuert und damit in anderen Tabs verfügbar.
- Optimierung: Verhindert das Hüpfen der Bannerhöhe durch Text-Slider-Initialisierung.
- Optimierung: Verbessert die Reinitialisierung des Text-Sliders beim Anzeigen-Event des Collapse-Elements.
- Optimierung: Der Text-Slider beginnt mit dem ersten Text-Slide beim Anzeigen-Event des Collapse-Elements.
- Optimierung: Benutzerdefinierte Komponente hinzugefügt, um Konfigurations-Informationen anzuzeigen.
- Bugfix: Anzeige im Suchcontroller funktionierte nicht.

# 2.2.1
- Optimierung: Registrieren von Js-Plugins auf andere Selektoren, um die Kompatibilität mit Shopware 6.4.8.0 zu gewährleisten

# 2.2.0
- Optimierung: Verbessert die Hilfetexte in der Plugin-Konfiguration.
- Optimierung: Verbesserung im SCSS-Code.
- Feature: Neue Konfiguration hinzugefügt, um zwischen Standardschriftarten und benutzerdefinierten Schriftarten zu wählen.
- Feature: Möglichkeit hinzugefügt, Texte zu übersetzen.

# 2.1.3
- Optimierung: Zentriert den Text-Slider, wenn kein Button angezeigt wird.

# 2.1.2
- Optimierung: Seitentypenauswahl für Shopseiten entfernt, da Shopware keine Unterscheidung mehr zwischen Shopseiten und Kategorieseiten innerhalb der Controller trifft.
- Optimierung: Verbessert die Kompatibilität mit custom Controllern in der Klasse GetControllerInfo.
- Optimierung: Entfernt die Funktion removeConfiguration aus der Plugin Bootstrap

# 2.1.1
- Optimierung: Überarbeitung der "Anzeigen auf"-Optionen
- Optimierung: Überarbeitung der Plugin-Konfiguration
- Optimization: Umbenennung der Twig-Variablen von `notificationBar` in `zenNotificationBar`
- Optimization: Umbenennung der SCSS-Variablen von `notification-bar` in `zen-notification-bar`
- Optimierung: Werte von url() in Anführungszeichen gesetzt, um Darstellungsprobleme bei URLs mit Sonderzeichen zu vermeiden.
- Optimierung der Plugin Bootstrap lifecycle Methoden

# 2.1.0
- Feature: Neuer Modus: "einklappbar". Banner wird über einen Close-Button permanent geschlossen. Initial erscheint der Banner aufgeklappt.
- Feature: Neuer Modus: "ausklappbar". Banner wird über einen Pfeil-Button aufgeklappt. Initial erscheint der Banner zugeklappt.
- Optimierung: Mobile Friendly Lighthouse Check - Optimierung der klickbaren Flächen
- Optimierung: Verbesserung des Buttonabstandes

# 2.0.1
- Optimierung: Lighthouse Accessibility Verbesserung

# 2.0.0
- Kompatibilität zu Shopware 6.2.0 und 6.3.0
- Feature: Einführung eines Medienauswahl-Feldes in der Konfiguration, welches mit SW 6.2.0 bereitgestellt wird.
- Feature: Einführung eines Color-Picker-Feldes in der Konfiguration, welches mit SW 6.2.0 bereitgestellt wird.
- Feature: Neuer Subscriber zum Hinzufügen benutzerdefinierter SCSS-Variablen über die Plugin-Konfiguration - NEXT-5116
- Optimierung: Neuer Twig-Block als Einstiegspunkt gewählt 
- Bugfix: Verhindert das Escapen der Ausgabe der Custom-CSS Konfiguration.

# 1.2.1
- Bugfix: Sichtbare Viewports Konfiguration.
- Bugfix: Kleinere Bugfixes in der Darstellung.

# 1.2.0
- Optimierung: Verbesserung der Erkennung des eingeklappten Zustandes. Speicherung bis Änderung in der Plugin-Konfiuration.
- Optimierung: Anpassung der vertikalen Ausrichtung der Inhalte für Chrome.

# 1.1.0
- Kompatibilität zu Shopware 6.1.0

# 1.0.1
- Optimierung der Text-Slider Zeilenhöhe

# 1.0.0
- Initial plugin release
