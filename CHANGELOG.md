Changelog
=========

Version 0.1.5 - 04.02.2018
------------------------------

### Update & Bugfixes

* Bugfix: Extensionpoints werden nun noch einmalig registriert
* Uploads können ebenfalls automatisch mit dem Eintrag gelöscht werden (yForm-Feldtyp upload)
* Autoencrypt/Autodelete sind nun eigene Klassen


Version 0.1.4 - 27.01.2018
------------------------------

### Update "Autodelete"

* Es ist jetzt möglich, bei yForm-Felder des Typs 'datestamp' einen Zeitraum zu definieren, nach dem sie automatisch gelöscht werden sollen.
* Das Format des datestamps Feldes sollte vorzugsweise Y-m-d H:i:s bzw. 'mysql' oder einfach leer sein.
* Zur regelmäßigen Ausführung ist ein Cronjob bzw. Php-Callback auf cryptor_yform::executeAutodelete() nötig.
* Der cronjob kann auch nur für eine Tabelle getriggert werden, dazu den Tabellennamen mitgeben cryptor_yform::executeAutodelete('my_table_name').


Version 0.1.3 - 23.01.2018
------------------------------

### Updates

* Neu: Plugin für yForm Manager
* – Bei Inserts in eine yForm-Tabelle werden ausgewählte Werte verschlüsselt.
* – Im cryptor_yform Backend können die zu verschlüsselnden Felder ausgewählt werden.
* – Automatisch verschlüsselbare yForm-Feldtypen sind aktuell: text, textarea, email
* – Im yForm-Manager-Backend können verschlüsselte Werte unentschlüsselt dargestellt und editiert werden.
* Optimierung: Verfeinerung der Userrechte bezüglich des neuen Plugins 
* Optimierung: Warnhinweis bei Änderung der cryptor Konfiguration

Version 0.1.2 - 12.01.2018
------------------------------

### Updates

* Neu: Backend-Tool zum manuellen ver-/entschlüsseln
* Optimierung: Code Vereinfachungen
* Optimierung: Sprach-Dateien geupdated

Version 0.1.1 – 11.01.2018
------------------------------

### Updates

* Php 5.3.0 als Minimum
* Openssl als Requirement

Version 0.1-alpha – 11.01.2018
------------------------------

### Inital release

* Aktuell getestet nur mit 'AES-256-CTR' cipher