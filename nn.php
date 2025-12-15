<?php
// Подключение logic.php с функциями
require_once __DIR__ . '/logic.php';
require_once __DIR__ . '/srt_logic.php';

// файл для хранения глоссария, И при новом заходе на страницу → JSON читается → таблица появляется снова.
$glossaryFile = 'glossary_data.json';


// массив с терминами (переменная $terms уже объявлена в logic.php)
// $terms = [];


//загрузка терминов которые уже есть в glossaryFile
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


// ОБРАБОТКА ЗАГРУЗКИ ФАЙЛА для глоссария (csv)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['glossary_file'])) { // проверяем что форма отправлена + что файл был приложен
   $terms = handleUploaded($terms, $maxId);


   file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); // сохранение обновленного глоссария после обработки


    header('Location: ' . $_SERVER['PHP_SELF']); // зачем этот редирект - нужен! при обновлении страницы не будет показываться уведомление в каких то случаях..
    exit;  

}

// ОБРАБОТКА ЗАГРУЗКИ ФАЙЛА для проверки субтитров (srt)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['srt'])) { // проверяем что форма отправлена + что файл был приложен
    $uploadedFile = $_FILES['srt'];

    // проверка загружен ли файл
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) { // если нет ошибок
        return $subtitles;
   }


    // функция парсинг
    $SRTfileContent = file_get_contents($_FILES['srt']['tmp_name']);
    $subs = parseSRT($SRTfileContent);

        // вывод массива на экран
        // echo "<pre>";
        // print_r($subs);
        // echo "</pre>";

 
    // функция со всеми проверками
    $errors = checkNegativeDuration($subs);

    if (!empty($errors)) {
        echo "<h3>Найдены ошибки:</h3>";
        foreach ($errors as $error) {
            echo "<div style='color: red; border: 1px solid red; padding: 10px; margin: 5px;'>";
            echo "<strong>{$error['type']}</strong>: {$error['message']}";
            echo "</div>";
        }
    } else {
        echo "<div style='color: green;'>Ошибок не найдено!</div>";
    }

    // функция для красивого вывода/вывода инфы - если ошибок нету



    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;  
}



// УДАЛЕНИЕ СТРОКИ - используем функцию deleteTerm() из logic.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
   $deleteId = (int)$_POST['delete_id'];
   
   $result = deleteTerm($terms, $deleteId);
   
   if ($result['success']) {
       $terms = $result['terms'];
       file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
       header('Location: ' . $_SERVER['PHP_SELF']);
       exit;
   }
}


// GET = читать данные (поиск, фильтрация, сортировка)
// POST = изменять данные (добавить, удалить, обновить)




//ДОБАВЛЕНИЕ СТРОКИ

// проверяем режим добавления через GET параметр (работаем с add_mode)
$addMode = isset($_GET['add_mode']) && $_GET['add_mode'] === '1';


