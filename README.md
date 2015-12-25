### IP-Symcon Modul // BatterieMonitor
---

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Systemanforderungen](#2-systemanforderungen)
3. [Installation](#3-installation)
4. [Befehlsreferenz](#4-befehlsreferenz)
5. [Changelog](#5-changelog) 

## 1. Funktionsumfang
Dieses Modul liest alle Batterie-Variablen von Aktoren aus, gibt die Informationen in einem Array zurück, stellt alle
Batterie-Aktoren in einer Tabelle dar und erzeugt eine 2. Tabelle mit allen Aktoren die eine leere Batterie haben.

*Aktuell unterstützte Hersteller:**
- HomeMatic
- Z-Wave

**In der Modul-Instanz könnt ihr folgende Einstellungen vornehmen:**
- Hintergrundfarbe (HEX Farbcode)
- Textfarbe (HEX Farbcode)
- Textfarbe OK (HEX Farbcode)
- Textfarbe LEER (HEX Farbcode)
- Textgröße
- Aktualisierungsintervall (std. 21600 Sek = 6 Std)


## 2. Systemanforderungen
- IP-Symcon ab Version 4.x

## 3. Installation
Über die Kern-Instanz "Module Control" folgende URL hinzufügen:

`git://github.com/BayaroX/BY_BatterieMonitor.git`


## 4. Befehlsreferenz
```php
  BMON_Update($InstanzID);
```
Liest alle Batterie-Variablen aus und schreibt die Informationen zu den Batterie-Aktoren in 2 Variablen (HTMLBox).

```php
  BMON_Alle_Auslesen($InstanzID);
```
Liest alle Batterie-Aktoren aus, gibt die Informationen in einem Array zurück und schreibt die Informationen in
eine String-Variable (HTMLBox). Wenn keine Aktoren mit Batterie vorhanden sind, wird "false" zurückgegeben.

```php
  BMON_Leere_Auslesen($InstanzID);
```
Liest alle Batterie-Aktoren mit leeren Batterien aus, gibt die Informationen in einem Array zurück und schreibt
die Informationen in eine String-Variable (HTMLBox). Wenn keine Aktoren mit leeren Batterie vorhanden sind,
wird "false" zurückgegeben.


## 5. Changelog
Version 1.0:
  - Erster Release
