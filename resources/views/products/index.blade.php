@extends('layouts.app')

@section('content')

    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>{{ session()->get('success') }}</strong>
    </div>
    @endif

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" value="{{ request()->title }}" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <option value="">Select Variant</option>
                        @foreach($variants_list as $k => $v)
                        <optgroup label="{{ $v->title }}">
                            @if(count($v->product_variant) > 0)
                                @foreach($v->product_variant->unique('variant') as $pvv)
                                    <option value="{{ $pvv->id}}" @if(request()->variant==$pvv->id) selected @endif>{{ $pvv->variant}}</option>
                                @endforeach
                            @endif
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" value="{{ request()->price_from }}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{ request()->price_to }}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{ request()->date ? request()->date : '' }}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th style="width: 10%">Title</th>
                        <th>Description</th>
                        <th style="width: 40%">Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php $sl = 1; ?>
                    @forelse ($products as $key => $product)
                        <tr>
                            <td>{{ $products->firstItem() + $key }}</td>
                            <td>{{ $product->title }} <br> Created at: <br>{{ date("d-M-Y") }}</td>
                            <td>{{ $product->description }}</td>
                            <td>
                                <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant{{$product->id}}">
                                    @if(count($product->product_variant_prices)>0)
                                        
                                        @foreach($product->product_variant_prices as $pvpv)
                                            <dt class="col-sm-3 pb-0">
                                            <?php $variant_value = ""; ?>    
                                            @if(isset($pvpv->product_variant_info_one) && $pvpv->product_variant_info_one)
                                                <?php $variant_value .= $pvpv->product_variant_info_one->variant.'/ '; ?>
                                            @endif
                                            @if(isset($pvpv->product_variant_info_two) && $pvpv->product_variant_info_two)   
                                                <?php $variant_value .= $pvpv->product_variant_info_two->variant.'/ '; ?> 
                                            @endif
                                            @if(isset($pvpv->product_variant_info_three) && $pvpv->product_variant_info_three)    
                                                <?php $variant_value .= $pvpv->product_variant_info_three->variant; ?>
                                            @endif
                                            {{ rtrim($variant_value, '/ ') }}
                                            </dt>
                                            <dd class="col-sm-9">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-4 pb-0">Price : {{ number_format($pvpv->price,2) }}</dt>
                                                    <dd class="col-sm-8 pb-0">InStock : {{ number_format($pvpv->stock,2) }}</dd>
                                                </dl>
                                            </dd>
                                        @endforeach
                                    @endif 
                                </dl>
                                <button onclick="$('#variant{{$product->id}}').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center"><b>No product found</b></td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                <p>Showing  {{$products->firstItem()}} @if($products->firstItem() != $products->lastItem() )   to {{$products->lastItem()}} @endif   out of    {{$products->total()}}</p>
                </div>
                <div class="col-md-2">
                    {!! $products->appends(Request::except('page'))->render() !!}
                </div>
            </div>
        </div>
    </div>

@endsection
