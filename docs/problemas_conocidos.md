# Bitácora de Problemas Conocidos y Soluciones Históricas

Esta documentación detalla los problemas de arquitectura, compatibilidad y desarrollo con los que nos hemos enfrentado en el transcurso del proyecto de Logística y Reportes, así como sus resoluciones provisionales y finales para orientar el futuro mantenimiento.

## 1. Crash fatal en peticiones `PATCH/PUT` sobre PHP 8.4 (Symfony/Laravel)
**Descripción**: 
Al actualizar un elemento a través de la API (por ejemplo `FloodApiClient->updateCentro`), la solicitud tipo HTTP `PATCH` terminaba provocando un Error 500 silencioso en el Backend (API) de Laravel manifestado a través de Symfony (`Call to undefined function Symfony\Component\HttpFoundation\request_parse_body()`). Al crashear silenciosamente en el servidor de desarrollo (`php artisan serve`), éste devolvía un Status Incongruente de *200 OK* al Frontend conteniendo en su cuerpo (body) la traza de error PHP en bruto, el cual hacía creer a Laravel Web que el guardado había sido un éxito sin efectuar ningún cambio en la base de datos real.

**Solución aplicada (Workaround)**: 
Se suplantaron los métodos en `FloodApiClient.php`. En lugar de invocar internamente a `$this->client()->patch(...)`, se reescribió para utilizar un `$this->client()->post(...)` inyectando subrepticiamente el campo especial `['_method' => 'PATCH']`. Esta técnica (`Method Spoofing`) es una solución avalada por Laravel para que el Router convierta la solicitud POST a PATCH nativamente en el backend sin disparar la función problemática de parseo.

## 2. Exclusiones en la Jerarquía de Excepciones del API (`ApiValidationException`)
**Descripción**: 
El componente que puenteaba las llamadas hacia la API atrapaba la mala validación desde el backend (códigos `422 Unprocessable Entity`), lanzando una clase `ApiValidationException`. Desgraciadamente, ésta clase extendía puramente de `\RuntimeException`, lo que forzaba a que los sub-bloques `catch (ApiRequestException)` en los Controladores omitieran el error. El resultado resultaba en que el manejador global de Laravel `Ignition` intentara renderizar en bruto el modelo, crasheando y recargando la página en la vista o emitiendo fallos catastróficos.

**Solución aplicada**: 
La clase `ApiValidationException` fue readaptada para ser un hijo (extends) explícito de `ApiRequestException`. Paralelamente, se instruyó a los controladores (ej. `LogisticsController@update`) a que contengan un bloque propio específico `catch (ApiValidationException $e)`. Gracias a esto, todos los errores de entrada por usuarios (como coordenadas vacías) ahora se muestran naturalmente mediante una burbuja roja sobre los formularios (vía `$errors` al Blade), protegiendo la UX.



---
**Notas Adicionales**: 
*   **Token Expirado**: Si alguna solicitud devuelve `401 Unauthorized`, recuerda que la conexión entre el Web (8001) y la API (8002) depende de un Sanctum Token en sesión. El cierre de sesión y logeo lo refrescarán automáticamente.
*   **CORS**: En caso de un pase a producción real, al estar hosteados en diferentes servidores, verificar dominios en `config/cors.php` del Backend asegurándose de agregar al frontend a la lista VIP permitida.
