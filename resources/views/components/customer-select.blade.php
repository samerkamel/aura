@props([
    'name' => 'customer_id',
    'id' => 'customer_id',
    'required' => true,
    'selected' => null,
    'placeholder' => 'Search or select customer...',
    'showAddButton' => true,
    'class' => ''
])

<div class="d-flex align-items-center">
    <select class="form-select select2-customer {{ $class }} @error($name) is-invalid @enderror"
            id="{{ $id }}" name="{{ $name }}" style="width: {{ $showAddButton ? 'calc(100% - 50px)' : '100%' }};"
            {{ $required ? 'required' : '' }}
            data-selected="{{ $selected }}">
        <option value="">{{ $placeholder }}</option>
    </select>
    @if($showAddButton)
        <button type="button" class="btn btn-outline-primary ms-2"
                data-bs-toggle="modal" data-bs-target="#addCustomerModal"
                title="Add New Customer">
            <i class="ti ti-plus"></i>
        </button>
    @endif
</div>
@error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror

@once
@push('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endpush

@push('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endpush

@push('page-script')
<script>
(function() {
    function initCustomerSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(initCustomerSelect2, 50);
            return;
        }

        $('.select2-customer').each(function() {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return; // Already initialized
            }

            const preSelected = $select.data('selected');

            $select.select2({
                placeholder: $select.find('option:first').text() || 'Search or select customer...',
                allowClear: true,
                ajax: {
                    url: '{{ route("administration.customers.api.index") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { search: params.term || '' };
                    },
                    processResults: function(data) {
                        return {
                            results: data.customers ? data.customers.map(function(customer) {
                                return {
                                    id: customer.id,
                                    text: customer.text,
                                    customerData: customer
                                };
                            }) : []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Pre-select customer if provided
            if (preSelected) {
                $.ajax({
                    url: '{{ route("administration.customers.api.index") }}',
                    dataType: 'json'
                }).then(function(data) {
                    if (data.customers) {
                        const customer = data.customers.find(c => c.id == preSelected);
                        if (customer) {
                            const option = new Option(customer.text, customer.id, true, true);
                            $select.append(option).trigger('change');
                        }
                    }
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomerSelect2);
    } else {
        initCustomerSelect2();
    }
})();
</script>
@endpush
@endonce
