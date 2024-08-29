<div>

    <button wire:click="$set('open',true)" class="bg-indigo-500 text-white active:bg-indigo-600 text-xs font-bold uppercase px-3 py-1 rounded outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button">
        {{__('Add user')}}
    </button>

    <x-dialog-modal wire:model="open">
        <x-slot name="title">
            Crear usuario
        </x-slot>
        <x-slot name="content">
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Name')}}: " />
                    <x-forms.input type="text" class="flex-1 ml-4" wire:model="name"/>
                </div>
                <x-forms.input-error for="name"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{__('Email')}}: " />
                    <x-forms.input type="email" class="flex-1 ml-4" wire:model="email"/>
                </div>
                <x-forms.input-error for="email"/>
            </div>
            <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{ __('Password') }}: " />
                    <x-forms.input type="password" class="flex-1 ml-4" wire:model="password"/>
                </div>
                <x-forms.input-error for="password"/>
           </div>
           <div>
                <div class="mb-4 flex">
                    <x-forms.label value="{{ __('Confirm Password') }}: " />
                    <x-forms.input type="password" class="flex-1 ml-4" wire:model="password_confirmation"/>
                </div>
                <x-forms.input-error for="password"/>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-forms.red-button wire:click="$set('open',false)">
                {{__('Cancel')}}
            </x-forms.red-button>
            <x-forms.blue-button wire:click="save"  wire:loading.attr="disabled" wire:target="save" class="disabled:opacity-65">
                {{__('Create')}}
            </x-forms.blue-button>
            {{-- <span wire:loading wire:target="save">Cargando...</span> --}}
        </x-slot>
    </x-dialog-modal>
</div>
