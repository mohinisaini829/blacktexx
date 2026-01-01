# 4.0.6
- Fehler beim Cache der Blog-Übersicht behoben

# 4.0.5
- Fehler im Blog-RSS-Feed behoben
- Fehler bei der Blog-Detailseite behoben

# 4.0.4
- Problem bei der Generierung kanonischer URLs für Blog-Details behoben.

# 4.0.3
- Das Problem, dass benutzerdefinierte Felder nicht auf der Blog-Detailseite angezeigt wurden, wurde behoben.

# 4.0.2
- Problem behoben, dass die Seitenleiste auf der Blog-Detailseite nach dem Sprachwechsel nicht mehr sichtbar war.

# 4.0.1
- Problem mit deinstalliertem Plugin behoben

# 4.0.0
- Kompatibilität mit Shopware 6.7

# 3.0.25
- Aktualisierte SEO-URL-Generierung für Blogs, denen kein Vertriebskanal zugewiesen ist.

# 3.0.24
- `blog.indexer` und `blog.category.indexer` zu den Einstellungen->Caches & Indizes hinzugefügt

# 3.0.23
- Kompatibilität mit Shopware 6.6.x alte Version

# 3.0.22
- Das Problem, dass der Blog-Slug nicht wie erwartet funktionieren konnte, wurde behoben.

# 3.0.21
- Weitere Twig-Blöcke zur besseren Anpassung der Blog-Detailseite hinzugefügt
- Nur Kategorie-Ids in den Invalidierungs-Cache aufnehmen

# 3.0.20
- Zentrierter Blog-Detailbeitrag bei Storefront

# 3.0.19
- Entfernen Sie vererbte Flagge von `cms_page` der Blog-Einträge.

# 3.0.18
- Das Problem mit der Paginierung auf der Blog-Listing-Seite im Schaufenster wurde behoben, wenn der Zugriff über die Blog-Kategorie-Breadcrumb erfolgt.

# 3.0.17
- Verbesserte SEO kanonische URL für Blog-Detailseite

# 3.0.16
- Einige Verbesserungen aktualisiert

# 3.0.15
- Einige Verbesserungen aktualisiert

# 3.0.14
- Problem behoben, dass das Blog-Detail nicht den Abschnitt Blöcke anzeigte

# 3.0.13
- Tag-Verwaltung für Blogs hinzugefügt.

# 3.0.12
- Suchmodul für Blogeinträge in der Administration hinzugefügt
- Sonderzeichen aus dem Slug des Blogeintrags entfernt
- Benutzerdefinierte Blog-Feldsätze hinzugefügt.
- Fix Blog Seo URL Generation, wenn der Blogeintrag in einem bestimmten Vertriebskanal erstellt wird.

# 3.0.11
- Der Fehler, dass die Beschreibung des Blogautors nicht im HTML-Format angezeigt wird, wurde behoben.
- Assoziation „Tags“ zum Blogeintrag hinzugefügt.
- Es wurde der Fehler behoben, dass nach der Installation des Plugins `CbaxModulManufacturers` keine neue Produktdetail- und Produktlistenseite erstellt werden konnte

# 3.0.10
- Der Routenname wurde von `sas.frontend.blog.search` in `frontend.sas.blog.search` geändert, um mit dem Shopware-Muster kompatibel zu sein.

# 3.0.9
- Der Fehler, dass der Blogeintrag auch dann angezeigt werden kann, wenn er noch nicht veröffentlicht wurde, wurde behoben.
- Das jsonld-Schema des Blogs wurde aktualisiert, um die SEO-URL des Autors des Blogeintrags anzuzeigen.
- Jetzt können Sie Tags für jeden Blogeintrag hinzufügen.
- Neue Registerkarte „Blog-Zuordnung“ auf der Produktdetailseite, um Blogeinträge dem Produkt zuzuordnen.
- Sie haben jetzt 2 Optionen, um die Blogeinträge auf der Produktdetailseite anzuzeigen: Bestimmten Blog zuweisen oder Blog nach Tags zuweisen.
- Es wurde ein neues Konfigurationselement zu `cms-element-blog-assignment` hinzugefügt, um zu entscheiden, ob die Blogeinträge in der Reihenfolge oder zufällig angezeigt werden sollen.

