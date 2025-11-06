/* IFABAO - JavaScript Optimizado para Responsividad */
class IFABAOResponsive {
    constructor() {
        this.touchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

        // No definimos isMobile/isTablet aquí; lo manejamos con matchMedia
        this.mediaQueries = {
            mobile: window.matchMedia('(max-width: 767px)'),
            tablet: window.matchMedia('(min-width: 768px) and (max-width: 991px)'),
            desktop: window.matchMedia('(min-width: 992px)')
        };

        this.init();
    }

    init() {
        this.setupViewport();
        this.setupTouchOptimizations();
        this.setupResponsiveImages();
        this.setupNavigation();
        this.setupPerformance();
        this.setupObservers();

        // Escuchar cambios de tamaño sin modificar estilos directamente
        this.bindMediaQueryListeners();
    }

    setupViewport() {
        const viewport = document.querySelector('meta[name="viewport"]');
        if (!viewport) return;

        // En móviles, permitir zoom táctil controlado
        if (this.mediaQueries.mobile.matches) {
            viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes');
        }
    }

    setupTouchOptimizations() {
        if (!this.touchDevice) return;

        // Habilitar eventos táctiles pasivos para mejor scroll
        document.addEventListener('touchstart', () => {}, { passive: true });

        // Prevenir doble-tap zoom
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });

        // Feedback visual en botones táctiles
        const tappableElements = document.querySelectorAll('.btn, .nav-link, .card, [role="button"]');
        tappableElements.forEach(el => {
            el.style.cursor = 'pointer';

            el.addEventListener('touchstart', () => {
                el.style.transition = 'transform 0.1s ease';
                el.style.transform = 'scale(0.98)';
            }, { passive: true });

            el.addEventListener('touchend', () => {
                el.style.transform = 'scale(1)';
            }, { passive: true });

            el.addEventListener('touchcancel', () => {
                el.style.transform = 'scale(1)';
            }, { passive: true });
        });
    }

    setupResponsiveImages() {
        // Lazy loading nativo
        if ('loading' in HTMLImageElement.prototype) {
            const lazyImages = document.querySelectorAll('img[data-src]');
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        } else {
            this.lazyLoadPolyfill(); // Fallback
        }

        // Optimización por dispositivo (opcional)
        const images = document.querySelectorAll('img:not([data-no-optimize])');
        images.forEach(img => this.optimizeImageForDevice(img));
    }

    optimizeImageForDevice(img) {
        if (this.mediaQueries.mobile.matches && img.src.includes('upload')) {
            try {
                const url = new URL(img.src);
                url.searchParams.set('quality', '70');
                url.searchParams.set('width', '600');
                img.src = url.toString();
            } catch (e) {
                console.warn('No se pudo optimizar imagen:', img.src);
            }
        }
    }

    setupNavigation() {
        const menuBtn = document.querySelector('.menu-btn');
        const nav = document.querySelector('.nav');

        if (!menuBtn || !nav) return;

        // Alternar menú móvil
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            nav.classList.toggle('nav-open');
            menuBtn.setAttribute('aria-expanded', nav.classList.contains('nav-open'));
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !menuBtn.contains(e.target)) {
                nav.classList.remove('nav-open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Cerrar menú al redimensionar (cuando pasa a desktop)
        this.mediaQueries.desktop.addListener((mql) => {
            if (mql.matches) {
                nav.classList.remove('nav-open');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    setupSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]:not([href="#"])');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                const target = document.querySelector(targetId);

                if (!target) return;

                const headerHeight = document.querySelector('.header')?.offsetHeight || 80;
                const targetPosition = target.offsetTop - headerHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Cerrar menú móvil tras navegar
                const nav = document.querySelector('.nav');
                if (nav && nav.classList.contains('nav-open')) {
                    nav.classList.remove('nav-open');
                    document.querySelector('.menu-btn')?.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    setupPerformance() {
        // Debounce para resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.handleResize();
            }, 150);
        });

        // Preconexión a recursos externos
        this.preconnectResources();
    }

    handleResize() {
        this.setupViewport(); // Actualiza viewport si es necesario
    }

    bindMediaQueryListeners() {
        // Puedes reaccionar a cambios de breakpoint si necesitas lógica específica
        Object.keys(this.mediaQueries).forEach(key => {
            this.mediaQueries[key].addListener(() => {
                // Ej: console.log(`Breakpoint cambiado a: ${key}`);
            });
        });
    }

    setupObservers() {
        // Animaciones al hacer scroll (entradas en viewport)
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.card, .section-title, .hero').forEach(el => {
                observer.observe(el);
            });
        }
    }

    preconnectResources() {
        const domains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://api.ifabao.com'
        ];

        domains.forEach(url => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = url;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    }

    lazyLoadPolyfill() {
        const images = Array.from(document.querySelectorAll('img[data-src]'));

        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        obs.unobserve(img);
                    }
                });
            });

            images.forEach(img => obs.observe(img));
        } else {
            // Scroll fallback
            const loadVisible = () => {
                images.forEach(img => {
                    const rect = img.getBoundingClientRect();
                    if (rect.top < window.innerHeight && rect.bottom >= 0) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                });
            };

            loadVisible();
            window.addEventListener('scroll', loadVisible);
            window.addEventListener('resize', loadVisible);
        }
    }
}

// Inicialización segura
document.addEventListener('DOMContentLoaded', () => {
    new IFABAOResponsive();

    // Carga diferida de funcionalidades no críticas
    setTimeout(() => {
        if ('connection' in navigator) {
            const conn = navigator.connection;
            if (conn.saveData || ['slow-2g', '2g'].includes(conn.effectiveType)) {
                document.body.classList.add('data-saver-mode');
            }
        }
    }, 1000);
});

// Service Worker para PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registrado:', reg.scope))
            .catch(err => console.error('Error al registrar SW:', err));
    });
}