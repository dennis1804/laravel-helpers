<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Service\BaseService;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Service\BaseService as ModelService;

use DB;

class BaseController extends Controller
{
    protected $modelService = false;
    protected $editValidationrules = [];
    protected $validationrules = [];
    protected $viewRoute = '';
    protected $baseRoute = '';
    public $useSameForm = true;
    protected $responseType = "html";

    protected $request = [];
    public function __construct(Model $model = null, Request $request)
    {

        // $this->middleware('auth');
        $this->model = $model;
        $this->setRequest($request);
        $this->setService(ModelService::class);


        if(!$this->viewRoute) {
            $this->setViewRoute(strtolower((new \ReflectionClass($model))->getShortName()));
        }

        if(!$this->baseRoute) {
            $this->setBaseRoute(strtolower((new \ReflectionClass($model))->getShortName()));
        }

        if($this->validationrules == []) {
            if($this->model->validationrules) {
                $this->setValidationRules($this->model->validationrules);
            }
        }


        if($this->request->headers->get('x-requested-with') == "XMLHttpRequest"){ 
            $this->setResponseType('json');
        }

    }


    public function index()
    {
        if (strpos($this->request->headers->get('referer'), 'window=tab') !== false) {
            return $this->closeTabResponse();
        }
        return $this->response('index',$this->modelService->serve('paginate'));

    }


    public function query() {
        return $this->modelService;
    }

    public function anyData() {
        return $this->query()->paginate(30);
    }

    public function search() {
        $zoekterm = $this->request->get('q', '');
        $data = $this->modelService->search($zoekterm);

        return $this->jsonresponse($data);
    }

    public function create()
    {
        $model = $this->modelService->fillModel($this->request->old());
        return $this->response('create', compact('model'));
    }

    public function store()
    {
        $this->validate($this->request, $this->validationrules);
        $this->modelService->store($this->request->all());

        return $this->redirectresponse('index', 'created!');
    }

    public function edit($id)
    {
        $model = $this->modelService->byId($id, $this->request->old());
        return $this->response('edit', compact('model'));
    }

    public function show($id)
    {
        $model = $this->modelService->byId($id, $this->request->all());
        return $this->response('show', compact('model'));
    }

    public function update($id)
    {

        $this->validate($this->request, $this->editValidationrules);
        $this->modelService->update($id, $this->request->all());

        return $this->redirectresponse('index', $this->modelService->getModel());
    }

    public function destroy($id)
    {
        $this->modelService->delete($id);

        return $this->redirectresponse()
            ->withSuccess('deleted!');
    }

    protected function setRequest(Request $request) {
        $this->request = $request;
    }

    protected function htmlResponse($bladeTempl, $data = []) {
        return view($this->viewRoute . '.' . $bladeTempl,  $data);
    }

    protected function jsonResponse($data) {
        return response()->json($data);
    }

    protected function redirectResponse($page = 'index', $data = []) {
        if($this->request->get('window') == 'tab') {
            return $this->closeTabResponse();
        }
         if($this->responseType == "html") {
        return redirect()->route($this->viewRoute . '.' . $page);
        }
        return $this->jsonResponse(['success' => true, 'data' => $data]);
    }


    protected function closeTabResponse() {
        return "<script>window.close();</script>";
    }


    public function setModel(Model $model) {
        $this->model = $model;
        $this->modelService = new BaseService($model);
    }

    public function setResponseType($type) {
        $this->responseType = $type;
    }

    public function response($page, array $data = []) {
        if($this->responseType == "html") {
            if($this->useSameForm && in_array($page, ['edit', 'create'])){
                return $this->htmlResponse('form', $data);
            }
            return $this->htmlResponse($page, $data);
        }else {
            return $this->jsonResponse($data);
        }
    }

    public function setService($service) {
        $this->modelService = new $service($this->model, $this->request);
    }

    public function setViewRoute($viewRoute) {
        $this->viewRoute = $viewRoute;
    }

    public function setBaseRoute($baseRoute) {
        $this->baseRoute = $baseRoute;
    }

    public function setValidationRules($rules) {
        $this->validationrules = $rules;
        $this->editValidationrules = $rules;
    }

    public function setEditValidationRules($rules) {
        $this->editValidationrules = array_merge($this->validationrules, $rules);
    }
}
