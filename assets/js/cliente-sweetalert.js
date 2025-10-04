/**
 * Configuraciones de SweetAlert2 para Usuarios Cliente
 * Sistema SweetPot - Repostería Artesanal
 */

// Configuración base para SweetAlert2
const SweetPotClienteConfig = {
  confirmButtonColor: "#ff6b9d",
  cancelButtonColor: "#8b4513",
  background: "#ffeaa7",
  color: "#8b4513",
  customClass: {
    popup: "rounded-4 shadow-lg",
    title: "text-sweetpot-brown",
    confirmButton: "btn btn-sweetpot-primary mx-2",
    cancelButton: "btn btn-sweetpot-secondary mx-2",
  },
};

// Funciones para manejo del carrito
function confirmarAgregarAlCarrito(nombreProducto, precio) {
  return Swal.fire({
    ...SweetPotClienteConfig,
    title: "¿Agregar al carrito?",
    html: `
            <div class="text-center">
                <i class="fas fa-shopping-cart text-sweetpot-pink fa-3x mb-3"></i>
                <p><strong>${nombreProducto}</strong></p>
                <p class="text-sweetpot-pink fs-5">$${precio}</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-cart-plus"></i> Agregar',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
  });
}

/**
 * Confirmar eliminar del carrito
 */
function confirmarEliminarCarrito(obraId, titulo, callback) {
  Swal.fire({
    title: "¿Eliminar del carrito?",
    text: `¿Deseas eliminar "${titulo}" de tu carrito?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: SweetConfigCliente.colors.error,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed && callback) {
      callback(obraId);
    }
  });
}

/**
 * Confirmar finalizar compra
 */
function confirmarCompra(total, callback) {
  Swal.fire({
    title: "¿Finalizar compra?",
    html: `
            <p>Estás a punto de realizar una compra por:</p>
            <h3 class="text-primary">$${total.toLocaleString()}</h3>
            <p class="text-muted">Se procesará tu pedido inmediatamente</p>
        `,
    icon: "info",
    showCancelButton: true,
    confirmButtonColor: SweetConfigCliente.colors.success,
    confirmButtonText: "Sí, comprar",
    cancelButtonText: "Revisar carrito",
  }).then((result) => {
    if (result.isConfirmed && callback) {
      callback();
    }
  });
}

/**
 * Mostrar compra exitosa
 */
function mostrarCompraExitosa(numeroPedido) {
  Swal.fire({
    title: "¡Compra realizada!",
    html: `
            <div class="text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p>Tu pedido ha sido procesado exitosamente</p>
                <p><strong>Número de pedido: #${numeroPedido}</strong></p>
                <p class="text-muted">Recibirás un email con los detalles</p>
            </div>
        `,
    icon: "success",
    confirmButtonText: "Ver mis pedidos",
    confirmButtonColor: SweetConfigCliente.colors.primary,
  }).then(() => {
    window.location.href = "mis-pedidos.php";
  });
}

/**
 * Mostrar loading para operaciones del carrito
 */
function mostrarLoadingCarrito(mensaje = "Procesando...") {
  Swal.fire({
    title: mensaje,
    html: '<i class="fas fa-shopping-cart fa-spin fa-2x"></i>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
  });
}

/**
 * Mostrar éxito del carrito
 */
function mostrarExitoCarrito(titulo, mensaje) {
  Swal.fire({
    icon: "success",
    title: titulo,
    text: mensaje,
    timer: 2000,
    timerProgressBar: true,
    showConfirmButton: false,
    toast: true,
    position: "top-end",
  });
}

/**
 * Confirmar logout
 */
function confirmarLogout(callback) {
  Swal.fire({
    title: "¿Cerrar sesión?",
    text: "¿Estás seguro de que deseas salir?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, salir",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed && callback) {
      callback();
    }
  });
}

/**
 * Mostrar información de producto
 */
function mostrarInfoProducto(obra) {
  Swal.fire({
    title: obra.titulo,
    html: `
            <div class="text-start">
                <img src="${
                  obra.imagen
                }" class="img-fluid mb-3" style="max-height: 200px;">
                <p><strong>Artista:</strong> ${obra.artista}</p>
                <p><strong>Precio:</strong> $${obra.precio.toLocaleString()}</p>
                <p><strong>Descripción:</strong></p>
                <p class="text-muted">${obra.descripcion}</p>
            </div>
        `,
    width: 600,
    confirmButtonText: "Cerrar",
    showCancelButton: true,
    cancelButtonText: "Agregar al carrito",
    confirmButtonColor: SweetConfigCliente.colors.secondary,
    cancelButtonColor: SweetConfigCliente.colors.primary,
    reverseButtons: false,
  }).then((result) => {
    if (result.dismiss === Swal.DismissReason.cancel) {
      // Usuario quiere agregar al carrito
      agregarAlCarrito(obra.id);
    }
  });
}

/**
 * Error de login
 */
function mostrarErrorLogin(mensaje) {
  Swal.fire({
    icon: "error",
    title: "Error de acceso",
    text: mensaje,
    confirmButtonText: "Intentar de nuevo",
  });
}

/**
 * Bienvenida después del login
 */
function mostrarBienvenida(nombre) {
  Swal.fire({
    icon: "success",
    title: `¡Bienvenido ${nombre}!`,
    text: "Has iniciado sesión correctamente",
    timer: 2000,
    timerProgressBar: true,
    showConfirmButton: false,
    toast: true,
    position: "top-end",
  });
}

/**
 * Carrito vacío
 */
function mostrarCarritoVacio() {
  Swal.fire({
    icon: "info",
    title: "Carrito vacío",
    text: "No tienes productos en tu carrito. ¡Explora nuestro catálogo!",
    confirmButtonText: "Ver catálogo",
  }).then(() => {
    window.location.href = "catalogo.php";
  });
}

/**
 * Confirmar limpiar carrito
 */
function confirmarLimpiarCarrito(callback) {
  Swal.fire({
    title: "¿Vaciar carrito?",
    text: "Se eliminarán todos los productos de tu carrito",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: SweetConfigCliente.colors.error,
    confirmButtonText: "Sí, vaciar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed && callback) {
      callback();
    }
  });
}

// Funciones genéricas (reutilizando del admin pero con colores de cliente)
function mostrarExito(titulo, mensaje, esToast = true) {
  if (esToast) {
    Swal.fire({
      icon: "success",
      title: titulo,
      text: mensaje,
      timer: 3000,
      timerProgressBar: true,
      showConfirmButton: false,
      toast: true,
      position: "top-end",
    });
  } else {
    Swal.fire({
      icon: "success",
      title: titulo,
      text: mensaje,
      confirmButtonText: "Entendido",
    });
  }
}

function mostrarError(titulo, mensaje) {
  Swal.fire({
    icon: "error",
    title: titulo,
    text: mensaje,
    confirmButtonText: "Entendido",
    confirmButtonColor: SweetConfigCliente.colors.error,
  });
}

function mostrarLoading(mensaje = "Procesando...") {
  Swal.fire({
    title: mensaje,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    },
  });
}

// Función para confirmar cerrar sesión
function confirmarCerrarSesion() {
  Swal.fire({
    ...SweetPotClienteConfig,
    title: "¿Cerrar sesión?",
    html: `
            <div class="text-center">
                <i class="fas fa-sign-out-alt text-warning fa-3x mb-3"></i>
                <p>¿Estás seguro/a de que deseas salir?</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Cerrar Sesión',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
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
