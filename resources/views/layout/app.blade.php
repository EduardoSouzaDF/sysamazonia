<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="pt-br">

<head>
    <title>
        Sistema de Premiação Amazônia @yeld('title')
    </title>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/themes/light.css" />


    @vite(['resources/css/styles.css', 'resources/comp_themes/keenicons/styles.bundle.css', 'resources/css/app.css', 'resources/js/app.js', 'resources/comp_themes/ktui/ktui.min.js', 'resources/js/core.bundle.js'])
    @stack('styles')
</head>

<body class="@yield('body_class')">
    <!-- Theme Mode -->
    <script>
        document.documentElement.classList.add('light');
    </script>

    <style>
        .page-bg {
            background-image: url('{{ asset('images/bg-10.png') }}');
        }
    </style>
    @yield('content')


    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/shoelace-autoloader.js">
    </script>
    @stack('scripts')
</body>



</html>
