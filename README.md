<div align="center">
  <h1>🌊 Sistema de Gestión de Inundaciones (SGI) - Santa Cruz</h1>
  <p><strong>Plataforma Inteligente de Alerta Temprana, Topografía Dinámica y Mapeo Ciudadano</strong></p>
</div>

---

## 📖 Visión General del Proyecto

El **Sistema de Gestión de Inundaciones (SGI)** es una plataforma web desarrollada para monitorear, validar y visualizar eventos hidrológicos extremos en **Santa Cruz de la Sierra, Bolivia**.

Este sistema aborda el problema combinando la **participación ciudadana (Crowdsourcing)** para predecir y dibujar cómo el agua se acumula en tiempo real.

---

## 🏗️ Arquitectura y Tecnologías

Monolito Laravel impulsado por eventos. La capa web usa `FloodApiClient` para invocar una **API REST interna** (Sanctum), fuente de verdad del negocio.

| Capa | Stack |
|------|--------|
| Backend | Laravel 13 (PHP 8.3+; entorno actual 8.4) |
| Frontend | Blade + Livewire + Tailwind CSS 4 + JS vanilla |
| Tiempo real | Laravel Reverb + Laravel Echo (chat de autoridades) |
| Base de datos | PostgreSQL (`jsonb` para polígonos y clima) |
| Mapas | Leaflet.js + Leaflet.heat |
| Colas | driver `database` (`queue:work` para topografía) |
| Infra | Docker Compose (`postgres_db`, `web_app`, `queue_worker`) |

---

## 🧩 Módulos del Sistema

Además del núcleo de inundaciones y reportes, el SGI incluye módulos operativos para la respuesta ante desastres:

| Módulo | Ruta principal | Descripción |
|--------|----------------|-------------|
| **Reportes e inundaciones** | `/reports` | Mapa de calor, validación ciudadana, quórum y TTL |
| **Centro de comando** | `/command-center` | Timeline, análisis de impacto, fusión de inundaciones, daños materiales |
| **Logística** | `/logistica` | Centros de asistencia |
| **Víctimas** | `/victimas` | Registro y seguimiento de afectados |
| **Inventario** | `/inventario` | Stock por centro de asistencia |
| **Vehículos** | `/vehiculos/mapa` | Flota en mapa y gestión (autoridad) |
| **Chat** | `/chat` | Mensajería entre autoridades (Reverb) |
| **Perfil** | `/perfil` | Datos del usuario autenticado |
| **Sugerencias / FAQ** | `/sugerencias`, `/faq` | Retroalimentación y ayuda pública |

Los roles son **ciudadano** (reporta y consulta) y **autoridad** (valida, opera logística, gestiona flota y datos sensibles).

---

## 🧠 Lógica Core: Mapa de Calor y Topografía

El SGI no muestra solo marcadores estáticos: simula acumulación de agua según tiempo y terreno.

### 1. Quórum dinámico y TTL (3 h)

- Reportes ciudadanos se asocian a inundaciones.
- **TTL:** vida activa de **3 horas** según `updated_at`.
- **Renovar** (autoridades): `touch()` al reporte (+3 h en mapa).
- Al caducar, el calor baja de forma realista.

### 2. Motor topográfico (`CalcularPoligonoInundacion`)

1. Job consulta elevación vía **Open Topo Data** (SRTM 30 m, sin API key), caché 24 h.
2. **Region growing** (celda ≈ 25 m; radio 100/200/300 m por intensidad).
3. Agua solo hacia celdas ≤ epicentro (margen 0.5 m).
4. Polígono en `polygon_coords`; fallback geométrico si falla la API.
5. **PolygonSimplifier:** Douglas-Peucker, máx. 150 vértices/anillo.

### 3. Unificación de zona

- Job de inundación fusiona polígonos de reportes (cierre morfológico).
- Cliente: `flood-outline.js` unifica contornos si el backend aún no calculó.

### 4. Mapa de calor por intensidad

- Capas fijas: baja `#7dd3fc`, media `#0ea5e9`, alta `#1e3a8a`.
- Radio en metros reales; leyenda y etiqueta por inundación.

### 5. Intervención de autoridades

`polygon_editado_autoridad` tiene prioridad absoluta sobre recálculos.

### 6. Validación de reportes

El panel de reportes está implementado con **varios componentes Livewire** y partials compartidos. La navegación entre secciones está en el menú desplegable **Reportes** de la barra superior.

| Ruta | Componente | Acceso |
|------|------------|--------|
| `/reports` | `ReportsHub` | Sesión — vista general con mapa e inundaciones activas |
| `/reports/mis-reportes` | `ReportsMisReportes` | Sesión — reportes del usuario |
| `/reports/historial` | `ReportsHistorial` | Sesión — inundaciones terminadas |
| `/reports/pendientes` | `ReportsPendientes` | Autoridad — cola de validación (paginado) |
| `/reports/rechazados` | `ReportsRechazados` | Autoridad — auditoría con filtros (paginado) |

En **`/reports`** (hub), las autoridades ven una **previsualización** de hasta 5 pendientes y 5 rechazados, con enlaces a las subpáginas completas. El mapa principal incluye rutas seguras (OpenRouteService) y capa de reportes pendientes.