# 3.0.7
- Die Blog-Kategorie Breadcrumb wurde eingeführt.

# 3.0.6
- Implementierte Funktionalität zum Öffnen des Blog-Suchergebnisses beim Eingeben eines Suchbegriffs, sofern die Suchergebniskarten-Konfiguration auf Blog eingestellt ist.
- Hinzugefügt die Anzeige des Blog-Autornamens für den Panel-Filter.

# 3.0.5
- Die Slug-Validierung wurde aktualisiert.

# 3.0.4
- Block zur Blog-Detailseite hinzugefügt, um benutzerdefinierte Inhalte für das Rich-Snippet hinzuzufügen

# 3.0.3
- Das ld+json-Schema für Blogeinträge wurde aktualisiert, um den Autor als Person zu verwenden.

# 3.0.2
- Erlauben Sie das Bearbeiten des Namens der Blogkategorie in mehreren Sprachen.

# 3.0.1
- Problem im Zusammenhang mit der Blog-Kategorie behoben
- Fehler behoben, der im Konsolenprotokoll des Schaufensters angezeigt wurde

# 3.0.0
- Kompatibilität mit Shopware 6.6

# 2.11.9
- Das Problem der Anzeige des alten Slugs auf der Blog-Detailseite wurde behoben.

# 2.11.8
- Problem im Zusammenhang mit der eindeutigen Slug-Beschränkung behoben. Dieses Problem trat nur bei bestehenden Blogs auf, die denselben Slug haben.
- Problem behoben, bei dem das Blog Detail CMS geändert wurde, aber der Cache nicht ungültig gemacht wurde

# 2.11.7
- Fehler behoben, wenn ein neuer Blog in verschiedenen Sprachen, aber mit gleichem Slug erstellt wird.

# 2.11.6
- Der Slug kann jetzt im Blogbeitrag bearbeitet werden.
- Aktualisiertes LD+JSON-Schema für Blogbeitrag.
- Meta-Keywords zum Blogbeitrag hinzugefügt.
- Neue Plugin-Konfiguration hinzugefügt, um den Tab für die Suchergebnisse des Blogs zu priorisieren.

# 2.11.5
- Benutzer sollten die Möglichkeit haben, zwischen zwei Registerkarten (Produkt und Blog) innerhalb der Suchergebnisse zu wechseln.
- Die Funktion des Blog-Suchfelds sollte automatisch deaktiviert werden, wenn es in der Plugin-Konfiguration deaktiviert wurde.

# 2.11.4
- Automatisch die Seitenleiste öffnen, wenn ein neuer Blogbeitrag erstellt wird.
- Beheben Sie das RSS-Feed, um es mit der neuesten SEO-URL des Blogs auf dem neuesten Stand zu halten.
- Beheben Sie den Fehler beim Anzeigen des Blog-RSS-Feeds mit Sonderzeichen.

# 2.11.3
- Fehler behoben beim Anzeigen des Blog-RSS-Feeds

# 2.11.2
- Fehler behoben beim migrieren von `Migration1609140710AddCmsPdpLayout` seit 2.11.0

# 2.11.1
- Aktualisieren Sie das Blog-Slider Plugin

# 2.11.0
- Neue Funktion hinzugefügt, um bestimmte Blog-Beiträge auf einer Produkt-Detailseite anzuzeigen.
- Lizens auf proprietary geaendert

# 2.10.0
- Überprüfte die Liste der Blog-IDs, bevor der Cache ungültig gemacht wurde.

