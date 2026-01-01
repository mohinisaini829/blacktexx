# 9.2.2
- Verbesserte Plugin-Kompatibilität mit Shopware 6.7.

# 9.2.1
- Verbesserung der Barrierefreiheit durch Ergänzung fehlender ARIA Attribute.

# 9.2.0
- Fügt die Tastatursteuerung für die Cookieeinstellungen hinzu, damit diese auch barrierefrei bedienbar sind.

# 9.1.2
- Korrektur möglicher Warnungen in Log Dateien.

# 9.1.1
- Behebt ein mögliches Problem, bei dem bei der Verwendung von "data-acriscookieid" bei JavaScript Einbindungen Inhalte doppelt dargestellt wurden.

# 9.1.0
- Standard Werte von Cookies können ab sofort auch im Admin angepasst werden. Dies ist hilfreich, wenn eigene Cookies definiert werden, worauf danach im Storefront über JavaScript reagiert werden soll.
- Änderung der Standardwerte in den Plugineinstellungen für Neuinstallationen.
- Änderung des Button Textes "Cookies akzeptieren" auf "Ausgewählte Cookies akzeptieren".

# 9.0.0
- Kompatibilität mit Shopware 6.7.
- Unterstützung der folgenden Sprachen: de-DE, en-GB, nl-NL, fr-FR, es-ES, fi-FI, nn-NO, sv-SE, cs-CZ, pt-PT, tr-TR, da-DK, it-IT, pl-PL, bs-BA

# 8.0.6
- Weitere Optimierung der Barrierefreiheit.

# 8.0.5
- Verbesserte Kompatibilität mit Stripe Zahlungen.

# 8.0.4
- Optimierung der Barrierefreiheit.

# 8.0.3
- Optimierung der Kompatibilität mit dem Shopware Standard Cookie Verhalten und weiteren Plugins.
- Entfernung nicht mehr benötigter CSRF-Tokens.

# 8.0.2
- Verbesserte Admin-Kompatibilität mit Shopware 6.6.10.*

# 8.0.1
- Änderung der angegebenen Kompatibilität mit Shopware.

# 8.0.0
- Verbessert die Plugin-Kompatibilität.

# 7.0.23
- Optimiert die Cookie-Gruppen Verwaltung in der Administration. 

# 7.0.22
- Ändert das Cookie für Herkunftsinformationen ("acris_cookie_landing_page|acris_cookie_referrer") als nicht Standard Cookie. Dieses kann somit ab sofort in der Administration deaktiviert werden. 

# 7.0.21
- Performance Optimierungen in Bezug auf 404 Seiten.

# 7.0.20
- Behebt ein mögliches Problem, bei dem die automatisch gefundenen Cookie Gruppen ohne Namen und Beschreibungstexte erstellt wurden.

# 7.0.19
- Korrektur möglicher Warnungen in Log Dateien bei neueren Shopware Versionen.

# 7.0.18
- Bugfix: Falsche Typkonvertierung für den Cookie-Wert entfernt.

# 7.0.17
- Optimierung der Shopware Standard-Analytics-Implementierung in Verbindung mit der Cookie-Zustimmung.

# 7.0.16
- Behebt ein mögliches Problem, bei dem ein JavaScript Fehler auf Produktseiten aufgetreten war, wenn ein Youtube Video eingebunden wurde und nachdem JavaScript in einer neueren Shopware 6 Umgebung neu aufgebaut wurde. 

# 7.0.15
- Bugfix behebt einen Fehler beim Hinzufügen von Vertriebskanälen in der Verwaltung

# 7.0.14
- Behebt ein Problem bei dem Standardwerte von Cookies in der funktionalen Cookie Gruppe nicht mehr korrekt gesetzt wurden.

# 7.0.13
- Optimierte SEO-Indizierung von Cookie-Modal.

# 7.0.12
- Fügt das Cookie _gcl_gs zur Liste der Google Conversion Tracking Cookies hinzu. 

# 7.0.11
- Optimierung des Cleanup Scheduled Task in Verbindung mit Shopware 6.6.

# 7.0.10
- Behebt ein mögliches Problem beim Update zu Shopware 6.6.

