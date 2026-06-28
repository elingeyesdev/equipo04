<style>
    .hidden { display: none !important; }

    .rapido-body {
        min-height: 100dvh;
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

    /* GPS status */
    .rapido-gps-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .rapido-gps-dot--pending { background: #eab308; animation: rapido-pulse-dot 1.5s ease-in-out infinite; }
    .rapido-gps-dot--active  { background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25); }
    .rapido-gps-dot--error   { background: #ef4444; }

    @keyframes rapido-pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* Alert banner */
    .rapido-alert-banner {
        background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
        color: #fff;
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-align: center;
        animation: rapido-alert-pulse 2s ease-in-out infinite;
    }

    @keyframes rapido-alert-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.85; }
    }

    /* Carousel wrapper + nav */
    .rapido-carousel-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0 0.5rem;
    }

    .rapido-carousel-nav {
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-size: 1.25rem;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        transition: background 0.15s, color 0.15s;
    }
    .rapido-carousel-nav:hover {
        background: #eff6ff;
        color: #2563eb;
        border-color: #93c5fd;
    }
    .rapido-carousel-nav:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .rapido-carousel-dots {
        display: flex;
        justify-content: center;
        gap: 0.375rem;
        padding: 0.375rem 1rem 0.5rem;
    }
    .rapido-carousel-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        border: none;
        padding: 0;
        background: #cbd5e1;
        cursor: pointer;
        transition: background 0.15s, transform 0.15s;
    }
    .rapido-carousel-dot.is-active {
        background: #2563eb;
        transform: scale(1.25);
    }

    /* Carousel */
    .rapido-carousel {
        flex: 1;
        min-width: 0;
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        padding: 0.75rem 0.25rem;
        scrollbar-width: none;
        cursor: grab;
        user-select: none;
    }
    .rapido-carousel.is-dragging {
        cursor: grabbing;
        scroll-snap-type: none;
    }
    .rapido-carousel::-webkit-scrollbar { display: none; }

    .rapido-flood-card {
        flex: 0 0 auto;
        width: min(260px, 78vw);
        scroll-snap-align: start;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        background: #fff;
        padding: 0.875rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }
    .rapido-flood-card:hover {
        border-color: #93c5fd;
    }
    .rapido-flood-card.is-selected {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .rapido-flood-card.is-intensity-alta { border-left: 4px solid #1e3a8a; }
    .rapido-flood-card.is-intensity-media { border-left: 4px solid #0ea5e9; }
    .rapido-flood-card.is-intensity-baja { border-left: 4px solid #7dd3fc; }

    .rapido-intensity-badge {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        color: #fff;
    }
    .rapido-intensity-badge--baja  { background: #38bdf8; color: #0c4a6e; }
    .rapido-intensity-badge--media { background: #0ea5e9; }
    .rapido-intensity-badge--alta  { background: #1e3a8a; }

    .rapido-status-badge {
        display: inline-block;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.15rem 0.45rem;
        border-radius: 9999px;
    }
    .rapido-status-badge--confirmada { background: #dcfce7; color: #166534; }
    .rapido-status-badge--validacion { background: #fef3c7; color: #92400e; }

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
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .rapido-heat-legend-item--baja  { background: #7dd3fc; color: #0c4a6e; }
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
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    #rapidoMap { width: 100%; height: 100%; z-index: 1; }

    .rapido-map-hint {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 500;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(4px);
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #475569;
        pointer-events: none;
        white-space: nowrap;
    }

    /* Pulsing user pin */
    .rapido-user-pin {
        background: #f97316 !important;
        border: 3px solid #fff !important;
        border-radius: 50% !important;
        width: 18px !important;
        height: 18px !important;
        box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5);
        animation: rapido-pin-pulse 2s ease-out infinite;
    }

    @keyframes rapido-pin-pulse {
        0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.55); }
        70% { box-shadow: 0 0 0 12px rgba(249, 115, 22, 0); }
        100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
    }

    /* Mode chip */
    .rapido-mode-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 9999px;
        margin: 0.5rem 1rem 0;
    }
    .rapido-mode-chip button {
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
        padding: 0 0.125rem;
    }

    /* Segmented control */
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
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        text-align: center;
        line-height: 1.2;
    }
    .rapido-intensity-btn:active { transform: scale(0.97); }

    .rapido-intensity-btn--baja {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .rapido-intensity-btn--baja.is-active {
        background: #059669;
        color: #fff;
        border-color: #059669;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
    }

    .rapido-intensity-btn--media {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .rapido-intensity-btn--media.is-active {
        background: #d97706;
        color: #fff;
        border-color: #d97706;
        box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);
    }

    .rapido-intensity-btn--alta {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #fecaca;
    }
    .rapido-intensity-btn--alta.is-active {
        background: #dc2626;
        color: #fff;
        border-color: #dc2626;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
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
    .rapido-submit-btn--alta  {
        background: #dc2626;
        animation: rapido-submit-urgency 2s ease-in-out infinite;
    }

    @keyframes rapido-submit-urgency {
        0%, 100% { box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4); }
        50% { box-shadow: 0 4px 24px rgba(220, 38, 38, 0.65); }
    }

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

    .rapido-empty-carousel {
        padding: 0.75rem 1rem;
        font-size: 0.8125rem;
        color: #64748b;
        text-align: center;
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
        box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }
</style>
