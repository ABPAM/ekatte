<?php
/**
 * Този файл е част от пакета ЕКАТТЕ.
 *
 * (c) 2018 - Emil Avramov
 */

    namespace Ekatte;

    use ZipArchive;
    use Ekatte\Oblast;
    use Ekatte\Obshtina;


    class Selishte
    {

        /**
         * @var String $ekatteDB Файл, съдържащ БД.
         */
        private $ekatteDB  = __DIR__."/db/ekatte_db.zip";



        /**
         * Списък на всички селища, намиращи се в дадена община или област. 
         *
         * @param String $oblastName Името на областта (трибуквен код), в която
         *                           се намира общината, от която да се изведе
         *                           списък на селищата.
         *
         * @param String $obshtinaName Името на общината (трибуквен код на
         *                             областта + пореден номер на общината), от
         *                             която да се изведе списък на селищата. Ако
         *                             не е зададен този параметър, функциятя връща
         *                             списък на селищата в цялата област (всички общини).
         *
         * @return Array|Boolean Списък на селищата в общината/областта. Или false при грешка.
         *
         */

        public function getSelishtaList($oblastName, $obshtinaName = null)
        {
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на архива.
            $z->open($this->ekatteDB);

            //Проверка дали дадената община съществува в дадената област
            $checkCompat = $z->getFromName('ekatte_db/'.$oblastName.'/'.$obshtinaName.'/obshtina.txt');
            
            if($obshtinaName != null && $checkCompat == false) {
            	return false;
            }

            //Създаване на масиви, в които да се трупа информация.
            $vsichkiSelishta = array();
            $selishta = array();

            for ($i=0; $i < $z->numFiles; $i++) { //За всички файлове и папки в архива.

                //В масива $vsichkiSelishta се записват имената на файловете/папките,
                //взети от поредния номер на файла/папката.
                array_push($vsichkiSelishta, $z->getNameIndex($i));
            }

            //Филтрира се масива $vsichkiSelishta, така че да останат информационните файлове
            //за селищата, отговарящи на филтъра (regex).
            $filterSelishta = array_filter(
                $vsichkiSelishta, 
                function($selishte) use (&$oblastName, &$obshtinaName)
                {
                    if($obshtinaName != null) { //Ако Е зададено да се търси в конкретна община.
                        return preg_match('/ekatte_db\/'.$oblastName.'\/'.$obshtinaName.'\/[0-9]{5}.txt/', $selishte);
                    } else { //Ако не е - търси в цялата област.
                        return preg_match('/ekatte_db\/'.$oblastName.'\/'.$oblastName.'[0-9]{2}\/[0-9]{5}.txt/', $selishte);
                    }
                }
            );

            //Прилага се функция върху всички елементи, след филтрацията,
            //която да добави елементите в масива, върнат като списък на селищата.
            array_walk($filterSelishta, function($selishte) use ($z, &$selishta){

                //Създава се масив от информационния файл на дадено селище.
                //и този масив се добавя като елемент на масива, върнат като списък със селища.
                //Идентификационния номер на селището е идентификационен номер по ЕКАТТЕ
                //(и е взет от името на информационния файл за даденото селище).
                $selishteInfo = explode(',', $z->getFromName($selishte));
                $selishteID = basename($selishte, '.txt');

                $selishta[$selishteID] = $selishteInfo;
            });


            //Затваряне на архива.
            $z->close();

            return $selishta;
        }

        /**
         * Информация за дадено селище.
         *
         * @param String $selishteID Номер по ЕКАТТЕ на селището, информацията
         *                           за което се изисква.
         * 
         * @return Array Информация за селището.
         *
         */

        public function getSelishte($selishteID)
        {
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на архива.
            $z->open($this->ekatteDB);

            //Създаване на празен масив, трупащ елементи като списък на селища.
            $selishtaList = array();

            for ($i=0; $i < $z->numFiles; $i++) { //За всички файлове и папки в архива.

                //В масива $selishtaList се записват имената на файловете/папките,
                //взети от поредния номер на файла/папката.
                array_push($selishtaList, $z->getNameIndex($i));
            }


            //Конкатениране на всички елементи, останали след филтрацията на масива.
            $selishte = implode('', array_filter(
                $selishtaList, 
                function($zapis) use (&$selishteID) {
                    return preg_match('/ekatte_db\/[A-Z]{3}\/[A-Z]{3}[0-9]{2}\/'.$selishteID.'.txt/', $zapis);
                }
            ));


            //Създаване на масив, от информационния файл на даденото селище.
            $selishte = explode(',', $selishteID.','.$z->getFromName($selishte));
    
            //Затваряне на архива.
            $z->close();

            return $selishte;
        }
    }
