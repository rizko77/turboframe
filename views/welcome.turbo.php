<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TurboFrame - PHP Framework for Students & Developers</title>
    <meta name="description" content="TurboFrame: Framework PHP tercepat dan termudah untuk mahasiswa, pelajar, dan developer profesional. Built-in PWA, Nitrous Mode, dan dokumentasi lengkap.">
    <link rel="icon" type="image/x-icon" href="/logo.png">
    <link rel="canonical" href="https://turboframe.my.id/">
    
    <!-- Open Graph -->
    <meta property="og:title" content="TurboFrame - Lightning Fast PHP Framework">
    <meta property="og:description" content="Framework PHP tercepat untuk mahasiswa dan developer. PWA ready, Nitrous Mode, dokumentasi lengkap.">
    <meta property="og:url" content="https://turboframe.my.id/">
    <meta property="og:image" content="https://turboframe.my.id/logo.png">
    <meta property="og:type" content="website">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: #ffffff; 
            color: #171717; 
        }
    </style>

    <!-- PWA Support -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <link rel="apple-touch-icon" href="/assets/icon-192.png">

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered.'))
                    .catch(err => console.log('Service Worker failed:', err));
            });
        }
    </script>
</head>
<body class="antialiased font-sans">

    <div class="max-w-5xl mx-auto px-6">
        <header class="border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-6">
                <div class="flex justify-between items-center py-8">
                    <div class="flex items-center gap-2">
                        <a href="/" class="font-bold text-xl tracking-tighter uppercase">TURBOFRAME</a>
                    </div>

                    <nav class="hidden md:flex items-center gap-8 text-[13px] font-medium tracking-wide">
                        
                        <a href="https://turboframe.my.id/docs" target="_blank" class="text-gray-500 hover:text-black transition-colors">DOCS</a>
                        <a href="https://github.com/rizko77" target="_blank" class="text-gray-500 hover:text-black transition-colors">GITHUB</a>
                    </nav>

                    <button id="mobile-menu-button" class="md:hidden p-2 -mr-2 text-gray-600" aria-label="Toggle Menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>

                <div id="mobile-menu" class="hidden md:hidden border-t border-gray-50 py-6 space-y-4">
                    
                    <a href="https://turboframe.my.id/docs" target="_blank" class="block text-sm font-bold text-gray-500 hover:text-black uppercase tracking-widest">Docs</a>
                    <a href="https://github.com/rizko77" target="_blank" class="block text-sm font-bold text-gray-500 hover:text-black uppercase tracking-widest">GitHub</a>
                </div>
            </div>
        </header>

        <script>
            const menuBtn = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        </script>

        <main class="py-24 md:py-40">
            <div class="mb-6">
                <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">Version 1.0.3 Stable</span>
            </div>

            <h1 class="text-5xl md:text-6xl font-bold tracking-tighter text-black mb-6">
                Build fast. Stay simple.
            </h1>

            <p class="text-gray-600 text-lg md:text-xl max-w-xl mb-10 leading-relaxed">
                PHP framework designed for performance and clarity. 
                Focus on your code, not the configuration.
                Hidupp PHP!
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
                <a href="https://turboframe.my.id/docs" target="_blank" class="bg-black text-white px-6 py-3 rounded font-medium text-center hover:bg-gray-800 transition-all">
                    Get Started
                </a>
                <div class="flex items-center bg-white border border-gray-200 rounded px-5 py-3 font-mono text-sm text-gray-700">
                    <span class="opacity-30 mr-2">$</span> php lambo serve
                </div>
            </div>
        </main>

        <footer class="py-12 border-t border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Developed By</p>
                    <p class="text-sm font-medium text-gray-900">Rizko Imsar</p>
                </div>
                
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Performance</p>
                    <p class="text-sm font-mono text-gray-600 italic">
                        <?php echo round((microtime(true) - TURBO_START) * 1000, 2); ?>ms execution
                    </p>
                </div>

                <div class="md:text-right">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Source Code</p>
                    <a href="https://github.com/rizko77/turboframe" target="_blank" class="text-sm font-medium text-black hover:underline">github.com/rizko77/turboframe</a>
                </div>
            </div>
        </footer>
    </div>

</body>
</html>