# 7.0.9
- Behebt ein Problem, bei dem das Session Cookie doppelt gesetzt wurde.

# 7.0.8
- Behebt ein Problem, bei dem der Cookie Hinweis bei einem erneuten Besuch der Webseite wieder angezeigt wurde wenn der Kunde nicht alle Cookies akzeptiert hatte.
- Optimierung in Verbindung mit dem Http-Cache, bei dem der Cookie Hinweis auf gewissen Seiten erneut angezeigt wurden, obwohl die Cookies bereits akzeptiert waren.

# 7.0.7
- Behebt ein Problem bei dem für Standard Cookies keine Standard Werte mehr gesetzt wurden.

# 7.0.6
- Behebt einen Fehler beim Auslösen der Cookie Button im Standard Cookie Hinweis Unten

# 7.0.5
- Optimierung der Plugin Konfiguration.
- Optimierung Speicherung der Cookie Bestätigung im Cache.

# 7.0.4
- Behebt ein mögliches Problem, bei dem der Cookie Hinweis auf verschiedenen Seiten nicht erscheint, obwohl der Cookie Hinweis noch nicht akzeptiert wurde.

# 7.0.3
- Optimierung der Cookie Script Einbindung.

# 7.0.2
- Optimierung der Kompatibilität mit anderen Consent Manager Integrationen.
- Fügt eine Einstellung hinzu um eine erneute Cookie Zustimmung einzuholen.

# 7.0.1
- Shopware 6.6. Optimierungen.

# 7.0.0
- Kompatibilität mit Shopware 6.6.

# 6.3.6
- Behebt ein Problem bei der initialen Cookie Zustimmung.

# 6.3.5
- Anpassung der vorab bekannten Cookies in Bezug auf die Chat Software Tawk.

# 6.3.4
- Erweitert die Liste der bereits vorab bekannten Cookies um das Pinterest Cookie.

# 6.3.3
- Formular Optimierungen

# 6.3.2
- Behebt ein Problem mit dem Akkordeon

# 6.3.1
- Code Optimierungen

# 6.3.0
- Google Cookie-Zustimmungsmodus v2 wurde hinzugefügt

# 6.2.3
- Behebt ein mögliches Problem, bei dem das Cookie Modalfenster nach dem akzeptieren der Cookies wieder auf der Seite erscheint.

# 6.2.2
- Das Laden von Javascript in den Cookie Einstellungen wurde optimiert.

# 6.2.1
- Fügt das Data-Attribut "data-nosnippet" für den Cookie Hinweis ein, damit dieser von Crawlern nicht für die Erstellung von Snippets verwendet wird. 

# 6.2.0
- Ab sofort kann im Skript Feld von Cookies auch TWIG Code eingefügt werden.

# 6.1.9
- Optimiert das hinzufügen der erlaubten Attribute für den HTML-Sanitizer

# 6.1.8
- Die erlaubten Attribute für den HTML-Sanitizer wurden optimiert, so dass die Cookie-Snippets nach dem Speichern in der Administration nicht kaputt gehen

# 6.1.7
- Behebt ein mögliches Problem beim Entfernen von gesetzten Cookies.

# 6.1.6
- Behebt ein Problem bei dem das Modalfenster beim Akzeptieren der Cookies nicht mehr korrekt geschlossen wurde.

# 6.1.5
- Setzen des SameSite Attributes bei den Cookies acris_cookie_first_activated, acris_cookie_landing_page und acris_cookie_referrer.

# 6.1.4
- Kompatibiliät mit Shopware >= 6.5.1.0 verbessert

# 6.1.3
- Die Seite mit der Cookie-Gruppenliste in der Verwaltung wurde optimiert.

# 6.1.2
- Eingeschränkte Löschung von Cookies, die Standard sind.

# 6.1.1
- Die Leistung der Cookie-Listenseiten in der Verwaltung wurde verbessert.

# 6.1.0
- Konfiguration hinzugefügt, um die Schaltfläche "Cookies akzeptieren" anzuzeigen, nachdem die Einstellungen geöffnet wurden.

