<?php
/**
 * Този файл е част от пакета ЕКАТТЕ.
 *
 * (c) 2018 - Emil Avramov
 */


    namespace Ekatte;

    use ZipArchive;

    class Oblast
    {
        /**
         * @var String $ekatteDB Файл, съдържащ БД.
         */
        private $ekatteDB  = __DIR__."/db/ekatte_db.zip";


        /**
         * Списък, съдържащ всички области.
         *
         * @return Array Списък на всички области.
         *
         */
        public function getOblastiList()
        {
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на БД архива.
            $z->open($this->ekatteDB);

            //Създаване на масив от имена на области от съдържанието на oblasti.txt
            //(намиращ се в ekatte_db.zip).

            $oblasti = explode(PHP_EOL, $z->getFromName('ekatte_db/oblasti.txt'));


            //Създаване на празна променлива-масив, в която да се трупа 
            //информация за областите.
            $oblastiList = array();

            //Прилагане на функция върху всеки елемент от масив $oblasti.
            array_walk(
                $oblasti, 
                function ($oblast) use ($z, &$oblastiList) {
                    //Създаване на масив от съдържанието на информационния файл за всяка област
                    //и добавянето му към $oblastiList.
                    $oblastInfo = explode(',', $z->getFromName('ekatte_db/'.$oblast.'/oblast.txt'));
                    array_push($oblastiList, $oblastInfo);
                }
            );

            //Затваряне на ZIP архива.
            $z->close();

            return $oblastiList;
        }

        /**
         * Информация за дадена област.
         *
         * @param  String $name Име на областта (трибуквен код)
         *
         * @return Array Информация за дадена област
         *
         */

        public function getOblast($name){
            //Създаване на обект ZipArchive.
            $z = new ZipArchive;

            //Отваряне на БД архива.
            $z->open($this->ekatteDB);

            //Създаване на масив от съдържанието на информационния файл за исканата област.
            $oblast = explode(',', $z->getFromName('ekatte_db/'.$name.'/oblast.txt'));


            //Затваряне на архива.
            $z->close();

            return $oblast;
        }
    }
    