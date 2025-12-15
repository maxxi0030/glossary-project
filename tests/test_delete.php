<?php 

require_once __DIR__ . "/../logic.php";

echo "Delete Tests:\n";

$terms = [
    ["id" => 1, "term" => "Apple", "translation" => "Fruit"],
    ["id" => 3, "term" => "Banana", "translation" => "Yellow fruit"],
    ["id" => 2, "term" => "Cherry", "translation" => "Red berry"]
];


// 1. ЗАПИСЬ ИСЧЕЗАЕТ ИЗ МАССИВА
$result = deleteTerm($terms, 1);

if ($result['success'] === true && count($result['terms']) === 2) {
    echo "1. OK\n";
} else {
    echo "1. FAIL\n";
}




// 2. УДАЛЕНИЕ ПО ID РАБОТАЕТ (попробуем найти в массиве) 
$result = deleteTerm($terms, 3);

$found = false;

foreach ($result['terms'] as $term) {
    if ($term['id'] === 3) {
        $found = true;
        break;
    }
}

if ($result['success'] === true && !$found) {
    echo "2. OK\n";
} else {
    echo "2. FAIL\n";
}




// 3. УДАЛЕНИЕ НЕ ЛОМАЕТ ДРУГИЕ ИНДЕКСЫ (array_values работает)
$result = deleteTerm($terms, 2);

if ($result['success'] === true &&
    isset($result['terms'][0]) &&      // isset() - проверяет существует ли переменная или ключ массива и не равна ли она null
    isset($result['terms'][1]) &&
    !isset($result['terms'][2])) { // 3 индекса не должно быть, так как всего 2 элемента

    echo "3. OK\n";
} else {
    echo "3. FAIL\n";
}


?>