# MAX Notification Channel for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alexwebprog/laravel-notification-channel-max.svg?style=flat-square)](https://packagist.org/packages/alexwebprog/laravel-notification-channel-max)
[![Total Downloads](https://img.shields.io/packagist/dt/alexwebprog/laravel-notification-channel-max.svg?style=flat-square)](https://packagist.org/packages/alexwebprog/laravel-notification-channel-max)

Канал уведомлений Laravel для мессенджера [MAX](https://max.ru).

## Установка

```bash
composer require alexwebprog/laravel-notification-channel-max
```

Опубликуйте конфиг:

```bash
php artisan vendor:publish --tag=max-notification-config
```

Добавьте токен бота в `.env`:

```
MAX_NOTIFICATION_BOT_TOKEN=your-bot-token
```

## Настройка

### URL API и сертификат Минцифры

По умолчанию пакет использует `https://platform-api2.max.ru` и встроенный сертификат удостоверяющего центра Минцифры для проверки SSL. Это работает «из коробки» без дополнительной настройки.

При необходимости вы можете переопределить настройки в `.env`:

```env
# Базовый URL API (по умолчанию: https://platform-api2.max.ru)
MAX_API_BASE_URL=https://platform-api2.max.ru

# Путь к пользовательскому CA-сертификату (по умолчанию: встроенный сертификат Минцифры)
MAX_CA_CERTIFICATE=/path/to/custom-ca.crt

# Отключить проверку SSL (не рекомендуется для продакшена)
MAX_VERIFY_SSL=false
```

## Использование

### Канал уведомлений

Добавьте метод `toMax` в ваш класс уведомления:

```php
use NotificationChannels\Max\MaxChannel;
use NotificationChannels\Max\MaxMessage;

class InvoicePaid extends Notification
{
    public function via($notifiable): array
    {
        return [MaxChannel::class];
    }

    public function toMax($notifiable): MaxMessage
    {
        return MaxMessage::create('Ваш счёт оплачен!')
            ->markdown();
    }
}
```

В модели `User` (или другом `Notifiable`) добавьте метод маршрутизации:

```php
public function routeNotificationForMax(): ?int
{
    return $this->max_user_id;
}
```

### On-demand уведомления (Notification::route)

Можно отправлять уведомления без привязки к модели через `Notification::route`:

```php
use Illuminate\Support\Facades\Notification;

// Отправка пользователю по user_id
Notification::route('max', $maxUserId)
    ->notify(new MyNotification($data));

// Отправка в чат/канал по chat_id
Notification::route('max', ['chat_id' => $chatId])
    ->notify(new MyNotification($data));
```

Скалярное значение трактуется как `user_id`. Для отправки в чат передайте массив `['chat_id' => $chatId]`.

### Прямая отправка

Вы можете отправлять сообщения напрямую без уведомлений:

```php
use NotificationChannels\Max\MaxMessage;

MaxMessage::create('Привет!')
    ->to($maxUserId)
    ->markdown()
    ->send();
```

Отправка в канал/чат:

```php
MaxMessage::create('Новый пост в канале')
    ->toChat($chatId)
    ->send();
```

### Использование другого токена бота

Вы можете переопределить токен бота для конкретного сообщения. Это полезно, если у вас несколько ботов:

```php
MaxMessage::create('Сообщение от другого бота')
    ->to($maxUserId)
    ->token('токен-другого-бота')
    ->send();
```

В уведомлении:

```php
public function toMax($notifiable): MaxMessage
{
    return MaxMessage::create('Уведомление от другого бота')
        ->token('токен-другого-бота');
}
```

При использовании `->token()` все операции (отправка сообщения, загрузка файлов) будут выполняться с указанным токеном.

## API

| Метод | Описание |
|---|---|
| `create(?string $text)` | Создать экземпляр сообщения (статический) |
| `text(string $text)` | Задать текст сообщения |
| `to(int $userId)` | Задать ID пользователя-получателя |
| `toChat(int $chatId)` | Задать ID чата/канала |
| `token(string $token)` | Использовать другой токен бота |
| `markdown()` | Формат текста — Markdown |
| `html()` | Формат текста — HTML |
| `format(string $format)` | Задать формат текста явно |
| `disableLinkPreview(bool $disable = true)` | Отключить превью ссылок |
| `silent(bool $silent = true)` | Отправить без уведомления |
| `inlineKeyboard(array $buttons)` | Добавить инлайн-клавиатуру |
| `photo(string $filePath)` | Загрузить и прикрепить изображение |
| `video(string $filePath)` | Загрузить и прикрепить видео |
| `audio(string $filePath)` | Загрузить и прикрепить аудио |
| `file(string $filePath)` | Загрузить и прикрепить файл |
| `attachment(array $attachment)` | Добавить произвольное вложение |
| `replyTo(string $messageId)` | Ответить на сообщение |
| `forward(string $messageId)` | Переслать сообщение |
| `send()` | Отправить сообщение напрямую |

## Медиафайлы

Пакет поддерживает загрузку и отправку медиафайлов:

```php
// Изображение
MaxMessage::create('Фото')
    ->to($userId)
    ->photo('/path/to/image.jpg')
    ->send();

// Видео
MaxMessage::create('Видео')
    ->to($userId)
    ->video('/path/to/video.mp4')
    ->send();

// Аудио
MaxMessage::create('Аудио')
    ->to($userId)
    ->audio('/path/to/audio.mp3')
    ->send();

// Файл
MaxMessage::create('Документ')
    ->to($userId)
    ->file('/path/to/document.pdf')
    ->send();
```

## Кнопки

```php
MaxMessage::create('Выберите действие:')
    ->to($userId)
    ->inlineKeyboard([
        [
            ['type' => 'link', 'text' => 'Открыть сайт', 'url' => 'https://example.com'],
        ],
        [
            ['type' => 'callback', 'text' => 'Нажми меня', 'payload' => 'button_clicked'],
        ],
        [
            ['type' => 'request_contact', 'text' => 'Отправить контакт'],
        ],
    ])
    ->send();
```

## Тихая отправка

```php
MaxMessage::create('Тихое сообщение')
    ->to($userId)
    ->silent()
    ->send();
```

## Миграция с v1.x

В версии 2.0 изменён URL API по умолчанию с `platform-api.max.ru` на `platform-api2.max.ru` и добавлена автоматическая поддержка сертификата Минцифры. Обновите конфигурацию:

```bash
php artisan vendor:publish --tag=max-notification-config --force
```

## Лицензия

MIT
