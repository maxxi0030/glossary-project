<?php 

require_once __DIR__ . "/../logic.php";

echo "Sort Tests:\n";

$terms = [
    ["term" => "Zebra"],
    ["term" => "apple"],
    ["term" => "Banana"],
    ["term" => "AI"],
    ["term" => "ai"],
    ["term" => "Ābols"], // латышский
    ["term" => "молоко"] // кириллица
];



// 1. СОРТИРОВКА A → Z
$result = sortGlossary($terms, 'asc');

if ($result[0]['term'] === 'Ābols' && $result[count($result)-1]['term'] === 'молоко') { 
    echo "1. OK\n";   // -1 это индекс посленего элемента - ДО момента улчшения стояла зебра вместо молока. но логичнее всего поставить молоко и чтобы кириллицы была в посленюю очередь (так я и сделал в коде)
} else {
    echo "1. FAIL\n";
}



// 2. СОРТИРОВКА Z → A
$result = sortGlossary($terms, 'desc');

if ($result[0]['term'] === 'молоко' && $result[count($result)-1]['term'] === 'Ābols') { // ДО этого момента стояла зебра вместо молока. но логичнее всего поставить молоко и чтобы кириллицы была в посленюю очередь (так я и сделал в коде)
    echo "2. OK\n";
} else {
    echo "2. FAIL\n";
}



// 3. Разный регистр (AI и ai) - должны быть рядом 

// ФЕЙЛ - на сайте тоже после фильтрации происходит такая фигня что если есть дубликат то (отредактирован или добавлен вручную) то при фильтрации тупо все ломаеться и фильтр выдает новый добавленный элемент еще раз как старый типо

$result = sortGlossary($terms, 'asc');

// находим обе позиции
$pos_AI = -1;
$pos_ai = -1;

foreach ($result as $i => $item) {
    if ($item['term'] === 'AI') $pos_AI = $i;
    if ($item['term'] === 'ai') $pos_ai = $i;
}

// проверяем что они рядом (разница в 1 индекс)
if (abs($pos_AI - $pos_ai) === 1) {
    echo "3. OK\n";
} else {
    echo "3. FAIL\n";
}




// 4. СОРТИРОВКА ПУСТОГО МАССИВА
$result = sortGlossary([], 'asc');
if (count($result) === 0) {
    echo "4. OK\n";
} else {
    echo "4. FAIL\n";
}




// 5. СОРТИРОВКА С ЛАТЫШСКИМИ БУКВАМИ +  КИРИЛЛИЦОЙ (по сути аболс должен быть раньше всех, а молоко наоборот - в конце, НО на практике так не работает)
$result = sortGlossary($terms, 'asc');

$abolsPos = -1;
$molokoPos = -1;

foreach ($result as $i => $item) {
    if ($item['term'] === 'Ābols') {
        $abolsPos = $i;
    }
    if ($item['term'] === 'молоко') {
        $molokoPos = $i;
    }
}

// Ābols должен быть ПЕРЕД молоко (но оба в конце после латиницы)
if ($abolsPos >= 0 && $molokoPos >= 0 && $abolsPos < $molokoPos) {
    echo "5. OK\n";
} else {
    echo "5. FAIL\n";
}




// РАБОТА НАД ОШИБКАМИ - взял второй вариант логики сортировки 



?>
