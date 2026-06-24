@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-heading">Inventario: {{ $centro->nombre }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $centro->direccion }}</p>
        </div>
        <a href="{{ route('inventario.index') }}" class="mt-4 sm:mt-0 text-sm font-medium text-primary-600 hover:text-primary-500">&larr; Volver a Centros</a>
    </div>

    @php($apiUser = (array) session('api_user', []))
    @php($apiRole = (string) ($apiUser['role'] ?? ''))

    @if($apiRole === 'authority')
    <!-- Formulario Agregar Item -->
    <div x-data="{ cat: 'comida', donor: 'anonimo' }" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Registrar Nuevo Ítem</h2>
        <form action="{{ route('inventario.store', $centro->id_centro) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="categoria" class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select x-model="cat" id="categoria" name="categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                        <option value="comida">Comida / Víveres</option>
                        <option value="bebida">Agua / Bebidas</option>
                        <option value="ropa">Ropa / Abrigo</option>
                        <option value="medicamentos">Medicamentos</option>
                        <option value="dinero">Dinero</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>
                
                <div>
                    <label for="donor_type" class="block text-sm font-medium text-gray-700">Tipo de Donante</label>
                    <select x-model="donor" id="donor_type" name="donor_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
                        <option value="anonimo">Anónimo</option>
                        <option value="ciudadano">Ciudadano Registrado/No Registrado</option>
                        <option value="autoridad">Autoridad (Mi carnet)</option>
                    </select>
                </div>

                <div x-show="donor === 'ciudadano'" class="sm:col-span-2" style="display: none;">
                    <label for="donor_carnet_input" class="block text-sm font-medium text-gray-700">Carnet del Ciudadano</label>
                    <input type="text" id="donor_carnet_input" name="donor_carnet_input" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2" placeholder="Ej. 1234567">
                </div>

                <!-- Cantidad Dinámica -->
                <div x-show="cat !== 'otros'" class="sm:col-span-2 grid grid-cols-2 gap-4">
                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2" placeholder="Ej. 50">
                    </div>
                    <div>
                        <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de Medida</label>
                        <select id="unidad_medida" name="unidad_medida" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2">
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

                <div class="sm:col-span-2">
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción Adicional (Opcional)</label>
                    <textarea id="descripcion" name="descripcion" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm px-3 py-2" placeholder="Ej. Tallas de ropa, marca del producto, etc..."></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Guardar Registro
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Lista de Inventario -->
    <div x-data="{ 
        showBulk: false,
        itemsData: {
            @foreach($inventario as $item)
            {{ $item->id }}: { status: '{{ $item->status }}', categoria: '{{ $item->categoria }}' },
            @endforeach
        },
        selectedItemIds: [],
        newStatus: '',
        
        get selectedStatus() {
            if (this.selectedItemIds.length === 0) return null;
            return this.itemsData[this.selectedItemIds[0]].status;
        },
        
        get canDiscard() {
            if (this.selectedItemIds.length === 0) return false;
            const allowed = ['comida', 'bebida', 'medicamentos'];
            for(let id of this.selectedItemIds) {
                if (!allowed.includes(this.itemsData[id].categoria)) return false;
            }
            return true;
        },

        get isTargetRetirado() {
            return this.newStatus === 'retirado';
        },

        isDisabled(itemId) {
            if (this.itemsData[itemId].status === 'desechado' || this.itemsData[itemId].status === 'entregado') return true;
            if (this.selectedItemIds.length === 0) return false;
            return this.itemsData[itemId].status !== this.selectedStatus;
        }
    }" class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-5 sm:px-6 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Ítems en Inventario</h3>
            
            @if($apiRole === 'authority' && $inventario->count() > 0)
            <button @click="showBulk = !showBulk" class="px-3 py-1.5 bg-gray-900 text-white text-sm font-medium rounded hover:bg-gray-800 transition-colors">
                Realizar Movimiento
            </button>
            @endif
        </div>

        @if($apiRole === 'authority')
        <form action="{{ route('inventario.bulkUpdateStatus', $centro->id_centro) }}" method="POST" enctype="multipart/form-data">
            @csrf
        @endif

        <ul class="divide-y divide-gray-200">
            @forelse($inventario as $item)
            <li class="p-6 hover:bg-gray-50 transition-colors" :class="{'opacity-50': isDisabled({{ $item->id }})}">
                <div class="flex items-start">
                    
                    @if($apiRole === 'authority')
                    <div x-show="showBulk" class="mr-4 mt-1" style="display: none;">
                        <input type="checkbox" name="items[]" value="{{ $item->id }}" x-model="selectedItemIds" :disabled="isDisabled({{ $item->id }})" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded disabled:bg-gray-200 disabled:cursor-not-allowed">
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
                            <span class="text-sm text-gray-500">{{ $item->created_at->format('d/m/Y H:i') }}</span>
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
                        <div class="flex items-center gap-3 mt-3">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado Actual:</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ strtoupper(str_replace('_', ' ', $item->status)) }}
                            </span>
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

        @if($apiRole === 'authority')
            <!-- Controles de Bulk Update -->
            <div x-show="showBulk && selectedItemIds.length > 0" class="bg-gray-100 p-4 border-t border-gray-200" style="display: none;" x-transition>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Actualizar <span x-text="selectedItemIds.length"></span> ítem(s) seleccionado(s)</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Nuevo Estado</label>
                        <select name="status" x-model="newStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" required>
                            <option value="" disabled selected>Seleccionar...</option>
                            <template x-if="selectedStatus === 'recibido_centro'">
                                <option value="almacenado">Guardado en Almacén</option>
                            </template>
                            <template x-if="selectedStatus === 'almacenado'">
                                <option value="retirado">Retiro de Almacén</option>
                            </template>
                            <template x-if="selectedStatus === 'retirado'">
                                <option value="en_transito">En Tránsito (Vehículo)</option>
                            </template>
                            <template x-if="selectedStatus === 'en_transito'">
                                <option value="entregado">Entregado al Beneficiario</option>
                            </template>
                            <template x-if="(selectedStatus === 'recibido_centro' || selectedStatus === 'almacenado') && canDiscard">
                                <option value="desechado">Desechar (Mal estado)</option>
                            </template>
                        </select>
                    </div>

                    <div x-show="isTargetRetirado" style="display: none;">
                        <label class="block text-xs font-medium text-gray-700">Vincular a Inundación (Opcional)</label>
                        <select name="inundacion_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">-- Ninguna --</option>
                            @foreach($inundaciones as $inundacion)
                                <option value="{{ $inundacion->id }}">Inundación #{{ $inundacion->id }} - {{ $inundacion->address ?? 'Sin dirección' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div :class="{'sm:col-span-1': isTargetRetirado, 'sm:col-span-2': !isTargetRetirado}">
                        <label class="block text-xs font-medium text-gray-700">
                            Observación / Destino <span x-show="newStatus === 'desechado'" class="text-red-500 font-bold">*</span>
                        </label>
                        <input type="text" name="usage_details" :required="newStatus === 'desechado'" placeholder="Ej: Entregado a la comunidad..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700">Foto <span x-show="newStatus === 'entregado'" class="text-red-500 font-bold">*</span></label>
                        <input type="file" name="photo" accept="image/*" :required="newStatus === 'entregado'" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="px-4 py-2 text-white text-sm font-medium rounded-md transition-colors" :class="newStatus === 'desechado' ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700'" :disabled="!newStatus">
                        <span x-show="newStatus !== 'desechado'">Confirmar Movimiento</span>
                        <span x-show="newStatus === 'desechado'" style="display: none;">Desechar Donaciones</span>
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>

<!-- Add Alpine.js if not present for the x-data toggle -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