// ДОБАВЛЕНИЕ НОВОГО ТЕРМИНА - используем функцию addTerm() из logic.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_new_term'])) {
   $newTerm = trim($_POST['new_term'] ?? '');
   $newTranslation = trim($_POST['new_translation'] ?? '');
   
   $result = addTerm($terms, $newTerm, $newTranslation);
   
   if ($result['success']) {
       $terms = $result['terms'];
       file_put_contents($glossaryFile, json_encode($terms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
       header('Location: ' . $_SERVER['PHP_SELF']);
       exit;
   }
}


//РЕДАКТИРОВАНИЕ СТРОКИ - используем функцию readactTerm() из logic.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edited'])) {
    $editId = (int)$_POST['change_id'];
    $editedTerm = trim($_POST['edited_term'] ?? '');
    $editedTranslation = trim($_POST['edited_translation'] ?? '');
    
    $result = readactTerm($terms, $editId, $editedTerm, $editedTranslation);
    
    if ($result['success']) {
        $terms = $result['terms'];
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




// Фильтрация терминов (поиск) - используем функцию filterGlossary() из logic.php
$filteredTerms = filterGlossary($terms, $searchQuery);


// Сортировка терминов - используем функцию sortGlossary() из logic.php
$filteredTerms = sortGlossary($filteredTerms, $sortOrder);



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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>glossary</title>
    <link rel="stylesheet" href="style2.css">
</head>

<!-- сделать заставку? -->

<body>
   <header>
       <h1>Glossary Viewer</h1>
       <!-- чистим весь json  -->
       <form method="POST">
           <button type="submit" name="clear_all">Clear All</button>
       </form>
    </header>


    <main>

        <div class="importFile">
            <h2>Import file</h2>

                <!-- добавляем новый файл -->
               <form method="POST" enctype="multipart/form-data">
                   <input type="file" name="glossary_file" accept=".csv" requared> <!-- стоит формат только csv -->
                   <button type="submit">Import</button>
                </form>
                <hr>
        </div>



            <!-- если термины есть то показываем сортировку и фильтрацию -->
        <?php if ($totalTerms > 0): ?>
            <div class="controls" >
                
                <div class="searchMetod">
                    <h2>Search</h2>
                    <form method="GET" >
                        <input type="text" name="search" placeholder="search..." value="<?= htmlspecialchars($searchQuery) ?>" required>
                        <button type="submit">Search</button>
                        <?php if (!empty($searchQuery)): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="sortMetod">
                    <h2>Sort</h2>
                    <p>
                        <a href="?sort=asc&search=<?= urlencode($searchQuery) ?>"><?= $sortOrder === 'asc' ? '[A → Z]' : 'A → Z' ?></a>
                        <a href="?sort=desc&search=<?= urlencode($searchQuery) ?>"><?= $sortOrder === 'desc' ? '[Z → A]' : 'Z → A' ?></a>
                    </p>
                </div>
            </div>

            <div class="table">
                <?php if ($filteredCount > 0): ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Term</th>
                                <th>Translation</th>

                                <!-- пустая для редактирования -->
                                <th class="action-column"></th>

                                <!-- кнопка добавить новый термин -->
                                <th class="actions-column">
                                    <?php if (!$addMode): ?>
                                        <button onclick="window.location='?add_mode=1'" class="add_term_btn">
                                            <img src="img\plus.png" alt="add" class="add_term">
                                        </button>
                                    <?php else: ?>
                                        <button class="add_term_btn">
                                            <img src="img\plus.png" alt="add" class="add_term">
                                        </button>
                                    <?php endif; ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <!-- если нажата кнопка добавить новый термин -->
                            <?php if ($addMode): ?>
                                <tr class="add-row">
                                    <form method="POST">

                                        <!-- поля ввода -->
                                        <td>
                                            <input type="text" name="new_term" placeholder="Enter term" required autofocus autocomplete="off">
                                        </td>

                                        <td>
                                            <input type="text" name="new_translation" placeholder="Enter translation" required autocomplete="off">
                                        </td>

                                        <!-- кнопки сохранить и отменить -->
                                        <td class="actions-column" colspan="2">
                                            <div class="add-row-actions">
                                                <button type="submit" name="save_new_term" class="save-btn">Save</button>
                                                <!-- сделано как кнопка, раньше было просто ссылкой (с другими кнопками также сделал везде)  -->
                                                <button onclick="window.location='<?= $_SERVER['PHP_SELF'] ?>'" class="cancel-btn">Cancel</button>
                                            </div>
                                        </td>
                                    </form>
                                </tr>
                            <?php endif; ?>



                            <?php foreach ($filteredTerms as $item): ?>
                                <?php if ($editMode === $item['id']): ?>
                                    <!-- если нажата кнопка редактировать -->
                                    <tr class="edit-row">
                                        <form method="POST">
                                            <input type="hidden" name="change_id" value="<?= $item['id'] ?>">
                                            
                                            <!-- поля редактирования -->
                                            <td>
                                                <textarea wrap="hard" name="edited_term" required autofocus autocomplete="off"><?= htmlspecialchars($item['term']) ?></textarea>

                                            </td>
                                            
                                            <td>
                                                <textarea wrap="hard" name="edited_translation" required autocomplete="off"><?= htmlspecialchars($item['translation']) ?></textarea>
                                            </td>
                                            
                                            <!-- кнопки сохранить и отменить -->
                                            <td class="actions-column" colspan="2"> <!-- colspan="2" на две колонки пространство занимает  -->
                                                <div class="edit-row-actions">
                                                    <button type="submit" name="save_edited" class="save-btn">Save</button>
                                                    <button type="button" onclick="window.location='<?= $_SERVER['PHP_SELF'] ?>'" class="cancel-btn">Cancel</button>
                                                </div>
                                            </td>
                                        </form>
                                    </tr>
                                <?php else: ?>
                                    <!-- если не нажаты кнопки то === ОБЫЧНЫЙ РЕЖИМ ТАБЛИЦЫ=============== -->
                                    <tr>
                                        <td><?= htmlspecialchars($item['term']) ?></td>
                                        <td><?= htmlspecialchars($item['translation']) ?></td>

                                        <td class="actions-column">
                                            <!-- Кнопка РЕДАКТИРОВАТЬ (открывает форму) -->
                                            <button type="button" onclick="window.location='?edit_id=<?= $item['id'] ?>'" class="change-btn">
                                                <img src="img\file-edit.png" alt="change_term" class="change">
                                            </button>
                                        </td>

                                        <td class="actions-column">
                                            <!-- Кнопка УДАЛИТЬ -->
                                            <form method="POST">
                                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="delete-btn">
                                                    <img src="img\trash.png" alt="delete_term" class="delete">
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Ничего не найдено по запросу "<?= htmlspecialchars($searchQuery) ?>"</p>
                <?php endif; ?>
          
            <hr>
            <!-- тут счетчики и кнопка скачать -->
            <div class="down_info">
                <!-- счетчики -->
                <div class="counters">
                    <p>
                        <strong>Total terms:</strong> <?= $totalTerms ?><br>
                        <strong>Filtered terms:</strong> <?= $filteredCount ?>
                    </p>
                </div>

                <!-- кнопка скачать новый файл - не будет работать если в режиме редактирования или добавления -->
                <div class="download-new">
                    <form method="POST">
                        <button type="submit" name="download_csv" class="download_btn" <?= ($editMode !== null || $addMode) ? 'disabled' : '' ?>>
                            Download
                        </button>
                    </form>
                </div>
            </div>
        </div>
  

        <!-- если терминов нету (текст + кнопка добавить первый термин) -->
        <?php else: ?>
            <!-- <div class="controls"> -->
            <div class = "emptyGlos">    
                <?php if (!$addMode): ?>
                    <p class="empty_text">Glossary is empty.</p>
                    <button onclick="window.location='?add_mode=1'" class="add_term_btn_empety">Add first term...</button>
            <!-- </div> -->

                <?php endif; ?>

            <!-- если нажата кнопка добавить первый термин-->
            <?php if ($addMode): ?>
                <div class="add-form">
                    
                    <form method="POST">
                        <!-- поля дял ввода первого термина -->
                        <input type="text" name="new_term" placeholder="Enter term" required autofocus>
                        <input type="text" name="new_translation" placeholder="Enter translation" required>

                        <!-- пробовал вариант с текстареа и с ннпутами, но работало и там и тут криво и непонятно, 
                         хотелось решить проблему используя врап хард, но не вышло как надо. -->
                         
                            <!-- textarea для первого термина -->
                        <!-- <textarea wrap="hard" name="new_term"
                                placeholder="Enter term"
                                required
                                autofocus></textarea> -->

                        <!-- textarea для перевода -->
                        <!-- <textarea wrap="hard" name="new_translation"
                                placeholder="Enter translation"
                                required></textarea> -->
    
                        <!-- кнопки добавить первый термин и отменить добавку  -->
                        <button type="submit" name="save_new_term" class="save-btn">Save</button>
                        <button onclick="window.location='<?= $_SERVER['PHP_SELF'] ?>'" class="cancel-btn">Cancel</button>

                    </form>
                </div>
            <?php endif; ?>
            </div>
        <?php endif; ?>


        
        <div class="importSRTfile">
            <h2>Import SRT</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="srt" accept=".srt" requared> <!-- стоит формат только srt -->
                <button type="submit">Import</button>
            </form>
            <hr>
        </div>



    </main>
</body>
</html>
