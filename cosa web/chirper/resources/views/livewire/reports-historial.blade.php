<div>
    @include('components.reports.styles')

    <div class="min-h-screen bg-gray-100 -m-4 sm:-m-6 lg:-m-8 p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('reports.index', [], false) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">← Vista general de reportes</a>
                <h1 class="text-3xl font-bold tracking-tight text-blue-800 mt-2">Historial de Inundaciones</h1>
                <p class="mt-2 text-sm text-slate-600">Inundaciones terminadas con duración y distribución de quórum.</p>
            </div>

            <div class="bg-white rounded border border-gray-200 overflow-hidden mb-10 shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-800">Terminadas</h2>
                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded text-xs font-bold">{{ $terminadasPaginator->total() }} registro(s)</span>
                </div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-sm glass-table rounded-xl overflow-hidden">
                        <thead class="text-slate-600">
                            <tr>
                                <th class="text-left font-semibold px-4 py-3 rounded-tl-xl">ID</th>
                                <th class="text-left font-semibold px-4 py-3">Intensidad</th>
                                <th class="text-left font-semibold px-4 py-3">Duración</th>
                                <th class="text-left font-semibold px-4 py-3">Distribución Q.</th>
                                <th class="text-right font-semibold px-4 py-3 rounded-tr-xl">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/50">
                            @forelse ($inundacionesTerminadas as $term)
                                @php
                                    $tid = data_get($term, 'id');
                                    $desglose = data_get($term, 'desglose_historico', ['alta' => 0, 'media' => 0, 'baja' => 0]);
                                    $totalQ = data_get($term, 'quorum_historico', 0);
                                    $intGanadora = $desglose['alta'] >= $desglose['media'] && $desglose['alta'] >= $desglose['baja']
                                        ? 'alta'
                                        : ($desglose['media'] >= $desglose['baja'] ? 'media' : 'baja');
                                @endphp
                                <tr class="transition-colors duration-200 cursor-pointer hover:bg-white/30" onclick="toggleHistorialDetails('term-{{ $tid }}')">
                                    <td class="px-4 py-3 font-semibold text-slate-700">N°{{ $tid }}</td>
                                    <td class="px-4 py-3">
                                        @if($totalQ > 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $intGanadora === 'alta' ? 'bg-rose-100 text-rose-700' : ($intGanadora === 'media' ? 'bg-amber-100 text-amber-700' : 'bg-teal-100 text-teal-700') }}">{{ $intGanadora }}</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-600 ml-1">Terminada</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 font-medium">{{ data_get($term, 'duracion_texto', '—') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1 text-[10px] font-bold uppercase">
                                            <span class="bg-rose-50 text-rose-600 px-2 py-1 rounded">A: {{ $desglose['alta'] }}</span>
                                            <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded">M: {{ $desglose['media'] }}</span>
                                            <span class="bg-teal-50 text-teal-600 px-2 py-1 rounded">B: {{ $desglose['baja'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="w-8 h-8 ml-auto flex items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg id="chevron-term-{{ $tid }}" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="details-term-{{ $tid }}" class="hidden bg-slate-50/50 border-t border-white/50">
                                    <td colspan="5" class="p-4">
                                        @php $repsVinc = data_get($term, 'reportes_vinculados', []); @endphp
                                        @if(count($repsVinc) > 0)
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2">
                                                @foreach($repsVinc as $rv)
                                                    <div class="bg-white rounded-lg p-2.5 text-xs border border-slate-100 shadow-sm flex justify-between items-center">
                                                        <div>
                                                            <span class="font-bold text-slate-700">N°{{ $rv['id'] }}</span>
                                                            <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $rv['intensidad_propuesta'] === 'alta' ? 'bg-rose-50 text-rose-700' : ($rv['intensidad_propuesta'] === 'media' ? 'bg-amber-50 text-amber-700' : 'bg-teal-50 text-teal-700') }}">{{ $rv['intensidad_propuesta'] }}</span>
                                                        </div>
                                                        <span class="text-slate-400 font-medium">{{ $rv['peso'] }}pts</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-slate-500 font-medium text-center">Sin reportes vinculados.</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 font-medium">No hay eventos en el historial.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 pb-4">
                    <x-reports.pagination :paginator="$terminadasPaginator" />
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleHistorialDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('chevron-' + id);
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if (icon) icon.classList.add('rotate-180');
            } else if (el) {
                el.classList.add('hidden');
                if (icon) icon.classList.remove('rotate-180');
            }
        }
    </script>
</div>
