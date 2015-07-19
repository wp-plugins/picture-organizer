=== OVM Picture Organizer ===
Contributors: RudolfFiedler
Donate link: http://www.picture-organizer.com/donate
Tags: pictures, images, image-license, licenses, stock-images, written warning, abmahnung, quellenangaben, Bildquelle, Bildnachweis, Urheberrecht, Bilder, Fotos, fotolia, aboutpixel, 123rf,pixelio 
Requires at least: 4.0
Tested up to: 4.2.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bildnachweise im Impressum einfach verwalten und anzeigen  - Abmahnungen vermeiden - Never written warnings because of missing picture-credits on your website

== Description ==
= Deutsch =
Viele Webmaster beziehen preisgünstig (oder oft auch kostenlos) Bilder von Image-Stock-Anbietern wie Fotolia, aboutpixel, 123rf.com oder anderen.
Eine Bedingung für die Verwendung dieser Bilder auf Webseiten ist meist die Nennung des Rechteinhabers, entweder direkt bei dem Bild oder im Bereich des Impressums.
Diese Nennung muss für jedes Bld erfolgen, Eine pauschale Nennung z.B. von Fotolia ist nicht ausreichend.
Achtung: Manche Bildanbieter verlangen, dass die Urheberrechtsangabe direkt beim Bild oder auf der gleichen Seite erfolgt.
Diese Funktion wird nur von der Premium-Version des Plugins erfüllt.

Dieses Plugin erzeugt für alle Bildanbieter die Urheberrechtsinformationen, die mit einer Veröffentlichung der Daten im Impressum einverstanden sein.
Zur Darstellung der Bildnachweisdaten direkt auf der Seite der Bilddarstellung benötigen Sie die Premium-Version.


= Englisch =
A lot of webmasters use stockimages from fotolia.com, 123rf.com, aboutpixel and a lot of others picture-sources.
This pictures are not expensive, but the license-infos has one duty for the webmasters:
The webmaster has to publish the copyright of every image he is using.

This plugins takes care of all picture-sellers which accepts the publischin within the legal-Info-site. For sellers who demands the the informations are on the same page as the picture, you need the premium-version of picture organizer.


== Installation ==
= Deutsch =

= Systemvoraussetzungen =
* WordPress 3.8 oder größer
* PHP 5.2 oder größer

= Installation =
= English =
 1. Lade  das Plugin "Picture-Organizer" einfach über die WordPress-Pluginfunktion in Ihre WordPress-Seite (Als Suchbegriff einfach "Picture-Organizer" eingeben.
 2. Aktiviere das Plugin über die Plugin-Übersichtsseite
 3. Füge den Shortcode [ovm_picture-organizer liste] am besten in das Impressum an der Stelle ein, an der die Liste mit den Urheberrechtsdaten gezeigt werden soll.
 4. Das war's auch schon, weitere Einstellungen sind nicht notwendig.
 
 Video-Tutorial Installation, Einrichtung und Handhabung vom Picture-Organizer: http://www.picture-organizer.com/support-2/support/
 

= Englisch =
= Requirements =
* WordPress 3.8 or greater
* PHP 5.2.4 or greater

= Installation =
1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Put the shortcode [ovm_picture-organizer liste] into your imprint-page, where do you want to place this infos, thats all.
4. That it was, there is nothing else to do. You can start with collection the copyright-infos within the media-center, by editing every image.

== Frequently Asked Questions ==
= Deutsch = 

= Wo finde ich den Shortcode für die Ausgabe der Urheberrechtsinfos im Impressum? =

Du findest den Shortcode in der Beschreibung des Plugins auf der Plugin-Seite

= Englisch = 

= Where I can find the shortcode to place the copyright-infos? =

You will find the shortcode in the plugins-description of your plugins page.

== Screenshots ==

1. Plugin-Beschreibung mit Angabe des Shortcodes für die Anzeige der Daten im Inhalt
2. Attachment-Details bei der Neuanlage eines Bildes
3. Nochmal Attachment-Details - Detailansicht
4. Anzeige der Beispieldaten im Frontend





== Changelog ==
= 1.5.1 =
* Fehlerbehebung: Jetzt werden nur noch die Attachments angezeigt, für die ein Lizenzkey eingegeben wurde


= 1.5 =
* Fehlerbehebung: Anzeige von nur 5 Bildnachweisen auf alle geändert
* Verwaltung der CSS-Angabe für die publizierten Bildnachweise eingefügt
* Anstelle der Lizenznummer wird eine Thumbnailversion des Bildes mit Link auf eine größere Version angezeigt
* Ergänzung dynamische Supportseite mit Links zu weiterführenden Seiten
* Vorbereitung Einbindung Premium-Version


= 1.4.3 =
* Ergänzung optonale Promotion über den Bildnachweise-Shortcode im Frontend
* Umstellung der Kontaktaufnahmen mit dem Server - nur noch für Dashboard+Einstellungsseite
* Umstellung diverser globalen Konstanten auf Klasseneigenschaften
 


= 1.4.1 =
* Removing frontend-calls to plugin-website

= 1.3 = 
* Optimize curl-calls
= 1.0 = 
* Secure formfields - validation

= 0.9 = 
* adding uninstall-file to remove all the data during uninstallation of the plugin

= 0.8 = 
* First Version after local tests only with the most important features

== Upgrade Notice ==

= 1.0 = 
* Überarbeitete Optik der Einfabefelder und der Ausgabe über den Shortcode


