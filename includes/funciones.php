<?php

function obtenerTodosLosProductos(PDO $pdo): array {
	$sql = "SELECT p.*, t.type_name
			FROM products p
			LEFT JOIN product_type t 
			ON p.type_id = t.type_id
			ORDER BY p.product_id ASC";

	$stmt = $pdo->prepare($sql);
	$stmt->execute();

	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function obtenerProductoPorId(PDO $pdo, int $id){
	$sql="SELECT * FROM products WHERE product_id=?";
	$stmt=$pdo->prepare($sql);
	$stmt->execute([$id]);
	return $stmt->fetch(PDO::FETCH_ASSOC);
}
function insertarProducto(PDO $pdo, string $nombre, ?float $precio, ?int $tipo_id, ?string $imagen): int|false {
        $sql = "INSERT INTO products (product_name, product_price, type_id, image) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([$nombre, $precio, $tipo_id, $imagen]);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al insertar: " . $e->getMessage());
            echo "<pre>ERROR: " . $e->getMessage() . "</pre>"; // ← agrega esta línea
            return false;
        }
    }
function actualizarProducto(PDO $pdo, int $id, string $nombre, ?float $precio, ?int $tipo_id, ?string $imagen = null)
{
    // Si $imagen es null, mantiene la anterior.
    // Si viene vacía '', la borra.

    $sql = $imagen === null
        ? "UPDATE products 
           SET product_name = ?, product_price = ?, type_id = ?
           WHERE product_id = ?"
        : "UPDATE products 
           SET product_name = ?, product_price = ?, type_id = ?,
               image = COALESCE(?, image)
           WHERE product_id = ?";

    $stmt = $pdo->prepare($sql);

    try {
        if ($imagen === null) {
            $stmt->execute([$nombre, $precio, $tipo_id, $id]);
        } else {
            $stmt->execute([$nombre, $precio, $tipo_id, $imagen, $id]);
        }

        return $stmt->rowCount() > 0;

    } catch (PDOException $e) {
        error_log("Error al actualizar: " . $e->getMessage());
        return false;
    }
}
function eliminarProducto(PDO $pdo, int $id): bool
{
	$sql="DELETE FROM products WHERE product_id=?";
	$stmt=$pdo->prepare($sql);

	try{
		$stmt->execute([$id]);
		return $stmt->rowCount() > 0;
	}catch (PDOException $e) {
		error_log("Error al eliminar: ". $e->getMessage());
		return false; 
	}
}
function obtenerTodosLosTipos(PDO $pdo): array
{
	$sql="SELECT * FROM product_type ORDER BY type_name ASC";
	$stmt=$pdo->prepare($sql);
    $stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
function crearTipo(PDO $pdo, string $nombre): int|false {
	$sql="INSERT INTO product_type (type_name) VALUES (?) ON DUPLICATE KEY UPDATE type_name=VALUES(type_name)";
	$stmt=$pdo->prepare($sql);
	try{
		$stmt->execute([$nombre]);
		return $pdo->lastInsertId();
	}catch (PDOException $e){
		return false;
	}
}
function actualizarTipo(PDO $pdo, int $id, string $nombre): bool {
	$sql="UPDATE product_type SET type_name=? WHERE type_id=?";
	$stmt=$pdo->prepare($sql);
	try{
		$stmt->execute([$nombre,$id]);
		return $stmt->rowCount() >0;
	}catch (PDOException $e){
		return false;
	}
}
function eliminarTipo(PDO $pdo, int $id_tipo): bool {
	$sql="DELETE FROM product_type WHERE type_id=?";
	$stmt=$pdo->prepare($sql);
	try{
		$stmt->execute([$id_tipo]);
		return $stmt->rowCount() >0;
	}catch (PDOException $e){
		return false;
	}
}
?>