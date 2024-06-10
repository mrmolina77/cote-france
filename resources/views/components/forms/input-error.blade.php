@props(['for'])

@error($for)
    <div class="mb-4 text-sm text-red-800 dark:text-red-400" role="alert">
        <span class="font-medium">Error!</span> {{$message}}
    </div>
@enderror