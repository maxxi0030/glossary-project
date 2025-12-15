<?php 

require_once __DIR__ . "/../logic.php";

echo "Filter Tests:\n";


$terms = [
    ["term" => "Apple"],
    ["term" => "banana"],
    ["term" => "Apricot"],
    ["term" => "Ābols"], // латышский
    ["term" => "молоко"] // кириллица
];


// 1. поиск по части слова
$result = filterGlossary($terms, "ap");

// echo (count($result) === 2 ? "OK\n" : "FAIL\n"); - можно писать и так

if (count($result) === 2) {
    echo "1. OK\n"; // должно быть Apple, Apricot
} else {
    echo "1. FAIL\n";
}


// 2. поиск без учета регистра
$result = filterGlossary($terms, "BAN");

if ($result[0]['term'] === "banana") {  // [0] — первый элемент массива совпадений (хоть у нас больше слов на БАН и нету)
    echo "2. OK\n"; // banana
} else {
    echo "2. FAIL\n";
}


// 3. поиск с пустой строкой
$result = filterGlossary($terms, "");

if (count($result) === 5) {
    echo "3. OK\n";            // все 5
} else {
    echo "3. FAIL\n";
}



// 4. поиск с отсутствующими совпадениями
$result = filterGlossary($terms, "asd");

if (count($result) === 0) {
    echo "4. OK\n";            // 0
} else {
    echo "4. FAIL\n";
}


// 5. корректная работа с латышским языком
$result = filterGlossary($terms, "Āb");

if ($result[0]['term'] === "Ābols") {
    echo "5. OK\n";
} else {
    echo "5. FAIL\n";
}


// 6. корректная работа с кириллицей
$result = filterGlossary($terms, "МОЛ");

if ($result[0]['term'] === "молоко") {
    echo "6. OK\n"; // молоко
} else {
    echo "6. FAIL\n";
}



// 7. ДОП: поиск с одним пробелом 
$result = filterGlossary($terms, " ");

if (count($result) === 5) {
    echo "7. OK\n";            // все 5
} else {
    echo "7. FAIL\n";
}



?>