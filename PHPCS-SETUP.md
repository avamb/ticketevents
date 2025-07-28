# PHPCS Setup and Usage Guide

Это руководство описывает настройку и использование PHP_CodeSniffer (PHPCS) для проекта Bil24 Connector.

## Что установлено

### Инструменты
- **PHPCS** (`tools/phpcs.phar`) - анализатор кода для выявления нарушений стандартов кодирования
- **PHPCBF** (`tools/phpcbf.phar`) - автоматический исправитель кода
- **WordPress Coding Standards** (`tools/wpcs/`) - стандарты кодирования WordPress

### Конфигурация
- **phpcs.xml.dist** - главный файл конфигурации PHPCS
- **phpcs.bat** - скрипт для запуска проверки кода в Windows
- **phpcbf.bat** - скрипт для автоматического исправления кода в Windows

## Использование

### 1. Проверка кода на соответствие стандартам

#### Windows (рекомендуется):
```cmd
phpcs.bat
```

#### Через PHP напрямую:
```cmd
php tools\phpcs.phar --standard=phpcs.xml.dist
```

#### Проверка конкретного файла:
```cmd
php tools\phpcs.phar --standard=phpcs.xml.dist includes\Plugin.php
```

### 2. Автоматическое исправление кода

#### Windows (рекомендуется):
```cmd
phpcbf.bat
```

#### Через PHP напрямую:
```cmd
php tools\phpcbf.phar --standard=phpcs.xml.dist
```

#### Исправление конкретного файла:
```cmd
php tools\phpcbf.phar --standard=phpcs.xml.dist includes\Plugin.php
```

### 3. Composer скрипты

```cmd
php composer.phar run phpcs    # Проверка кода
php composer.phar run phpcbf   # Исправление кода
php composer.phar run fix      # Alias для phpcbf
php composer.phar run check    # Проверка кода и тесты
```

## Конфигурация стандартов

Файл `phpcs.xml.dist` настроен со следующими стандартами:

### Базовые стандарты
- **PSR2** - базовый стандарт PHP
- **PSR12** - расширенный стандарт PHP

### Дополнительные правила
- Проверка длины строк (лимит 120 символов, максимум 200)
- Запрет коротких открывающих тегов PHP
- Запрет опасных функций
- Запрет подавления ошибок с @
- Современный синтаксис массивов
- Проверка дублирования имен классов

### Исключения
Следующие папки исключены из проверки:
- `vendor/` - зависимости Composer
- `node_modules/` - зависимости Node.js
- `tests/` - тестовые файлы
- `assets/` - статические ресурсы
- `languages/` - файлы локализации
- `templates/` - шаблоны
- `tools/` - инструменты разработки

## Интеграция в рабочий процесс

### 1. Перед коммитом
```cmd
# Исправить все автоматически исправимые проблемы
phpcbf.bat

# Проверить оставшиеся проблемы
phpcs.bat
```

### 2. В IDE/редакторе
Многие IDE поддерживают интеграцию с PHPCS:
- **VS Code**: расширение PHP Sniffer
- **PhpStorm**: встроенная поддержка PHPCS
- **Sublime Text**: пакет PHP CS Fixer

### 3. Настройка pre-commit hook (опционально)
Можно добавить Git hook для автоматической проверки кода перед коммитом:

```bash
# .git/hooks/pre-commit
#!/bin/sh
php tools/phpcs.phar --standard=phpcs.xml.dist --error-severity=1 --warning-severity=8
```

## Результаты проверки

### Типы сообщений
- **ERROR** - серьезные нарушения, требующие исправления
- **WARNING** - предупреждения, рекомендуемые к исправлению

### Символы в выводе
- **E** - найдены ошибки (Errors)
- **W** - найдены предупреждения (Warnings)
- **F** - файл исправлен (Fixed)
- **.** - файл проверен без проблем

### Автоисправление
Ошибки с пометкой `[x]` могут быть исправлены автоматически с помощью PHPCBF.

## Устранение неполадок

### PHP не найден
```
Error: PHP is not available in PATH
```
**Решение**: Установите PHP или добавьте его в переменную PATH.

### PHPCS не найден
```
Error: PHPCS not found at tools\phpcs.phar
```
**Решение**: Убедитесь что файлы `tools/phpcs.phar` и `tools/phpcbf.phar` существуют.

### Файл конфигурации не найден
```
Error: PHPCS configuration file not found
```
**Решение**: Убедитесь что файл `phpcs.xml.dist` существует в корне проекта.

## Дополнительные возможности

### Генерация отчетов
```cmd
# XML отчет
php tools\phpcs.phar --standard=phpcs.xml.dist --report=xml --report-file=phpcs-report.xml

# JSON отчет
php tools\phpcs.phar --standard=phpcs.xml.dist --report=json --report-file=phpcs-report.json

# CSV отчет
php tools\phpcs.phar --standard=phpcs.xml.dist --report=csv --report-file=phpcs-report.csv
```

### Настройка серьезности
```cmd
# Показать только ошибки
php tools\phpcs.phar --standard=phpcs.xml.dist --error-severity=1 --warning-severity=0

# Показать все проблемы
php tools\phpcs.phar --standard=phpcs.xml.dist --error-severity=1 --warning-severity=1
```

### Подробный вывод
```cmd
# Показать обрабатываемые файлы
php tools\phpcs.phar --standard=phpcs.xml.dist -v

# Показать коды правил
php tools\phpcs.phar --standard=phpcs.xml.dist -s
```

---

**Примечание**: Данная настройка использует локальные .phar файлы вместо Composer зависимостей из-за отсутствия openssl расширения в PHP. Это полнофункциональная альтернатива, которая работает без дополнительных требований. 