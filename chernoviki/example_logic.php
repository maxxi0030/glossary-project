<!-- ТЕСТ -->


<!-- тут всю логику (функции) которые нужно проверить перенесли сюда  (потом можно будет использовать ее в основном коде nn.php) -->

<?php
$terms = []; // для всех функций



// ======== ФИЛЬТРАЦИЯ ==========

// БЫЛО
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : '';

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



// СТАЛО
function filterGlossary($terms, $searchQuery){
    $filteredTerms = [];
    
    // делаем трим
    $searchQuery = trim($searchQuery);

    // Если поиск пустой — возвращаем всё
    if (empty($searchQuery)) {
        return $filteredTerms = $terms;
    }


    foreach ($terms as $item) { // перебираем все элементы
       // с помощью stripos ищем + если нашли то добавляем в новый массив
       if (stripos($item['term'], $searchQuery) !== false) {
           $filteredTerms[] = $item;
       }
   }

   return $filteredTerms; // возвращаем ВСЕ найденные
}

















// ========== СОРТИРОВКА ==========

// БЫЛО
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : '';


if (!empty($sortOrder)) {


   // Берём массив терминов
   $termsOnly = array_column($filteredTerms, 'term');


   // Сортируем строки
   if ($sortOrder === 'desc') {
       rsort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // Z → A, без учёта регистра
   } else {
       sort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // A → Z
   }


   // Создаём новый отсортированный массив
   $sortedTerms = [];
   foreach ($termsOnly as $term) {
       foreach ($filteredTerms as $item) {
           if (strcasecmp($item['term'], $term) === 0) {
               $sortedTerms[] = $item;
               break; // чтобы не добавлять дубликаты
           }
       }
   }

   $filteredTerms = $sortedTerms;
}


// СТАЛО
function sortGlossary($terms, $sortOrder) {

    // если ниче не выбрано - возвращем как есть
    if (empty($sortOrder)) {
        return $terms;
    }

    // извлекаем только термины для сортировки 
    $termsOnly = array_column($terms, 'term');

    // сортируем
    if ($sortOrder === 'desc') {
       rsort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // Z → A, без учёта регистра
    } else {
       sort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // A → Z
   }

   // создаем новый отсортированный массив
    $sortedTerms = [];
    foreach ($termsOnly as $term) {
        foreach ($terms as $item) {
            if (strcasecmp($item['term'], $term) === 0) {
               $sortedTerms[] = $item;
               break; // чтобы не добавлять дубликаты
            }
        }
    }

    return $sortedTerms;

}












// ========== ДОБАВЛЕНИЕ ТЕРМИНА ==========

// БЫЛО
// проверяем режим добавления через GET параметр (работаем с add_mode)
$addMode = isset($_GET['add_mode']) && $_GET['add_mode'] === '1';


// Обработка сохранения нового термина
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_new_term'])) {
   $newTerm = trim($_POST['new_term'] ?? ''); // если НУЛЛ то пустая строка
   $newTranslation = trim($_POST['new_translation'] ?? '');
  
   // Валидация (в хтмл коде стоит required)
   if (!empty($newTerm) && !empty($newTranslation)) {
       // Проверка на дубликат (убрано)

       
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



// СТАЛО
function addTerm($terms, $newTerm, $newTranslation) {

    // делаем трим
    $newTerm = trim($newTerm);
    $newTranslation = trim($newTranslation);


    // если ничего не заполнено
    if (empty($newTerm) && empty($newTranslation)) {
        return ['success' => false, 'error' => 'Пустые поля'];
    }

    // генерация нового айди

    $maxId = 0; // в nn выше есть
    foreach ($terms as $item) {
        if (isset($item['id']) && $item['id'] > $maxId) { //  isset поставил для подстраховки, вдруг будет запись без айди
            $maxId = $item['id'];
        }
    }
    $maxId++;


    // Добавляем в НАЧАЛО массива
    array_unshift($terms, [
        'id' => $maxId,
        'term' => $newTerm,
        'translation' => $newTranslation
    ]);

    return ['success' => true, 'terms' => $terms]; // возвращает отчет об операции + обновленный массив 
}















// ========== РЕДАКТИРОВАНИЕ ==========

// БЫЛО
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edited'])) {
    $editId = (int)$_POST['change_id'];
    $editedTerm = trim($_POST['edited_term'] ?? '');
    $editedTranslation = trim($_POST['edited_translation'] ?? '');
    
    if (!empty($editedTerm) && !empty($editedTranslation)) {
        // проверка на дубликат (убрана)

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



// СТАЛО
function readactTerm($terms, $editId, $editedTerm, $editedTranslation) {

    // делаем трим
    $editedTerm = trim($editedTerm);
    $editedTranslation = trim($editedTranslation);

    // валидация || (хотя бы одно пустое?)
    if (empty($editedTerm) && empty($editedTranslation)) {
        return ['success' => false, 'error' => 'Пустые поля'];
    }

    // тут может быть проверка на дубликат

    // находим и обновляем запись на ту которую ввел пользователь 
    $found = false; // добавлен флаг фоунд чтобы проверить существования на всякий пожарный
    foreach ($terms as &$term) {
        if ($term['id'] === $editId) {
            $term['term'] = $editedTerm;
            $term['translation'] = $editedTranslation;
            $found = true;
            break;
        }
    }

    unset($term);

    if (!$found) {
        return ['success' => false, 'error' => 'Термин не найден'];
    }

    return ['success' => true, 'terms' => $terms];

}

















// ========== УДАЛЕНИЕ ==========

// БЫЛО
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


// СТАЛО
function deleteTerm($terms, $deleteId) {
    $deleteId = (int)$deleteId; // делаем int!

    // Проверка существования
    $found = false; // как и в прошлом добавил фоунд 
    foreach ($terms as $term) {
        if ($term['id'] === $deleteId) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        return ['success' => false, 'error' => 'Термин не найден'];
    }

    // тут мы фильтруем убирая запись с нужным айди
   $terms = array_filter($terms, function($term) use ($deleteId) {
       return $term['id'] !== $deleteId;
   });

   // это надо чтобы ключи шли правильно (без пропусков -> 0, 1, 2, 3...)
   $terms = array_values($terms);

   return ['success' => true, 'terms' => $terms];

}


?>