<?php

namespace App\Http\Controllers\Admin\Lgs\Items;

use Illuminate\Http\Request;

use App\Models\Lgs\Items\Item;
use App\Http\Controllers\Admin\Lgs\BaseController;

use DB;

class ItemController extends BaseController
{

    public function __construct(Item $model, Request $request)
    {
		$this->setModel($model);
		$this->setViewRoute('admin.lgs.items.item');
		$this->setBaseRoute('admin.lgs.items.item');
		$this->setValidationRules($model->validation);
		$this->setEditValidationRules($model->editValidation);
		$this->setRequest($request);

    }



    


    public function update( $id) {

    	$this->validate($this->request, $this->editValidationrules);
		$model = $this->model->findOrFail($id);
		$model->fill($this->request->all());
		$model->save($this->request->all());


	    return $this->redirectresponse('index', 'updated');

    }
}