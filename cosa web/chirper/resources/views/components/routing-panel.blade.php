<div id="routing-panel" class="absolute top-32 left-4 pointer-events-auto bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-2xl border border-gray-100 z-[1000] w-80 transition-all duration-300">
    <div id="routing-panel-header" class="flex justify-between items-center mb-3 cursor-move" title="Arrastrar panel">
        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Rutas Seguras
        </h3>
        <button id="toggle-routing-panel" type="button" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    {{-- Barra de progreso (visible en pasos del wizard) --}}
    <div id="routing-progress" class="hidden mb-4">
        <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 uppercase mb-1.5">
            <span id="routing-progress-label">Paso 1 de 4</span>
            <span id="routing-progress-percent">25%</span>
        </div>
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div id="routing-progress-bar" class="h-full bg-emerald-500 rounded-full transition-all duration-300" style="width: 25%"></div>
        </div>
    </div>

    {{-- ── IDLE ── --}}
    <div id="routing-step-idle" class="routing-step">
        <p class="text-xs text-gray-600 mb-3 leading-relaxed">Evita zonas inundadas al planificar tu recorrido.</p>
        <div id="routing-flood-badge" class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
            <span id="routing-flood-count">0 inundaciones activas en el mapa</span>
        </div>
        <button id="btn-start-safe-route" type="button" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
            Comenzar ruta más segura
        </button>
        <button id="btn-toggle-how-it-works" type="button" class="w-full text-xs text-indigo-600 hover:text-indigo-800 font-semibold py-1 transition-colors">
            ¿Cómo funciona?
        </button>
        <ul id="routing-how-it-works" class="hidden mt-3 space-y-1.5 text-[11px] text-gray-600 list-disc list-inside leading-relaxed">
            <li>Evita polígonos de zonas inundadas reportadas.</li>
            <li>Recalcula al cambiar el modo de transporte.</li>
            <li>Si no hay ruta 100% segura, se muestra advertencia.</li>
        </ul>
    </div>

    {{-- ── TRANSPORT (Paso 1) ── --}}
    <div id="routing-step-transport" class="routing-step hidden">
        <h4 class="text-sm font-bold text-gray-800 mb-1">¿Cómo te moverás?</h4>
        <p id="routing-transport-hint" class="text-[11px] text-gray-500 mb-3">Usa calles; evita polígonos inundados.</p>
        <div class="flex bg-gray-100 p-1 rounded-lg mb-4">
            <button id="mode-car" type="button" data-mode="driving-car" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-emerald-700 bg-white shadow-sm transition-all" title="Auto">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M199.2 181.4L173.1 256L466.9 256L440.8 181.4C436.3 168.6 424.2 160 410.6 160L229.4 160C215.8 160 203.7 168.6 199.2 181.4zM103.6 260.8L138.8 160.3C152.3 121.8 188.6 96 229.4 96L410.6 96C451.4 96 487.7 121.8 501.2 160.3L536.4 260.8C559.6 270.4 576 293.3 576 320L576 512C576 529.7 561.7 544 544 544L512 544C494.3 544 480 529.7 480 512L480 480L160 480L160 512C160 529.7 145.7 544 128 544L96 544C78.3 544 64 529.7 64 512L64 320C64 293.3 80.4 270.4 103.6 260.8zM192 368C192 350.3 177.7 336 160 336C142.3 336 128 350.3 128 368C128 385.7 142.3 400 160 400C177.7 400 192 385.7 192 368zM480 400C497.7 400 512 385.7 512 368C512 350.3 497.7 336 480 336C462.3 336 448 350.3 448 368C448 385.7 462.3 400 480 400z"/></svg>
            </button>
            <button id="mode-bike" type="button" data-mode="cycling-regular" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="Bicicleta">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M331.7 107.3C336 100.3 343.7 96 352 96L456 96C469.3 96 480 106.7 480 120C480 133.3 469.3 144 456 144L390.4 144L462.6 292.4C473.3 289.5 484.5 288 496 288C566.7 288 624 345.3 624 416C624 486.7 566.7 544 496 544C425.3 544 368 486.7 368 416C368 374 388.2 336.8 419.4 313.4L399 271.5L325.5 418.5C323.2 423.3 319.2 427.3 314.1 429.7C313.5 430 312.9 430.2 312.3 430.4C309.4 431.5 306.4 432 303.4 431.9L271 432C263.1 495.1 209.3 544 144 544C73.3 544 16 486.7 16 416C16 345.3 73.3 288 144 288C154.8 288 165.2 289.3 175.2 291.8L203.7 234.9L192.2 208L152 208C138.7 208 128 197.3 128 184C128 170.7 138.7 160 152 160L208 160C217.6 160 226.3 165.7 230.1 174.5L244.4 208L368.1 208L330.4 130.5C326.8 123.1 327.2 114.3 331.6 107.3zM228.5 292.7L182.9 384L267.7 384L228.6 292.7zM305.7 351L353.2 256L265 256L305.7 351zM474.4 426.5L444.7 365.5C431.9 378.5 424 396.3 424 416C424 455.8 456.2 488 496 488C535.8 488 568 455.8 568 416C568 376.2 535.8 344 496 344C493.3 344 490.5 344.2 487.9 344.5L517.6 405.5C523.4 417.4 518.4 431.8 506.5 437.6C494.6 443.4 480.2 438.4 474.4 426.5zM149.2 432C129 432 115.8 410.7 124.9 392.6L149.1 344.1C147.4 344 145.7 343.9 144 343.9C104.2 343.9 72 376.1 72 415.9C72 455.7 104.2 487.9 144 487.9C178.3 487.9 206.9 464 214.2 431.9L149.2 431.9z"/></svg>
            </button>
            <button id="mode-foot" type="button" data-mode="foot-walking" class="transport-btn flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="A pie">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M320 144C350.9 144 376 118.9 376 88C376 57.1 350.9 32 320 32C289.1 32 264 57.1 264 88C264 118.9 289.1 144 320 144zM233.4 291.9L256 269.3L256 338.6C256 366.6 268.2 393.3 289.5 411.5L360.9 472.7C366.8 477.8 370.7 484.8 371.8 492.5L384.4 580.6C386.9 598.1 403.1 610.3 420.6 607.8C438.1 605.3 450.3 589.1 447.8 571.6L435.2 483.5C431.9 460.4 420.3 439.4 402.6 424.2L368.1 394.6L368.1 279.4L371.9 284.1C390.1 306.9 417.7 320.1 446.9 320.1L480.1 320.1C497.8 320.1 512.1 305.8 512.1 288.1C512.1 270.4 497.8 256.1 480.1 256.1L446.9 256.1C437.2 256.1 428 251.7 421.9 244.1L404 221.7C381 192.9 346.1 176.1 309.2 176.1C277 176.1 246.1 188.9 223.4 211.7L188.1 246.6C170.1 264.6 160 289 160 314.5L160 352C160 369.7 174.3 384 192 384C209.7 384 224 369.7 224 352L224 314.5C224 306 227.4 297.9 233.4 291.9zM245.8 471.3C244.3 476.5 241.5 481.3 237.7 485.1L169.4 553.4C156.9 565.9 156.9 586.2 169.4 598.7C181.9 611.2 202.2 611.2 214.7 598.7L283 530.4C294.5 518.9 302.9 504.6 307.4 488.9L309.6 481.3L263.6 441.9C261.1 439.7 258.6 437.5 256.2 435.1L245.8 471.3z"/></svg>
            </button>
        </div>
        <div class="flex gap-2">
            <button id="btn-transport-cancel" type="button" class="flex-1 text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 font-bold py-2 rounded-xl transition-colors">Cancelar</button>
            <button id="btn-transport-continue" type="button" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition-colors">Continuar</button>
        </div>
    </div>

    {{-- ── PICK ORIGIN (Paso 2) ── --}}
    <div id="routing-step-pick-origin" class="routing-step hidden">
        <h4 class="text-sm font-bold text-gray-800 mb-3">¿Dónde estás ahora?</h4>
        <div class="space-y-2 mb-4">
            <button id="btn-origin-gps" type="button" class="w-full flex items-center gap-3 p-3 rounded-xl border-2 border-blue-100 bg-blue-50/50 hover:bg-blue-50 hover:border-blue-200 transition-all text-left">
                <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </span>
                <span>
                    <span class="block text-xs font-bold text-gray-800">Mi ubicación actual</span>
                    <span id="btn-origin-gps-sub" class="block text-[10px] text-gray-500">Usar GPS del dispositivo</span>
                </span>
            </button>
            <button id="btn-origin-map" type="button" class="w-full flex items-center gap-3 p-3 rounded-xl border-2 border-emerald-100 bg-emerald-50/50 hover:bg-emerald-50 hover:border-emerald-200 transition-all text-left">
                <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                </span>
                <span>
                    <span class="block text-xs font-bold text-gray-800">Elegir en el mapa</span>
                    <span class="block text-[10px] text-gray-500">Marca tu punto de partida</span>
                </span>
            </button>
        </div>
        <p id="routing-gps-error" class="hidden text-[11px] text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2 mb-3"></p>
        <button id="btn-pick-origin-back" type="button" class="w-full text-xs text-gray-600 hover:text-gray-800 font-semibold py-1.5 transition-colors">← Atrás</button>
    </div>

    {{-- ── PICK ORIGIN MAP (Paso 2b) ── --}}
    <div id="routing-step-pick-origin-map" class="routing-step hidden">
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-3 py-2.5 mb-3">
            <p class="text-xs font-bold text-emerald-800">Haz clic en el mapa para marcar tu <span class="underline">punto de partida</span></p>
        </div>
        <div class="mb-3">
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coordenadas</label>
            <div class="flex gap-1.5 items-center">
                <input type="text" id="route-start-input" readonly placeholder="Esperando clic…" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-700 cursor-default">
                <button id="btn-focus-start" type="button" title="Centrar mapa en origen" class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors border border-transparent hover:border-blue-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </button>
                <button id="btn-clear-start" type="button" title="Borrar origen" class="text-red-500 hover:bg-red-100 p-2 rounded-lg transition-colors border border-transparent hover:border-red-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="btn-origin-map-back" type="button" class="flex-1 text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 font-bold py-2 rounded-xl transition-colors">Atrás</button>
            <button id="btn-origin-map-confirm" type="button" disabled class="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2 rounded-xl text-xs transition-colors">Confirmar y continuar</button>
        </div>
    </div>

    {{-- ── PICK DEST (Paso 3) ── --}}
    <div id="routing-step-pick-dest" class="routing-step hidden">
        <h4 class="text-sm font-bold text-gray-800 mb-3">¿A dónde quieres ir?</h4>
        <div class="mb-3 p-2.5 rounded-lg bg-emerald-50 border border-emerald-100">
            <span class="block text-[10px] font-bold text-emerald-700 uppercase mb-0.5">Origen</span>
            <span id="routing-origin-summary" class="text-xs font-medium text-gray-800">—</span>
        </div>
        <button id="btn-change-origin" type="button" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold mb-3 block">Cambiar origen</button>
        <button id="btn-dest-map" type="button" class="w-full flex items-center gap-3 p-3 rounded-xl border-2 border-red-100 bg-red-50/50 hover:bg-red-50 hover:border-red-200 transition-all text-left mb-3">
            <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </span>
            <span>
                <span class="block text-xs font-bold text-gray-800">Elegir destino en el mapa</span>
                <span class="block text-[10px] text-gray-500">Marca dónde quieres llegar</span>
            </span>
        </button>
        <details class="mb-2">
            <summary class="text-[11px] text-gray-500 cursor-pointer hover:text-gray-700 font-medium">Ingresar coordenadas manualmente</summary>
            <div class="mt-2">
                <input type="text" id="route-end-input-manual" placeholder="Lat, Lng" class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-700 focus:ring-2 focus:ring-emerald-500">
                <button id="btn-dest-manual-apply" type="button" class="mt-2 w-full text-xs bg-gray-100 hover:bg-gray-200 font-bold py-1.5 rounded-lg transition-colors">Aplicar coordenadas</button>
            </div>
        </details>
        <button id="btn-pick-dest-back" type="button" class="w-full text-xs text-gray-600 hover:text-gray-800 font-semibold py-1.5 transition-colors">← Atrás</button>
    </div>

    {{-- ── PICK DEST MAP (Paso 3b) ── --}}
    <div id="routing-step-pick-dest-map" class="routing-step hidden">
        <div class="mb-2 p-2 rounded-lg bg-emerald-50 border border-emerald-100">
            <span class="block text-[10px] font-bold text-emerald-700 uppercase">Origen</span>
            <span id="routing-origin-summary-map" class="text-xs text-gray-800">—</span>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-xl px-3 py-2.5 mb-3">
            <p class="text-xs font-bold text-red-800">Haz clic en el mapa para marcar tu <span class="underline">destino</span></p>
        </div>
        <div class="mb-3">
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Coordenadas destino</label>
            <div class="flex gap-1.5 items-center">
                <input type="text" id="route-end-input" readonly placeholder="Esperando clic…" class="w-full text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-gray-700 cursor-default">
                <button id="btn-focus-end" type="button" title="Centrar mapa en destino" class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors border border-transparent hover:border-blue-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </button>
                <button id="btn-clear-end" type="button" title="Borrar destino" class="text-red-500 hover:bg-red-100 p-2 rounded-lg transition-colors border border-transparent hover:border-red-200 hidden shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="btn-dest-map-back" type="button" class="flex-1 text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 font-bold py-2 rounded-xl transition-colors">Atrás</button>
            <button id="btn-dest-map-confirm" type="button" disabled class="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold py-2 rounded-xl text-xs transition-colors">Confirmar y continuar</button>
        </div>
    </div>

    {{-- ── REVIEW (Paso 4) ── --}}
    <div id="routing-step-review" class="routing-step hidden">
        <h4 class="text-sm font-bold text-gray-800 mb-3">Revisa tu ruta</h4>
        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 mb-3 space-y-2">
            <div class="flex items-start gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                <div>
                    <span class="block text-[10px] font-bold text-gray-500 uppercase">Origen</span>
                    <span id="review-origin-coords" class="text-xs text-gray-800">—</span>
                </div>
            </div>
            <div class="flex items-center gap-2 pl-1">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                <span id="review-straight-distance" class="text-[10px] text-gray-500 italic">—</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 mt-1.5 shrink-0"></span>
                <div>
                    <span class="block text-[10px] font-bold text-gray-500 uppercase">Destino</span>
                    <span id="review-dest-coords" class="text-xs text-gray-800">—</span>
                </div>
            </div>
        </div>
        <div id="review-transport-label" class="text-[11px] text-gray-600 mb-2">Transporte: <span class="font-bold text-gray-800">Auto</span></div>
        <div id="review-hazard-chip" class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-100 px-2 py-1 rounded-full mb-4">
            Evitando 0 zonas de inundación
        </div>
        <button id="btn-calculate-route" type="button" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-xs transition-colors shadow-sm flex items-center justify-center gap-2 mb-2">
            <span>Calcular ruta alterna</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </button>
        <div class="flex justify-between text-[11px]">
            <button id="btn-review-edit-origin" type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold">Editar origen</button>
            <button id="btn-review-edit-dest" type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold">Editar destino</button>
        </div>
        <button id="btn-review-cancel" type="button" class="w-full mt-3 text-xs text-gray-600 hover:text-gray-800 font-semibold py-1.5 transition-colors">Cancelar</button>
    </div>

    {{-- ── RESULTS ── --}}
    <div id="routing-step-results" class="routing-step hidden">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </span>
            <span class="text-sm font-bold text-gray-800">Ruta calculada</span>
        </div>
        <div id="routing-fallback-warning" class="hidden text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3 leading-relaxed">
            No fue posible trazar una ruta 100% segura. Se muestra la ruta directa — tenga extrema precaución al cruzar zonas inundadas.
        </div>
        <div id="route-results" class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-600">Distancia:</span>
                <span id="route-distance" class="text-sm font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded">0 km</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-600">Tiempo est.:</span>
                <span id="route-duration" class="text-sm font-bold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded">0 min</span>
            </div>
        </div>
        <div class="flex bg-gray-100 p-1 rounded-lg mb-3">
            <button id="mode-car-results" type="button" data-mode="driving-car" class="transport-btn-results flex-1 flex justify-center items-center py-1.5 rounded-md text-emerald-700 bg-white shadow-sm transition-all" title="Auto">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M199.2 181.4L173.1 256L466.9 256L440.8 181.4C436.3 168.6 424.2 160 410.6 160L229.4 160C215.8 160 203.7 168.6 199.2 181.4zM103.6 260.8L138.8 160.3C152.3 121.8 188.6 96 229.4 96L410.6 96C451.4 96 487.7 121.8 501.2 160.3L536.4 260.8C559.6 270.4 576 293.3 576 320L576 512C576 529.7 561.7 544 544 544L512 544C494.3 544 480 529.7 480 512L480 480L160 480L160 512C160 529.7 145.7 544 128 544L96 544C78.3 544 64 529.7 64 512L64 320C64 293.3 80.4 270.4 103.6 260.8zM192 368C192 350.3 177.7 336 160 336C142.3 336 128 350.3 128 368C128 385.7 142.3 400 160 400C177.7 400 192 385.7 192 368zM480 400C497.7 400 512 385.7 512 368C512 350.3 497.7 336 480 336C462.3 336 448 350.3 448 368C448 385.7 462.3 400 480 400z"/></svg>
            </button>
            <button id="mode-bike-results" type="button" data-mode="cycling-regular" class="transport-btn-results flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="Bicicleta">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M331.7 107.3C336 100.3 343.7 96 352 96L456 96C469.3 96 480 106.7 480 120C480 133.3 469.3 144 456 144L390.4 144L462.6 292.4C473.3 289.5 484.5 288 496 288C566.7 288 624 345.3 624 416C624 486.7 566.7 544 496 544C425.3 544 368 486.7 368 416C368 374 388.2 336.8 419.4 313.4L399 271.5L325.5 418.5C323.2 423.3 319.2 427.3 314.1 429.7C313.5 430 312.9 430.2 312.3 430.4C309.4 431.5 306.4 432 303.4 431.9L271 432C263.1 495.1 209.3 544 144 544C73.3 544 16 486.7 16 416C16 345.3 73.3 288 144 288C154.8 288 165.2 289.3 175.2 291.8L203.7 234.9L192.2 208L152 208C138.7 208 128 197.3 128 184C128 170.7 138.7 160 152 160L208 160C217.6 160 226.3 165.7 230.1 174.5L244.4 208L368.1 208L330.4 130.5C326.8 123.1 327.2 114.3 331.6 107.3zM228.5 292.7L182.9 384L267.7 384L228.6 292.7zM305.7 351L353.2 256L265 256L305.7 351zM474.4 426.5L444.7 365.5C431.9 378.5 424 396.3 424 416C424 455.8 456.2 488 496 488C535.8 488 568 455.8 568 416C568 376.2 535.8 344 496 344C493.3 344 490.5 344.2 487.9 344.5L517.6 405.5C523.4 417.4 518.4 431.8 506.5 437.6C494.6 443.4 480.2 438.4 474.4 426.5zM149.2 432C129 432 115.8 410.7 124.9 392.6L149.1 344.1C147.4 344 145.7 343.9 144 343.9C104.2 343.9 72 376.1 72 415.9C72 455.7 104.2 487.9 144 487.9C178.3 487.9 206.9 464 214.2 431.9L149.2 431.9z"/></svg>
            </button>
            <button id="mode-foot-results" type="button" data-mode="foot-walking" class="transport-btn-results flex-1 flex justify-center items-center py-1.5 rounded-md text-gray-500 hover:text-gray-700 transition-all" title="A pie">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 640 640"><path d="M320 144C350.9 144 376 118.9 376 88C376 57.1 350.9 32 320 32C289.1 32 264 57.1 264 88C264 118.9 289.1 144 320 144zM233.4 291.9L256 269.3L256 338.6C256 366.6 268.2 393.3 289.5 411.5L360.9 472.7C366.8 477.8 370.7 484.8 371.8 492.5L384.4 580.6C386.9 598.1 403.1 610.3 420.6 607.8C438.1 605.3 450.3 589.1 447.8 571.6L435.2 483.5C431.9 460.4 420.3 439.4 402.6 424.2L368.1 394.6L368.1 279.4L371.9 284.1C390.1 306.9 417.7 320.1 446.9 320.1L480.1 320.1C497.8 320.1 512.1 305.8 512.1 288.1C512.1 270.4 497.8 256.1 480.1 256.1L446.9 256.1C437.2 256.1 428 251.7 421.9 244.1L404 221.7C381 192.9 346.1 176.1 309.2 176.1C277 176.1 246.1 188.9 223.4 211.7L188.1 246.6C170.1 264.6 160 289 160 314.5L160 352C160 369.7 174.3 384 192 384C209.7 384 224 369.7 224 352L224 314.5C224 306 227.4 297.9 233.4 291.9zM245.8 471.3C244.3 476.5 241.5 481.3 237.7 485.1L169.4 553.4C156.9 565.9 156.9 586.2 169.4 598.7C181.9 611.2 202.2 611.2 214.7 598.7L283 530.4C294.5 518.9 302.9 504.6 307.4 488.9L309.6 481.3L263.6 441.9C261.1 439.7 258.6 437.5 256.2 435.1L245.8 471.3z"/></svg>
            </button>
        </div>
        <button id="btn-view-route-map" type="button" class="w-full text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 rounded-xl transition-colors mb-2">Ver ruta en mapa</button>
        <button id="btn-new-route" type="button" class="w-full text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold py-2 rounded-xl transition-colors mb-2">Nueva ruta</button>
        <button id="btn-finish-route" type="button" class="w-full text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 font-bold py-2 rounded-xl transition-colors">Terminar</button>
    </div>

    {{-- Loading overlay --}}
    <div id="routing-loading" class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl flex-col items-center justify-center hidden z-10">
        <svg class="animate-spin h-8 w-8 text-emerald-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-xs font-bold text-emerald-700">Trazando ruta...</span>
    </div>
</div>

<button id="btn-open-routing" type="button" class="hidden absolute top-32 left-4 pointer-events-auto bg-emerald-600 text-white px-4 py-3 rounded-full shadow-xl hover:bg-emerald-700 hover:-translate-y-0.5 transition-all z-[900] flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
    <span class="text-xs font-bold">Rutas seguras</span>
</button>
