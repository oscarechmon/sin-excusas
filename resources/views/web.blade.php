<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin Excusas - Centro Estético</title>
    <link href="{{ asset('voroz-web/assets/css/main.css') }}" rel="stylesheet">
</head>
<body>
    <div id="app">
        @include('components.nav')
        <main>
            <!-- Home Page Content -->
            <section class="hero">
                <div class="container">
                    <h1>Sin Excusas</h1>
                    <p>Centro Estético Profesional</p>
                    <a href="#contacto" class="cta-button">Contáctanos</a>
                </div>
            </section>
        </main>
        @include('components.footer')
    </div>
    <script src="{{ asset('voroz-web/assets/js/main.js') }}"></script>
</body>
</html>
