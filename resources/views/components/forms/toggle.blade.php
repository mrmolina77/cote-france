
<div class="cursor-pointer my-5 rounded-full bg-gray-200 relative shadow-sm">
    <input {!! $attributes->merge(['class' => 'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-700 focus:bg-indigo-500 checkbox w-6 h-6 rounded-full bg-white absolute shadow-sm appearance-none cursor-pointer border border-transparent top-0 bottom-0 m-auto'
                                  ,'type'=>"checkbox"]) !!} />
    <label class="toggle-label dark:bg-gray-700 block w-12 h-8 overflow-hidden rounded-full bg-gray-300 cursor-pointer"></label>
</div>
