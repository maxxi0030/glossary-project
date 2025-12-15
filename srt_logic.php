<?php
$subtitles = [];


function parseSRT($SRTfileContent) {

    // разбиваем содержимое на строки - запасной варик
    // $lines = preg_split("/\R/", $SRTfileContent);

    // Сначала нормализуем переносы строк (могут быть \r\n или \n)
    $SRTfileContent = str_replace("\r\n", "\n", $SRTfileContent);

    // Разбиваем на блоки субтитров по пустым строкам (типо будет Blank line)
    $blocks = explode("\n\n", trim($SRTfileContent));


    foreach ($blocks as $block) {
        if (empty(trim($block))) continue; // пропускаем пустые блоки
        
        $lines = explode("\n", trim($block));
        
        // Минимум должно быть 3 строки: индекс, время, текст
        if (count($lines) < 3) continue;
        
        $subtitle = [];
        
        // 1. индекс
        $subtitle['index'] = (int)$lines[0];
        

        // 2. временная метка
        $timeLine = $lines[1];

        // Нужно разбить по " --> " и распарсить каждую часть
        // Разбить по разделителю
        list($startTimeStr, $endTimeStr) = explode(' --> ', $timeLine);

        $subtitle['startTime'] = $startTimeStr;
        $subtitle['endTime'] = $endTimeStr;
        

        // 3. текст (всё что после второй строки)
        $textLines = array_slice($lines, 2);
        $subtitle['text'] = implode("\n", $textLines);
        
        $subtitles[] = $subtitle;
    }



    return $subtitles;
}






// A. Таймкоды

// End раньше Start → ERROR: NEGATIVE_DURATION
function endEarlier($subtitles) {
    // будем собирать ошибки в массив и потом выведем
    $errors = [];
    
    
    
    foreach ($subtitles as $sub) {
        // Преобразуем в миллисекунды для сравнения
        $start = $sub['startTime'];
        $end = $sub['endTime'];


        if($start >= $end) {
            $errors = [
                           'type' => 'NEGATIVE_DURATION',
                'index' => $sub['index'],
                'startTime' => $sub['startTime'],
                'endTime' => $sub['endTime'],
                'message' => "oshibka"


            ];
            
        }
    }

}

// Пересечение с предыдущим сегментом (this.start < prev.end) → ERROR: OVERLAP

// Минимальный gap между сегментами → если gap < 160ms → WARN: GAP_TOO_SMALL

// Слишком короткая длительность → если duration < 1080ms → WARN: DURATION_TOO_SHORT

// Слишком длинная длительность → если duration > 7000ms → WARN: DURATION_TOO_LONG




// B. Текст субтитров

// Пустой текст (после trim) → ERROR: EMPTY_TEXT     

// Больше 2 строк в одном сегменте→ WARN: TOO_MANY_LINES

// Длина строки: если любая строка > 36 символов → WARN: LINE_TOO_LONG











?>