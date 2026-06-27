<div>
    @include('components.reports.styles')

    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('reports.index', [], false) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Vista general de reportes</a>
                <h1 class="text-3xl font-bold tracking-tight text-blue-800 mt-2">Mis Reportes</h1>
                <p class="mt-2 text-sm text-slate-600">Estado de tus reportes enviados al sistema.</p>
            </div>

            <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Reportes enviados</h2>
                    @if($misReportes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ $misReportes->total() }} registro(s)</span>
                    @endif
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                <th class="text-left font-semibold px-4 py-3">Estado</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Detalle</th>
                                <th class="text-left font-semibold px-4 py-3 rounded-tr-xl">Actualización</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse($misReportes as $rep)
                                @php /** @var \App\Models\Reporte $rep */ @endphp
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $rep->id }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold
                                            {{ (string) $rep->estado_validacion === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : ((string) $rep->estado_validacion === 'aceptado' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                            {{ ucfirst((string) $rep->estado_validacion) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-600">
                                        {{ ucfirst((string) $rep->intensidad_propuesta) }}
                                        @if($rep->fueAjustado())
                                            <span class="block text-[11px] text-indigo-600 mt-0.5">Validada: {{ ucfirst((string) $rep->intensidad_validada) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 text-xs max-w-[14rem]">
                                        @if((string) $rep->estado_validacion === 'rechazado')
                                            {{ $rep->motivoRechazo?->label_ciudadano ?? 'Sin motivo registrado.' }}
                                        @elseif($rep->fueAjustado())
                                            {{ $rep->ajuste_comentario }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-500">{{ optional($rep->updated_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-slate-500 text-center font-medium">Aún no has enviado reportes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($misReportes instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <div class="px-4 pb-4">
                        <x-reports.pagination :paginator="$misReportes" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
