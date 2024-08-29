<?php

namespace App\Http\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CreateUsuarios extends Component
{
    public $open = false;

    public $name,$email,$password,$password_confirmation;

    protected $rules = [
        'name'=>'required|min:10|max:250',
        'email'=>'required|unique:users|email',
        'password'=>'required|min:8|max:512|confirmed',
    ];



    public function save(){
        $this->validate();
        User::create([
            'name' =>$this->name,
            'email' =>$this->email,
            'password' => Hash::make($this->password)
        ]);
        $this->reset(['open','name','email','password']);
        $this->emitTo('show-usuarios','render');
        $this->emit('alert','El usuario fue agregado satifactoriamente');
    }



    public function render()
    {
        return view('livewire.create-usuarios');
    }
}
