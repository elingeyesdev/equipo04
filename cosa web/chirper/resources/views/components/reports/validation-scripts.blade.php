

<div wire:ignore>
<script src="{{ asset('js/smart-heatmap.js') }}?v=20260629e"></script>
<script src="{{ asset('js/flood-outline.js') }}?v=20260627b"></script>
<script>
    window.ORS_API_KEY = "{{ $ors_key ?? '' }}";
</script>
<script src="{{ asset('js/safe-routing.js') }}"></script>

<script>
window.renderPendingReports = function (pendingData) {
    if (typeof window.renderPendingReportsMap === 'function') {
        window.renderPendingReportsMap(pendingData);
    }
};

window.validateReport = function(id, action, intensidadPropuesta) {
    if (action === 'rechazar') {
        openRejectModal(id, 'Ciudadano', null, null, 1, 0, 0);
        return;
    }
    openApproveModal(id, action, intensidadPropuesta || 'media');
};

window.buildPendingReportDrawerPayload = function (report) {
    const cercanas = Array.isArray(report.cercanas) ? report.cercanas : [];
    return {
        id: report.id,
        description: report.description || '',
        address: report.address || '',
        intensidad_propuesta: report.intensidad_propuesta || 'media',
        lat_reporte: report.lat_reporte,
        long_reporte: report.long_reporte,
        lat_gps: report.lat_gps,
        long_gps: report.long_gps,
        cercanas: cercanas,
        solo_vincular: !!report.solo_vincular,
        dentro_contorno_activo: !!report.dentro_contorno_activo,
    };
};

window.openReviewDrawerFromReport = function (report) {
    const fakeBtn = document.createElement('button');
    fakeBtn.setAttribute('data-report', JSON.stringify(window.buildPendingReportDrawerPayload(report)));
    openReviewDrawer(fakeBtn);
};

window.openReviewDrawerByReportId = function (reportId) {
    const report = (window.pendingReports || []).find(function (r) {
        return r.id === reportId;
    });
    if (report) {
        window.openReviewDrawerFromReport(report);
    }
};

