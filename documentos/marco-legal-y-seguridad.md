# Fase 1: cumplimiento legal y seguridad

**Sistema:** tienda en línea Fastpack (nombre comercial observado en el código; titular legal por confirmar)  
**Fecha del análisis:** 25 de septiembre de 2026  
**Alcance:** revisión del código PHP disponible. No se inspeccionó `.env` ni se verificó la configuración real de producción, contratos con proveedores, avisos de Openpay, esquema completo de MySQL o prácticas operativas. Este documento es una guía de trabajo, no una opinión legal ni una certificación.

## 1. Normativa aplicable

### México: protección de datos personales

La referencia principal es la **Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP) vigente**, su Reglamento y disposiciones aplicables sobre avisos de privacidad y medidas de seguridad. La tienda decide finalidades y medios para gestionar cuentas, pedidos y entregas, por lo que debe identificarse con su razón social/nombre legal y domicilio reales como responsable, informar finalidades y transferencias, habilitar el ejercicio de derechos ARCO, limitar conservación y proteger los datos. La Ley Federal de Protección al Consumidor también aplica a información, publicidad, precio, entrega, garantías, cancelaciones y reembolsos de comercio electrónico.

**Acción bloqueante:** confirmar titular legal, domicilio, canal de privacidad y política comercial real de devoluciones. No publicar un aviso que presente “Fastpack” como razón social si solo es una marca.

### GDPR

El **Reglamento General de Protección de Datos (GDPR/RGPD)** no se aplica automáticamente por usar software PHP o por tener un sitio web. Puede resultar aplicable si el responsable ofrece bienes/servicios a personas en la Unión Europea o monitoriza su comportamiento allí. En ese caso se requiere determinar base jurídica por finalidad, informar derechos y transferencias internacionales, atender solicitudes y evaluar obligaciones adicionales (por ejemplo, representante en la UE cuando corresponda). Debe analizarse con asesoría según el mercado objetivo y los proveedores realmente contratados.

### ISO/IEC 27001

**ISO/IEC 27001** es un estándar certificable y voluntario para un sistema de gestión de seguridad de la información; no es por sí misma una ley mexicana que certifique esta tienda. Es una referencia útil para inventario de activos, evaluación y tratamiento de riesgos, control de accesos, gestión de incidentes, continuidad, proveedores y mejora continua. No se afirma que el sitio esté certificado.

### PCI DSS y Openpay

**PCI DSS** es un estándar de la industria de tarjetas, exigido contractualmente por las marcas/adquirentes según el rol y alcance del comercio. El código observado usa Openpay.js para tokenizar la tarjeta en el navegador y manda al servidor `token_id` como `source_id`; el cargo se solicita con la API de Openpay. El comercio también envía datos de cliente (nombre, correo y teléfono) y guarda identificadores del cargo. El código revisado no persiste PAN ni CVV, lo cual reduce exposición, pero tokenización no significa automáticamente que el comercio quede fuera de PCI DSS. Hay que confirmar con Openpay/adquirente el SAQ y alcance aplicables a la integración y mantener protegidos el token, la llave secreta, el identificador de dispositivo, los registros y el servidor. Nunca registrar PAN/CVV ni enviar esos datos a PHP propio.

## 2. Hallazgos y controles implementados

