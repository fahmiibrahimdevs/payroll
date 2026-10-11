# Larashiz Frontend, UI/UX, & Blade Guidelines

All user interface development, Blade templates, styling, data tables, modals, and Livewire interactions MUST follow these standards.

## 1. Template-First Principle (Shizuefi / Stisla)
- **Check Built-in Template Elements First**: Always use native template components (e.g., badges `badge badge-primary`, `badge badge-success`, `badge badge-danger`, buttons, alerts, cards).
- **No Ad-Hoc Modifiers**: Do NOT add arbitrary modifiers to built-in components (e.g. adding `text-dark` to `badge-warning`). Use pure template classes.
- **User Approval for Custom Components**: If a component does not exist in the template and requires new custom CSS classes, ask for user approval first.

## 2. Component-First CSS Standard
- **No Arbitrary Bracket Utility in Blade**: NEVER write classes like `text-[11px]`, `px-[25px]`, `bg-[#f8fafc]`, or inline `style="..."` in Blade templates.
- **Declare in CSS**: Declare semantic classes in `public/assets/css/components-tailwind.css` using `@apply` and compile via `npm run build:shizuefi`.

## 3. Page Header & Section Title Standard
- **Header Slot**: Every page view under `layouts.app` MUST define:
  ```blade
  <x-slot name="header">
      <h1>Judul Halaman</h1>
      <div class="section-header-breadcrumb">
          <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
          <div class="breadcrumb-item">Kategori</div>
      </div>
  </x-slot>
  ```
- **Section Title & Lead**: Immediately below the header slot, MUST include:
  ```blade
  <h2 class="section-title">Judul Seksi</h2>
  <p class="section-lead mb-3">Deskripsi ringkas halaman ini.</p>
  ```
- **No Redundant Section Wrappers**: `layouts/app.blade.php` already automatically wraps `$slot` in `<section class="section custom-section"><div class="section-body">`. NEVER write redundant `<section>` or `<div class="section-body">` in view files.

## 4. Data Table Component Standard
- Follow the structure in `resources/views/livewire/posts/index.blade.php`:
  - Card wrapper: `.card` > `.card-header` > `.card-body.p-0`
  - Top controls: `.dt-control-wrapper` containing `.dt-show-entries` (with `wire:model.live="perPage"`) and `.dt-search-wrapper` (with `wire:model.live.debounce.300ms="search"`).
  - Table: `.table-responsive` > `table.table.table-clean`
  - Table Header: All `<th>` MUST have `class="text-nowrap"`. Primary text columns must not have fixed widths.
  - Table Body: Each row MUST have `wire:key="item-{{ $item->id }}"`, numbering formula `{{ $items->firstItem() + $index }}`, aligned with `.align-middle`.
  - Footer: `.dt-footer-wrapper` with entry counter and `$items->links('livewire.custom-pagination')`.

## 5. Modal & Select2 Standards
- **Modal Header & Footer**: Clean `<div class="modal-header">` (no custom bg) and `<div class="modal-footer bg-whitesmoke br">`.
- **Select2 Focus Trap Fix**:
  - Remove `tabindex="-1"` from modal wrapper to prevent Select2 input focus lock.
  - Configure `dropdownParent: $('#modalId')`.
  - Override `$.fn.modal.Constructor.prototype._enforceFocus = function() {};`.
  - Wrap select in `wire:ignore` and synchronize via `@this.set()`.

## 6. Primary Brand Color (`#0b52aa`)
- The project primary brand color is `#0b52aa`.
- NEVER use old default Stisla indigo/purple (`#6777ef`).
- Floating Action Buttons (FAB), active filter chips, and primary buttons use `#0b52aa` / `.btn-primary`.

## 7. Mandatory SweetAlert2 Notifications & Confirmations
- **All Notifications MUST Use SweetAlert2 Modal Pop-up**: Dispatch `swal:alert` with `type`, `title`, `message`. Toastr/toast corner and static flash banners are strictly prohibited.
- **Destructive Actions & Status Changes**: Deletions and status toggles MUST trigger SweetAlert2 confirmation (`swal:confirm` or `swal:confirm-delete`).
- **User Approval Required**: Ask for user approval before introducing any new confirmation flows.

## 8. Clean Blade Standard (No Inline Logic)
- **Zero `@php` Blocks for Presentation Logic**: NEVER write `match()`, `switch()`, or styling calculations in Blade.
- **Use Eloquent Accessors**: Place badge class mappings and status labels in Eloquent Models (e.g. `$item->status_badge_class`).

## 9. Wire Loading & Spinner Animation Standard
- **Mandatory `wire:loading.attr="disabled"`**: All interactive buttons triggering Livewire actions MUST have `wire:loading.attr="disabled"` and specific `wire:target="..."`.
- **Mandatory Spinner**: Include animated FontAwesome spinner (`<i wire:loading wire:target="..." class="fas fa-spinner fa-spin"></i>`) and hide normal icon (`wire:loading.remove`).

## 10. Single-Line HTML Tag Formatting
- **Single-Line Form Elements**: Tag `<input>`, `<select>`, `<textarea>`, and `<button>` attributes MUST NOT be broken across multiple lines. Write them concisely in one line.
- **Single-Line `<th>` Tags**: All `<th>` tags in `<thead>` MUST be written in one line with their text label (e.g. `<th class="text-nowrap" style="width: 120px;">AKSI</th>`).

## 11. Zero-Wrap Rule for Labels, Nav Pills, Badges & Headers
- **Strict Single-Line UI Elements**: Field labels, form headers, nav pills, status badges, action buttons, and table headers MUST NOT wrap awkwardly into multiple lines on desktop or mobile screens.
- **Concise & Clean Wording**: Keep label texts concise, crisp, and direct (e.g. `Total Kasbon`, `Kepegawaian & Gaji`). Avoid lengthy parenthesized helper notes inside label text that trigger awkward text wrapping.
- **Responsive Pills & Tabs**: When using nav-pills or tabs in modals/forms, always enforce single horizontal row layout (`.nav-fill.flex-row.flex-nowrap`, `style="flex: 1 1 50%;"`, and `.text-nowrap`) to prevent pills from stacking or wrapping in mobile viewports.
