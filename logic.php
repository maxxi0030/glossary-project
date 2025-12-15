<?php
$terms = []; // для всех функций



// ======== ФИЛЬТРАЦИЯ ==========

// старая логика  
// function filterGlossary($terms, $searchQuery){
//     $filteredTerms = [];
    
//     // делаем трим
//     $searchQuery = trim($searchQuery);

//     // Если поиск пустой — возвращаем всё
//     if (empty($searchQuery)) {
//         return $filteredTerms = $terms;
//     }


//     foreach ($terms as $item) { // перебираем все элементы
//        // с помощью stripos ищем + если нашли то добавляем в новый массив
//        if (stripos($item['term'], $searchQuery) !== false) {
//            $filteredTerms[] = $item;
//        }
//    }

//    return $filteredTerms; // возвращаем ВСЕ найденные
// }


// новая логика с mb_stripos и UTF-8
function filterGlossary($terms, $searchQuery) {
    $searchQuery = trim($searchQuery);

    // Если поисковый запрос пустой — возвращаем все элементы
    if ($searchQuery === '') {
        return $terms;
    }

    $result = [];

    foreach ($terms as $item) {
        if (mb_stripos($item['term'], $searchQuery, 0, 'UTF-8') !== false) {
            $result[] = $item;
        }
    }

    return $result;
}






// ========== СОРТИРОВКА ==========

// СТАРАЯ ЛОГИКА
// function sortGlossary($terms, $sortOrder) {

//     // если ниче не выбрано - возвращем как есть
//     if (empty($sortOrder)) {
//         return $terms;
//     }

//     // извлекаем только термины для сортировки 
//     $termsOnly = array_column($terms, 'term');

//     // сортируем
//     if ($sortOrder === 'desc') {
//        rsort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // Z → A, без учёта регистра
//     } else {
//        sort($termsOnly, SORT_NATURAL | SORT_FLAG_CASE); // A → Z
//    }

//    // создаем новый отсортированный массив
//     $sortedTerms = [];
//     foreach ($termsOnly as $term) {
//         foreach ($terms as $item) {
//             if (strcasecmp($item['term'], $term) === 0) {
//                $sortedTerms[] = $item;
//                break; // чтобы не добавлять дубликаты
//             }
//         }
//     }

//     return $sortedTerms;

// }


// НОВАЯ ЛОГИКА (1.0)

// function sortGlossary($terms, $sortOrder) {

//     // если сортировка не выбрана — вернуть как есть
//     if (empty($sortOrder)) {
//         return $terms;
//     }

//     // используем usort — эффективнее и короче
//     usort($terms, function($a, $b) use ($sortOrder) {

//         // сравнение без учета регистра
//         $cmp = strcasecmp($a['term'], $b['term']);

//         // если DESC — инвертируем
//         return ($sortOrder === 'desc') ? -$cmp : $cmp;
//     });

//     return $terms;
// }


// НОВАЯ ЛОГИКА 2.0 для решения проблем с латышским и кириллицой
function sortGlossary($terms, $sortOrder)
{
    if (empty($sortOrder)) {
        return $terms;
    }

    // Нормализация NFC, если есть расширение normalizer
    $normalize = function($s) {
        if (function_exists('normalizer_normalize')) {
            $n = normalizer_normalize($s, Normalizer::FORM_C);
            if ($n !== false) return $n;
        }
        return $s;
    };

    // MAP: латышские диакритики -> идут ПЕРЕД базовой буквой
    $alphabet = [
        'Ā' => 0.5,  'ā' => 0.51,
        'A'  => 1.0, 'a'  => 1.01,

        'B' => 2.0, 'b' => 2.01,
        'C' => 3.0, 'c' => 3.01,
        'Č' => 3.5, 'č' => 3.51,
        'D' => 4.0, 'd' => 4.01,
        'E' => 5.0, 'e' => 5.01,
        'Ē' => 5.5, 'ē' => 5.51,
        'F' => 6.0, 'f' => 6.01,
        'G' => 7.0, 'g' => 7.01,
        'Ģ' => 7.5, 'ģ' => 7.51,
        'H' => 8.0, 'h' => 8.01,
        'I' => 9.0, 'i' => 9.01,
        'Ī' => 9.5, 'ī' => 9.51,
        'J' => 10.0,'j' => 10.01,
        'K' => 11.0,'k' => 11.01,
        'Ķ' => 11.5,'ķ' => 11.51,
        'L' => 12.0,'l' => 12.01,
        'Ļ' => 12.5,'ļ' => 12.51,
        'M' => 13.0,'m' => 13.01,
        'N' => 14.0,'n' => 14.01,
        'Ņ' => 14.5,'ņ' => 14.51,
        'O' => 15.0,'o' => 15.01,
        'P' => 16.0,'p' => 16.01,
        'Q' => 17.0,'q' => 17.01,
        'R' => 18.0,'r' => 18.01,
        'S' => 19.0,'s' => 19.01,
        'Š' => 19.5,'š' => 19.51,
        'T' => 20.0,'t' => 20.01,
        'U' => 21.0,'u' => 21.01,
        'Ū' => 21.5,'ū' => 21.51,
        'V' => 22.0,'v' => 22.01,
        'W' => 23.0,'w' => 23.01,
        'X' => 24.0,'x' => 24.01,
        'Y' => 25.0,'y' => 25.01,
        'Z' => 26.0,'z' => 26.01,
        'Ž' => 26.5,'ž' => 26.51,

        // кириллица — отдельный блок (по желанию можно поместить иначе)
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

    // fallback: используем codepoint, чтобы неизвестные символы упорядочивались стабильно
    $charFallbackWeight = function($ch) {
        $code = mb_ord($ch, 'UTF-8');
        return 50 + ($code / 1000000);
    };

    // преобразуем строку в массив весов
    $toWeights = function($str) use ($alphabet, $charFallbackWeight, $normalize) {
        $str = $normalize($str);
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $weights = [];
        foreach ($chars as $ch) {
            if (isset($alphabet[$ch])) {
                $weights[] = $alphabet[$ch];
            } else {
                $weights[] = $charFallbackWeight($ch);
            }
        }
        return $weights;
    };

    usort($terms, function($a, $b) use ($sortOrder, $toWeights) {
        $wa = $toWeights($a['term']);
        $wb = $toWeights($b['term']);

        // если сортировка по убыванию — меняем местами массивы весов
        if ($sortOrder === 'desc') {
            $tmp = $wa; $wa = $wb; $wb = $tmp;
        }

        // поэлементное (лексикографическое) сравнение массивов весов
        $len = max(count($wa), count($wb));
        for ($i = 0; $i < $len; $i++) {
            $va = $i < count($wa) ? $wa[$i] : -INF; // короче — раньше
            $vb = $i < count($wb) ? $wb[$i] : -INF;
            if ($va < $vb) {
                return -1;
            } elseif ($va > $vb) {
                return 1;
            }
            // если равны — продолжаем
        }
        return 0; // строки эквивалентны по весам
    });

    return $terms;
}






// ========== ДОБАВЛЕНИЕ ТЕРМИНА ==========
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