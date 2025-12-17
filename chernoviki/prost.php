<?php

// файл для хранения глоссария, И при новом заходе на страницу → JSON читается → таблица появляется снова.
$glossaryFile = 'glossary_data.json';


// массив с терминами
$terms = [];


// // загрузка терминов которые уже есть в glossaryFile
if (file_exists($glossaryFile)) {
   $jsonData = file_get_contents($glossaryFile); // читаем json файл как строку
   $terms = json_decode($jsonData, true) ?? []; // из json строки в php массив, но если json вернут 0 то будет создан пустой массив
}


// МИГРАЦИЯ: добавляем id к существующим записям без него
$maxId = 0;
$needsMigration = false;


foreach ($terms as &$term) {
   if (isset($term['id'])) {
       // Если id уже есть, обновляем максимальный
       if ($term['id'] > $maxId) {
           $maxId = $term['id']; // находим максимальный
       }
   } else {
       // если id нет - это старая запись, нужна миграция!
       $needsMigration = true;
   }
}
unset($term); // Убираем ссылку, так как переменная после цикла foreach все еще существует - если не удалить то term будет ссылаться на последний элемент массива


// Если нужна миграция - добавляем id всем записям без него
if ($needsMigration) {
   // переберем массив и напрямую изменим элемент в terms
   foreach ($terms as &$term) {
       if (!isset($term['id'])) {
           $maxId++;
           $term['id'] = $maxId;
       }
   }
   unset($term);
  
   // Сохраняем обновленный файл
   file_put_contents($glossaryFile, json_encode($terms, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); // флаги (опции) для функции (будем читаемо)


}


// Очистка файла JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {
    $terms = []; // очистка массива
   file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
   header('Location: ' . $_SERVER['PHP_SELF']);
   exit;
}


// ОБРАБОТКА ЗАГРУЗКИ ФАЙЛА
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['glossary_file'])) { // проверяем что форма отправлена + что файл был приложен
   $terms = handleUploaded($terms, $maxId);


   file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); // сохранение обновленного глоссария после обработки


    header('Location: ' . $_SERVER['PHP_SELF']); // зачем этот редирект - нужен! при обновлении страницы не будет показываться уведомление в каких то случаях..
   exit;  

}



// УДАЛЕНИЕ СТРОКИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
   $deleteId = (int)$_POST['delete_id']; // делаем int!
  
   // тут мы фильтруем убирая запись с нужным айди
   $terms = array_filter($terms, function($term) use ($deleteId) {
       return $term['id'] !== $deleteId;
   });


   // это надо чтобы ключи шли правильно (без пропусков -> 0, 1, 2, 3...)
   $terms = array_values($terms);
  


   file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); // сохранение обновленного глоссария после обработки


   header('Location: ' . $_SERVER['PHP_SELF']); // зачем этот редирект - нужен!
   exit;


}


// GET = читать данные (поиск, фильтрация, сортировка)
// POST = изменять данные (добавить, удалить, обновить)




//ДОБАВЛЕНИЕ СТРОКИ

// проверяем режим добавления через GET параметр (работаем с add_mode)
$addMode = isset($_GET['add_mode']) && $_GET['add_mode'] === '1';


// Обработка сохранения нового термина
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_new_term'])) {
   $newTerm = trim($_POST['new_term'] ?? ''); // если НУЛЛ то пустая строка
   $newTranslation = trim($_POST['new_translation'] ?? '');
  
   // Валидация
   if (!empty($newTerm) && !empty($newTranslation)) {
       // Проверка на дубликат
           $maxId++;
          
           // Добавляем в НАЧАЛО массива (moj)
           array_unshift($terms, [
               'id' => $maxId,
               'term' => $newTerm,
               'translation' => $newTranslation
           ]);
          
           // добавляем в джейсон файл
           file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
          
           // Редирект без параметра add_mode
           header('Location: ' . $_SERVER['PHP_SELF']);
           exit;
   }
}


