

<?php
/**
 * ProductoModel
 *  - Catálogo de productos (id, nombre, categoria_id, código)
 *  - Genera un código interno único “PROD-0001”, “PROD-0002”, …
 */
require_once __DIR__ . '/Conexion.php';

class ProductoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::getInstance()->getConnection();
    }

    /* ============================================================
       Listar catálogo completo (con nombre de categoría)
       ============================================================*/
    public function listar(): array
    {
        $sql = "SELECT p.*, c.nombre AS categoria
                FROM productos p
                JOIN categorias c ON c.id = p.categoria_id
                ORDER BY p.nombre";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ============================================================
       Crear producto (nombre + categoría) → devuelve id insertado
       ============================================================*/
    public function crear(string $nombre, int $categoriaId): int
    {
        $codigo = $this->generarCodigoUnico();

        $sql = "INSERT INTO productos (nombre, categoria_id, codigo)
                VALUES (:n, :c, :cod)";
        $ok  = $this->db->prepare($sql)->execute([
            ':n'   => $nombre,
            ':c'   => $categoriaId,
            ':cod' => $codigo
        ]);

        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    /* ============================================================
       Obtener código por producto_id
       ============================================================*/
    public function obtenerCodigo(int $productoId): string
    {
        $st = $this->db->prepare("SELECT codigo FROM productos WHERE id = ?");
        $st->execute([$productoId]);
        return $st->fetchColumn() ?: '';
    }

    /* ============================================================
       Métodos privados
       ============================================================*/
    private function generarCodigoUnico(): string
    {
        do {
            $codigo = 'PROD-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while ($this->existeCodigo($codigo));
        return $codigo;
    }

    private function existeCodigo(string $codigo): bool
    {
        $st = $this->db->prepare("SELECT 1 FROM productos WHERE codigo = ? LIMIT 1");
        $st->execute([$codigo]);
        return (bool)$st->fetchColumn();
    }

        public function listarProductosActivosConStock(): array {
        $db = Conexion::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT p.id, p.nombre, p.codigo, i.cantidad_stock
            FROM productos p
            JOIN inventario i ON p.id = i.producto_id
            WHERE i.cantidad_stock > 0 -- O puedes quitar esta condición si quieres mostrar todos los productos
            ORDER BY p.nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
 * Verifica si ya existe un producto con el mismo nombre y categoría
 */
/**
 * Verifica si ya existe un producto con el mismo nombre (sin importar la categoría)
 */
/**
 * Verifica si ya existe un producto con el mismo nombre (sin importar la categoría)
 */
/**
 * Verifica si ya existe un producto con el mismo nombre y categoría (sin importar mayúsculas/minúsculas)
 */
/**
 * Verifica si ya existe un producto con el mismo nombre y categoría (sin importar mayúsculas/minúsculas)
 */
/**
 * Verifica si ya existe un producto con el mismo nombre (sin importar la categoría)
 */
public function existeProducto(string $nombre, int $categoriaId): bool
{
    $sql = "SELECT 1 FROM productos WHERE LOWER(nombre) = LOWER(:n) LIMIT 1";
    $st  = $this->db->prepare($sql);
    $st->execute([':n' => $nombre]);
    return (bool)$st->fetchColumn();
}
}