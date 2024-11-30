// Función para mostrar mensajes dinámicos
function showMessage(type, message) {
    const messageBox = document.getElementById('message-box');
    const messageContent = document.getElementById('message-content');
    
    // Limpia las clases previas
    messageBox.className = '';
    messageBox.classList.add(type); // Agrega clase 'error' o 'success'
    messageContent.innerText = message;

    // Muestra el mensaje
    messageBox.style.display = 'block';

    // Oculta el mensaje después de 5 segundos
    setTimeout(() => {
        messageBox.style.display = 'none';
    }, 5000);
}

// Ejemplo de cómo llamar a la función
// showMessage('error', 'Usuario o contraseña incorrectos.');
// showMessage('success', 'Usuario registrado con éxito.');
