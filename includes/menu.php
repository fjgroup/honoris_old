<?php
// Incluir el archivo de configuracion global
require_once __DIR__ . '/../config.php'; // Sube un nivel para encontrar config.php en la raíz
?>

<header class="bg-blue-600 text-white p-4 shadow-md">
    <style>
        /* Para asegurar que el menú desplegable de Gestion Publica aparezca con hover en escritorio */
        @media (min-width: 768px) { /* Corresponde al breakpoint 'md' de Tailwind */
            #mainNav ul li.group:hover > #gestionPublicaDropdown {
                display: block;
            }
        }
    </style>

    <div class="container mx-auto flex justify-between items-center relative">
        <h1 class="text-xl font-bold"><a href="<?php echo $base_url; ?>index.php" class="text-white no-underline">Honoris</a></h1>

        <!-- Boton Hamburguesa para moviles -->
        <div class="md:hidden">
            <button id="mobileMenuButton" class="text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-4 6h16"></path>
                </svg>
            </button>
        </div>

        <nav id="mainNav" class="hidden md:flex md:items-center w-full md:w-auto absolute md:relative top-16 left-0 md:top-auto md:left-auto bg-blue-600 md:bg-transparent shadow-md md:shadow-none rounded-md md:rounded-none py-2 md:py-0">
            <ul class="flex flex-col md:flex-row md:space-x-4 w-full px-4 md:px-0">
                <li><a href="<?php echo $base_url; ?>index.php" class="hover:underline">Inicio</a></li>
                <li class="relative group">
                    <button id="gestionPublicaToggle" class="w-full text-left hover:underline flex items-center justify-between block py-2 md:py-0">
                        Gestion Publica
                        <svg class="w-4 h-4 ml-1 inline-block transform group-hover:rotate-180 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                     <ul id="gestionPublicaDropdown" class="md:absolute hidden bg-blue-700 text-white shadow-lg rounded-md mt-2 py-1 z-20 min-w-48 md:group-hover:block">
                        <li><a href="<?php echo $base_url; ?>calculator/leather_calculator.php" class="block px-4 py-2 hover:bg-blue-600">Calculadora de Refinado</a></li>
                        <li><a href="<?php echo $base_url; ?>calculator/crafting_calculator.php" class="block px-4 py-2 hover:bg-blue-600">Calculadora de Crafting</a></li>
                        <li><a href="<?php echo $base_url; ?>public_web/solicitudes_honoris.php" class="block px-4 py-2 hover:bg-blue-600">Solicitudes</a></li>
                        <li><a href="<?php echo $base_url; ?>public_web/propiedades_honoris.php" class="block px-4 py-2 hover:bg-blue-600">Edificios Asociados</a></li>
                         </ul>
                </li>
                <li><a href="<?php echo $base_url; ?>admin/index.php" class="hover:underline font-bold block py-2 md:py-0">Admin</a></li>
            </ul>
        </nav>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mainNav = document.getElementById('mainNav');
    const gestionPublicaToggle = document.getElementById('gestionPublicaToggle');
    const gestionPublicaDropdown = document.getElementById('gestionPublicaDropdown');

    if (mobileMenuButton && mainNav) {
        mobileMenuButton.addEventListener('click', function () {
            mainNav.classList.toggle('hidden');
            // Opcional: Cambiar el icono de hamburguesa a X y viceversa
            // Para esto, el SVG del boton tendria dos paths y se alternaria su visibilidad.
        });
    }

    if (gestionPublicaToggle && gestionPublicaDropdown) {
        gestionPublicaToggle.addEventListener('click', function (event) {
            event.stopPropagation(); // Evitar que el clic se propague al documento si se anade un listener para cerrar al hacer clic fuera
            gestionPublicaDropdown.classList.toggle('hidden');
            // En pantallas md y mayores, el group-hover:block de Tailwind maneja el hover.
            // Para movil o si se prefiere click siempre, este toggle es necesario.
            // Si quieres que solo sea por click en todas las pantallas, quita 'md:group-hover:block' del ul.
        });

        // Opcional: Cerrar el dropdown de Gestion Publica si se hace clic fuera de el
        document.addEventListener('click', function (event) {
            if (gestionPublicaDropdown && !gestionPublicaDropdown.classList.contains('hidden')) {
                if (!gestionPublicaToggle.contains(event.target) && !gestionPublicaDropdown.contains(event.target)) {
                    gestionPublicaDropdown.classList.add('hidden');
                }
            }
        });
    }
});
</script>