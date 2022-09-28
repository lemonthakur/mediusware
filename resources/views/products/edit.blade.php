@extends('layouts.app')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Product</h1>
    </div>
    <div id="app">
        <edit-product :variants="{{ $variants }}" :products_details="{{$product}}" :selected_variants="{{json_encode($array_to)}}" :price_array="{{json_encode($price_array)}}" :image_array="{{json_encode($image_array)}}">Loading</edit-product>
    </div>
@endsection
