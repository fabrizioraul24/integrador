document.addEventListener("DOMContentLoaded", function () {
    fetch("../../backend/controllers/UserController.php?action=list")
        .then((response) => response.json())
        .then((usuarios) => {
            const userTable = document.getElementById("userTable");
            userTable.innerHTML = "";
            usuarios.forEach((usuario) => {
                userTable.innerHTML += `
                    <tr>
                        <td>${usuario.id_usuario}</td>
                        <td>${usuario.nombre_usu}</td>
                        <td>${usuario.email}</td>
                        <td>${usuario.nombre_rol}</td>
                    </tr>
                `;
            });
        })
        .catch((error) => console.error("Error al cargar los usuarios:", error));
});
