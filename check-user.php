<?php
/**
 * Диагностика прав пользователя
 * ДОСТУП ТОЛЬКО ДЛЯ АВТОРИЗОВАННЫХ АДМИНОВ
 */

// Найти WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('⚠️ WordPress не найден. Скопируйте этот код в functions.php вашей темы вместо запуска файла.');
}

// Проверка авторизации
if (!is_user_logged_in()) {
    die('❌ Вы не авторизованы. Войдите в админку WordPress.');
}

echo '<h1>🔍 Диагностика прав пользователя Bil24 Connector</h1>';
echo '<style>body{font-family:monospace;margin:20px} .ok{color:green} .error{color:red} .warning{color:orange} table{border-collapse:collapse;margin:10px 0} td,th{border:1px solid #ccc;padding:8px;text-align:left}</style>';

// 1. Информация о пользователе
$user = wp_get_current_user();
echo '<h2>👤 Информация о пользователе</h2>';
echo '<table>';
echo '<tr><th>Параметр</th><th>Значение</th></tr>';
echo '<tr><td>ID</td><td>' . $user->ID . '</td></tr>';
echo '<tr><td>Логин</td><td>' . $user->user_login . '</td></tr>';
echo '<tr><td>Email</td><td>' . $user->user_email . '</td></tr>';
echo '<tr><td>Роли</td><td>' . implode(', ', $user->roles) . '</td></tr>';
echo '</table>';

// 2. Проверка ключевых прав
echo '<h2>🔑 Проверка прав (capabilities)</h2>';

$capabilities_to_check = [
    'manage_options' => 'Управление настройками WordPress',
    'administrator' => 'Роль администратора',
    'edit_plugins' => 'Редактирование плагинов',
    'activate_plugins' => 'Активация плагинов',
    'install_plugins' => 'Установка плагинов',
    'switch_themes' => 'Смена тем',
    'edit_users' => 'Редактирование пользователей',
    'delete_users' => 'Удаление пользователей',
    'edit_posts' => 'Редактирование записей',
    'read' => 'Чтение контента'
];

echo '<table>';
echo '<tr><th>Право</th><th>Описание</th><th>Статус</th></tr>';

foreach ($capabilities_to_check as $cap => $description) {
    $has_cap = current_user_can($cap);
    $status = $has_cap ? '<span class="ok">✅ ЕСТЬ</span>' : '<span class="error">❌ НЕТ</span>';
    echo "<tr><td>{$cap}</td><td>{$description}</td><td>{$status}</td></tr>";
}
echo '</table>';

// 3. Все права пользователя
echo '<h2>📋 Все права пользователя</h2>';
$all_caps = $user->allcaps;
ksort($all_caps);

echo '<table>';
echo '<tr><th>Право</th><th>Значение</th></tr>';
foreach ($all_caps as $cap => $value) {
    if ($value) {
        echo "<tr><td>{$cap}</td><td><span class='ok'>✅</span></td></tr>";
    }
}
echo '</table>';

// 4. Проверка специфично для нашего плагина
echo '<h2>🔧 Диагностика плагина Bil24</h2>';

// Проверяем существование меню
global $submenu;
$bil24_menu_found = false;

if (isset($submenu['options-general.php'])) {
    foreach ($submenu['options-general.php'] as $menu_item) {
        if (isset($menu_item[2]) && $menu_item[2] === 'bil24-connector') {
            $bil24_menu_found = true;
            echo "✅ <span class='ok'>Меню Bil24 найдено в настройках</span><br>";
            echo "   Заголовок: " . $menu_item[0] . "<br>";
            echo "   Права: " . $menu_item[1] . "<br>";
            echo "   Slug: " . $menu_item[2] . "<br>";
            break;
        }
    }
}

if (!$bil24_menu_found) {
    echo "❌ <span class='error'>Меню Bil24 НЕ НАЙДЕНО в настройках</span><br>";
}

// 5. Проверка URL и доступа
echo '<h2>🌐 Проверка URL</h2>';

$settings_url = admin_url('options-general.php?page=bil24-connector');
echo "URL настроек: <a href='{$settings_url}' target='_blank'>{$settings_url}</a><br>";

// Проверяем права на конкретный URL
$page_slug = 'bil24-connector';
$parent_slug = 'options-general.php';

// Имитируем проверку WordPress
$required_capability = 'manage_options'; // Стандартная для options-general.php

echo "<br><strong>Анализ доступа:</strong><br>";
echo "Родительская страница: {$parent_slug}<br>";
echo "Требуемое право: {$required_capability}<br>";
echo "У пользователя есть право: " . (current_user_can($required_capability) ? '<span class="ok">✅ ДА</span>' : '<span class="error">❌ НЕТ</span>') . "<br>";

// 6. Проверка классов плагина
echo '<h2>🔧 Состояние плагина</h2>';

$plugin_classes = [
    '\\Bil24\\Plugin' => 'Основной класс плагина',
    '\\Bil24\\Admin\\SettingsPage' => 'Класс страницы настроек',
    '\\Bil24\\Constants' => 'Константы плагина'
];

foreach ($plugin_classes as $class => $description) {
    $exists = class_exists($class);
    $status = $exists ? '<span class="ok">✅ ЗАГРУЖЕН</span>' : '<span class="error">❌ НЕ ЗАГРУЖЕН</span>';
    echo "{$description} ({$class}): {$status}<br>";
}

// 7. Проверка хуков
echo '<h2>🪝 Проверка хуков WordPress</h2>';

global $wp_filter;

$hooks_to_check = ['admin_menu', 'admin_init', 'plugins_loaded'];
foreach ($hooks_to_check as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "✅ Хук {$hook}: " . count($wp_filter[$hook]->callbacks) . " коллбэков<br>";
    } else {
        echo "❌ Хук {$hook}: не найден<br>";
    }
}

echo '<h2>💡 Рекомендации</h2>';

if (!current_user_can('manage_options')) {
    echo '<p class="error">🚨 КРИТИЧЕСКАЯ ПРОБЛЕМА: У пользователя нет права "manage_options"</p>';
    echo '<p>Возможные причины:</p>';
    echo '<ul>';
    echo '<li>Пользователь не является администратором</li>';
    echo '<li>Права были изменены плагином или кодом</li>';
    echo '<li>Проблемы с базой данных WordPress</li>';
    echo '</ul>';
} else if (!$bil24_menu_found) {
    echo '<p class="warning">⚠️ Меню плагина не зарегистрировано. Проблема в инициализации плагина.</p>';
} else {
    echo '<p class="ok">✅ Права пользователя в порядке. Проблема может быть в логике плагина.</p>';
}

echo '<hr>';
echo '<p><strong>Скопируйте эту информацию и предоставьте разработчику для диагностики!</strong></p>';
?> 