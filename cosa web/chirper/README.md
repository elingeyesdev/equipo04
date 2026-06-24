<div align="center">
  <h1>🌊 Sistema de Gestión de Inundaciones (SGI) - Santa Cruz</h1>
  <p><strong>Plataforma Inteligente de Alerta Temprana, Topografía Dinámica y Mapeo Ciudadano</strong></p>
</div>

---

## 📖 Visión General del Proyecto

El **Sistema de Gestión de Inundaciones (SGI)** es una plataforma web desarrollada para monitorear, validar y visualizar eventos hidrológicos extremos en **Santa Cruz de la Sierra, Bolivia**. 

Santa Cruz presenta un desafío geográfico único: es una ciudad caracterizada por una topografía extremadamente plana (una planicie aluvial), donde el sistema de drenaje natural y los canales concéntricos (los famosos "Anillos") colapsan rápidamente frente a las intensas precipitaciones tropicales. Este sistema aborda el problema combinando la **participación ciudadana (Crowdsourcing)** con **Cálculos Topográficos Inteligentes** para predecir y dibujar cómo el agua se acumula en tiempo real.

---

## 🏗️ Arquitectura y Tecnologías

El proyecto sigue una arquitectura monolítica moderna impulsada por eventos, utilizando el ecosistema de Laravel. La capa web no consulta los modelos directamente: usa `FloodApiClient` para invocar una **API REST interna** (autenticada con Sanctum), que es la fuente de verdad del negocio.

* **Backend:** Laravel 13 (PHP 8.3+; entorno actual 8.4).
* **Frontend:** Blade Templates + Livewire + Tailwind CSS 4 (Glassmorphism & Diseño Premium) + JS Vainilla.
* **Tiempo real:** Laravel Reverb (WebSockets) + Laravel Echo para el chat de autoridades.
* **Base de Datos:** PostgreSQL (campos `jsonb` para polígonos y clima).
* **Mapas y GIS:** Leaflet.js + Leaflet.heat (Capa de Calor).
* **Colas:** driver `database` (procesamiento topográfico asíncrono vía `queue:work`).
* **Infraestructura Local:** Docker Compose (`postgres_db`, `web_app`, `queue_worker`).

> 📐 Para una descripción técnica completa (arquitectura, componentes, dependencias y flujo de datos) consulta **[`SCD.md`](SCD.md)**.

---

## 🧠 Lógica Core: El Mapa de Calor y la Topografía Inteligente

A diferencia de los mapas estáticos tradicionales, el SGI no muestra simples "marcadores" donde alguien reportó agua. Simula el comportamiento del agua basándose en el tiempo y el terreno. 

### 1. Sistema de Quórum Dinámico y TTL (Time-To-Live)
Las inundaciones son eventos dinámicos que aparecen y desaparecen. 
* **Reportes Ciudadanos:** Cuando un usuario envía un reporte, este se asocia a una inundación.
* **Tiempo de Vida (TTL):** Cada reporte tiene un tiempo de vida activo de **3 horas**, basado estrictamente en el campo **`updated_at`** de la base de datos.
* **Renovación (Autoridades):** Si el evento de lluvia continúa, una autoridad puede pulsar el botón **"Renovar"**. Esto hace un `touch()` al reporte (actualiza su `updated_at` al momento actual), otorgándole 3 horas adicionales de vida, manteniendo el área visualmente inundada en el mapa sin requerir reportes basura.
* Si el tiempo (`updated_at` + 3h) se agota, el reporte pasa a estar "Caducado" o inactivo, y sus puntos de calor se retiran automáticamente del mapa principal, reduciendo la intensidad de la inundación de forma realista a medida que el agua drena.

### 2. Motor Topográfico (`CalcularPoligonoInundacion` Job)
Cuando se valida un reporte, el sistema no asume que el agua se queda en un punto exacto. El agua fluye según el terreno:
1. El backend encola un **Job** (`CalcularPoligonoInundacion`) que consulta elevación del terreno vía **Open Topo Data** (dataset SRTM 30 m, **sin API key**), con caché de 24 h.
2. Aplica **region growing** sobre una grilla de elevación (celda ≈ 25 m, radio máximo 100/200/300 m según intensidad baja/media/alta).
3. Evalúa el terreno: **el agua solo fluye hacia celdas con elevación igual o menor** al epicentro (margen `0.5 m` por bordes de calle/acera).
4. Genera el polígono del área inundable y lo guarda en `polygon_coords`. Si la API de elevación falla, usa un **fallback geométrico** (círculo) marcado `polygon_es_fallback`.
5. **Resultado:** en lugar de un círculo perfecto, se obtiene la forma irregular real de cómo se empozó el agua.

