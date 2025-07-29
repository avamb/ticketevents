<?php
/**
 * Временный скрипт для исправления структуры папок согласно PSR-4
 * УДАЛИТЬ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!
 */

echo "Исправление структуры папок для PSR-4...\n";

$folders_to_rename = [
    'includes/api' => 'includes/Api',
    'includes/frontend' => 'includes/Frontend', 
    'includes/integrations' => 'includes/Integrations',
    'includes/models' => 'includes/Models',
    'includes/public' => 'includes/Public',
    'includes/services' => 'includes/Services'
];

foreach ($folders_to_rename as $old => $new) {
    if (is_dir($old)) {
        echo "Переименовываю $old -> $new\n";
        
        // Создаем временную папку
        $temp = $new . '_temp';
        if (is_dir($temp)) {
            echo "Удаляю существующую временную папку $temp\n";
            exec("rmdir /s /q \"$temp\"");
        }
        
        // Копируем содержимое
        exec("xcopy \"$old\" \"$temp\" /e /i /h /y");
        
        // Удаляем старую
        exec("rmdir /s /q \"$old\"");
        
        // Переименовываем временную в новую
        rename($temp, $new);
        
        echo "✅ Готово: $new\n";
    } else {
        echo "⚠️ Папка $old не найдена\n";
    }
}

echo "\nГотово! Теперь запустите: php composer.phar dump-autoload\n";