/**
 * SweetAlert2 Configuraciones para Administrador - SweetPot
 */

// Configuración global de SweetAlert2 para administrador
const adminSwalConfig = {
  confirmButtonColor: "#ff6b9d",
  cancelButtonColor: "#8b4513",
  showClass: {
    popup: "animate__animated animate__fadeInDown",
  },
  hideClass: {
    popup: "animate__animated animate__fadeOutUp",
  },
};

// Aplicar configuración global
Swal.mixin(adminSwalConfig);

/**
 * Alertas específicas para administrador
 */

// Confirmar eliminación de usuario
function confirmarEliminarUsuario(id, nombre) {
  return Swal.fire({
    title: "¿Eliminar usuario?",
    html: `¿Estás seguro de que deseas eliminar al usuario <strong>${nombre}</strong>?<br><small class="text-danger">Esta acción no se puede deshacer</small>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Confirmar eliminación de producto
function confirmarEliminarProducto(id, nombre) {
  return Swal.fire({
    title: "¿Eliminar producto?",
    html: `¿Estás seguro de que deseas eliminar el producto <strong>${nombre}</strong>?<br><small class="text-danger">Esta acción no se puede deshacer</small>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Confirmar eliminación de categoría
function confirmarEliminarCategoria(id, nombre) {
  return Swal.fire({
    title: "¿Eliminar categoría?",
    html: `¿Estás seguro de que deseas eliminar la categoría <strong>${nombre}</strong>?<br><small class="text-warning">Esto podría afectar los productos asociados</small>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Confirmar cambio de estado
function confirmarCambiarEstado(tipo, nombre, estadoActual) {
  const nuevoEstado = estadoActual === "activo" ? "inactivo" : "activo";
  const accion = nuevoEstado === "activo" ? "activar" : "desactivar";

  return Swal.fire({
    title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} ${tipo}?`,
    html: `¿Estás seguro de que deseas ${accion} <strong>${nombre}</strong>?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: `Sí, ${accion}`,
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Confirmar cambio de estado de pedido
function confirmarCambioEstadoPedido(numeroPedido, nuevoEstado) {
  let titulo, texto, icono, textoBoton;

  switch (nuevoEstado) {
    case "pendiente":
      titulo = "¿Marcar como pendiente?";
      texto = "El pedido será marcado como pendiente";
      icono = "info";
      textoBoton = "Sí, marcar pendiente";
      break;
    case "en_proceso":
      titulo = "¿Poner en proceso?";
      texto = "El pedido será marcado como en proceso";
      icono = "info";
      textoBoton = "Sí, poner en proceso";
      break;
    case "enviado":
      titulo = "¿Marcar como enviado?";
      texto = "El pedido será marcado como enviado";
      icono = "success";
      textoBoton = "Sí, marcar enviado";
      break;
    case "entregado":
      titulo = "¿Marcar como entregado?";
      texto = "El pedido será completado y marcado como entregado";
      icono = "success";
      textoBoton = "Sí, marcar entregado";
      break;
    case "cancelado":
      titulo = "¿Cancelar pedido?";
      texto = "El pedido será cancelado. Esta acción no se puede deshacer.";
      icono = "warning";
      textoBoton = "Sí, cancelar";
      break;
    default:
      titulo = "¿Cambiar estado?";
      texto = "Se cambiará el estado del pedido";
      icono = "question";
      textoBoton = "Sí, cambiar";
  }

  return Swal.fire({
    title: titulo,
    html: `${texto}<br><strong>Pedido:</strong> ${numeroPedido}`,
    icon: icono,
    showCancelButton: true,
    confirmButtonText: textoBoton,
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Confirmar generación de QR
function confirmarGenerarQR(tipo, nombre) {
  return Swal.fire({
    title: "¿Generar código QR?",
    html: `¿Deseas generar un nuevo código QR para <strong>${nombre}</strong>?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, generar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
  });
}

// Mostrar código QR generado
function mostrarQRGenerado(qrUrl, nombre) {
  Swal.fire({
    title: "Código QR Generado",
    html: `
            <div class="text-center">
                <p>Código QR para <strong>${nombre}</strong>:</p>
                <img src="${qrUrl}" alt="Código QR" class="img-fluid" style="max-width: 200px;">
                <br><br>
                <a href="${qrUrl}" download class="btn btn-primary btn-sm">
                    <i class="fas fa-download me-2"></i>Descargar QR
                </a>
            </div>
        `,
    showConfirmButton: false,
    showCloseButton: true,
    width: 400,
  });
}

// Mostrar estadísticas
function mostrarEstadisticas(datos) {
  Swal.fire({
    title: "Estadísticas del Sistema",
    html: `
            <div class="row text-center">
                <div class="col-6">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title text-primary">${
                              datos.usuarios || 0
                            }</h5>
                            <p class="card-text">Usuarios</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success">${
                              datos.productos || 0
                            }</h5>
                            <p class="card-text">Productos</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h5 class="card-title text-warning">${
                              datos.ventas || 0
                            }</h5>
                            <p class="card-text">Ventas</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 mt-3">
                    <div class="card border-info">
                        <div class="card-body">
                            <h5 class="card-title text-info">$${
                              datos.ingresos || 0
                            }</h5>
                            <p class="card-text">Ingresos</p>
                        </div>
                    </div>
                </div>
            </div>
        `,
    width: 500,
    showConfirmButton: false,
    showCloseButton: true,
  });
}

// Toast para notificaciones rápidas
function toastAdmin(tipo, mensaje) {
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
  });

  Toast.fire({
    icon: tipo,
    title: mensaje,
  });
}

// Alerta de bienvenida para administrador
function bienvenidaAdmin(nombreAdmin) {
  Swal.fire({
    title: `¡Bienvenido, ${nombreAdmin}!`,
    html: `
            <div class="text-center">
                <i class="fas fa-crown text-warning" style="font-size: 3rem;"></i>
                <p class="mt-3">Panel de Administración - SweetPot</p>
                <small class="text-muted">Gestiona tu repostería desde aquí</small>
            </div>
        `,
    timer: 2500,
    showConfirmButton: false,
    allowOutsideClick: false,
  });
}

// Alerta de stock bajo
function alertaStockBajo(productos) {
  let html = '<div class="text-start">';
  productos.forEach((producto) => {
    html += `<div class="alert alert-warning mb-2 py-2">
            <strong>${producto.nombre}</strong><br>
            <small>Stock actual: ${producto.stock} | Mínimo: ${producto.stock_minimo}</small>
        </div>`;
  });
  html += "</div>";

  Swal.fire({
    title: "⚠️ Productos con Stock Bajo",
    html: html,
    icon: "warning",
    confirmButtonText: "Revisar Inventario",
    width: 500,
  });
}

// Confirmar respaldo de datos
function confirmarRespaldo() {
  return Swal.fire({
    title: "¿Crear respaldo?",
    text: "Se creará una copia de seguridad de todos los datos del sistema",
    icon: "info",
    showCancelButton: true,
    confirmButtonText: "Crear respaldo",
    cancelButtonText: "Cancelar",
  });
}

// Loading para operaciones largas
function mostrarCargando(mensaje = "Procesando...") {
  Swal.fire({
    title: mensaje,
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });
}

// Ocultar loading
function ocultarCargando() {
  Swal.close();
}

// Funciones genéricas
function mostrarExito(titulo, mensaje) {
  Swal.fire({
    icon: "success",
    title: titulo,
    text: mensaje,
    confirmButtonText: "Entendido",
  });
}

function mostrarError(titulo, mensaje) {
  Swal.fire({
    icon: "error",
    title: titulo,
    text: mensaje,
    confirmButtonText: "Entendido",
  });
}

function mostrarAdvertencia(titulo, mensaje) {
  Swal.fire({
    icon: "warning",
    title: titulo,
    text: mensaje,
    confirmButtonText: "Entendido",
  });
}

function mostrarInfo(titulo, mensaje) {
  Swal.fire({
    icon: "info",
    title: titulo,
    text: mensaje,
    confirmButtonText: "Entendido",
  });
}

// Función para confirmar cerrar sesión
function confirmarCerrarSesion() {
  Swal.fire({
    title: "¿Cerrar sesión?",
    html: `
            <div class="text-center">
                <i class="fas fa-sign-out-alt text-warning fa-3x mb-3"></i>
                <p>¿Estás seguro/a de que deseas salir?</p>
            </div>
        `,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Cerrar Sesión',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      // Mostrar loading
      Swal.fire({
        title: "Cerrando sesión...",
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      // Redirigir al logout
      setTimeout(() => {
        window.location.href = "../logout.php";
      }, 1000);
    }
  });
}
