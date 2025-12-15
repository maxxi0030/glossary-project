<?php 

require_once __DIR__ . "/../logic.php";

echo "Redact Tests:\n";

$terms = [
    ["id" => 1, "term" => "Apple", "translation" => "Fruit"],
    ["id" => 2, "term" => "Banana", "translation" => "Yellow fruit"],
    ["id" => 3, "term" => "Cherry", "translation" => "Red berry"]

];


// 1. ИЗМЕНЕНИЕ ТЕКСТА TERM
$result = readactTerm($terms, 1, "NoApple", "Fruit");

if ($result['success'] === true && 
    $result['terms'][0]['term'] === 'NoApple' &&
    $result['terms'][0]['translation'] === 'Fruit') {
    echo "1. OK\n";
} else {
    echo "1. FAIL\n";
}

// 2. ИЗМЕНЕНИЕ TRANSLATION
$result = readactTerm($terms, 1, "Apple", "NoFruit");

if ($result['success'] === true && 
    $result['terms'][0]['term'] === 'Apple' &&
    $result['terms'][0]['translation'] === 'NoFruit') {
    echo "2. OK\n";
} else {
    echo "2. FAIL\n";
}




// 3. ОТСУТСТВИЕ ДУБЛЕЙ ПРИ РЕДАКТИРОВАНИИ (должно быть OK, т.к. спецом разрешил добавлние дублей)
$result = readactTerm($terms, 1, "Banana", "Yellow fruit");

if ($result['success'] === true && 
    $result['terms'][0]['term'] === 'Banana' &&
    $result['terms'][0]['translation'] === 'Yellow fruit') {
    echo "3. OK\n";
} else {
    echo "3. FAIL\n";
}




// 4. КОРРЕКТНОСТЬ СОХРАНЕНИЯ
$result = readactTerm($terms, 1, "NewApple", "NewFruit");

if ($result['success'] === true && 
    $result['terms'][1]['term'] === 'Banana' && // проверяем что 2 и 3 запись осталась как была
    $result['terms'][2]['term'] === 'Cherry') {
    echo "4. OK\n";
} else {
    echo "4. FAIL\n";
}





?>