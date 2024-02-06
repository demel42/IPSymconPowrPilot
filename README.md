# IPSymconPowrPilot

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Übernahme der Daten von dem "do it yourself" Smartmeter-Interface _PowrPilot_ von ([stall.biz](https://www.stall.biz/project/der-powrpilot-stromzaehler-smartmeter-interface-fuer-die-hausautomation)).

Getestet mit der PowrPilot-Version **17**.

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0
 - ein PowrPilot-Zählermodul

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](docs/de/img/store_icon.png?raw=true "open store")

Im Suchfeld nun _PowrPilot_ eingeben, das Modul auswählen und auf _Installieren_ drücken.

#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

Die Webconsole von IP-Symcon mit _http://\<IP-Symcon IP\>:3777/console/_ aufrufen.

Anschließend den Objektbaum _öffnen_.

![Objektbaum](docs/de/img/objektbaum.png?raw=true "Objektbaum")

Die Instanz _Modules_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](docs/de/img/Modules.png?raw=true "Modules")

![Plus](docs/de/img/plus.png?raw=true "Plus")

![ModulURL](docs/de/img/add_module.png?raw=true "Add Module")

Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/demel42/IPSymconPowrPilot.git
```

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_.

### b. Einrichtung des Geräte-Moduls

In IP-Symcon nun unterhalb des Wurzelverzeichnisses die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _stall.biz_ und als Gerät _PowrPilot_ auswählen.
Es wird automatisch eine I/O-Instanz vom Type Server-Socket angelegt und das Konfigurationsformular dieser Instanz geöffnet.

Hier die Portnummer eintragen, an die der PowrPilot Daten schicken soll und die Instanz aktiv schalten.

In dem Konfigurationsformular der PowrPilot-Instanz kann man konfigurieren, welche Variablen übernommen werden sollen.

### c. Anpassung des PowrPilot

Der PowrPilot muss in zwei Punkten angepaast werden

- Einrichten der IP von IP-Symcon
```
http://<ip des PowrPilot>/?ccu:<ip von IPS>:
```
- aktivieren der automatischen Übertragung
```
http://<ip des PowrPilot>/?param:12:<port von IPS>:
```
damit schickt der PowrPilot zyklisch die Daten.

Gemäß der Dokumentation sind die 4 Zähler im PowrPilot zu konfigurieren (_Modus_ und _Impuls/Einheit_) sowie ggfs der aktuelle Wert des Zählers einzustellen.

## 4. Funktionsreferenz

## 5. Konfiguration

#### Properties

| Eigenschaft                           | Typ      | Standardwert | Beschreibung |
| :------------------------------------ | :------  | :----------- | :----------- |
| Zähler 1                              | integer  | -1           | Typ des 1. Zählers |
| Zähler 2                              | integer  | -1           | Typ des 2. Zählers |
| Zähler 3                              | integer  | -1           | Typ des 3. Zählers |
| Zähler 4                              | integer  | -1           | Typ des 4. Zählers |

| Typ          | Wert |
| :----------- | :--- |
| undefiniert  | -1 |
| Elektrizität | 0 |
| Gas          | 1 |
| Wasser       | 2 |

In Abhängigkeit von dem ṮTyp_ werden jeweils 2 Variablen angelegt mit dem entsprechenden Datentyp, jeweils ein Zähler und eine Angabe der aktuellen Leistung/Verbrauch.
Falls man die Werte archivieren möchte, ist sinnvollerweise die _Aggregation_ auf _Zähler_ einzustellen.

#### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Integer<br>
PowrPilot.sec,
PowrPilot.Wifi

* Float<br>
PowrPilot.kW,
PowrPilot.kWh,

## 6. Anhang

GUIDs
- Modul: `{3EAACC75-ADCF-AC0E-C663-768ED814A722}`
- Instanzen:
  - PowrPilot: `{0003E135-D3AE-16BD-AA4B-753FACFC58A1}`

## 7. Versions-Historie

- 1.4 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.3 @ 05.12.2023 15:23
  - Fix: fehlerhafte Variablenprofile korrigiert

- 1.2 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.1 @ 04.07.2023 14:44
  - Fix: README korrigiert (fehlende Images)
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.0 @ 09.02.2023 14:43
  - initiale Version