//РЕДАКТИРОВАНИЕ СТРОКИ

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edited'])) {
    $editId = (int)$_POST['change_id'];
    $editedTerm = trim($_POST['edited_term'] ?? '');
    $editedTranslation = trim($_POST['edited_translation'] ?? '');
    
    if (!empty($editedTerm) && !empty($editedTranslation)) {
        // находим и обновляем запись на ту которую ввел пользователь 
        foreach ($terms as &$term) {
            if ($term['id'] === $editId) {
                $term['term'] = $editedTerm;
                $term['translation'] = $editedTranslation;
                break;
            }
        }
        unset($term);
        
        file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Проверяем режим редактирования через GET параметр
$editMode = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;


// ЗАГРУЗКА ФАЙЛА НОВОГО ФАЙЛА
function handleUploaded ($terms, &$maxId) {
   $uploadedFile = $_FILES['glossary_file']; // берем инфу о загруженном файле

  
   if ($uploadedFile['error'] !== UPLOAD_ERR_OK) { // если нет ошибок
       return $terms;
       // $fileTmpPath = $uploadedFile['tmp_name']; // получаем путь к временному файлу
       // $fileContent = file_get_contents($fileTmpPath); // дальше читаем содержимое временного файла
   }


   $fileContent = file_get_contents($uploadedFile['tmp_name']); // читаем содержимое файла


   $parsedTerms = parseCSV($fileContent);


   // Добавляем новые, если не дубль
   foreach ($parsedTerms as $item) {
       if (!isDublicate($item['term'], $terms)) {
           // $terms[] = $item;
           $maxId++;
           $terms[] = [
               'id' => $maxId,  // ← теперь ID добавляется!
               'term' => $item['term'],
               'translation' => $item['translation']
       ];
       }
   }


   return $terms;
}



// ПАРСИНГ CSV файла
function parseCSV($fileContent){
  
   $result = [];


   // разбиваем текст на строки
   $lines = explode("\n", $fileContent); // текст в массив строк
      
   foreach ($lines as $line) {
       $line = trim($line);
       if (empty($line)) continue;




       // Разделяем по запятой
       $parts = explode(',', $line, 2);
       // $parts = str_getcsv($line); // есть такой спосоь еще, но он не хочет работать
      
       if (count($parts) >= 2) {
           $term = trim($parts[0]);
           $translation = trim($parts[1]);


           if ($term !== '' && $translation !== '') {
               $result[] = [
                   'term' => $term,
                   'translation' => $translation
               ];
           }
              
       }
   }
   return $result;
}




// ПРОВЕРКА НА ДУБЛИКАТ
function isDublicate($term, $terms){
   foreach ($terms as $existingTerm) {
       if (strtolower($existingTerm['term']) === strtolower($term)) {
           return true;
       }
   }
   return false;
}




// ФИЛЬТРАЦИЯ (ПОИСК) И СОРТИРОВКА


// Получаем параметры поиска и сортировки от пользователя
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : '';




// Фильтрация терминов (поиск)


// 1 ВАРИАНТ
// $filteredTerms = $terms;  // копируем все термины


// if (!empty($searchQuery)) {
//     $filteredTerms = array_filter($terms, function($item) use ($searchQuery) { // перебираем все элементы массива
//         return stripos($item['term'], $searchQuery) !== false; // с помощью stripos ищем + если нашли то добавляем в новый массив
//     });
// }



// 2 ВАРИАНТ с циклом foreach
$filteredTerms = []; // создаём пустой массив для результатов


if (!empty($searchQuery)) { // проверяем, есть ли что искать
   foreach ($terms as $item) { // перебираем все элементы
       // с помощью stripos ищем + если нашли то добавляем в новый массив
       if (stripos($item['term'], $searchQuery) !== false) {
           $filteredTerms[] = $item;
       }
   }
} else {
   $filteredTerms = $terms;
}



// Сортировка терминов

// 1 ВАРИАНТ
// if (!empty($sortOrder)) { // проверяем можно ли сортировать
//     usort($filteredTerms, function($a, $b) use ($sortOrder) { // используем usort
//         if ($sortOrder === 'desc') {
//             return strcasecmp($b['term'], $a['term']);
//         }
//         return strcasecmp($a['term'], $b['term']);
//     });
// }



// 2 ВАРИАНТ
// if (!empty($sortOrder)) {


//    // Берём массив терминов
//    $termsOnly = array_column($filteredTerms, 'term');


//    // Сортируем строки
//    if ($sortOrder === 'desc') {
//        rsort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // Z → A, без учёта регистра
//    } else {
//        sort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // A → Z
//    }


//    // Создаём новый отсортированный массив
//    $sortedTerms = [];
//    foreach ($termsOnly as $term) {
//        foreach ($filteredTerms as $item) {
//            if (strcasecmp($item['term'], $term) === 0) {
//                $sortedTerms[] = $item;
//                break; // чтобы не добавлять дубликаты
//            }
//        }
//    }


//    $filteredTerms = $sortedTerms;
// }



// 3 ВАРИАНТ - чат предложил решение проблемы с кириллицой и латшыскими буквами
// ===================== КАСТОМНАЯ СОРТИРОВКА =====================

if (!empty($sortOrder)) {

    // нормализация (если есть extension intl)
    $normalize = function($s) {
        if (function_exists('normalizer_normalize')) {
            $n = normalizer_normalize($s, Normalizer::FORM_C);
            if ($n !== false) return $n;
        }
        return $s;
    };

    // вручную описанный порядок букв
    $alphabet = [
        'Ā'=>0.5,'ā'=>0.51,'A'=>1,'a'=>1.01,
        'B'=>2,'b'=>2.01,'C'=>3,'c'=>3.01,'Č'=>3.5,'č'=>3.51,
        'D'=>4,'d'=>4.01,'E'=>5,'e'=>5.01,'Ē'=>5.5,'ē'=>5.51,
        'F'=>6,'f'=>6.01,'G'=>7,'g'=>7.01,'Ģ'=>7.5,'ģ'=>7.51,
        'H'=>8,'h'=>8.01,'I'=>9,'i'=>9.01,'Ī'=>9.5,'ī'=>9.51,
        'J'=>10,'j'=>10.01,'K'=>11,'k'=>11.01,'Ķ'=>11.5,'ķ'=>11.51,
        'L'=>12,'l'=>12.01,'Ļ'=>12.5,'ļ'=>12.51,'M'=>13,'m'=>13.01,
        'N'=>14,'n'=>14.01,'Ņ'=>14.5,'ņ'=>14.51,'O'=>15,'o'=>15.01,
        'P'=>16,'p'=>16.01,'Q'=>17,'q'=>17.01,'R'=>18,'r'=>18.01,
        'S'=>19,'s'=>19.01,'Š'=>19.5,'š'=>19.51,'T'=>20,'t'=>20.01,
        'U'=>21,'u'=>21.01,'Ū'=>21.5,'ū'=>21.51,'V'=>22,'v'=>22.01,
        'W'=>23,'w'=>23.01,'X'=>24,'x'=>24.01,'Y'=>25,'y'=>25.01,
        'Z'=>26,'z'=>26.01,'Ž'=>26.5,'ž'=>26.51,

        // кириллица после латышского алфавита
        'А'=>100,'а'=>100.1,'Б'=>101,'б'=>101.1,'В'=>102,'в'=>102.1,
        'Г'=>103,'г'=>103.1,'Д'=>104,'д'=>104.1,'Е'=>105,'е'=>105.1,
        'Ё'=>106,'ё'=>106.1,'Ж'=>107,'ж'=>107.1,'З'=>108,'з'=>108.1,
        'И'=>109,'и'=>109.1,'Й'=>110,'й'=>110.1,'К'=>111,'к'=>111.1,
        'Л'=>112,'л'=>112.1,'М'=>113,'м'=>113.1,'Н'=>114,'н'=>114.1,
        'О'=>115,'о'=>115.1,'П'=>116,'п'=>116.1,'Р'=>117,'р'=>117.1,
        'С'=>118,'с'=>118.1,'Т'=>119,'т'=>119.1,'У'=>120,'у'=>120.1,
        'Ф'=>121,'ф'=>121.1,'Х'=>122,'х'=>122.1,'Ц'=>123,'ц'=>123.1,
        'Ч'=>124,'ч'=>124.1,'Ш'=>125,'ш'=>125.1,'Щ'=>126,'щ'=>126.1,
        'Ъ'=>127,'ъ'=>127.1,'Ы'=>128,'ы'=>128.1,'Ь'=>129,'ь'=>129.1,
        'Э'=>130,'э'=>130.1,'Ю'=>131,'ю'=>131.1,'Я'=>132,'я'=>132.1
    ];

    // fallback для неизвестных символов
    $fallback = function($ch) {
        return 50 + (mb_ord($ch, 'UTF-8') / 1000000);
    };

    // конвертация строки в массив "весов"
    $toWeights = function($str) use ($alphabet, $fallback, $normalize) {
        $str = $normalize($str);
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($chars as $ch) {
            $out[] = $alphabet[$ch] ?? $fallback($ch);
        }
        return $out;
    };

    // основная сортировка
    usort($filteredTerms, function($a, $b) use ($sortOrder, $toWeights) {

        $wa = $toWeights($a['term']);
        $wb = $toWeights($b['term']);

        // при DESC — просто меняем местами сравниваемые массивы
        if ($sortOrder === 'desc') {
            $tmp = $wa; $wa = $wb; $wb = $tmp;
        }

        // лексикографическое сравнение
        $len = max(count($wa), count($wb));
        for ($i = 0; $i < $len; $i++) {
            $va = $wa[$i] ?? -INF;
            $vb = $wb[$i] ?? -INF;
            if ($va < $vb) return -1;
            if ($va > $vb) return 1;
        }
        return 0;
    });
}




// подсчет
$totalTerms = count($terms);
$filteredCount = count($filteredTerms);



// СКАЧАТЬ НОВЫЙ ФАЙЛ (json уже прочитан)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_csv'])) {
    
    // проверка включен ли режим редактирования или добавления 
    if (isset($_GET['edit_id']) || isset($_GET['add_mode'])) {
        // Перенаправляем обратно, если режим редактирования активен
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Отключаем вывод ошибок (чтобы не попали в CSV)
    ini_set('display_errors', 0);
    
    // Устанавливаем заголовки
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="glossary_' . date('Y-m-d') . '.csv"'); // качает файл не открывая его + задает название 
    header('Pragma: no-cache'); // каждый раз скачиваеться свежия версия (не кэширует файл)
    header('Expires: 0');
    
    // Открываем поток вывода
    $output = fopen('php://output', 'w'); // Пишет напрямую в браузер (не создает файл на сервере), w это перезапись файла
    
    // Записываем каждую строку (с явным указанием параметра escape)
    foreach ($terms as $term) {

    // записываем
    // чистим переносы
    $clean_term = str_replace(["\r\n", "\r", "\n"], '', $term['term']);
    $clean_translation = str_replace(["\r\n", "\r", "\n"], '', $term['translation']);

    // чистим лишние кавычки внутри, чтобы не ломали CSV
    $clean_term = str_replace('"', "'", $clean_term);
    $clean_translation = str_replace('"', "'", $clean_translation);

    // собираем вручную
    $line = $clean_term . ',' . $clean_translation . "\n";

    fwrite($output, $line);
        // fputcsv($output, [$term['term'], $term['translation']], ',', '"', '\\');  - старый варик (он добавлял переносы и кавычки внутри полей)
        // разделитель: запятая
        // обрамление: двойные кавычки! Обрамление - это символ, который оборачивает текст в CSV,
        // если внутри есть специальные символы (запятые, переносы строк, кавычки).
        // экранирование: если внутри текста сами кавычки + обратный слэш (это убирает deprecated warning)
    }
    

    fclose($output); // закрывем поток
    exit; // останавливаем выполнение чтобы html не добавился к csv файлу
}

?>