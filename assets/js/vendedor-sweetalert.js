/**
 * Configuraciones de SweetAlert2 para Usuarios Vendedor
 * Sistema SweetPot - Repostería Artesanal
 */

// Configuración base para SweetAlert2
const SweetPotVendedorConfig = {
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

// Funciones para gestión de productos
function confirmarEliminarProducto(nombreProducto, id) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¿Eliminar producto?",
    html: `
            <div class="text-center">
                <i class="fas fa-trash text-danger fa-3x mb-3"></i>
                <p>¿Estás seguro de eliminar <strong>${nombreProducto}</strong>?</p>
                <p class="text-danger small">Esta acción no se puede deshacer</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-trash"></i> Eliminar',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    confirmButtonColor: "#dc3545",
  });
}

function mostrarProductoCreado(nombreProducto) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¡Producto creado!",
    html: `
            <div class="text-center">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <p><strong>${nombreProducto}</strong> se creó correctamente</p>
                <p class="text-muted">Ya está disponible para los clientes</p>
            </div>
        `,
    confirmButtonText: '<i class="fas fa-eye"></i> Ver Producto',
  });
}

function mostrarProductoActualizado(nombreProducto) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¡Producto actualizado!",
    html: `
            <div class="text-center">
                <i class="fas fa-edit text-success fa-3x mb-3"></i>
                <p><strong>${nombreProducto}</strong> se actualizó correctamente</p>
            </div>
        `,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: "top-end",
  });
}

function confirmarActualizarStock(nombreProducto, stockActual) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "Actualizar Stock",
    html: `
            <div class="text-center">
                <i class="fas fa-boxes text-sweetpot-pink fa-3x mb-3"></i>
                <p><strong>${nombreProducto}</strong></p>
                <p>Stock actual: <span class="badge bg-info">${stockActual}</span></p>
                <input type="number" id="nuevoStock" class="form-control mt-3" placeholder="Nuevo stock" min="0">
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-save"></i> Actualizar',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    preConfirm: () => {
      const nuevoStock = document.getElementById("nuevoStock").value;
      if (!nuevoStock || nuevoStock < 0) {
        Swal.showValidationMessage("Por favor ingresa un stock válido");
      }
      return nuevoStock;
    },
  });
}

// Funciones para gestión de pedidos
function confirmarCambiarEstadoPedido(numeroPedido, estadoActual, nuevoEstado) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¿Cambiar estado del pedido?",
    html: `
            <div class="text-center">
                <i class="fas fa-exchange-alt text-info fa-3x mb-3"></i>
                <p><strong>Pedido #${numeroPedido}</strong></p>
                <div class="row justify-content-center">
                    <div class="col-5">
                        <small class="text-muted">Estado Actual</small>
                        <div class="badge badge-estado-${estadoActual.toLowerCase()}">${estadoActual}</div>
                    </div>
                    <div class="col-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="col-5">
                        <small class="text-muted">Nuevo Estado</small>
                        <div class="badge badge-estado-${nuevoEstado.toLowerCase()}">${nuevoEstado}</div>
                    </div>
                </div>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check"></i> Cambiar Estado',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
  });
}

function mostrarDetallesPedido(pedido) {
  let productosHTML = "";
  pedido.productos.forEach((producto) => {
    productosHTML += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <strong>${producto.nombre}</strong><br>
                    <small class="text-muted">Cantidad: ${producto.cantidad}</small>
                </div>
                <span class="text-sweetpot-pink">$${producto.subtotal}</span>
            </div>
        `;
  });

  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: `Detalles - Pedido #${pedido.numero}`,
    html: `
            <div class="text-start">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span><strong>Cliente:</strong> ${pedido.cliente}</span>
                        <span class="badge badge-estado-${pedido.estado.toLowerCase()}">${
      pedido.estado
    }</span>
                    </div>
                    <small class="text-muted">Fecha: ${pedido.fecha}</small>
                </div>
                <hr>
                <h6>Productos:</h6>
                <div class="mb-3" style="max-height: 200px; overflow-y: auto;">
                    ${productosHTML}
                </div>
                <hr>
                <div class="d-flex justify-content-between fs-5">
                    <strong>Total:</strong>
                    <strong class="text-sweetpot-pink">$${pedido.total}</strong>
                </div>
            </div>
        `,
    width: "600px",
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-edit"></i> Cambiar Estado',
    cancelButtonText: '<i class="fas fa-times"></i> Cerrar',
  });
}

