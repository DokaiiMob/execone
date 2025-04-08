# API документация для EXECONE

## Основная информация

Базовый URL API: `/api`

Все запросы к API (кроме `/auth/login`) должны содержать токен авторизации, который можно передать одним из следующих способов:
- Через заголовок `Authorization: Bearer YOUR_TOKEN`
- Через GET-параметр `?token=YOUR_TOKEN`
- Через POST-параметр `token`

Все ответы от API имеют следующий формат:
```json
{
  "success": true|false,
  "message": "Сообщение о результате операции",
  "data": { ... } // Данные ответа, структура зависит от эндпоинта
}
```

## Аутентификация

### POST /api/auth/login

Авторизация пользователя.

**Запрос:**
```json
{
  "username": "username",
  "password": "password"
}
```

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "YOUR_API_TOKEN",
    "user_id": 123,
    "username": "username",
    "role": "user"
  }
}
```

### POST /api/auth/logout

Завершение сессии (деактивация токена).

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

## Информация о пользователе

### GET /api/user/info

Получение информации о пользователе.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "User information retrieved successfully",
  "data": {
    "id": 123,
    "username": "username",
    "email": "user@example.com",
    "role": "user",
    "avatar": "http://localhost/uploads/avatars/avatar.jpg",
    "created_at": "2023-10-01 10:00:00",
    "loyalty": {
      "level": "bronze",
      "level_name": "Бронзовый",
      "days": 30,
      "discount": 5,
      "progress": 30,
      "next_level": "silver",
      "days_for_next_level": 90
    },
    "referrals": {
      "total": 5,
      "active": 3,
      "total_bonus_days": 15,
      "current_bonus_days": 10
    },
    "subscription": {
      "id": 456,
      "plan_type": "premium",
      "plan_name": "Премиум",
      "status": "active",
      "end_date": "2023-11-01 10:00:00",
      "is_active": true,
      "days_left": 30
    }
  }
}
```

### GET /api/user/subscription

Получение информации о подписке пользователя.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Subscription information retrieved successfully",
  "data": {
    "subscription": {
      "id": 456,
      "plan_type": "premium",
      "plan_name": "Премиум",
      "status": "active",
      "start_date": "2023-10-01 10:00:00",
      "end_date": "2023-11-01 10:00:00",
      "is_active": true,
      "days_left": 30
    },
    "history": [
      {
        "id": 456,
        "plan_type": "premium",
        "plan_name": "Премиум",
        "status": "active",
        "start_date": "2023-10-01 10:00:00",
        "end_date": "2023-11-01 10:00:00",
        "payment_id": "payment_123"
      },
      {
        "id": 455,
        "plan_type": "basic",
        "plan_name": "Базовая",
        "status": "expired",
        "start_date": "2023-09-01 10:00:00",
        "end_date": "2023-10-01 10:00:00",
        "payment_id": "payment_122"
      }
    ]
  }
}
```

### GET /api/user/referrals

Получение информации о рефералах пользователя.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "User referrals information retrieved successfully",
  "data": {
    "code": "ABC123",
    "stats": {
      "total_referrals": 5,
      "active_referrals": 3,
      "total_bonus_days": 15,
      "current_bonus_days": 10
    },
    "referrals": [
      {
        "id": 789,
        "username": "referred_user1",
        "status": "active",
        "bonus_earned": 3,
        "created_at": "2023-09-15 12:00:00"
      },
      {
        "id": 790,
        "username": "referred_user2",
        "status": "active",
        "bonus_earned": 3,
        "created_at": "2023-09-20 14:30:00"
      }
    ],
    "referral_url": "http://localhost/register.php?ref=ABC123",
    "rewards": {
      "registration": 3,
      "subscription_percent": 10
    }
  }
}
```

## Подписки

### GET /api/subscription/plans

