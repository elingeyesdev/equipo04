@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 font-heading">Promover a Autoridad</h1>
        <p class="mt-2 text-sm text-gray-600">Busca a un ciudadano registrado y otórgale permisos de administrador en el sistema.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <form method="POST" action="{{ route('authorities.store') }}" class="p-6">
            @csrf

            <div class="mb-6 relative" id="autocomplete-container">
                <label for="search_input" class="block text-sm font-medium text-gray-700 mb-1">
                    Buscar Ciudadano (Nombre o Carnet)
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search_input" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2 px-3 border"
                        placeholder="Escribe para buscar..."
                        autocomplete="off"
                    >
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Lista de sugerencias -->
                <ul id="suggestions_list" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm hidden">
                    <!-- Los items se inyectarán aquí -->
                </ul>

                <!-- Campo oculto que guarda el carnet para enviar en el form -->
                <input type="hidden" name="carnet" id="carnet_input" value="{{ old('carnet') }}">
                
                @error('carnet')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button type="submit" id="submit_btn" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Promover a Autoridad
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search_input');
    const carnetInput = document.getElementById('carnet_input');
    const suggestionsList = document.getElementById('suggestions_list');
    const submitBtn = document.getElementById('submit_btn');
    const container = document.getElementById('autocomplete-container');
    
    let debounceTimer;

    // Cuando el usuario escribe
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Si borra el input, limpiamos el carnet oculto y deshabilitamos el botón
        carnetInput.value = '';
        submitBtn.disabled = true;

        if (query.length < 2) {
            suggestionsList.classList.add('hidden');
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetch(`/authorities/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsList.innerHTML = '';
                    
                    if (data.length === 0) {
                        const li = document.createElement('li');
                        li.className = 'text-gray-500 cursor-default select-none relative py-2 pl-3 pr-9';
                        li.textContent = 'No se encontraron ciudadanos.';
                        suggestionsList.appendChild(li);
                    } else {
                        data.forEach(citizen => {
                            const li = document.createElement('li');
                            li.className = 'text-gray-900 cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary-50 hover:text-primary-900 transition-colors';
                            
                            const div = document.createElement('div');
                            div.className = 'flex items-center justify-between';
                            
                            const nameSpan = document.createElement('span');
                            nameSpan.className = 'font-medium truncate';
                            nameSpan.textContent = citizen.name;
                            
                            const carnetSpan = document.createElement('span');
                            carnetSpan.className = 'text-gray-500 text-xs ml-2';
                            carnetSpan.textContent = `CI: ${citizen.carnet}`;
                            
                            div.appendChild(nameSpan);
                            div.appendChild(carnetSpan);
                            li.appendChild(div);
                            
                            li.addEventListener('click', () => {
                                searchInput.value = `${citizen.name} (CI: ${citizen.carnet})`;
                                carnetInput.value = citizen.carnet;
                                submitBtn.disabled = false;
                                suggestionsList.classList.add('hidden');
                            });
                            
                            suggestionsList.appendChild(li);
                        });
                    }
                    suggestionsList.classList.remove('hidden');
                })
                .catch(err => console.error('Error fetching citizens:', err));
        }, 300);
    });

    // Ocultar lista al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            suggestionsList.classList.add('hidden');
        }
    });
    
    // Mostrar lista al hacer focus si hay texto
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2 && suggestionsList.children.length > 0) {
            suggestionsList.classList.remove('hidden');
        }
    });
});
</script>
@endsection