### 3. Unificación de Zona (una sola mancha por inundación)
Si llueve fuerte en una avenida habrá múltiples reportes en la misma inundación. El objetivo es mostrarlos como **una sola zona continua**, no como puntos sueltos:
* El Job de la **inundación** fusiona los polígonos de todos sus reportes mediante **cierre morfológico** (rasterizar → dilatar → erosionar → componentes conexas; celda 10 m, puente 100 m), produciendo un `Polygon` o `MultiPolygon` unificado.
* En el frontend, si aún no existe el polígono unificado del backend, `flood-outline.js` fusiona en el cliente los polígonos de los reportes en **un único contorno suavizado** (cierre morfológico + suavizado de Chaikin, con convex hull de respaldo).
* Así, reportes de una misma inundación forman **una zona unida**; inundaciones distintas se mantienen separadas.

### 4. Mapa de Calor por Intensidad
El color del mapa de calor representa la **intensidad real de la inundación** (calculada por voto ponderado de peso), no la densidad de puntos:
* Se renderiza **una capa por nivel** con un azul fijo: **baja** `#7dd3fc`, **media** `#0ea5e9`, **alta** `#1e3a8a`. El peso de cada punto modula únicamente la opacidad (centro sólido, bordes difuminados).
* El **radio del blob se fija en metros reales** y escala con el zoom, manteniendo la forma a cualquier acercamiento (sin círculos sueltos).
* Una **leyenda** en el mapa explica los tres niveles, y cada inundación muestra una **etiqueta** con su intensidad.

### 5. Capa de Intervención de Autoridades
Una Autoridad puede dibujar manualmente la zona de desastre. Si la *Inundación* tiene un polígono editado por autoridad (`polygon_editado_autoridad`), este tiene **absoluta prioridad** y el sistema **no lo sobrescribe** en los recálculos automáticos.

---

## ⚙️ Operación y Configuración Clave

* **Zona horaria:** la app usa `America/La_Paz` (UTC‑4, Santa Cruz/Bolivia) vía `APP_TIMEZONE`. Todos los `create`/`update` guardan y muestran la hora local de Bolivia.
* **Procesamiento asíncrono:** los polígonos topográficos se calculan en **cola** (`QUEUE_CONNECTION=database`). El contenedor `queue_worker` (`php artisan queue:work`) debe estar activo para que los cálculos se procesen automáticamente al validar reportes.
* **Recálculo manual (backfill):** para regenerar polígonos de inundaciones antiguas o tras un fallo del worker:
  ```bash
  docker compose exec web_app php artisan topografia:recalcular-inundaciones
  ```
* **Claves de API:** se almacenan solo en `.env` (gitignoreado) y se leen vía `config/services.php` (`OPEN_ROUTE_SERVICE_KEY`, `OPENTOPOGRAPHY_API_KEY`, credenciales OpenWeatherMap). No se exponen en el frontend (salvo OpenRouteService, requerida por el cliente para rutas).
* **Tras cambiar `.env`/config:** `php artisan config:clear` y reinicia `queue_worker`.

---

## 🚀 Guía de Instalación Rápida para Desarrolladores

El proyecto utiliza **Laravel Sail**, lo que significa que solo necesitas Docker instalado.

1. **Clonar el repositorio y entrar a la carpeta del proyecto.**
2. **Instalar dependencias de PHP usando un contenedor efímero:**
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs
   ```
3. **Copiar el archivo de entorno y levantar los contenedores:**
   ```bash
   cp .env.example .env
   ./vendor/bin/sail up -d
   ```
4. **Generar la clave de la app y correr migraciones (con PostGIS):**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```
5. **Compilar los assets del Frontend (Tailwind):**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```
6. Ingresa a `http://localhost:8001` (o el puerto configurado) en tu navegador.

---

## 🎨 Arquitectura del Diseño (UI/UX)
Toda la interfaz del sistema ha sido construida siguiendo estándares **Premium**.
* Se hace uso intensivo del concepto de **Glassmorphism**: paneles translúcidos (`backdrop-blur`) que flotan sobre un fondo vibrante.
* Tipografía **Outfit** (Google Fonts) para un aspecto limpio y moderno.
* Animaciones orgánicas (`hover:-translate-y`, `transition-all`) para darle reactividad al ecosistema.

---
*Desarrollado para la protección y gestión del riesgo hídrico en el Departamento de Santa Cruz de la Sierra, Bolivia.*
