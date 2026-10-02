# Fase 3: ingeniería inversa y control de accesos

**Sistema:** tienda en línea Fastpack  
**Fecha:** 1 de octubre de 2026  
**Alcance:** análisis estático PHP/JavaScript y comprobaciones locales sobre XAMPP. No se accedió a producción, no se usaron cuentas de terceros y no se copió ni reutilizó una cookie de sesión real.

## 1. Principios de seguridad

### Disponibilidad e integridad del servicio

- **Anti-DDoS:** no se pudo confirmar proveedor, plan de hosting, CDN/WAF ni controles anti-DDoS. No se afirma que exista mitigación. Confirmar con el proveedor protección L3/L4/L7, límites, alertamiento y procedimiento de escalamiento; el código de la tienda no sustituye esa protección.
- **Caída de MySQL:** antes, `dbcon.php` exponía detalles de conexión al cliente. Ahora registra el detalle en el log privado y responde HTTP 503 con `Retry-After: 60` y un mensaje genérico. La tienda no tiene failover, cola de pedidos ni caché transaccional; mientras MySQL no esté disponible, las rutas que necesitan datos deben fallar cerradas y el usuario no puede completar el pedido. Probar recuperación/restauración y acordar RPO/RTO con el hosting.
- **Cálculo de compra:** `carrito-de-compras.php` calcula en JavaScript los totales que se muestran. DevTools/localStorage permiten cambiar esa presentación y cantidades antes de enviar el formulario; no deben considerarse autoritativas.
- **Autoridad del servidor:** `codeenvio.php` recibe identificadores y cantidades, vuelve a consultar productos, precios, descuentos, cupones, configuraciones y stock en MySQL, y calcula el pedido guardado. `codepago.php` obtiene `pedidos.total` usando el identificador del pedido y manda ese importe a Openpay desde PHP; el navegador no envía el importe a cobrar. Según el código revisado, editar el total visual no altera por sí solo el importe solicitado a Openpay.
- **Webhook:** no se encontró endpoint de webhook/notificación de Openpay ni verificación de firma. El código observado crea el cargo mediante la API de Openpay y cambia el estado con la respuesta síncrona. Por tanto, no hay webhook cuyo monto se haya validado. Si se habilitan notificaciones, el handler debe verificar autenticidad conforme a la documentación vigente de Openpay, recuperar/validar la transacción del lado servidor, comparar monto/moneda/orden con MySQL, procesar eventos idempotentemente y no confiar en datos del navegador.

### Confidencialidad y HTTPS

Las pruebas se realizaron en `127.0.0.1/ecommerce`:

- `GET http://127.0.0.1/ecommerce/login.php` respondió `200 OK`; el servidor local acepta HTTP y no se observó redirección a HTTPS. No se puede afirmar que el tráfico del login esté siempre cifrado.
- Una petición HTTPS con validación normal del certificado falló con `SEC_E_UNTRUSTED_ROOT`. El servidor local permite negociación TLS si se omite la verificación, pero eso no demuestra que el certificado sea válido o confiable. No usar `curl -k` como prueba de confianza del certificado.
- Las bibliotecas de Openpay se cargan mediante URLs `https://`; esto no certifica que el dominio de la tienda ni todas las llamadas desde el servidor estén correctamente configurados en producción.
- Después del endurecimiento, la cookie PHP lleva `HttpOnly` y `SameSite=Lax`; `Secure` se activa cuando PHP detecta HTTPS. La prueba HTTPS local observó `Secure`, pero la cadena del certificado no es confiable.

**Conclusión:** configurar certificado vigente de una CA confiable en el dominio real, redirección HTTP→HTTPS en Apache/proxy, TLS moderno y URLs HTTPS de producción de Openpay antes de procesar credenciales o pagos. Validar desde una máquina externa sin desactivar la verificación de certificados. El hosting y el certificado de producción no fueron identificados.

## 2. Ingeniería inversa del cliente y sesión

El carrito guarda `id` y `cantidad` en `localStorage`. `updateTotals()` calcula en JavaScript subtotal, descuentos, envío y total para mostrarlos. Un usuario puede modificar DOM, precios visibles o `localStorage`; la protección es que el servidor no acepta ese total como dato de pago y recalcula al guardar el pedido. Esta conclusión es revisión de código, no una prueba completa con una transacción de Openpay.

