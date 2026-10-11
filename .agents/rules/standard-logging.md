# Larashiz Standard Logging v1.0 Rules

All application services, APIs, and background processes MUST adhere to the Standard Logging v1.0 specification.

## 1. Log Levels & RFC 2119 Semantics
- **DEBUG**: Technical development details. Inactive in production.
- **INFO**: Normal significant business/operational events (`Post created`, `User logged in`).
- **WARN**: Suboptimal condition where system still functions (`Retry to gateway`, `User login failed`).
- **ERROR**: Operation failed and requires human attention.
- **CRITICAL**: Core infrastructure down / total failure.
- *Note*: Validation failures (user form error) or 404s are NOT errors. Use INFO/WARN or skip.

## 2. Static Message & Dynamic Context
- **Static Message**: Log messages MUST be static strings without variable interpolation (e.g. `'Order created'`, `'Post update failed'`).
- **Dynamic Context**: All dynamic variables MUST be passed in `context` array using `snake_case` keys (e.g. `['post_id' => $post->id, 'author_id' => $user->id]`).

## 3. Request Correlation & Tracing
- Every HTTP-scoped log includes `request_id` (via `AssignRequestId` middleware).
- Correlate external system calls and services using this identifier.

## 4. Exception Handling
- Always log full exception context: `type`, `message`, `stack`, `file`, `line`.
- NEVER swallow exceptions in empty `catch` blocks.
- Log once at the handling layer; avoid duplicate log-and-rethrow patterns.

## 5. Sensitive Data Protection & Masking
- **STRICTLY PROHIBITED in Logs**: Passwords, hashes, PINs, OTPs, session IDs, bearer tokens, API secrets, full credit cards, CVVs, and Authorization headers.
- **Masking**: Automatically mask emails (`f***@mail.com`) and phone numbers (`0812****5678`).

## 6. Implementation via `ActivityLogService`
Use `ActivityLogService` for audit and activity logging:
```php
$logger = app(ActivityLogService::class);

// INFO log
$logger->info(
    action: 'CREATE',
    module: 'posts',
    message: 'Post created',
    context: ['post_id' => $post->id, 'title' => $post->title]
);

// ERROR log
$logger->error(
    action: 'CREATE_FAILED',
    module: 'posts',
    message: 'Post creation failed',
    context: ['payload' => $data],
    exception: $e
);
```
