<?php
$pageTitle = 'Aviso de privacidad | Fastpack';
require __DIR__ . '/templates/header.php';
?>
<main class="legal-page container">
    <header class="legal-heading">
        <p class="legal-kicker">FASTPACK / DATOS PERSONALES</p>
        <h1>Aviso de privacidad integral</h1>
        <p>Última actualización: 25 de septiembre de 2026</p>
    </header>


    <section class="legal-section">
        <h2>1. Responsable</h2>
        <p>El responsable del tratamiento es el titular que opera comercialmente como Fastpack (razón social o nombre legal pendiente de confirmar), con domicilio en [DOMICILIO COMPLETO EN MÉXICO]. Para consultas de privacidad y para ejercer derechos sobre tus datos, escribe a <a href="mailto:ventas@fastpack.mx">ventas@fastpack.mx</a>.</p>
    </section>
    <section class="legal-section">
        <h2>2. Datos que tratamos</h2>
        <p>Al crear una cuenta podemos tratar nombre, apellidos y correo electrónico. Para atender una compra podemos tratar además teléfono, domicilio de entrega (calle, números, colonia, ciudad, estado, código postal y país), productos adquiridos, importes, identificador y estado del pedido, y datos necesarios para aclaraciones y entrega.</p>
        <p>El sitio no solicita ni almacena el número completo de tarjeta, fecha de vencimiento ni código de seguridad (CVV). En pagos con tarjeta, los campos de tarjeta se envían a Openpay para tokenización; la tienda recibe el token de pago y referencias/identificadores de la transacción para solicitar el cargo y conciliar el pedido. Un token y los identificadores de pago se tratan como información confidencial.</p>
    </section>
    <section class="legal-section">
        <h2>3. Finalidades</h2>
        <p>Usamos los datos para crear y administrar tu cuenta, procesar pagos, confirmar compras, preparar y entregar pedidos, emitir comprobantes cuando se soliciten, atender consultas, aclaraciones, devoluciones y obligaciones contables, fiscales o legales. El correo también puede recibir avisos operativos de registro, inicio de sesión y pedido. No usamos esos avisos como autorización para publicidad; cualquier comunicación promocional requerirá el consentimiento aplicable y una opción para dejar de recibirla.</p>
    </section>
    <section class="legal-section">
        <h2>4. Encargados y transferencias</h2>
        <p>Openpay procesa la tokenización y los pagos con tarjeta. Para el cargo se le transmiten los datos que requiere la operación, como nombre, apellidos, correo y teléfono, además del token y la información de la transacción; el proveedor recibe directamente los datos de tarjeta desde su componente de pago. Openpay trata esa información conforme a sus propios avisos y condiciones. También pueden intervenir proveedores de correo electrónico, alojamiento y mapas/direcciones cuando sean habilitados en el sitio. Solo se compartirán los datos necesarios para la finalidad y con las salvaguardas contractuales y legales correspondientes.</p>
        <p>No se venden datos personales. Si una transferencia distinta de las necesarias para prestar el servicio requiere consentimiento, se solicitará previamente, salvo excepción legal.</p>
    </section>
    <section class="legal-section">
        <h2>5. Conservación y seguridad</h2>
        <p>Conservamos los datos durante el tiempo necesario para las finalidades descritas y los plazos de prescripción, fiscales y de defensa de derechos que resulten aplicables; después se eliminan o disocian de forma segura. Las contraseñas se almacenan como hashes bcrypt y no son reversibles. El teléfono y domicilio se cifran en la base de datos mediante AES-256-GCM; la clave se mantiene fuera del código fuente, en configuración protegida del servidor. Los datos de tarjeta no se guardan en la tienda.</p>
    </section>
    <section class="legal-section">
        <h2>6. Derechos ARCO y revocación</h2>
        <p>Puedes solicitar acceso, rectificación, cancelación u oposición, así como revocar el consentimiento cuando proceda, escribiendo a <a href="mailto:ventas@fastpack.mx">ventas@fastpack.mx</a> con el asunto “Privacidad / derechos ARCO”. Incluye tu nombre, el correo asociado a la cuenta, el derecho que deseas ejercer y los elementos necesarios para localizar los datos. Para protegerte, podremos pedir información razonable para verificar identidad y representación. Responderemos dentro de los plazos previstos por la legislación mexicana vigente y comunicaremos el resultado y, en su caso, las medidas adoptadas.</p>
        <p>La cancelación puede quedar sujeta a conservación obligatoria de información necesaria para atender obligaciones legales, pagos, garantías o controversias; en tal caso se bloqueará para otros usos.</p>
    </section>
    <section class="legal-section">
        <h2>7. Cookies y tecnologías similares</h2>
        <p>El carrito puede utilizar almacenamiento local del navegador. El proceso de pago puede cargar componentes de Openpay y, en la página de envío, servicios de mapas/direcciones. El navegador puede recibir cookies o identificadores técnicos de esos proveedores. Puedes limitar cookies desde tu navegador; desactivarlas puede impedir funciones del sitio o del pago.</p>
    </section>
    <section class="legal-section">
        <h2>8. Cambios y autoridad</h2>
        <p>Publicaremos las actualizaciones de este aviso en esta página e indicaremos su fecha. En México puedes acudir a la autoridad garante competente en materia de protección de datos personales conforme a la legislación vigente, sin perjuicio de los medios de defensa aplicables.</p>
    </section>
</main>
<?php require __DIR__ . '/templates/footer.php'; ?>
