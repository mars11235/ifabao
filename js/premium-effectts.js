// Efectos visuales premium para IFABAO
class IFABAOPremium {
  constructor() {
    this.initParticles();
    this.initSmoothScrolling();
    this.initParallax();
    this.initCounterAnimation();
  }

  initParticles() {
    // Efecto de partículas para fondo
    const canvas = document.createElement('canvas');
    canvas.className = 'particles-container';
    document.body.prepend(canvas);
    
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    const particles = [];
    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
      particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        size: Math.random() * 2 + 1,
        speedX: Math.random() * 0.5 - 0.25,
        speedY: Math.random() * 0.5 - 0.25,
        color: `rgba(255, 107, 53, ${Math.random() * 0.5})`
      });
    }
    
    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      
      particles.forEach(particle => {
        particle.x += particle.speedX;
        particle.y += particle.speedY;
        
        if (particle.x > canvas.width) particle.x = 0;
        if (particle.x < 0) particle.x = canvas.width;
        if (particle.y > canvas.height) particle.y = 0;
        if (particle.y < 0) particle.y = canvas.height;
        
        ctx.beginPath();
        ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        ctx.fillStyle = particle.color;
        ctx.fill();
      });
      
      requestAnimationFrame(animate);
    }
    
    animate();
    
    window.addEventListener('resize', () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    });
  }

  initSmoothScrolling() {
    // Scroll suave con offset para header
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          const headerHeight = document.querySelector('.header-premium').offsetHeight;
          const targetPosition = target.offsetTop - headerHeight;
          
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  }

  initParallax() {
    // Efecto parallax para elementos
    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      const parallaxElements = document.querySelectorAll('.parallax');
      
      parallaxElements.forEach(element => {
        const speed = element.dataset.speed || 0.5;
        element.style.transform = `translateY(${scrolled * speed}px)`;
      });
    });
  }

  initCounterAnimation() {
    // Animación de contadores para estadísticas
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200;
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          const target = +counter.getAttribute('data-target');
          const count = +counter.innerText;
          const increment = target / speed;
          
          if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(() => this.animateCounter(counter, target, speed), 1);
          }
        }
      });
    });
    
    counters.forEach(counter => observer.observe(counter));
  }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
  new IFABAOPremium();
  
  // Efecto header al hacer scroll
  window.addEventListener('scroll', function() {
    const header = document.getElementById('mainHeader');
    if (window.scrollY > 100) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });
});