Ambos paneles de validación usan la misma **tabla de 5 columnas** (clase `report-validation-table`), labels `.report-field-label` / valores `.report-field-value`, y minimapas Leaflet (`public/js/report-minimaps.js`: GPS azul vs evento rojo, zoom adaptativo, carga diferida).

#### Panel «Pendientes de Validación»

| Columna | Contenido |
|---------|-----------|
| **Foto** | Thumbnail; clic → modal pantalla completa |
| **Reporte** | N°, Fecha, Reportado por |
| **Detalles** | Descripción; Dirección e **Intensidad propuesta** (50/50); enlace *Ver detalle completo* |
| **Mapa** | Minimapa GPS vs evento; pan + zoom con rueda al hover |
| **Acciones** | **Aprobar** · **Vincular** · **Rechazar** |

#### Panel «Reportes Rechazados»

Misma estructura de 5 columnas. Columna **Reporte** incluye fecha de rechazo, validador y motivo. Columna **Acciones**: botón **Modificar** (modal Livewire) y **Ver historial** (modal Livewire). En `/reports/rechazados` hay filtros por motivo, validador y rango de fechas.

#### Modal «Ver detalle completo» (pendientes)

Texto íntegro, **Reportado por**, **Intensidad propuesta**, minimapa embebido y los tres botones de acción (misma semántica que la fila).

#### Review Drawer (vinculación)

Botón **Vincular** → panel lateral con mapa ampliado, inundaciones cercanas (≤300 m) desde `window.floodReports`, confirmación SweetAlert2 → `POST /api/reportes/{id}/validar`.

---

## ⚙️ Operación y Configuración

- **Zona horaria:** `America/La_Paz` (`APP_TIMEZONE`).
- **Colas:** `QUEUE_CONNECTION=database`; el servicio `queue_worker` debe estar activo.
- **Recálculo topográfico:** `php artisan topografia:recalcular-inundaciones`
- **Optimizar polígonos:** `php artisan topografia:simplificar-poligonos --solo-activas`
- **Tras cambios en Blade:** `php artisan view:clear`
- **Tras `.env`/config:** `php artisan config:clear` y reiniciar `queue_worker`

Desde la carpeta **`equipo04/`** (donde está `docker-compose.yml`):

```bash
docker compose exec web_app php artisan topografia:recalcular-inundaciones
docker compose exec web_app php artisan topografia:simplificar-poligonos --solo-activas
docker compose exec web_app php artisan view:clear
```

> **Datos:** la BD persiste en el volumen Docker `equipo04_pgdata`. No uses `docker compose down -v`. No ejecutes `php artisan test` dentro del contenedor salvo que confirmes SQLite de test (ver [SCD §8.1](cosa%20web/chirper/SCD.md)). Si la BD quedó vacía: `docker compose exec web_app php artisan db:seed`.

---

## 🚀 Instalación (WSL / Ubuntu recomendado)

Para Docker en Windows, ejecutar el proyecto **dentro de WSL** (no en `C:\Users\...`).

1. **WSL:** `wsl --install` (PowerShell admin) y `wsl --update` si hace falta.
2. **Clonar o copiar el repo** al filesystem Linux (ej. `/home/tu_usuario/ProyectoInundaciones2`).
3. **Desde la carpeta `equipo04/`:**
   ```bash
   docker compose up -d --build
   docker compose exec web_app cp .env.example .env    # si aún no existe
   docker compose exec web_app php artisan key:generate
   docker compose exec web_app php artisan migrate --seed
   ```
4. Abrir **`http://localhost:8001`**.

El compose levanta **PostgreSQL** (puerto host `5440`), la app web (`web_app`) y el worker de colas (`queue_worker`). Variables de entorno sensibles (`OPEN_ROUTE_SERVICE_KEY`, claves de clima, etc.) se configuran en `cosa web/chirper/.env`.

Para desarrollo frontend con Vite, desde `cosa web/chirper/`: `npm install && npm run dev`.

---

## 🎨 Diseño UI/UX (`/reports` y paneles de validación)

Interfaz con glassmorphism (`backdrop-blur`), tipografía **Outfit** (Google Fonts) y **SweetAlert2** para confirmaciones.

### Jerarquía de campos

| Elemento | Color / estilo |
|----------|----------------|
| Cabeceras de tabla (Foto, Reporte, …) | Texto `#1F2937` |
| Labels (DESCRIPCIÓN, DIRECCIÓN, **INTENSIDAD PROPUESTA**, …) | `#71717A` (`.report-field-label`) |
| Valores de datos | `#1F2937` (`.report-field-value`) |
| Enlace *Ver detalle completo* | `#4F46E5` (índigo) |

### Botones de acción

| Acción | Fondo | Texto / icono |
|--------|-------|----------------|
| **Aprobar** / Guardar cambios | `#059669` | `#FFFFFF` |
| **Vincular** | `#2563EB` | `#FFFFFF` |
| **Rechazar** | `#F3F4F6` | `#DC2626` |
| **Modificar** (rechazados) | `#EEF2FF` | `#4338CA` |

Pills de **intensidad propuesta** (baja/media/alta): clases `intensity-pill-*` en columna Detalles.

Otros elementos: modales de foto, detalle e historial (z-index sobre Leaflet), minimapas con `wire:ignore`, animaciones suaves en hover.

---

*Desarrollado para la protección y gestión del riesgo hídrico en Santa Cruz de la Sierra, Bolivia.*
