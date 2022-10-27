<?php

namespace App\Models\Lgs\Items;

use \App\Models\Vat\Code;
use DB;
use App\Models\Model;

class Item extends Model
{
    protected $table = 'lgs_items_base';


    public $redirect = true; 
    public $timestamps = false;


    public $fillable = [
        'type',
        'code',
        'description',
        'remark',
        'group_id',
        'unit_code_id',
        'is_stock',
        'vat_reference_id',
        'snelstart_id'
    ];

    public $validation  = [
        'type' => 'required|in:arbeid,artikel,materiaal',
        'code' => 'max:16',
        'description' => 'required|max:50',
        'remark' => 'max:100',
        'group_id' => 'required|exists:lgs_items_group,id',
        'unit_code_id' => 'required|exists:lgs_items_unit,id',
        'is_stock' => 'required|boolean',
        'vat_reference_id' => 'required|exists:vat_reference_code,id',

        ];

    public $editValidation = [
        'price.*.date_start' => 'date_format:d-m-Y',
        'price.*.date_end' => 'date_format:d-m-Y|after:price.*.date_start',
        'price.*.price' => 'required_with:price.*.date_start',
        'price.*.price_pro_piece' => 'required_with:price.*.date_start',
        'price.*.piece_pro_price' => 'required_with:price.*.date_start',

        'supplier.*.creditor_id' => 'required_with:supplier.*.barcode,supplier.*.creditor_item_code,supplier.*.is_default_supplier|exists:crm_relation,id',
        'supplier.*.barcode' => 'required_with:supplier.*.creditor_id,supplier.*.creditor_item_code,supplier.*.is_default_supplier',
        'supplier.*.creditor_item_code' => 'required_with:supplier.*.creditor_id,supplier.*.barcode,supplier.*.is_default_supplier',
        'supplier.*.is_default_supplier' => 'required_with:supplier.*.creditor_id,supplier.*.barcode,supplier.*.creditor_item_code'


    ];

    public $itemType = ['arbeid', 'artikel', 'materiaal'];

    public function group() {

        return $this->belongsTo(Group::class);
    }


    public function stock() {

        return $this->belongsTo(Stock::class);
    }


    public function price() {

        return $this->hasMany(Price::class);
    }

    public function supplier() {

        return $this->hasMany(Supplier::class);
    }

    public function unit() {

        return $this->belongsTo(Unit::class, 'unit_code_id');
    }

    public function vat_code() {

        return $this->belongsTo(Code::class, 'vat_reference_id');
    }

    /*
    *   @function savePrice
    *   -Saves the existing prices, creates new ones and removes the ones that weren't in the form anymore
    * 
    */
    protected function savePrice(array $prices) {
        $exist = [];
        foreach($prices as $key => $priceAttributes) {
            if(is_int($key) && array_get($priceAttributes, 'id', false)) {
                $price = Price::findOrFail(array_get($priceAttributes, 'id', false));
                $price->fill($priceAttributes);
                $price->save();
                array_push($exist, $price->id);
            } else {
                if(array_filter($priceAttributes)) {
                    $price = new Price();
                    $price->fill($priceAttributes);
                    $price->item_id = $this->getKey();
                    $price->save();
                    array_push($exist, $price->id);
                }
            }
        }
        Price::whereItemId($this->id)->whereNotIn('id', $exist)->delete();
    }

    /*
    *   @function fillPrice
    *   -Fills the price relation with request->old() to display form correctly when validation fails
    * 
     */
    protected function fillPrice($prices) {
        foreach($prices as $key => $priceAttributes) {
            if(!is_int($key)) {
                if(array_filter($priceAttributes)) {
                    $price = new Price();
                    $price->fill($priceAttributes);
                    $this->price[$key] = $price;
                }
            }
        }
    }
    /*
    *   @function fillSupplier
    *   -Saves the existing suppliers, creates new ones and removes the ones that weren't in the form anymore
    * 
    */
    protected function saveSupplier(array $suppliers) {
        $exist = [];
        foreach($suppliers as $key => $item) {
            if(is_int($key) && array_get($item, 'id', false)) {
                $supplier = Supplier::findOrFail(array_get($item, 'id', false));
                $supplier->fill($item);
                $supplier->save();
                array_push($exist, $supplier->id);
            } else {
                if(array_filter($item)) {
                    $supplier = new Supplier();
                    $supplier->fill($item);
                    $supplier->item_id = $this->getKey();
                    $supplier->save();
                    array_push($exist, $supplier->id);
                }
            }
        }
        Supplier::whereItemId($this->id)->whereNotIn('id', $exist)->delete();
    }

    /*
    *   @function fillSupplier
    *   -Fills the supplier relation with request->old() to display form correctly when validation fails
    * 
    */
    protected function fillSupplier(array $suppliers) {
        foreach($suppliers as $key => $item) {
            if(!is_int($key)) {
                if(array_filter($item)) {
                    $supplier = new Supplier();
                    $supplier->fill($item);
                    $this->supplier[$key] = $supplier;
                }
            }
        }
    }

    public function save(array $options = []) {
        try {
            $this->savePrice(array_get($options, 'price', []));
            $this->saveSupplier(array_get($options, 'supplier', []));

            parent::save();
        } catch(\Exception $e) {
           throw $e;
        }

    }

    public function fill(array $attributes) {
        parent::fill($attributes);
        $this->fillPrice(array_get($attributes, 'price', []));
        $this->fillSupplier(array_get($attributes, 'supplier', []));
        
    }


}
