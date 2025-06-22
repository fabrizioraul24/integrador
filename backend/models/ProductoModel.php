<?php

class ProductoModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function obtenerProductos()
    {
        $query = $this->db->prepare("SELECT * FROM productos");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function agregarProducto($id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto)
    {
        $query = $this->db->prepare("
            INSERT INTO productos (id_categoria, nombre_producto, tipo_de_presentacion, descripcion, cantidad, precio, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $query->execute([$id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto]);
    }

    public function obtenerProductoPorId($id_producto)
    {
        $query = $this->db->prepare("SELECT * FROM productos WHERE id_producto = ?");
        $query->execute([$id_producto]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarProducto($id_producto, $id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto)
    {
        $query = $this->db->prepare("
            UPDATE productos
            SET id_categoria = ?, nombre_producto = ?, tipo_de_presentacion = ?, descripcion = ?, cantidad = ?, precio = ?, foto = ?
            WHERE id_producto = ?
        ");
        return $query->execute([$id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto, $id_producto]);
    }

    public function eliminarProducto($id_producto)
    {
        $query = $this->db->prepare("DELETE FROM productos WHERE id_producto = ?");
        return $query->execute([$id_producto]);
    }
}
