<?php
$subtitles = [];


// парсинг СРТ файла
function parseSRT($SRTfileContent) {

    // разбиваем содержимое на строки - запасной варик
    // $lines = preg_split("/\R/", $SRTfileContent);

    // Сначала нормализуем переносы строк (могут быть \r\n или \n)
    $SRTfileContent = str_replace("\r\n", "\n", $SRTfileContent);

    // Разбиваем на блоки субтитров по пустым строкам (типо будет Blank line)
    $blocks = explode("\n\n", trim($SRTfileContent));


    foreach ($blocks as $block) {
        if (empty(trim($block))) continue; // пропускаем пустые блоки
        
        $lines = explode("\n", trim($block)); // explode не делает trim каждого элемента автоматически
        $lines = array_map('trim', $lines); // поэтому делаем trim для каждой строки!
        
        // Минимум должно быть 2 строки: индекс, время
        // Текст может отсутствовать - это нормально, мы его проверим позже
        if (count($lines) < 2) continue;
        
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






// ============ Таймкоды ============

// функция для перевода в миллисекунды
function toMiliseconds($time){
    // 00:00:10,500 (часы:минуты:секунды,миллисекунды)

    // Разбиваем по запятой
    list($hms, $ms) = explode(',', $time);
    
    // Разбиваем часы:минуты:секунды
    list($h, $m, $s) = explode(':', $hms);
    
    // Переводим всё в миллисекунды
    return ($h * 3600000) + ($m * 60000) + ($s * 1000) + $ms;

}






// End раньше Start → ERROR: NEGATIVE_DURATION
function endEarlier($subtitles) {
    // будем собирать ошибки в массив и потом выведем
    $errors = [];


    foreach ($subtitles as $sub) {
        // Преобразуем в миллисекунды для сравнения -  сделали
        $start = toMiliseconds($sub['startTime']);
        $end = toMiliseconds($sub['endTime']);


        if($start >= $end) {
            $errors [] = [
                'type' => 'NEGATIVE_DURATION',
                'index' => $sub['index'],
                'startTime' => $sub['startTime'],
                'endTime' => $sub['endTime'],
                'message' => "Subtitle #{$sub['index']}: end time ({$sub['endTime']}) is earlier than or equal to start time ({$sub['startTime']})",
                'advice' => "Ensure that the end time is strictly later than the start time"
            ];
        
        }
    }
    return $errors;


}


// Пересечение с предыдущим сегментом (this.start < prev.end) → ERROR: OVERLAP
function crossWprevious($subtitles) {
    $errors = [];

    // проходим по всем субтитрам - начиная со второго
    for($i =1; $i < count($subtitles); $i++) {
        $prev_indx = $subtitles[$i-1]; 
        $current_indx = $subtitles[$i];

        $this_start = toMiliseconds($current_indx['startTime']);
        $prev_end = toMiliseconds($prev_indx['endTime']);


        if ($this_start < $prev_end) {
            $overlap = $prev_end - $this_start; // узнаем насолько они пересеклись - но еще под вопросом 

            $errors [] = [
                'type' => 'OVERLAP',
                'index' => $current_indx['index'],
                'prevIndex' => $prev_indx['index'],
                'overlap' => $overlap,
                'message' => "Subtitle #{$current_indx['index']} overlaps with previous subtitle #{$prev_indx['index']} by {$overlap} ms",
                'advice' => "Adjust the start time so it does not overlap with the previous subtitle, or shorten the previous subtitle"
                ];
        }
    }


    return  $errors;
}


// Минимальный gap между сегментами → если gap < 160ms → WARN: GAP_TOO_SMALL
function minGap($subtitles) {
    $warnings = [];  // будет как варнинг

    for($i =1; $i < count($subtitles); $i++) {
        $prev_indx = $subtitles[$i-1]; 
        $current_indx = $subtitles[$i];

        $this_start = $current_indx['startTime'];
        $prev_end = $prev_indx['endTime'];

        $gap = toMiliseconds($this_start) - toMiliseconds($prev_end); // узнаем gap между сегментами - но еще под вопросом  с функией в миллисекунды 

        // пропускаем если есть пересечение (это уже ERROR)
        if($gap < 0) {
            continue;
        }

        if($gap < 160) {
            $warnings [] = [
                'type' => 'GAP_TOO_SMALL',
                'index' => $current_indx['index'],
                'prevIndex' => $prev_indx['index'],
                'gap' => $gap,
                'message' => "Subtitle #{$current_indx['index']} starts too close to previous subtitle #{$prev_indx['index']} (gap: {$gap} ms)",
                'advice' => "Increase the gap between this subtitle and the previous one to at least 160 ms"
                ];
        }
    }

    return $warnings;
}   


// Слишком короткая длительность → если duration < 1080ms → WARN: DURATION_TOO_SHORT
function durationTooShort($subtitles) {
    $warnings = [];

    foreach ($subtitles as $sub) {

        $start = $sub['startTime'];
        $end = $sub['endTime'];

        $duration = toMiliseconds($end) - toMiliseconds($start); // с функией в миллисекунды 

        //  если длительность отрицательная - то это уже ошибка
        if($duration < 0) {
            continue;
        }

        if($duration < 1080) {
            $warnings [] = [
                'type' => 'DURATION_TOO_SHORT',
                'index' => $sub['index'],
                'startTime' => $sub['startTime'],
                'endTime' => $sub['endTime'],
                'duration' => $duration,
                'message' => "Subtitle #{$sub['index']} duration is too short ({$duration} ms). Minimum recommended duration is 1080 ms",
                'advice' => "Extend the subtitle duration to at least 1080 ms for better readability"
            ];
        
        }
    }

    return $warnings;
}

// Слишком длинная длительность → если duration > 7000ms → WARN: DURATION_TOO_LONG
function durationTooLong($subtitles) {
    $warnings = [];

    foreach ($subtitles as $sub) {

        $start = $sub['startTime'];
        $end = $sub['endTime'];

        $duration = toMiliseconds($end) - toMiliseconds($start); // с функией в миллисекунды 


        if($duration > 7000){
            $warnings [] = [
                'type' => 'DURATION_TOO_LONG',
                'index' => $sub['index'],
                'startTime' => $sub['startTime'],
                'endTime' => $sub['endTime'],
                'duration' => $duration,
                'message' => "Subtitle #{$sub['index']} duration is too long ({$duration} ms). Maximum recommended duration is 7000 ms",
                'advice' => "Reduce the subtitle duration to no more than 7000 ms, or split it into multiple subtitles"
            ];
        
        }
    }

    return $warnings;    

}



// ============ Текст субтитров ============


// Пустой текст (после trim) → ERROR: EMPTY_TEXT      $subtitle['text'] 
function emptyText($subtitles) {
    $errors = [];

    foreach ($subtitles as $sub) {
        $text = trim($sub['text']);

        // if ($text=== '') {
        if(empty($text)) {
            $errors [] = [
                'type' => 'EMPTY_TEXT',
                'index' => $sub['index'],
                'message' => "Subtitle #{$sub['index']} has no visible text",
                'advice' => "Add visible subtitle text or remove this subtitle if it is not needed"
            ];
        }
    }

    return $errors;
}



// Больше 2 строк в одном сегменте→ WARN: TOO_MANY_LINES    $subtitle['text'] 
function tooManyLines($subtitles) {
    $warnings = [];

    foreach ($subtitles as $sub) {
        $text = $sub['text'];

        $lines = explode("\n", $text);

        if (count($lines) > 2) {
            $warnings [] = [
                'type' => 'TOO_MANY_LINES',
                'index' => $sub['index'],
                'text' => $sub['text'],
                'message' => "Subtitle #{$sub['index']} contains more than 2 lines",
                'advice' => "Reduce the subtitle to a maximum of 2 lines or split it into multiple subtitles"
            ];
        }
    }

    return $warnings;
}




// Длина строки: если любая строка > 36 символов → WARN: LINE_TOO_LONG   
function tooLongLine($subtitles) {
    $warnings = [];

    foreach ($subtitles as $sub) {

        $text = $sub['text'];
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            // $symbols = mb_strlen($line); - плохо работает со смайликами
            // $symbols = strlen($line); - работает хорошо только с латиницой
            $symbols = grapheme_strlen($line);

            if ($symbols > 36) {
                $warnings [] = [
                    'type' => 'LINE_TOO_LONG',
                    'index' => $sub['index'],
                    'text' => $line,
                    'message' => "Subtitle #{$sub['index']} contains a line longer than 36 characters ({$symbols} characters)",
                    'advice' => "Shorten the line to 36 characters or less, or break it into two lines"
                ];
            }
        }
    }

    return $warnings;
}





// собираем все функции вместе и делаем ввывод ошибок если они нашлись - если нет если ошибки не были найдены - отчет о start-end, количество сегментов

function checkAll($subtitles) {

    // всего может быть 3 ошибки и 5 варнингов 
    $allIssues = [
        'errors' => [],
        'warnings' => []
    ];


    // array_merge() — функция, которая объединяет несколько массивов в один.

    // ошибки
    $allIssues['errors'] = array_merge($allIssues['errors'], 
        endEarlier($subtitles), 
        crossWprevious($subtitles), 
        emptyText($subtitles));


    // варининги
    $allIssues['warnings'] = array_merge($allIssues['warnings'],
        minGap($subtitles), 
        durationTooShort($subtitles), 
        durationTooLong($subtitles),
        tooManyLines($subtitles),
        tooLongLine($subtitles)
    );


    return $allIssues;

}

?>