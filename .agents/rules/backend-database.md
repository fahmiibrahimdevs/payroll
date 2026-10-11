# Larashiz Backend, Database, & Security Rules

All backend development, database design, query optimizations, security validations, and transactions MUST follow these guidelines.

## 1. Database Schema & Indexing Standards
- **Access Pattern Analysis**: Any column used in `WHERE`, `ORDER BY`, `GROUP BY`, or `FOREIGN KEY` must be indexed.
- **Composite Index (Multi-Column Index)**: For queries filtering/sorting on multiple columns, create a composite index following the *Left-to-Right / Index Prefix* rule (highest selectivity / cardinality first).
  - Examples: `activity_logs` -> `['module', 'action']`, `['level', 'created_at']`, `['user_id', 'created_at']`.
  - `posts` -> `['status', 'published_at']`, `['user_id', 'status']`.
- **Foreign Key Constraints**: Define clear cascade/null/restrict constraints on all relational columns.
- **Unique Constraints**: Prevent race conditions by defining database-level `unique()` constraints on unique business data (`email`, `slug`, combination keys).
- **Index Cost Awareness**: Avoid over-indexing low-cardinality boolean columns unless part of a composite index.

## 2. Database Transactions (ACID) & Anti-Deadlock Rule
- **Atomic Multi-Step Mutations**: Wrap any multi-row or multi-table mutations inside `DB::transaction(function () { ... })`.
- **Anti-Deadlock / No Slow I/O in Transactions**: NEVER make external HTTP requests, send synchronous emails, or generate heavy files inside a `DB::transaction()` block. Dispatch asynchronous queued jobs *after commit*.

## 3. Bulletproof Security & Boundary Validation
- **Strict Boundary Validation**: Never trust user input. Validate data types, string lengths (`max:255`), relationship existence (`exists:`), uniqueness (`unique:`), and Enums (`Rule::enum()`).
- **File Upload Limits**: Always restrict MIME types (`mimes:jpg,jpeg,png,pdf`) and maximum size (`max:2048` KB).
- **Input Sanitization**: Normalize inputs (e.g. `trim()`, lowercase emails, strip unsafe HTML tags).
- **IDOR Protection & Authorization**: Verify ownership and permissions via Laratrust (`$user->hasRole()`, `$user->hasPermission()`) or Laravel Policies before mutating data.
- **Mass Assignment Prevention**: Define explicit `$fillable` on all models. Never use `$guarded = []`.
- **SQL Injection Prevention**: Always use Eloquent/Query Builder parameter bindings. Never concatenate variables directly into raw SQL strings.
- **Rate Limiting**: Protect authentication and sensitive mutation endpoints with Laravel rate limiters (`throttle:6,1`).

## 4. Query Performance & Scalability
- **Eager Loading (Prevent N+1)**: Always use `with(['relation'])` or `loadMissing()` when iterating through collections in views/components.
- **Mandatory Pagination**: Always use `paginate($perPage)` or `simplePaginate()` for dynamic operational tables. Never call unbounded `->get()` or `->all()`.
- **Selective Columns**: Avoid `SELECT *` on tables with large text/json columns; select only required columns (`->select(['id', 'title', 'created_at'])`).
- **Efficient Existence & Aggregations**: Use `->exists()` or `->doesntExist()` instead of `->count() > 0`. Run database-level aggregates (`->sum()`, `->avg()`) instead of collection methods.
- **Chunking Large Datasets**: Use `chunkById()` or `lazyById()` in background jobs/commands to keep memory usage constant.

## 5. Testing Database Isolation
- Automated tests MUST use isolated test database `DB_DATABASE=laravel_test` (configured in `phpunit.xml`).
- NEVER run tests touching the main development database.
- Every Feature Test class must use `Illuminate\Foundation\Testing\DatabaseTransactions` to ensure clean rollback.

## 6. PHP 8.4 Conventions & Formatting
- Format all modified PHP files: `vendor/bin/pint --format agent`.
- Use constructor property promotion, explicit parameter types, and explicit return type declarations.
- Always use curly braces `{}` for all control structures.