# 6.0.8
- Behebt ein Problem, bei dem bei Cookies hinterlegte Skripte, die zu Beginn ein Kommentar enthalten, nicht geladen wurden.
- Behebt ein Problem, bei dem bei Cookies hinterlegte Skripte in Übersetzungen nicht richtig geladen wurden.

# 6.0.7
- Behebt ein Problem, bei dem Skripte seit Shopware 6.5 nicht mehr mit einem script-Tag bei einem Cookie hinzugefügt werden konnten.

# 6.0.6
- Optimiertes Cookie-Modal beim Laden der Seite.

# 6.0.5
- Das Schließen des Cookie-Modals wurde behoben, wenn wir außerhalb des Cookies klicken.

# 6.0.4
- Das Laden von Javascript in den Cookie Einstellungen wurde optimiert.

# 6.0.3
- Behebt ein Problem bei dem Cookies nach dem Akzeptieren dieser dennoch nicht aktiv waren.

# 6.0.2
- Erweitert die Liste der bereits vorab bekannten Cookies um das Mollie Payment Cookie.

# 6.0.1
- Ändert die Cookie-ID des Google Conversion Trackings.

# 6.0.0
- Kompatibilität mit Shopware 6.5.

# 5.1.3
- Optimierung in Verbindung mit AdBlockern.

# 5.1.2
- Behebt ein mögliches Problem beim Laden von 404-Seiten.

# 5.1.1
- Behebt ein Problem, bei dem der Cookie Hinweis angezeigt wurde nachdem der Browser erneut geöffnet wurde. 

# 5.1.0
- Plugin Einstellung für Cookie Titel / Beschreibung Layout hinzugefügt.

# 5.0.5
- Integration des Matomo Tag Managers

# 5.0.4
- Problem beim Setzen von Cookie-Default-Werten und Http-Cache behoben

# 5.0.3
- Änderung des Pluginnamens und der Hersteller Links.

# 5.0.2
- Verbessert die Plugin Kompatibilität

# 5.0.1
- Optimierung des Seiten-Caches für nicht gefundene Seiten.

# 5.0.0
- Das Laden von Javascript in den Cookie Einstellungen wurde optimiert.
- Cookie Einstellungen in der Admin Oberfläche angepasst.

# 4.4.0
- Cookie Einstellungen um das Laden von Javascript erweitert.

# 4.3.4
- Zusätzliche Cookie IDs für die Erkennung der Hotjar Cookies hinzugefügt.

# 4.3.3
- Behebt ein Problem, bei dem bei 404-Seiten mit zugewiesener Erlebniswelt der Cookie Hinweis immer wieder auftauchte.

# 4.3.2
- Optimiertes Laden von Cookie-Styles aus der Plugin-Konfiguration.

# 4.3.1
- Iframe Anzeige optimiert.

# 4.3.0
- Externe Inhalte können jetzt über definierte Cookies und HTML Code geladen werden.

# 4.2.0
- Fügt ein geplante Aufgabe hinzu, welche unbekannte Cookies automatisch nach Ablauf einer festgelegten Frist aus dem System löscht.

# 4.1.0
- Ermöglicht es in der Plugin Einstellung die Position der Cookie Buttons zu verändern.

# 4.0.5
- Behebt ein Problem beim Seitenaufruf, wenn es die URL nicht gibt

# 4.0.4
- Verbesserte Kompatibilität mit dem ACRIS Import Export Plugin.

# 4.0.3
- Behebt ein Problem bei dem Cookies nach dem Akzeptieren dieser dennoch nicht aktiv waren.

# 4.0.2
- HTTP-Cache Optimierungen.

# 4.0.1
- Verbesserte Kompatibilität mit Shopware >= 6.4.11.0.

# 4.0.0
- Kompatibilität mit Shopware >= 6.4.11.0.

# 3.4.1
- Optimiert das Plugin-Image.
- Verbessert die Kompatibilität mit Shopware >= 6.4.10.0.
- Optimiert die Plugin-Farbe in der Verwaltung.

# 3.4.0
- Setzt funktionale Cookies die von Shopware selbst oder einem Shopware Plugin stammen und einen Standardwert besitzen automatisch im Browser.
- Problembehebungen in Verbindung mit der Nutzung von Google reCAPTCHA.

