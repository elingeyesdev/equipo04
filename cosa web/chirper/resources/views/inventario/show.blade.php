@extends('layouts.app')

@section('content')
<div x-data="{ showSlideOver: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-heading">Inventario: {{ $centro->nombre }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $centro->direccion }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-4">
            <a href="{{ route('inventario.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-2 rounded-md shadow-sm transition-colors">Volver a Centros</a>
            
            @php($apiUser = (array) session('api_user', []))
            @php($apiRole = (string) ($apiUser['role'] ?? ''))
            
            @if(in_array($apiRole, ['authority', 'super-admin']))
            <button @click="showSlideOver = true" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                </svg>
                Registrar Donación
            </button>
            @endif
        </div>
    </div>

    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    <!-- Estadísticas del Centro -->
    <dl class="mt-2 grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">Total en Registro</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ number_format($stats['total']) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">En Almacén</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">{{ number_format($stats['en_almacen']) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6 border border-gray-100">
            <dt class="truncate text-sm font-medium text-gray-500">Entregados</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">{{ number_format($stats['entregados']) }}</dd>
        </div>
    </dl>

    @if(in_array($apiRole, ['authority', 'super-admin']))
    <!-- Slide-over (Formulario Oculto) -->
    <div x-show="showSlideOver" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
        <!-- Fondo oscuro -->
        <div x-show="showSlideOver"
             x-transition:enter="ease-in-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-500"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
                    <div x-show="showSlideOver"
                         @click.away="showSlideOver = false"
                         x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="pointer-events-auto w-screen max-w-md">
                         
                         <!-- Contenido del Slide-over -->
                         <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                            <div class="px-4 py-6 sm:px-6 bg-primary-700 text-white flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-bold" id="slide-over-title">Registrar Donación</h2>
                                    <p class="mt-1 text-sm text-primary-100">Complete los datos del ítem a ingresar al inventario.</p>
                                </div>
                                <button type="button" @click="showSlideOver = false" class="rounded-md bg-primary-700 text-primary-200 hover:text-white focus:outline-none">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="relative flex-1 px-4 py-6 sm:px-6" x-data="{ cat: 'comida', donor: 'anonimo' }">
                                <form action="{{ route('inventario.store', $centro->id_centro) }}" method="POST">
                                    @csrf
                                    <div class="space-y-6">
                                        <!-- Categoría -->
                                        <div>
                                            <label for="categoria" class="block text-sm font-semibold text-gray-700">Categoría del Ítem</label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <select x-model="cat" id="categoria" name="categoria" class="block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 px-3">
                                                    <option value="comida">Comida / Víveres</option>
                                                    <option value="bebida">Agua / Bebidas</option>
                                                    <option value="ropa">Ropa / Abrigo</option>
                                                    <option value="medicamentos">Medicamentos</option>
                                                    <option value="dinero">Dinero</option>
                                                    <option value="otros">Otros</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <!-- Tipo de Donante -->
                                        <div>
                                            <label for="donor_type" class="block text-sm font-semibold text-gray-700">Origen de la Donación</label>
                                            <select x-model="donor" id="donor_type" name="donor_type" class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 px-3 shadow-sm">
                                                <option value="anonimo">Donante Anónimo</option>
                                                <option value="ciudadano">Ciudadano (C.I.)</option>
                                                <option value="autoridad">Registro Propio (Mi cuenta)</option>
                                            </select>
                                        </div>

                                        <div x-show="donor === 'ciudadano'" style="display: none;" x-transition>
                                            <label for="donor_carnet_input" class="block text-sm font-semibold text-gray-700">Carnet del Ciudadano</label>
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <input type="text" id="donor_carnet_input" name="donor_carnet_input" class="block w-full pl-10 rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3" placeholder="Ej. 1234567">
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500">Si el ciudadano está registrado, la donación se vinculará a su cuenta.</p>
                                        </div>

                                        <!-- Cantidad Dinámica -->
                                        <div x-show="cat !== 'otros'" class="grid grid-cols-2 gap-4" x-transition>
                                            <div>
                                                <label for="cantidad" class="block text-sm font-semibold text-gray-700">Cantidad</label>
                                                <input type="number" id="cantidad" name="cantidad" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 px-3" placeholder="Ej. 50">
                                            </div>
                                            <div>
                                                <label for="unidad_medida" class="block text-sm font-semibold text-gray-700">Unidad</label>
                                                <select id="unidad_medida" name="unidad_medida" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 px-3 bg-gray-50">
                                                    <template x-if="cat === 'comida'">
                                                        <optgroup label="Comida">
                                                            <option value="kg">Kilogramos (Kg)</option>
                                                            <option value="raciones">Raciones</option>
                                                            <option value="latas">Latas</option>
                                                            <option value="bolsas">Bolsas</option>
                                                        </optgroup>
                                                    </template>
                                                    <template x-if="cat === 'bebida'">
                                                        <optgroup label="Bebidas">
                                                            <option value="litros">Litros (L)</option>
                                                            <option value="botellas">Botellas</option>
                                                            <option value="paquetes">Paquetes</option>
                                                        </optgroup>
                                                    </template>
                                                    <template x-if="cat === 'ropa'">
                                                        <optgroup label="Ropa">
                                                            <option value="prendas">Prendas / Piezas</option>
                                                            <option value="bolsas">Bolsas</option>
                                                        </optgroup>
                                                    </template>
                                                    <template x-if="cat === 'medicamentos'">
                                                        <optgroup label="Medicamentos">
                                                            <option value="cajas">Cajas</option>
                                                            <option value="unidades">Unidades</option>
                                                        </optgroup>
                                                    </template>
                                                    <template x-if="cat === 'dinero'">
                                                        <optgroup label="Dinero">
                                                            <option value="bs">Bolivianos (Bs)</option>
                                                            <option value="usd">Dólares (USD)</option>
                                                        </optgroup>
                                                    </template>
                                                </select>
                                            </div>
                                        </div>

                                        <div>
                                            <label for="descripcion" class="block text-sm font-semibold text-gray-700">Descripción Adicional</label>
                                            <textarea id="descripcion" name="descripcion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-3 px-3" placeholder="Ej. Tallas de ropa, marca del producto, observaciones importantes..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-8 pt-5 border-t border-gray-200">
                                        <div class="flex justify-end gap-3">
                                            <button type="button" @click="showSlideOver = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                Guardar Registro
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Lista de Inventario -->
    <div x-data="{ 
        showBulk: false,
        itemsData: {
            @foreach($inventario as $item)
            {{ $item->id }}: { 
                status: '{{ $item->status }}', 
                categoria: '{{ $item->categoria }}',
                inundacion_id: '{{ $item->inundacion_id }}'
            },
            @endforeach
        },
        selectedItemIds: [],
        isDiscarding: false,
        newStatus: '',
        
        get selectedStatus() {
            if (this.selectedItemIds.length === 0) return null;
            return this.itemsData[this.selectedItemIds[0]].status;
        },
        
        get hasInundacion() {
            if (this.selectedItemIds.length === 0) return false;
            return this.itemsData[this.selectedItemIds[0]].inundacion_id !== '';
        },
        
        get canDiscard() {
            if (this.selectedItemIds.length === 0) return false;
            const allowed = ['comida', 'bebida', 'medicamentos'];
            for(let id of this.selectedItemIds) {
                if (!allowed.includes(this.itemsData[id].categoria)) return false;
            }
            return (this.selectedStatus === 'recibido_centro' || this.selectedStatus === 'almacenado' || this.selectedStatus === 'en_inventario');
        },

        get nextStatusInfo() {
            if (!this.selectedStatus) return { value: '', label: 'Seleccione un ítem' };
            if (this.selectedStatus === 'recibido_centro') return { value: 'almacenado', label: 'Guardar en Almacén' };
            if (this.selectedStatus === 'almacenado' || this.selectedStatus === 'en_inventario') return { value: 'en_transito', label: 'Enviar a Tránsito' };
            if (this.selectedStatus === 'retirado' || this.selectedStatus === 'en_transito') return { value: 'entregado', label: 'Marcar Entregado' };
            return { value: '', label: 'Proceso Finalizado' };
        }
    }" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        
        <!-- Filtros -->
        <div class="p-4 bg-white border-b border-gray-200">
            <form method="GET" action="{{ route('inventario.show', $centro->id_centro) }}" class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="w-full sm:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="status" onchange="this.form.submit()" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3">
                        <option value="">Activos (Ocultar entregados/desechados)</option>
                        <option value="all" {{ ($status ?? '') == 'all' ? 'selected' : '' }}>Todos los estados</option>
                        <option value="recibido_centro" {{ ($status ?? '') == 'recibido_centro' ? 'selected' : '' }}>Recibido en Centro</option>
                        <option value="almacenado" {{ ($status ?? '') == 'almacenado' ? 'selected' : '' }}>Almacenado</option>
                        <option value="en_transito" {{ ($status ?? '') == 'en_transito' ? 'selected' : '' }}>En Tránsito</option>
                        <option value="entregado" {{ ($status ?? '') == 'entregado' ? 'selected' : '' }}>Entregado</option>
                        <option value="desechado" {{ ($status ?? '') == 'desechado' ? 'selected' : '' }}>Desechado</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" onchange="this.form.submit()" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm py-2.5 px-3">
                        <option value="">Todas las categorías</option>
                        <option value="comida" {{ ($categoria ?? '') == 'comida' ? 'selected' : '' }}>Comida</option>
                        <option value="bebida" {{ ($categoria ?? '') == 'bebida' ? 'selected' : '' }}>Bebidas</option>
                        <option value="ropa" {{ ($categoria ?? '') == 'ropa' ? 'selected' : '' }}>Ropa</option>
                        <option value="medicamentos" {{ ($categoria ?? '') == 'medicamentos' ? 'selected' : '' }}>Medicamentos</option>
                        <option value="dinero" {{ ($categoria ?? '') == 'dinero' ? 'selected' : '' }}>Dinero</option>
                        <option value="otros" {{ ($categoria ?? '') == 'otros' ? 'selected' : '' }}>Otros</option>
                    </select>
                </div>
                <div class="w-full sm:w-1/4 flex items-center pt-5">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="recent" value="30_days" onchange="this.form.submit()" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-5 w-5" {{ ($recent ?? '') == '30_days' ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700 font-medium">Últimos 30 días</span>
                    </label>
                </div>
                <div class="w-full sm:w-1/4 flex gap-2 items-center pt-5">
                    @if(request()->hasAny(['status', 'categoria', 'recent']))
                        <a href="{{ route('inventario.show', $centro->id_centro) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Limpiar Filtros
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Ítems en Inventario</h3>
            
            @if(in_array($apiRole, ['authority', 'super-admin']) && $inventario->count() > 0)
            <button type="button" @click="showBulk = !showBulk" class="px-3 py-1.5 bg-gray-900 text-white text-sm font-medium rounded hover:bg-gray-800 transition-colors shadow-sm">
                Realizar Movimiento (Lote)
            </button>
            @endif
        </div>

        <ul class="divide-y divide-gray-200">
            @forelse($inventario as $item)
            <li class="p-6 hover:bg-gray-50 transition-colors" :class="{'opacity-50': isDisabled({{ $item->id }})}">
                <div class="flex items-start">
                    
                    @if(in_array($apiRole, ['authority', 'super-admin']))
                    <div x-show="showBulk" class="mr-4 mt-1" style="display: none;">
                        <input type="checkbox" name="items[]" form="bulkUpdateForm" value="{{ $item->id }}" x-model="selectedItemIds" :disabled="itemsData[{{ $item->id }}].status === 'desechado' || itemsData[{{ $item->id }}].status === 'entregado' || (selectedItemIds.length > 0 && itemsData[{{ $item->id }}].status !== selectedStatus)" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded disabled:bg-gray-200 disabled:cursor-not-allowed">
                    </div>
                    @endif

                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $item->categoria == 'comida' ? 'bg-orange-100 text-orange-800' : 
                                  ($item->categoria == 'bebida' ? 'bg-blue-100 text-blue-800' : 
                                  ($item->categoria == 'ropa' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($item->categoria) }}
                            </span>
                            <div class="text-right">
                                <span class="block text-sm text-gray-700 font-semibold">Donado hace: {{ $item->created_at->diffForHumans() }}</span>
                                <span class="block text-xs text-gray-400 mt-0.5">Última act. hace: {{ $item->updated_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        
                        @if($item->cantidad)
                        <p class="text-gray-900 font-bold mb-1 text-lg">{{ $item->cantidad }} {{ $item->unidad_medida }}</p>
                        @endif
                        
                        <p class="text-gray-900 font-medium mb-1">{{ $item->descripcion }}</p>
                        
                        <div class="text-sm text-gray-500 mb-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <span class="font-semibold text-gray-700">Donado por:</span> 
                                @if($item->is_anonymous)
                                    <span class="italic text-gray-400">Anónimo</span>
                                @else
                                    {{ $item->donor_carnet ? ($item->donor ? $item->donor->name : 'C.I. ' . $item->donor_carnet . ' (No Registrado)') : 'Desconocido' }}
                                @endif
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Registrado por:</span> 
                                {{ $item->registrador ? $item->registrador->name : 'Autoridad' }}
                            </div>
                        </div>

                        <!-- Estado actual -->
                        <div class="mt-4">
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border tracking-wider
                                {{ $item->status == 'recibido_centro' ? 'border-gray-300 text-gray-700 bg-white shadow-sm' : '' }}
                                {{ $item->status == 'almacenado' ? 'border-blue-300 text-blue-800 bg-blue-50 shadow-sm' : '' }}
                                {{ $item->status == 'retirado' ? 'border-yellow-300 text-yellow-800 bg-yellow-50 shadow-sm' : '' }}
                                {{ $item->status == 'en_transito' ? 'border-orange-300 text-orange-800 bg-orange-50 shadow-sm' : '' }}
                                {{ $item->status == 'entregado' ? 'border-green-300 text-green-800 bg-green-50 shadow-sm' : '' }}
                                {{ $item->status == 'desechado' ? 'border-red-300 text-red-800 bg-red-50 shadow-sm' : '' }}">
                                <span class="uppercase">{{ str_replace('_', ' ', $item->status) }}</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('inventario.item.show', $item->id) }}" class="text-sm font-medium text-primary-600 hover:text-primary-800">
                                Ver Detalles y Trazabilidad &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="p-6 text-center text-gray-500">No hay ítems registrados en este centro aún.</li>
            @endforelse
        </ul>

        @if(in_array($apiRole, ['authority', 'super-admin']))
            <!-- Floating Action Bar para Bulk Update -->
            <div x-show="showBulk && selectedItemIds.length > 0" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition ease-in duration-300 transform"
                 x-transition:leave-start="translate-y-0"
                 x-transition:leave-end="translate-y-full"
                 class="fixed bottom-0 left-0 right-0 z-40 bg-white shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)] border-t border-gray-200 p-4" 
                 style="display: none;">
                 
                 <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                     <form id="bulkUpdateForm" action="{{ route('inventario.bulkUpdateStatus', $centro->id_centro) }}" method="POST" enctype="multipart/form-data">
                         @csrf
                         
                         <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3">
                             <h4 class="text-sm font-medium text-gray-900">
                                 Actualizar <span x-text="selectedItemIds.length" class="font-bold text-primary-600"></span> ítem(s) seleccionado(s)
                             </h4>
                             <button type="button" @click="showBulk = false; selectedItemIds = []" class="text-sm text-gray-500 hover:text-gray-700 font-medium">
                                 Cerrar y Cancelar
                             </button>
                         </div>
                         
                         <div class="flex flex-col sm:flex-row gap-4 items-end">
                             
                             <input type="hidden" name="status" :value="isDiscarding ? 'desechado' : nextStatusInfo.value">
                             
                             <div class="flex-1 min-w-[200px]" x-show="['en_transito', 'entregado'].includes(nextStatusInfo.value) && !hasInundacion" style="display: none;">
                                 <label class="block text-xs font-medium text-gray-700 mb-1">Inundación Destino (Opcional)</label>
                                 <select name="inundacion_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2.5 px-3">
                                     <option value="">-- Ninguna --</option>
                                     @foreach($inundaciones as $inundacion)
                                         <option value="{{ $inundacion->id }}">Inundación #{{ $inundacion->id }} - {{ $inundacion->address ?? 'Sin dirección' }}</option>
                                     @endforeach
                                 </select>
                             </div>
    
                             <div class="flex-1 min-w-[200px]" x-show="nextStatusInfo.value === 'entregado'" style="display: none;">
                                 <label class="block text-xs font-medium text-gray-700 mb-1">Víctima Beneficiada (Opcional)</label>
                                 <select name="victima_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2.5 px-3">
                                     <option value="">-- Ninguna --</option>
                                     @foreach($victimas as $victima)
                                         <option value="{{ $victima->id }}">{{ $victima->nombre_completo }} (CI: {{ $victima->carnet }})</option>
                                     @endforeach
                                 </select>
                             </div>
    
                             <div class="flex-1 min-w-[250px]">
                                 <label class="block text-xs font-medium text-gray-700 mb-1">
                                     Observación / Detalle Destino <span x-show="canDiscard" class="text-red-500 text-xs font-normal ml-1" style="display: none;">(*Requerido si Desecha)</span>
                                 </label>
                                 <input type="text" name="usage_details" placeholder="Ej: Entregado a la comunidad..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-4 py-2.5">
                             </div>
    
                             <div x-show="nextStatusInfo.value === 'entregado'" style="display: none;" class="w-48">
                                 <label class="block text-xs font-medium text-gray-700 mb-1">Foto <span class="text-red-500 font-bold">*</span></label>
                                 <input type="file" name="photo" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                             </div>
    
                             <div class="flex items-center gap-2">
                                 <button type="submit" x-show="canDiscard" @click="isDiscarding = true" class="px-4 py-2.5 bg-white text-red-700 border border-red-200 hover:bg-red-50 text-sm font-medium rounded-md transition-colors shadow-sm" style="display: none;">
                                     Desechar
                                 </button>
                                 <button type="submit" @click="isDiscarding = false" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-md transition-colors shadow-sm" :disabled="!nextStatusInfo.value">
                                     <span x-text="nextStatusInfo.label"></span> &rarr;
                                 </button>
                             </div>
                         </div>
                     </form>
                 </div>
            </div>
        @endif
    </div>
</div>

<!-- Add Alpine.js if not present for the x-data toggle -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
