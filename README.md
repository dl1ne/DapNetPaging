# DapNetPaging

Ich habe diese API bzw. PHP Klasse geschrieben, um eigenen Scripten und Anwendungen eine einfache Möglichkeit zu geben, einen Funkruf über das DapNet abzusetzen.
Die Klasse enthält dabei auch einige Prüfungen und Funktionen, sodass man sich in seinen Scripten nur noch um den eigentlichen Ruf kümmern muss.

## Beispiele
Einfachen Ruf an einen Empfänger absetzen:
```
<?php
require("./DapNetPaging.Class.php");
$demo = new DapNetPaging("your-callsign", "your-password");
$demo->page_users(array("dl1ne"), "testruf via script");
```

Anschließend kann man den Ruf, bzw. das Ergebnis der Klasse auch abrufen und in seinen Scripten verwerten. Dieses ist zu finden als:
```
print($demo->result);
```

## Core-Auswahl
Es kann bei der Instanzierung auch direkt ein DapNet Core mit angegeben werden:
```
<?php
require("./DapNetPaging.Class.php");
$demo = new DapNetPaging("your-callsign", "your-password", "http://dapnet.core/api");
```
ggf. muss hier noch ein entsprechender Port hinzugefügt werden.

## Auto-Failover
In der PHP Klasse ist ein Auto-Failover für die DapNet Core's implementiert. Das bedeutet, es wird bei jedem ersten erfolgreichen Verbindungsaufbau zu einem Core die Liste aller verfügbaren Core's abgerufen und lokal zwischengespeichert (var $dapnet_ini = "./DapNetPaging.ini";).
Mithilfe dieses Caches findet bei einem fehlerhaften API-Request ein Failover statt:
1.) Funkruf kann nicht an den angegebenen Master abgesetzt werden
2.) Mithilfe des Caches werden alle (bekannten) Cores nun mit dem Request durchgearbeitet, bis einer eine positive Rückmeldung gibt

## Debug
In der Klasse ist ein sehr einfaches Debug vorhanden. Dies kann mithilfe von
```
$demo->debug = true;
```
aktiviert werden. 

## Benutzerprüfung
Weiterhin ist es möglich, zu prüfen, ob ein Empfänger überhaupt im DapNet existiert:
```
if ( $demo->isUserExisting($callsign) ) {
  echo "Benutzer existiert!";
  }
```

