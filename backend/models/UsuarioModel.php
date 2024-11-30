<?php
require_once('../config/config.php');

class UsuarioModel {
    private $conexion;

    public function __construct() {
        $this->conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }
    }

    public function obtenerUsuarioPorNombre($usuario) {
        $stmt = $this->conexion->prepare("SELECT * FROM usuarios WHERE nombre_usu = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crearUsuario($usuario, $password, $rol) {
        $stmt = $this->conexion->prepare("INSERT INTO usuarios (nombre_usu, password, id_rol) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $usuario, $password, $rol);
        return $stmt->execute();
    }
}
?>
