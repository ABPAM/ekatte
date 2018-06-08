<?php

/**
 * Този файл е част от пакета ЕКАТТЕ.
 *
 * (c) 2018 - Emil Avramov
 *
 * Този клас съдържа функциите, нужни за обновяване на БД с населени места
 * по ЕКАТТЕ, в съответсвие с най-скорошните версии на класификатора, предоставени
 * от НСИ (Национален Статистически Институт). 
 *
 * Обект от клас Update() се създава във файл updater.php и е желателно да не бъде
 * създаван на друго място, както и да не бъде променяно съдържанието на настоящия
 * файл или на updater.php.
 *
 */


    namespace Ekatte;

    use ZipArchive;
    use SpreadsheetReader;

    class Update
    {

        /**
         * Изтегляне на отдалечен файл в локалната файлова система.
         *
         * @param String $url  URL адресът на файла, който да бъде изтеглен
         * @param String $path Път до мястото, където да бъде изтеглен файла
         *
         * @return Boolean Резултат от изтеглянето на $url в $path.
         *
         */

        public function downloadFile($url, $path)
        {
            //Отваряне на отдалечения файл и вземане на размера му.
            $remoteFile = fopen ($url, 'rb');
            $remoteFileSize = $this->getRemoteFilesize($url);
            
            if ($remoteFile == true) { //Ако отварянето на отдалечения файл е успешно.

                //Отваряне на локален файл в два отделни режима - запис и четене.
                $localFile      = fopen ($path, 'wb');
                $localFileDone  = fopen ($path, "rb");

                if ($localFile == true) { //Ако локалния файл е отворен (или създаден) успешно.

                    while(!feof($remoteFile)) {

                        //Проверка за размера на изтегленото.
                        fseek($localFileDone, 0, SEEK_END);

                        //Показване на прогрес-бара.
                        print $this->progressBar(ftell($localFileDone), $remoteFileSize);
                        
                        //Записване на 1 килобайт от отдалечения файл в локалната файлова с-ма.
                        fwrite($localFile, fread($remoteFile, 1024 * 8), 1024 * 8);
                    }
                } else { //Ако локалния файл не е отворен успешно.

                    return false;
                }

                //Затваряне на всички отворени файлове.
                fclose($localFile);
                fclose($localFileDone);
                fclose($remoteFile);

            } else { //Ако не е отворен успешно отдалеченият файл (Грешка в свързването с НСИ).
                return false;
            }

            //Ако всичко е наред връща истина
            return true;
        }

        /**
         * Определяне размера на отдалечен файл.
         * 
         * @param String $fileUrl Адрес на отдалечения файл.
         *
         * @return Integer Размер на отдалечения файл.
         *
         */

        private function getRemoteFilesize($fileUrl)
        {
            //Приравняване на всички елементи от хедъра в малки букви.
            $head = array_change_key_case(get_headers($fileUrl, 1));
            
            //Вземане на размера на отдалечения файл. 
            //Ако един от хедърите е content-length, се взема стойността му.
            //Ако такъв елемент в хедъра отсъства, се взема 0.
            $size = isset($head['content-length']) ? $head['content-length'] : 0;

            return $size;
        }

        /**
         * Създаване на прогрес-бар. 
         *
         * @param Integer $done  Моментен коефициент на завършване на процеса.
         * @param Integer $total Общ коефициент, който да бъде достигнат.
         * @param String $info   [optional] Информативен текст.
         * @param Integer $width [optional] Ширина на прогрес-бара в брой символи
         *
         * @return String Прогрес-бар, в конзолна среда.
         *
         */

        private function progressBar($done, $total, $info="", $width=50)
        {
            $perc = round(($done * 100) / $total);
            $bar  = round(($width * $perc) / 100);
         
            return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
        }


        /**
         * Създаване на ZIP архив от директория.
         *
         * @param String $dir  Директория, която да бъде компресирана в .zip файл.
         * @param String $dest Път до новия .zip файл.
         *
         * @return Boolean True при правилно изпълнен процес, False при неправилно.
         *
         */

        public function addToZip($dir, $dest)
        {
            //Създаване на обект ZipArchive
            $db = new ZipArchive;

            //Вземане на списък с файлове и подпапки в директорията, 
            //която ще се компресира в ZIP архив.
            $list = $this->getDirContents($dir);

            //Създаване на нов ZIP архив.
            $db->open($dest, ZipArchive::CREATE);
            
            foreach ($list as $file) {
                
                //Добавяне в архива на всеки елемент от списъка $list.
                $db->addFile($file);
            }

            //Затваряне на архива.
            $db->close();
        }

        /**
         * Разархивиране на ZIP архив.
         *
         * @param String $file Път до архивния файл, който да бъде РАЗархивиран.
         * @param String $dest Път до мястото за разархивиране.
         *
         * @return Boolean True при правилно изпълнен процес, False при неправилно.
         *
         */

        public function extractZip($file, $dest)
        {
            //Създаване на обект ZipArchive.
            $zip = new ZipArchive;
            
            //Опит за отваряне на архива.
            $result = $zip->open($file); 
            
            if($result == true) { //Ако опитът за отваряне е успешен.
                //Разхивиране на архива в директория $dest.
                $zip->extractTo($dest);

                //Затваряне на архива.
                $zip->close();
            } else {
                return false;
            }

            return true;
        }


        /**
         * Създаване на списък с файлове и подпапки, намиращи се в папка.
         * 
         * @param String $dir Директория, за която да се създаде списък.
         * @param String $filter [optional] Филтър на имената на файловете и 
         *                                  подпапките в директорията. Филтрират
         *                                  се имената, които НЕ отговарят на филтъра.
         *
         * @param Array $results [inherited] Използва се за рекурсия на функцията.
         *
         * @return Array Списък с файлове и подпапки.
         *
         */

        private function getDirContents($dir, $filter = '', &$results = array())
        {
            //Създаване на списък с файлове и подпапки в папка $dir
            //и премахване на текуща и родителска папка от списъка.
            $files = array_diff(scandir($dir), ['.', '..']);
            sort($files);


            foreach($files as $file) {
                
                $path = $dir.DIRECTORY_SEPARATOR.$file; 
                
                if(!is_dir($path)) { //Ако даденият елемент не е папка.

                    //Ако не е зададен филтърен шаблон или името на елемента отговаря
                    //на филтърния шаблон...
                    if(empty($filter) || preg_match($filter, $path)) {
                        //...добавя елемента към крайния списък.
                        array_push($results, $path);
                    }
                } else { //Ако даденият елемент Е папка.
                    //Изпълняваме рекурсивно настоящата функция за папката-елемент.
                    $this->getDirContents($path, $filter, $results);
                }
            }

            return $results;
        }


        /**
         * Изтрива съдържанието на дадена директория, в това число - файлове и под-
         * директории.
         *
         * @param String $path Път до папката, която да се изтрие.
         *
         * @return Boolean
         *
         */
        public function deleteDir($path)
        {
            if(is_dir($path)) { //Ако $path е папка.
                //Създава се списък на файловете и подпапките в $path, 
                //като се изключват текущата и родителска папки.
                $contents = array_diff(@scandir($path), ['.', '..']);
                
                if($contents) {//Ако списъкът е създаден
                    
                    //Пресортиране на елементите в списъка.
                    sort($contents);

                    foreach ($contents as $element) {
                        if(is_dir($path.'/'.$element)) {//Ако даденият елемент е папка.
                            //Рекурсивно изпълняваме функцията, задавайки конкретния елемент
                            $this->deleteDir($path.'/'.$element);

                        } else {//Ако не е папка.
                            //Изтриваме елемента като обикновен файл.
                            unlink($path.'/'.$element);
                        }
                    }

                    //Поради изпълнението на горния цикъл, не следва да има файлове, 
                    //затова изтриваме папката по традиционния начин.
                    rmdir($path);
                }
            }

            return true;
        }


        /**
         * Форматилане на данни, взети от XLS файл в CSV формат.
         *
         * @param String $xlsFile Път до .xls файла, който съдържа данните.
         * @param RegExp $pattern Шаблон, по който да се филтрират нужните данни.
         *
         * @return Array
         *
         */
        private function XLStoCSV($xlsFile, $pattern)
        {
            //Създаване на нов обект от тип SpreadsheetReader.
            $xls = new SpreadsheetReader($xlsFile);

            //Саздава се празна променлива, в която да се натрупва информация
            //в CSV формат.
            $csv = '';

            foreach ($xls as $row) {
                foreach ($row as $cell) {
                    $csv .= $cell.',';
                }

                $csv = substr($csv, 0, -1).'\n';
            }

            $csv = explode('\n', $csv);

            $csv = preg_grep($pattern, $csv);

            return $csv;
        }


        /**
         * Попълване на БД с данни за области.
         *
         * @param String $tmpDir Път до временната директория, в която е 
         *                       разархивиран файлът от НСИ.
         *
         * @param String $db     Път до временната директория, съдържаща файловете, 
         *                       които да бъдат компресирани в БД.
         *
         * @return Boolean
         *
         */

        public function oblastiDB($tmpDir, $db)
        {
            //Вземане на информация за области в CSV формат
            $csv = $this->XLStoCSV($tmpDir.'/xls/Ek_obl.xls', "/^[A-Z]{3},[0-9]{5},(\(?\pL{1,}\)?\s?\(?\pL{1,}\)?),[A-Z]{2}[0-9]{2},[0-9]{4},[0-9]{1,}$/um");

            $content = "";
            
            //Създаване/отваряне на файл-списък в режим „запис“
            $oblastiList = fopen($db.'/oblasti.txt', 'w');

            foreach ($csv as $oblast) {
                $parameter = explode(',', $oblast);

                $content .= $parameter[0].PHP_EOL;
             
                if(!is_dir($db.'/'.$parameter[0])) {
                    
                    mkdir($db.'/'.$parameter[0]);

                    $dbFile = fopen($db.'/'.$parameter[0].'/oblast.txt', 'w');
                    fwrite($dbFile, $oblast);
                    fclose($dbFile);
                }
            }

            $content = substr($content, 0, strrpos($content, PHP_EOL));

            fwrite($oblastiList, $content);

            fclose($oblastiList);
        }



        /**
         * Попълване на БД с данни за общини.
         *
         * @param String $tmpDir Път до временната директория, в която е 
         *                       разархивиран файлът от НСИ.
         *
         * @param String $db     Път до временната директория, съдържаща файловете, 
         *                       които да бъдат компресирани в БД.
         *
         * @return Boolean
         *
         */

        public function obshtiniDB($tmpDir, $db)
        {

            $csv = $this->XLStoCSV($tmpDir.'/xls/Ek_obst.xls', "/^[A-Z]{3}[0-9]{2},[0-9]{5},([\pL]{1,}\s?-?[\pL]{1,}),[0-9]{1},[0-9]{4},[0-9]{1,}$/mu");

            foreach ($csv as $obshtina) {

                $parameter = explode(',', $obshtina);
                $oblast = substr($parameter[0], 0, 3);
                
                if(!is_dir($db.'/'.$oblast.'/'.$parameter[0])) {
                    
                    mkdir($db.'/'.'/'.$oblast.'/'.$parameter[0]);

                    $dbFile = fopen($db.'/'.$oblast.'/'.$parameter[0].'/obshtina.txt', 'w');
                    fwrite($dbFile, $obshtina);
                    fclose($dbFile);
                }
            }
        }



        /**
         * Попълване на БД с данни за селища.
         *
         * @param String $tmpDir Път до временната директория, в която е 
         *                       разархивиран файлът от НСИ.
         *
         * @param String $db     Път до временната директория, съдържаща файловете, 
         *                       които да бъдат компресирани в БД.
         *
         * @return Boolean
         *
         */

        public function selishtaDB($tmpDir, $db)
        {
            $csv = $this->XLStoCSV($tmpDir.'/xls/Ek_atte.xls', "/^[0-9]{5},[\pL]{1,}.,([\pL]{1,}\s?[\pL]{1,}),[A-Z]{3},[A-Z]{3}[0-9]{2},[A-Z]{3}[0-9]{2}-[0-9]{1,},[0-9]{1},[0-9]{1},[0-9]{1},[0-9]{4},[A-Z]{1,},[0-9]{1,4}$/mu");

            foreach ($csv as $selishte) {
                
                $parameter = explode(',', $selishte);
                $oblast = $parameter[3];
                $obshtina = $parameter[4];

                if(!file_exists($db.'/'.$oblast.'/'.$obshtina.'/'.$parameter[0])) {
                    
                    $dbFile = fopen($db.'/'.$oblast.'/'.$obshtina.'/'.$parameter[0].'.txt', 'w');
                    $selishte = ''.$parameter[1].' '.$parameter[2].','.$parameter[5];
                    fwrite($dbFile, $selishte);
                    fclose($dbFile);
                }
            }
        }
    }
