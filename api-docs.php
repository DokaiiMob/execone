<?php
/**
 * Страница документации по API
 */

// Подключаем общие файлы
require_once 'config/init.php';

// Проверяем, есть ли файл api-docs.md
$docsPath = __DIR__ . '/api-docs.md';
$docsContent = '';

if (file_exists($docsPath)) {
    $docsContent = file_get_contents($docsPath);
} else {
    $docsContent = "# API документация\n\nДокументация временно недоступна.";
}

// Преобразуем Markdown в HTML
// Если у вас нет библиотеки для работы с Markdown, используйте простой формат текста
// В данном примере мы используем простую замену заголовков, списков и блоков кода
function simpleMarkdownToHtml($markdown) {
    // Заменяем заголовки
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $markdown);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);

    // Заменяем списки
    $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
    $html = str_replace("<li>", "<ul><li>", $html);
    $html = str_replace("</li>\n\n", "</li></ul>\n\n", $html);
    $html = str_replace("</li>\n<ul>", "</li>\n", $html);

    // Заменяем блоки кода
    $html = preg_replace('/```([a-z]*)\n([\s\S]*?)```/m', '<pre><code class="language-$1">$2</code></pre>', $html);

    // Заменяем обычные абзацы
    $html = preg_replace('/^(?!<h|<ul|<pre|<table)(.+)$/m', '<p>$1</p>', $html);

    // Заменяем таблицы
    if (preg_match_all('/\|\s*(.*?)\s*\|\n\|\s*[-:]+\s*\|\s*[-:]+\s*\|\n((?:\|\s*.*?\s*\|\n)+)/m', $html, $matches)) {
        foreach ($matches[0] as $index => $fullMatch) {
            $headerRow = $matches[1][$index];
            $dataRows = $matches[2][$index];

            $headerCells = explode('|', trim($headerRow, ' |'));
            $headerHtml = '<tr>' . implode('', array_map(function($cell) {
                return '<th>' . trim($cell) . '</th>';
            }, $headerCells)) . '</tr>';

            $dataRowsArray = explode("\n", trim($dataRows));
            $dataRowsHtml = '';
            foreach ($dataRowsArray as $row) {
                if (empty(trim($row))) continue;
                $cells = explode('|', trim($row, ' |'));
                $dataRowsHtml .= '<tr>' . implode('', array_map(function($cell) {
                    return '<td>' . trim($cell) . '</td>';
                }, $cells)) . '</tr>';
            }

            $tableHtml = '<table class="table table-bordered">' .
                '<thead>' . $headerHtml . '</thead>' .
                '<tbody>' . $dataRowsHtml . '</tbody>' .
                '</table>';

            $html = str_replace($fullMatch, $tableHtml, $html);
        }
    }

    // Заменяем двойные переносы строк на </p><p>
    $html = str_replace("\n\n", "</p><p>", $html);

    // Удаляем лишние теги <p> и </p>
    $html = preg_replace('/<p><\/p>/', '', $html);
    $html = preg_replace('/<p><(h|ul|pre|table)/', '<$1', $html);
    $html = preg_replace('/<\/(h|ul|pre|table)><\/p>/', '</$1>', $html);

    return $html;
}

// Преобразуем Markdown в HTML
$docsHtml = simpleMarkdownToHtml($docsContent);

// Устанавливаем заголовок страницы
$pageTitle = 'API документация';

// Включаем шапку сайта
include 'views/layouts/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">API документация</h1>
                </div>
                <div class="card-body api-docs">
                    <?php echo $docsHtml; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .api-docs pre {
        background-color: #f5f5f5;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 20px;
        overflow-x: auto;
    }

    .api-docs code {
        font-family: Consolas, Monaco, 'Andale Mono', monospace;
        font-size: 14px;
    }

    .api-docs h2 {
        margin-top: 30px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .api-docs h3 {
        margin-top: 25px;
        color: #0056b3;
    }

    .api-docs table {
        width: 100%;
        margin-bottom: 20px;
    }

    .api-docs th, .api-docs td {
        padding: 8px 12px;
        border: 1px solid #ddd;
    }

    .api-docs th {
        background-color: #f2f2f2;
    }

    .dark-theme .api-docs pre {
        background-color: #2a2a2a;
        border-color: #444;
    }

    .dark-theme .api-docs h2 {
        border-bottom-color: #444;
    }

    .dark-theme .api-docs h3 {
        color: #4e9aff;
    }

    .dark-theme .api-docs th, .dark-theme .api-docs td {
        border-color: #444;
    }

    .dark-theme .api-docs th {
        background-color: #333;
    }
</style>

<?php
// Включаем подвал сайта
include 'views/layouts/footer.php';
?>
