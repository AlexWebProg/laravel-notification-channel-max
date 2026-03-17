# MAX Notification Channel для Laravel

Канал уведомлений Laravel для мессенджера [MAX](https://max.ru).

## Установка

```bash
composer require alexwebprog/laravel-notification-channel-max
```

Добавьте токен бота в `.env`:

```env
MAX_BOT_TOKEN=your-bot-token-here
```

Опционально опубликуйте конфиг:

```bash
php artisan vendor:publish --tag=max-notification-config
```

## Использование

### 1. Добавьте роутинг в модель

```php
class User extends Authenticatable
{
    use Notifiable;

    /**
     * Куда отправлять уведомления MAX.
     */
    public function routeNotificationForMax(): int|array
    {
        // Вариант A: отправка пользователю по user_id
        return $this->max_user_id;

        // Вариант B: отправка в чат
        // return ['chat_id' => $this->max_chat_id];
    }
}
```

### 2. Создайте уведомление

```php
use Illuminate\Notifications\Notification;
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
        return MaxMessage::create("Оплата получена: {$this->invoice->amount} ₽")
            ->markdown()
            ->inlineKeyboard([
                [
                    ['type' => 'link', 'text' => 'Открыть счёт', 'url' => $this->invoice->url],
                ],
            ]);
    }
}
```

### 3. Отправьте

```php
$user->notify(new InvoicePaid($invoice));
```

## API — MaxMessage

| Метод | Описание |
|---|---|
| `::create(?string $text)` | Статический конструктор |
| `->text(string $text)` | Текст сообщения (до 4000 символов) |
| `->to(int $userId)` | Отправить пользователю |
| `->toChat(int $chatId)` | Отправить в чат |
| `->markdown()` | Формат Markdown |
| `->html()` | Формат HTML |
| `->silent()` | Без уведомления участникам чата |
| `->disableLinkPreview()` | Отключить превью ссылок |
| `->inlineKeyboard(array $buttons)` | Добавить инлайн-клавиатуру |
| `->attachment(array $attachment)` | Добавить произвольное вложение |
| `->replyTo(string $messageId)` | Ответить на сообщение |
| `->forward(string $messageId)` | Переслать сообщение |
| `->photo(string $filePath)` | Загрузить и прикрепить изображение |
| `->video(string $filePath)` | Загрузить и прикрепить видео |
| `->audio(string $filePath)` | Загрузить и прикрепить аудио |
| `->file(string $filePath)` | Загрузить и прикрепить файл |
| `->send()` | Отправить сообщение напрямую (без Notification) |

## Прямая отправка из кода

Помимо стандартного механизма Laravel Notifications, можно отправлять сообщения напрямую — из контроллеров, job'ов, console command'ов и любого другого места.

### Вариант 1: Цепочка с `->send()`

Самый компактный способ — вызов `->send()` в конце цепочки:

```php
use NotificationChannels\Max\MaxMessage;

MaxMessage::create('Здравствуйте! Нажмите кнопку ниже.')
    ->to(123456)
    ->inlineKeyboard([
        [
            ['type' => 'request_contact', 'text' => 'Отправить мой номер телефона'],
        ],
    ])
    ->send();
```

### Вариант 2: Через MaxApi (dependency injection)

Если нужен контроль над ответом или вы предпочитаете явное внедрение зависимостей:

```php
use NotificationChannels\Max\MaxApi;
use NotificationChannels\Max\MaxMessage;

public function sendWelcome(MaxApi $api): void
{
    $message = MaxMessage::create('Добро пожаловать!')
        ->to($user->max_user_id);

    $response = $api->sendMessage($message);
}
```

### Вариант 3: Через контейнер

В местах, где DI недоступен (замыкания, статические методы):

```php
app(MaxApi::class)->sendMessage($message);
```
## Медиафайлы

Методы `->photo()`, `->video()`, `->audio()` и `->file()` автоматически загружают файл на серверы MAX и прикрепляют его к сообщению. Достаточно передать путь к файлу:

```php
// Изображение с текстом и кнопкой
MaxMessage::create('Расчёт стоимости ремонта')
    ->toChat(-123456)
    ->photo(storage_path('app/images/banner.jpg'))
    ->inlineKeyboard([
        [
            ['type' => 'link', 'text' => 'Рассчитать стоимость', 'url' => 'https://example.com/calc'],
        ],
    ])
    ->send();
```

```php
// Отправка документа пользователю
MaxMessage::create('Ваш отчёт готов')
    ->to($user->max_user_id)
    ->file(storage_path('app/reports/report.pdf'))
    ->send();
```

```php
// Видео
MaxMessage::create('Видеоинструкция')
    ->toChat($chatId)
    ->video(storage_path('app/videos/tutorial.mp4'))
    ->send();
```

Поддерживаемые форматы: изображения (JPG, PNG, GIF, TIFF, BMP, HEIC), видео (MP4, MOV, MKV, WEBM), аудио (MP3, WAV, M4A), файлы (любые). Максимальный размер — 4 ГБ.

> **Примечание:** после загрузки больших файлов может потребоваться небольшая пауза перед отправкой — сервер MAX обрабатывает файл асинхронно. Если получаете ошибку `attachment.not.ready`, повторите отправку через несколько секунд.

## Пример: кнопки

```php
MaxMessage::create('Выберите действие:')
    ->inlineKeyboard([
        // Первый ряд
        [
            ['type' => 'callback', 'text' => 'Подтвердить', 'payload' => 'confirm'],
            ['type' => 'callback', 'text' => 'Отмена', 'payload' => 'cancel'],
        ],
        // Второй ряд
        [
            ['type' => 'link', 'text' => 'Подробнее', 'url' => 'https://example.com'],
        ],
    ]);
```

## Пример: тихая отправка с HTML-форматированием

```php
MaxMessage::create('<b>Внимание!</b> Обновление системы в 03:00')
    ->html()
    ->silent();
```

## Лицензия

MIT
