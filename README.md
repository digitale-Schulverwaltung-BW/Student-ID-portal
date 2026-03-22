# Digitaler Schülerausweis (Google, Apple) mit WalletStudentID

Dieses Projekt stellt eine Schnittstelle zwischen der Schule/dem Schulverwaltungsprogramm und dem [WalletStudentID](https://gitlab.hhs.karlsruhe.de/digitale-schulverwaltung/walletstudentid) zur Ausgabe von Apple/Google-Wallet-Schülerausweisen dar.

Installation und Benutzeranleitung finden sich im [Wiki](https://gitlab.hhs.karlsruhe.de/digitale-schulverwaltung/student-id-portal/-/wikis/home).

Die folgenden Schaubilder stellen die unterstützten Vorgänge schematisch dar:

## Registrierung
Die Schülerausweise können über eine ID, welche etwa aus dem Schulverwaltungsprogramm automatisch erzeugt wird, registriert werden. Es sind keine vorbereitenden Import- und Re-Import-Läufe erforderlich:

![Registierung: 1. Abscannen QR-Code, 2. Auswahl Wallet-Typ, 3. Ausweis in Wallet](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/4ae4d830696a7067f01ae05e869696f7/Registrierung.png)

## Verifizierung
Eine Überprüfung des Ausweises erfolgt über die Schulhomepage durch einen nachgelagerten Server, auf dem eine aktuelle Schülerliste aus dem Schulverwaltungsprogramm hinterlegt ist. Es werden hierbei keinerlei Schülerdaten über das Internet übertragen.

![Verifizierung: 1. Abscannen QR-Code Ausweis, 2. Bestätigungsseite auf Schul-Homepage](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/a14fa7a27770a0d1e8d014108b9a3585/Verifizierung.png)

## Vorteile
Der Vorteil des Systems ist, dass nicht der ganze Datenbestand aller Schülerinnen und Schüler in WalletStudentID, Apple und Google importiert werden muss, sondern nur die Datensätze in abgerufen werden gelangen, deren Nutzer diesem ausdrücklich zugestimmt haben und die den digitalen Schülerausweis auch nutzen wollen.

Die verify-Funktion stellt einen weiteren Vorteil für eine bessere Akzeptanz der digitalen Schülerausweise dar. Wird der Ausweis-QR-Code gescannt, so erfolgt eine Gültigkeitsprüfung auf der Schulhomepage. Diese kann (je nach Aktualität des Datenexports aus dem Schulverwaltungsprogramm) tagesaktuell erfolgen.

## Architektur
Um möglichst keine Schülerdaten auf exponierten Servern lagern zu müssen, ist die Architektur des Registrierungssystems so aufgebaut, dass ein nachgelagerter Server die Schülerliste verwaltet und dem Frontend auf der Homepage lediglich Informationen über die Schulzugehörigkeit mitteilt. Ebenso erfolgt die Registrierung eines Wallet-Passes in WalletStudentID aus dem nachgelagerten System.

![Systemarchitektur](https://gitlab.hhs.karlsruhe.de/Seyfried/student-id-kortpress/-/wikis/uploads/f364f8768044c4841c524a7b73b89253/Architektur.svg)

*Performance:* ein erfolgreicher Verify-Aufruf benötigt 22ms ohne Webserver-Overhead. Verify-Aufrufe auf nicht existierende IDs werden per rate limit gedrosselt. Neue Registrierungs-Aufrufe sind durch den nachgelagerten Web-Request auf die WalletStudentID- und Google/Apple-Server langsamer. Registierungs-Aufrufe auf bereits ausgestellte Passes werden durch den Datenbank-Abruf auch innerhalb von 22ms bereit gestellt. Der Abruf der Download-URLs beinhaltet dann wieder einen nachgelagerten Web-Request.