No se robó ni pegó una cookie real. Para una demostración académica, usar únicamente una cuenta sintética y una instancia local aislada; no capturar cookies de usuarios reales ni incluirlas en capturas o el informe. `HttpOnly` impide leer `PHPSESSID` desde JavaScript de la página, pero no impide que el propietario de un navegador inspeccione sus propias cookies en DevTools. La protección de transporte depende de HTTPS válido; `SameSite=Lax` reduce ciertos envíos cross-site, pero no reemplaza CSRF tokens.

El login ya llama a `session_regenerate_id(true)` después de validar las credenciales y antes de establecer `username`, `user_id` y `user_role`, mitigando fijación de sesión. No se simuló robo/replay de cookie. Las cookies no se deben copiar entre perfiles como prueba con sesiones reales.

## 3. RBAC y autenticación

### Roles y acceso

- **Rol 1: Administrador.** Puede entrar a configuración de Usuarios y realizar administración autorizada.
- **Rol 2: Vendedor.** Puede usar los flujos permitidos de catálogo/ventas, pero no ver `usuarios.php`; el menú también oculta el enlace. El servidor verifica el rol en la página y en los controladores, no solo en la interfaz.
- **Rol 3: Cliente.** No pasa las comprobaciones de acceso a Intranet.

El acceso a la lista de usuarios está restringido a rol 1; los controladores administrativos vuelven a comprobar roles en cada POST. El registro público crea rol 3 y no permite elegir rol administrativo.

### Política de contraseña

Registro y creación/cambio administrativo ahora exigen en PHP y en el navegador mínimo 8 caracteres, al menos una minúscula, una mayúscula y un número. El cliente mejora el mensaje, pero el servidor es la validación de autoridad. La política no se aplica retroactivamente hasta que se cambie una contraseña; el login conserva compatibilidad con hashes antiguos para su migración a bcrypt.

### Bloqueo temporal

Los fallos de autenticación de una cuenta existente se cuentan en MySQL, no en la sesión del navegador. El tercer intento inválido activa un bloqueo de cinco minutos; un login correcto elimina el contador. Se añadió `scripts/migrate-login-lockout.php` y se aplicó a la base local. **En cada entorno de despliegue debe ejecutarse una vez** antes de habilitar el login actualizado; de otro modo la tabla `login_attempts` no existirá.

La prueba local llamó a la lógica real contra MySQL con un identificador sintético: intentos 1 y 2 sin bloqueo, intento 3 bloqueado, y borrado del registro al terminar. El bloqueo por cuenta puede ser usado para causar denegación temporal de servicio contra una cuenta objetivo; monitorear estos eventos y evaluar límites adicionales por IP/proxy sin registrar contraseñas.

## 4. Antes y después

### Total alterado en el navegador

Antes, JavaScript produce el total visible:

```javascript
document.getElementById("totalPagar").textContent = `$ ${totalFinal.toFixed(2)}`;
```

Después, el servidor continúa calculando el monto desde catálogo y configuración de MySQL, y `codepago.php` usa `pedidos.total` para llamar a Openpay; el texto del DOM no es fuente de cobro. La modificación del código cliente puede engañar visualmente al comprador, pero no debe cambiar el cargo solicitado por el backend.

### Contraseña y bloqueo

Antes, registro solo comprobaba longitud mínima de ocho caracteres y no había bloqueo persistente. Ahora PHP valida composición y después de tres fallos guarda un bloqueo temporal en `login_attempts`:

```php
passwordMeetsPolicy($password);
recordFailedLogin($pdo, $email);
```

La política también se valida en los formularios de registro y administración mediante restricciones nativas y eventos JavaScript.

## 5. Verificación y pendientes

- PHP lint pasó en las rutas editadas; los diagnósticos del editor no mostraron errores en la pasada anterior.
- Pruebas PHP confirmaron aceptación/rechazo de ejemplos de contraseña y bloqueo MySQL al tercer fallo.
- Inspección de código confirmó regeneración del ID tras login correcto y cálculo del importe de Openpay desde el pedido almacenado.
- **Pendiente:** ejecutar la migración `php scripts/migrate-login-lockout.php` en staging/producción.
- **Pendiente:** HTTPS de producción con certificado confiable y redirección obligatoria; el certificado local actual falla la validación normal y HTTP sigue respondiendo.
- **Pendiente:** confirmar anti-DDoS, copias, disponibilidad y recuperación con el proveedor de hosting.
- **Pendiente:** si el negocio utiliza webhook de Openpay, documentar su contrato y agregar verificación criptográfica/idempotencia; no hay receptor en el código actual.
- **Pendiente:** probar con comprador/vendedor/administrador sintéticos en staging que el rol 2 recibe 403 en Usuarios y que edición de totales del navegador no cambia el importe final de sandbox.