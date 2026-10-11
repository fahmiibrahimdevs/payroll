<div wire:ignore.self class="modal fade" id="employeeFilterMobileModal" tabindex="-1" role="dialog" aria-labelledby="employeeFilterMobileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeFilterMobileModalLabel">
                    Filter Karyawan
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body">
                @include('livewire.employees.filter-content', ['filterContext' => 'mobile'])
            </div>

            <div class="modal-footer bg-whitesmoke br">
                <button type="button" class="btn btn-primary btn-block shadow-none" data-dismiss="modal">Tutup / Terapkan</button>
            </div>
        </div>
    </div>
</div>
