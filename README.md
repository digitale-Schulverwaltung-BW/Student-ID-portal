# Digitaler Schülerausweis (Google, Apple, PDF) mit Kortpress

Dieses Projekt stellt eine Schnittstelle zwischen der Schule/dem Schulverwaltungsprogramm und dem Kortpress-System zur Ausgabe von Apple/Google-Wallets oder PDF-Schülerausweisen dar.

Die folgenden Schaubilder stellen die unterstützten Vorgänge schematisch dar:

## Registrierung
Die Schülerausweise können über eine ID, welche etwa aus dem Schulverwaltungsprogramm automatisch erzeugt wird, registriert werden. Es sind keine vorbereitenden Import- und Re-Import-Läufe erforderlich:

![Registierung: 1. Abscannen QR-Code, 2. Auswahl Wallet-Typ, 3. Ausweis in Wallet](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/4ae4d830696a7067f01ae05e869696f7/Registrierung.png)

## Verifizierung
Eine Überprüfung des Ausweises erfolgt über die Schulhomepage anhand einer aktuellen Schülerliste aus dem Schulverwaltungsprogramm.

![Verifizierung: 1. Abscannen QR-Code Ausweis, 2. Bestätigungsseite auf Schul-Homepage](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/a14fa7a27770a0d1e8d014108b9a3585/Verifizierung.png)

## Vorteile
Der Vorteil des Systems ist, dass nicht der ganze Datenbestand aller Schülerinnen und Schüler in Kortpress importiert werden muss, sondern nur die Datensätze in Kortpress gelangen, deren Nutzer diesem ausdrücklich zugestimmt haben und die den digitalen Schülerausweis auch nutzen wollen.

Die verify-Funktion stellt einen weiteren Vorteil für eine bessere Akzeptanz der digitalen Schülerausweise dar. Wird der Ausweis-QR-Code gescannt, so erfolgt eine Gültigkeitsprüfung auf der Schulhomepage. Diese kann (je nach Aktualität des Datenexports aus dem Schulverwaltungsprogramm) tagesaktuell erfolgen.

## Architektur
Um möglichst keine Schülerdaten auf exponierten Servern lagern zu müssen, ist die Architektur des Registrierungssystems so aufgebaut, dass ein nachgelagerter Server die Schülerliste verwaltet und dem Frontend auf der Homepage lediglich Informationen über die Schulzugehörigkeit mitteilt. Ebenso erfolgt die Registrierung eines Wallet-Passes in Kortpress aus dem nachgelagerten System.

![Systemarchitektur](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/f364f8768044c4841c524a7b73b89253/Architektur.svg)