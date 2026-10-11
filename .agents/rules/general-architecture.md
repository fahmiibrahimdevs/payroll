# Larashiz General Software Engineering & Code Quality Rules

This workspace follows strict Clean Architecture, Clean Code, and Senior Software Engineering standards. All AI agents and developers MUST adhere to these rules.

## 1. Senior Mindset & Principles
- **Think Before You Code**: Analyze domain logic, data flow, edge cases, performance bottlenecks, and security boundaries before writing code.
- **KISS & YAGNI**: Keep It Simple; avoid premature abstractions and over-engineering.
- **The Boy Scout Rule**: Always leave code cleaner, more structured, and more readable than you found it.

## 2. Layered Architecture & Separation of Concerns
- **Presentation Layer (Controllers, Livewire Components, Blade Views)**:
  - Thin Controller / Thin Component: only handle input validation boundary, delegate to Service layer, and return views.
  - NEVER write complex database queries, multi-step transactions, or business calculation formulas inside Controllers / Livewire Components.
- **Service Layer (app/Services/)**:
  - Rich Service: encapsulates all business logic, data mutation orchestration, atomic database transactions (`DB::transaction`), and standard audit logging.
  - Use Dependency Injection via PHP 8 constructor promotion (`public function __construct(protected SomeService $service) {}`).
- **Domain & Data Access Layer (Models, Enums, Value Objects)**:
  - Define explicit `$fillable` attributes and casts.
  - Reusable query scopes and model relationships.

## 3. Clean Code Rules
- **Readability is King**: Write self-documenting code with intention-revealing names. Avoid cryptic abbreviations (`chk()`, `temp()`, `data()`).
- **Single Responsibility Principle (SRP)**: Methods should be short (10–30 lines) and focused on a single level of abstraction (SLAP).
- **Guard Clauses & Early Returns**: Prevent deep if/else nesting (maximum 2 nesting levels). Fail-fast with early returns or exceptions.
- **Zero Magic Numbers & Magic Strings**: Always use PHP 8.1+ backed Enums or class constants (e.g., `PostStatus::PUBLISHED`).
- **Zero Dead Code & Meaningful Comments**: Never leave commented-out dead code. Comments are strictly for "Why", not for describing "What" the code does.

## 4. Gold Standard CRUD Reference (Post Module)
All new CRUD modules MUST follow the proven patterns in the Post Module:
- **Livewire Component**: `app/Livewire/Posts/Index.php`
- **Service Layer**: `app/Services/PostService.php` (encapsulating query filters, `DB::transaction`, and logging)
- **Eloquent Model**: `app/Models/Post.php`
- **Modular Views**: `resources/views/livewire/posts/` (`index.blade.php`, `form-modal.blade.php`, `detail-modal.blade.php`, `filter-modal.blade.php`)
- **Automated Feature Test**: `tests/Feature/PostCrudTest.php`

## 5. Anti-Hallucination & Quality Verification
- **Schema Grounding**: Always inspect `database/migrations/` before writing model attributes, queries, or validation rules.
- **Version Grounding**: Check `composer.json` and `package.json` for installed package versions.
- **Mandatory Quality Execution**:
  1. Format code with Pint: `vendor/bin/pint --format agent`
  2. Run automated test suite: `php artisan test --filter="TestClass"`
  3. Compile frontend assets if CSS was modified: `npm run build:shizuefi`