- Registro y alta administrativa guardan contraseñas con `password_hash()` en bcrypt, coste 12. Login usa `password_verify()` y rehash cuando corresponde. Hashes MD5 o contraseñas históricas en texto se migran a bcrypt al primer login válido; después del cambio de código, el hash antiguo debe seguir disponible hasta ese momento.
- Auditoría de solo lectura ejecutada sobre la base local: 3 cuentas, 3 hashes bcrypt, 0 MD5 y 0 valores clasificados como texto plano/u otro formato.
- Login regenera el identificador de sesión al autenticarse.
- Teléfono y campos de dirección de nuevos pedidos usan cifrado autenticado AES-256-GCM. La clave procede de `DATA_ENCRYPTION_KEY` fuera del repositorio. Se mantiene lectura compatible con filas todavía no migradas.
- `scripts/migrate-order-personal-data.php` prepara ampliación de columnas y cifrado de filas antiguas. El simulacro local confirmó que `telefono` es `VARCHAR(20)`, la mayoría de domicilios son `VARCHAR(150)` y `postal` es `INT`; el código de alta de pedidos exige columnas de al menos 512 caracteres y, por tanto, rechazará nuevos pedidos hasta aplicar la migración. El simulacro no modificó la base. Aplicar solo después de un respaldo verificado y con `--apply`.
- El navegador envía la tarjeta a la biblioteca de Openpay para obtener token. PHP usa el token para crear el cargo; no se debe guardar número de tarjeta ni CVV.
- Las credenciales SMTP incrustadas en dos archivos se cambiaron por configuración del entorno. Las contraseñas ya no se incluyen en correos administrativos.

## 3. Pendientes operativos antes de producción

1. Completar identidad legal y domicilio en `aviso-privacidad.php` y `terminos-condiciones.php`; validar ambos documentos con asesoría local y con la operación real.
2. Configurar `DATA_ENCRYPTION_KEY` en el gestor de secretos del servidor; en la máquina de despliegue se puede generar con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Mantener copia de recuperación cifrada, con acceso restringido. Perderla impide leer los datos cifrados. No reutilizar ni pegarla en el repositorio o en el chat.
3. Respaldar MySQL, revisar el modo de simulación y ejecutar la migración en mantenimiento; verificar después pedidos y flujos de pago/envío. Mantener el respaldo protegido y con acceso limitado.
4. Las llaves/contraseñas SMTP que estaban incrustadas en el historial del código deben considerarse comprometidas: revocarlas/rotarlas con el proveedor y configurar las nuevas exclusivamente en `.env` protegido o el gestor de secretos. No basta con borrar el texto del archivo actual. La clave secreta de Openpay también debe rotarse si estuvo expuesta.
5. Verificar que `.env` no se publique, tenga permisos de solo lectura para el usuario de la aplicación y que `vendor/` y directorios de respaldo no sean servidos públicamente. En producción, desactivar `display_errors`, usar HTTPS y cookies de sesión `Secure`, `HttpOnly` y `SameSite`.
6. Confirmar con Openpay/adquirente el alcance PCI DSS, cuestionario/SAQ, flujos de reembolso, retención de identificadores, ambiente de producción y procedimiento de incidentes. Rotar cualquier llave Openpay que se haya expuesto.
7. Revisar proveedor de hosting/correo/mapas, contratos de encargado/transferencias, controles de acceso del panel administrativo, respaldos, borrado y respuesta a incidentes.
8. Confirmar el procedimiento y los plazos reales de cancelación, devolución, garantía y reembolso, e incorporarlos a los términos y al flujo de atención. No restringir derechos legales del consumidor.

## 4. Verificación

La revisión de código no sustituye pruebas de integración ni una evaluación PCI, auditoría de seguridad, análisis de impacto de privacidad o revisión legal. Antes de liberar, probar registro/login con bcrypt y con un hash MD5/texto histórico en ambiente de prueba; cifrado/descifrado y fallo ante clave incorrecta; pedido nuevo, tarjeta tokenizada, SPEI, correo de envío, vista administrativa y migración con respaldo restaurable.

## 5. Fuentes de referencia

- [LFPDPPP, Cámara de Diputados](https://www.diputados.gob.mx/LeyesBiblio/pdf/LFPDPPP.pdf).
- [Ley Federal de Protección al Consumidor, Cámara de Diputados](https://www.diputados.gob.mx/LeyesBiblio/pdf/LFPC.pdf).
- [Reglamento (UE) 2016/679, EUR-Lex](https://eur-lex.europa.eu/eli/reg/2016/679/oj).
- [ISO/IEC 27001:2022, ISO](https://www.iso.org/standard/27001).
- [PCI DSS, PCI Security Standards Council](https://www.pcisecuritystandards.org/standards/pci-dss/).
- [Documentación para integración, Openpay México](https://www.openpay.mx/docs/).