function mostrarTicketGenerado(numeroTicket, qrUrl) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¡Ticket Generado!",
    html: `
            <div class="text-center">
                <i class="fas fa-ticket-alt text-success fa-3x mb-3"></i>
                <p><strong>Ticket #${numeroTicket}</strong></p>
                <div class="mb-3">
                    <img src="${qrUrl}" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                </div>
                <p class="text-muted">El cliente puede escanear el código QR</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-download"></i> Descargar',
    cancelButtonText: '<i class="fas fa-times"></i> Cerrar',
  });
}

// Funciones para reportes y estadísticas
function mostrarResumenVentas(datos) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "Resumen de Ventas",
    html: `
            <div class="text-center">
                <i class="fas fa-chart-bar text-sweetpot-pink fa-3x mb-3"></i>
                <div class="row">
                    <div class="col-6">
                        <div class="border rounded p-3 mb-2">
                            <h5 class="text-sweetpot-pink">${datos.ventasHoy}</h5>
                            <small class="text-muted">Ventas Hoy</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 mb-2">
                            <h5 class="text-sweetpot-pink">$${datos.ingresoHoy}</h5>
                            <small class="text-muted">Ingresos Hoy</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 mb-2">
                            <h5 class="text-sweetpot-pink">${datos.ventasMes}</h5>
                            <small class="text-muted">Ventas del Mes</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 mb-2">
                            <h5 class="text-sweetpot-pink">$${datos.ingresoMes}</h5>
                            <small class="text-muted">Ingresos del Mes</small>
                        </div>
                    </div>
                </div>
            </div>
        `,
    width: "500px",
    confirmButtonText: '<i class="fas fa-chart-line"></i> Ver Reporte Completo',
  });
}

function mostrarProductosMasVendidos(productos) {
  let productosHTML = "";
  productos.forEach((producto, index) => {
    productosHTML += `
            <div class="d-flex justify-content-between align-items-center py-2 ${
              index !== productos.length - 1 ? "border-bottom" : ""
            }">
                <div>
                    <strong>${producto.nombre}</strong><br>
                    <small class="text-muted">${producto.categoria}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-sweetpot-pink">${
                      producto.vendidos
                    }</span><br>
                    <small class="text-muted">vendidos</small>
                </div>
            </div>
        `;
  });

  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "Productos Más Vendidos",
    html: `
            <div class="text-start">
                <div class="mb-3 text-center">
                    <i class="fas fa-crown text-warning fa-3x mb-3"></i>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    ${productosHTML}
                </div>
            </div>
        `,
    width: "500px",
    confirmButtonText: '<i class="fas fa-times"></i> Cerrar',
  });
}

// Funciones para inventario
function alertaStockBajo(productos) {
  let productosHTML = "";
  productos.forEach((producto) => {
    productosHTML += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <strong>${producto.nombre}</strong><br>
                    <small class="text-muted">${producto.categoria}</small>
                </div>
                <span class="badge bg-warning text-dark">${producto.stock}</span>
            </div>
        `;
  });

  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "⚠️ Stock Bajo",
    html: `
            <div class="text-start">
                <div class="mb-3 text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <p>Los siguientes productos tienen stock bajo:</p>
                </div>
                <div style="max-height: 200px; overflow-y: auto;">
                    ${productosHTML}
                </div>
            </div>
        `,
    width: "500px",
    confirmButtonText: '<i class="fas fa-boxes"></i> Actualizar Inventario',
  });
}