window.buildPendingValidationPopupHtml = function (report) {
    const intensidad = (report.intensidad_propuesta || 'media').replace(/'/g, "\\'");
    const reporter = (report.reporter_name || 'Ciudadano').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const cercanasCount = Array.isArray(report.cercanas) ? report.cercanas.length : 0;
    const soloVincular = !!report.solo_vincular;
    const dentroContorno = !!report.dentro_contorno_activo;
    const distancia = report.distancia_gps_metros != null ? report.distancia_gps_metros : 'null';
    const peso = report.peso != null ? report.peso : 1;
    const rechazos = report.rechazos_previos || 0;

    let html = '<div class="max-w-xs font-sans">'
        + '<p class="font-semibold text-sm mb-1 text-orange-600">Reporte Pendiente N°' + report.id + '</p>'
        + '<p class="text-xs text-gray-600 mb-1"><b>Intensidad propuesta:</b> ' + (report.intensidad_propuesta || 'media') + '</p>';

    if (dentroContorno) {
        html += '<p class="text-[10px] text-blue-700 bg-blue-50 rounded px-2 py-1 mb-2 font-semibold">Dentro de zona de inundación activa — vincular</p>';
    }

    html += '<div class="flex flex-col gap-2 mt-2">';

    if (!soloVincular) {
        html += '<button type="button" onclick="openApproveModal(' + report.id + ', \'crear\', \'' + intensidad + '\')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1.5 text-xs rounded-lg font-bold">Aprobar</button>';
    }

    if (cercanasCount > 0) {
        html += '<button type="button" onclick="openReviewDrawerByReportId(' + report.id + ')" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 text-xs rounded-lg font-bold">Vincular</button>';
    }

    html += '<button type="button" onclick="openRejectModal(' + report.id + ', \'' + reporter + '\', ' + distancia + ', null, ' + peso + ', ' + cercanasCount + ', ' + rechazos + ')" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-2 py-1.5 text-xs rounded-lg font-bold">Rechazar</button>'
        + '</div></div>';

    return html;
};
</script>


<script src="{{ asset('js/report-minimaps.js') }}"></script>

<script>
        function toggleDetails(id) {
            const el = document.getElementById('details-' + id);
            const icon = document.getElementById('chevron-' + id);
            if (el && el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.classList.add('rotate-180');
            } else if (el) {
                el.classList.add('hidden');
                if(icon) icon.classList.remove('rotate-180');
            }
        }

        const motivosRechazo = @json(($motivosRechazo ?? collect())->values());
        let pendingValidation = { id: null, action: null, inundacionId: null, intensidadPropuesta: 'media' };

        function postValidar(body) {
            return fetch('/api/reportes/' + body.reportId + '/validar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': 'Bearer {{ session("api_token") }}'
                },
                body: JSON.stringify(body.payload)
            }).then(async (res) => {
                const data = await res.json();
                if (!res.ok) {
                    const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al procesar.');
                    throw new Error(msg);
                }
                return data;
            });
        }

        function validarRapido(id, action, inundacion_id = null, extra = {}) {
            const payload = Object.assign({ action: action }, extra);
            if (action === 'vincular') {
                if (!inundacion_id) return;
                payload.inundacion_id = inundacion_id;
            }

            postValidar({ reportId: id, payload: payload })
                .then((data) => {
                    Swal.fire('¡Listo!', data.message, 'success');
                    Livewire.dispatch('refreshReports');
                    if (typeof window.refreshReportsMap === 'function') {
                        window.refreshReportsMap();
                        if (action === 'crear' || action === 'vincular') {
                            [500, 2000, 5000, 10000].forEach(function (delayMs) {
                                setTimeout(function () { window.refreshReportsMap(); }, delayMs);
                            });
                        }
                    }
                })
                .catch((err) => {
                    Swal.fire('Error', err.message || 'Ocurrió un error al procesar la solicitud.', 'error');
                });
        }

        function openRejectModal(id, reporter, distancia, precipitacion, peso, cercanas, rechazos) {
            pendingValidation = { id: id, action: 'rechazar', inundacionId: null, intensidadPropuesta: 'media' };
            document.getElementById('rejectReportId').textContent = 'N°' + id;
            document.getElementById('rejectContext').innerHTML =
                '<p><strong>Reportado por:</strong> ' + reporter + '</p>' +
                (distancia !== null ? '<p><strong>Distancia GPS:</strong> ' + Math.round(distancia) + ' m</p>' : '') +
                (precipitacion !== null ? '<p><strong>Precipitación:</strong> ' + precipitacion + ' mm</p>' : '') +
                '<p><strong>Peso:</strong> ' + peso + '</p>' +
                (cercanas > 0 ? '<p class="text-amber-700"><strong>Atención:</strong> ' + cercanas + ' inundación(es) activa(s) a ≤300 m.</p>' : '') +
                (rechazos > 0 ? '<p class="text-amber-700"><strong>Historial:</strong> ' + rechazos + ' rechazo(s) previo(s) del ciudadano.</p>' : '');

            const select = document.getElementById('rejectMotivoCodigo');
            select.innerHTML = '<option value="">Seleccionar motivo...</option>';
            motivosRechazo.forEach((m) => {
                select.innerHTML += '<option value="' + m.codigo + '" data-requiere-nota="' + (m.requiere_nota ? '1' : '0') + '">' + m.label_autoridad + '</option>';
            });
            document.getElementById('rejectMotivoTexto').value = '';
            document.getElementById('rejectModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('rejectModal').classList.remove('opacity-0'), 10);
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 200);
        }

        function confirmRejectModal() {
            const codigo = document.getElementById('rejectMotivoCodigo').value;
            const texto = document.getElementById('rejectMotivoTexto').value.trim();
            const option = document.getElementById('rejectMotivoCodigo').selectedOptions[0];
            const requiereNota = option && option.dataset.requiereNota === '1';

            if (!codigo) {
                Swal.fire('Motivo requerido', 'Selecciona un motivo de rechazo.', 'warning');
                return;
            }
            if (requiereNota && texto.length < 5) {
                Swal.fire('Nota requerida', 'Este motivo requiere una nota más detallada.', 'warning');
                return;
            }

            validarRapido(pendingValidation.id, 'rechazar', null, {
                motivo_codigo: codigo,
                motivo_texto: texto || null,
            });
            closeRejectModal();
        }

        function populateApproveIntensidadOptions(excludeLevel) {
            const select = document.getElementById('approveIntensidadValidada');
            const levels = [
                { value: 'baja', label: 'Baja' },
                { value: 'media', label: 'Media' },
                { value: 'alta', label: 'Alta' },
            ];
            const available = levels.filter((level) => level.value !== excludeLevel);

            select.innerHTML = '';
            available.forEach((level) => {
                const option = document.createElement('option');
                option.value = level.value;
                option.textContent = level.label;
                select.appendChild(option);
            });

            if (available.length > 0) {
                select.value = available[0].value;
            }
        }

        function setApproveIntensidadPropuestaPill(intensidad) {
            const el = document.getElementById('approveIntensidadPropuesta');
            const level = intensidad || 'media';
            el.textContent = level.charAt(0).toUpperCase() + level.slice(1);
            el.className = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide intensity-pill-' + (level === 'alta' ? 'alta' : level === 'media' ? 'media' : 'baja');
        }

        function openApproveModal(id, action, intensidadPropuesta, inundacionId = null) {
            const propuesta = intensidadPropuesta || 'media';
            pendingValidation = { id: id, action: action, inundacionId: inundacionId, intensidadPropuesta: propuesta };
            document.getElementById('approveReportId').textContent = 'N°' + id;
            setApproveIntensidadPropuestaPill(propuesta);
            populateApproveIntensidadOptions(propuesta);
            document.getElementById('approveAjusteToggle').checked = false;
            document.getElementById('approveAjusteFields').classList.add('hidden');
            document.getElementById('approveComentario').value = '';
            document.getElementById('approveModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('approveModal').classList.remove('opacity-0'), 10);
        }

        function closeApproveModal() {
            const modal = document.getElementById('approveModal');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 200);
        }

        function toggleApproveAjuste() {
            const checked = document.getElementById('approveAjusteToggle').checked;
            document.getElementById('approveAjusteFields').classList.toggle('hidden', !checked);
        }

        function confirmApproveModal() {
            const payload = { action: pendingValidation.action };
            if (pendingValidation.action === 'vincular' && pendingValidation.inundacionId) {
                payload.inundacion_id = pendingValidation.inundacionId;
            }
            if (document.getElementById('approveAjusteToggle').checked) {
                payload.intensidad_validada = document.getElementById('approveIntensidadValidada').value;
                payload.ajuste_comentario = document.getElementById('approveComentario').value.trim();
            }
            validarRapido(pendingValidation.id, pendingValidation.action, pendingValidation.inundacionId, payload);
            closeApproveModal();
            closeReportDetailModal();
        }



        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            img.src = src;
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 10);
        }

        let reportDetailCurrentData = null;

        function openReportDetailModal(btn) {
            const row = btn.closest('tr');
            const vincularBtn = row?.querySelector('.report-vincular-btn[data-report]');
            const reportRaw = vincularBtn?.getAttribute('data-report') ?? null;

            reportDetailCurrentData = {
                id: parseInt(btn.dataset.id, 10),
                reportRaw,
                hasCercanas: btn.dataset.hasCercanas === '1',
                soloVincular: btn.dataset.soloVincular === '1',
            };

            document.getElementById('reportDetailId').textContent = 'N°' + btn.dataset.id;
            document.getElementById('reportDetailReporter').textContent = btn.dataset.reporter;
            document.getElementById('reportDetailDate').textContent = btn.dataset.date;
            document.getElementById('reportDetailDescription').textContent = btn.dataset.description;
            document.getElementById('reportDetailAddress').textContent = btn.dataset.address;

            const intensityEl = document.getElementById('reportDetailIntensity');
            const intensity = btn.dataset.intensity || 'baja';
            intensityEl.textContent = intensity.charAt(0).toUpperCase() + intensity.slice(1);
            intensityEl.className = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide mt-0.5 intensity-pill-' + (intensity === 'alta' ? 'alta' : intensity === 'media' ? 'media' : 'baja');

            const btnVincular = document.getElementById('reportDetailBtnVincular');
            const btnAprobar = document.querySelector('#reportDetailModal [onclick="reportDetailAprobar()"]');

            if (reportDetailCurrentData.hasCercanas && reportRaw) {
                btnVincular.classList.remove('hidden');
                btnVincular.disabled = false;
                btnVincular.title = '';
            } else {
                btnVincular.classList.add('hidden');
            }

            if (btnAprobar) {
                if (reportDetailCurrentData.soloVincular) {
                    btnAprobar.classList.add('hidden');
                } else {
                    btnAprobar.classList.remove('hidden');
                }
            }

            const mapEl = document.getElementById('report-detail-minimap');
            const mapCoords = {
                latGps: btn.dataset.latGps,
                lngGps: btn.dataset.lngGps,
                latRep: btn.dataset.latRep,
                lngRep: btn.dataset.lngRep,
            };

            const modal = document.getElementById('reportDetailModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                if (mapEl) {
                    try {
                        destroyReportMinimap('report-detail-minimap');
                        initReportLocationMinimap(mapEl, mapCoords);
                    } catch (err) {
                        console.warn('Minimapa del modal no pudo inicializarse:', err);
                    }
                }
            }, 50);
        }

        function closeReportDetailModal() {
            try {
                destroyReportMinimap('report-detail-minimap');
            } catch (err) {
                console.warn('No se pudo destruir minimapa del modal:', err);
            }
            reportDetailCurrentData = null;

            const modal = document.getElementById('reportDetailModal');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function reportDetailAprobar() {
            if (!reportDetailCurrentData) return;
            const intensity = document.getElementById('reportDetailIntensity').textContent.toLowerCase();
            openApproveModal(reportDetailCurrentData.id, 'crear', intensity);
        }

        function reportDetailRechazar() {
            if (!reportDetailCurrentData) return;
            openRejectModal(reportDetailCurrentData.id, document.getElementById('reportDetailReporter').textContent, null, null, 1, 0, 0);
            closeReportDetailModal();
        }

        function reportDetailVincular() {
            if (!reportDetailCurrentData?.hasCercanas || !reportDetailCurrentData?.reportRaw) return;
            const fakeBtn = document.createElement('button');
            fakeBtn.setAttribute('data-report', reportDetailCurrentData.reportRaw);
            closeReportDetailModal();
            openReviewDrawer(fakeBtn);
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('modalImage').src = '';
            }, 300);
        }
    </script>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 z-[10001] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeRejectModal()">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-bold text-slate-800">Rechazar reporte <span id="rejectReportId"></span></h3>
            </div>
            <div class="p-5 space-y-4">
                <div id="rejectContext" class="text-xs text-slate-600 space-y-1 bg-slate-50 rounded-lg p-3"></div>
                <div>
                    <label class="report-field-label">Motivo de rechazo</label>
                    <select id="rejectMotivoCodigo" class="w-full mt-1 rounded border-slate-200 text-sm"></select>
                </div>
                <div>
                    <label class="report-field-label">Nota (si aplica)</label>
                    <textarea id="rejectMotivoTexto" rows="3" class="w-full mt-1 rounded border-slate-200 text-sm" placeholder="Detalle adicional"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRejectModal()" class="flex-1 px-3 py-2 text-sm rounded-lg border border-slate-200 text-slate-600">Cancelar</button>
                    <button type="button" onclick="confirmRejectModal()" class="flex-1 px-3 py-2 text-sm rounded-lg btn-report-rechazar font-bold">Confirmar rechazo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="fixed inset-0 z-[10001] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeApproveModal()">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-bold text-slate-800">Aprobar reporte <span id="approveReportId"></span></h3>
            </div>
            <div class="p-5 space-y-4 report-validation-form">
                <div>
                    <span class="report-field-label mb-1">Intensidad propuesta</span>
                    <p class="mt-1.5">
                        <span id="approveIntensidadPropuesta" class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide intensity-pill-media">Media</span>
                    </p>
                </div>
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" id="approveAjusteToggle" onchange="toggleApproveAjuste()" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0">
                    <span class="text-sm font-medium text-[#1F2937]">Ajustar intensidad validada</span>
                </label>
                <div id="approveAjusteFields" class="hidden space-y-4 pt-1">
                    <div>
                        <label class="report-field-label mb-1" for="approveIntensidadValidada">Intensidad validada</label>
                        <select id="approveIntensidadValidada"></select>
                    </div>
                    <div>
                        <label class="report-field-label mb-1" for="approveComentario">Comentario del ajuste</label>
                        <textarea id="approveComentario" rows="3" placeholder="Explica por qué corriges la intensidad (mín. 10 caracteres)"></textarea>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" onclick="closeApproveModal()" class="flex-1 px-4 py-2.5 text-sm rounded-lg border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors">Cancelar</button>
                    <button type="button" onclick="confirmApproveModal()" class="flex-1 px-4 py-2.5 text-sm rounded-lg btn-report-aprobar font-bold text-white shadow-sm transition-colors">Confirmar aprobación</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 z-[10000] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeImageModal()">
        <div class="relative max-w-5xl w-full max-h-[90vh] flex flex-col items-center justify-center" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-12 right-0 sm:-right-12 sm:top-0 text-white hover:text-rose-400 bg-white/10 hover:bg-white/20 rounded-full p-2 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modalImage" src="" alt="Report Image" class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl border border-white/20">
        </div>
    </div>

    <!-- Report Detail Modal -->
    <div id="reportDetailModal" class="fixed inset-0 z-[10000] hidden bg-slate-900/90 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0" onclick="closeReportDetailModal()">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" onclick="event.stopPropagation()">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-slate-800">Detalle del Reporte <span id="reportDetailId"></span></h3>
                        <div class="mt-2 space-y-1">
                            <div>
                                <span class="report-field-label">Reportado por</span>
                                <p id="reportDetailReporter" class="report-field-value text-slate-700 font-medium"></p>
                            </div>
                            <div>
                                <span class="report-field-label">Fecha</span>
                                <p id="reportDetailDate" class="report-field-value text-slate-500"></p>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="closeReportDetailModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition-colors shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <div class="p-5 space-y-4 max-h-[55vh] overflow-y-auto">
                <div>
                    <span class="report-field-label">Intensidad propuesta</span>
                    <span id="reportDetailIntensity" class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide mt-0.5"></span>
                </div>
                <div>
                    <span class="report-field-label">Descripción</span>
                    <p id="reportDetailDescription" class="report-field-value text-slate-700 whitespace-pre-wrap break-words mt-1"></p>
                </div>
                <div>
                    <span class="report-field-label">Dirección</span>
                    <p id="reportDetailAddress" class="report-field-value text-slate-600 whitespace-pre-wrap break-words mt-1"></p>
                </div>
                <div>
                    <span class="report-field-label mb-1">Ubicación en mapa</span>
                    <div
                        id="report-detail-minimap"
                        class="report-detail-minimap"
                        aria-label="Mapa de ubicación del reporte"
                    ></div>
                    <p class="text-[9px] text-slate-400 mt-1"><span class="text-blue-500">●</span> Usuario <span class="text-rose-500 ml-1">●</span> Evento</p>
                </div>
            </div>
            <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex gap-2">
                <button type="button" onclick="reportDetailAprobar()" class="flex-1 inline-flex items-center justify-center btn-report-aprobar px-3 py-2.5 text-xs rounded-lg font-bold shadow-sm transition-colors">
                    Aprobar
                </button>
                <button type="button" id="reportDetailBtnVincular" onclick="reportDetailVincular()" class="hidden flex-1 inline-flex items-center justify-center btn-report-vincular px-3 py-2.5 text-xs rounded-lg font-bold shadow-sm transition-colors">
                    Vincular
                </button>
                <button type="button" onclick="reportDetailRechazar()" class="flex-1 inline-flex items-center justify-center btn-report-rechazar px-3 py-2.5 text-xs rounded-lg font-bold shadow-sm transition-colors">
                    Rechazar
                </button>
            </div>
        </div>
    </div>

    {{-- Drawer de Revisión de Reporte --}}
    <div id="review-drawer-backdrop" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[2500] hidden transition-opacity duration-300 opacity-0" onclick="closeReviewDrawer()"></div>
    <div id="review-drawer" class="fixed inset-y-0 right-0 w-full max-w-xl bg-white shadow-[0_0_40px_rgba(0,0,0,0.5)] z-[2501] flex flex-col translate-x-full transition-transform duration-300 ease-in-out border-l border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-white">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2" id="drawer-title">
                Revisar Reporte
            </h3>
            <button type="button" onclick="closeReviewDrawer()" class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-100 hover:bg-gray-200 p-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white flex flex-col">
            <!-- Mapa de revisión -->
            <div class="w-full h-[350px] relative border-b border-gray-200 shrink-0" wire:ignore>
                <div id="review-map" class="w-full h-full z-0"></div>
            </div>

            <div class="p-6">
                <!-- Info del Reporte -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Información Reportada</span>
                            <h4 class="font-bold text-slate-800" id="drawer-desc">Descripción del problema</h4>
                            <p class="text-xs text-slate-500 mt-1" id="drawer-address">Dirección</p>
                        </div>
                        <span id="drawer-intensity" class="px-2 py-1 rounded text-[10px] font-bold uppercase">INTENSIDAD</span>
                    </div>
                </div>

                <!-- Inundaciones Cercanas Sugeridas -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Inundaciones Cercanas (Sugeridas para Vincular)</h4>
                    <div id="drawer-cercanas-list" class="space-y-3">
                        <!-- Cards dinámicas -->
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white border-t border-slate-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <button id="btn-vincular" disabled class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white py-3 rounded-lg text-sm font-bold shadow transition-colors flex justify-center items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                Vincular a Inundación Seleccionada
            </button>
        </div>
    </div>

    <script>
        let reviewDrawerReportId = null;
        let reviewMap = null;
        let reviewSelectedFloodId = null;
        let reviewPolygons = {};

        function openReviewDrawer(btnElement) {
            const reportData = JSON.parse(btnElement.getAttribute('data-report'));
            const id = reportData.id;
            reviewDrawerReportId = id;
            reviewSelectedFloodId = null;

            document.getElementById('drawer-title').innerText = 'Opciones de Vinculación - Reporte N°' + id;
            document.getElementById('drawer-desc').innerText = reportData.description || 'Sin descripción detallada';
            document.getElementById('drawer-address').innerText = reportData.address || 'Ubicación GPS generada';

            let intensityColor = 'bg-slate-100 text-slate-700';
            if (reportData.intensidad_propuesta === 'alta') intensityColor = 'bg-rose-100 text-rose-700';
            else if (reportData.intensidad_propuesta === 'media') intensityColor = 'bg-amber-100 text-amber-700';
            else if (reportData.intensidad_propuesta === 'baja') intensityColor = 'bg-teal-100 text-teal-700';

            const intBadge = document.getElementById('drawer-intensity');
            intBadge.className = `px-2 py-1 rounded text-[10px] font-bold uppercase ${intensityColor}`;
            intBadge.innerText = reportData.intensidad_propuesta || 'N/A';

            document.getElementById('review-drawer-backdrop').classList.remove('hidden');
            requestAnimationFrame(() => {
                document.getElementById('review-drawer-backdrop').classList.remove('opacity-0');
                document.getElementById('review-drawer').classList.remove('translate-x-full');

                setTimeout(() => {
                    initReviewMap(reportData);
                }, 350); // esperar la animación
            });

            const listContainer = document.getElementById('drawer-cercanas-list');
            listContainer.innerHTML = '';

            if (reportData.cercanas && reportData.cercanas.length > 0) {
                reportData.cercanas.forEach(flood => {
                    const card = document.createElement('div');
                    card.id = `flood-card-${flood.id}`;
                    card.className = `p-3 rounded-xl border-2 border-slate-200 bg-white shadow-sm cursor-pointer hover:border-blue-300 transition-all flex justify-between items-center`;
                    const distLabel = flood.distancia_metros != null ? Math.round(flood.distancia_metros) + ' m' : '';
                    const contornoBadge = flood.dentro_contorno
                        ? '<span class="text-[9px] font-bold uppercase tracking-wide text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded ml-1">Dentro del contorno</span>'
                        : '';
                    card.innerHTML = `
                        <div>
                            <span class="text-xs font-bold text-slate-800">Inundación N°${flood.id}</span>${contornoBadge}
                            <p class="text-[10px] text-slate-500 mt-1">Intensidad ${flood.intensidad_calculada || flood.intensidad || 'N/A'}${distLabel ? ' · ' + distLabel : ''}</p>
                        </div>
                    `;

                    card.onclick = () => selectFloodToLink(flood.id);
                    card.onmouseenter = () => highlightFloodPolygon(flood.id, true);
                    card.onmouseleave = () => highlightFloodPolygon(flood.id, false);

                    listContainer.appendChild(card);
                });
            } else {
                listContainer.innerHTML = '<p class="text-xs text-slate-400 italic bg-slate-100 p-4 rounded-lg text-center">No se detectaron inundaciones activas cercanas.</p>';
            }

            document.getElementById('btn-vincular').onclick = () => {
                if (reviewSelectedFloodId) {
                    closeReviewDrawer();
                    openApproveModal(id, 'vincular', reportData.intensidad_propuesta || 'media', reviewSelectedFloodId);
                }
            };

            updateVincularButton();
        }

        function closeReviewDrawer() {
            document.getElementById('review-drawer-backdrop').classList.add('opacity-0');
            document.getElementById('review-drawer').classList.add('translate-x-full');
            setTimeout(() => document.getElementById('review-drawer-backdrop').classList.add('hidden'), 300);
        }

        function initReviewMap(report) {
            // Reconstruimos el mapa en cada apertura para evitar estados obsoletos:
            // Livewire puede morfar el DOM entre aperturas y dejar el mapa "muerto"
            // (desconectado de la vista), lo que impedía dibujar los polígonos.
            if (reviewMap) {
                reviewMap.remove();
                reviewMap = null;
            }
            reviewPolygons = {};

            reviewMap = L.map('review-map', { zoomControl: false }).setView([-17.7833, -63.1821], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(reviewMap);
            L.control.zoom({ position: 'topright' }).addTo(reviewMap);

            const latRep = parseFloat(report.lat_reporte);
            const lngRep = parseFloat(report.long_reporte);
            const latUser = parseFloat(report.lat_gps);
            const lngUser = parseFloat(report.long_gps);

            const bounds = L.latLngBounds();
            let addedUser = false;
            let addedEvent = false;

            if (!isNaN(latUser) && !isNaN(lngUser)) {
                const userIcon = L.divIcon({
                    className: 'custom-leaflet-user',
                    html: `<div style="background-color:#3b82f6;width:12px;height:12px;border-radius:50%;border:2px solid white;box-shadow:0 0 5px rgba(0,0,0,0.4);"></div>`,
                    iconSize: [12, 12], iconAnchor: [6, 6]
                });
                L.marker([latUser, lngUser], { icon: userIcon }).bindTooltip("Ubicación del Reportero (GPS)").addTo(reviewMap);
                bounds.extend([latUser, lngUser]);
                addedUser = true;
            }

            if (!isNaN(latRep) && !isNaN(lngRep)) {
                const eventIcon = L.divIcon({
                    className: 'custom-leaflet-event',
                    html: `<div style="background-color:#f43f5e;width:16px;height:16px;border-radius:50%;border:2px solid white;box-shadow:0 0 10px rgba(0,0,0,0.5);animation:pulse 2s infinite;"></div>`,
                    iconSize: [16, 16], iconAnchor: [8, 8]
                });
                L.marker([latRep, lngRep], { icon: eventIcon }).bindTooltip("Punto de Evento Reportado", { permanent: true, direction: "top", className: "text-xs font-bold" }).addTo(reviewMap);
                bounds.extend([latRep, lngRep]);
                addedEvent = true;
            }

            if (addedUser && addedEvent && (latUser !== latRep || lngUser !== lngRep)) {
                L.polyline([[latUser, lngUser], [latRep, lngRep]], {
                    color: '#94a3b8', weight: 2, dashArray: '4, 4'
                }).addTo(reviewMap);

                const dist = reviewMap.distance([latUser, lngUser], [latRep, lngRep]);
                L.marker([(latUser + latRep) / 2, (lngUser + lngRep) / 2], {
                    icon: L.divIcon({
                        className: 'dist-label',
                        html: `<div style="background:rgba(255,255,255,0.9);color:#64748b;font-size:9px;font-weight:bold;padding:1px 4px;border-radius:4px;border:1px solid #cbd5e1;box-shadow:0 1px 2px rgba(0,0,0,0.1);">${Math.round(dist)}m</div>`,
                        iconSize: [40, 16], iconAnchor: [20, 8]
                    })
                }).addTo(reviewMap);
            }

            if (report.cercanas && report.cercanas.length > 0) {
                const baseStyle = { color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2, dashArray: '5,5' };

                report.cercanas.forEach(flood => {
                    const latC = parseFloat(flood.latitud);
                    const lngC = parseFloat(flood.longitud);

                    let floodGeom = null;
                    if (Array.isArray(window.floodReports)) {
                        floodGeom = window.floodReports.find(f => String(f.id) === String(flood.id));
                    }

                    let ring = null;
                    if (floodGeom && window.resolveUnifiedHeatRing) {
                        ring = window.resolveUnifiedHeatRing(floodGeom);
                    }
                    if (!ring && floodGeom && window.computeInundacionSelectionOutline) {
                        ring = window.computeInundacionSelectionOutline(floodGeom);
                    }
                    if (!ring && floodGeom && window.normalizePolygonRings) {
                        const rings = window.normalizePolygonRings(floodGeom.polygon_coords);
                        if (rings.length === 1) {
                            ring = rings[0];
                        }
                    }

                    let shape = null;
                    if (ring && ring.length >= 3) {
                        shape = L.polygon(ring, baseStyle);
                    } else if (!isNaN(latC) && !isNaN(lngC)) {
                        shape = L.circle([latC, lngC], { radius: 150, ...baseStyle });
                    }

                    if (!shape) return;

                    shape.addTo(reviewMap);
                    shape.on('click', () => selectFloodToLink(flood.id));
                    shape.bindTooltip(`Inundación N°${flood.id}`, { className: 'text-xs font-bold' });

                    reviewPolygons[flood.id] = shape;
                    bounds.extend(shape.getBounds());
                });
            }

            if (bounds.isValid()) {
                reviewMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
            }

            setTimeout(() => {
                reviewMap.invalidateSize();
                if (bounds.isValid()) {
                    reviewMap.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
                }
                const dentroContorno = (report.cercanas || []).filter(function (f) { return f.dentro_contorno; });
                if (dentroContorno.length === 1) {
                    selectFloodToLink(dentroContorno[0].id);
                }
            }, 100);
        }

        function highlightFloodPolygon(floodId, isHover) {
            if (floodId === reviewSelectedFloodId) return;
            const poly = reviewPolygons[floodId];
            if (poly) {
                if (isHover) {
                    poly.setStyle({ color: '#60a5fa', fillColor: '#93c5fd', fillOpacity: 0.4, weight: 2 });
                } else {
                    poly.setStyle({ color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2 });
                }
            }
        }

        function selectFloodToLink(floodId) {
            // Reset de TODOS los polígonos dibujados al estilo base.
            Object.keys(reviewPolygons).forEach(idKey => {
                reviewPolygons[idKey].setStyle({ color: '#cbd5e1', fillColor: '#94a3b8', fillOpacity: 0.2, weight: 2 });
            });

            // Reset de TODAS las tarjetas recorriendo el DOM (no reviewPolygons),
            // así se limpian aunque una inundación no tenga polígono en el mapa.
            document.querySelectorAll('#drawer-cercanas-list [id^="flood-card-"]').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-slate-200', 'bg-white');
            });

            reviewSelectedFloodId = floodId;
            if (reviewPolygons[floodId]) {
                reviewPolygons[floodId].setStyle({ color: '#2563eb', fillColor: '#3b82f6', fillOpacity: 0.5, weight: 3 });
            }
            const selectedCard = document.getElementById(`flood-card-${floodId}`);
            if (selectedCard) {
                selectedCard.classList.remove('border-slate-200', 'bg-white');
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
            }

            updateVincularButton();
        }

        function updateVincularButton() {
            const btn = document.getElementById('btn-vincular');
            btn.disabled = !reviewSelectedFloodId;
        }

        // Envolvente convexa (monotone chain) para puntos [lat, lng].
        // Tratamos lng como X y lat como Y. Devuelve el anillo del casco.
        function convexHullLatLng(points) {
            if (!Array.isArray(points) || points.length < 3) return points;
            const pts = points.slice().sort((a, b) => (a[1] - b[1]) || (a[0] - b[0]));
            const cross = (o, a, b) => (a[1] - o[1]) * (b[0] - o[0]) - (a[0] - o[0]) * (b[1] - o[1]);

            const lower = [];
            for (const p of pts) {
                while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) lower.pop();
                lower.push(p);
            }
            const upper = [];
            for (let i = pts.length - 1; i >= 0; i--) {
                const p = pts[i];
                while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) upper.pop();
                upper.push(p);
            }
            lower.pop();
            upper.pop();
            return lower.concat(upper);
        }
    </script>
</div>
