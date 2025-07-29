<?php
/**
 * Скрипт для создания установочного пакета плагина
 */

echo "Создание установочного пакета Bil24 Connector...\n";

// Папка для пакета
$package_dir = 'bil24-connector-package';
$package_zip = 'bil24-connector-v0.1.3.zip';

// Удаляем старые файлы если есть
if (is_dir($package_dir)) {
    exec("rmdir /s /q \"$package_dir\"");
}
if (file_exists($package_zip)) {
    unlink($package_zip);
}

// Создаем структуру
mkdir($package_dir);
mkdir($package_dir . '/bil24-connector');

echo "Копирование файлов...\n";

// Основные файлы
copy('bil24-connector.php', $package_dir . '/bil24-connector/bil24-connector.php');
copy('composer.json', $package_dir . '/bil24-connector/composer.json');
copy('composer.lock', $package_dir . '/bil24-connector/composer.lock');
copy('README.md', $package_dir . '/bil24-connector/README.md');

// Папки для копирования
$folders_to_copy = [
    'includes',
    'assets', 
    'templates',
    'languages',
    'vendor'
];

foreach ($folders_to_copy as $folder) {
    if (is_dir($folder)) {
        echo "Копирую $folder...\n";
        exec("xcopy \"$folder\" \"$package_dir\\bil24-connector\\$folder\" /e /i /h /y");
    }
}

echo "Создание ZIP архива...\n";

// Создаем ZIP
$zip = new ZipArchive();
if ($zip->open($package_zip, ZipArchive::CREATE) === TRUE) {
    
    function addDirToZip($zip, $dir, $base = '') {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $fullPath = $dir . '/' . $file;
                $relativePath = $base . $file;
                
                if (is_dir($fullPath)) {
                    $zip->addEmptyDir($relativePath);
                    addDirToZip($zip, $fullPath, $relativePath . '/');
                } else {
                    $zip->addFile($fullPath, $relativePath);
                }
            }
        }
    }
    
    addDirToZip($zip, $package_dir . '/bil24-connector', 'bil24-connector/');
    $zip->close();
    
    echo "✅ Создан установочный пакет: $package_zip\n";
} else {
    echo "❌ Ошибка создания ZIP архива\n";
}

// Очищаем временную папку
exec("rmdir /s /q \"$package_dir\"");

echo "\n📦 ГОТОВО!\n";
echo "Файл для установки: $package_zip\n";
echo "\nИнструкция по установке:\n";
echo "1. Скачайте файл $package_zip\n";
echo "2. В WordPress админке: Плагины → Добавить новый → Загрузить плагин\n";
echo "3. Выберите файл $package_zip\n";
echo "4. Нажмите 'Установить сейчас'\n";
echo "5. Активируйте плагин\n"; 