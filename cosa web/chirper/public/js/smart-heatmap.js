/**
 * Smart Heatmap - Lógica compartida para mapas de calor
 * Renderiza dinámicamente un mapa de calor adaptativo basado en reportes de inundación.
 */
window.createSmartHeatmap = function(map, reports, options = {}) {
    if (!map || !window.L || !window.L.heatLayer) return null;

    const heatPoints = [];

    // Normalizar la estructura de los reportes (algunos módulos usan lat/lng, otros lat_reporte/long_reporte)
    let parsedReports = reports.map(r => {
        return {
            lat: parseFloat(r.lat || r.lat_reporte || r.latitud),
            lng: parseFloat(r.lng || r.long_reporte || r.longitud),
            intensity: r.intensidad || r.intensidad_propuesta || r.intensidad_calculada || 'baja'
        };
    }).filter(r => !isNaN(r.lat) && !isNaN(r.lng));

    // Agregar puntos principales
    parsedReports.forEach(rep => {
        let weight = 0.3; // baja por defecto
        if (rep.intensity === 'alta') weight = 1.0;
        else if (rep.intensity === 'media') weight = 0.6;
        
        heatPoints.push([rep.lat, rep.lng, weight]);
    });

    // --- LÓGICA DE PUENTES TÉRMICOS Y TOPOGRÁFICOS ---
    if (parsedReports.length > 1) {
        for (let i = 0; i < parsedReports.length; i++) {
            for (let j = i + 1; j < parsedReports.length; j++) {
                let p1 = L.latLng(parsedReports[i].lat, parsedReports[i].lng);
                let p2 = L.latLng(parsedReports[j].lat, parsedReports[j].lng);
                let dist = p1.distanceTo(p2);
                
                // Si están a menos de 250 metros, se conectan con manchas
                if (dist > 10 && dist <= 250) {
                    let steps = Math.floor(dist / 5);
                    for (let k = 1; k < steps; k++) {
                        let fraction = k / steps;
                        let interLat = p1.lat + (p2.lat - p1.lat) * fraction;
                        let interLng = p1.lng + (p2.lng - p1.lng) * fraction;
                        heatPoints.push([interLat, interLng, 0.35]);
                    }
                }
            }
        }
    }

    if (heatPoints.length === 0) return null;

    // Calcular radio inicial según el zoom del mapa
    let initialZoom = map.getZoom();
    let initialRadius = Math.max(12, Math.round(35 * Math.pow(1.5, initialZoom - 16)));
    let initialBlur = Math.max(10, Math.round(initialRadius * 0.8));

    const heatLayer = L.heatLayer(heatPoints, Object.assign({
        radius: initialRadius,
        blur: initialBlur,
        minOpacity: 0.5,
        maxZoom: 18,
        gradient: {
            0.2: '#38bdf8', // Bordes (Agua somera)
            0.5: '#2563eb', // Zonas intermedias
            1.0: '#1e3a8a'  // Epicentro profundo
        }
    }, options.heatOptions || {}));

    if (options.targetLayer) {
        options.targetLayer.addLayer(heatLayer);
    } else {
        heatLayer.addTo(map);
    }

    // Escalar dinámicamente cuando el usuario cambia el zoom
    const zoomListener = function() {
        let zoom = map.getZoom();
        let newRadius = Math.max(12, Math.round(35 * Math.pow(1.5, zoom - 16)));
        let newBlur = Math.max(10, Math.round(newRadius * 0.8));
        heatLayer.setOptions({ radius: newRadius, blur: newBlur });
    };

    map.on('zoomend', zoomListener);

    return {
        layer: heatLayer,
        remove: function() {
            if (options.targetLayer) {
                options.targetLayer.removeLayer(heatLayer);
            } else {
                map.removeLayer(heatLayer);
            }
            map.off('zoomend', zoomListener);
        }
    };
};