# 3.3.5
- Verbesserung der Regular Expression der Erkennung des Session-Cookies, damit nicht andere Cookies, die "session-" beinhalten ebenfalls erkannt und genehmigt werden. Hinzufügen des Cookies "ledgerCurrency" zum AmazonPay Cookie.

# 3.3.4
- Kompatibilität mit Shopware 6.4.8.0.

# 3.3.3
- Cache Optimierungen in Verbindung mit anderen Plugins

# 3.3.2
- Behebt ein Problem bei der Pluginaktivierung.

# 3.3.1
- Behebt Probleme bei Verwendung des PayPal Cookies in Verbindung mit dem Safari Browser.

# 3.3.0
- Änderung der IDs der Cookie-Buttons und Cookie Einstellungen im HTML-Code, damit diese von Ad-Blockern der Browser nicht mehr ausgeblendet werden.

# 3.2.0
- Fügt die Möglichkeit hinzu, die Buttons in gleicher Breite darzustellen.

# 3.1.8
- Code Optimierungen. Entfernung von alten Code-Teilen.

# 3.1.7
- Optimierung des Cookie Hinweises in der mobilen Ansicht

# 3.1.6
- Optimierung des Modal-Fensters in der mobilen Ansicht, wenn viel Text vorhanden ist.

# 3.1.5
- Behebt ein mögliches Problem, bei dem Cookie und Cookie Gruppen mit der falschen Übersetzung eingefügt wurden.

# 3.1.4
- Optimierung in Verbindung mit anderen Plugins. Feuert das Javascript Event der geänderten Cookies erst nachdem die Änderungen auch ans Backend übermittelt wurden.

# 3.1.3
- Erweitert die Liste der bereits vorab bekannten Cookies um das Google Tag Manager Debug Cookie.

# 3.1.2
- Behebt ein mögliches Problem, bei dem im Admin vom Import / Export Modul generierte Download Dateien nicht heruntergeladen werden konnten.

# 3.1.1
- Behebt mögliche Probleme in Verbindung mit dem Internet Explorer.

# 3.1.0
- Ändert die Cookie-ID von Google Analytics für die Erkennung der neuen Cookie Struktur.
- Achtung: Auch die Anleitung für den Google Tag Manager hat sich abgeändert!

# 3.0.7
- Behebt ein Problem, bei dem ein kritischer PHP-Fehler nach einer Weile auftritt.

# 3.0.6
- Ab sofort können der Titel und der Text der im Standard eingefügten funktionalen Cookie-Gruppe geändert werden.  

# 3.0.5
- Behebt Probleme beim Ändern der Währung, dass diese dennoch falsch bei im Http-Cache gespeicherten Seiten angezeigt wurde.

# 3.0.4
- Optimierung in Verbindung mit dem Shopware Http-Cache

# 3.0.3
- Behebt ein mögliches Problem in Verbindung mit diversen Drittanbieter Plugins.

# 3.0.2
- Optimize module of cookie group in administration.

# 3.0.1
- Performance Optimierungen

# 3.0.0
- Kompatibilität mit Shopware >= 6.4.0.0.

# 2.8.10
- Verbesserte Kompatibilität mit anderen Plugins beim Öffnen des Cookie Hinweises.

# 2.8.9
- Behebt Probleme, bei dem der Cookie Hinweis durch ein JavaScript Problem beim Akzeptieren der Cookies immer wieder erscheint.
- Beugt Probleme vor, die durch Sonderzeichen verschiedener Art verursacht werden können.

# 2.8.8
- Behebt ein Problem bei dem Cookie von anderen Drittanbieter Plugins nicht automatisch erkannt werden.

# 2.8.7
- Problem mit fehlenden Sprachdaten behoben.

# 2.8.6
- Entfernt den Textbaustein acrisCookieConsent.footerCmsPageLinkPrefixFirst und fügt statt dessen 3 Textbausteine ein acrisCookieConsent.footerCmsPageLinkPrefixFirstFirst, acrisCookieConsent.footerCmsPageLinkPrefixFirstSecond und acrisCookieConsent.footerCmsPageLinkPrefixFirstThird für eine bessere Individualisierbarkeit.
- Entfernt veraltete Textbaustein Services.

