@extends('layouts.app')
@section('page_title', 'Edit Stock Entry')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stocks.entries') }}">Stock Entries</a></li>
<li class="breadcrumb-item active">Edit Stock Entry</li>
@endsection
@section('content')
<form method="POST" action="{{ route('stocks.entries.update', $stockEntry) }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('product_id', $stockEntry->product_id) == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} @if($product->category) ({{ $product->category->name }})
                    @endif
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Location (Warehouse or Site)</label>
                <select name="location" class="form-control" required>
                    <option value="">-- Select Location --</option>
                    @php
                        $currentLocation = old('location', $stockEntry->location_type . ':' . $stockEntry->location_id);
                    @endphp
                    <optgroup label="Warehouses">
                        @foreach($warehouses as $w)
                            <option value="warehouse:{{ $w->id }}"
                                {{ $currentLocation == "warehouse:$w->id" ? 'selected' : '' }}>
                                [W] {{ $w->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Sites">
                        @foreach($sites as $s)
                            <option value="site:{{ $s->id }}"
                                {{ $currentLocation == "site:$s->id" ? 'selected' : '' }}>
                                [S] {{ $s->name }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required
                    step="0.001" min="0.001" value="{{ old('quantity', $stockEntry->quantity) }}">
            </div>
            <div class="form-group">
                <label>Entry Date</label>
                <input type="date" name="entry_date" class="form-control" required
                    value="{{ old('entry_date', \Carbon\Carbon::parse($stockEntry->entry_date)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Reference</label>
                <input type="text" name="reference" class="form-control" placeholder="Optional: invoice, GRN, etc."
                    value="{{ old('reference', $stockEntry->reference) }}">
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success">Update Entry</button>
            <a href="{{ route('stocks.entries') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endsection
