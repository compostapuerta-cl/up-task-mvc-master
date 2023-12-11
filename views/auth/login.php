<div class="contenedor login">

<?php include_once __DIR__ . '/../templates/nombre-sitio.php'; ?>

    <div class="contenedor-sm">
        <p class="descripcion-pagina">Iniciar Sesion</p>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>
        <form action="/" class="formulario" method="POST" novalidate>

            <div class="campo">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Tu Email">
            </div>

            <div class="campo">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Tu Password">
            </div>

            <input type="submit" class="boton" value="Iniciar Sesion">

        </form>

<div class="acciones">
<a href="/crear">Aún no tienes una cuenta? obtener una. </a>
<a href="/olvide">Olvidaste tu Password?</a>
</div>

    </div> <!-- .contenedor-sm -->

</div>