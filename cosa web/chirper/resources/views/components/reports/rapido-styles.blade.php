<style>
    .hidden { display: none !important; }

    .rapido-body {
        min-height: 100dvh;
        font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        background: #f8fafc;
        color: #0f172a;
    }

    .rapido-header {
        position: sticky;
        top: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 0.75rem 1rem;
    }

    .rapido-brand {
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        font-weight: 700;
        font-size: 1.25rem;
        letter-spacing: -0.025em;
        color: #1e40af;
        text-decoration: none;
    }

    .rapido-brand-accent {
        color: #2563eb;
    }

    .rapido-header-link {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        text-decoration: none;
    }

    .rapido-header-link:hover {
        color: #111827;
    }

    .safe-top {
        padding-top: max(0.75rem, env(safe-area-inset-top));
    }

    .safe-bottom {
        padding-bottom: max(1rem, env(safe-area-inset-bottom));
    }

    .rapido-main {
        max-width: 32rem;
        margin: 0 auto;
        padding: 0 0 1.5rem;
    }

    /* GPS bar */
    .rapido-gps-bar {
        display: flex;
        justify-content: flex-end;
        padding: 0.75rem 1rem 0;
    }

    .rapido-btn-refresh-gps {
        background: none;
        border: none;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #171717;
        cursor: pointer;
        padding: 0.25rem 0;
    }
    .rapido-btn-refresh-gps:hover {
        color: #404040;
    }

    /* Alert banner */
    .rapido-alert-banner {
        background: #dc2626;
        color: #fff;
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-align: center;
    }

    /* Heat legend */
    .rapido-heat-legend {
        position: absolute;
        bottom: 36px;
        left: 10px;
        z-index: 500;
        display: flex;
        gap: 0.375rem;
        pointer-events: none;
    }
    .rapido-heat-legend-item {
        font-size: 0.625rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .rapido-heat-legend-item--baja  { background: #7dd3fc; }
    .rapido-heat-legend-item--media { background: #0ea5e9; }
    .rapido-heat-legend-item--alta  { background: #1e3a8a; }

    /* Map */
    .rapido-map-wrap {
        position: relative;
        height: 45vh;
        min-height: 220px;
        max-height: 420px;
        margin: 0 1rem;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
    }
    #rapidoMap { width: 100%; height: 100%; z-index: 1; }

    .rapido-map-hint {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 500;
        background: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #475569;
        pointer-events: none;
        white-space: nowrap;
        border: 1px solid #e2e8f0;
    }

    /* User pin */
    .rapido-user-pin {
        background: #ea580c !important;
        border: 3px solid #fff !important;
        border-radius: 50% !important;
        width: 18px !important;
        height: 18px !important;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    }

    /* Segmented control — siempre color sólido; anillo negro en seleccionado */
    .rapido-intensity-group {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 0.5rem;
        padding: 0 1rem;
        margin-top: 1rem;
    }

    .rapido-intensity-btn {
        border: 2px solid transparent;
        border-radius: 10px;
        padding: 0.75rem 0.25rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        cursor: pointer;
        text-align: center;
        line-height: 1.2;
        color: #fff;
    }
    .rapido-intensity-btn:active { transform: scale(0.97); }

    .rapido-intensity-btn--baja  { background: #059669; }
    .rapido-intensity-btn--media { background: #d97706; }
    .rapido-intensity-btn--alta  { background: #dc2626; }

    .rapido-intensity-btn.is-active {
        box-shadow: 0 0 0 3px #171717;
    }

    /* Submit */
    .rapido-submit-wrap {
        padding: 1rem;
    }

    .rapido-submit-btn {
        width: 100%;
        border: none;
        border-radius: 12px;
        padding: 1rem;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        transition: opacity 0.2s, transform 0.15s;
    }
    .rapido-submit-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .rapido-submit-btn:not(:disabled):active {
        transform: scale(0.98);
    }

    .rapido-submit-btn--baja  { background: #2563eb; }
    .rapido-submit-btn--media { background: #d97706; }
    .rapido-submit-btn--alta  { background: #dc2626; }

    .rapido-submit-hint {
        text-align: center;
        font-size: 0.6875rem;
        color: #64748b;
        margin-top: 0.5rem;
    }

    /* Success panel */
    .rapido-success-panel {
        margin: 1rem;
        padding: 1.5rem;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 14px;
        text-align: center;
    }
    .rapido-success-panel h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #065f46;
        margin-bottom: 0.5rem;
    }
    .rapido-success-panel p {
        font-size: 0.875rem;
        color: #047857;
        margin-bottom: 0.25rem;
    }
    .rapido-success-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .rapido-success-actions a {
        display: block;
        padding: 0.625rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
    }
    .rapido-link-primary {
        background: #059669;
        color: #fff;
    }
    .rapido-link-secondary {
        background: #fff;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    .rapido-section-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94a3b8;
        padding: 0.75rem 1rem 0.25rem;
    }

    .rapido-distance-warn {
        margin: 0.5rem 1rem 0;
        font-size: 0.8125rem;
        color: #dc2626;
        font-weight: 500;
    }

    .rapido-report-dot {
        width: 8px;
        height: 8px;
        background: #3b82f6;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
    }
</style>
