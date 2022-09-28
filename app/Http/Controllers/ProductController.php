<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;
use DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $product_title  = $request->title;
        $variant        = $request->variant;
        $price_from     = $request->price_from;
        $price_to       = $request->price_to;
        $date           = $request->date ? date("Y-m-d", strtotime($request->date)) : '';

        $data['products'] = Product::with(
            'product_variant_prices:product_id,stock,price,product_variant_one,product_variant_two,product_variant_three', 
            'product_variant_prices.product_variant_info_one:variant,id',
            'product_variant_prices.product_variant_info_two:variant,id',
            'product_variant_prices.product_variant_info_three:variant,id'
        );
        if($product_title)
            $data['products']->where('title', 'LIKE', "%{$product_title}%");
        if($variant){
            $data['products']->with(['product_variant_prices' => function ($query) use($variant) {
                $query->where('product_variant_one', $variant);
                $query->orWhere('product_variant_two', $variant);
                $query->orWhere('product_variant_three', $variant);
            }]);

            $data['products']->whereIn('id', function($query) use ($variant){
                $query->select('product_id')
                ->from(with(new ProductVariantPrice)->getTable())
                ->where('product_variant_one', $variant)
                ->orWhere('product_variant_two', $variant)
                ->orWhere('product_variant_three', $variant)
                ;
            });
        }
        if($price_from || $price_to){
            $data['products']->whereIn('id', function($query) use ($price_from, $price_to){
                $query->select('product_id')
                ->from(with(new ProductVariantPrice)->getTable());
                if($price_from){
                    $query->where('price', '>=', $price_from);
                }
                if($price_to){
                    $query->where('price', '<=', $price_to);
                }
            });
            if($price_from){
                $data['products']->with(['product_variant_prices' => function ($query) use($price_from) {
                    $query->where('price', '>=', $price_from);
                }]);
            }
            if($price_to){
                $data['products']->with(['product_variant_prices' => function ($query) use($price_to) {
                    $query->where('price', '>=', $price_to);
                }]);
            }   
        }
        if($date){
            $data['products']->whereDate('created_at', $date);
        }

        $data['products'] = $data['products']->paginate(5);                          
        $data['variants_list'] = Variant::with('product_variant:id,variant_id,variant')->get();

        return view('products.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ProductRequest $request)
    {
        $variant_option_array = array();
        DB::beginTransaction();
        try
        {
            $product = new Product();
            $product->title         = $request->title;
            $product->sku           = $request->sku;
            $product->description   = $request->description;
            if($product->save()){
                if(count($request->product_image) > 0){
                    foreach($request->product_image as $image){
                        $product_image = new ProductImage();
                        
                        $product_image->product_id  = $product->id;
                        $product_image->file_path   = $image;
                        $product_image->thumbnail   = 1;
                        $product_image->save();
                    }
                }

                $variant_option_array = array();
                if(count($request->product_variant) > 0){
                    foreach($request->product_variant as $pvv){
                        $option_id = $pvv['option'];
                        if(count($pvv['tags']) > 0){
                            foreach($pvv['tags'] as $tv){
                                $product_variant = new ProductVariant();
                                $product_variant->variant       = $tv;
                                $product_variant->variant_id    = $option_id;
                                $product_variant->product_id    = $product->id;
                                $product_variant->save();

                                $variant_option_array[$tv] = $product_variant->id;
                            }
                        }
                    }
                }

                if(count($request->product_variant_prices) > 0){
                    foreach($request->product_variant_prices as $price){
                        $explode_title = explode("/", rtrim($price['title'], '/'));
                        
                        if(count($explode_title) > 0){
                            $product_price = new ProductVariantPrice();
                            foreach($explode_title as $key => $vv){
                                if(isset($variant_option_array[$vv]) && $key == 0){
                                    $product_price->product_variant_one       = $variant_option_array[$vv];
                                }
                                if(isset($variant_option_array[$vv]) && $key == 1){
                                    $product_price->product_variant_two       = $variant_option_array[$vv];
                                }
                                if(isset($variant_option_array[$vv]) && $key == 2){
                                    $product_price->product_variant_three     = $variant_option_array[$vv];
                                }
                            }
        
                            $product_price->price                     = $price['price'];
                            $product_price->stock                     = $price['stock'];
                            $product_price->product_id                = $product->id;
                            $product_price->save();
                        }
                    }
                }

            }// End product save


            DB::commit();
        } catch (ValidationException $e) {
            DB::rollback();
            return response()->json($e->getErrors());
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }    

        session()->flash("success", 'Product created successfully.');

    }

     public function image_upload(Request $request)
    {

        $image_name = rand(1,100000);
        $ext = strtolower($request->file->getClientOriginalExtension());
        $image_full_name = $image_name . '.' . $ext;
        $upload_path = public_path('images/product');
        $image_url = $upload_path . $image_full_name;
        $request->file->move($upload_path, $image_full_name);
        //$imageName = time().'.'.$request->file->getClientOriginalExtension();
        //$request->file->move(public_path('images'), $imageName);
          
        return response()->json(['success'=>'We have successfully upload file.', 'image_url'=>'images/product/'.$image_full_name]);
        
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $product->toArray();
        $variants = Variant::all();

        $all_variant = $product->product_variant;
        $array_too = array();
        foreach($all_variant as $key => $alv){
            $array_too[$alv->variant_id]['option'] = $alv->variant_id;
            $array_too[$alv->variant_id]['tags'][] = $alv->variant;
        }
        $array_to = array_values($array_too);

        $price_array = array();
        if(count($product->product_variant_prices)>0){
            foreach($product->product_variant_prices as $index => $pvpv){
                 $variant_value = "";
                if(isset($pvpv->product_variant_info_one) && $pvpv->product_variant_info_one)
                    $variant_value .= $pvpv->product_variant_info_one->variant.'/ ';
                if(isset($pvpv->product_variant_info_two) && $pvpv->product_variant_info_two)
                    $variant_value .= $pvpv->product_variant_info_two->variant.'/ ';
                if(isset($pvpv->product_variant_info_three) && $pvpv->product_variant_info_three)
                    $variant_value .= $pvpv->product_variant_info_three->variant.'/ ';

                $price_array[$index]['title'] = rtrim($variant_value, '/ ');
                $price_array[$index]['price'] = $pvpv->price;
                $price_array[$index]['stock'] = $pvpv->stock;
            }  
        }

        $image_array = [];
        if(count($product->product_images)>0){

            foreach($product->product_images as $i => $image){
            
                $file_name = \File::name(public_path($image->file_path)).'.'.\File::extension(public_path($image->file_path));
                $size = \File::size(public_path($image->file_path));
                $sizeinMB = round($size / (1000 * 1024), 2);// MB 

                $rep_url = \URL::to('/').'/'.$image->file_path;

                if($sizeinMB <= 2){ // Check file size is <= 2 MB 
                     $image_array[] = array( 
                           "name" => $file_name, 
                           "size" => $size, 
                           "existing_path" => $image->file_path, 
                           //"path" => public_path($image->file_path) 
                           "path" => $rep_url 
                     ); 
                 } 
            }
        }
         
        //dd($price_array);
        return view('products.edit', compact('variants', 'product', 'array_to', 'price_array', 'image_array'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product)
    {
        $variant_option_array = array();
        DB::beginTransaction();
        try
        {
            $product->title         = $request->title;
            $product->sku           = $request->sku;
            $product->description   = $request->description;
            if($product->save()){
                if(count($request->product_image) > 0){
                    ProductImage::where('product_id', $product->id)->delete();
                    foreach($request->product_image as $image){
                        $product_image = new ProductImage();
                        
                        $product_image->product_id  = $product->id;
                        $product_image->file_path   = $image;
                        $product_image->thumbnail   = 1;
                        $product_image->save();
                    }
                }

                $variant_option_array = array();
                if(count($request->product_variant) > 0){
                    ProductVariant::where('product_id', $product->id)->delete();
                    foreach($request->product_variant as $pvv){
                        $option_id = $pvv['option'];
                        if(count($pvv['tags']) > 0){
                            foreach($pvv['tags'] as $tv){
                                $product_variant = new ProductVariant();
                                $product_variant->variant       = $tv;
                                $product_variant->variant_id    = $option_id;
                                $product_variant->product_id    = $product->id;
                                $product_variant->save();

                                $variant_option_array[$tv] = $product_variant->id;
                            }
                        }
                    }
                }

                if(count($request->product_variant_prices) > 0){
                    ProductVariantPrice::where('product_id', $product->id)->delete();
                    foreach($request->product_variant_prices as $price){
                        $explode_title = explode("/", rtrim($price['title'], '/'));
                        
                        if(count($explode_title) > 0){
                            $product_price = new ProductVariantPrice();
                            foreach($explode_title as $key => $vv){
                                if(isset($variant_option_array[$vv]) && $key == 0){
                                    $product_price->product_variant_one       = $variant_option_array[$vv];
                                }
                                if(isset($variant_option_array[$vv]) && $key == 1){
                                    $product_price->product_variant_two       = $variant_option_array[$vv];
                                }
                                if(isset($variant_option_array[$vv]) && $key == 2){
                                    $product_price->product_variant_three     = $variant_option_array[$vv];
                                }
                            }
        
                            $product_price->price                     = $price['price'];
                            $product_price->stock                     = $price['stock'];
                            $product_price->product_id                = $product->id;
                            $product_price->save();
                        }
                    }
                }

            }// End product save


            DB::commit();
        } catch (ValidationException $e) {
            DB::rollback();
            return response()->json($e->getErrors());
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }    

        session()->flash("success", 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
