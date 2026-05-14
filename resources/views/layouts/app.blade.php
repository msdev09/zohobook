<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Zoho Books – Variance Report')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @stack('styles')
</head>

<body class="bg-[#f5f5f5] text-gray-800 min-h-screen text-[13px] leading-tight">

    <!-- Toast Container -->
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50">
        <div id="toast-container" class="flex w-full flex-col items-center space-y-4 sm:items-end"></div>
    </div>

    <!-- Laravel Session Flashes -->
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => showToast("{{ session('success') }}", 'success'));
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', () => showToast("{{ session('error') }}", 'error'));
        </script>
    @endif

    @yield('content')

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            const iconColor = type === 'success' ? 'text-green-400' : 'text-red-400';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';

            toast.className = `pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black/5 transition-all duration-300 transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2`;

            toast.innerHTML = `
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="shrink-0">
                            <i class="fa ${iconClass} ${iconColor} text-xl"></i>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900">${message}</p>
                        </div>
                        <div class="ml-4 flex shrink-0">
                            <button type="button" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="this.closest('.pointer-events-auto').remove()">
                                <span class="sr-only">Close</span>
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0', 'sm:translate-y-0', 'sm:translate-x-2');
            });

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>

    @stack('scripts')

</body>

</html>