function confirmarReposicionStock(nombreProducto, stockMinimo) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "Reposición de Stock",
    html: `
            <div class="text-center">
                <i class="fas fa-plus-circle text-success fa-3x mb-3"></i>
                <p><strong>${nombreProducto}</strong></p>
                <p>Stock mínimo recomendado: <span class="badge bg-info">${stockMinimo}</span></p>
                <input type="number" id="cantidadReposicion" class="form-control mt-3" placeholder="Cantidad a reponer" min="1">
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-plus"></i> Reponer',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    preConfirm: () => {
      const cantidad = document.getElementById("cantidadReposicion").value;
      if (!cantidad || cantidad < 1) {
        Swal.showValidationMessage("Por favor ingresa una cantidad válida");
      }
      return cantidad;
    },
  });
}

// Funciones para categorías
function confirmarEliminarCategoria(nombreCategoria, productosAsociados) {
  let mensaje = `¿Estás seguro de eliminar la categoría <strong>${nombreCategoria}</strong>?`;
  if (productosAsociados > 0) {
    mensaje += `<br><span class="text-warning">Esta categoría tiene ${productosAsociados} producto(s) asociado(s)</span>`;
  }

  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¿Eliminar categoría?",
    html: `
            <div class="text-center">
                <i class="fas fa-folder-minus text-danger fa-3x mb-3"></i>
                <p>${mensaje}</p>
                <p class="text-danger small">Esta acción no se puede deshacer</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-trash"></i> Eliminar',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    confirmButtonColor: "#dc3545",
  });
}

function mostrarCategoriaCreada(nombreCategoria) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¡Categoría creada!",
    html: `
            <div class="text-center">
                <i class="fas fa-folder-plus text-success fa-3x mb-3"></i>
                <p><strong>${nombreCategoria}</strong> se creó correctamente</p>
            </div>
        `,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: "top-end",
  });
}

// Funciones de validación y errores
function mostrarErrorValidacion(errores) {
  let erroresHTML = "";
  errores.forEach((error) => {
    erroresHTML += `<li class="text-start">${error}</li>`;
  });

  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "Campos requeridos",
    html: `
            <div class="text-center">
                <i class="fas fa-exclamation-circle text-warning fa-3x mb-3"></i>
                <p>Por favor corrige los siguientes errores:</p>
                <ul class="text-start">${erroresHTML}</ul>
            </div>
        `,
    confirmButtonText: '<i class="fas fa-check"></i> Entendido',
  });
}

function mostrarCargando(mensaje = "Procesando...") {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: mensaje,
    html: `
            <div class="text-center">
                <div class="spinner-sweetpot mx-auto mb-3"></div>
                <p class="text-muted">Por favor espera un momento</p>
            </div>
        `,
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    },
  });
}

function mostrarExitoOperacion(mensaje, icono = "check-circle") {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "¡Éxito!",
    html: `
            <div class="text-center">
                <i class="fas fa-${icono} text-success fa-3x mb-3"></i>
                <p>${mensaje}</p>
            </div>
        `,
    timer: 2000,
    showConfirmButton: false,
    toast: true,
    position: "top-end",
  });
}

function mostrarError(titulo, mensaje) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: titulo,
    html: `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                <p>${mensaje}</p>
            </div>
        `,
    confirmButtonText: '<i class="fas fa-check"></i> Entendido',
  });
}

// Funciones para notificaciones
function mostrarNotificacionNuevoPedido(numeroPedido, cliente) {
  return Swal.fire({
    ...SweetPotVendedorConfig,
    title: "🔔 ¡Nuevo Pedido!",
    html: `
            <div class="text-center">
                <i class="fas fa-bell text-info fa-3x mb-3 pulse-sweetpot"></i>
                <p><strong>Pedido #${numeroPedido}</strong></p>
                <p>Cliente: ${cliente}</p>
                <p class="text-muted">Revisa los detalles y confirma el pedido</p>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-eye"></i> Ver Pedido',
    cancelButtonText: '<i class="fas fa-times"></i> Cerrar',
  });
}

function confirmarCerrarSesion() {
  Swal.fire({
    ...SweetPotVendedorConfig,
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
