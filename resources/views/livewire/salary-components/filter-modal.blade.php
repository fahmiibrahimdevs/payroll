<div wire:ignore.self class="modal fade" id="salaryComponentFilterMobileModal" tabindex="-1" role="dialog" aria-labelledby="salaryComponentFilterMobileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="salaryComponentFilterMobileModalLabel">
                    <i class="fas fa-filter text-primary mr-1"></i> Filter Komponen Gaji
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body py-3 px-4">
                @include('livewire.salary-components.filter-content', ['filterContext' => 'mobile'])
            </div>

            <div class="modal-footer bg-white border-top px-4 py-3">
                <div class="row w-100 no-gutters">
                    <div class="col-6 pr-1">
                        <button type="button" wire:click="resetFilters" class="btn btn-danger btn-block shadow-none"><i class="fas fa-undo mr-1"></i> Reset</button>
                    </div>
                    <div class="col-6 pl-1">
                        <button type="button" class="btn btn-primary btn-block shadow-none" data-dismiss="modal">Tutup / Terapkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