# 2.8.5
- Das von Shopware im Standard eingefügte Google Analytics Cookie wird ab sofort nicht mehr hinzugefügt, sofern Google Analytics im Verkaufskanal nicht als aktiv gekennzeichnet wurde oder keine Tracking-ID angegeben wurde.

# 2.8.4
- Behebt ein mögliches Problem bei der spezifischen Änderung von Cookies oder Cookie Gruppen funktionale Cookies löschen können.

# 2.8.3
- Behebt ein Problem bei dem die maximale Zeichenlänge der Cookie-ID auf 255 Zeichen beschränkt ist.

# 2.8.2
- Behebt ein Problem wenn beim erneuten öffnen des Browsers und der Anzeige des Cookie Hinweises wenn dieser bereits akzeptiert wurde. 
- Die Beschreibung der Cookie Gruppen wird ab sofort auch als HTML im Storefront ausgegeben.
- Optimierung der Cache Behandlung im Zusammenspiel mit anderen ACRIS Plugins.

# 2.8.1
- Behebt ein Problem bei dem funktionale Cookies einem Verkaufskanal zugeordnet werden und dies Probleme verursachen kann. 
- Wird die automatische Cookie Erkennung deaktiviert, wird auch keine extra Anfrage an den Server zur Erkennung des Cookies gestellt.
- Die Einstellung "Cookie Einstellungen beim Seitenaufruf aufklappen" zeigt ab sofort die gewünschte Wirkung.
- Cookies von Shopware Plugins werden auch erkannt, wenn die automatische Cookie Erkennung deaktiviert wurde.

# 2.8.0
- Fügt ein neues Cookie "acris_cookie_first_activated" hinzu. Dieses speichert welche Cookies bereits vom Benutzer zum ersten Mala akzeptiert wurden. Das Update ermöglicht es somit den Referrer und die Landing Page korrekt nachträglich an Google Analytics zu übermitteln. Achtung: Es ist ein Update der Tag Manager konfiguration erfordelich, sobald dies bereits für Landing Page und Referrer konfiguriert wurde.

# 2.7.0
- Ergänzung des Google Analytics Conversion-Tracking Cookies _gac. Wichtig: Sofern das Google Analytics Cookie beim Tagmanager für eine zusätzliche Übergabe von Referrer und Landingpage konfiguriert wurde, muss diese ID nun im Tagmanager aktualisiert werden.

# 2.6.1
- Behebt Probleme beim Speichern von HTML Text im Admin Bereich bei Shopware 6.3.x

# 2.6.0
- Fügt die Option für zusätzlich CMS-Seiten Links ein wie z.B. einem Impressum Link. 
- Fügt die Möglichkeit hinzu eine Überschrift im Cookie Hinweis anzuzeigen. 
- Fügt die Möglichkeit ein den Cookie Status nicht in den DataLayer zu übernehmen.

# 2.5.1
- Kompatibilität mit Shopware 6.3.x.

# 2.5.0
- Speichert den Referrer und die erst besuchte Seite der Benutzer in separate Cookies und fügt sie dem DataLayer hinzu.

# 2.4.0
- Ermöglicht es den Button "Cookies akzeptieren" am Ende des Informationstextes anzugeben anstatt ihn als eigenen Button anzuführen.
- Behebt ein Problem beim Laden des Links der Datenschutzseite von den Plugineinstellungen.

# 2.3.0
- Fügt ein neues Event acrisCookieStateChanged zum DataLayer hinzu zur besseren Weiterverarbeitung im Google Tag Manager.
- Fügt die einzelnen Cookies mit den Präfixen acrisCookie bzw. acrisCookieUniqueId zum DataLayer hinzu zur besseren Weiterverarbeitung im Google Tag Manager.  
- Erweitert die Liste der bereits vorab bekannten Cookies.

# 2.2.1
- Fügt zusätzliche Informationen in den DataLayer zur weiteren Nutzung im Google Tag Manager ein.

# 2.2.0
- Fügt die akzeptierten Cookies in den DataLayer ein, damit im Google Tag Manager darauf reagiert werden kann.
- Ermöglicht es beim Akzeptieren der Cookies einen sofortigen Seitenreload durchzuführen.

