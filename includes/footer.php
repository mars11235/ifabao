<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
    <div class="footer-logo-institucion">
        <img src="imagenes/imh.jpg" alt="IFABAO" class="footer-logo">
        <div>
                <h3>Bellas Artes Oruro</h3>
            <p>Instituto de Formación Artística</p>
        </div>
    </div>
</div>

<style>
.footer-logo-institucion {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.footer-logo {
    height: 50px;
    width: auto;
    border-radius: 5px;
}
</style>
            
            <div class="footer-section">
                <h4>Enlaces Rápidos</h4>
                <a href="index.php">Inicio</a>
                <a href="galeria.php">Galería</a>
                <a href="artistas.php">Artistas</a>
                <a href="contacto.php">Contacto</a>
            </div>
            
            <div class="footer-section">
                <h4>Contacto</h4>
                <p><i class="fas fa-map-marker-alt"></i> Oruro, Bolivia</p>
                <p><i class="fas fa-phone"></i> +591 XXX XXX XXX</p>
                <p><i class="fas fa-envelope"></i> info@ifabao.edu.bo</p>
            </div>
            
            <div class="footer-section">
                <h4>Síguenos</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 IFABAO - Instituto de Formación Artística Bellas Artes Oruro. Todos los derechos reservados.</p>
        </div>
    </div>

    <style>
        .footer {
            background: linear-gradient(135deg, var(--deep-plum), var(--charcoal));
            color: var(--soft-white);
            margin-top: auto;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 1.5rem 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3, .footer-section h4 {
            margin-bottom: 1rem;
            color: var(--soft-pink);
        }

        .footer-section a {
            display: block;
            color: var(--soft-white);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }

        .footer-section a:hover {
            color: var(--soft-pink);
            transform: translateX(5px);
        }

        .footer-logo {
            width: 50px;
            height: 50px;
            background: var(--accent-coral);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: var(--soft-mauve);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--accent-coral);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid var(--soft-mauve);
            padding-top: 1rem;
            text-align: center;
            color: var(--dusty-rose);
        }

        @media (max-width: 768px) {
            .footer-container {
                padding: 2rem 1rem 1rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    </style>
</footer>
</body>
</html>