@props([
    'formAction' => null, 
    'method' => 'GET',
    'idPrefix' => 'filter',
    'btnText' => 'Filtrar',
    'selectedRegion' => request('region'),
    'selectedProvincia' => request('provincia'),
    'selectedMunicipio' => request('municipio'),
    'showEstado' => false,
    'showSearch' => false,
    'resetBelow' => false,
    'requireFields' => null,
])

@php
    $isRequired = $requireFields ?? !$formAction;
@endphp

<div class="location-filter-container w-full">
    @if($formAction)
    <form action="{{ $formAction }}" method="{{ $method }}" class="flex flex-col gap-4">
    @else
    <div class="flex flex-col gap-4">
    @endif
        
        <div class="flex flex-col sm:flex-row items-start gap-4 w-full">
            <div class="flex-1 w-full">
                <label for="{{ $idPrefix }}_region" class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                <select id="{{ $idPrefix }}_region" name="region" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">-- Seleccionar Región --</option>
                </select>
            </div>

            <div class="flex-1 w-full">
                <label for="{{ $idPrefix }}_provincia" class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                <select id="{{ $idPrefix }}_provincia" name="provincia" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" {{ $isRequired ? 'required' : '' }}>
                    <option value="">-- Seleccionar Provincia --</option>
                </select>
            </div>

            <div class="flex-1 w-full">
                <label for="{{ $idPrefix }}_municipio" class="block text-sm font-medium text-gray-700 mb-1">Municipio</label>
                <select id="{{ $idPrefix }}_municipio" name="municipio" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" {{ $isRequired ? 'required' : '' }}>
                    <option value="">-- Seleccione primero región o provincia --</option>
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
            
            @if(!$resetBelow)
            {{-- Botón Restablecer inline (comportamiento por defecto para el filtro) --}}
            <div class="w-full sm:w-auto mt-2 sm:mt-0 flex items-center justify-center h-[38px]">
                <button type="button" id="{{ $idPrefix }}_reset" class="text-sm text-blue-500 hover:text-blue-700 underline whitespace-nowrap" style="display: none;">Restablecer</button>
            </div>
            @endif
        </div>

        @if($resetBelow)
        {{-- Botón Restablecer debajo de los selects (para formularios donde el espacio vertical es preferible) --}}
        <div class="mt-1 flex justify-start">
            <button type="button" id="{{ $idPrefix }}_reset" class="text-sm text-blue-500 hover:text-blue-700 underline whitespace-nowrap" style="display: none;">Restablecer</button>
        </div>
        @endif

        @if($showSearch)
        <div class="w-full sm:w-2/3 lg:w-1/2">
            <label for="{{ $idPrefix }}_nombre" class="block text-sm font-medium text-gray-700 mb-1">Buscar por Nombre</label>
            <input type="text" id="{{ $idPrefix }}_nombre" name="nombre" placeholder="Ej. Centro Vida..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        @endif

    @if($formAction)
    </form>
    @else
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para evitar conflictos si el componente se usa múltiples veces, encapsulamos en una función
    (function() {
        const regSelect = document.getElementById('{{ $idPrefix }}_region');
        const provSelect = document.getElementById('{{ $idPrefix }}_provincia');
        const munSelect = document.getElementById('{{ $idPrefix }}_municipio');
        if (!provSelect || !munSelect) return;

        const selectedReg = @json($selectedRegion);
        const selectedProv = @json($selectedProvincia);
        const selectedMun = @json($selectedMunicipio);

        window.geographicData = { provincias: [], regiones: [] };

        fetch('/provincias-municipios-lista-oficial.json')
            .then(res => res.json())
            .then(data => {
                window.geographicData = data;
                // Limpiar opciones anteriores
                if (regSelect) regSelect.innerHTML = '<option value="">-- Seleccionar Región --</option>';
                provSelect.innerHTML = '<option value="">-- Seleccionar Provincia --</option>';
                
                const regionesData = data.regiones || [];
                if (regSelect) {
                    regionesData.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(rObj => {
                        const r = rObj.nombre;
                        const opt = document.createElement('option');
                        opt.value = r;
                        opt.textContent = r;
                        if (r === selectedReg) opt.selected = true;
                        regSelect.appendChild(opt);
                    });
                }

                const provincesData = data.provincias || [];
                
                provincesData.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(pObj => {
                    const p = pObj.nombre;
                    const opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    if (p === selectedProv) opt.selected = true;
                    provSelect.appendChild(opt);
                });

                function dispatchFilterChange() {
                    const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                    const nombreInput = document.getElementById('{{ $idPrefix }}_nombre');
                    const resetBtn = document.getElementById('{{ $idPrefix }}_reset');
                    
                    window.dispatchEvent(new CustomEvent('locationFilterChanged', {
                        detail: {
                            idPrefix: '{{ $idPrefix }}',
                            region: regSelect ? regSelect.value : '',
                            provincia: provSelect.value,
                            municipio: munSelect.value,
                            estado: estadoSelect ? estadoSelect.value : '',
                            nombre: nombreInput ? nombreInput.value : ''
                        }
                    }));
                    
                    if (resetBtn) {
                        const hasFilter = (regSelect && regSelect.value) || provSelect.value || munSelect.value || (estadoSelect && estadoSelect.value) || (nombreInput && nombreInput.value);
                        resetBtn.style.display = hasFilter ? 'inline-block' : 'none';
                    }
                }

                const resetBtn = document.getElementById('{{ $idPrefix }}_reset');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        if (regSelect) regSelect.value = '';
                        provSelect.value = '';
                        provSelect.dispatchEvent(new Event('change'));
                        const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                        if (estadoSelect) {
                            estadoSelect.value = '';
                            estadoSelect.dispatchEvent(new Event('change'));
                        }
                        const nombreInput = document.getElementById('{{ $idPrefix }}_nombre');
                        if (nombreInput) {
                            nombreInput.value = '';
                            nombreInput.dispatchEvent(new Event('input'));
                        }
                    });
                }

                if (regSelect) {
                    regSelect.addEventListener('change', function() {
                        if (this.value) {
                            provSelect.value = ''; // Clear provincia if region selected
                            if (provSelect.hasAttribute('required')) {
                                provSelect.removeAttribute('required');
                            }
                        } else if (!this.value && !provSelect.value) {
                            if (!provSelect.hasAttribute('required') && {{ $isRequired ? 'true' : 'false' }}) {
                                provSelect.setAttribute('required', 'required');
                            }
                        }
                        
                        munSelect.innerHTML = '<option value="">-- Seleccionar Municipio --</option>';
                        const r = this.value;
                        const foundReg = data.regiones.find(rg => rg.nombre === r);

                        if (r && foundReg && foundReg.municipios) {
                            const muns = [...foundReg.municipios].sort();
                            muns.forEach(m => {
                                const opt = document.createElement('option');
                                opt.value = m;
                                opt.textContent = m;
                                if (m === selectedMun && r === selectedReg) opt.selected = true;
                                munSelect.appendChild(opt);
                            });
                        } else if (!r && !provSelect.value) {
                            munSelect.innerHTML = '<option value="">-- Seleccione primero región o provincia --</option>';
                        }

                        const munDisplay = document.getElementById('{{ $idPrefix }}_municipio_display');
                        if (munDisplay) {
                            munDisplay.textContent = munSelect.value || '\u2014';
                        }

                        dispatchFilterChange();
                    });
                }

                provSelect.addEventListener('change', function() {
                    if (this.value && regSelect) {
                        regSelect.value = ''; // Clear region if provincia selected
                    }
                    
                    munSelect.innerHTML = '<option value="">-- Seleccionar Municipio --</option>';
                    const p = this.value;
                    const foundProv = data.provincias.find(pr => pr.nombre === p);

                    if (p && foundProv && foundProv.municipios) {
                        const muns = [...foundProv.municipios].sort();
                        muns.forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = m;
                            opt.textContent = m;
                            if (m === selectedMun && p === selectedProv) opt.selected = true;
                            munSelect.appendChild(opt);
                        });
                    } else if (!p && regSelect && !regSelect.value) {
                        munSelect.innerHTML = '<option value="">-- Seleccione primero región o provincia --</option>';
                    }

                    const munDisplay = document.getElementById('{{ $idPrefix }}_municipio_display');
                    if (munDisplay) {
                        munDisplay.textContent = munSelect.value || '\u2014';
                    }

                    dispatchFilterChange();
                });

                munSelect.addEventListener('change', dispatchFilterChange);
                const estadoSelect = document.getElementById('{{ $idPrefix }}_estado');
                if (estadoSelect) {
                    estadoSelect.addEventListener('change', dispatchFilterChange);
                }
                const nombreInput = document.getElementById('{{ $idPrefix }}_nombre');
                if (nombreInput) {
                    nombreInput.addEventListener('input', dispatchFilterChange);
                }

                if (selectedReg && regSelect) {
                    regSelect.dispatchEvent(new Event('change'));
                } else if (selectedProv) {
                    provSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(err => console.error("Error loading locations:", err));
    })();
});
</script>
