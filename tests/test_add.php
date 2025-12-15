<?php 

require_once __DIR__ . "/../logic.php";

echo "Add Tests:\n";

$terms = [
    ["id" => 1, "term" => "Apple", "translation" => "Fruit"],
    ["id" => 3, "term" => "Banana", "translation" => "Yellow fruit"],
    ["id" => 2, "term" => "Cherry", "translation" => "Red berry"]

];

// 1. КОРРЕКТНОЕ ДОБАВЛЕНИЕ ЗАПИСИ
$result = addTerm($terms, "Orange", "Citrus fruit");  // должно быть в самом начале

if ($result['success'] === true &&
    count($result['terms']) === 4 && 
    $result['terms'][0]['term'] === 'Orange' &&
    $result['terms'][0]['translation'] === 'Citrus fruit') {
    echo "1. OK\n";
} else {
    echo "1. FAIL\n";
}



// 2. ГЕНЕРАЦИЯ УНИКАЛЬНОГО ID
$result = addTerm($terms, "Grape", "Small fruit");

if ($result['success'] === true && $result['terms'][0]['id'] === 4) {
    echo "2. OK\n";
} else {
    echo "2. FAIL\n";
}




// 3. ОТКАЗ ОТ ДОБАВЛЕНИЯ ДУБЛЕЙ (должно быть OK, т.к. спецом разрешил добавлние дублей)
$result = addTerm($terms, "Apple", "Fruit");

if ($result['success'] === true) {
    echo "3. OK\n";
} else {
    echo "3. FAIL\n";
}




?>