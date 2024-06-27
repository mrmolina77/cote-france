<!-- component -->

<section class="py-1 bg-blueGray-50">
<div class="w-full xl:w-10/12 mb-12 xl:mb-0 px-4 mx-auto mt-24">
<div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6 shadow-lg rounded ">
    <div class="rounded-t mb-0 px-4 py-3 border-0">
    {{$header}}
    </div>
    <div class="block w-full overflow-x-auto">
        {{$slot}}
    </div>
</div>
</div>
<footer class="relative pt-8 pb-6 mt-16">
<div class="container mx-auto px-4">
    <div class="flex flex-wrap items-center md:justify-between justify-center">
    <div class="w-full md:w-6/12 px-4 mx-auto text-center">
        <div class="text-sm text-blueGray-500 font-semibold py-1">
            Movida TCI - Programa de Gestión PDG - Web site Propiedad de <a href="https://www.cotefrance.mx/" class="text-blueGray-500 hover:text-gray-800" target="_blank">https://www.cotefrance.mx/</a> Todos los Derechos Reservados. Copyright © 2024
        </div>
    </div>
    </div>
</div>
</footer>
</section>