# 2.1.2
- Behebt ein Problem beim Laden der Cookies auf der Bestellbestätigungsseite.
- Beugt Probleme in der Storefront beim Laden des Cookie-Scripts und in Verbindung mit anderen Plugins vor.
- Fügt ein fehlendes Snippet im Admin Bereich ein.

# 2.1.1
- Erweitert die Liste der bereits vorab bekannten Cookies.

# 2.1.0
- Behebt ein Problem bei dem Google Analytics mehrfach getrackt wird über die Standard Shopware Einbindung.
- Behebt ein Problem, bei dem das Cookie, das gesetzt wird beim Akzeptieren nicht über Javascript verfügbar ist.
- Behebt ein Problem bei unterschiedlichen Tracking Verhalten beim Bestätigen über einen unterschiedlichen Button.
- Optimierung der Regex Cookie Prüfung in der Storefront. 
- Optimierung der Ansicht der Cookies in der Administration.
- Fügt eine zusätzliche Option hinzu den Hinweis erneut zu öffnen nach dem neuen Shopware Standard.

# 2.0.7
- Behebt ein Problem beim Setzen von Cookies für unterschiedliche Verkaufskanäle.

# 2.0.6
- Behebt ein Problem wenn andere Plugins einzelne Cookies in den Cookie Hinweis einfügen.

# 2.0.5
- Behebt ein mögliches Problem beim Hinzufügen von vorab bekannten Cookies.
- Erweitert die Liste der bereits vorab bekannten Cookies.

# 2.0.4
- Kompatibilität mit Shopware >= 6.2.0.
- Umpositionierung des Admin Menü Eintrages.

# 2.0.3
- Behebt Probleme bei der Änderung der Hintergrundfarbe des Cookie Hinweises.
- Optimierung der Vererbung von anderen Themes. 

# 2.0.2
- Behebt ein Problem bei der Erkennung von Cookies von anderen Plugins bei unterschiedlichen Sprachkonfigurationen.

# 2.0.1
- Behebt ein mögliches Problem bei der Aktualisierung des Plugins wenn das Plugin inaktiv oder nicht installiert ist.

# 2.0.0
- Korrekte Funktionsweise in Vebindung mit dem Http-Cache.
- Behebt ein Problem beim Hinzufügen des Standardwerts von Cookies, die von anderen Shopware-Plugins gesetzt werden.
- Behebt ein Problem beim Hinzufügen neuer gefundener Cookies.

# 1.3.2
- Behebt mögliche Probleme beim Update und bei der Deinstallation des Plugins in Verbindung mit niedrigen Datenbank Versionen.

# 1.3.1
- Behebt ein mögliches Problem bei dem SASS Storefront Variablen nicht gefunden werden von Shopware beim Theme kompilieren während der Aktivierung des Plugins.

# 1.3.0
- Berücksichtigt registrierte Cookies von anderen Plugins. Behebt ein Problem in Verbindung mit der Wartungsseite. Verbessert die Anzeige der Cookies in der Administration. Erweitert die Liste der vorab bekannten Cookies.  

# 1.2.1
- Behebt einen Fehler beim Laden einer Cookie Gruppe eines nicht zugeordneten Cookies bei einem HTTP-Request.

# 1.2.0
- Fügt die Möglichkeit hinzu die automatische Cookie Erkennung zu deaktivieren.
- Behebt einen Fehler beim Laden des Cookie Plugins auf der Seite.

# 1.1.2
- Behebt ein Problem beim Laden von Cookies ohne zugewiesener Cookie Gruppe. Erweitert die Liste der bereits vorab bekannten Cookies.

# 1.1.1
- Behebt ein JavaScript Problem beim Einsatz des Internet Explorers.

# 1.1.0
- Fügt die Möglichkeit für die Anzeige eines Modalfensters ein. Fügt ausgenommene Cookies zu der Liste der akzeptierten Cookies hinzu. Behebt Probleme in der Administration bei der erkannte Cookies nicht zuerst für die Standard-Sprache angelegt werden.

# 1.0.1
- Behebt mögliche Java Script Probleme wenn keine nicht funktionalen Cookies vorhanden sind.

# 1.0.0
- Veröffentlichung