Получение списка доступных планов подписки.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Subscription plans retrieved successfully",
  "data": {
    "plans": [
      {
        "type": "basic",
        "name": "Базовая",
        "price": 299,
        "duration": 30,
        "features": [
          "Базовые функции чита",
          "Поддержка через форум"
        ],
        "daily_price": 9.97
      },
      {
        "type": "premium",
        "name": "Премиум",
        "price": 599,
        "duration": 30,
        "features": [
          "Все функции чита",
          "Приоритетная поддержка",
          "Ранний доступ к обновлениям"
        ],
        "daily_price": 19.97
      },
      {
        "type": "vip",
        "name": "VIP",
        "price": 1499,
        "duration": 30,
        "features": [
          "Все функции чита",
          "Поддержка 24/7",
          "Эксклюзивные функции",
          "Доступ к бета-версиям"
        ],
        "daily_price": 49.97
      }
    ],
    "custom_plans_available": true
  }
}
```

### POST /api/subscription/purchase

Создание заказа на покупку подписки.

**Запрос:**
```json
{
  "plan_type": "premium",
  "payment_method": "card",
  "duration": 30,
  "promocode": "PROMO10",
  "use_bonus_balance": 1
}
```

Где:
- `plan_type` - тип плана подписки (basic, premium, vip, custom)
- `payment_method` - метод оплаты (card, qiwi, webmoney, crypto)
- `duration` - длительность в днях (обязательно для custom плана)
- `promocode` - промокод (опционально)
- `use_bonus_balance` - использовать ли бонусный баланс (1 или 0, опционально)

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_id": "order_12345",
    "plan_type": "premium",
    "duration": 30,
    "price": 599,
    "payment_method": "card",
    "payment_url": "http://localhost/payment.php?order=order_12345",
    "status": "pending"
  }
}
```

## Читы

### GET /api/cheat/versions

Получение списка версий чита.

**Запрос:**
```
GET /api/cheat/versions?all=1 // Параметр all=1 доступен только для администраторов
```

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Cheat versions retrieved successfully",
  "data": {
    "versions": [
      {
        "id": 1,
        "version": "2.0.0",
        "description": "Новая версия с улучшенными функциями",
        "required_plan": "vip",
        "required_plan_name": "VIP",
        "created_at": "2023-10-01 10:00:00",
        "is_active": true,
        "is_available": true
      },
      {
        "id": 2,
        "version": "1.5.0",
        "description": "Исправлены ошибки, добавлены новые функции",
        "required_plan": "premium",
        "required_plan_name": "Премиум",
        "created_at": "2023-09-15 10:00:00",
        "is_active": true,
        "is_available": true
      },
      {
        "id": 3,
        "version": "1.0.0",
        "description": "Базовая версия",
        "required_plan": "basic",
        "required_plan_name": "Базовая",
        "created_at": "2023-09-01 10:00:00",
        "is_active": true,
        "is_available": true
      }
    ],
    "total": 3
  }
}
```

### GET /api/cheat/download

Получение ссылки на скачивание чита.

**Запрос:**
```
GET /api/cheat/download?version_id=1
```

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Download link generated successfully",
  "data": {
    "version_id": 1,
    "version": "2.0.0",
    "file_name": "cheat_2.0.0_67f51b9de7b26.zip",
    "download_url": "http://localhost/download.php?token=a1b2c3d4e5f6g7h8i9j0",
    "expires_in": 3600,
    "expires_at": "2023-10-01 11:00:00"
  }
}
```

## Уведомления

### GET /api/notifications/check

Проверка наличия новых уведомлений.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Notifications checked successfully",
  "data": {
    "unread_count": 3,
    "has_new": true
  }
}
```

### POST /api/notifications/mark-read

Отметить уведомление как прочитанное.

**Запрос:**
```json
{
  "notification_id": 123
}
```

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### POST /api/notifications/mark-all-read

Отметить все уведомления как прочитанные.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

### DELETE /api/notifications/delete

Удалить уведомление.

**Запрос:**
```json
{
  "notification_id": 123
}
```

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

### DELETE /api/notifications/delete-all

Удалить все уведомления.

**Запрос:** *Не требует параметров*

**Ответ в случае успеха:**
```json
{
  "success": true,
  "message": "All notifications deleted"
}
```

## Коды ошибок

| Код HTTP | Описание |
|----------|----------|
| 200 | Запрос выполнен успешно |
| 400 | Некорректный запрос (проверьте параметры) |
| 401 | Требуется авторизация (токен не предоставлен или недействителен) |
| 403 | Доступ запрещен (недостаточно прав) |
| 404 | Ресурс не найден |
| 405 | Метод не разрешен |
| 500 | Внутренняя ошибка сервера |

## Примеры использования API

### JavaScript (fetch)

```javascript
// Авторизация
async function login(username, password) {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username, password })
  });
  return response.json();
}

// Получение информации о пользователе с использованием токена
async function getUserInfo(token) {
  const response = await fetch('/api/user/info', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return response.json();
}
```

### PHP (cURL)

```php
// Авторизация
function login($username, $password) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/auth/login');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => $username,
    'password' => $password
  ]));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response, true);
}

// Получение информации о пользователе с использованием токена
function getUserInfo($token) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/user/info');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  return json_decode($response, true);
}
```

## Ограничения

- Максимальное количество запросов с одного IP-адреса: 100 запросов в минуту
- Максимальный размер тела запроса: 10 МБ
- Срок действия токена: по умолчанию бессрочно (если не указан иной срок при создании)
