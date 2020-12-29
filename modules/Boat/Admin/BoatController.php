<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 7/30/2019
 * Time: 1:56 PM
 */
namespace Modules\Boat\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Boat\Models\Boat;
use Modules\Boat\Models\BoatTerm;
use Modules\Boat\Models\BoatTranslation;
use Modules\Core\Models\Attributes;
use Modules\Location\Models\Location;

class BoatController extends AdminController
{
    protected $boat;
    protected $boat_translation;
    protected $boat_term;
    protected $attributes;
    protected $location;
    public function __construct()
    {
        parent::__construct();
        $this->setActiveMenu('admin/module/boat');
        $this->boat = Boat::class;
        $this->boat_translation = BoatTranslation::class;
        $this->boat_term = BoatTerm::class;
        $this->attributes = Attributes::class;
        $this->location = Location::class;
    }

    public function callAction($method, $parameters)
    {
        if(!Boat::isEnable())
        {
            return redirect('/');
        }
        return parent::callAction($method, $parameters); // TODO: Change the autogenerated stub
    }
    public function index(Request $request)
    {
        $this->checkPermission('boat_view');
        $query = $this->boat::query() ;
        $query->orderBy('id', 'desc');
        if (!empty($s = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $s . '%');
            $query->orderBy('title', 'asc');
        }

        if ($this->hasPermission('boat_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('create_user', $author);
            }
        } else {
            $query->where('create_user', Auth::id());
        }
        $data = [
            'rows'               => $query->with(['author'])->paginate(20),
            'boat_manage_others' => $this->hasPermission('boat_manage_others'),
            'breadcrumbs'        => [
                [
                    'name' => __('Boats'),
                    'url'  => 'admin/module/tour'
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Boat Management")
        ];
        return view('Boat::admin.index', $data);
    }

    public function recovery(Request $request)
    {
        $this->checkPermission('boat_view');
        $query = $this->boat::onlyTrashed() ;
        $query->orderBy('id', 'desc');
        if (!empty($s = $request->input('s'))) {
            $query->where('title', 'LIKE', '%' . $s . '%');
            $query->orderBy('title', 'asc');
        }

        if ($this->hasPermission('boat_manage_others')) {
            if (!empty($author = $request->input('vendor_id'))) {
                $query->where('create_user', $author);
            }
        } else {
            $query->where('create_user', Auth::id());
        }
        $data = [
            'rows'               => $query->with(['author'])->paginate(20),
            'boat_manage_others' => $this->hasPermission('boat_manage_others'),
            'recovery'           => 1,
            'breadcrumbs'        => [
                [
                    'name' => __('Boats'),
                    'url'  => 'admin/module/tour'
                ],
                [
                    'name'  => __('Recovery'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Recovery Boat Management")
        ];
        return view('Boat::admin.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('boat_create');
        $row = new $this->boat();
        $row->fill([
            'status' => 'publish'
        ]);
        $data = [
            'row'            => $row,
            'attributes'     => $this->attributes::where('service', 'boat')->get(),
            'boat_location' => $this->location::where('status', 'publish')->get()->toTree(),
            'translation'    => new $this->boat_translation(),
            'breadcrumbs'    => [
                [
                    'name' => __('Boats'),
                    'url'  => 'admin/module/boat'
                ],
                [
                    'name'  => __('Add Boat'),
                    'class' => 'active'
                ],
            ],
            'page_title'     => __("Add new Boat")
        ];
        return view('Boat::admin.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('boat_update');
        $row = $this->boat::find($id);
        if (empty($row)) {
            return redirect(route('boat.admin.index'));
        }
        $translation = $row->translateOrOrigin($request->query('lang'));
        if (!$this->hasPermission('boat_manage_others')) {
            if ($row->create_user != Auth::id()) {
                return redirect(route('boat.admin.index'));
            }
        }
        $data = [
            'row'            => $row,
            'translation'    => $translation,
            "selected_terms" => $row->terms->pluck('term_id'),
            'attributes'     => $this->attributes::where('service', 'boat')->get(),
            'boat_location'  => $this->location::where('status', 'publish')->get()->toTree(),
            'enable_multi_lang'=>true,
            'breadcrumbs'    => [
                [
                    'name' => __('Boats'),
                    'url'  => 'admin/module/boat'
                ],
                [
                    'name'  => __('Edit Boat'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Edit: :name",['name'=>$row->title])
        ];
        return view('Boat::admin.detail', $data);
    }

    public function store( Request $request, $id ){

        if($id>0){
            $this->checkPermission('boat_update');
            $row = $this->boat::find($id);
            if (empty($row)) {
                return redirect(route('boat.admin.index'));
            }

            if($row->create_user != Auth::id() and !$this->hasPermission('boat_manage_others'))
            {
                return redirect(route('boat.admin.index'));
            }
        }else{
            $this->checkPermission('boat_create');
            $row = new $this->boat();
            $row->status = "publish";
        }
        $dataKeys = [
            'title',
            'content',
            'price',
            'is_instant',
            'status',
            'video',
            'faqs',
            'image_id',
            'banner_image_id',
            'gallery',
            'location_id',
            'address',
            'map_lat',
            'map_lng',
            'map_zoom',
            'number',
            'price',
            'sale_price',
            'passenger',
            'gear',
            'baggage',
            'door',
            'enable_extra_price',
            'extra_price',
            'is_featured',
            'default_state',
        ];
        if($this->hasPermission('boat_manage_others')){
            $dataKeys[] = 'create_user';
        }

        $row->fillByAttr($dataKeys,$request->input());
        if($request->input('slug')){
            $row->slug = $request->input('slug');
        }

        $res = $row->saveOriginOrTranslation($request->input('lang'),true);

        if ($res) {
            if(!$request->input('lang') or is_default_lang($request->input('lang'))) {
                $this->saveTerms($row, $request);
            }

            if($id > 0 ){
                return back()->with('success',  __('Boat updated') );
            }else{
                return redirect(route('boat.admin.edit',$row->id))->with('success', __('Boat created') );
            }
        }
    }

    public function saveTerms($row, $request)
    {
        $this->checkPermission('boat_manage_attributes');
        if (empty($request->input('terms'))) {
            $this->boat_term::where('target_id', $row->id)->delete();
        } else {
            $term_ids = $request->input('terms');
            foreach ($term_ids as $term_id) {
                $this->boat_term::firstOrCreate([
                    'term_id' => $term_id,
                    'target_id' => $row->id
                ]);
            }
            $this->boat_term::where('target_id', $row->id)->whereNotIn('term_id', $term_ids)->delete();
        }
    }

    public function bulkEdit(Request $request)
    {

        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        switch ($action){
            case "delete":
                foreach ($ids as $id) {
                    $query = $this->boat::where("id", $id);
                    if (!$this->hasPermission('boat_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('boat_delete');
                    }
                    $query->first();
                    if(!empty($query)){
                        $query->delete();
                    }
                }
                return redirect()->back()->with('success', __('Deleted success!'));
                break;
            case "recovery":
                foreach ($ids as $id) {
                    $query = $this->boat::where("id", $id);
                    if (!$this->hasPermission('boat_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('boat_delete');
                    }
                    $query->first();
                    if(!empty($query)){
                        $query->restore();
                    }
                }
                return redirect()->back()->with('success', __('Recovery success!'));
                break;
            case "clone":
                $this->checkPermission('boat_create');
                foreach ($ids as $id) {
                    (new $this->boat())->saveCloneByID($id);
                }
                return redirect()->back()->with('success', __('Clone success!'));
                break;
            default:
                // Change status
                foreach ($ids as $id) {
                    $query = $this->boat::where("id", $id);
                    if (!$this->hasPermission('boat_manage_others')) {
                        $query->where("create_user", Auth::id());
                        $this->checkPermission('boat_update');
                    }
                    $query->update(['status' => $action]);
                }
                return redirect()->back()->with('success', __('Update success!'));
                break;
        }


    }
}
