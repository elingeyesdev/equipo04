<style>
    body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
    .glass-panel {
        background: rgba(255, 255, 255, 0.65);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
    }
    .glass-panel-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
    }
    .glass-table th { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px); }
    .glass-table tr:hover { background: rgba(255, 255, 255, 0.5); }
    .gradient-text {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .report-location-minimap {
        width: 11rem;
        height: 6.75rem;
        border-radius: 0.75rem;
        overflow: visible;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #f1f5f9;
        position: relative;
        z-index: 0;
        isolation: isolate;
    }
    .report-location-minimap .leaflet-container {
        z-index: 1 !important;
        border-radius: 0.75rem;
        overflow: hidden;
    }
    @media (min-width: 768px) {
        .report-location-minimap {
            width: 12.5rem;
            height: 7.5rem;
        }
    }
    .report-location-minimap--compact {
        height: 5rem;
    }
    @media (min-width: 768px) {
        .report-location-minimap--compact {
            height: 5.5rem;
        }
    }
    .intensity-pill-alta { background: #fef2f2; color: #991b1b; }
    .intensity-pill-media { background: #fffbeb; color: #92400e; }
    .intensity-pill-baja { background: #f0fdfa; color: #115e59; }
    .report-field-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        color: #71717A;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        line-height: 1.2;
    }
    .report-field-value {
        font-size: 0.875rem;
        line-height: 1.4;
        color: #1F2937;
    }
    .report-validation-table thead th {
        vertical-align: top;
        font-size: 0.875rem;
        font-weight: 600;
        color: #1F2937;
        padding: 0.5rem 0.75rem;
        background: transparent;
        backdrop-filter: none;
        text-align: left;
    }
    .report-validation-table tbody td {
        vertical-align: top;
        padding: 0.5rem 0.75rem;
    }
    .report-validation-form select,
    .report-validation-form textarea,
    .report-filter-control {
        font-size: 0.875rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        min-height: 2.5rem;
        color: #1F2937;
        background: #fff;
        width: 100%;
    }
    .report-validation-form textarea {
        min-height: auto;
    }
    .report-validation-form select:focus,
    .report-validation-form textarea:focus,
    .report-filter-control:focus {
        outline: none;
        border-color: #2563EB;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }
    .btn-report-aprobar {
        background-color: #059669;
        color: #FFFFFF;
    }
    .btn-report-aprobar:hover {
        background-color: #047857;
    }
    .btn-report-vincular {
        background-color: #2563EB;
        color: #FFFFFF;
    }
    .btn-report-vincular:hover:not(:disabled) {
        background-color: #1d4ed8;
    }
    .btn-report-vincular:disabled {
        background-color: #2563EB;
        color: #FFFFFF;
        opacity: 0.45;
        cursor: not-allowed;
    }
    .btn-report-rechazar {
        background-color: #F3F4F6;
        color: #DC2626;
    }
    .btn-report-rechazar:hover {
        background-color: #e5e7eb;
    }
    .btn-report-modificar {
        background-color: #EEF2FF;
        color: #4338CA;
        border: 1px solid #C7D2FE;
    }
    .btn-report-modificar:hover {
        background-color: #E0E7FF;
    }
    .report-detail-link {
        color: #4F46E5;
    }
    .report-detail-link:hover {
        color: #4338ca;
    }
    .report-detail-minimap {
        width: 100%;
        height: 12rem;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #f1f5f9;
        position: relative;
        z-index: 0;
        isolation: isolate;
    }
    .report-detail-minimap .leaflet-container {
        z-index: 1 !important;
    }
    .report-filter-bar {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f8fafc;
    }
    .report-filter-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: end;
    }
    @media (min-width: 768px) {
        .report-filter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    @media (min-width: 1024px) {
        .report-filter-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
    .btn-filter-clear {
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #64748b;
        transition: background 0.15s, color 0.15s;
    }
    .btn-filter-clear:hover {
        background: #f1f5f9;
        color: #334155;
    }
</style>
