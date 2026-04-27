@props([
    'formAction' => null, 
    'method' => 'GET',
    'idPrefix' => 'filter',
    'btnText' => 'Filtrar',
    'selectedProvincia' => request('provincia'),
    'selectedMunicipio' => request('municipio'),
    'showEstado' => false
])

<div class="location-filter-container w-full">
    @if($formAction)
    <form action="{{ $formAction }}" method="{{ $method }}" class="flex flex-col sm:flex-row items-end gap-4">
    @endif
        
        <div class="flex-1 w-full">
            <label for="{{ $idPrefix }}_provincia" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
            <select id="{{ $idPrefix }}_provincia" name="provincia" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" {{ !$formAction ? 'required' : '' }}>
                <option value="">-- Seleccionar Provincia --</option>
            </select>
        </div>

        <div class="flex-1 w-full">
            <label for="{{ $idPrefix }}_municipio" class="block text-sm font-medium text-gray-700 mb-1">Municipio</label>
            <select id="{{ $idPrefix }}_municipio" name="municipio" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" {{ !$formAction ? 'required' : '' }}>
                <option value="">-- Seleccione primero una provincia --</option>
            </select>
        </div>

    @if($showEstado)
        <div class="flex-1 w-full">
            <label for="{{ $idPrefix }}_estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select id="{{ $idPrefix }}_estado" name="estado" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">Todos</option>
                <option value="abierto">Abierto Ahora</option>
                <option value="cerrado">Cerrado</option>
            </select>
        </div>
    @endif
        <div class="w-full sm:w-auto mt-2 sm:mt-0 flex items-center justify-center">
            <button type="button" id="{{ $idPrefix }}_reset" class="text-sm text-blue-500 hover:text-blue-700 underline whitespace-nowrap" style="display: none;">Restablecer</button>
        </div>
    @if($formAction)
    </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para evitar conflictos si el componente se usa múltiples veces, encapsulamos en una función
    (function() {
        const provSelect = document.getElementById('{{ $idPrefix }}_provincia');
        const munSelect = document.getElementById('{{ $idPrefix }}_municipio');
        if (!provSelect || !munSelect) return;

        const selectedProv = @json($selectedProvincia);
        const selectedMun = @json($selectedMunicipio);

        fetch('/locations.json')
            .then(res => res.json())
            .then(data => {
                // Limpiar opciones anteriores
                provSelect.innerHTML = '<option value="">-- Seleccionar Provincia --</option>';
                const provinces = Object.keys(data.provinces).sort();
                
                provinces.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    if (p === selectedProv) opt.selected = true;
                    provSelect.appendChild(opt);
                });

                function dispatchFilterChange() {
                    const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                    const resetBtn = document.getElementById('{{ $idPrefix }}_reset');
                    
                    window.dispatchEvent(new CustomEvent('locationFilterChanged', {
                        detail: {
                            idPrefix: '{{ $idPrefix }}',
                            provincia: provSelect.value,
                            municipio: munSelect.value,
                            estado: estadoSelect ? estadoSelect.value : ''
                        }
                    }));
                    
                    if (resetBtn) {
                        const hasFilter = provSelect.value || munSelect.value || (estadoSelect && estadoSelect.value);
                        resetBtn.style.display = hasFilter ? 'inline-block' : 'none';
                    }
                }

                const resetBtn = document.getElementById('{{ $idPrefix }}_reset');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        provSelect.value = '';
                        provSelect.dispatchEvent(new Event('change'));
                        const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                        if (estadoSelect) {
                            estadoSelect.value = '';
                            estadoSelect.dispatchEvent(new Event('change'));
                        }
                    });
                }

                provSelect.addEventListener('change', function() {
                    munSelect.innerHTML = '<option value="">-- Seleccionar Municipio --</option>';
                    const p = this.value;
                    if (p && data.provinces[p]) {
                        const muns = data.provinces[p].sort();
                        muns.forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = m;
                            opt.textContent = m;
                            if (m === selectedMun && p === selectedProv) opt.selected = true;
                            munSelect.appendChild(opt);
                        });
                    } else if (!p) {
                        munSelect.innerHTML = '<option value="">-- Seleccione primero una provincia --</option>';
                    }
                    dispatchFilterChange();
                });

                munSelect.addEventListener('change', dispatchFilterChange);
                const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                if (estadoSelect) {
                    estadoSelect.addEventListener('change', dispatchFilterChange);
                }

                if (selectedProv) {
                    provSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(err => console.error("Error loading locations:", err));
    })();
});
</script>
