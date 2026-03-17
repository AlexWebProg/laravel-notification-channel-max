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
