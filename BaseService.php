<?php

namespace App\Http\Service;

use \DB;
use App\Models\Model;
use Illuminate\Http\Request;


class BaseService
{

	private $model;


	public function __construct(Model $model = null)
	{
		$this->model = $model;
	}


	public function getModel() {
		return $this->model;
	}

	public function fillModel(array $request) {
		$this->model->fill($request);
		return $this->getModel();
	}

	public function store(array $request) {
		$this->fillModel($request);
		return $this->getModel()->save();
	}


	public function byId($modelId, array $request = []) {
		$this->model = $this->model->findOrFail($modelId);
		return $this->fillModel($request);
	}


	public function update($modelId, array $request = []) {

		$this->byId($modelId, $request);
		return $this->getModel()->save();
	}


	public function search(string $search) {
		return $this->getModel()->select('id', 'description as text')->where('description', 'LIKE', '%'.$search.'%')->paginate(30)->toArray();
	}


	public function delete($modelId) {
		return $this->getModel()->findOrFail($modelId)->delete();
	}


	

	public function __call($method, $parameters)
    {
        return $this->getModel()->$method(...$parameters);
    }
}