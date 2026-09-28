@extends('layouts.app')
@section('title','Shop — GBUKPOP x KRJASTIP')
@section('content')
<section class="section"><div class="container">
<div class="section-head"><div><div class="eyebrow">SHOP</div><h2>PO &amp; Ready Stock</h2></div></div>
<form class="filters"><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari produk, versi, website, atau negara"><select class="select" name="type"><option value="">Semua jenis</option><option value="po" @selected(request('type')==='po')>PO</option><option value="ready" @selected(request('type')==='ready')>Ready Stock</option></select>@include('partials.filter-actions')</form>
<div class="grid grid-3">@forelse($products as $product)<a class="card product-card" href="{{ route('catalog.show',$product) }}"><div class="product-img">@if($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">@else<div class="noimg">{{ strtoupper($product->type) }}</div>@endif</div><div class="product-body"><div class="status-line"><span class="badge">{{ strtoupper($product->type) }}</span><span class="badge gray">{{ $product->country->name }}</span>@if($product->type==='po' && !$product->preorder?->isOpen())<span class="badge danger">Closed</span>@endif</div><div class="card-title" style="margin-top:10px">{{ $product->name }}</div>@if($product->variants->count())<div class="price">Mulai {{ $product->variants->whereNotNull('price_idr')->count()?'Rp'.number_format($product->variants->whereNotNull('price_idr')->min('price_idr'),0,',','.'):$product->country->moneySymbol().number_format($product->variants->min('price_foreign'),0,',','.') }}</div>@endif</div></a>@empty<div class="card empty">Tidak ada produk yang cocok.</div>@endforelse</div>
@include('partials.pagination',['paginator'=>$products])
</div></section>
@endsection
