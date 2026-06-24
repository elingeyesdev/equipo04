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

El proyecto sigue una arquitectura monolítica moderna impulsada por eventos, utilizando el ecosistema de Laravel. La capa web usa `FloodApiClient` para invocar una **API REST interna** (Sanctum), que es la fuente de verdad del negocio.

* **Backend:** Laravel 13 (PHP 8.3+; entorno actual 8.4).
* **Frontend:** Blade Templates + Livewire + Tailwind CSS 4 (Glassmorphism & Diseño Premium) + JS Vainilla.
* **Tiempo real:** Laravel Reverb (WebSockets) + Laravel Echo.
* **Base de Datos:** PostgreSQL (campos `jsonb`).
* **Mapas y GIS:** Leaflet.js + Leaflet.heat (Capa de Calor).
* **Colas:** driver `database` (cálculo topográfico asíncrono).
* **Infraestructura Local:** Docker Compose (`postgres_db`, `web_app`, `queue_worker`).

> 📐 Descripción técnica completa en **[`cosa web/chirper/SCD.md`](cosa%20web/chirper/SCD.md)**.

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
Cuando se valida un reporte, el sistema simula cómo fluye el agua según el terreno:
1. El backend encola un **Job** que consulta elevación vía **Open Topo Data** (SRTM 30 m, **sin API key**), con caché de 24 h.
2. Aplica **region growing** sobre una grilla de elevación (celda ≈ 25 m, radio 100/200/300 m según intensidad).
3. **El agua solo fluye hacia celdas con elevación igual o menor** al epicentro (margen `0.5 m`).
4. Guarda el polígono en `polygon_coords`. Si la API falla, usa un **fallback geométrico** (`polygon_es_fallback`).

### 3. Unificación de Zona (una sola mancha por inundación)
Los reportes de una misma inundación se muestran como **una sola zona continua**:
* El Job de la inundación fusiona los polígonos de sus reportes por **cierre morfológico** (rasterizar → dilatar → erosionar → componentes conexas) en un `Polygon`/`MultiPolygon` unificado.
* En el frontend, `flood-outline.js` puede fusionarlos en el cliente en un **contorno único suavizado** (Chaikin) cuando el backend aún no lo calculó.

### 4. Mapa de Calor por Intensidad
El color representa la **intensidad de la inundación** (voto ponderado por peso), no la densidad:
* Una capa por nivel con azul fijo: **baja** `#7dd3fc`, **media** `#0ea5e9`, **alta** `#1e3a8a`; el peso modula la opacidad.
* El radio del blob se fija en **metros reales** y escala con el zoom (sin círculos sueltos). Incluye **leyenda** y **etiqueta de intensidad** por inundación.

### 5. Capa de Intervención de Autoridades
Una Autoridad puede dibujar la zona de desastre manualmente. Si la *Inundación* tiene `polygon_editado_autoridad`, este polígono tiene **absoluta prioridad** y no se sobrescribe en los recálculos automáticos.

---

## 🚀 Guía de Instalación Rápida para Desarrolladores (Optimizado para WSL/Ubuntu)

Para maximizar el rendimiento de Docker en Windows, este proyecto se ha configurado para ejecutarse nativamente dentro de **WSL (Ubuntu)**. Sigue estos pasos para levantar el entorno:

1. **Instalar WSL (Linux) en Windows:**
   Abre una terminal de PowerShell como administrador y ejecuta:
   ```powershell
   wsl --install
   ```
   *(Si ya lo tienes, asegúrate de que esté actualizado con `wsl --update` y reinicia si es necesario).*

2. **Mover o copiar el proyecto a Ubuntu:**
   Para que Docker funcione a la máxima velocidad, el proyecto **no** debe estar en tu disco de Windows (como `C:\Users\...`). 
   - Abre tu terminal de Ubuntu y clona o copia tu proyecto dentro del sistema de archivos de Linux (por ejemplo, en `/home/tu_usuario/ProyectoInundaciones2` o `/root/...`).

3. **Instalar dependencias clave en Ubuntu:**
   Abre tu terminal de Ubuntu y asegúrate de instalar PHP, Composer y Node.js para poder gestionar los paquetes correctamente de forma local antes de levantar Sail:
   ```bash
   # 1. Actualizar sistema
   sudo apt update

   # 2. Instalar PHP y extensiones básicas
   sudo apt install -y php-cli php-curl php-xml php-mbstring unzip

   # 3. Instalar Composer
   curl -sS https://getcomposer.org/installer -o composer-setup.php
   sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
   rm composer-setup.php

   # 4. Instalar Node.js y NPM
   sudo apt install -y nodejs npm
   ```

4. **Configurar Docker y levantar el sistema (Laravel Sail):**
   Dentro de Ubuntu, navega a la carpeta principal de tu proyecto web (ej. `cd "cosa web/chirper"`) y ejecuta:
   ```bash
   # Instalar dependencias de backend
   composer install

   # Copiar archivo de entorno
   cp .env.example .env

   # Levantar los contenedores de Docker (Base de Datos, Redis, etc.) en segundo plano
   ./vendor/bin/sail up -d

   # Generar clave de aplicación y correr las migraciones (PostGIS)
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

5. **Correr el programa (Frontend):**
   Finalmente, instala los paquetes de Node y arranca el servidor de desarrollo:
   ```bash
   npm install
   npm run dev
   ```

6. Ingresa a `http://localhost:8001` (o el puerto configurado en tu `.env`) en tu navegador para ver la plataforma funcionando a máxima velocidad.

---

## 🎨 Arquitectura del Diseño (UI/UX)
Toda la interfaz del sistema ha sido construida siguiendo estándares **Premium**.
* Se hace uso intensivo del concepto de **Glassmorphism**: paneles translúcidos (`backdrop-blur`) que flotan sobre un fondo vibrante.
* Tipografía **Outfit** (Google Fonts) para un aspecto limpio y moderno.
* Animaciones orgánicas (`hover:-translate-y`, `transition-all`) para darle reactividad al ecosistema.

---
*Desarrollado para la protección y gestión del riesgo hídrico en la ciudad.*