# 2.0.9
- Setzen Sie die Begrüßung des Blog-Autors als nullable, um die Kompatibilität mit Shopware 6.5 zu gewährleisten und Fehler bei einem Upgrade von Shopware 6.4 auf 6.5 zu verhindern.
- Leeren Sie den Cache, wenn ein Blog-Eintrag bearbeitet wird.

# 2.0.8
- Gelöst für das Problem [#206](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/206)

# 2.0.7
- Gelöst für das Problem [#201](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/201)

# 2.0.6
- Gelöst für das Problem [#201](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/201)
- Gelöst für das Problem [#200](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/200)
- Gelöst für das Problem [#199](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/199)
- Gelöst für das Problem [#195](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/195)

# 2.0.5
- Ein Fehler wurde behoben, der dazu führte, dass der Blog-Inhalt im Storefront dupliziert wurde.

# 2.0.4
- Das Sidebar-Snippet wurde aktualisiert, um mit der neuen Struktur der Schnipsel von Shopware 6.5 kompatibel zu sein.

# 2.0.3
- Fehler behoben, dass die SEO-URL-Vorlage nach dem Entfernen des Plugins nicht gespeichert werden kann, ohne das Kontrollkästchen `Remove all app data permanently` zu aktivieren

# 2.0.2
- Fixed composer version

# 2.0.1
- Fixed `Replace` im CMS-Designer nicht angezeigt wurde

# 2.0.0 
-  Feature hinzugefügt: Blog Artikel können nun mittels CMS Designer gestaltet werden

# 1.5.15

- Fixed `Serialization of 'Closure' is not allowed` während der Komprimierung von Cache-Daten in den Klassen `CachedBlogController`, `CachedBlogRssController` und `CachedBlogSearchController`.
- Entfernen der nicht existierenden Funktion `onUpdateCacheCategory` in der Klasse `BlogCacheInvalidSubscriber`.
- Fallback `meta title` und `meta description` auf die Hauptsprache, wenn keine Übersetzung gefunden wird.

# 1.5.14

- Entfernen des feature flags FEATURE_SAS_BLOG_V2
- Entfernen des gesamten Codes im Zusammenhang mit FEATURE_SAS_BLOG_V2

# 1.5.12

- Bug behoben, dass wenn man das SEO Template ändert - die Änderungen erst greifen, nachdem man einen Blog Artikel erneut gespeichert hat
- Fixed BlogSubscriber um nach Autoren & Kategorien um Listing zu suchen
- Problem behoben, dass den Autor & Kategorie nicht im Blog Beitrag anzeigt.
- Der Cache des Blog Artikels wird gelöscht, sobald dieser aktualisiert oder gelöscht wird.

# 1.5.11

- Ein Bug innerhalb der SEO Templates wurde gefixed.

# 1.5.10

- Fix zum Erstellen neuer SEO-URLs für neue Vertriebskanäle [#75](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/75/files)
- Ein Feature-Flag wurde hinzugefügt: FEATURE_SAS_BLOG_V2

# 1.5.9

- Beheben der vorgegebenen Thumbnail-Größen [#110](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/110/files)
- Newest blog items [#108](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/108/files)
- Fix Block is overwritten instead of added [#116](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/116/files)
- Beheben Sie das Problem mit der Sitemap:generate [#75](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/issues/75/files)

# 1.5.8

- Fix error when run bin/console dal:validation [#97](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/97/files)
- Fix SERP Information and Preview Picture for shopware 6.4.9.0 or later [#100](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/100/files)

# 1.5.7

- Fixed compiled administration file

# 1.5.6

- Integrated ACL [#84](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/84/files)
- Sitemap generation [#78](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/78/files)
- Fixed wrong slug in Umlaute [#83](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/83/files)
- Fixed duplicate Slug [#76](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/76/files)
- Added a plugin option to make the blog search optional [#77](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/77/files)
- RSS Feed - URL: `/blog/rss` [#79](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/79/files)
- Fixed a "back" icon [#80](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/80/files)
- Change `createdAt` to `publishDate` [#81](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/81/files)
- Added SEO Canonical-Link [#82](https://github.com/ChristopherDosin/Shopware-6-Blog-Plugin/pull/82/files)

# 1.5.5

- change access to global Shopware object [wrongspot](https://github.com/wrongspot)
- set navigation id by active navigation [Drumm3r](https://github.com/Drumm3r)

# 1.5.4

- Fixed blog pro loading

# 1.5.3

- Added missing compiled files

# 1.5.2

- Blog Pro compability

# 1.5.1

- Extended the core search, to be able to search also for blog entries
- Fixed bug for the PWA usage

# 1.5.0

- Added ApiAware flags & store-api controller for usage in PWA and Elasticsearch [Drumm3r](https://github.com/Drumm3r)
- Implemented custom fields [gRoberts84](https://github.com/gRoberts84)
- Fixed Bug which caused that you can't create a blog category anymore

# 1.4.1

- fixed migration issue with the salutation if the default `not_specified` was deleted

# 1.4.0

- Added Shopware 6.4 compability

# 1.3.1

- Added DESC sorting to admin blog listing

# 1.3.0

- Added enable/disable filter for author & category to the blog listing element
- Added enable/disable filter for author & category to the blog detail element
- Added a new `publishedAt` date field for a blog entry
- Added back button to blog detail page
- Centered blog detail post with `col-md-8 offset-md-2`\_

# 1.2.13

- Added category slug in seo url template

# 1.2.12

- fixed single select element if it's `null`

# 1.2.11

- Der Autor kann im Listing durch die Plugin Konfiguration aktiviert oder deaktiviert werden
- Twig Block `sas_blog_card_footer` und `sas_blog_card_footer_author` hinzugefügt
- Styling Fehler bei leeren Placeholder Autor Image behoben

# 1.2.10

- Storefront lokales datum fix
- Autor in der Storefront hinzugefügt
- Generelle Überarbeitung des Stylings im Listing

# 1.2.9

- Teaser Thumbnail Größe angepasst

# 1.2.8

- Strukturierte Daten Autor Fehler behoben
- read more title der Beitrags ersetzt durch title

# 1.2.7

- Fehler behoben, welcher auftrat, wenn man das Blog listing + Blog detail Element auf derselben CMS Seite hat
- Überflüssigen Twig Filter entfernt
- Single Select Data Resolver verschoben

# 1.2.6

- Single Select blog CMS Element & Block verbessert

#1.2.5

- Datumsausgabe im Blog Box Template angepasst

# 1.2.4

- Es können nun auch Kategorien in einer non-system Sprache (Default Sprache welche während der Installation angegeben wird) übersetzt werden

# 1.2.3

- Es wird nun dir Systemsprache beim erstellen eines Artikels geprüft, welches zuvor zu Fehlern führte,
  da Einträge immer zuerst in der Systemsprache angelegt werden müssen.

# 1.2.2

- Fehler der Übersetzung der Kategorie im Twig Template behoben

# 1.2.1

- editor.js entfernt
- fehlende Beschreibung Übersetzung hinzugefügt

# 1.2.0

- Replaced editor.js with Shopware's default text editor
- Added `Blog Single Select` CMS element, to show a single blog entry card
- Added counter for meta title and description
- Fixed JSON-LD SEO template
- Prepared plugin base for the Blog PRO version

# 1.1.5

- Added missing German author translations

# 1.1.4

- Added author entity
- Added category entity

# 1.1.3

- changed `SalesChannelCmsPageLoader` to `SalesChannelCmsPageLoaderInterface` in `BlogController`

# 1.1.2

- fixed Blog Plugin kills all meta-Infos from all other pages #20
- fixed Stay on blogpost when saving #14
- fixed Error 500 when using «Raw HTML» in Editor #9

# 1.1.1

- fixed license to MIT

# 1.1.0

- Added teaser image within listing
- fixed error messages if title or slug is empty
- added publish date to listing card

# 1.0.0

- Erster Release im Store
