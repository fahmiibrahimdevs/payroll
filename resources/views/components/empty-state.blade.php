@props([
    'icon' => 'fas fa-search',
    'title' => 'Tidak Ada Data Ditemukan',
    'subtitle' => 'Tidak ditemukan data yang sesuai dengan kriteria filter atau kata kunci pencarian saat ini.',
    'actionLabel' => null,
    'actionWireClick' => null,
    'actionUrl' => null,
    'actionIcon' => 'fas fa-plus',
])

<div class="empty-state-wrapper d-flex flex-column align-items-center justify-content-center py-5 px-3 text-center my-2">
    <!-- Friendly Soft Icon Circle (Clean Executive Modern Accent) -->
    <div class="d-inline-flex align-items-center justify-content-center mb-3 shadow-xs"
         style="width: 60px; height: 60px; border-radius: 16px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; color: #0b52aa;">
        <i class="{{ $icon }}" style="font-size: 24px;"></i>
    </div>

    <!-- Title: Dark Crisp Slate -->
    <h6 class="font-weight-700 mb-1.5" style="color: #0f172a; font-size: 15px; letter-spacing: -0.2px;">
        {{ $title }}
    </h6>

    <!-- Subtitle: Crisp Readable High-Contrast Dark Slate -->
    <p class="mb-0" style="color: #475569; font-size: 13px; max-width: 440px; line-height: 1.5;">
        {{ $subtitle }}
    </p>

    <!-- Optional Friendly Action Button -->
    @if ($actionLabel)
        <div class="mt-3">
            @if ($actionWireClick)
                <button type="button" wire:click="{{ $actionWireClick }}" class="btn btn-sm btn-primary d-inline-flex align-items-center font-weight-500 shadow-sm" style="border-radius: 8px; padding: 6px 14px; font-size: 12.5px; background-color: #0b52aa; border-color: #0b52aa;">
                    @if ($actionIcon) <i class="{{ $actionIcon }} mr-1.5" style="font-size: 11px;"></i> @endif
                    {{ $actionLabel }}
                </button>
            @elseif ($actionUrl)
                <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary d-inline-flex align-items-center font-weight-500 shadow-sm" style="border-radius: 8px; padding: 6px 14px; font-size: 12.5px; background-color: #0b52aa; border-color: #0b52aa;">
                    @if ($actionIcon) <i class="{{ $actionIcon }} mr-1.5" style="font-size: 11px;"></i> @endif
                    {{ $actionLabel }}
                </a>
            @endif
        </div>
    @endif
</div>
