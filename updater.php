<?php

/**
 * Този файл съдържа скрипт, създаващ обект от клас Update, служещ за обновяване
 * съдържанието на локалната БД (ekatte_db.zip), в съответсвие с най-скорошните
 * данни на класификатора, издадени от НСИ.
 * Желателно е съдържанието на този файл да не се променя.
 *
 * Даденият скрипт изтрива временните файлове и директории, създадени при предходно
 * обновяване (ако такива са налице) и изтегля файла Еkatte.zip от сървърите на 
 * НСИ, след което разархивира този файл, както и съдържащия се в него Ekatte_xls.zip 
 * във временно създадена директория ekatteTMP. След обработка на .xls файловете, 
 * съдържащи се в директорията и привеждането на данните от тях в удобен за работа 
 * формат (CSV), бива създадена файлова структура, съдържаща информацията нужна 
 * за работа на пакета ЕКАТТЕ. Цялата БД в суров вид (файловата структура) бива 
 * компресирана в ZIP архив (ekatte_db.zip).
 * След завършване на архивирането, скриптът изтрива всички създадени временни
 * файлове и директории.
 *
 */

    namespace Ekatte;

    require __DIR__.'/../../autoload.php';

    use Ekatte\Update;
    use Ekatte\Selishte;


    //Създаване на обект Update.
    $updater = new Update();


    //Дефиниране на някои променливи, участващи по-нататък в процесът на обновяване

    //URL адресът на Ekatte.zip, съхраняван в сървърите на НСИ.
    $ekatteURL = "http://www.nsi.bg/sites/default/files/files/EKATTE/Ekatte.zip";
    //Името на временния файл, изтеглен от НСИ.
    $ekatteZIP = "Ekatte.zip";
    //Името на временната директория, в която да бъде разархивиран $ekatteZIP.
    $ekatteTMP = "ekatteTMP";
    //Името на новосъздадения файл, изпълняващ роля на БД.
    //Същата променлива служи и за име на временната директория, в която се подготвя БД.
    $ekatteDB  = "ekatte_db";

    if(PHP_SAPI != 'cli') { //Средата конзолна ли е?

        print 'Updater is a CLI (console) function and cannot 
               work in a browser. Please use it inside a terminal (command 
               prompt) by typing "php updater.php"';

        die();
    }


    //Изтриване на временния .zip архив, ако съществува такъв.
    @unlink($ekatteZIP);
    //Изтриване на файла-БД.
    @unlink($ekatteDB.'.zip');

    //Изтриване на временната директория, създадена от разархивирането на Ekatte.zip,
    //изтеглен от НСИ, ако такава съществува, както и 
    //изтриване на на временната директория, създадена за подготовка на файла-БД,
    //ако такава съществува.
    $updater->deleteDir($ekatteTMP);
    $updater->deleteDir($ekatteDB);

    //Изтегляне на Ekatte.zip от НСИ.
    $updater->downloadFile($ekatteURL, $ekatteZIP);


    //Разархивиране на файла от НСИ, и разахивиране на съдържащия се в него .zip архив.
    //Изтриване на изтегления .zip файл.
    //Защо от НСИ архивират архив?
    $updater->extractZip($ekatteZIP, $ekatteTMP);
    $updater->extractZip($ekatteTMP.'/Ekatte_xls.zip', $ekatteTMP.'/xls');

    @unlink($ekatteZIP);


    //Създаване на нова директория, за подготовка на БД.
    mkdir($ekatteDB);

    //Попълване на БД.
    $updater->oblastiDB($ekatteTMP, $ekatteDB);
    $updater->obshtiniDB($ekatteTMP, $ekatteDB);
    $updater->selishtaDB($ekatteTMP, $ekatteDB);

    //Архивиране на БД и изтриване на временните директории.
    $updater->addToZip($ekatteDB, 'src/db/'.$ekatteDB.'.zip');

    $updater->deleteDir($ekatteDB);
    $updater->deleteDir($ekatteTMP);

    //Съобщение за завършено обновяване.
    print PHP_EOL.'Обновено...'.PHP_EOL;