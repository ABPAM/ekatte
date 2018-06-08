<?php
/**
 * Този файл е част от пакета ЕКАТТЕ.
 *
 * (c) 2018 - Emil Avramov
 */

    namespace Ekatte;

    use ZipArchive;

    class Obshtina
    {

        /**
         * @var String $ekatteDB Файл, съдържащ БД.
         */
        private $ekatteDB  = __DIR__."/db/ekatte_db.zip";




        /**
         * Списък на всички общини в дадена област.
         *
         * @param String $oblasName Името на областта (трибуквен код), от която 
         *                          да се вземе списък на общините.
         *
         * @return Array Списък на общините в дадена област.
         *
         */

        public function getObshtiniList($oblastName)
        {   
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на ZIP архива.
            $z->open($this->ekatteDB);

            //Създаване на празни масиви, в които ще се трупа информация.
            $vsichkiObshtini = array();
            $obshtini = array();

            for ($i=0; $i < $z->numFiles; $i++) { //За всички файлове и папки в архива.

                //В масива $vsichkiObshtini се записват имената на файловете/папките,
                //взети от поредния номер на файла/папката.
                array_push($vsichkiObshtini, $z->getNameIndex($i));
            }

            //Филтрира се масива $vsichkiObshtini, така че да останат информационните файлове
            //за общините, отговарящи на филтъра (regex).
            $filterObshtini = array_filter($vsichkiObshtini, function($obshtina) use (&$oblastName){
                return preg_match("/ekatte_db\/".$oblastName."\/".$oblastName."[0-9]{2}\/obshtina.txt/", $obshtina);
            });


            //Прилага се функция върху всички елементи, след филтрацията,
            //която да добави елементите в масива, върнат като списък на общините.
            array_walk($filterObshtini, function($obshtina) use ($z, &$oblastName, &$obshtini){

                //Създава се масив от информационния файл на дадена община.
                //и този масив се добавя като елемент на масива, върнат като списък с общини.
                $obshtinaInfo = explode(',', $z->getFromName($obshtina));
                $obshtinaID = $obshtinaInfo[0];
                
                array_push($obshtini, $obshtinaInfo);
            });


            //Затваря се архива.
            $z->close();

            return $obshtini;
        }

        /**
         * Информация за дадена община.
         *
         * @param String $oblastName Името на областта (трибуквен код), в която 
         *                           се намира общината, за която се изисква информация.
         *
         * @param String $obshtinaName Името на общината (трибуквен код на 
         *                             областта + пореден номер на общината), за
         *                             която се изисква информация.
         *
         * @return Array Информация за общината
         *
         */
        public function getObshtina($oblastName, $obshtinaName)
        {
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на архива.
            $z->open($this->ekatteDB);

            //Саздаване на масив от информационния файл за дадената община.
            $obshtina = explode(',', $z->getFromName('ekatte_db/'.$oblastName.'/'.$obshtinaName.'/obshtina.txt'));

            //Затваряне на архива.
            $z->close();

            return $obshtina;
        }
    }
