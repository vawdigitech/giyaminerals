@extends('layouts.app')
@section('page_title', 'New Stock Transfer')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Transfer History</a></li>
    <li class="breadcrumb-item active">New Transfer</li>
@endsection
@section('content')

<form method="POST" action="{{ route('transfers.store') }}">
    @csrf
    <div class="card">
        <div class="card-body">

            <div class="form-group">
                <label>Product</label>
                <select name="product_id" id="product-select" class="form-control" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} @if($product->category) ({{ $product->category->name }})
                    @endif
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label>From (Warehouse/Site)</label>
                    <select name="from" id="from-select" class="form-control" required>
                        <option value="">-- Select Source --</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>To (Warehouse/Site)</label>
                    <select name="to" id="to-select" class="form-control" required>
                        <option value="">-- Select Destination --</option>
                    </select>
                </div>
            </div>

            <div class="form-group mt-2">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required value="{{ old('quantity') }}">
            </div>

            <div class="form-group">
                <label>Transfer Date</label>
                <input type="datetime-local" name="transfer_date" class="form-control"
                    value="{{ old('transfer_date', now()->format('Y-m-d\TH:i')) }}" required>
            </div>

        </div>
        <div class="card-footer">
            <button class="btn btn-success">Save Transfer</button>
        </div>
    </div>
</form>
@endsection

@if(session('success'))
    <script>
        window.toastMessage = {
            type: 'success',
            text: @json(session('success'))
        };
    </script>
@elseif(session('error'))
    <script>
        window.toastMessage = {
            type: 'error',
            text: @json(session('error'))
        };
    </script>
@endif

@php
    $allLocationsArray = collect($warehouses)->map(function($w) {
        return [
            'value' => "warehouse:{$w->id}",
            'label' => '[W] ' . $w->name,
        ];
    })->merge(
        collect($sites)->map(function($s) {
            return [
                'value' => "site:{$s->id}",
                'label' => '[S] ' . $s->name,
            ];
        })
    )->values();
@endphp

@push('scripts')
<script>
    const allLocations = @json($allLocationsArray);
    const oldFrom = "{{ old('from') }}";
    const oldTo = "{{ old('to') }}";
    const oldProductId = "{{ old('product_id') }}";

    const fromSelect = document.getElementById('from-select');
    const toSelect = document.getElementById('to-select');
    const productSelect = document.getElementById('product-select');

    function clearSelect(select, placeholder) {
        select.innerHTML = `<option value="">-- ${placeholder} --</option>`;
    }

    function populateToSelect(selectedFrom) {
        clearSelect(toSelect, 'Select Destination');
        allLocations
            .filter(loc => loc.value !== selectedFrom)
            .forEach(loc => {
                const option = new Option(loc.label, loc.value);
                toSelect.add(option);
            });
        if (oldTo) {
            toSelect.value = oldTo;
        }
    }

    productSelect.addEventListener('change', () => {
        const productId = productSelect.value;
        clearSelect(fromSelect, 'Select Source');
        clearSelect(toSelect, 'Select Destination');

        if (!productId) return;

        fetch(`{{ route('transfers.locations') }}?product_id=${productId}`)
            .then(res => res.json())
            .then(locations => {
                locations.forEach(loc => {
                    fromSelect.add(new Option(loc.label, loc.value));
                });

                if (oldFrom) {
                    fromSelect.value = oldFrom;
                    populateToSelect(oldFrom);
                }
            });
    });

    fromSelect.addEventListener('change', () => {
        const selectedFrom = fromSelect.value;
        populateToSelect(selectedFrom);
    });

    if (oldProductId) {
        productSelect.dispatchEvent(new Event('change'));
    }

    toastr.options = {
        "positionClass": "toast-top-right",
        "progressBar": true,
        "timeOut": "3000"
    };
    if (window.toastMessage) {
        toastr[window.toastMessage.type](window.toastMessage.text);
    }
</script>
@endpush
