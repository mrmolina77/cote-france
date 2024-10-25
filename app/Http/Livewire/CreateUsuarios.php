<?php

namespace App\Http\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CreateUsuarios extends Component
{
    public $open = false;

    public $name,$email,$password,$password_confirmation,$roles_id;

    protected $rules = [
        'name'=>'required|min:10|max:250',
        'email'=>'required|unique:users|email',
        'password'=>'required|min:8|max:512|confirmed',
        'roles_id'=>'required',
    ];



    public function save(){
        $this->validate();
        User::create([
            'name' =>$this->name,
            'email' =>$this->email,
            'password' => Hash::make($this->password),
            'roles_id' =>$this->roles_id
        ]);
        $this->reset(['open','name','email','password','roles_id']);
        $this->emitTo('show-usuarios','render');
        $this->emit('alert','El usuario fue agregado satifactoriamente');
    }



    public function render()
    {
        $roles = Role::all();
        return view('livewire.create-usuarios',['roles'=>$roles]);
    }
}
