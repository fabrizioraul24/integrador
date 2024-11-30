<?php

require_once '../models/ProductoModel.php';

class ProductoController
{
    private $productoModel;

    public function __construct($db)
    {
        $this->productoModel = new ProductoModel($db);
    }

    public function listarProductos()
    {
        return $this->productoModel->obtenerProductos();
    }

    public function crearProducto($id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto)
    {
        return $this->productoModel->agregarProducto($id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto);
    }

    public function obtenerProducto($id_producto)
    {
        return $this->productoModel->obtenerProductoPorId($id_producto);
    }

    public function editarProducto($id_producto, $id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto)
    {
        return $this->productoModel->actualizarProducto($id_producto, $id_categoria, $nombre_producto, $tipo_de_presentacion, $descripcion, $cantidad, $precio, $foto);
    }

    public function eliminarProducto($id_producto)
    {
        return $this->productoModel->eliminarProducto($id_producto);
    }
}
