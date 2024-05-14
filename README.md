# Digitaler Schülerausweis (Google, Apple, PDF) mit Kortpress

Dieses Projekt stellt eine Schnittstelle zwischen der Schule/dem Schulverwaltungsprogramm und dem Kortpress-System zur Ausgabe von Apple/Google-Wallets oder PDF-Schülerausweisen dar.

Das folgende Schaubild stellt die unterstützten Vorgänge schematisch dar. ToDo.

Der Vorteil des Systems ist, dass nicht der ganze Datenbestand aller Schülerinnen und Schüler in Kortpress importiert werden muss, sondern nur die Datensätze in Kortpress gelangen, deren Nutzer diesem ausdrücklich zugestimmt haben und die den digitalen Schülerausweis auch nutzen wollen.

Die verify-Funktion stellt einen weiteren Vorteil für eine bessere Akzeptanz der digitalen Schülerausweise dar. Wird der Ausweis-QR-Code gescannt, so erfolgt eine Gültigkeitsprüfung auf der Schulhomepage. Diese kann (je nach Aktualität des Datenexports aus dem Schulverwaltungsprogramm) tagesaktuell erfolgen.

