/**
 * NIXGARU – Cafetería | Lógica Frontend Interactiva
 */

document.addEventListener('DOMContentLoaded', () => {
  // Elementos DOM del carrito
  const cartToggleBtn = document.getElementById('cart-toggle-btn');
  const cartSidebar = document.getElementById('cart-sidebar');
  const cartCloseBtn = document.getElementById('cart-close-btn');
  const cartBackdrop = document.getElementById('cart-backdrop');

  /**
   * Alternar visibilidad de la barra lateral del carrito con overlay empañado en móvil
   */
  function toggleCart() {
    if (!cartSidebar) return;
    
    const isOpen = cartSidebar.classList.toggle('open');
    cartSidebar.classList.toggle('active', isOpen);
    cartSidebar.classList.remove('hidden');
    const isExpanded = isOpen;


    if (cartToggleBtn) {
      cartToggleBtn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }
    cartSidebar.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

    if (cartBackdrop) {
      if (isExpanded && window.innerWidth <= 1024) {
        cartBackdrop.classList.add('active', 'open');
        cartBackdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      } else {
        cartBackdrop.classList.remove('active', 'open');
      cartBackdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }
    }
  }

  function closeCart() {
    if (!cartSidebar) return;
    cartSidebar.classList.remove('open', 'active');
    cartSidebar.setAttribute('aria-hidden', 'true');
    if (cartToggleBtn) {
      cartToggleBtn.setAttribute('aria-expanded', 'false');
    }
    if (cartBackdrop) {
      cartBackdrop.classList.remove('active', 'open');
      cartBackdrop.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }
  }

  // Event Listeners para abrir / cerrar carrito
  if (cartToggleBtn) {
    cartToggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleCart();
    });
  }

  if (cartCloseBtn) {
    cartCloseBtn.addEventListener('click', (e) => {
      e.preventDefault();
      closeCart();
    });
  }

  if (cartBackdrop) {
    cartBackdrop.addEventListener('click', () => {
      closeCart();
    });
  }

  // Cerrar carrito en vista movil con Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && window.innerWidth <= 1024 && cartSidebar && (cartSidebar.classList.contains('open') || cartSidebar.classList.contains('active'))) {
      closeCart();
    }
  });

  // Limpiar overlay si se redimensiona a pantalla de escritorio
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      if (cartBackdrop) {
        cartBackdrop.classList.remove('active', 'open');
        cartBackdrop.setAttribute('aria-hidden', 'true');
      }
      const productModalEl = document.getElementById('product-modal');
      const checkoutModalEl = document.getElementById('modal-checkout-cliente');
      const exitoModalEl = document.getElementById('modal-pedido-exito');
      const hayModalAbierto =
        (productModalEl && (productModalEl.classList.contains('active') || productModalEl.getAttribute('aria-hidden') === 'false')) ||
        (checkoutModalEl && checkoutModalEl.getAttribute('aria-hidden') === 'false') ||
        (exitoModalEl && exitoModalEl.getAttribute('aria-hidden') === 'false');
      if (!hayModalAbierto) {
        document.body.style.overflow = '';
      }
    }
  });

  /**
   * ==========================================================================
   * FILTRO DINÁMICO DE CATEGORÍAS (VISTA DEL CLIENTE)
   * ==========================================================================
   */
  const filtroBotones = document.querySelectorAll('.filtro-btn, .category-pill');
  const tarjetasProductos = document.querySelectorAll('.producto-card, .product-card');

  if (filtroBotones.length > 0) {
    filtroBotones.forEach((boton) => {
      boton.addEventListener('click', () => {
        // 1. Quitar la clase active de todos los botones y asignarla al seleccionado
        filtroBotones.forEach((b) => b.classList.remove('active'));
        boton.classList.add('active');

        // 2. Leer el valor de data-filter (o fallback a data-category)
        const filtroSeleccionado = (boton.dataset.filter || boton.dataset.category || 'all').toString().trim().toLowerCase();

        // 3. Iterar sobre todas las tarjetas de producto y filtrar
        tarjetasProductos.forEach((tarjeta) => {
          const categoriaTarjeta = (tarjeta.dataset.category || '').toString().trim().toLowerCase();

          // Si el filtro es "all" o "todos", mostrar todas las tarjetas
          if (filtroSeleccionado === 'all' || filtroSeleccionado === 'todos') {
            tarjeta.style.display = 'flex';
          } else if (categoriaTarjeta === filtroSeleccionado) {
            // Si coincide con el id_categoria o identificador, mostrarla
            tarjeta.style.display = 'flex';
          } else {
            // Si no coincide, ocultarla
            tarjeta.style.display = 'none';
          }
        });
      });
    });
  }

  /**
   * Controles de Cantidad en el Carrito (- / +)
   */
  const quantityControls = document.querySelectorAll('.quantity-control');
  quantityControls.forEach((control) => {
    const decreaseBtn = control.querySelector('[data-action="decrease"]');
    const increaseBtn = control.querySelector('[data-action="increase"]');
    const valueSpan = control.querySelector('.qty-value');

    if (decreaseBtn && increaseBtn && valueSpan) {
      decreaseBtn.addEventListener('click', () => {
        let currentQty = parseInt(valueSpan.textContent, 10) || 1;
        if (currentQty > 1) {
          currentQty--;
          valueSpan.textContent = currentQty;
        }
      });

      increaseBtn.addEventListener('click', () => {
        let currentQty = parseInt(valueSpan.textContent, 10) || 1;
        currentQty++;
        valueSpan.textContent = currentQty;
      });
    }
  });

  /**
   * ==========================================================================
   * CARRITO DE COMPRAS DEL CLIENTE Y SINCRONIZACIÓN DE TOTALES
   * ==========================================================================
   */
  const cartItemsContainer = document.getElementById('cart-items-container') || document.querySelector('.cart-items');
  const cartBadge = document.getElementById('cart-badge') || document.querySelector('.cart-badge');
  const cartSubtotal = document.getElementById('cart-subtotal');
  const cartTip = document.getElementById('cart-tip');
  const cartTotal = document.getElementById('cart-total');

  function actualizarTotalesCarrito() {
    if (!cartItemsContainer) return;

    const items = cartItemsContainer.querySelectorAll('.cart-item');
    let totalItems = 0;
    let subtotal = 0;

    items.forEach(item => {
      const qtySpan = item.querySelector('.qty-value');
      const qty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      const unitPrice = parseFloat(item.dataset.price) || 0;

      totalItems += qty;
      subtotal += (unitPrice * qty);

      const priceSpan = item.querySelector('.cart-item-price');
      if (priceSpan) {
        priceSpan.textContent = `$${(unitPrice * qty).toFixed(2)}`;
      }
    });

    subtotal = Math.round(subtotal * 100) / 100;
    const propina = Math.round(subtotal * 0.10 * 100) / 100;
    const total = Math.round((subtotal + propina) * 100) / 100;

    if (cartBadge) {
      cartBadge.textContent = `${totalItems} Items`;
    }
    if (cartSubtotal) {
      cartSubtotal.textContent = `$${subtotal.toFixed(2)}`;
    }
    if (cartTip) {
      cartTip.textContent = `$${propina.toFixed(2)}`;
    }
    if (cartTotal) {
      cartTotal.textContent = `$${total.toFixed(2)}`;
    }

    // Sincronizar contadores visuales en las tarjetas del catálogo
    sincronizarContadoresTarjetas();
  }

  function agregarAlCarrito(producto, cantidad) {
    if (!cartItemsContainer || cantidad <= 0) return;

    // Asegurar que el carrito sea visible en desktop
    if (cartSidebar && window.innerWidth > 1024) {
      cartSidebar.classList.remove('hidden');
      cartSidebar.classList.add('open', 'active');
      cartSidebar.setAttribute('aria-hidden', 'false');
    }

    const codigo = producto.codigo || '';
    const name = producto.name || 'Producto';
    const price = parseFloat(producto.price) || 0;
    const foto = producto.foto || '';

    // Buscar si ya existe el producto en el carrito
    const itemExistente = Array.from(cartItemsContainer.querySelectorAll('.cart-item')).find(item => {
      if (codigo && item.dataset.codigo === codigo) return true;
      const titleEl = item.querySelector('.cart-item-title');
      return titleEl && titleEl.textContent.trim().toLowerCase() === name.trim().toLowerCase();
    });

    if (itemExistente) {
      const qtySpan = itemExistente.querySelector('.qty-value');
      let currentQty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      currentQty += cantidad;
      if (qtySpan) qtySpan.textContent = currentQty;

      const priceSpan = itemExistente.querySelector('.cart-item-price');
      if (priceSpan) {
        priceSpan.textContent = `$${(price * currentQty).toFixed(2)}`;
      }
    } else {
      const nuevoItem = document.createElement('div');
      nuevoItem.className = 'cart-item';
      nuevoItem.dataset.codigo = codigo;
      nuevoItem.dataset.price = price;
      nuevoItem.innerHTML = `
        <div class="cart-item-img">
          ${foto ? `<img src="../../public/img/${foto}" alt="${name}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" onerror="this.style.display='none';">` : ''}
        </div>
        <div class="cart-item-details">
          <h4 class="cart-item-title">${name}</h4>
          <div class="quantity-control">
            <button class="qty-btn" data-action="decrease" aria-label="Disminuir cantidad" type="button">-</button>
            <span class="qty-value">${cantidad}</span>
            <button class="qty-btn" data-action="increase" aria-label="Aumentar cantidad" type="button">+</button>
          </div>
        </div>
        <span class="cart-item-price">$${(price * cantidad).toFixed(2)}</span>
      `;
      cartItemsContainer.appendChild(nuevoItem);
    }

    actualizarTotalesCarrito();
  }

  /**
   * Modificar cantidad incremental (+ / -) desde controles rápidos de las tarjetas
   */
  function modificarCantidadEnCarrito(producto, delta) {
    if (!cartItemsContainer || !producto) return;

    if (delta > 0) {
      agregarAlCarrito(producto, delta);
      return;
    }

    const codigo = (producto.codigo || '').trim();
    const name = (producto.name || '').trim().toLowerCase();
    const price = parseFloat(producto.price) || 0;

    const items = Array.from(cartItemsContainer.querySelectorAll('.cart-item'));
    const itemExistente = items.find(item => {
      if (codigo && item.dataset.codigo === codigo) return true;
      const titleEl = item.querySelector('.cart-item-title');
      return titleEl && titleEl.textContent.trim().toLowerCase() === name;
    });

    if (itemExistente) {
      const qtySpan = itemExistente.querySelector('.qty-value');
      let currentQty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      currentQty += delta;

      if (currentQty > 0) {
        if (qtySpan) qtySpan.textContent = currentQty;
        const priceSpan = itemExistente.querySelector('.cart-item-price');
        if (priceSpan) {
          priceSpan.textContent = `$${(price * currentQty).toFixed(2)}`;
        }
      } else {
        itemExistente.remove();
      }
      actualizarTotalesCarrito();
    }
  }

  /**
   * Sincronizar los contadores visuales de las tarjetas con los ítems del carrito
   */
  function sincronizarContadoresTarjetas() {
    const cards = document.querySelectorAll('.product-card, .producto-card');
    if (!cards.length) return;

    const cartMap = new Map();
    if (cartItemsContainer) {
      const items = cartItemsContainer.querySelectorAll('.cart-item');
      items.forEach(item => {
        const codigo = (item.dataset.codigo || '').trim();
        const name = (item.querySelector('.cart-item-title')?.textContent || '').trim().toLowerCase();
        const qty = parseInt(item.querySelector('.qty-value')?.textContent || '0', 10) || 0;
        if (codigo) cartMap.set(`code:${codigo}`, (cartMap.get(`code:${codigo}`) || 0) + qty);
        if (name) cartMap.set(`name:${name}`, (cartMap.get(`name:${name}`) || 0) + qty);
      });
    }

    cards.forEach(card => {
      const codigo = (card.dataset.codigo || '').trim();
      const name = (card.dataset.name || card.querySelector('.product-name')?.textContent || '').trim().toLowerCase();
      const qtySpan = card.querySelector('.card-qty-value');
      const control = card.querySelector('.card-quantity-control');

      let currentQty = 0;
      if (codigo && cartMap.has(`code:${codigo}`)) {
        currentQty = cartMap.get(`code:${codigo}`);
      } else if (name && cartMap.has(`name:${name}`)) {
        currentQty = cartMap.get(`name:${name}`);
      }

      if (qtySpan) {
        qtySpan.textContent = currentQty;
      }
      if (control) {
        if (currentQty > 0) {
          control.classList.add('has-items');
        } else {
          control.classList.remove('has-items');
        }
      }
    });
  }

  // Delegación de eventos para los botones (- / +) dentro del carrito
  if (cartItemsContainer) {
    cartItemsContainer.addEventListener('click', (e) => {
      const btn = e.target.closest('.qty-btn');
      if (!btn) return;

      const action = btn.dataset.action;
      const cartItem = btn.closest('.cart-item');
      if (!cartItem) return;

      const qtySpan = cartItem.querySelector('.qty-value');
      let currentQty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;

      if (action === 'increase') {
        currentQty++;
        if (qtySpan) qtySpan.textContent = currentQty;
        actualizarTotalesCarrito();
      } else if (action === 'decrease') {
        if (currentQty > 1) {
          currentQty--;
          if (qtySpan) qtySpan.textContent = currentQty;
        } else {
          cartItem.remove();
        }
        actualizarTotalesCarrito();
      }
    });
  }

  /**
   * ==========================================================================
   * LÓGICA DE CONFIRMACIÓN DE PEDIDO (VISTA DEL CLIENTE - menu_cliente.php)
   * ==========================================================================
   */

  // 1. Vaciar el carrito de compras y restablecer los totales a $0.00
  function vaciarCarritoCliente() {
    if (cartItemsContainer) {
      cartItemsContainer.innerHTML = '';
    }
    if (cartBadge) {
      cartBadge.textContent = '0 Items';
    }
    if (cartSubtotal) {
      cartSubtotal.textContent = '$0.00';
    }
    if (cartTip) {
      cartTip.textContent = '$0.00';
    }
    if (cartTotal) {
      cartTotal.textContent = '$0.00';
    }
    sincronizarContadoresTarjetas();
  }

  // 2. Extraer productos del carrito con IDs, cantidades y precios
  function obtenerProductosDelCarrito() {
    if (!cartItemsContainer) return [];
    const items = [];
    const itemEls = cartItemsContainer.querySelectorAll('.cart-item');
    itemEls.forEach(el => {
      const codigo = el.dataset.codigo || '';
      const name = el.querySelector('.cart-item-title')?.textContent.trim() || 'Producto';
      const qtySpan = el.querySelector('.qty-value');
      const qty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      const price = parseFloat(el.dataset.price) || 0;

      if (codigo && qty > 0) {
        items.push({
          id: codigo,
          codigo: codigo,
          name: name,
          price: price,
          qty: qty
        });
      }
    });
    return items;
  }

  // 3. Utilidad de escape HTML para inyección segura en el comprobante del cliente
  function escaparHtmlCliente(valor) {
    return String(valor ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // 4. Calcular desglose financiero exacto del carrito actual (Subtotal, Propina 10%, Total)
  function calcularMontosCarrito(items) {
    let subtotal = 0;
    items.forEach((item) => {
      const unitPrice = parseFloat(item.price) || 0;
      const qty = parseInt(item.qty, 10) || 0;
      subtotal += Math.round(unitPrice * qty * 100) / 100;
    });
    subtotal = Math.round(subtotal * 100) / 100;
    const propina = Math.round(subtotal * 0.10 * 100) / 100;
    const total = Math.round((subtotal + propina) * 100) / 100;
    return { subtotal, propina, total };
  }

  // 5. Referencias del flujo de Checkout Web (#modal-checkout-cliente) y Comprobante PDF (#modal-pedido-exito)
  const btnConfirmarPedido = document.getElementById('btnConfirmarPedido');
  const modalCheckoutCliente = document.getElementById('modal-checkout-cliente');
  const formCheckoutCliente = document.getElementById('formCheckoutCliente');
  const btnCerrarModalCheckout = document.getElementById('btnCerrarModalCheckout');
  const btnCancelarCheckout = document.getElementById('btnCancelarCheckout');
  const btnSubmitCheckout = document.getElementById('btnSubmitCheckout');
  const checkoutErrorAlert = document.getElementById('checkoutErrorAlert');
  const checkoutErrorText = document.getElementById('checkoutErrorText');

  const inputNombreCliente = document.getElementById('inputNombreCliente');
  const inputApellidoCliente = document.getElementById('inputApellidoCliente');
  const inputTelefonoCliente = document.getElementById('inputTelefonoCliente');
  const inputCorreoCliente = document.getElementById('inputCorreoCliente');

  const radiosTipoOrden = document.querySelectorAll('input[name="tipo_orden"]');
  const pillTipoDomicilio = document.getElementById('pillTipoDomicilio');
  const pillTipoRetiro = document.getElementById('pillTipoRetiro');
  const bloqueDatosLogisticos = document.getElementById('bloqueDatosLogisticos');
  const selectMunicipioCliente = document.getElementById('selectMunicipioCliente');
  const inputDireccionCliente = document.getElementById('inputDireccionCliente');
  const inputReferenciaCliente = document.getElementById('inputReferenciaCliente');

  const checkoutSubtotalEl = document.getElementById('checkoutSubtotal');
  const checkoutPropinaEl = document.getElementById('checkoutPropina');
  const checkoutTotalEl = document.getElementById('checkoutTotal');
  const checkoutMontoAbonadoInfoEl = document.getElementById('checkoutMontoAbonadoInfo');

  const radiosMetodoPago = document.querySelectorAll('input[name="metodo_pago"]');
  const pillMetodoEfectivo = document.getElementById('pillMetodoEfectivo');
  const pillMetodoTarjeta = document.getElementById('pillMetodoTarjeta');
  const bloquePagoTarjetaSimulado = document.getElementById('bloquePagoTarjetaSimulado');
  const inputTarjetaNumero = document.getElementById('inputTarjetaNumero');
  const inputTarjetaVencimiento = document.getElementById('inputTarjetaVencimiento');
  const inputTarjetaCvv = document.getElementById('inputTarjetaCvv');

  const modalPedidoExito = document.getElementById('modal-pedido-exito');
  const clientTicketContainer = document.getElementById('clientTicketContainer');
  const btnDescargarTicketCliente = document.getElementById('btnDescargarTicketCliente');
  const btnAceptarPedidoExito = document.getElementById('btnAceptarPedidoExito');

  let procesandoCheckoutCliente = false;
  let ultimoIdPedidoCliente = null;

  function mostrarErrorCheckout(mensaje) {
    if (checkoutErrorText) {
      checkoutErrorText.textContent = mensaje;
    }
    if (checkoutErrorAlert) {
      checkoutErrorAlert.classList.add('visible');
      checkoutErrorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
      alert(mensaje);
    }
  }

  function ocultarErrorCheckout() {
    if (checkoutErrorAlert) {
      checkoutErrorAlert.classList.remove('visible');
    }
  }

  function obtenerTipoOrdenSeleccionado() {
    const seleccionado = document.querySelector('input[name="tipo_orden"]:checked');
    return seleccionado ? seleccionado.value : 'Envío a domicilio';
  }

  function obtenerMetodoPagoSeleccionado() {
    const seleccionado = document.querySelector('input[name="metodo_pago"]:checked');
    return seleccionado ? seleccionado.value : 'Efectivo';
  }

  // Alternar visibilidad y atributo required de los datos logísticos según tipo_orden (RF15)
  function sincronizarModalidadOrden() {
    const tipoOrden = obtenerTipoOrdenSeleccionado();
    const esDomicilio = (tipoOrden === 'Envío a domicilio');

    if (pillTipoDomicilio) {
      pillTipoDomicilio.classList.toggle('active', esDomicilio);
    }
    if (pillTipoRetiro) {
      pillTipoRetiro.classList.toggle('active', !esDomicilio);
    }

    if (bloqueDatosLogisticos) {
      bloqueDatosLogisticos.classList.toggle('is-hidden', !esDomicilio);
    }
    if (selectMunicipioCliente) {
      selectMunicipioCliente.required = esDomicilio;
      if (esDomicilio) {
        selectMunicipioCliente.value = 'San Salvador';
      }
    }
    if (inputDireccionCliente) {
      inputDireccionCliente.required = esDomicilio;
    }
    if (inputReferenciaCliente) {
      inputReferenciaCliente.required = esDomicilio;
    }
  }

  // Alternar visibilidad y atributo required de los campos simulados de tarjeta según metodo_pago (RNF03)
  function sincronizarMetodoPagoCliente() {
    const metodoPago = obtenerMetodoPagoSeleccionado();
    const esTarjeta = (metodoPago === 'Tarjeta');

    if (pillMetodoEfectivo) {
      pillMetodoEfectivo.classList.toggle('active', !esTarjeta);
    }
    if (pillMetodoTarjeta) {
      pillMetodoTarjeta.classList.toggle('active', esTarjeta);
    }

    if (bloquePagoTarjetaSimulado) {
      bloquePagoTarjetaSimulado.classList.toggle('is-hidden', !esTarjeta);
    }
    if (inputTarjetaNumero) {
      inputTarjetaNumero.required = esTarjeta;
    }
    if (inputTarjetaVencimiento) {
      inputTarjetaVencimiento.required = esTarjeta;
    }
    if (inputTarjetaCvv) {
      inputTarjetaCvv.required = esTarjeta;
    }
  }

  function abrirModalCheckoutCliente(items) {
    if (!modalCheckoutCliente) return;

    const { subtotal, propina, total } = calcularMontosCarrito(items);
    if (checkoutSubtotalEl) checkoutSubtotalEl.textContent = `$${subtotal.toFixed(2)}`;
    if (checkoutPropinaEl) checkoutPropinaEl.textContent = `$${propina.toFixed(2)}`;
    if (checkoutTotalEl) checkoutTotalEl.textContent = `$${total.toFixed(2)}`;
    if (checkoutMontoAbonadoInfoEl) checkoutMontoAbonadoInfoEl.textContent = `$${total.toFixed(2)}`;

    ocultarErrorCheckout();
    sincronizarModalidadOrden();
    sincronizarMetodoPagoCliente();

    modalCheckoutCliente.classList.add('active', 'open');
    modalCheckoutCliente.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
      if (inputNombreCliente && !inputNombreCliente.value.trim()) {
        inputNombreCliente.focus();
      }
    }, 80);
  }

  function cerrarModalCheckoutCliente(forzar = false) {
    if (!modalCheckoutCliente) return;
    if (procesandoCheckoutCliente && !forzar) return;

    modalCheckoutCliente.classList.remove('active', 'open');
    modalCheckoutCliente.setAttribute('aria-hidden', 'true');
    ocultarErrorCheckout();

    const exitoAbierto = modalPedidoExito && modalPedidoExito.getAttribute('aria-hidden') === 'false';
    const carritoMovilAbierto = window.innerWidth <= 1024 && cartSidebar && cartSidebar.classList.contains('open');
    if (!exitoAbierto && !carritoMovilAbierto) {
      document.body.style.overflow = '';
    }
  }

  function cerrarModalExitoPedido() {
    if (!modalPedidoExito) return;
    modalPedidoExito.classList.remove('active', 'open');
    modalPedidoExito.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Rellenar dinámicamente #clientTicketContainer con los datos reales devueltos por el backend (RF10, RF15)
  function poblarComprobanteCliente(datosPedido, payloadEnviado, itemsComprados) {
    const idPedido = parseInt(datosPedido.id_pedido ?? 0, 10) || 0;
    ultimoIdPedidoCliente = idPedido;

    if (btnDescargarTicketCliente) {
      btnDescargarTicketCliente.dataset.idPedido = String(idPedido);
    }

    const folioFormateado = `#NIX-${String(idPedido).padStart(5, '0')}`;
    const ahora = new Date();
    const fechaStr = ahora.toLocaleDateString('es-SV', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
    const horaStr = ahora.toLocaleTimeString('es-SV', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });

    const modalidadReal = datosPedido.tipo_orden || payloadEnviado.tipo_orden || 'Retiro en el local';
    const esDomicilio = (modalidadReal === 'Envío a domicilio');
    const nombreCompleto = `${payloadEnviado.nombre_cliente || ''} ${payloadEnviado.apellido_cliente || ''}`.trim();

    const subtotalNum = Number(datosPedido.subtotal ?? 0);
    const propinaNum = Number(datosPedido.propina ?? 0);
    const totalNum = Number(datosPedido.total_pagar ?? 0);
    const metodoPagoReal = datosPedido.metodo_pago || payloadEnviado.metodo_pago || 'Efectivo';
    const montoAbonadoNum = Number(datosPedido.monto_abonado ?? 0);
    const saldoPendienteNum = Number(datosPedido.saldo_pendiente ?? 0);

    const folioEl = document.getElementById('ticketFolioCliente');
    const fechaHoraEl = document.getElementById('ticketFechaHoraCliente');
    const modalidadEl = document.getElementById('ticketModalidadCliente');
    const nombreEl = document.getElementById('ticketNombreCliente');
    const telefonoEl = document.getElementById('ticketTelefonoCliente');
    const bloqueEntregaEl = document.getElementById('ticketBloqueEntrega');
    const municipioEl = document.getElementById('ticketMunicipioCliente');
    const direccionEl = document.getElementById('ticketDireccionCliente');
    const referenciaEl = document.getElementById('ticketReferenciaCliente');
    const itemsBodyEl = document.getElementById('ticketItemsCliente');
    const subtotalEl = document.getElementById('ticketSubtotalCliente');
    const propinaEl = document.getElementById('ticketPropinaCliente');
    const totalEl = document.getElementById('ticketTotalCliente');
    const metodoEl = document.getElementById('ticketMetodoPagoCliente');
    const abonadoEl = document.getElementById('ticketMontoAbonadoCliente');
    const saldoEl = document.getElementById('ticketSaldoPendienteCliente');

    if (folioEl) folioEl.textContent = folioFormateado;
    if (fechaHoraEl) fechaHoraEl.textContent = `${fechaStr} ${horaStr}`;
    if (modalidadEl) modalidadEl.textContent = modalidadReal;
    if (nombreEl) nombreEl.textContent = nombreCompleto || 'Cliente Web';
    if (telefonoEl) telefonoEl.textContent = payloadEnviado.telefono_cliente || 'N/D';

    if (bloqueEntregaEl) {
      bloqueEntregaEl.classList.toggle('is-hidden', !esDomicilio);
    }
    if (esDomicilio) {
      if (municipioEl) municipioEl.textContent = payloadEnviado.municipio || 'San Salvador';
      if (direccionEl) direccionEl.textContent = payloadEnviado.direccion || '';
      if (referenciaEl) referenciaEl.textContent = payloadEnviado.punto_referencia || 'Sin referencia adicional';
    }

    if (itemsBodyEl) {
      itemsBodyEl.innerHTML = itemsComprados.map((item) => {
        const qty = parseInt(item.qty, 10) || 1;
        const unit = parseFloat(item.price) || 0;
        const lineSub = Math.round(qty * unit * 100) / 100;
        return `
          <tr>
            <td class="ct-col-qty">${qty}x</td>
            <td>${escaparHtmlCliente(item.name)}</td>
            <td class="ct-col-unit">$${unit.toFixed(2)}</td>
            <td class="ct-col-sub">$${lineSub.toFixed(2)}</td>
          </tr>
        `;
      }).join('');
    }

    if (subtotalEl) subtotalEl.textContent = `$${subtotalNum.toFixed(2)}`;
    if (propinaEl) propinaEl.textContent = `$${propinaNum.toFixed(2)}`;
    if (totalEl) totalEl.textContent = `$${totalNum.toFixed(2)}`;
    if (metodoEl) {
      metodoEl.textContent = metodoPagoReal === 'Tarjeta'
        ? 'Tarjeta (Pago en línea)'
        : 'Efectivo (Contra entrega / caja)';
    }
    if (abonadoEl) abonadoEl.textContent = `$${montoAbonadoNum.toFixed(2)}`;
    if (saldoEl) saldoEl.textContent = `$${saldoPendienteNum.toFixed(2)}`;
  }

  // Formateo amigable para los campos sandbox de tarjeta simulada y teléfono
  if (inputTarjetaNumero) {
    inputTarjetaNumero.addEventListener('input', () => {
      const soloDigitos = inputTarjetaNumero.value.replace(/\D/g, '').substring(0, 16);
      const grupos = soloDigitos.match(/.{1,4}/g);
      inputTarjetaNumero.value = grupos ? grupos.join(' ') : '';
    });
  }
  if (inputTarjetaVencimiento) {
    inputTarjetaVencimiento.addEventListener('input', () => {
      const soloDigitos = inputTarjetaVencimiento.value.replace(/\D/g, '').substring(0, 4);
      if (soloDigitos.length >= 3) {
        inputTarjetaVencimiento.value = `${soloDigitos.substring(0, 2)}/${soloDigitos.substring(2, 4)}`;
      } else {
        inputTarjetaVencimiento.value = soloDigitos;
      }
    });
  }
  if (inputTarjetaCvv) {
    inputTarjetaCvv.addEventListener('input', () => {
      inputTarjetaCvv.value = inputTarjetaCvv.value.replace(/\D/g, '').substring(0, 4);
    });
  }

  radiosTipoOrden.forEach((radio) => {
    radio.addEventListener('change', () => {
      ocultarErrorCheckout();
      sincronizarModalidadOrden();
    });
  });

  radiosMetodoPago.forEach((radio) => {
    radio.addEventListener('change', () => {
      ocultarErrorCheckout();
      sincronizarMetodoPagoCliente();
    });
  });

  // 1) Apertura de #modal-checkout-cliente al hacer clic en #btnConfirmarPedido
  if (btnConfirmarPedido) {
    btnConfirmarPedido.addEventListener('click', () => {
      const items = obtenerProductosDelCarrito();
      if (items.length === 0) {
        alert('Tu carrito está vacío. Por favor selecciona al menos un producto antes de confirmar el pedido.');
        return;
      }
      abrirModalCheckoutCliente(items);
    });
  }

  if (btnCerrarModalCheckout) {
    btnCerrarModalCheckout.addEventListener('click', () => cerrarModalCheckoutCliente());
  }
  if (btnCancelarCheckout) {
    btnCancelarCheckout.addEventListener('click', () => cerrarModalCheckoutCliente());
  }
  if (modalCheckoutCliente) {
    modalCheckoutCliente.addEventListener('click', (e) => {
      if (e.target === modalCheckoutCliente) {
        cerrarModalCheckoutCliente();
      }
    });
  }

  // 2) Envío asíncrono (fetch) de #formCheckoutCliente a ServicioController.php?action=guardar_orden
  if (formCheckoutCliente) {
    formCheckoutCliente.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (procesandoCheckoutCliente) return;

      ocultarErrorCheckout();

      const items = obtenerProductosDelCarrito();
      if (items.length === 0) {
        mostrarErrorCheckout('Tu carrito está vacío. Agrega al menos un producto para continuar.');
        return;
      }

      const nombreCliente = (inputNombreCliente?.value || '').trim();
      const apellidoCliente = (inputApellidoCliente?.value || '').trim();
      const telefonoCliente = (inputTelefonoCliente?.value || '').trim();
      const correoCliente = (inputCorreoCliente?.value || '').trim();
      const tipoOrden = obtenerTipoOrdenSeleccionado();
      const esDomicilio = (tipoOrden === 'Envío a domicilio');
      const municipio = esDomicilio ? ((selectMunicipioCliente?.value || 'San Salvador').trim() || 'San Salvador') : null;
      const direccion = esDomicilio ? (inputDireccionCliente?.value || '').trim() : null;
      const puntoReferencia = esDomicilio ? (inputReferenciaCliente?.value || '').trim() : null;
      const metodoPago = obtenerMetodoPagoSeleccionado();
      const esTarjeta = (metodoPago === 'Tarjeta');

      // Validaciones de campos obligatorios (RF15, RNF03)
      if (!nombreCliente) {
        mostrarErrorCheckout('Por favor ingresa tu nombre.');
        inputNombreCliente?.focus();
        return;
      }
      if (!apellidoCliente) {
        mostrarErrorCheckout('Por favor ingresa tu apellido.');
        inputApellidoCliente?.focus();
        return;
      }
      if (!telefonoCliente || telefonoCliente.replace(/\D/g, '').length < 8) {
        mostrarErrorCheckout('Por favor ingresa un número de teléfono válido (ej. 7000-0000).');
        inputTelefonoCliente?.focus();
        return;
      }
      const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!correoCliente || !patronCorreo.test(correoCliente)) {
        mostrarErrorCheckout('Por favor ingresa un correo electrónico válido.');
        inputCorreoCliente?.focus();
        return;
      }

      if (esDomicilio) {
        if (municipio !== 'San Salvador') {
          mostrarErrorCheckout('El servicio a domicilio está disponible exclusivamente para el municipio de San Salvador.');
          selectMunicipioCliente?.focus();
          return;
        }
        if (!direccion) {
          mostrarErrorCheckout('La dirección exacta de entrega es obligatoria para pedidos a domicilio.');
          inputDireccionCliente?.focus();
          return;
        }
        if (!puntoReferencia) {
          mostrarErrorCheckout('Por favor indica un punto de referencia para facilitar la entrega.');
          inputReferenciaCliente?.focus();
          return;
        }
      }

      if (esTarjeta) {
        const digitosTarjeta = (inputTarjetaNumero?.value || '').replace(/\D/g, '');
        const vencimiento = (inputTarjetaVencimiento?.value || '').trim();
        const cvv = (inputTarjetaCvv?.value || '').replace(/\D/g, '');

        if (digitosTarjeta.length < 13 || digitosTarjeta.length > 16) {
          mostrarErrorCheckout('Ingresa un número de tarjeta de prueba válido (13 a 16 dígitos).');
          inputTarjetaNumero?.focus();
          return;
        }
        if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(vencimiento)) {
          mostrarErrorCheckout('Ingresa una fecha de vencimiento válida en formato MM/AA.');
          inputTarjetaVencimiento?.focus();
          return;
        }
        if (cvv.length < 3 || cvv.length > 4) {
          mostrarErrorCheckout('Ingresa un código CVV de prueba válido (3 o 4 dígitos).');
          inputTarjetaCvv?.focus();
          return;
        }
      }

      const payload = {
        id_mesa: null,
        tipo_orden: tipoOrden,
        nombre_cliente: nombreCliente,
        apellido_cliente: apellidoCliente,
        telefono_cliente: telefonoCliente,
        correo_cliente: correoCliente,
        municipio: esDomicilio ? 'San Salvador' : null,
        direccion: esDomicilio ? direccion : null,
        punto_referencia: esDomicilio ? puntoReferencia : null,
        metodo_pago: metodoPago,
        productos: items
      };

      procesandoCheckoutCliente = true;
      const htmlOriginalBoton = btnSubmitCheckout ? btnSubmitCheckout.innerHTML : '';
      if (btnSubmitCheckout) {
        btnSubmitCheckout.disabled = true;
        btnSubmitCheckout.innerHTML = '<span>Procesando pedido...</span>';
      }

      try {
        const res = await fetch('../../controllers/ServicioController.php?action=guardar_orden', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(payload)
        });

        let data = null;
        try {
          data = await res.json();
        } catch (_) {
          data = null;
        }

        if (!res.ok || !data || data.success !== true) {
          const mensajeError = (data && data.error)
            ? data.error
            : `No se pudo registrar el pedido (Error HTTP ${res.status}). Intenta nuevamente.`;
          mostrarErrorCheckout(mensajeError);
          return;
        }

        const datosPedido = data.pedido || {
          id_pedido: data.id_pedido || 0,
          tipo_orden: tipoOrden,
          metodo_pago: metodoPago
        };

        // Cerrar modal de checkout, poblar comprobante real, abrir modal de éxito y vaciar carrito
        cerrarModalCheckoutCliente(true);
        poblarComprobanteCliente(datosPedido, payload, items);

        if (modalPedidoExito) {
          modalPedidoExito.classList.add('active', 'open');
          modalPedidoExito.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }

        vaciarCarritoCliente();
        formCheckoutCliente.reset();
        sincronizarModalidadOrden();
        sincronizarMetodoPagoCliente();

        if (window.innerWidth <= 1024) {
          closeCart();
          document.body.style.overflow = 'hidden';
        }
      } catch (err) {
        console.error('Error de comunicación al registrar el pedido web:', err);
        mostrarErrorCheckout('No fue posible conectar con el servidor para registrar tu pedido. Verifica tu conexión e inténtalo de nuevo.');
      } finally {
        procesandoCheckoutCliente = false;
        if (btnSubmitCheckout) {
          btnSubmitCheckout.disabled = false;
          btnSubmitCheckout.innerHTML = htmlOriginalBoton;
        }
      }
    });
  }

  // 3) Exportación del Comprobante PDF mediante html2pdf.js (RF10, RF15)
  if (btnDescargarTicketCliente && clientTicketContainer) {
    btnDescargarTicketCliente.addEventListener('click', () => {
      const idPedidoPdf = btnDescargarTicketCliente.dataset.idPedido || ultimoIdPedidoCliente || '0';
      const nombreArchivo = `Comprobante_NIXGARU_Pedido_${idPedidoPdf}.pdf`;

      const opcionesPdf = {
        margin: [10, 10, 10, 10],
        filename: nombreArchivo,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          letterRendering: true,
          backgroundColor: '#ffffff',
          scrollY: 0
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      const htmlOriginalDescarga = btnDescargarTicketCliente.innerHTML;
      btnDescargarTicketCliente.disabled = true;
      btnDescargarTicketCliente.innerHTML = '<span>Generando PDF...</span>';

      if (typeof html2pdf !== 'undefined') {
        html2pdf()
          .set(opcionesPdf)
          .from(clientTicketContainer)
          .save()
          .then(() => {
            btnDescargarTicketCliente.innerHTML = '<span>¡Comprobante descargado!</span>';
            setTimeout(() => {
              btnDescargarTicketCliente.disabled = false;
              btnDescargarTicketCliente.innerHTML = htmlOriginalDescarga;
            }, 1600);
          })
          .catch((err) => {
            console.error('Error al generar el comprobante PDF del cliente:', err);
            btnDescargarTicketCliente.disabled = false;
            btnDescargarTicketCliente.innerHTML = htmlOriginalDescarga;
            alert('Ocurrió un inconveniente al generar el archivo PDF. Intenta nuevamente.');
          });
      } else {
        window.print();
        btnDescargarTicketCliente.disabled = false;
        btnDescargarTicketCliente.innerHTML = htmlOriginalDescarga;
      }
    });
  }

  if (btnAceptarPedidoExito) {
    btnAceptarPedidoExito.addEventListener('click', () => cerrarModalExitoPedido());
  }
  if (modalPedidoExito) {
    modalPedidoExito.addEventListener('click', (e) => {
      if (e.target === modalPedidoExito) {
        cerrarModalExitoPedido();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (modalPedidoExito && modalPedidoExito.getAttribute('aria-hidden') === 'false') {
        cerrarModalExitoPedido();
      } else if (modalCheckoutCliente && modalCheckoutCliente.getAttribute('aria-hidden') === 'false') {
        cerrarModalCheckoutCliente();
      }
    }
  });

  /**
   * ==========================================================================
   * MODAL: VISTA DETALLADA DE PRODUCTO CON INGREDIENTES DINÁMICOS
   * ==========================================================================
   */
  const productModal = document.getElementById('product-modal');
  const modalCloseBtn = document.getElementById('modal-close-btn');
  const modalProductTitle = document.getElementById('modal-product-title');
  const modalProductPrice = document.getElementById('modal-product-price');
  const modalProductDesc = document.getElementById('modal-product-desc');
  const modalProductIngredients = document.getElementById('modal-product-ingredients');
  const modalQtyMinus = document.getElementById('modal-qty-minus');
  const modalQtyPlus = document.getElementById('modal-qty-plus');
  const modalQtyVal = document.getElementById('modal-qty-val');
  const modalAddBtn = document.getElementById('modal-add-btn');

  let currentModalProduct = null;

  function openProductModal(productData) {
    if (!productModal) return;

    currentModalProduct = productData;

    if (modalProductTitle) modalProductTitle.textContent = productData.name;
    if (modalProductPrice) modalProductPrice.textContent = `$${parseFloat(productData.price).toFixed(2)}`;
    if (modalProductDesc) modalProductDesc.textContent = productData.desc || 'Delicioso producto preparado con ingredientes frescos.';
    if (modalQtyVal) modalQtyVal.textContent = '1'; // Inicia en 1 para facilitar agregar

    // 1. Mostrar ingredientes precargados inmediatamente
    if (modalProductIngredients) {
      modalProductIngredients.textContent = productData.ingredients || 'Consultando ingredientes...';
    }

    // Imagen en modal
    const modalImageContainer = document.getElementById('modal-product-image');
    if (modalImageContainer) {
      if (productData.foto) {
        modalImageContainer.innerHTML = `<img src="../../public/img/${productData.foto}" alt="${productData.name}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;" onerror="this.style.display='none';">`;
      } else {
        modalImageContainer.innerHTML = '';
      }
    }

    // 2. Consulta AJAX para traer los ingredientes cruzando productos, recetas e ingredientes
    if (productData.codigo) {
      fetch(`../../controllers/ApiController.php?action=obtener_ingredientes&codigo=${encodeURIComponent(productData.codigo)}`)
        .then(response => response.json())
        .then(data => {
          if (data && data.ingredientes && data.ingredientes.length > 0) {
            modalProductIngredients.textContent = data.ingredientes.join(', ');
          }
        })
        .catch(err => {
          console.log('Ingredientes mostrados desde atributos precargados:', err);
        });
    }

    productModal.classList.add('active');
    productModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeProductModal() {
    if (!productModal) return;
    productModal.classList.remove('active');
    productModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // ==========================================================================
  // CONTROLES RÁPIDOS EN TARJETAS DE PRODUCTO (AGREGAR / QUITAR DESDE CATÁLOGO)
  // ==========================================================================
  const productsGrid = document.querySelector('.products-grid');
  if (productsGrid) {
    productsGrid.addEventListener('click', (e) => {
      const minusBtn = e.target.closest('.card-qty-minus');
      const plusBtn = e.target.closest('.card-qty-plus');

      if (!minusBtn && !plusBtn) return;

      e.stopPropagation();
      e.preventDefault();

      const card = e.target.closest('.product-card, .producto-card');
      if (!card) return;

      const codigo = card.dataset.codigo || '';
      const name = card.dataset.name || card.querySelector('.product-name')?.textContent?.trim() || 'Producto';
      const rawPrice = card.dataset.price || card.querySelector('.product-price')?.textContent || '0.00';
      const price = parseFloat(String(rawPrice).replace(/[^0-9.]/g, '')) || 0;
      const desc = card.dataset.desc || card.querySelector('.product-description')?.textContent?.trim() || '';
      const foto = card.dataset.foto || '';
      const ingredients = card.dataset.ingredients || '';

      const producto = { codigo, name, price, desc, foto, ingredients };

      if (plusBtn) {
        modificarCantidadEnCarrito(producto, 1);
      } else if (minusBtn) {
        modificarCantidadEnCarrito(producto, -1);
      }
    });
  }

  // Escuchar clics en todas las tarjetas de producto para abrir el modal detallado
  const productCards = document.querySelectorAll('.product-card, .producto-card');
  productCards.forEach((card) => {
    card.addEventListener('click', (e) => {
      // Si el clic ocurrió dentro de los controles rápidos (+ / -) o en botones interactivos, evitar abrir el modal
      if (e.target.closest('.card-quantity-control, .qty-btn, button, a')) {
        return;
      }

      const codigo = card.dataset.codigo || '';
      const name = card.dataset.name || card.querySelector('.product-name')?.textContent || 'Producto';
      const rawPrice = card.dataset.price || card.querySelector('.product-price')?.textContent || '0.00';
      const price = parseFloat(String(rawPrice).replace(/[^0-9.]/g, '')) || 0;
      const desc = card.dataset.desc || card.querySelector('.product-description')?.textContent || '';
      const foto = card.dataset.foto || '';
      const ingredients = card.dataset.ingredients || 'Consultando ingredientes...';

      openProductModal({ codigo, name, price, desc, foto, ingredients });
    });
  });

  // Cerrar modal al hacer clic en el botón X
  if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', closeProductModal);
  }

  // Cerrar modal al hacer clic en el fondo empañado
  if (productModal) {
    productModal.addEventListener('click', (e) => {
      if (e.target === productModal) {
        closeProductModal();
      }
    });
  }

  // Cerrar con Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && productModal && productModal.classList.contains('active')) {
      closeProductModal();
    }
  });

  // Controles de cantidad dentro del modal (- / +)
  if (modalQtyMinus && modalQtyPlus && modalQtyVal) {
    modalQtyMinus.addEventListener('click', () => {
      let qty = parseInt(modalQtyVal.textContent, 10) || 0;
      if (qty > 0) {
        qty--;
        modalQtyVal.textContent = qty;
      }
    });

    modalQtyPlus.addEventListener('click', () => {
      let qty = parseInt(modalQtyVal.textContent, 10) || 0;
      qty++;
      modalQtyVal.textContent = qty;
    });
  }

  // Botón "Agregar" dentro del modal: sincroniza con el carrito y se cierra de inmediato
  if (modalAddBtn) {
    modalAddBtn.addEventListener('click', () => {
      const qty = parseInt(modalQtyVal?.textContent || '0', 10) || 0;

      if (qty > 0 && currentModalProduct) {
        // Añadir automáticamente al carrito de compras con sus cálculos
        agregarAlCarrito(currentModalProduct, qty);
        // Cerrar el modal de inmediato al hacer clic en "Agregar"
        closeProductModal();
      } else {
        alert('Por favor selecciona una cantidad mayor a 0 antes de agregar.');
      }
    });
  }

  // ==========================================================================
  // ESTILOS Y EVENTOS DEL LOGOTIPO
  // El cambio de color a terracota se gestiona exclusivamente mediante CSS (:active)
  // ==========================================================================

  // ==========================================================================
  // NAVEGACIÓN Y DESPLAZAMIENTO SUAVE ("INICIO" Y "MENÚ")
  // ==========================================================================
  function desplazarseHaciaInicio() {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
    if (window.history.pushState) {
      window.history.pushState(null, null, '#inicio');
    }
  }

  function desplazarseHaciaMenu() {
    const targetElement = document.getElementById('menu');
    if (!targetElement) return;

    const header = document.querySelector('.main-header');
    const headerHeight = header ? header.getBoundingClientRect().height : 0;
    // Margen de holgura visual (18px) para que las pastillas de categoría queden 100% visibles
    const paddingOffset = 18;
    const elementTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
    const targetScroll = elementTop - headerHeight - paddingOffset;

    window.scrollTo({
      top: Math.max(0, targetScroll),
      behavior: 'smooth'
    });

    if (window.history.pushState) {
      window.history.pushState(null, null, '#menu');
    }
  }

  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach((link) => {
    link.addEventListener('click', (e) => {
      const href = (link.getAttribute('href') || '').trim();
      const text = link.textContent.trim().toLowerCase();

      // Anclaje garantizado hacia el punto más alto de la página
      if (href === '#inicio' || text === 'inicio') {
        e.preventDefault();
        desplazarseHaciaInicio();
        navLinks.forEach((l) => l.classList.remove('active'));
        link.classList.add('active');
        return;
      }

      // Anclaje con offset exacto para que las pastillas del menú no queden tapadas
      if (href === '#menu' || text === 'menú' || text === 'menu') {
        e.preventDefault();
        desplazarseHaciaMenu();
        navLinks.forEach((l) => l.classList.remove('active'));
        link.classList.add('active');
        return;
      }

      navLinks.forEach((l) => l.classList.remove('active'));
      link.classList.add('active');
    });
  });

  // Botón "Explorar menú" en el banner promocional
  const btnExplore = document.querySelector('.btn-explore');
  if (btnExplore) {
    btnExplore.addEventListener('click', (e) => {
      const href = (btnExplore.getAttribute('href') || '').trim();
      if (href === '#menu') {
        e.preventDefault();
        desplazarseHaciaMenu();
        const menuNavLink = document.querySelector('.nav-link[href="#menu"]');
        if (menuNavLink) {
          navLinks.forEach((l) => l.classList.remove('active'));
          menuNavLink.classList.add('active');
        }
      }
    });
  }

  // Sincronización inicial de contadores en tarjetas de catálogo al cargar la página
  sincronizarContadoresTarjetas();

  /**
   * ==========================================================================
   * PLANO DE MESAS: PERSISTENCIA Y SINCRONIZACIÓN CON LOCALSTORAGE (mesas.php)
   * ==========================================================================
   */
  const tableStatusSelects = document.querySelectorAll('.table-status-select');
  const summaryCountsElement = document.querySelector('.summary-counts');
  const tableCards = document.querySelectorAll('.table-card');
  const cuentaMesaTitulo = document.getElementById('cuentaMesaTitulo');
  const cuentaMesaEstado = document.getElementById('cuentaMesaEstado');

  // Actualiza dinámicamente la barra inferior de conteo de mesas
  function actualizarResumenMesas() {
    if (!summaryCountsElement) return;
    const selects = document.querySelectorAll('.table-status-select');
    let disponibles = 0, ocupadas = 0, proceso = 0, sinServicio = 0;

    selects.forEach(select => {
      if (select.value === 'disponible') disponibles++;
      else if (select.value === 'ocupada') ocupadas++;
      else if (select.value === 'proceso') proceso++;
      else if (select.value === 'sin-servicio') sinServicio++;
    });

    summaryCountsElement.innerHTML = `Resumen actual: <strong>${disponibles} Disponibles</strong> | <strong>${ocupadas} Ocupadas</strong> | <strong>${proceso} En proceso</strong> | <strong>${sinServicio} Sin servicio</strong>`;
  }

  // Actualiza el círculo indicador de estado en el panel lateral de la cuenta (únicamente el punto de color)
  function actualizarEstadoCuentaLateral(estado) {
    const elEstado = document.getElementById('cuentaMesaEstado') || cuentaMesaEstado;
    if (!elEstado) return;
    elEstado.className = 'cuenta-status-badge';
    elEstado.style.display = 'inline-flex';
    
    const etiquetas = {
      'disponible':   { texto: 'Disponible', clase: 'badge-disponible', dot: 'dot-disponible' },
      'ocupada':      { texto: 'Ocupada', clase: 'badge-ocupada', dot: 'dot-ocupada' },
      'proceso':      { texto: 'En proceso', clase: 'badge-proceso', dot: 'dot-proceso' },
      'sin-servicio': { texto: 'Sin servicio', clase: 'badge-sin-servicio', dot: 'dot-sin-servicio' }
    };

    const cfg = etiquetas[estado] || etiquetas['disponible'];
    elEstado.classList.add(cfg.clase);
    elEstado.setAttribute('title', cfg.texto);
    elEstado.innerHTML = `<span class="status-dot ${cfg.dot}"></span>`;
  }

  // Carga la orden guardada de una mesa en el panel lateral derecho ("Mesa X")
  function cargarOrdenMesaEnPanelLateral(mesaId) {
    const listaProductosCuenta = document.getElementById('listaProductosCuenta');
    const cuentaSubtotal = document.getElementById('cuentaSubtotal');
    const cuentaPropina = document.getElementById('cuentaPropina');
    const cuentaTotal = document.getElementById('cuentaTotal');

    if (!listaProductosCuenta) return;

    // Verificar si la página actual ya cargó la orden desde el servidor PHP (fuente de la BD)
    const serverItems = listaProductosCuenta.querySelectorAll('.cuenta-item');
    const urlParams = new URLSearchParams(window.location.search);
    const mesaUrl = urlParams.get('mesa_id') || urlParams.get('mesa');

    // Si coincide la mesa con la URL y PHP ya renderizó elementos de la BD, preservarlos
    if (mesaUrl && String(mesaUrl) === String(mesaId) && serverItems.length > 0) {
      return;
    }

    // Leer orden de localStorage
    const ordenDataRaw = localStorage.getItem('orden_mesa_' + mesaId);
    let items = [];

    if (ordenDataRaw) {
      try {
        items = JSON.parse(ordenDataRaw);
        if (!Array.isArray(items)) items = [];
      } catch (e) {
        console.error('Error al parsear orden de mesa ' + mesaId, e);
        items = [];
      }
    }

    if (items.length === 0) {
      listaProductosCuenta.innerHTML = `
        <div class="empty-cuenta-msg" style="text-align: center; color: #6C757D; font-size: 13.5px; padding: 36px 12px;">
          <p style="margin: 0 0 6px 0; font-weight: 600; color: #343A40;">Mesa disponible sin consumos activos</p>
          <span style="font-size: 12.5px; opacity: 0.85;">No hay pedidos pendientes de pago para esta mesa.</span>
        </div>
      `;
      if (cuentaSubtotal) cuentaSubtotal.textContent = '$0.00';
      if (cuentaPropina) cuentaPropina.textContent = '$0.00';
      if (cuentaTotal) cuentaTotal.textContent = '$0.00';
      return;
    }

    // Renderizar los productos reales guardados
    let subtotal = 0;
    let html = '';

    items.forEach(item => {
      const qty = parseInt(item.qty, 10) || 1;
      const unitPrice = parseFloat(item.price ?? item.unitPrice) || 0;
      const itemSubtotal = unitPrice * qty;
      subtotal += itemSubtotal;

      html += `
        <div class="cuenta-item">
          <span class="item-qty-badge">${qty}</span>
          <span class="item-name">${item.name || item.nombre || 'Producto'}</span>
          <span class="item-price">$${itemSubtotal.toFixed(2)}</span>
        </div>
      `;
    });

    listaProductosCuenta.innerHTML = html;

    const propina = subtotal * 0.10;
    const total = subtotal + propina;

    if (cuentaSubtotal) cuentaSubtotal.textContent = `$${subtotal.toFixed(2)}`;
    if (cuentaPropina) cuentaPropina.textContent = `$${propina.toFixed(2)}`;
    if (cuentaTotal) cuentaTotal.textContent = `$${total.toFixed(2)}`;
  }

  // Función encargada de actualizar la mesa seleccionada y sincronizarla sin alterar selectores
  window.seleccionarMesa = function(id, estado, idRealParam) {
    const mesaId = String(id);
    const mesaRealId = idRealParam ? String(idRealParam) : mesaId;

    // 1. Guardar en localStorage la mesa activa seleccionada
    localStorage.setItem('mesaActiva', mesaRealId);
    sessionStorage.setItem('mesaActiva', mesaRealId);

    // 2. Resaltar visualmente ÚNICAMENTE la tarjeta de la mesa activa en la cuadrícula sin alterar colores de selects
    const allCards = document.querySelectorAll('.table-card');
    allCards.forEach(card => {
      const matchNum = String(card.dataset.tableNumber || '') === mesaId;
      const matchId = String(card.dataset.tableId || '') === mesaRealId || String(card.dataset.tableId || '') === mesaId;
      if (matchNum || matchId) {
        card.classList.add('active');
      } else {
        card.classList.remove('active');
      }
    });

    // 3. Actualizar el título del panel lateral derecho (exclusivamente "Mesa X", sin prefijo "Cuenta -")
    const cuentaMesaTitulo = document.getElementById('cuentaMesaTitulo');
    if (cuentaMesaTitulo) {
      const numLimpio = mesaId.replace(/^Mesa\s*/i, '').trim();
      cuentaMesaTitulo.textContent = `Mesa ${numLimpio}`;
    }

    // 4. Actualizar el indicador de estado en el panel lateral (solo círculo de color)
    actualizarEstadoCuentaLateral(estado);

    // 5. Alternar visibilidad: ocultar estado vacío y mostrar contenedor de productos/totales
    const emptyState = document.getElementById('cuentaEmptyState');
    const detalleContainer = document.getElementById('cuentaDetalleContainer');
    if (emptyState) emptyState.style.display = 'none';
    if (detalleContainer) {
      detalleContainer.style.display = 'flex';
      detalleContainer.style.flexDirection = 'column';
    }

    // 6. Habilitar y actualizar botones de acción del panel lateral
    const btnAgregar = document.getElementById('btnAgregarProducto');
    if (btnAgregar) {
      btnAgregar.style.pointerEvents = 'auto';
      btnAgregar.style.opacity = '1';
      btnAgregar.style.cursor = 'pointer';
      btnAgregar.removeAttribute('tabindex');
      btnAgregar.removeAttribute('aria-disabled');
      btnAgregar.dataset.mesa = mesaRealId;
      btnAgregar.dataset.numeroMesa = mesaId;
      btnAgregar.href = `gestion_orden.php?mesa_id=${encodeURIComponent(mesaRealId)}`;
    }

    const btnCerrar = document.getElementById('btnCerrarCuenta');
    if (btnCerrar) {
      btnCerrar.style.pointerEvents = 'auto';
      btnCerrar.style.opacity = '1';
      btnCerrar.style.cursor = 'pointer';
      btnCerrar.removeAttribute('tabindex');
      btnCerrar.removeAttribute('aria-disabled');
      btnCerrar.dataset.mesa = mesaRealId;
      btnCerrar.dataset.numeroMesa = mesaId;
      btnCerrar.href = `cierre_cuenta.php?mesa_id=${encodeURIComponent(mesaRealId)}`;
    }

    // 7. Sincronizar URL del navegador de forma transparente sin recargar la página
    if (window.history && window.history.replaceState) {
      const newUrl = `mesas.php?mesa_id=${encodeURIComponent(mesaRealId)}`;
      window.history.replaceState({ mesa_id: mesaRealId }, '', newUrl);
    }

    // 8. Cargar automáticamente la orden de esa mesa
    cargarOrdenMesaEnPanelLateral(mesaRealId);
  };

  // Inicializar plano de mesas al cargar mesas.php
  function inicializarPlanoMesas() {
    const allCards = document.querySelectorAll('.table-card');
    if (!allCards || allCards.length === 0) return;

    allCards.forEach(card => {
      const mesaId = card.dataset.tableNumber;
      if (!mesaId) return;

      // Leer orden guardada para calcular consumo real en la tarjeta de la mesa si existe en localStorage
      const ordenRaw = localStorage.getItem('orden_mesa_' + mesaId);
      if (ordenRaw) {
        try {
          const items = JSON.parse(ordenRaw);
          if (Array.isArray(items) && items.length > 0) {
            const subtotalMesa = items.reduce((acc, it) => acc + ((parseFloat(it.price ?? it.unitPrice) || 0) * (parseInt(it.qty, 10) || 1)), 0);
            const totalConPropina = subtotalMesa * 1.10;
            const consumoVal = card.querySelector('.table-data-row:first-child .data-value');
            if (consumoVal) {
              consumoVal.textContent = `$${totalConPropina.toFixed(2)}`;
            }
          }
        } catch (e) {
          console.error('Error al procesar consumo de mesa ' + mesaId, e);
        }
      }
    });

    actualizarResumenMesas();

    // Seleccionar la mesa activa únicamente si viene explícitamente en el parámetro GET de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const mesaUrl = urlParams.get('mesa_id') || urlParams.get('mesa');

    if (mesaUrl) {
      localStorage.setItem('mesaActiva', mesaUrl);
      sessionStorage.setItem('mesaActiva', mesaUrl);

      const cardActiva = Array.from(allCards).find(c => String(c.dataset.tableNumber) === String(mesaUrl) || String(c.dataset.tableId) === String(mesaUrl));
      if (cardActiva) {
        cardActiva.classList.add('active');
        const selectActiva = cardActiva.querySelector('.table-status-select');
        const estadoActivo = selectActiva ? selectActiva.value : 'disponible';
        const numDisplay = cardActiva.dataset.tableNumber || mesaUrl;
        const idReal = cardActiva.dataset.tableId || mesaUrl;
        seleccionarMesa(numDisplay, estadoActivo, idReal);
      }
    } else {
      // Estado inicial vacío: ninguna mesa seleccionada
      allCards.forEach(c => c.classList.remove('active'));
    }
  }

  // Ejecutar inicialización si estamos en la vista de mesas
  if (tableCards.length > 0) {
    inicializarPlanoMesas();
  }

  // Cambio de estado en el selector de cada mesa (guarda en localStorage y actualiza clases solo al cambiar select)
  tableStatusSelects.forEach(select => {
    select.addEventListener('change', () => {
      const parentCard = select.closest('.table-card');
      const mesaNum = parentCard ? (parentCard.dataset.tableNumber || '1') : '1';
      const mesaId = parentCard ? (parentCard.dataset.tableId || mesaNum) : '1';

      select.classList.remove('status-disponible', 'status-ocupada', 'status-proceso', 'status-sin-servicio');
      select.classList.add(`status-${select.value}`);
      
      // Guardar en localStorage únicamente cuando el usuario cambia el selector
      localStorage.setItem('estado_mesa_' + mesaId, select.value);
      localStorage.setItem('estado_mesa_' + mesaNum, select.value);

      if (parentCard && parentCard.classList.contains('active')) {
        actualizarEstadoCuentaLateral(select.value);
      }
      actualizarResumenMesas();
    });
  });

  // Event listener para clic en las tarjetas de mesa:
  // Gestiona exclusivamente la selección visual (.active) y el panel lateral sin recargar la página ni alterar selects
  tableCards.forEach(card => {
    card.addEventListener('click', (e) => {
      // Ignorar clics dentro del selector para permitir interacción directa con el dropdown de estados
      if (e.target.closest('.table-status-select') || e.target.tagName.toLowerCase() === 'select') {
        return;
      }

      const mesaNum = card.dataset.tableNumber || '';
      const mesaIdReal = card.dataset.tableId || mesaNum;
      const select = card.querySelector('.table-status-select');
      const estadoActual = select ? select.value : 'disponible';

      seleccionarMesa(mesaNum, estadoActual, mesaIdReal);

      // En vista móvil (<= 1024px), desplegar automáticamente el panel de detalle como modal superpuesto
      if (window.innerWidth <= 1024) {
        abrirDetalleMovil();
      } else {
        // En vista de escritorio (> 1024px), si el panel lateral de la cuenta está oculto, volverlo visible de inmediato
        const panelCuenta = document.querySelector('aside.cuenta-panel') || document.getElementById('cuentaPanel');
        const layoutMesas = document.querySelector('.mesas-layout') || document.getElementById('mesasLayout');
        const estaOculto = (panelCuenta && panelCuenta.classList.contains('hidden')) ||
                           (layoutMesas && layoutMesas.classList.contains('detail-hidden'));

        if (estaOculto) {
          const btnToggle = document.getElementById('btnToggleDetalle');
          if (btnToggle && panelCuenta && panelCuenta.classList.contains('hidden')) {
            btnToggle.click();
          } else {
            // Replicación directa de apertura en caso de ausencia del botón o desincronización de clases
            if (panelCuenta) {
              panelCuenta.classList.remove('hidden');
              panelCuenta.setAttribute('aria-hidden', 'false');
            }
            if (layoutMesas) {
              layoutMesas.classList.remove('detail-hidden');
            }
            if (btnToggle) {
              btnToggle.setAttribute('aria-expanded', 'true');
              btnToggle.classList.remove('panel-closed');
              btnToggle.classList.add('active');
            }
          }
        }
      }
    });
  });

  /**
   * ==========================================================================
   * GESTIÓN DEL PANEL DE DETALLE RESPONSIVO Y MENÚ DE PERFIL
   * ==========================================================================
   */
  // 1. Menú Desplegable del Perfil del Empleado (Header)
  const userProfileBadge = document.getElementById('userProfileBadge');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  const userProfileWrapper = document.querySelector('.user-profile-dropdown-wrapper');

  if (userProfileBadge && userDropdownMenu) {
    userProfileBadge.addEventListener('click', (e) => {
      e.stopPropagation();
      const isExpanded = userProfileBadge.getAttribute('aria-expanded') === 'true';
      userProfileBadge.setAttribute('aria-expanded', !isExpanded);
      userDropdownMenu.classList.toggle('open');
      userDropdownMenu.classList.toggle('show');
      userDropdownMenu.setAttribute('aria-hidden', isExpanded ? 'true' : 'false');
      if (userProfileWrapper) {
        userProfileWrapper.classList.toggle('open');
      }
    });

    // Soporte de accesibilidad para teclado (Enter / Espacio)
    userProfileBadge.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        userProfileBadge.click();
      }
    });

    // Cerrar el dropdown al hacer clic fuera del contenedor
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.user-profile-dropdown-wrapper')) {
        userDropdownMenu.classList.remove('open', 'show');
        userDropdownMenu.setAttribute('aria-hidden', 'true');
        userProfileBadge.setAttribute('aria-expanded', 'false');
        if (userProfileWrapper) userProfileWrapper.classList.remove('open');
      }
    });

    // Cerrar el dropdown con tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        userDropdownMenu.classList.remove('open', 'show');
        userDropdownMenu.setAttribute('aria-hidden', 'true');
        userProfileBadge.setAttribute('aria-expanded', 'false');
        if (userProfileWrapper) userProfileWrapper.classList.remove('open');
      }
    });
  }

  // 2. Comportamiento del Panel de Detalle en Escritorio y Móviles (mesas.php, gestion_orden.php y cierre_cuenta.php)
  const btnToggleDetalle = document.getElementById('btnToggleDetalle');
  const panelSidebar = document.querySelector('aside.cuenta-panel, aside.active-order-sidebar, aside.ticket-sidebar, aside.cierre-ticket-panel');
  const layoutContainer = document.querySelector('.mesas-layout, .order-layout, .cierre-layout');
  const closeBtns = document.querySelectorAll('#cuentaCloseBtn, #activeOrderCloseBtn, #ticketCloseBtn, .cuenta-close-btn, .ticket-close-btn');
  const backdrops = document.querySelectorAll('#cuentaBackdrop, #activeOrderBackdrop, #cierreBackdrop, .cuenta-backdrop');

  // Funciones para abrir y cerrar el panel superpuesto en móviles o escritorio
  function abrirDetalleMovil() {
    if (!panelSidebar) return;
    panelSidebar.classList.add('open', 'active');
    panelSidebar.classList.remove('hidden');
    panelSidebar.setAttribute('aria-hidden', 'false');

    backdrops.forEach(b => {
      b.classList.add('open', 'active');
      b.setAttribute('aria-hidden', 'false');
    });
    document.body.style.overflow = 'hidden';

    if (btnToggleDetalle) {
      btnToggleDetalle.classList.add('active');
      btnToggleDetalle.classList.remove('panel-closed');
      btnToggleDetalle.setAttribute('aria-expanded', 'true');
    }
  }

  function cerrarDetalleMovil() {
    if (!panelSidebar) return;
    panelSidebar.classList.remove('open', 'active');
    panelSidebar.setAttribute('aria-hidden', 'true');

    backdrops.forEach(b => {
      b.classList.remove('open', 'active');
      b.setAttribute('aria-hidden', 'true');
    });
    document.body.style.overflow = '';

    if (btnToggleDetalle) {
      btnToggleDetalle.classList.remove('active');
      btnToggleDetalle.classList.add('panel-closed');
      btnToggleDetalle.setAttribute('aria-expanded', 'false');
    }
  }

  window.abrirDetalleMovil = abrirDetalleMovil;
  window.cerrarDetalleMovil = cerrarDetalleMovil;

  // Alternar visibilidad del panel lateral con el botón "Detalle":
  // - En móviles (<= 1024px): abre y cierra el modal drawer con fondo oscuro y difuminado
  // - En escritorio (> 1024px): oculta y muestra el panel lateral expandiendo el área principal
  if (btnToggleDetalle && panelSidebar) {
    btnToggleDetalle.addEventListener('click', (e) => {
      e.preventDefault();
      if (window.innerWidth <= 1024) {
        if (panelSidebar.classList.contains('open') || panelSidebar.classList.contains('active')) {
          cerrarDetalleMovil();
        } else {
          abrirDetalleMovil();
        }
      } else {
        const isHidden = panelSidebar.classList.toggle('hidden');
        if (layoutContainer) {
          layoutContainer.classList.toggle('detail-hidden', isHidden);
        }
        btnToggleDetalle.setAttribute('aria-expanded', !isHidden ? 'true' : 'false');
        btnToggleDetalle.classList.toggle('panel-closed', isHidden);
        btnToggleDetalle.classList.toggle('active', !isHidden);
        panelSidebar.setAttribute('aria-hidden', isHidden ? 'true' : 'false');
      }
    });
  }

  // Botones "X" dentro del panel en vista móvil/drawer
  closeBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarDetalleMovil();
    });
  });

  // Clic en el fondo oscuro difuminado (Backdrop)
  backdrops.forEach(b => {
    b.addEventListener('click', () => {
      cerrarDetalleMovil();
    });
  });

  // Cerrar modal al presionar tecla Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && window.innerWidth <= 1024) {
      cerrarDetalleMovil();
    }
  });

  // Limpiar estados móviles al redimensionar a pantalla de escritorio
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      backdrops.forEach(b => {
        b.classList.remove('open', 'active');
        b.setAttribute('aria-hidden', 'true');
      });
      if (panelSidebar) {
        panelSidebar.classList.remove('open', 'active');
        // Si no fue ocultado explícitamente por el botón Detalle, mantenerlo visible en escritorio
        if (!layoutContainer || !layoutContainer.classList.contains('detail-hidden')) {
          panelSidebar.classList.remove('hidden');
          panelSidebar.setAttribute('aria-hidden', 'false');
          if (btnToggleDetalle) {
            btnToggleDetalle.classList.add('active');
            btnToggleDetalle.classList.remove('panel-closed');
            btnToggleDetalle.setAttribute('aria-expanded', 'true');
          }
        }
      }
      // Restaurar scroll de body si no hay otros modales abiertos
      const productModal = document.getElementById('product-modal');
      const cartBackdropEl = document.getElementById('cart-backdrop');
      const checkoutModalEl = document.getElementById('modal-checkout-cliente');
      const exitoModalEl = document.getElementById('modal-pedido-exito');
      if ((!productModal || !productModal.classList.contains('active')) &&
          (!cartBackdropEl || !cartBackdropEl.classList.contains('active')) &&
          (!checkoutModalEl || checkoutModalEl.getAttribute('aria-hidden') !== 'false') &&
          (!exitoModalEl || exitoModalEl.getAttribute('aria-hidden') !== 'false')) {
        document.body.style.overflow = '';
      }
    }
  });

  // Botón "Agregar Producto" en panel lateral de mesas.php:
  // Redirige a gestion_orden.php manteniendo el ID de la mesa activa en la URL
  const btnAgregarProducto = document.getElementById('btnAgregarProducto');
  if (btnAgregarProducto) {
    btnAgregarProducto.addEventListener('click', (e) => {
      if (btnAgregarProducto.style.pointerEvents === 'none' || btnAgregarProducto.getAttribute('aria-disabled') === 'true' || !btnAgregarProducto.dataset.mesa) {
        e.preventDefault();
        return;
      }
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = btnAgregarProducto.dataset.mesa || urlParams.get('mesa_id') || urlParams.get('mesa');
      if (!mesaActiva) {
        e.preventDefault();
        return;
      }
      localStorage.setItem('mesaActiva', mesaActiva);
      window.location.href = `gestion_orden.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: GESTIÓN DE ORDEN (gestion_orden.php)
   * ==========================================================================
   */
  const btnMapaMesas = document.getElementById('btnMapaMesas');
  const btnEnviarCocina = document.getElementById('btnEnviarCocina');
  const btnActualizarOrden = document.getElementById('btnActualizarOrden');

  // Redirección al presionar "Mapa de mesas"
  if (btnMapaMesas) {
    btnMapaMesas.addEventListener('click', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = urlParams.get('mesa_id') || urlParams.get('mesa') || localStorage.getItem('mesaActiva') || '1';
      window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
    });
  }

  // 2. Botón "Enviar a Cocina" en gestion_orden.php:
  // - Captura productos del carrito actual
  // - Guarda en base de datos (pedidos y detalles_pedido) vía api/guardar_orden.php
  // - Actualiza el estado de la mesa a 'En proceso'
  // - Redirige de regreso a mesas.php?mesa_id=X
  if (btnEnviarCocina) {
    btnEnviarCocina.addEventListener('click', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = btnEnviarCocina.dataset.mesa || urlParams.get('mesa_id') || urlParams.get('mesa') || localStorage.getItem('mesaActiva') || sessionStorage.getItem('mesaActiva') || '1';

      // Capturar productos del carrito actual
      const itemsCarrito = [];
      const orderListEl = document.getElementById('activeOrderList');
      if (orderListEl) {
        const orderItems = orderListEl.querySelectorAll('.active-order-item');
        orderItems.forEach(item => {
          const id = item.dataset.id || '';
          const name = item.querySelector('.active-item-title')?.textContent.trim() || 'Producto';
          const qty = parseInt(item.querySelector('.qty-circle-value')?.textContent, 10) || 1;
          const unitPrice = parseFloat(item.dataset.unitPrice) || 0;

          if (id && qty > 0) {
            itemsCarrito.push({
              id: id,
              name: name,
              price: unitPrice,
              qty: qty
            });
          }
        });
      }

      if (itemsCarrito.length === 0) {
        alert('Por favor agrega al menos un producto a la orden antes de enviar a cocina.');
        return;
      }

      // Animación visual de confirmación
      const textoOriginal = btnEnviarCocina.innerHTML;
      btnEnviarCocina.innerHTML = `<span>¡Guardando y enviando a cocina...!</span>`;
      btnEnviarCocina.disabled = true;

      // Enviar al backend PHP (MySQL) mediante AJAX Fetch
      fetch('../../controllers/ServicioController.php?action=guardar_orden', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id_mesa: (mesaActiva && !isNaN(parseInt(mesaActiva, 10)) && !mesaActiva.toString().startsWith('M')) ? parseInt(mesaActiva, 10) : mesaActiva,
          productos: itemsCarrito,
          estado_mesa: 'En proceso',
          estado_pedido: 'En preparación'
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Guardar en localStorage asociados a la mesa activa para sincronización visual
          localStorage.setItem('orden_mesa_' + mesaActiva, JSON.stringify(itemsCarrito));
          localStorage.setItem('estado_mesa_' + mesaActiva, 'proceso');
          localStorage.setItem('mesaActiva', mesaActiva);

          setTimeout(() => {
            window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
          }, 350);
        } else {
          alert('Error al guardar la orden en la base de datos: ' + (data.error || 'Error desconocido'));
          btnEnviarCocina.innerHTML = textoOriginal;
          btnEnviarCocina.disabled = false;
        }
      })
      .catch(err => {
        console.error('Error al enviar la orden a api/guardar_orden.php:', err);
        // Fallback local en caso de pérdida de red momentánea
        localStorage.setItem('orden_mesa_' + mesaActiva, JSON.stringify(itemsCarrito));
        localStorage.setItem('estado_mesa_' + mesaActiva, 'proceso');
        localStorage.setItem('mesaActiva', mesaActiva);
        window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
      });
    });
  }

  // Botón "Actualizar Orden" en gestion_orden.php
  if (btnActualizarOrden) {
    btnActualizarOrden.addEventListener('click', () => {
      const mesaActiva = localStorage.getItem('mesaActiva') || sessionStorage.getItem('mesaActiva') || '1';

      const itemsCarrito = [];
      const orderListEl = document.getElementById('activeOrderList');
      if (orderListEl) {
        const orderItems = orderListEl.querySelectorAll('.active-order-item');
        orderItems.forEach(item => {
          const id = item.dataset.id || '';
          const name = item.querySelector('.active-item-title')?.textContent.trim() || 'Producto';
          const qty = parseInt(item.querySelector('.qty-circle-value')?.textContent, 10) || 1;
          const unitPrice = parseFloat(item.dataset.unitPrice) || 0;

          itemsCarrito.push({
            id: id,
            name: name,
            price: unitPrice,
            qty: qty
          });
        });
      }

      localStorage.setItem('orden_mesa_' + mesaActiva, JSON.stringify(itemsCarrito));

      const textoOriginal = btnActualizarOrden.innerHTML;
      btnActualizarOrden.innerHTML = `<span>¡Orden Guardada!</span>`;
      setTimeout(() => {
        btnActualizarOrden.innerHTML = textoOriginal;
      }, 1000);
    });
  }

  // Sincronizar número de mesa activa en gestion_orden.php
  const urlParamsGO = new URLSearchParams(window.location.search);
  const mesaActivaActual = urlParamsGO.get('mesa_id') || urlParamsGO.get('mesa') || localStorage.getItem('mesaActiva') || sessionStorage.getItem('mesaActiva') || '1';
  localStorage.setItem('mesaActiva', mesaActivaActual);
  const activeOrderPillText = document.querySelector('.order-pill-text');
  const activeOrderTitle = document.querySelector('.active-order-title');
  if (activeOrderPillText) {
    activeOrderPillText.textContent = `Orden Activa – Mesa ${mesaActivaActual}`;
  }
  if (activeOrderTitle) {
    activeOrderTitle.textContent = `Cuenta - Mesa ${mesaActivaActual}`;
  }

  // Filtros de categoría en catálogo de orden (gestion_orden.php)
  const orderCategoryPills = document.querySelectorAll('.order-category-pill');
  orderCategoryPills.forEach(pill => {
    pill.addEventListener('click', () => {
      orderCategoryPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');

      const filterCat = (pill.dataset.category || 'todos').toLowerCase();
      const cards = document.querySelectorAll('.order-product-card');

      cards.forEach(card => {
        const cardCat = (card.dataset.category || '').toLowerCase();
        if (filterCat === 'todos' || cardCat === filterCat) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  /**
   * ==========================================================================
   * GESTIÓN DE ORDEN: CARRITO DE LA CUENTA, AGRUPACIÓN Y TOTALES
   * ==========================================================================
   */
  const activeOrderList = document.getElementById('activeOrderList');
  const orderItemsBadge = document.getElementById('orderItemsBadge');
  const orderSubtotal = document.getElementById('orderSubtotal');
  const orderTip = document.getElementById('orderTip');
  const orderTotal = document.getElementById('orderTotal');

  // Función para recalcular el total de ítems, subtotal, propina (10%) y total de la cuenta
  function actualizarTotalesOrden() {
    if (!activeOrderList) return;
    const items = activeOrderList.querySelectorAll('.active-order-item');
    let totalItems = 0;
    let subtotalAcumulado = 0;

    items.forEach(item => {
      const qtySpan = item.querySelector('.qty-circle-value');
      const qty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      
      // Obtener o inferir precio unitario
      let unitPrice = parseFloat(item.dataset.unitPrice);
      if (isNaN(unitPrice)) {
        const priceText = item.querySelector('.active-item-price')?.textContent || '$0.00';
        const parsedPrice = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
        unitPrice = qty > 0 ? (parsedPrice / qty) : parsedPrice;
        item.dataset.unitPrice = unitPrice;
      }

      totalItems += qty;
      subtotalAcumulado += (unitPrice * qty);

      // Sincronizar el precio total de cada fila (precio unitario * cantidad)
      const priceSpan = item.querySelector('.active-item-price');
      if (priceSpan) {
        priceSpan.textContent = `$${(unitPrice * qty).toFixed(2)}`;
      }
    });

    const propina = subtotalAcumulado * 0.10;
    const totalGeneral = subtotalAcumulado + propina;

    if (orderItemsBadge) {
      orderItemsBadge.textContent = `${totalItems} Items`;
    }
    if (orderSubtotal) {
      orderSubtotal.textContent = `$${subtotalAcumulado.toFixed(2)} USD`;
    }
    if (orderTip) {
      orderTip.textContent = `$${propina.toFixed(2)} USD`;
    }
    if (orderTotal) {
      orderTotal.textContent = `$${totalGeneral.toFixed(2)} USD`;
    }

    // Sincronizar contadores interactivos de las tarjetas del catálogo
    sincronizarContadoresCatalogo();
  }

  // Sincronizar los contadores visuales ([-] [ N ] [+]) de cada tarjeta del catálogo
  function sincronizarContadoresCatalogo() {
    const cards = document.querySelectorAll('.order-product-card');
    if (!cards.length) return;

    cards.forEach(card => {
      const id = (card.dataset.id || card.dataset.codigo || '').trim();
      const nombre = (card.dataset.nombre || card.dataset.name || card.querySelector('.order-product-name')?.textContent || '').trim().toLowerCase();
      const qtySpan = card.querySelector('.card-qty-value');
      if (!qtySpan) return;

      let count = 0;
      if (activeOrderList) {
        const item = Array.from(activeOrderList.querySelectorAll('.active-order-item')).find(it => {
          if (id && it.dataset.id && it.dataset.id === id) return true;
          const itemTitle = it.querySelector('.active-item-title')?.textContent.trim().toLowerCase();
          return itemTitle && itemTitle === nombre;
        });
        if (item) {
          const itemQtySpan = item.querySelector('.qty-circle-value');
          count = parseInt(itemQtySpan ? itemQtySpan.textContent : '0', 10) || 0;
        }
      }
      qtySpan.textContent = count;
    });
  }

  // Controles de cantidad (- / +) y eliminación (basurero) en la lista de la cuenta
  if (activeOrderList) {
    activeOrderList.addEventListener('click', (e) => {
      const target = e.target.closest('button');
      if (!target) return;

      const action = target.dataset.action;
      const orderItem = target.closest('.active-order-item');
      if (!orderItem) return;

      const qtySpan = orderItem.querySelector('.qty-circle-value');
      const priceSpan = orderItem.querySelector('.active-item-price');
      let currentQty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      const unitPrice = parseFloat(orderItem.dataset.unitPrice) || 0;

      if (action === 'increase' && qtySpan) {
        currentQty++;
        qtySpan.textContent = currentQty;
        if (priceSpan) {
          priceSpan.textContent = `$${(unitPrice * currentQty).toFixed(2)}`;
        }
        actualizarTotalesOrden();
      } else if (action === 'decrease' && qtySpan) {
        if (currentQty > 1) {
          currentQty--;
          qtySpan.textContent = currentQty;
          if (priceSpan) {
            priceSpan.textContent = `$${(unitPrice * currentQty).toFixed(2)}`;
          }
        } else {
          orderItem.remove();
        }
        actualizarTotalesOrden();
      } else if (action === 'delete') {
        orderItem.style.opacity = '0';
        orderItem.style.transform = 'translateX(10px)';
        orderItem.style.transition = 'all 0.2s ease';
        setTimeout(() => {
          orderItem.remove();
          actualizarTotalesOrden();
        }, 180);
      }
    });
  }

  // Controles de cantidad interactivos en las tarjetas del catálogo ([-] [ 0 ] [+])
  document.addEventListener('click', (e) => {
    const qtyBtn = e.target.closest('.card-qty-btn');
    if (!qtyBtn) return;
    e.preventDefault();

    if (!activeOrderList) return;

    const card = qtyBtn.closest('.order-product-card');
    const control = qtyBtn.closest('.card-quantity-control');
    const id = (control?.dataset.id || control?.dataset.codigo || card?.dataset.id || card?.dataset.codigo || '').trim();
    const nombre = (card?.dataset.nombre || card?.dataset.name || card?.querySelector('.order-product-name')?.textContent || 'Producto').trim();
    const rawPrecio = card?.dataset.precio || card?.dataset.price || card?.querySelector('.order-product-price')?.textContent || '0.00';
    const unitPrice = parseFloat(String(rawPrecio).replace(/[^0-9.]/g, '')) || 0;

    const isPlus = qtyBtn.classList.contains('card-qty-plus') || qtyBtn.textContent.trim() === '+';
    const isMinus = qtyBtn.classList.contains('card-qty-minus') || qtyBtn.textContent.trim() === '-';

    // Buscar si el producto ya existe en activeOrderList
    const existingItem = Array.from(activeOrderList.querySelectorAll('.active-order-item')).find(item => {
      if (id && item.dataset.id && item.dataset.id === id) return true;
      const itemTitle = item.querySelector('.active-item-title')?.textContent.trim();
      return itemTitle && itemTitle.toLowerCase() === nombre.toLowerCase();
    });

    if (isPlus) {
      if (existingItem) {
        const itemQtySpan = existingItem.querySelector('.qty-circle-value');
        let currentQty = parseInt(itemQtySpan ? itemQtySpan.textContent : '1', 10) || 1;
        currentQty++;
        if (itemQtySpan) itemQtySpan.textContent = currentQty;

        const baseUnit = parseFloat(existingItem.dataset.unitPrice) || unitPrice;
        const priceSpan = existingItem.querySelector('.active-item-price');
        if (priceSpan) {
          priceSpan.textContent = `$${(baseUnit * currentQty).toFixed(2)}`;
        }
      } else {
        const newItem = document.createElement('div');
        newItem.className = 'active-order-item';
        newItem.dataset.id = id;
        newItem.dataset.unitPrice = unitPrice;
        newItem.innerHTML = `
          <div class="active-item-info">
            <h4 class="active-item-title">${nombre}</h4>
            <span class="active-item-price">$${unitPrice.toFixed(2)}</span>
          </div>
          <div class="active-item-controls">
            <button class="qty-circle-btn" data-action="decrease" aria-label="Disminuir">-</button>
            <span class="qty-circle-value">1</span>
            <button class="qty-circle-btn" data-action="increase" aria-label="Aumentar">+</button>
            <button class="btn-remove-item" data-action="delete" aria-label="Eliminar producto">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </div>
        `;
        activeOrderList.appendChild(newItem);
      }
      actualizarTotalesOrden();
    } else if (isMinus) {
      if (existingItem) {
        const itemQtySpan = existingItem.querySelector('.qty-circle-value');
        let currentQty = parseInt(itemQtySpan ? itemQtySpan.textContent : '1', 10) || 1;
        if (currentQty > 1) {
          currentQty--;
          if (itemQtySpan) itemQtySpan.textContent = currentQty;
          const baseUnit = parseFloat(existingItem.dataset.unitPrice) || unitPrice;
          const priceSpan = existingItem.querySelector('.active-item-price');
          if (priceSpan) {
            priceSpan.textContent = `$${(baseUnit * currentQty).toFixed(2)}`;
          }
        } else {
          existingItem.remove();
        }
        actualizarTotalesOrden();
      }
    }
  });

  // Capturar el clic en el botón '+' de los productos del catálogo (compatibilidad botón único)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-add-item');
    if (!btn) return;
    e.preventDefault();

    if (!activeOrderList) return;

    const card = btn.closest('.order-product-card');
    const id = (btn.dataset.id || btn.dataset.codigo || card?.dataset.id || card?.dataset.codigo || '').trim();
    const nombre = (btn.dataset.nombre || card?.dataset.nombre || card?.dataset.name || card?.querySelector('.order-product-name')?.textContent || 'Producto').trim();
    const rawPrecio = btn.dataset.precio || card?.dataset.precio || card?.dataset.price || card?.querySelector('.order-product-price')?.textContent || '0.00';
    const unitPrice = parseFloat(String(rawPrecio).replace(/[^0-9.]/g, '')) || 0;

    // Verificar si el producto ya existe en la lista activa (por id o nombre)
    const existingItem = Array.from(activeOrderList.querySelectorAll('.active-order-item')).find(item => {
      if (id && item.dataset.id && item.dataset.id === id) return true;
      const itemTitle = item.querySelector('.active-item-title')?.textContent.trim();
      return itemTitle && itemTitle.toLowerCase() === nombre.toLowerCase();
    });

    if (existingItem) {
      const qtySpan = existingItem.querySelector('.qty-circle-value');
      const priceSpan = existingItem.querySelector('.active-item-price');
      
      let currentQty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      currentQty++;
      if (qtySpan) qtySpan.textContent = currentQty;

      const baseUnit = parseFloat(existingItem.dataset.unitPrice) || unitPrice;
      if (priceSpan) {
        priceSpan.textContent = `$${(baseUnit * currentQty).toFixed(2)}`;
      }

      existingItem.style.transition = 'background-color 0.25s ease';
      existingItem.style.backgroundColor = 'rgba(193, 122, 93, 0.15)';
      setTimeout(() => {
        existingItem.style.backgroundColor = 'transparent';
      }, 300);

    } else {
      const newItem = document.createElement('div');
      newItem.className = 'active-order-item';
      newItem.dataset.id = id;
      newItem.dataset.unitPrice = unitPrice;
      newItem.innerHTML = `
        <div class="active-item-info">
          <h4 class="active-item-title">${nombre}</h4>
          <span class="active-item-price">$${unitPrice.toFixed(2)}</span>
        </div>
        <div class="active-item-controls">
          <button class="qty-circle-btn" data-action="decrease" aria-label="Disminuir">-</button>
          <span class="qty-circle-value">1</span>
          <button class="qty-circle-btn" data-action="increase" aria-label="Aumentar">+</button>
          <button class="btn-remove-item" data-action="delete" aria-label="Eliminar producto">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
        </div>
      `;
      activeOrderList.appendChild(newItem);
    }

    actualizarTotalesOrden();
  });

  // Pre-cargar orden previa guardada en localStorage para la mesa activa en gestion_orden.php
  if (activeOrderList) {
    const ordenGuardadaMesa = localStorage.getItem('orden_mesa_' + mesaActivaActual);
    if (ordenGuardadaMesa) {
      try {
        const productosPrevios = JSON.parse(ordenGuardadaMesa);
        if (Array.isArray(productosPrevios) && productosPrevios.length > 0) {
          activeOrderList.innerHTML = '';
          productosPrevios.forEach(prod => {
            const id = prod.id || '';
            const nombre = prod.name || prod.nombre || 'Producto';
            const unitPrice = parseFloat(prod.price ?? prod.unitPrice) || 0;
            const qty = parseInt(prod.qty, 10) || 1;

            const newItem = document.createElement('div');
            newItem.className = 'active-order-item';
            newItem.dataset.id = id;
            newItem.dataset.unitPrice = unitPrice;
            newItem.innerHTML = `
              <div class="active-item-info">
                <h4 class="active-item-title">${nombre}</h4>
                <span class="active-item-price">$${(unitPrice * qty).toFixed(2)}</span>
              </div>
              <div class="active-item-controls">
                <button class="qty-circle-btn" data-action="decrease" aria-label="Disminuir">-</button>
                <span class="qty-circle-value">${qty}</span>
                <button class="qty-circle-btn" data-action="increase" aria-label="Aumentar">+</button>
                <button class="btn-remove-item" data-action="delete" aria-label="Eliminar producto">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  </svg>
                </button>
              </div>
            `;
            activeOrderList.appendChild(newItem);
          });
          actualizarTotalesOrden();
        }
      } catch (e) {
        console.error('Error al precargar orden en gestion_orden:', e);
      }
    }
    // Sincronizar totales y contadores visuales del catálogo al cargar la vista
    actualizarTotalesOrden();
  }

  /**
   * ==========================================================================
   * PANTALLA: CIERRE DE CUENTA (cierre_cuenta.php)
   * ==========================================================================
   */
  const btnImprimirTicket = document.getElementById('btnImprimirTicket') || document.getElementById('btnGenerarPDF');
  const ticketContainer = document.getElementById('ticketContainer');
  const btnProcesarPago = document.getElementById('btnProcesarPago');
  const inputMontoRecibido = document.getElementById('inputMontoRecibido');
  const cashChangeValue = document.getElementById('cashChangeValue');

  // Sincronizar número de mesa activa en cierre_cuenta.php
  const mesaCierre = localStorage.getItem('mesaActiva') || '1';
  const cierreTitle = document.querySelector('.cierre-main-title');
  if (cierreTitle && (btnProcesarPago || ticketContainer)) {
    cierreTitle.textContent = `Cierre de Cuenta – Mesa ${mesaCierre}`;
  }

  // Alternar selección de método de pago (Efectivo / Tarjeta)
  const cardMetodoEfectivo = document.getElementById('metodoEfectivoCard');
  const cardMetodoTarjeta = document.getElementById('metodoTarjetaCard');
  const cashDetailCard = document.querySelector('.cash-detail-card');

  if (cardMetodoEfectivo && cardMetodoTarjeta) {
    function seleccionarMetodo(metodo) {
      if (metodo === 'efectivo') {
        cardMetodoEfectivo.classList.add('active');
        cardMetodoEfectivo.classList.remove('payment-method-card-inactive');
        cardMetodoTarjeta.classList.remove('active');
        cardMetodoTarjeta.classList.add('payment-method-card-inactive');
        if (cashDetailCard) {
          cashDetailCard.style.display = '';
        }
      } else {
        cardMetodoTarjeta.classList.add('active');
        cardMetodoTarjeta.classList.remove('payment-method-card-inactive');
        cardMetodoEfectivo.classList.remove('active');
        cardMetodoEfectivo.classList.add('payment-method-card-inactive');
        if (cashDetailCard) {
          cashDetailCard.style.display = 'none';
        }
      }
    }

    cardMetodoEfectivo.addEventListener('click', () => seleccionarMetodo('efectivo'));
    cardMetodoTarjeta.addEventListener('click', () => seleccionarMetodo('tarjeta'));
  }

  // 1. Botón "Imprimir": exporta el comprobante de venta a PDF mediante html2pdf.js
  if (btnImprimirTicket && ticketContainer) {
    btnImprimirTicket.addEventListener('click', () => {
      const opciones = {
        margin:       [10, 10, 10, 10],
        filename:     `Ticket_NIXGARU_Mesa_${mesaCierre}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      const textoOriginal = btnImprimirTicket.innerHTML;
      btnImprimirTicket.disabled = true;
      btnImprimirTicket.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;">
          <line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line>
          <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
          <line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line>
          <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
        </svg>
        <span>Generando PDF...</span>
      `;

      if (typeof html2pdf !== 'undefined') {
        html2pdf()
          .set(opciones)
          .from(ticketContainer)
          .save()
          .then(() => {
            btnImprimirTicket.innerHTML = `
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <span>¡Ticket PDF generado!</span>
            `;
            setTimeout(() => {
              btnImprimirTicket.disabled = false;
              btnImprimirTicket.innerHTML = textoOriginal;
            }, 1800);
          })
          .catch((err) => {
            console.error('Error al generar PDF del ticket:', err);
            btnImprimirTicket.disabled = false;
            btnImprimirTicket.innerHTML = textoOriginal;
          });
      } else {
        window.print();
        btnImprimirTicket.disabled = false;
        btnImprimirTicket.innerHTML = textoOriginal;
      }
    });
  }

  // 2. Botón "Procesar Pago & Liberar Mesa"
  if (btnProcesarPago) {
    btnProcesarPago.addEventListener('click', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = btnProcesarPago.dataset.mesa || urlParams.get('mesa_id') || urlParams.get('mesa') || localStorage.getItem('mesaActiva') || '1';
      const inputMonto = document.getElementById('inputMontoRecibido');
      const esTarjeta = cardMetodoTarjeta && cardMetodoTarjeta.classList.contains('active');
      const montoRecibido = inputMonto ? parseFloat(inputMonto.value) || 0 : 0;
      const totalOperacion = parseFloat(btnProcesarPago.dataset.total) || 0;

      if (!esTarjeta && montoRecibido < totalOperacion) {
        alert('El monto recibido en efectivo no puede ser menor al total de la operación ($' + totalOperacion.toFixed(2) + ').');
        return;
      }

      const checkMesaDesocupada = document.getElementById('checkMesaDesocupada');
      if (checkMesaDesocupada && !checkMesaDesocupada.checked) {
        alert('Por favor confirma que la mesa ha desocupado la estación antes del cierre de cuenta.');
        return;
      }

      const textoOriginal = btnProcesarPago.innerHTML;
      btnProcesarPago.innerHTML = `<span>Procesando pago y liberando mesa...</span>`;
      btnProcesarPago.disabled = true;

      // Llamada AJAX/Fetch al backend para actualizar MySQL (marcar pagado y liberar mesa)
      fetch('../../controllers/ServicioController.php?action=cerrar_cuenta', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          id_mesa: (mesaActiva && !isNaN(parseInt(mesaActiva, 10)) && !mesaActiva.toString().startsWith('M')) ? parseInt(mesaActiva, 10) : mesaActiva,
          id_pedido: btnProcesarPago.dataset.idpedido ? parseInt(btnProcesarPago.dataset.idpedido, 10) : null,
          monto_recibido: esTarjeta ? totalOperacion : (montoRecibido > 0 ? montoRecibido : totalOperacion),
          id_cajero: 1
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Limpiar datos locales de esta mesa
          localStorage.removeItem('orden_mesa_' + mesaActiva);
          localStorage.setItem('estado_mesa_' + mesaActiva, 'disponible');

          alert(`¡Pago procesado con éxito para la Mesa ${mesaActiva}!\nLa orden ha sido registrada como 'Pagada' y la mesa ahora está 'Disponible'.`);
          window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
        } else {
          alert('Error al procesar el pago: ' + (data.error || 'Ocurrió un error inesperado'));
          btnProcesarPago.innerHTML = textoOriginal;
          btnProcesarPago.disabled = false;
        }
      })
      .catch(err => {
        console.error('Error al contactar api/cerrar_cuenta.php:', err);
        // Fallback local en caso de desconexión
        localStorage.removeItem('orden_mesa_' + mesaActiva);
        localStorage.setItem('estado_mesa_' + mesaActiva, 'disponible');
        alert(`¡Pago procesado para la Mesa ${mesaActiva}!\nLa mesa ha sido liberada.`);
        window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
      });
    });
  }

  // Cálculo interactivo del cambio según el monto recibido
  if (inputMontoRecibido && cashChangeValue) {
    const total = parseFloat(inputMontoRecibido.dataset.total) || 0;
    inputMontoRecibido.addEventListener('input', () => {
      const recibido = parseFloat(inputMontoRecibido.value) || 0;
      const cambio = Math.max(0, recibido - total);
      cashChangeValue.textContent = cambio.toFixed(2);
    });
  }

  // Conexión del botón "Cerrar Cuenta" en mesas.php hacia cierre_cuenta.php
  const btnCerrarCuentaMesas = document.getElementById('btnCerrarCuenta');
  if (btnCerrarCuentaMesas) {
    btnCerrarCuentaMesas.addEventListener('click', (e) => {
      if (btnCerrarCuentaMesas.style.pointerEvents === 'none' || btnCerrarCuentaMesas.getAttribute('aria-disabled') === 'true' || !btnCerrarCuentaMesas.dataset.mesa) {
        e.preventDefault();
        return;
      }
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = btnCerrarCuentaMesas.dataset.mesa || urlParams.get('mesa_id') || urlParams.get('mesa');
      if (!mesaActiva) {
        e.preventDefault();
        return;
      }
      localStorage.setItem('mesaActiva', mesaActiva);
      window.location.href = `cierre_cuenta.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
    });
  }

  // Conexión de botones de "Cerrar Sesión" hacia el Login (index.php)
  const botonesCerrarSesion = document.querySelectorAll('#btnCerrarSesion, .btn-logout');
  botonesCerrarSesion.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.clear();
      window.location.href = '../../index.php';
    });
  });

  /**
   * ==========================================================================
   * PANTALLA: ADMINISTRACIÓN DE MESAS Y MODAL (admin_mesas.php)
   * ==========================================================================
   */
  const btnAbrirModalMesa = document.getElementById('btnAbrirModalMesa');
  const modalNuevaMesa = document.getElementById('modalNuevaMesa');
  const btnCancelarModal = document.getElementById('btnCancelarModal');
  const formNuevaMesa = document.getElementById('formNuevaMesa');
  const inputNumeroMesa = document.getElementById('inputNumeroMesa');
  const selectEstadoMesa = document.getElementById('selectEstadoMesa');
  const tablaMesasBody = document.getElementById('tablaMesasBody');
  const tableCountBadge = document.querySelector('.table-count-badge');

  // Función para abrir el modal (añade la clase 'show' y asegura display flex)
  function abrirModalNuevaMesa() {
    if (!modalNuevaMesa) return;
    modalNuevaMesa.classList.add('show');
    modalNuevaMesa.style.display = 'flex';
    modalNuevaMesa.setAttribute('aria-hidden', 'false');
    if (inputNumeroMesa) {
      setTimeout(() => inputNumeroMesa.focus(), 50);
    }
  }

  // Función para cerrar el modal (remueve 'show' y oculta el display)
  function cerrarModalNuevaMesa() {
    if (!modalNuevaMesa) return;
    modalNuevaMesa.classList.remove('show');
    modalNuevaMesa.style.display = 'none';
    modalNuevaMesa.setAttribute('aria-hidden', 'true');
    if (formNuevaMesa) {
      formNuevaMesa.reset();
    }
  }

  // 1. Abrir el modal al hacer clic en "+ Agregar nueva mesa"
  if (btnAbrirModalMesa) {
    btnAbrirModalMesa.addEventListener('click', (e) => {
      e.preventDefault();
      abrirModalNuevaMesa();
    });
  }

  // 2. Cerrar el modal al hacer clic en "Cancelar"
  if (btnCancelarModal) {
    btnCancelarModal.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarModalNuevaMesa();
    });
  }

  // 3. Cerrar el modal al hacer clic fuera de la tarjeta blanca (fondo oscuro del modal)
  if (modalNuevaMesa) {
    modalNuevaMesa.addEventListener('click', (e) => {
      if (e.target === modalNuevaMesa) {
        cerrarModalNuevaMesa();
      }
    });
  }

  // Cerrar el modal con la tecla Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalNuevaMesa && (modalNuevaMesa.classList.contains('show') || modalNuevaMesa.style.display === 'flex')) {
      cerrarModalNuevaMesa();
    }
  });

  // Procesar registro de nueva mesa al hacer clic en "Guardar" / submit
  if (formNuevaMesa) {
    formNuevaMesa.addEventListener('submit', (e) => {
      e.preventDefault();

      const nombreMesa = inputNumeroMesa?.value.trim() || 'Mesa';
      const estadoMesa = selectEstadoMesa?.value || 'LIBRE';

      if (tablaMesasBody) {
        const filasActuales = tablaMesasBody.querySelectorAll('tr').length;
        const nuevoId = filasActuales + 1;
        const idFormateado = nuevoId < 10 ? `0${nuevoId}` : `${nuevoId}`;

        let pillClass = 'status-libre';
        if (estadoMesa === 'OCUPADA') {
          pillClass = 'status-ocupada';
        } else if (estadoMesa === 'RESERVADA') {
          pillClass = 'status-reservada';
        }

        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = `
          <td class="cell-id">${idFormateado}</td>
          <td class="cell-name">${nombreMesa}</td>
          <td class="cell-estado">
            <span class="admin-status-pill ${pillClass}">${estadoMesa}</span>
          </td>
          <td class="cell-acciones">
            <button class="btn-action-edit" title="Editar mesa">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="btn-action-delete" title="Eliminar mesa">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </td>
        `;

        tablaMesasBody.appendChild(nuevaFila);

        // Actualizar el contador de mesas mostradas
        if (tableCountBadge) {
          tableCountBadge.textContent = `Total: ${nuevoId} mostradas`;
        }
      }

      // Cerrar y resetear el modal
      cerrarModalNuevaMesa();
    });
  }

  // Delegación de eventos para los botones de acción en la tabla (Eliminar)
  if (tablaMesasBody) {
    tablaMesasBody.addEventListener('click', (e) => {
      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        const fila = btnDelete.closest('tr');
        if (fila) {
          fila.style.opacity = '0';
          fila.style.transform = 'translateX(10px)';
          fila.style.transition = 'all 0.2s ease';
          setTimeout(() => {
            fila.remove();
            const totalFilas = tablaMesasBody.querySelectorAll('tr').length;
            if (tableCountBadge) {
              tableCountBadge.textContent = `Total: ${totalFilas} mostradas`;
            }
          }, 200);
        }
      }
    });
  }

  // Interacción para el filtro de fechas en el Dashboard
  const btnDateFilter = document.getElementById('btnDateFilter');
  if (btnDateFilter) {
    btnDateFilter.addEventListener('click', () => {
      /* Preparado para conectar con librería de calendario o modal de fechas */
      console.log('Filtro de fecha clickeado: 01/09/2026 - 12/09/2026');
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: CONTROL DE INVENTARIOS Y AJUSTES (admin_inventario.php)
   * ==========================================================================
   */
  const inputBuscarIngrediente = document.getElementById('inputBuscarIngrediente');
  const tablaInventarioBody = document.getElementById('tablaInventarioBody');
  const noResultadosInventario = document.getElementById('noResultadosInventario');

  // Filtrado de búsqueda de ingredientes en tiempo real
  if (inputBuscarIngrediente && tablaInventarioBody) {
    inputBuscarIngrediente.addEventListener('keyup', () => {
      const filtro = inputBuscarIngrediente.value.trim().toLowerCase();
      const filas = tablaInventarioBody.querySelectorAll('tr');
      let coincidencias = 0;

      filas.forEach((fila) => {
        const elementoNombre = fila.querySelector('.ingredient-name');
        const nombreIngrediente = elementoNombre ? elementoNombre.textContent.trim().toLowerCase() : '';

        if (nombreIngrediente.includes(filtro)) {
          fila.style.display = '';
          coincidencias++;
        } else {
          fila.style.display = 'none';
        }
      });

      // Mensaje de retroalimentación cuando no hay resultados
      if (noResultadosInventario) {
        noResultadosInventario.style.display = (coincidencias === 0) ? 'block' : 'none';
      }
    });
  }

  // Interacción interactiva para los botones "Guardar" de ajuste manual
  if (tablaInventarioBody) {
    tablaInventarioBody.addEventListener('click', (e) => {
      const btnGuardar = e.target.closest('.btn-adjust-save');
      if (btnGuardar) {
        const fila = btnGuardar.closest('tr');
        const inputAjuste = fila ? fila.querySelector('.input-adjust') : null;
        const valorAjuste = inputAjuste ? parseFloat(inputAjuste.value) : 0;

        btnGuardar.textContent = '¡Listo!';
        btnGuardar.style.backgroundColor = 'var(--color-secondary)';
        setTimeout(() => {
          btnGuardar.textContent = 'Guardar';
          btnGuardar.style.backgroundColor = '';
          if (inputAjuste) {
            inputAjuste.value = '0.00';
          }
        }, 800);
      }
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: GESTIÓN DE CUENTAS DE PERSONAL Y MODAL (admin_personal.php)
   * ==========================================================================
   */
  const btnAbrirModalEmpleado = document.getElementById('btnAbrirModalEmpleado');
  const modalNuevoEmpleado = document.getElementById('modalNuevoEmpleado');
  const btnCancelarModalEmpleado = document.getElementById('btnCancelarModalEmpleado');
  const formNuevoEmpleado = document.getElementById('formNuevoEmpleado');
  const inputNombreEmpleado = document.getElementById('inputNombreEmpleado');
  const inputUsuarioEmpleado = document.getElementById('inputUsuarioEmpleado');
  const inputPasswordEmpleado = document.getElementById('inputPasswordEmpleado');
  const selectRolEmpleado = document.getElementById('selectRolEmpleado');
  const tablaPersonalBody = document.getElementById('tablaPersonalBody');
  const personalCountBadge = document.getElementById('personalCountBadge');

  // Función para abrir el modal de nuevo empleado
  function abrirModalNuevoEmpleado() {
    if (!modalNuevoEmpleado) return;
    modalNuevoEmpleado.classList.add('show');
    modalNuevoEmpleado.style.display = 'flex';
    modalNuevoEmpleado.setAttribute('aria-hidden', 'false');
    if (inputNombreEmpleado) {
      setTimeout(() => inputNombreEmpleado.focus(), 50);
    }
  }

  // Función para cerrar el modal de nuevo empleado
  function cerrarModalNuevoEmpleado() {
    if (!modalNuevoEmpleado) return;
    modalNuevoEmpleado.classList.remove('show');
    modalNuevoEmpleado.style.display = 'none';
    modalNuevoEmpleado.setAttribute('aria-hidden', 'true');
    if (formNuevoEmpleado) {
      formNuevoEmpleado.reset();
    }
  }

  // 1. Abrir modal al hacer clic en "+ Agregar Empleado"
  if (btnAbrirModalEmpleado) {
    btnAbrirModalEmpleado.addEventListener('click', (e) => {
      e.preventDefault();
      abrirModalNuevoEmpleado();
    });
  }

  // 2. Cerrar modal al hacer clic en "Cancelar"
  if (btnCancelarModalEmpleado) {
    btnCancelarModalEmpleado.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarModalNuevoEmpleado();
    });
  }

  // 3. Cerrar modal al hacer clic en el fondo oscuro
  if (modalNuevoEmpleado) {
    modalNuevoEmpleado.addEventListener('click', (e) => {
      if (e.target === modalNuevoEmpleado) {
        cerrarModalNuevoEmpleado();
      }
    });
  }

  // Cerrar modal con la tecla Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalNuevoEmpleado && (modalNuevoEmpleado.classList.contains('show') || modalNuevoEmpleado.style.display === 'flex')) {
      cerrarModalNuevoEmpleado();
    }
  });

  // Procesar registro de nuevo empleado al hacer submit
  if (formNuevoEmpleado) {
    formNuevoEmpleado.addEventListener('submit', (e) => {
      e.preventDefault();

      const nombre = inputNombreEmpleado?.value.trim() || 'Empleado';
      const usuario = inputUsuarioEmpleado?.value.trim() || 'usuario';
      const rol = selectRolEmpleado?.value || 'Personal de servicio';

      if (tablaPersonalBody) {
        const totalFilas = tablaPersonalBody.querySelectorAll('tr').length + 1;
        const idFormateado = totalFilas < 10 ? `U0${totalFilas}` : `U${totalFilas}`;

        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = `
          <td class="cell-id">${idFormateado}</td>
          <td class="cell-name">${nombre}</td>
          <td class="cell-user">${usuario}</td>
          <td class="cell-role">
            <span class="role-pill">${rol}</span>
          </td>
          <td class="cell-acciones">
            <button class="btn-action-edit" title="Editar empleado">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </button>
            <button class="btn-action-delete" title="Eliminar empleado">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
              </svg>
            </button>
          </td>
        `;

        tablaPersonalBody.appendChild(nuevaFila);

        if (personalCountBadge) {
          personalCountBadge.textContent = `Total: ${totalFilas} mostrados`;
        }
      }

      cerrarModalNuevoEmpleado();
    });
  }

  // Delegación de eventos para eliminar empleado de la tabla
  if (tablaPersonalBody) {
    tablaPersonalBody.addEventListener('click', (e) => {
      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        const fila = btnDelete.closest('tr');
        if (fila) {
          fila.style.opacity = '0';
          fila.style.transform = 'translateX(10px)';
          fila.style.transition = 'all 0.2s ease';
          setTimeout(() => {
            fila.remove();
            const total = tablaPersonalBody.querySelectorAll('tr').length;
            if (personalCountBadge) {
              personalCountBadge.textContent = `Total: ${total} mostrados`;
            }
          }, 200);
        }
      }
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: GESTIÓN DE CATÁLOGO Y MENÚ (admin_catalogo.php)
   * ==========================================================================
   */
  // 1. Filtros de Categorías (Píldoras)
  const catalogFilterPills = document.querySelectorAll('.catalog-filter-pill');
  catalogFilterPills.forEach((pill) => {
    pill.addEventListener('click', () => {
      catalogFilterPills.forEach((p) => p.classList.remove('active'));
      pill.classList.add('active');

      const filterCat = (pill.dataset.category || 'todos').toLowerCase();
      const filas = document.querySelectorAll('#tablaCatalogoBody tr');
      let visibles = 0;

      filas.forEach(fila => {
        const filaCat = (fila.dataset.category || '').toLowerCase();
        if (filterCat === 'todos' || filaCat === filterCat) {
          fila.style.display = '';
          visibles++;
        } else {
          fila.style.display = 'none';
        }
      });

      if (catalogCountBadge) {
        catalogCountBadge.textContent = `Total: ${visibles} productos mostrados`;
      }
    });
  });

  // 2. Interactividad del Interruptor (Toggle Switch) de Disponibilidad y Acciones
  const tablaCatalogoBody = document.getElementById('tablaCatalogoBody');
  const catalogCountBadge = document.getElementById('catalogCountBadge');

  if (tablaCatalogoBody) {
    tablaCatalogoBody.addEventListener('click', (e) => {
      // Cambio de estado en el toggle switch
      const toggleWrapper = e.target.closest('.toggle-switch-wrapper');
      if (toggleWrapper) {
        e.preventDefault();
        const estaActivo = toggleWrapper.classList.toggle('active');
        const switchBtn = toggleWrapper.querySelector('.toggle-switch');
        const label = toggleWrapper.querySelector('.toggle-status-label');

        if (switchBtn) {
          switchBtn.setAttribute('aria-checked', estaActivo ? 'true' : 'false');
        }

        if (label) {
          label.textContent = estaActivo ? 'Activo' : 'Inactivo';
        }
      }

      // Eliminar fila de producto
      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        const fila = btnDelete.closest('tr');
        if (fila) {
          fila.style.opacity = '0';
          fila.style.transform = 'translateX(10px)';
          fila.style.transition = 'all 0.2s ease';
          setTimeout(() => {
            fila.remove();
            const total = tablaCatalogoBody.querySelectorAll('tr').length;
            if (catalogCountBadge) {
              catalogCountBadge.textContent = `Total: ${total} productos mostrados`;
            }
          }, 200);
        }
      }
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: INICIO DE SESIÓN / LOGIN (index.php)
   * ==========================================================================
   */
  const btnTogglePassword = document.getElementById('btnTogglePassword');
  const inputPassword = document.getElementById('inputPassword');
  const formLogin = document.getElementById('formLogin');

  // 1. Mostrar / Ocultar Contraseña (Toggle visibilidad)
  if (btnTogglePassword && inputPassword) {
    btnTogglePassword.addEventListener('click', () => {
      const esPassword = inputPassword.getAttribute('type') === 'password';
      inputPassword.setAttribute('type', esPassword ? 'text' : 'password');

      const iconEye = btnTogglePassword.querySelector('.icon-eye');
      const iconEyeOff = btnTogglePassword.querySelector('.icon-eye-off');

      if (iconEye && iconEyeOff) {
        iconEye.style.display = esPassword ? 'none' : 'block';
        iconEyeOff.style.display = esPassword ? 'block' : 'none';
      }
    });
  }

  // 2. Ingreso al Sistema mediante AuthController
  if (formLogin) {
    formLogin.addEventListener('submit', (e) => {
      e.preventDefault();

      const inputUsuario = document.getElementById('inputUsuario');
      const inputPassword = document.getElementById('inputPassword');
      const usuario = inputUsuario ? inputUsuario.value.trim() : '';
      const password = inputPassword ? inputPassword.value : '';

      if (!usuario || !password) {
        alert('Por favor ingrese usuario y contraseña.');
        return;
      }

      const btnSubmit = document.getElementById('btnLoginSubmit');
      const textoOriginal = btnSubmit ? btnSubmit.textContent : '';
      if (btnSubmit) {
        btnSubmit.textContent = 'Verificando...';
        btnSubmit.disabled = true;
      }

      fetch('controllers/AuthController.php?action=login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          usuario: usuario,
          password: password
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
        } else {
          alert(data.error || 'Credenciales incorrectas');
        }
      })
      .catch(err => {
        console.error('Error al iniciar sesión:', err);
        alert('Credenciales incorrectas');
      })
      .finally(() => {
        if (btnSubmit) {
          btnSubmit.textContent = textoOriginal;
          btnSubmit.disabled = false;
        }
      });
    });
  }

  /**
   * ==========================================================================
   * MÓDULO DE ADMINISTRACIÓN: CONTROL DE BARRA LATERAL (ESCRITORIO Y MÓVIL)
   * ==========================================================================
   */
  if (window.__NIXGARU_ADMIN_INITIALIZED__) {
    return;
  }
  window.__NIXGARU_ADMIN_INITIALIZED__ = true;

  const adminSidebar = document.querySelector('.admin-sidebar');
  const adminBackdrop = document.getElementById('adminSidebarBackdrop');

  // Función para determinar si nos encontramos en modo vista móvil
  const esVistaMovilAdmin = () => {
    const mHeader = document.getElementById('adminMobileHeader') || document.querySelector('.admin-mobile-header');
    if (mHeader && window.getComputedStyle(mHeader).display !== 'none') {
      return true;
    }
    return window.innerWidth <= 1024;
  };

  const abrirDrawerMovil = () => {
    const s = document.querySelector('.admin-sidebar') || adminSidebar;
    const b = document.getElementById('adminSidebarBackdrop') || adminBackdrop;
    if (s) {
      s.classList.add('mobile-open', 'open', 'active');
      s.setAttribute('aria-hidden', 'false');
    }
    document.body.classList.add('admin-mobile-open');
    if (b) {
      b.classList.add('active', 'open');
      b.setAttribute('aria-hidden', 'false');
    }
    document.body.style.overflow = 'hidden';
  };

  const cerrarDrawerMovil = () => {
    const s = document.querySelector('.admin-sidebar') || adminSidebar;
    const b = document.getElementById('adminSidebarBackdrop') || adminBackdrop;
    if (s) {
      s.classList.remove('mobile-open', 'open', 'active');
      s.setAttribute('aria-hidden', 'true');
    }
    document.body.classList.remove('admin-mobile-open');
    if (b) {
      b.classList.remove('active', 'open');
      b.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = '';
  };

  // Función global disponible para llamadas inline (onclick) y delegadas
  window.toggleAdminSidebar = (e) => {
    if (e) {
      if (typeof e.preventDefault === 'function') e.preventDefault();
      if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const s = document.querySelector('.admin-sidebar') || adminSidebar;
    if (!s) return;

    if (esVistaMovilAdmin()) {
      // En vista móvil: despliega o cierra la barra lateral tipo drawer desde la izquierda
      const isOpen = s.classList.contains('mobile-open') || s.classList.contains('open') || document.body.classList.contains('admin-mobile-open');
      if (isOpen) {
        cerrarDrawerMovil();
      } else {
        abrirDrawerMovil();
      }
    } else {
      // En escritorio: colapsa a vista reducida de iconos (82px) o expande (270px)
      const isCollapsed = s.classList.toggle('collapsed');
      if (isCollapsed) {
        document.documentElement.classList.add('admin-sidebar-is-collapsed');
      } else {
        document.documentElement.classList.remove('admin-sidebar-is-collapsed');
      }
      localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
      document.cookie = "admin_sidebar_collapsed=" + (isCollapsed ? "true" : "false") + "; path=/; max-age=31536000; SameSite=Lax";
    }
  };

  /**
   * ==========================================================================
   * MÓDULO DE ADMINISTRACIÓN: CONTROL DE PERFIL Y CERRAR SESIÓN
   * ==========================================================================
   */
  const cerrarTodosLosDropdownsAdmin = () => {
    document.querySelectorAll('.admin-user-dropdown').forEach(dd => {
      dd.classList.remove('open', 'show');
      dd.setAttribute('aria-hidden', 'true');
    });
    document.querySelectorAll('.admin-header-profile, .admin-sidebar-profile').forEach(p => {
      p.setAttribute('aria-expanded', 'false');
    });
    document.querySelectorAll('.admin-header-profile-wrapper, .admin-sidebar-profile-wrapper').forEach(w => {
      w.classList.remove('open');
    });
  };

  const abrirDropdownAdmin = (wrapper, trigger, e) => {
    if (e) {
      if (typeof e.preventDefault === 'function') e.preventDefault();
      if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const dropdown = wrapper.querySelector('.admin-user-dropdown');
    if (!dropdown) return;
    cerrarTodosLosDropdownsAdmin();
    dropdown.classList.add('open', 'show');
    dropdown.setAttribute('aria-hidden', 'false');
    trigger.setAttribute('aria-expanded', 'true');
    wrapper.classList.add('open');
  };

  const toggleDropdownAdmin = (wrapper, trigger, e) => {
    if (e) {
      if (typeof e.preventDefault === 'function') e.preventDefault();
      if (typeof e.stopPropagation === 'function') e.stopPropagation();
    }
    const dropdown = wrapper.querySelector('.admin-user-dropdown');
    if (!dropdown) return;
    const estaAbierto = dropdown.classList.contains('open') || dropdown.classList.contains('show');
    cerrarTodosLosDropdownsAdmin();
    if (!estaAbierto) {
      dropdown.classList.add('open', 'show');
      dropdown.setAttribute('aria-hidden', 'false');
      trigger.setAttribute('aria-expanded', 'true');
      wrapper.classList.add('open');
    }
  };

  if (adminSidebar) {
    // Restaurar estado guardado en localStorage SÓLO para escritorio (> 1024px)
    if (!esVistaMovilAdmin()) {
      const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
      adminSidebar.classList.toggle('collapsed', isCollapsed);
      document.documentElement.classList.toggle('admin-sidebar-is-collapsed', isCollapsed);
      document.cookie = "admin_sidebar_collapsed=" + (isCollapsed ? "true" : "false") + "; path=/; max-age=31536000; SameSite=Lax";
    }

    // Reactivar transiciones después del primer ciclo de renderizado para permitir animaciones suaves en toggles futuros
    requestAnimationFrame(() => {
      setTimeout(() => {
        adminSidebar.classList.remove('no-transition');
      }, 50);
    });

    // Delegación centralizada de clics en el documento
    document.addEventListener('click', (e) => {
      // 1. Clic en botón "Cerrar Sesión"
      const btnLogout = e.target.closest('.btn-admin-logout, .btn-logout, #btnCerrarSesion');
      if (btnLogout) {
        e.preventDefault();
        sessionStorage.clear();
        try {
          fetch('/NIXGARU/controllers/AuthController.php?action=logout').finally(() => {
            window.location.href = '/NIXGARU/index.php';
          });
        } catch (err) {
          window.location.href = '/NIXGARU/index.php';
        }
        return;
      }

      // 2. Clic en el perfil del header móvil
      const headerProfile = e.target.closest('.admin-header-profile');
      if (headerProfile) {
        const wrapper = headerProfile.closest('.admin-header-profile-wrapper');
        if (wrapper) {
          toggleDropdownAdmin(wrapper, headerProfile, e);
          return;
        }
      }

      // 3. Clic en el perfil de la barra lateral (desktop o drawer móvil)
      const sidebarProfile = e.target.closest('.admin-sidebar-profile');
      if (sidebarProfile) {
        const wrapper = sidebarProfile.closest('.admin-sidebar-profile-wrapper');
        if (wrapper) {
          const s = document.querySelector('.admin-sidebar') || adminSidebar;
          // En escritorio (> 1024px): con la barra minimizada, expandirla y mostrar opciones de sesión
          if (!esVistaMovilAdmin() && s) {
            const estaColapsada = s.classList.contains('collapsed') || document.documentElement.classList.contains('admin-sidebar-is-collapsed');
            if (estaColapsada) {
              s.classList.remove('collapsed');
              document.documentElement.classList.remove('admin-sidebar-is-collapsed');
              localStorage.setItem('admin_sidebar_collapsed', 'false');
              document.cookie = "admin_sidebar_collapsed=false; path=/; max-age=31536000; SameSite=Lax";
              abrirDropdownAdmin(wrapper, sidebarProfile, e);
              return;
            }
          }

          // Con la barra expandida: solo mostrar/alternar opciones de sesión sin minimizar la barra
          toggleDropdownAdmin(wrapper, sidebarProfile, e);
          return;
        }
      }

      // 4. Si se hace clic dentro de la burbuja dropdown, no cerrarla (salvo logout)
      if (e.target.closest('.admin-user-dropdown')) {
        return;
      }

      // 5. Clic fuera de las burbujas -> cerrarlas
      cerrarTodosLosDropdownsAdmin();

      // 6. Clic en el telón de fondo oscuro (Backdrop) -> cerrar menú móvil
      if (e.target.closest('#adminSidebarBackdrop, .admin-sidebar-backdrop')) {
        cerrarDrawerMovil();
        return;
      }

      // 7. Clic en botón de la taza o en contenedor de la marca
      const btnTaza = e.target.closest('#btnToggleAdminMobile, #btnToggleAdminSidebar, .btn-toggle-admin-mobile, .btn-toggle-admin-sidebar, .admin-brand-icon');
      const brandCont = e.target.closest('#adminMobileBrand, #adminBrand, .admin-mobile-header .admin-brand, .admin-sidebar .admin-brand');

      if (btnTaza || (brandCont && !e.target.closest('a') && !e.target.closest('input'))) {
        window.toggleAdminSidebar(e);
      }
    });

    // Cerrar con la tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        cerrarTodosLosDropdownsAdmin();
        if (esVistaMovilAdmin()) {
          cerrarDrawerMovil();
        }
      }
    });

    // Reaccionar al redimensionar la ventana
    window.addEventListener('resize', () => {
      cerrarTodosLosDropdownsAdmin();
      if (!esVistaMovilAdmin()) {
        cerrarDrawerMovil();
        const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
        adminSidebar.classList.toggle('collapsed', isCollapsed);
        document.documentElement.classList.toggle('admin-sidebar-is-collapsed', isCollapsed);
      } else {
        // En móvil no debe aplicarse el colapsado de iconos de escritorio
        adminSidebar.classList.remove('collapsed');
        document.documentElement.classList.remove('admin-sidebar-is-collapsed');
      }
    });
  }
});



/**
 * Función global para inyectar datos de usuario dinámicamente en el Header
 * @param {string} nombre - Nombre del usuario en sesión (ej. "Carlos Méndez")
 * @param {string} rol - Rol asignado al usuario (ej. "Mesero", "Administrador")
 */
function cargarUsuario(nombre, rol) {
  const aplicarDatos = () => {
    const nombreElement = document.getElementById('nombreUsuario');
    const rolElement = document.getElementById('rolUsuario');
    const avatarElement = document.getElementById('avatarUsuario');

    if (nombreElement) {
      nombreElement.textContent = nombre;
    }
    if (rolElement) {
      rolElement.textContent = rol;
    }
    if (avatarElement && nombre) {
      const iniciales = nombre
        .trim()
        .split(' ')
        .map(palabra => palabra[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
      avatarElement.textContent = iniciales || 'U';
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', aplicarDatos);
  } else {
    aplicarDatos();
  }
}

// Llamada de prueba inicial para verificar la inyección dinámica de datos
cargarUsuario("Carlos Méndez", "Mesero");


