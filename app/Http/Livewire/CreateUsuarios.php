<?php

namespace App\Http\Livewire;

use App\Models\Inscripcion;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Illuminate\Validation\Rule;

class CreateUsuarios extends Component
{
    public $open = false;

    public $name,$email,$password,$password_confirmation,$roles_id,$relacionados,$rolesid,$relacionados_id;

    protected $rules = [
        'name'=>'required|min:10|max:250',
        'email'=>'required|unique:users|email',
        'password'=>'required|min:8|max:512|confirmed',
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



    public function save(){
        $this->validate();
        User::create([
            'name' =>$this->name,
            'email' =>$this->email,
            'password' => Hash::make($this->password),
            'roles_id' =>$this->rolesid,
            'relacionados_id' =>$this->relacionados_id
        ]);
        $this->reset(['open','name','email','password','roles_id','relacionados_id']);
        $this->emitTo('show-usuarios','render');
        $this->emit('alert','El usuario fue agregado satifactoriamente');
    }



    public function render()
    {
        $roles = Role::all();
        return view('livewire.create-usuarios',['roles'=>$roles]);
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
