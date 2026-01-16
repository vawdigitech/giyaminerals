@extends('layouts.app')
@section('page_title', 'New Stock Entry')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stocks.entries') }}">Stock Entries</a></li>
<li class="breadcrumb-item active">New Stock Entry</li>
@endsection
@section('content')
<form method="POST" action="{{ route('stocks.store') }}"> @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
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
            <div class="form-group">
                <label>Location (Warehouse or Site)</label>
                <select name="location" class="form-control" required>
                    <option value="">-- Select Location --</option>
                    <optgroup label="Warehouses">
                        @foreach($warehouses as $w)
                            <option value="warehouse:{{ $w->id }}"
                                {{ old('location') == "warehouse:$w->id" ? 'selected' : '' }}>
                                [W] {{ $w->name }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Sites">
                        @foreach($sites as $s)
                            <option value="site:{{ $s->id }}"
                                {{ old('location') == "site:$s->id" ? 'selected' : '' }}>
                                [S] {{ $s->name }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control" required
                    value="{{ old('quantity') }}">
            </div>
            <div class="form-group">
                <label>Entry Date</label>
                <input type="date" name="entry_date" class="form-control" required
                    value="{{ old('entry_date', now()->toDateString()) }}">
            </div>
            <div class="form-group">
                <label>Reference</label>
                <input type="text" name="reference" class="form-control" placeholder="Optional: invoice, GRN, etc."
                    value="{{ old('reference') }}">
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success">Save Entry</button>
        </div>
    </div>
</form>
@endsection
