# CI/CD Pipeline Documentation

Этот документ описывает настроенную CI/CD инфраструктуру для WordPress плагина Bil24 Connector.

## Обзор Workflows

### 1. Основной CI/CD Pipeline (`ci.yml`)

**Триггеры:**
- Push в ветки `main`, `develop`
- Pull requests в ветки `main`, `develop`
- Создание релиза

**Задачи:**

#### Lint Job
- Проверка качества кода и стиля
- Запуск на PHP 8.0, 8.1, 8.2, 8.3
- Валидация `composer.json`
- PHPCS (стиль кода)
- PHPStan (статический анализ)

#### Test Job
- Unit и интеграционные тесты
- Тестирование на комбинациях PHP (8.0-8.3) и WordPress (6.0-6.4)
- MySQL 8.0 как сервис
- Покрытие кода с отправкой в Codecov
- Установка WordPress test suite

#### Security Job
- Аудит безопасности Composer
- Проверка известных уязвимостей

#### Compatibility Job
- Проверка совместимости с WordPress
- Валидация заголовков плагина
- WordPress Coding Standards

#### Build Job (только для релизов)
- Создание готового пакета плагина
- Исключение dev-файлов
- Создание ZIP архива
- Загрузка как Release Asset

#### Deploy Job (только для стабильных релизов)
- Автоматическая публикация в WordPress.org
- Требует настройки секретов `WP_ORG_USERNAME` и `WP_ORG_PASSWORD`

### 2. Расширенные Проверки Безопасности (`security.yml`)

**Триггеры:**
- Еженедельно по понедельникам в 2:00
- Ручной запуск

**Задачи:**
- Подробный аудит безопасности
- PHP Mess Detector для качества кода
- Расширенная проверка совместимости с WordPress

### 3. Матричное Тестирование (`test-matrix.yml`)

**Триггеры:**
- Push/PR в основные ветки
- Еженедельно по воскресеньям

**Особенности:**
- Полная матрица PHP × WordPress версий
- Тестирование мультисайт конфигураций
- Тестирование nightly builds WordPress
- Проверка активации плагина

### 4. Проверка Pull Requests (`pr-check.yml`)

**Триггеры:**
- Открытие, синхронизация, переоткрытие PR

**Быстрые проверки:**
- Анализ только измененных файлов
- Быстрая проверка синтаксиса
- Базовые тесты безопасности
- Оптимизирован для скорости

## Настройка

### Необходимые Секреты GitHub

Для полной функциональности добавьте в настройки репозитория:

```
WP_ORG_USERNAME=your-wordpress-org-username
WP_ORG_PASSWORD=your-wordpress-org-password
CODECOV_TOKEN=your-codecov-token (опционально)
```

### Локальная Разработка

Для запуска тех же проверок локально:

```bash
# Установка зависимостей
composer install

# Проверка стиля кода
composer run phpcs

# Исправление стиля кода
composer run phpcbf

# Статический анализ
composer run phpstan

# Запуск тестов
composer run test

# Все проверки
composer run check
```

### WordPress Test Suite

Для локального тестирования с WordPress:

```bash
# Установка test suite
bash tests/bin/install-wp-tests.sh wordpress_test db_user db_pass localhost latest

# Запуск тестов
php phpunit.phar
```

## Файлы Конфигурации

- **`.github/workflows/.buildignore`** - Файлы для исключения из релизной сборки
- **`tests/bin/install-wp-tests.sh`** - Скрипт установки WordPress test suite
- **`phpcs.xml.dist`** - Конфигурация PHPCS
- **`phpunit.xml`** - Конфигурация PHPUnit
- **`composer.json`** - Зависимости и скрипты

## Покрытие Кода

Репорты покрытия кода автоматически отправляются в Codecov. Добавьте badge в README:

```markdown
[![codecov](https://codecov.io/gh/yourname/bil24-connector/branch/main/graph/badge.svg)](https://codecov.io/gh/yourname/bil24-connector)
```

## Status Badges

Добавьте в основной README следующие badges:

```markdown
[![CI/CD Pipeline](https://github.com/yourname/bil24-connector/workflows/CI/CD%20Pipeline/badge.svg)](https://github.com/yourname/bil24-connector/actions)
[![Test Matrix](https://github.com/yourname/bil24-connector/workflows/Test%20Matrix/badge.svg)](https://github.com/yourname/bil24-connector/actions)
[![Security & Code Quality](https://github.com/yourname/bil24-connector/workflows/Security%20&%20Code%20Quality/badge.svg)](https://github.com/yourname/bil24-connector/actions)
```

## Расширение Pipeline

### Добавление Новых Проверок

1. Создайте новый workflow файл в `.github/workflows/`
2. Используйте существующие actions для согласованности
3. Настройте правильные триггеры
4. Добавьте кэширование для ускорения

### Настройка Уведомлений

Настройте уведомления в Slack/Teams/Email через GitHub Actions:

```yaml
- name: Notify on failure
  if: failure()
  uses: 8398a7/action-slack@v3
  with:
    status: failure
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

## Лучшие Практики

1. **Используйте кэширование** для ускорения сборок
2. **Параллелизуйте задачи** где возможно
3. **Ограничивайте matrix builds** для экономии ресурсов
4. **Используйте continue-on-error** для экспериментальных функций
5. **Версионируйте actions** для стабильности (@v4, @v3)
6. **Группируйте связанные проверки** в отдельные jobs

## Отладка

Для отладки failed builds:

1. Проверьте логи в GitHub Actions
2. Запустите команды локально
3. Используйте `continue-on-error: true` для временного игнорирования
4. Проверьте совместимость версий PHP/WordPress
5. Убедитесь в правильности путей к файлам 