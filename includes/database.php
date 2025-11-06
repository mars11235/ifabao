<?php
class Database {
    private $conn;
    private $usando_demo = false;

    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, 
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            $this->conn = null;
            $this->usando_demo = true;
            error_log("Error de conexión a BD IFABAO: " . $e->getMessage());
        }
    }

    // =========================================================================
    // MÉTODOS DE USUARIOS
    // =========================================================================
    public function login($correo, $password) {
        if (!$this->conn) {
            return $this->loginDemo($correo, $password);
        }

        try {
            $sql = "SELECT id, nombre, correo, password, tipo, estado, intentos_login 
                    FROM usuarios 
                    WHERE correo = :correo AND estado IN ('activo', 'bloqueado')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                if ($usuario['estado'] === 'bloqueado') {
                    return ['error' => 'Cuenta bloqueada por seguridad.'];
                }
                
                if ($usuario['intentos_login'] >= 5) {
                    $this->bloquearUsuario($usuario['id']);
                    return ['error' => 'Demasiados intentos fallidos. Cuenta bloqueada.'];
                }
                
                if (password_verify($password, $usuario['password'])) {
                    $this->resetearIntentosLogin($usuario['id']);
                    $this->registrarAcceso($usuario['id']);
                    
                    unset($usuario['password']);
                    unset($usuario['intentos_login']);
                    return $usuario;
                } else {
                    $this->incrementarIntentosLogin($usuario['id']);
                    return false;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return $this->loginDemo($correo, $password);
        }
    }

    private function incrementarIntentosLogin($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET intentos_login = intentos_login + 1 WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error incrementando intentos: " . $e->getMessage());
        }
    }

    private function resetearIntentosLogin($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET intentos_login = 0, ultimo_acceso = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error reseteando intentos: " . $e->getMessage());
        }
    }

    private function bloquearUsuario($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET estado = 'bloqueado', fecha_bloqueo = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error bloqueando usuario: " . $e->getMessage());
        }
    }

    private function registrarAcceso($usuario_id) {
        try {
            $sql = "INSERT INTO accesos_usuario (usuario_id, fecha_acceso, ip) VALUES (:usuario_id, NOW(), :ip)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA', PDO::PARAM_STR);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Error registrando acceso: " . $e->getMessage());
        }
    }

    public function registrarUsuario($datos) {
        if (!$this->conn) {
            return $this->registrarUsuarioDemo($datos);
        }

        try {
            // Verificar si el correo ya existe
            $sqlCheck = "SELECT id FROM usuarios WHERE correo = :correo";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            $stmtCheck->execute();
            
            if ($stmtCheck->fetch()) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado'
                ];
            }
            
            // Insertar nuevo usuario
            $sql = "INSERT INTO usuarios (nombre, correo, password, tipo, telefono, ciudad, estado) 
                    VALUES (:nombre, :correo, :password, :tipo, :telefono, :ciudad, 'activo')";
            
            $stmt = $this->conn->prepare($sql);
            $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':nombre', $datos['nombre'], PDO::PARAM_STR);
            $stmt->bindParam(':correo', $datos['correo'], PDO::PARAM_STR);
            $stmt->bindParam(':password', $passwordHash, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $datos['tipo'], PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $datos['telefono'], PDO::PARAM_STR);
            $stmt->bindParam(':ciudad', $datos['ciudad'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $usuario_id = $this->conn->lastInsertId();
                
                // Si es artista, crear registro en tabla artistas
                if ($datos['tipo'] === 'artista') {
                    $this->crearArtista($usuario_id, $datos['nombre']);
                }
                
                return [
                    'success' => true,
                    'id' => $usuario_id,
                    'message' => 'Usuario registrado exitosamente'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error al registrar usuario'
            ];
            
        } catch (Exception $e) {
            error_log("Error registrando usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error de base de datos'
            ];
        }
    }

    private function crearArtista($usuario_id, $nombre) {
        try {
            $sql = "INSERT INTO artistas (usuario_id, nombre_artistico, fecha_registro) VALUES (:usuario_id, :nombre, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creando artista: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // MÉTODOS DE OBRAS
    // =========================================================================
    public function obtenerObraCompleta($id) {
        if (!$this->conn) {
            return $this->obtenerObraDemo($id);
        }

        try {
            $sql = "SELECT o.*, a.nombre_artistico as artista_nombre, a.biografia as artista_biografia,
                           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                    FROM obras o 
                    LEFT JOIN artistas a ON o.artista_id = a.id 
                    LEFT JOIN obras_categorias oc ON o.id = oc.obra_id 
                    LEFT JOIN categorias c ON oc.categoria_id = c.id 
                    WHERE o.id = :id 
                    GROUP BY o.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $obra = $stmt->fetch();
            
            if ($obra) {
                $this->incrementarVistas($id);
                return $obra;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error obteniendo obra: " . $e->getMessage());
            return $this->obtenerObraDemo($id);
        }
    }

    public function obtenerObras($limite = 8, $pagina = 1, $categoria_id = null, $artista_id = null) {
        if (!$this->conn) {
            return $this->obtenerObrasDemo($limite);
        }

        try {
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT o.*, a.nombre_artistico as artista_nombre,
                           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                    FROM obras o 
                    LEFT JOIN artistas a ON o.artista_id = a.id 
                    LEFT JOIN obras_categorias oc ON o.id = oc.obra_id 
                    LEFT JOIN categorias c ON oc.categoria_id = c.id 
                    WHERE o.estado = 'disponible'";
            
            $params = [];
            
            if ($categoria_id) {
                $sql .= " AND EXISTS (SELECT 1 FROM obras_categorias WHERE obra_id = o.id AND categoria_id = :categoria_id)";
                $params[':categoria_id'] = $categoria_id;
            }
            
            if ($artista_id) {
                $sql .= " AND o.artista_id = :artista_id";
                $params[':artista_id'] = $artista_id;
            }
            
            $sql .= " GROUP BY o.id ORDER BY o.destacada DESC, o.fecha_publicacion DESC LIMIT :limite OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo obras: " . $e->getMessage());
            return $this->obtenerObrasDemo($limite);
        }
    }

    public function agregarObra($artista_id, $datos, $imagen_path) {
        if (!$this->conn) {
            return true;
        }

        try {
            $this->conn->beginTransaction();
            
            $sql = "INSERT INTO obras (artista_id, titulo, descripcion, precio, tecnica, dimensiones, ano_creacion, imagen, estado, fecha_publicacion) 
                    VALUES (:artista_id, :titulo, :descripcion, :precio, :tecnica, :dimensiones, :ano_creacion, :imagen, 'revision', NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':artista_id', $artista_id, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $datos['titulo'], PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $datos['descripcion'], PDO::PARAM_STR);
            $stmt->bindParam(':precio', $datos['precio'], PDO::PARAM_STR);
            $stmt->bindParam(':tecnica', $datos['tecnica'], PDO::PARAM_STR);
            $stmt->bindParam(':dimensiones', $datos['dimensiones'], PDO::PARAM_STR);
            $stmt->bindParam(':ano_creacion', $datos['ano_creacion'], PDO::PARAM_INT);
            $stmt->bindParam(':imagen', $imagen_path, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $obra_id = $this->conn->lastInsertId();
                
                if (isset($datos['categoria_id']) && $datos['categoria_id'] > 0) {
                    $this->asignarCategoriaObra($obra_id, $datos['categoria_id']);
                }
                
                $this->conn->commit();
                return $obra_id;
            }
            
            $this->conn->rollBack();
            return false;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error agregando obra: " . $e->getMessage());
            return false;
        }
    }

    private function asignarCategoriaObra($obra_id, $categoria_id) {
        try {
            $sql = "INSERT INTO obras_categorias (obra_id, categoria_id) VALUES (:obra_id, :categoria_id)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':obra_id', $obra_id, PDO::PARAM_INT);
            $stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error asignando categoría: " . $e->getMessage());
            return false;
        }
    }

    private function incrementarVistas($obra_id) {
        try {
            $sql = "UPDATE obras SET vistas = COALESCE(vistas, 0) + 1 WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $obra_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            // No crítico
        }
    }

    // =========================================================================
    // MÉTODOS DE ARTISTAS - CORREGIDOS
    // =========================================================================

    /**
     * Obtener artista por ID de usuario
     */
    public function obtenerArtistaPorUsuarioId($usuario_id) {
        if (!$this->conn) {
            return $this->obtenerArtistaDemoPorUsuarioId($usuario_id);
        }

        try {
            $sql = "SELECT a.*, u.correo, u.telefono, u.ciudad, u.nombre as nombre_completo,
                           COUNT(o.id) as total_obras
                    FROM artistas a 
                    INNER JOIN usuarios u ON a.usuario_id = u.id 
                    LEFT JOIN obras o ON a.id = o.artista_id 
                    WHERE a.usuario_id = :usuario_id 
                    GROUP BY a.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo artista por usuario: " . $e->getMessage());
            return $this->obtenerArtistaDemoPorUsuarioId($usuario_id);
        }
    }

    /**
     * Obtener artista por ID - MÉTODO NUEVO CORREGIDO
     */
    public function obtenerArtistaPorId($artista_id) {
        if (!$this->conn) {
            return $this->obtenerArtistaDemo($artista_id);
        }

        try {
            $sql = "SELECT a.*, 
                           u.nombre as nombre_completo,
                           u.correo,
                           u.telefono,
                           u.ciudad,
                           COUNT(o.id) as total_obras,
                           GROUP_CONCAT(DISTINCT o.tecnica SEPARATOR ', ') as tecnicas
                    FROM artistas a 
                    INNER JOIN usuarios u ON a.usuario_id = u.id 
                    LEFT JOIN obras o ON a.id = o.artista_id AND o.estado = 'disponible'
                    WHERE a.id = :id 
                    GROUP BY a.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $artista_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $artista = $stmt->fetch();
            
            if ($artista) {
                // Procesar técnicas para obtener la principal
                $tecnicas = explode(', ', $artista['tecnicas'] ?? '');
                $artista['tecnica_principal'] = $tecnicas[0] ?? 'Arte Visual';
                $artista['tecnica'] = $artista['tecnica_principal'];
            }
            
            return $artista;
        } catch (Exception $e) {
            error_log("Error obteniendo artista por ID: " . $e->getMessage());
            return $this->obtenerArtistaDemo($artista_id);
        }
    }

    /**
     * Verificar si usuario tiene perfil de artista - MÉTODO NUEVO
     */
    public function verificarPerfilArtista($usuario_id) {
        if (!$this->conn) {
            return $this->obtenerArtistaDemoPorUsuarioId($usuario_id) !== null;
        }

        try {
            $sql = "SELECT id FROM artistas WHERE usuario_id = :usuario_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error verificando perfil artista: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear perfil de artista - MÉTODO NUEVO
     */
    public function crearPerfilArtista($usuario_id, $datos_artista) {
        if (!$this->conn) {
            return rand(100, 999); // ID demo
        }

        try {
            $sql = "INSERT INTO artistas (usuario_id, nombre_artistico, biografia, tecnica_principal, fecha_registro) 
                    VALUES (:usuario_id, :nombre_artistico, :biografia, :tecnica_principal, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre_artistico', $datos_artista['nombre_artistico'], PDO::PARAM_STR);
            $stmt->bindParam(':biografia', $datos_artista['biografia'], PDO::PARAM_STR);
            $stmt->bindParam(':tecnica_principal', $datos_artista['tecnica_principal'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creando perfil artista: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener obras por artista
     */
    public function obtenerObrasPorArtista($artista_id, $solo_disponibles = true) {
        if (!$this->conn) {
            return $this->obtenerObrasDemoPorArtista($artista_id);
        }

        try {
            $sql = "SELECT o.*, 
                           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                    FROM obras o 
                    LEFT JOIN obras_categorias oc ON o.id = oc.obra_id 
                    LEFT JOIN categorias c ON oc.categoria_id = c.id 
                    WHERE o.artista_id = :artista_id";
            
            if ($solo_disponibles) {
                $sql .= " AND o.estado = 'disponible'";
            }
            
            $sql .= " GROUP BY o.id ORDER BY o.fecha_publicacion DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':artista_id', $artista_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo obras por artista: " . $e->getMessage());
            return $this->obtenerObrasDemoPorArtista($artista_id);
        }
    }

    /**
     * Verificar disponibilidad de obra
     */
    public function verificarDisponibilidadObra($obra_id) {
        if (!$this->conn) {
            return true; // En demo, siempre disponible
        }

        try {
            $sql = "SELECT estado FROM obras WHERE id = :id AND estado = 'disponible'";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $obra_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error verificando disponibilidad: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Obtener obra por ID
     */
    public function obtenerObraPorId($obra_id) {
        if (!$this->conn) {
            return $this->obtenerObraDemo($obra_id);
        }

        try {
            $sql = "SELECT o.*, a.nombre_artistico as artista_nombre
                    FROM obras o 
                    LEFT JOIN artistas a ON o.artista_id = a.id 
                    WHERE o.id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $obra_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo obra por ID: " . $e->getMessage());
            return $this->obtenerObraDemo($obra_id);
        }
    }

    /**
     * Obtener lista de artistas
     */
    public function obtenerArtistas($limite = 12, $pagina = 1) {
        if (!$this->conn) {
            return $this->obtenerArtistasDemo();
        }

        try {
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT a.*, 
                           COUNT(o.id) as total_obras,
                           u.nombre as nombre_completo
                    FROM artistas a 
                    INNER JOIN usuarios u ON a.usuario_id = u.id 
                    LEFT JOIN obras o ON a.id = o.artista_id AND o.estado = 'disponible'
                    WHERE u.estado = 'activo'
                    GROUP BY a.id 
                    ORDER BY total_obras DESC 
                    LIMIT :limite OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo artistas: " . $e->getMessage());
            return $this->obtenerArtistasDemo();
        }
    }

    /**
     * Obtener artista por ID (alias para compatibilidad)
     */
    public function obtenerArtista($id) {
        return $this->obtenerArtistaPorId($id);
    }

    // =========================================================================
    // MÉTODOS DE CATEGORÍAS
    // =========================================================================
    public function obtenerCategorias() {
        if (!$this->conn) {
            return $this->obtenerCategoriasDemo();
        }

        try {
            $sql = "SELECT * FROM categorias WHERE estado = 'activa' ORDER BY nombre";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo categorías: " . $e->getMessage());
            return $this->obtenerCategoriasDemo();
        }
    }

    // =========================================================================
    // MÉTODOS DEL CARRITO
    // =========================================================================
    public function agregarAlCarrito($usuario_id, $obra_id, $cantidad = 1) {
        if (!$this->conn) {
            return true;
        }

        try {
            // Verificar si ya existe en el carrito
            $sqlCheck = "SELECT id, cantidad FROM carrito WHERE usuario_id = :usuario_id AND obra_id = :obra_id";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmtCheck->bindParam(':obra_id', $obra_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            
            $existente = $stmtCheck->fetch();
            
            if ($existente) {
                $sql = "UPDATE carrito SET cantidad = cantidad + :cantidad WHERE id = :id";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
                $stmt->bindParam(':id', $existente['id'], PDO::PARAM_INT);
            } else {
                $sql = "INSERT INTO carrito (usuario_id, obra_id, cantidad, fecha_agregado) VALUES (:usuario_id, :obra_id, :cantidad, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->bindParam(':obra_id', $obra_id, PDO::PARAM_INT);
                $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            }
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error agregando al carrito: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerCarrito($usuario_id) {
        if (!$this->conn) {
            return $this->obtenerCarritoDemo();
        }

        try {
            $sql = "SELECT c.*, o.titulo, o.precio, o.imagen, o.estado as obra_estado, a.nombre_artistico as artista
                    FROM carrito c 
                    INNER JOIN obras o ON c.obra_id = o.id 
                    INNER JOIN artistas a ON o.artista_id = a.id 
                    WHERE c.usuario_id = :usuario_id 
                    ORDER BY c.fecha_agregado DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo carrito: " . $e->getMessage());
            return $this->obtenerCarritoDemo();
        }
    }

    // =========================================================================
    // MÉTODOS DEMO (fallback)
    // =========================================================================
    private function loginDemo($correo, $password) {
        $usuariosDemo = [
            [
                'id' => 1,
                'nombre' => 'Administrador IFABAO',
                'correo' => 'admin@ifabao.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'tipo' => 'admin',
                'estado' => 'activo'
            ],
            [
                'id' => 2,
                'nombre' => 'Juan Pérez Artista',
                'correo' => 'artista@ifabao.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'tipo' => 'artista',
                'estado' => 'activo'
            ],
            [
                'id' => 3,
                'nombre' => 'María López Compradora',
                'correo' => 'comprador@ifabao.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'tipo' => 'comprador',
                'estado' => 'activo'
            ]
        ];
        
        foreach ($usuariosDemo as $usuario) {
            if ($usuario['correo'] === $correo && password_verify($password, $usuario['password'])) {
                unset($usuario['password']);
                return $usuario;
            }
        }
        
        return false;
    }

    private function registrarUsuarioDemo($datos) {
        return [
            'success' => true,
            'id' => rand(100, 999),
            'message' => 'Usuario registrado exitosamente en modo demostración'
        ];
    }

    private function obtenerObraDemo($id) {
        $obrasDemo = [
            [
                'id' => 1,
                'titulo' => 'Paisaje Andino',
                'descripcion' => 'Hermosa pintura que captura la esencia de los Andes bolivianos',
                'precio' => 2500.00,
                'imagen' => 'images/obra1.jpg',
                'artista_nombre' => 'Juan Pérez Artista',
                'artista_id' => 1,
                'tecnica' => 'Óleo sobre lienzo',
                'dimensiones' => '80x60 cm',
                'ano_creacion' => 2023,
                'estado' => 'disponible',
                'vistas' => 45,
                'categorias' => 'Paisaje, Tradicional'
            ],
            [
                'id' => 2,
                'titulo' => 'Retrato Indígena',
                'descripcion' => 'Retrato que honra la cultura indígena boliviana',
                'precio' => 1800.00,
                'imagen' => 'images/obra2.jpg',
                'artista_nombre' => 'María López',
                'artista_id' => 2,
                'tecnica' => 'Acrílico',
                'dimensiones' => '60x50 cm',
                'ano_creacion' => 2023,
                'estado' => 'disponible',
                'vistas' => 32,
                'categorias' => 'Retrato'
            ]
        ];
        
        foreach ($obrasDemo as $obra) {
            if ($obra['id'] == $id) {
                return $obra;
            }
        }
        
        return null;
    }

    private function obtenerObrasDemo($limite = 8) {
    return [
        [
            'id' => 1,
            'titulo' => 'Paisaje Andino',
            'precio' => 2500.00,
            'imagen' => 'images/obra1.jpg',
            'artista_nombre' => 'Juan Pérez Artista',
            'artista_id' => 1, // ✅ AGREGADO
            'tecnica' => 'Óleo',
            'estado' => 'disponible',
            'categorias' => 'Paisaje'
        ],
        [
            'id' => 2,
            'titulo' => 'Retrato Indígena',
            'precio' => 1800.00,
            'imagen' => 'images/obra2.jpg',
            'artista_nombre' => 'María López',
            'artista_id' => 2, // ✅ AGREGADO
            'tecnica' => 'Acrílico',
            'estado' => 'disponible',
            'categorias' => 'Retrato'
        ],
            [
                'id' => 3,
                'titulo' => 'Cultura Aymara',
                'precio' => 3200.00,
                'imagen' => 'images/obra3.jpg',
                'artista_nombre' => 'Carlos Rodríguez',
                'tecnica' => 'Escultura',
                'estado' => 'disponible',
                'categorias' => 'Escultura'
            ],
            [
                'id' => 4,
                'titulo' => 'Textiles Andinos',
                'precio' => 1500.00,
                'imagen' => 'images/obra4.jpg',
                'artista_nombre' => 'Ana Martínez',
                'tecnica' => 'Arte Textil',
                'estado' => 'disponible',
                'categorias' => 'Arte Textil'
            ]
        ];
    }

    private function obtenerArtistasDemo() {
        return [
            [
                'id' => 1,
                'nombre_artistico' => 'Juan Pérez Artista',
                'biografia' => 'Artista boliviano especializado en paisajes andinos con más de 10 años de experiencia en técnicas tradicionales.',
                'imagen' => 'images/artista1.jpg',
                'total_obras' => 12,
                'tecnica_principal' => 'Pintura al Óleo',
                'nombre_completo' => 'Juan Pérez',
                'usuario_id' => 2
            ],
            [
                'id' => 2,
                'nombre_artistico' => 'María López',
                'biografia' => 'Fotógrafa y pintora contemporánea enfocada en retratos que capturan la esencia cultural boliviana.',
                'imagen' => 'images/artista2.jpg',
                'total_obras' => 8,
                'tecnica_principal' => 'Fotografía Artística',
                'nombre_completo' => 'María López',
                'usuario_id' => 3
            ],
            [
                'id' => 3,
                'nombre_artistico' => 'Carlos Rodríguez',
                'biografia' => 'Escultor especializado en madera y piedra, inspirándose en las tradiciones indígenas locales.',
                'imagen' => 'images/artista3.jpg',
                'total_obras' => 15,
                'tecnica_principal' => 'Escultura',
                'nombre_completo' => 'Carlos Rodríguez',
                'usuario_id' => 4
            ],
            [
                'id' => 4,
                'nombre_artistico' => 'Ana Martínez',
                'biografia' => 'Artista textil que fusiona técnicas ancestrales con diseños contemporáneos.',
                'imagen' => 'images/artista4.jpg',
                'total_obras' => 6,
                'tecnica_principal' => 'Arte Textil',
                'nombre_completo' => 'Ana Martínez',
                'usuario_id' => 5
            ]
        ];
    }

    private function obtenerArtistaDemo($id) {
        $artistas = $this->obtenerArtistasDemo();
        foreach ($artistas as $artista) {
            if ($artista['id'] == $id) {
                return $artista;
            }
        }
        return null;
    }

    private function obtenerCategoriasDemo() {
        return [
            ['id' => 1, 'nombre' => 'Pintura', 'descripcion' => 'Obras realizadas con técnicas de pintura'],
            ['id' => 2, 'nombre' => 'Escultura', 'descripcion' => 'Esculturas en diversos materiales'],
            ['id' => 3, 'nombre' => 'Fotografía', 'descripcion' => 'Fotografía artística y documental'],
            ['id' => 4, 'nombre' => 'Arte Digital', 'descripcion' => 'Obras creadas con herramientas digitales'],
            ['id' => 5, 'nombre' => 'Arte Textil', 'descripcion' => 'Tejidos y bordados artísticos'],
            ['id' => 6, 'nombre' => 'Cerámica', 'descripcion' => 'Piezas de cerámica y alfarería artística']
        ];
    }

    private function obtenerCarritoDemo() {
        return [
            [
                'id' => 1,
                'obra_id' => 1,
                'titulo' => 'Paisaje Andino',
                'precio' => 2500.00,
                'imagen' => 'images/obra1.jpg',
                'artista' => 'Juan Pérez Artista',
                'cantidad' => 1
            ]
        ];
    }

    private function obtenerArtistaDemoPorUsuarioId($usuario_id) {
        $artistasDemo = $this->obtenerArtistasDemo();
        foreach ($artistasDemo as $artista) {
            if ($artista['usuario_id'] == $usuario_id) {
                return $artista;
            }
        }
        return null;
    }

    private function obtenerObrasDemoPorArtista($artista_id) {
        $obras = $this->obtenerObrasDemo(20);
        return array_filter($obras, function($obra) use ($artista_id) {
            return $obra['artista_id'] == $artista_id;
        });
    }

 public function obtenerObrasRelacionadas($obra_actual, $limite = 4) {
        if (!$this->conn) {
            return $this->obtenerObrasRelacionadasDemo($obra_actual, $limite);
        }

        try {
            // Verificar que tenemos artista_id válido
            if (!isset($obra_actual['artista_id']) || $obra_actual['artista_id'] <= 0) {
                return [];
            }

            $sql = "SELECT o.*, a.nombre_artistico as artista_nombre,
                           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                    FROM obras o 
                    LEFT JOIN artistas a ON o.artista_id = a.id 
                    LEFT JOIN obras_categorias oc ON o.id = oc.obra_id 
                    LEFT JOIN categorias c ON oc.categoria_id = c.id 
                    WHERE o.artista_id = :artista_id 
                    AND o.id != :obra_id
                    AND o.estado = 'disponible'
                    GROUP BY o.id 
                    ORDER BY RAND() 
                    LIMIT :limite";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':artista_id', $obra_actual['artista_id'], PDO::PARAM_INT);
            $stmt->bindValue(':obra_id', $obra_actual['id'], PDO::PARAM_INT);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error obteniendo obras relacionadas: " . $e->getMessage());
            return $this->obtenerObrasRelacionadasDemo($obra_actual, $limite);
        }
    }

    /**
     * Obtener obras relacionadas demo
     */
    private function obtenerObrasRelacionadasDemo($obra_actual, $limite = 4) {
        $todas_obras = $this->obtenerObrasDemo(20);
        
        // Filtrar obras del mismo artista (si existe artista_id)
        if (isset($obra_actual['artista_id']) && $obra_actual['artista_id'] > 0) {
            $obras_mismo_artista = array_filter($todas_obras, function($obra) use ($obra_actual) {
                return $obra['artista_id'] == $obra_actual['artista_id'] && 
                       $obra['id'] != $obra_actual['id'];
            });
            
            if (!empty($obras_mismo_artista)) {
                return array_slice($obras_mismo_artista, 0, $limite);
            }
        }
        
        // Si no hay obras del mismo artista, devolver obras aleatorias
        $obras_aleatorias = array_filter($todas_obras, function($obra) use ($obra_actual) {
            return $obra['id'] != $obra_actual['id'];
        });
        
        shuffle($obras_aleatorias);
        return array_slice($obras_aleatorias, 0, $limite);
    }

    // ... (el resto del código permanece igual)

    /**
     * Obtener obra completa con manejo seguro de datos
     */
    public function obtenerObraCompleta($id) {
        if (!$this->conn) {
            return $this->obtenerObraDemo($id);
        }

        try {
            $sql = "SELECT o.*, a.nombre_artistico as artista_nombre, a.biografia as artista_biografia,
                           GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ', ') as categorias
                    FROM obras o 
                    LEFT JOIN artistas a ON o.artista_id = a.id 
                    LEFT JOIN obras_categorias oc ON o.id = oc.obra_id 
                    LEFT JOIN categorias c ON oc.categoria_id = c.id 
                    WHERE o.id = :id 
                    GROUP BY o.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $obra = $stmt->fetch();
            
            if ($obra) {
                // Asegurar que tenemos todos los campos necesarios
                $obra = $this->completarDatosObra($obra);
                $this->incrementarVistas($id);
                return $obra;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error obteniendo obra: " . $e->getMessage());
            return $this->obtenerObraDemo($id);
        }
    }

    /**
     * Completar datos faltantes en obra
     */
    private function completarDatosObra($obra) {
        // Campos por defecto
        $campos_por_defecto = [
            'artista_id' => 0,
            'artista_nombre' => 'Artista IFABAO',
            'artista_biografia' => 'Artista talentoso de la comunidad IFABAO',
            'tecnica' => 'No especificada',
            'dimensiones' => 'No especificadas',
            'ano_creacion' => date('Y'),
            'estado' => 'disponible',
            'vistas' => 0,
            'categorias' => 'Sin categorías',
            'descripcion' => 'Obra de arte exclusiva de IFABAO',
            'destacada' => false
        ];
        
        foreach ($campos_por_defecto as $campo => $valor) {
            if (!isset($obra[$campo]) || empty($obra[$campo])) {
                $obra[$campo] = $valor;
            }
        }
        
        return $obra;
    }



    // =========================================================================
    // MÉTODOS DE VERIFICACIÓN
    // =========================================================================
    public function verificarConexion() {
        return $this->conn !== null;
    }

    public function obtenerEstadisticas() {
        if (!$this->conn) {
            return [
                'total_obras' => 6,
                'total_artistas' => 4,
                'total_usuarios' => 6,
                'total_categorias' => 6,
                'total_ventas' => 15,
                'ingresos_totales' => 45000
            ];
        }

        try {
            $sql = "SELECT 
                (SELECT COUNT(*) FROM obras WHERE estado = 'disponible') as total_obras,
                (SELECT COUNT(*) FROM artistas a INNER JOIN usuarios u ON a.usuario_id = u.id WHERE u.estado = 'activo') as total_artistas,
                (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') as total_usuarios,
                (SELECT COUNT(*) FROM categorias WHERE estado = 'activa') as total_categorias,
                (SELECT COUNT(*) FROM ventas WHERE estado = 'completada') as total_ventas,
                (SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'completada') as ingresos_totales";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [
                'total_obras' => 0,
                'total_artistas' => 0,
                'total_usuarios' => 0,
                'total_categorias' => 0,
                'total_ventas' => 0,
                'ingresos_totales' => 0
            ];
        }
    }
}
?>