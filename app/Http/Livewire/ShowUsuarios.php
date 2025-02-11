<?php

namespace App\Http\Livewire;

use App\Models\Inscripcion;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ShowUsuarios extends Component
{
    use WithPagination;
    public $search = "";
    public $sort = 'id';
    public $direction = 'asc';
    public $user;
    public $cant = 5;
    public $readyToLoad = false;

    public $open_edit = false;
    protected $listeners = ['render','delete'];

    public $name,$email,$password,$password_confirmation,$rolesid,$relacionados,$relacionados_id;

    protected $rules = [
        'name'=>'required|min:10|max:250',
        'email'=>'required|unique:posts|email',
        'password'=>'required|min:10|max:512|confirmed',
        'rolesid'=>'required',
        'relacionados_id'=>'required',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, [
            'relacionados_id' => Rule::requiredIf(function () {
                return in_array($this->rolesid, [3, 4]);
            }),
        ]);
    }

    public function boot(){
        $this->relacionados = collect([]);
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function render()
    {
        if($this->readyToLoad){
            // $users = User::where('tareas_descripcion','like','%'.trim($this->search).'%')
            //                        ->orderBy($this->sort,$this->direction)
            //                        ->paginate($this->cant);
            $users = DB::table('users')
            ->join('roles','users.roles_id','=','roles.roles_id')
            ->orWhere('users.id','like','%'.trim($this->search).'%')
            ->orWhere('users.name','like','%'.trim($this->search).'%')
            ->orWhere('users.email','like','%'.trim($this->search).'%')
            ->orWhere('roles.roles_nombre','like','%'.trim($this->search).'%')
            ->select('users.id','users.name','users.email','roles.roles_nombre')
            ->paginate($this->cant);

        } else {
            $users = array();
        }

        $roles = Role::all();
        return view('livewire.show-usuarios',['users'=>$users,
                                              'roles'=>$roles]);
    }

    public function loadPosts(){
        $this->readyToLoad = true;
    }

    public function order($order){
        if ($this->sort== $order) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort= $order;
            $this->direction = 'asc';
        }
    }

    public function edit($id){
        $user = User::find($id);
        $this->user = $user;
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->rolesid = $this->user->roles_id;
        $this->relacionados = collect([]);
        if($this->rolesid == 3) {
            $profesores = Profesor::all();
            foreach ($profesores as $item) {
                $this->relacionados[] = collect(['id'=>$item->profesores_id,'nombres'=>$item->profesores_nombres .' '. $item->profesores_apellidos]);
            }
        } elseif ($this->rolesid == 4) {
            $inscripciones =  Inscripcion::all();
            foreach ($inscripciones as $item) {
                $this->relacionados[] = collect(['id'=>$item->prospecto->prospectos_id,'nombres'=>$item->prospecto->prospectos_nombres .' '. $item->prospecto->prospectos_apellidos]);
            }
        }
        $this->relacionados_id = $this->user->relacionados_id;
        $this->open_edit = true;
    }

    public function update(){
        // $this->user->password = Hash::make($this->user->password);
        // $this->user->save();
        // $this->validate();
        $this->user->forceFill([
            'name' => $this->name,
            'email' => $this->email,
            'roles_id' => $this->rolesid,
            'relacionados_id' => $this->relacionados_id,
            'password' => Hash::make($this->password),
        ])->save();
        unset($this->user);
        $this->reset(['open_edit']);
        $this->emit('alert','El usuario fue modificado satifactoriamente');

    }

    public function delete(User $user){
        $user->delete();
        $this->emit('alert','El usuario fue eliminado satifactoriamente');
    }

    public function updatedRolesid($roles_id){

        $this->relacionados = collect([]);
        if($roles_id == 3) {
            $profesores = Profesor::all();
            foreach ($profesores as $item) {
                $this->relacionados[] = collect(['id'=>$item->profesores_id,'nombres'=>$item->profesores_nombres .' '. $item->profesores_apellidos]);
            }
        } elseif ($roles_id == 4) {
            $inscripciones =  Inscripcion::all();
            foreach ($inscripciones as $item) {
                $this->relacionados[] = collect(['id'=>$item->prospecto->prospectos_id,'nombres'=>$item->prospecto->prospectos_nombres .' '. $item->prospecto->prospectos_apellidos]);
            }
        }
    }
}
