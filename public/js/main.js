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
   * FILTRO DINÁMICO DE CATEGORÍAS Y BÚSQUEDA EN TIEMPO REAL (VISTA DEL CLIENTE)
   * ==========================================================================
   */
  const filtroBotones = document.querySelectorAll('.filtro-btn, .category-pill');
  const tarjetasProductos = document.querySelectorAll('.producto-card, .product-card');
  const btnToggleSearch = document.getElementById('btnToggleSearch');
  const headerSearchBox = document.getElementById('headerSearchBox');
  const inputBuscarCliente = document.getElementById('inputBuscarCliente');
  const btnClearSearch = document.getElementById('btnClearSearch');
  const noSearchResults = document.getElementById('noSearchResults');
  const noSearchTerm = document.getElementById('noSearchTerm');
  const btnResetSearch = document.getElementById('btnResetSearch');

  let terminoBusquedaActivo = '';
  let categoriaFiltroActiva = 'all';

  function normalizarTextoBusqueda(txt) {
    return String(txt || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  function aplicarFiltrosYBusquedaCliente() {
    const terminoNorm = normalizarTextoBusqueda(terminoBusquedaActivo);
    let totalVisibles = 0;

    tarjetasProductos.forEach((tarjeta) => {
      const catTarjeta = (tarjeta.dataset.category || '').toString().trim().toLowerCase();
      const coincideCategoria = (categoriaFiltroActiva === 'all' || categoriaFiltroActiva === 'todos' || catTarjeta === categoriaFiltroActiva);

      if (!coincideCategoria) {
        tarjeta.style.display = 'none';
        return;
      }

      if (!terminoNorm) {
        tarjeta.style.display = 'flex';
        totalVisibles++;
        return;
      }

      const nombre = tarjeta.dataset.name || tarjeta.querySelector('.product-name')?.textContent || '';
      const desc = tarjeta.dataset.desc || tarjeta.querySelector('.product-description')?.textContent || '';
      const ingredients = tarjeta.dataset.ingredients || '';
      const contenidoBusqueda = normalizarTextoBusqueda(`${nombre} ${desc} ${ingredients}`);

      if (contenidoBusqueda.includes(terminoNorm)) {
        tarjeta.style.display = 'flex';
        totalVisibles++;
      } else {
        tarjeta.style.display = 'none';
      }
    });

    // Control de mensaje de sin coincidencias
    if (noSearchResults) {
      if (totalVisibles === 0 && tarjetasProductos.length > 0) {
        if (noSearchTerm) {
          noSearchTerm.textContent = terminoBusquedaActivo.trim() || 'el criterio actual';
        }
        noSearchResults.style.display = 'block';
      } else {
        noSearchResults.style.display = 'none';
      }
    }

    // Visibilidad del botón de limpiar búsqueda (X)
    if (btnClearSearch) {
      btnClearSearch.classList.toggle('visible', terminoBusquedaActivo.trim().length > 0);
    }
  }

  // Eventos de selección de categoría
  if (filtroBotones.length > 0) {
    filtroBotones.forEach((boton) => {
      boton.addEventListener('click', () => {
        filtroBotones.forEach((b) => b.classList.remove('active'));
        boton.classList.add('active');

        categoriaFiltroActiva = (boton.dataset.filter || boton.dataset.category || 'all').toString().trim().toLowerCase();
        aplicarFiltrosYBusquedaCliente();
      });
    });
  }

  // Eventos y controles del buscador en el header
  if (btnToggleSearch && headerSearchBox && inputBuscarCliente) {
    function abrirBuscador() {
      headerSearchBox.classList.add('open');
      btnToggleSearch.classList.add('active');
      btnToggleSearch.setAttribute('aria-expanded', 'true');
      setTimeout(() => {
        inputBuscarCliente.focus();
        inputBuscarCliente.select();
      }, 100);
    }

    function cerrarBuscador() {
      headerSearchBox.classList.remove('open');
      btnToggleSearch.classList.remove('active');
      btnToggleSearch.setAttribute('aria-expanded', 'false');
    }

    function limpiarBuscador() {
      inputBuscarCliente.value = '';
      terminoBusquedaActivo = '';
      aplicarFiltrosYBusquedaCliente();
    }

    btnToggleSearch.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = headerSearchBox.classList.contains('open');
      if (isOpen) {
        cerrarBuscador();
      } else {
        abrirBuscador();
      }
    });

    inputBuscarCliente.addEventListener('input', (e) => {
      terminoBusquedaActivo = e.target.value;
      aplicarFiltrosYBusquedaCliente();

      // Al escribir el primer carácter, desplazar suavemente al catálogo si está más abajo
      if (terminoBusquedaActivo.trim().length === 1 && typeof desplazarseHaciaMenu === 'function') {
        desplazarseHaciaMenu();
      }
    });

    inputBuscarCliente.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        if (inputBuscarCliente.value) {
          limpiarBuscador();
        } else {
          cerrarBuscador();
        }
      } else if (e.key === 'Enter') {
        inputBuscarCliente.blur();
        if (typeof desplazarseHaciaMenu === 'function') {
          desplazarseHaciaMenu();
        }
      }
    });

    if (btnClearSearch) {
      btnClearSearch.addEventListener('click', (e) => {
        e.stopPropagation();
        limpiarBuscador();
        inputBuscarCliente.focus();
      });
    }

    if (btnResetSearch) {
      btnResetSearch.addEventListener('click', () => {
        limpiarBuscador();
        categoriaFiltroActiva = 'all';
        filtroBotones.forEach((b) => {
          const val = (b.dataset.filter || b.dataset.category || '').toLowerCase();
          b.classList.toggle('active', val === 'all' || val === 'todos');
        });
        aplicarFiltrosYBusquedaCliente();
      });
    }

    // Cerrar buscador si se hace clic fuera del header y está vacío
    document.addEventListener('click', (e) => {
      if (!headerSearchBox.contains(e.target) && !btnToggleSearch.contains(e.target)) {
        if (headerSearchBox.classList.contains('open') && !inputBuscarCliente.value.trim()) {
          cerrarBuscador();
        }
      }
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
  if (inputTelefonoCliente) {
    inputTelefonoCliente.addEventListener('input', () => {
      let soloDigitos = inputTelefonoCliente.value.replace(/\D/g, '');
      if (soloDigitos.startsWith('503') && soloDigitos.length > 8) {
        soloDigitos = soloDigitos.substring(3);
      }
      soloDigitos = soloDigitos.substring(0, 8);
      if (soloDigitos.length > 4) {
        inputTelefonoCliente.value = `${soloDigitos.substring(0, 4)}-${soloDigitos.substring(4)}`;
      } else {
        inputTelefonoCliente.value = soloDigitos;
      }
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
      let digitosTel = telefonoCliente.replace(/\D/g, '');
      if (digitosTel.startsWith('503') && digitosTel.length === 11) {
        digitosTel = digitosTel.substring(3);
      }
      if (digitosTel.length !== 8) {
        mostrarErrorCheckout('El número de teléfono debe tener exactamente 8 cifras según el formato de El Salvador (ej. 7000-0000).');
        inputTelefonoCliente?.focus();
        return;
      }
      if (!/^[267]/.test(digitosTel)) {
        mostrarErrorCheckout('El número de teléfono de El Salvador debe iniciar con 2 (fijo), 6 o 7 (móvil).');
        inputTelefonoCliente?.focus();
        return;
      }
      const telefonoNormalizado = `${digitosTel.substring(0, 4)}-${digitosTel.substring(4)}`;
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
        telefono_cliente: telefonoNormalizado,
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

      const htmlOriginalDescarga = btnDescargarTicketCliente.innerHTML;
      btnDescargarTicketCliente.disabled = true;
      btnDescargarTicketCliente.innerHTML = '<span>Generando PDF...</span>';

      if (typeof html2pdf !== 'undefined') {
        const anchoPx = Math.max(clientTicketContainer.scrollWidth, clientTicketContainer.offsetWidth, 380);
        const altoPx = Math.max(clientTicketContainer.scrollHeight, clientTicketContainer.offsetHeight);
        const margenMm = 10;
        const anchoPdfMm = 210;
        const anchoContenidoMm = anchoPdfMm - (margenMm * 2);
        const altoContenidoMm = Math.ceil((altoPx * anchoContenidoMm) / anchoPx);
        const altoPdfMm = Math.max(297, altoContenidoMm + (margenMm * 2) + 6);

        const opcionesPdf = {
          margin: [margenMm, margenMm, margenMm, margenMm],
          filename: nombreArchivo,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            letterRendering: true,
            backgroundColor: '#ffffff',
            scrollY: 0,
            windowHeight: altoPx + 200
          },
          jsPDF: { unit: 'mm', format: [anchoPdfMm, altoPdfMm], orientation: 'portrait' },
          pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };

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
   * UTILIDADES COMUNES DEL MÓDULO DE SERVICIO Y CAJERO
   * ==========================================================================
   */
  function escaparHtmlServicio(valor) {
    return String(valor ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function mapearEstadoMesaASlug(estadoRaw) {
    const limpio = String(estadoRaw || '').toLowerCase().trim();
    if (limpio.includes('ocupad')) return 'ocupada';
    if (limpio.includes('proces')) return 'proceso';
    if (limpio.includes('sin')) return 'sin-servicio';
    return 'disponible';
  }

  /**
   * ==========================================================================
   * PLANO DE MESAS: SINCRONIZACIÓN EN TIEMPO REAL CON MYSQL (mesas.php - RF06, RF08, RF09)
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

  // Actualiza el círculo indicador de estado en el panel lateral de la cuenta
  function actualizarEstadoCuentaLateral(estado) {
    const elEstado = document.getElementById('cuentaMesaEstado') || cuentaMesaEstado;
    if (!elEstado) return;
    const slug = mapearEstadoMesaASlug(estado);
    elEstado.className = 'cuenta-status-badge';
    elEstado.style.display = 'inline-flex';
    
    const etiquetas = {
      'disponible':   { texto: 'Disponible', clase: 'badge-disponible', dot: 'dot-disponible' },
      'ocupada':      { texto: 'Ocupada', clase: 'badge-ocupada', dot: 'dot-ocupada' },
      'proceso':      { texto: 'En proceso', clase: 'badge-proceso', dot: 'dot-proceso' },
      'sin-servicio': { texto: 'Sin servicio', clase: 'badge-sin-servicio', dot: 'dot-sin-servicio' }
    };

    const cfg = etiquetas[slug] || etiquetas['disponible'];
    elEstado.classList.add(cfg.clase);
    elEstado.setAttribute('title', cfg.texto);
    elEstado.innerHTML = `<span class="status-dot ${cfg.dot}"></span>`;
  }

  // Consulta asíncrona a MySQL (`obtener_cuenta_mesa`) para renderizar productos, propina, abono web y saldo real (RF06, RF09)
  async function cargarOrdenMesaEnPanelLateral(mesaId) {
    const listaProductosCuenta = document.getElementById('listaProductosCuenta');
    const cuentaSubtotal = document.getElementById('cuentaSubtotal');
    const cuentaPropina = document.getElementById('cuentaPropina');
    const cuentaTotal = document.getElementById('cuentaTotal');
    const filaCuentaAbonoWeb = document.getElementById('filaCuentaAbonoWeb');
    const cuentaAbonoWeb = document.getElementById('cuentaAbonoWeb');
    const filaCuentaSaldoPendiente = document.getElementById('filaCuentaSaldoPendiente');
    const cuentaSaldoPendiente = document.getElementById('cuentaSaldoPendiente');

    if (!listaProductosCuenta) return;

    try {
      const res = await fetch(`../../controllers/ServicioController.php?action=obtener_cuenta_mesa&id_mesa=${encodeURIComponent(mesaId)}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });
      const data = await res.json();

      if (!res.ok || !data || data.success !== true) {
        throw new Error((data && data.error) ? data.error : 'No se pudo consultar la cuenta de la mesa.');
      }

      const detalles = Array.isArray(data.detalles) ? data.detalles : [];
      const subtotal = Number(data.subtotal || 0);
      const propina = Number(data.propina || 0);
      const total = Number(data.total ?? data.total_pagar ?? 0);
      const montoAbonado = Number(data.monto_abonado || 0);
      const saldoPendiente = Number(data.saldo_pendiente ?? Math.max(0, total - montoAbonado));

      // Sincronizar estado y consumo de la tarjeta en la cuadrícula si existe
      if (data.mesa && data.mesa.id_mesa) {
        const cardMesa = Array.from(document.querySelectorAll('.table-card')).find(
          c => String(c.dataset.tableId) === String(data.mesa.id_mesa) || String(c.dataset.tableNumber) === String(data.mesa.numero_mesa)
        );
        if (cardMesa) {
          const consumoValEl = cardMesa.querySelector('.table-consumo-val') || cardMesa.querySelector('.table-data-row:first-child .data-value');
          const ordenStatusEl = cardMesa.querySelector('.table-orden-status');
          if (consumoValEl) {
            consumoValEl.textContent = `$${total.toFixed(2)}`;
          }
          if (ordenStatusEl) {
            ordenStatusEl.textContent = detalles.length > 0 ? 'Activa' : 'Sin orden';
          }
          const selMesa = cardMesa.querySelector('.table-status-select');
          if (selMesa && data.mesa.estado_mesa) {
            const slugBD = mapearEstadoMesaASlug(data.mesa.estado_mesa);
            selMesa.value = slugBD;
            selMesa.dataset.prevStatus = slugBD;
            selMesa.classList.remove('status-disponible', 'status-ocupada', 'status-proceso', 'status-sin-servicio');
            selMesa.classList.add(`status-${slugBD}`);
            if (cardMesa.classList.contains('active')) {
              actualizarEstadoCuentaLateral(slugBD);
            }
            actualizarResumenMesas();
          }
        }
      }

      if (detalles.length === 0) {
        listaProductosCuenta.innerHTML = `
          <div class="empty-cuenta-msg" style="text-align: center; color: #6C757D; font-size: 13.5px; padding: 36px 12px;">
            <p style="margin: 0 0 6px 0; font-weight: 600; color: #343A40;">Mesa disponible sin consumos activos</p>
            <span style="font-size: 12.5px; opacity: 0.85;">No hay pedidos pendientes de pago para esta mesa.</span>
          </div>
        `;
      } else {
        let html = '';
        detalles.forEach(item => {
          const qty = parseInt(item.cantidad ?? item.qty, 10) || 1;
          const nombreProd = escaparHtmlServicio(item.nombre_producto || item.name || 'Producto');
          const subLinea = Number(item.subtotal ?? ((Number(item.precio || 0)) * qty));
          html += `
            <div class="cuenta-item">
              <span class="item-qty-badge">${qty}</span>
              <span class="item-name">${nombreProd}</span>
              <span class="item-price">$${subLinea.toFixed(2)}</span>
            </div>
          `;
        });
        listaProductosCuenta.innerHTML = html;
      }

      if (cuentaSubtotal) cuentaSubtotal.textContent = `$${subtotal.toFixed(2)}`;
      if (cuentaPropina) cuentaPropina.textContent = `$${propina.toFixed(2)}`;
      if (cuentaTotal) cuentaTotal.textContent = `$${total.toFixed(2)}`;

      if (filaCuentaAbonoWeb && cuentaAbonoWeb) {
        cuentaAbonoWeb.textContent = `-$${montoAbonado.toFixed(2)}`;
        filaCuentaAbonoWeb.style.display = montoAbonado > 0 ? 'flex' : 'none';
      }
      if (filaCuentaSaldoPendiente && cuentaSaldoPendiente) {
        cuentaSaldoPendiente.textContent = `$${saldoPendiente.toFixed(2)}`;
        filaCuentaSaldoPendiente.style.display = montoAbonado > 0 ? 'flex' : 'none';
      }
    } catch (err) {
      console.error('Error al consultar la cuenta activa desde MySQL:', err);
    }
  }

  // Función encargada de actualizar la mesa seleccionada y sincronizar su panel lateral con MySQL
  window.seleccionarMesa = function(id, estado, idRealParam) {
    const mesaId = String(id);
    const mesaRealId = idRealParam ? String(idRealParam) : mesaId;

    sessionStorage.setItem('mesaActiva', mesaRealId);

    // Resaltar visualmente ÚNICAMENTE la tarjeta de la mesa activa en la cuadrícula
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

    // Actualizar el título del panel lateral derecho ("Mesa X")
    const elTitulo = document.getElementById('cuentaMesaTitulo');
    if (elTitulo) {
      const numLimpio = mesaId.replace(/^Mesa\s*/i, '').trim();
      elTitulo.textContent = `Mesa ${numLimpio}`;
    }

    actualizarEstadoCuentaLateral(estado);

    // Alternar visibilidad: ocultar estado vacío y mostrar contenedor de productos/totales
    const emptyState = document.getElementById('cuentaEmptyState');
    const detalleContainer = document.getElementById('cuentaDetalleContainer');
    if (emptyState) emptyState.style.display = 'none';
    if (detalleContainer) {
      detalleContainer.style.display = 'flex';
      detalleContainer.style.flexDirection = 'column';
    }

    // Habilitar y actualizar botones de acción del panel lateral
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

    // Sincronizar URL del navegador sin recargar la página
    if (window.history && window.history.replaceState) {
      const newUrl = `mesas.php?mesa_id=${encodeURIComponent(mesaRealId)}`;
      window.history.replaceState({ mesa_id: mesaRealId }, '', newUrl);
    }

    // Consultar la cuenta real de esa mesa desde MySQL
    cargarOrdenMesaEnPanelLateral(mesaRealId);
  };

  // Inicializar plano de mesas al cargar mesas.php respetando los datos SSR de MySQL
  function inicializarPlanoMesas() {
    const allCards = document.querySelectorAll('.table-card');
    if (!allCards || allCards.length === 0) return;

    tableStatusSelects.forEach(sel => {
      sel.dataset.prevStatus = sel.value;
    });

    actualizarResumenMesas();

    // Seleccionar la mesa activa únicamente si viene explícitamente en el parámetro GET de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const mesaUrl = urlParams.get('mesa_id') || urlParams.get('mesa');

    if (mesaUrl) {
      sessionStorage.setItem('mesaActiva', mesaUrl);
      const cardActiva = Array.from(allCards).find(
        c => String(c.dataset.tableId) === String(mesaUrl) || String(c.dataset.tableNumber) === String(mesaUrl)
      );
      if (cardActiva) {
        cardActiva.classList.add('active');
        const selectActiva = cardActiva.querySelector('.table-status-select');
        const estadoActivo = selectActiva ? selectActiva.value : 'disponible';
        const numDisplay = cardActiva.dataset.tableNumber || mesaUrl;
        const idReal = cardActiva.dataset.tableId || mesaUrl;
        seleccionarMesa(numDisplay, estadoActivo, idReal);
      }
    } else {
      allCards.forEach(c => c.classList.remove('active'));
    }
  }

  if (tableCards.length > 0) {
    inicializarPlanoMesas();
  }

  // Cambio de estado en el selector de cada mesa -> persistencia inmediata en MySQL (RF08)
  tableStatusSelects.forEach(select => {
    select.addEventListener('change', async () => {
      const parentCard = select.closest('.table-card');
      const mesaNum = parentCard ? (parentCard.dataset.tableNumber || '1') : '1';
      const mesaId = select.dataset.tableId || (parentCard ? (parentCard.dataset.tableId || mesaNum) : '1');
      const estadoAnterior = select.dataset.prevStatus || 'disponible';
      const nuevoValor = select.value;

      select.classList.remove('status-disponible', 'status-ocupada', 'status-proceso', 'status-sin-servicio');
      select.classList.add(`status-${nuevoValor}`);

      if (parentCard && parentCard.classList.contains('active')) {
        actualizarEstadoCuentaLateral(nuevoValor);
      }
      actualizarResumenMesas();

      try {
        const res = await fetch('../../controllers/ServicioController.php?action=cambiar_estado_mesa', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            id_mesa: parseInt(mesaId, 10) || mesaId,
            estado_mesa: nuevoValor
          })
        });
        const data = await res.json();

        if (!res.ok || !data || data.success !== true) {
          throw new Error((data && data.error) ? data.error : 'No fue posible actualizar el estado de la mesa.');
        }

        const slugConfirmado = mapearEstadoMesaASlug(data.estado_mesa || nuevoValor);
        select.value = slugConfirmado;
        select.dataset.prevStatus = slugConfirmado;
        select.classList.remove('status-disponible', 'status-ocupada', 'status-proceso', 'status-sin-servicio');
        select.classList.add(`status-${slugConfirmado}`);

        if (parentCard && parentCard.classList.contains('active')) {
          actualizarEstadoCuentaLateral(slugConfirmado);
        }
        actualizarResumenMesas();
      } catch (err) {
        console.error('Error al actualizar estado de mesa en MySQL:', err);
        select.value = estadoAnterior;
        select.classList.remove('status-disponible', 'status-ocupada', 'status-proceso', 'status-sin-servicio');
        select.classList.add(`status-${estadoAnterior}`);
        if (parentCard && parentCard.classList.contains('active')) {
          actualizarEstadoCuentaLateral(estadoAnterior);
        }
        actualizarResumenMesas();
        alert('Error al cambiar el estado de la mesa: ' + (err.message || 'Fallo de conexión con el servidor.'));
      }
    });
  });

  // Event listener para clic en las tarjetas de mesa
  tableCards.forEach(card => {
    card.addEventListener('click', (e) => {
      if (e.target.closest('.table-status-select') || e.target.tagName.toLowerCase() === 'select') {
        return;
      }

      const mesaNum = card.dataset.tableNumber || '';
      const mesaIdReal = card.dataset.tableId || mesaNum;
      const select = card.querySelector('.table-status-select');
      const estadoActual = select ? select.value : 'disponible';

      seleccionarMesa(mesaNum, estadoActual, mesaIdReal);

      if (window.innerWidth <= 1024) {
        abrirDetalleMovil();
      } else {
        const panelCuenta = document.querySelector('aside.cuenta-panel') || document.getElementById('cuentaPanel');
        const layoutMesas = document.querySelector('.mesas-layout') || document.getElementById('mesasLayout');
        const estaOculto = (panelCuenta && panelCuenta.classList.contains('hidden')) ||
                           (layoutMesas && layoutMesas.classList.contains('detail-hidden'));

        if (estaOculto) {
          const btnToggle = document.getElementById('btnToggleDetalle');
          if (btnToggle && panelCuenta && panelCuenta.classList.contains('hidden')) {
            btnToggle.click();
          } else {
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

    userProfileBadge.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        userProfileBadge.click();
      }
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.user-profile-dropdown-wrapper')) {
        userDropdownMenu.classList.remove('open', 'show');
        userDropdownMenu.setAttribute('aria-hidden', 'true');
        userProfileBadge.setAttribute('aria-expanded', 'false');
        if (userProfileWrapper) userProfileWrapper.classList.remove('open');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        userDropdownMenu.classList.remove('open', 'show');
        userDropdownMenu.setAttribute('aria-hidden', 'true');
        userProfileBadge.setAttribute('aria-expanded', 'false');
        if (userProfileWrapper) userProfileWrapper.classList.remove('open');
      }
    });
  }

  // 2. Comportamiento del Panel de Detalle en Escritorio y Móviles (mesas.php, pedidos_web.php, gestion_orden.php y cierre_cuenta.php)
  const btnToggleDetalle = document.getElementById('btnToggleDetalle');
  const ariaControlsTarget = btnToggleDetalle ? btnToggleDetalle.getAttribute('aria-controls') : null;
  const panelSidebar = (ariaControlsTarget ? document.getElementById(ariaControlsTarget) : null)
    || document.querySelector('#cuentaPanel, #activeOrderSidebar, #ticketSidebar, #webOrderSidebar, aside.cuenta-panel, aside.web-order-sidebar, aside.active-order-sidebar, aside.ticket-sidebar, aside.cierre-ticket-panel');
  const layoutContainer = document.querySelector('.mesas-layout, .web-orders-layout, .order-layout, .cierre-layout');
  const closeBtns = document.querySelectorAll('#cuentaCloseBtn, #webOrderCloseBtn, #activeOrderCloseBtn, #ticketCloseBtn, .cuenta-close-btn, .ticket-close-btn');
  const backdrops = document.querySelectorAll('#cuentaBackdrop, #webOrderBackdrop, #activeOrderBackdrop, #cierreBackdrop, .cuenta-backdrop');

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

  // En pantallas móviles (<= 1024px), los drawers laterales inician cerrados por defecto al cargar la página
  if (window.innerWidth <= 1024) {
    cerrarDetalleMovil();
  }

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

  closeBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarDetalleMovil();
    });
  });

  backdrops.forEach(b => {
    b.addEventListener('click', () => {
      cerrarDetalleMovil();
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && window.innerWidth <= 1024) {
      cerrarDetalleMovil();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      backdrops.forEach(b => {
        b.classList.remove('open', 'active');
        b.setAttribute('aria-hidden', 'true');
      });
      if (panelSidebar) {
        panelSidebar.classList.remove('open', 'active');
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
      const productModal = document.getElementById('product-modal');
      const cartBackdropEl = document.getElementById('cart-backdrop');
      const checkoutModalEl = document.getElementById('modal-checkout-cliente');
      const exitoModalEl = document.getElementById('modal-pedido-exito');
      const modalAsignarEl = document.getElementById('modalAsignarMesaWeb');
      if ((!productModal || !productModal.classList.contains('active')) &&
          (!cartBackdropEl || !cartBackdropEl.classList.contains('active')) &&
          (!checkoutModalEl || checkoutModalEl.getAttribute('aria-hidden') !== 'false') &&
          (!exitoModalEl || exitoModalEl.getAttribute('aria-hidden') !== 'false') &&
          (!modalAsignarEl || !modalAsignarEl.classList.contains('open'))) {
        document.body.style.overflow = '';
      }
    }
  });

  // Botón "Agregar Producto" en panel lateral de mesas.php
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
      sessionStorage.setItem('mesaActiva', mesaActiva);
      window.location.href = `gestion_orden.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: GESTIÓN DE ORDEN (gestion_orden.php - RF06, RF07, RF09)
   * ==========================================================================
   */
  const btnMapaMesas = document.getElementById('btnMapaMesas');
  const btnEnviarCocina = document.getElementById('btnEnviarCocina');
  const btnActualizarOrden = document.getElementById('btnActualizarOrden');
  const activeOrderList = document.getElementById('activeOrderList');
  const orderItemsBadge = document.getElementById('orderItemsBadge');
  const orderSubtotal = document.getElementById('orderSubtotal');
  const orderTip = document.getElementById('orderTip');
  const orderTotal = document.getElementById('orderTotal');
  const filaOrderAbono = document.getElementById('filaOrderAbono');
  const orderAbono = document.getElementById('orderAbono');
  const filaOrderSaldo = document.getElementById('filaOrderSaldo');
  const orderSaldo = document.getElementById('orderSaldo');

  const urlParamsGO = new URLSearchParams(window.location.search);
  const idMesaGestionActual = activeOrderList?.dataset.mesa
    || btnEnviarCocina?.dataset.mesa
    || urlParamsGO.get('mesa_id')
    || urlParamsGO.get('mesa')
    || sessionStorage.getItem('mesaActiva')
    || '1';
  const numeroMesaGestionActual = activeOrderList?.dataset.numeroMesa
    || btnEnviarCocina?.dataset.numeroMesa
    || idMesaGestionActual;

  if (activeOrderList) {
    sessionStorage.setItem('mesaActiva', String(idMesaGestionActual));
    const activeOrderPillText = document.querySelector('.order-pill-text');
    const activeOrderTitle = document.querySelector('.active-order-title');
    if (activeOrderPillText) {
      activeOrderPillText.textContent = `Orden Activa – Mesa ${numeroMesaGestionActual}`;
    }
    if (activeOrderTitle) {
      activeOrderTitle.textContent = `Cuenta - Mesa ${numeroMesaGestionActual}`;
    }
  }

  // Redirección al presionar "Mapa de mesas"
  if (btnMapaMesas) {
    btnMapaMesas.addEventListener('click', () => {
      window.location.href = `mesas.php?mesa_id=${encodeURIComponent(idMesaGestionActual)}`;
    });
  }

  // Recolecta los productos actuales de `#activeOrderList`
  function obtenerItemsCarritoMesaActual() {
    const itemsCarrito = [];
    if (!activeOrderList) return itemsCarrito;

    const orderItems = activeOrderList.querySelectorAll('.active-order-item');
    orderItems.forEach(item => {
      const id = (item.dataset.id || '').trim();
      const name = item.querySelector('.active-item-title')?.textContent.trim() || 'Producto';
      const qty = parseInt(item.querySelector('.qty-circle-value')?.textContent || '0', 10) || 0;
      const unitPrice = parseFloat(item.dataset.unitPrice || '0') || 0;

      if (id && qty > 0) {
        itemsCarrito.push({
          id: !isNaN(parseInt(id, 10)) ? parseInt(id, 10) : id,
          name: name,
          price: unitPrice,
          qty: qty
        });
      }
    });

    return itemsCarrito;
  }

  // Persiste cualquier apertura o modificación de la orden en MySQL vía `actualizar_orden` (RF07, RF09)
  async function persistirOrdenMesaEnServidor({ redirigirAlMapa = false, botonAccion = null } = {}) {
    const itemsCarrito = obtenerItemsCarritoMesaActual();
    if (itemsCarrito.length === 0) {
      alert('Por favor agrega al menos un producto a la orden antes de guardarla.');
      return;
    }

    const idMesaRaw = (botonAccion && botonAccion.dataset.mesa)
      ? botonAccion.dataset.mesa
      : idMesaGestionActual;
    const idMesaEnviar = (!isNaN(parseInt(idMesaRaw, 10)) && !String(idMesaRaw).startsWith('M'))
      ? parseInt(idMesaRaw, 10)
      : idMesaRaw;

    const textoOriginalBtn = botonAccion ? botonAccion.innerHTML : '';
    if (btnActualizarOrden) btnActualizarOrden.disabled = true;
    if (btnEnviarCocina) btnEnviarCocina.disabled = true;
    if (botonAccion) {
      botonAccion.innerHTML = redirigirAlMapa
        ? `<span>¡Guardando y enviando a cocina...!</span>`
        : `<span>Guardando cambios en BD...</span>`;
    }

    try {
      const res = await fetch('../../controllers/ServicioController.php?action=actualizar_orden', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          id_mesa: idMesaEnviar,
          productos: itemsCarrito,
          reemplazar_items: true,
          estado_mesa: 'En proceso',
          estado_pedido: 'En preparación'
        })
      });

      const data = await res.json();
      if (!res.ok || !data || data.success !== true) {
        throw new Error((data && data.error) ? data.error : 'No fue posible guardar la orden en la base de datos.');
      }

      // Limpiar cualquier residuo obsoleto en localStorage y sincronizar abono/totales reales
      localStorage.removeItem('orden_mesa_' + idMesaEnviar);
      sessionStorage.setItem('mesaActiva', String(data.id_mesa || idMesaEnviar));

      if (activeOrderList && data.orden) {
        if (data.orden.id_pedido) {
          activeOrderList.dataset.idPedido = String(data.orden.id_pedido);
        }
        if (typeof data.orden.monto_abonado !== 'undefined') {
          activeOrderList.dataset.montoAbonado = Number(data.orden.monto_abonado || 0).toFixed(2);
        }
        actualizarTotalesOrden();
      }

      if (redirigirAlMapa) {
        window.location.href = `mesas.php?mesa_id=${encodeURIComponent(data.id_mesa || idMesaEnviar)}`;
        return;
      }

      if (botonAccion) {
        botonAccion.innerHTML = `<span>¡Orden Actualizada!</span>`;
        setTimeout(() => {
          botonAccion.innerHTML = textoOriginalBtn;
          if (btnActualizarOrden) btnActualizarOrden.disabled = false;
          if (btnEnviarCocina) btnEnviarCocina.disabled = false;
        }, 1200);
      } else {
        if (btnActualizarOrden) btnActualizarOrden.disabled = false;
        if (btnEnviarCocina) btnEnviarCocina.disabled = false;
      }
    } catch (err) {
      console.error('Error al actualizar la orden en MySQL:', err);
      alert('Error al guardar la orden en la base de datos: ' + (err.message || 'Fallo de conexión con el servidor.'));
      if (botonAccion && textoOriginalBtn) {
        botonAccion.innerHTML = textoOriginalBtn;
      }
      if (btnActualizarOrden) btnActualizarOrden.disabled = false;
      if (btnEnviarCocina) btnEnviarCocina.disabled = false;
    }
  }

  if (btnEnviarCocina) {
    btnEnviarCocina.addEventListener('click', () => {
      persistirOrdenMesaEnServidor({ redirigirAlMapa: true, botonAccion: btnEnviarCocina });
    });
  }

  if (btnActualizarOrden) {
    btnActualizarOrden.addEventListener('click', () => {
      persistirOrdenMesaEnServidor({ redirigirAlMapa: false, botonAccion: btnActualizarOrden });
    });
  }

  // Filtros de categoría en catálogo de orden (gestion_orden.php)
  const orderCategoryPills = document.querySelectorAll('.order-category-pill');
  orderCategoryPills.forEach(pill => {
    pill.addEventListener('click', () => {
      orderCategoryPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');

      const filterCat = (pill.dataset.category || 'todos').toLowerCase();
      const filterCatId = (pill.dataset.categoryId || '').trim();
      const cards = document.querySelectorAll('.order-product-card');

      cards.forEach(card => {
        const cardCat = (card.dataset.category || '').toLowerCase();
        const cardCatId = (card.dataset.categoryId || '').trim();
        const coincide = filterCat === 'todos'
          || (filterCatId !== '' && cardCatId !== '' && filterCatId === cardCatId)
          || cardCat === filterCat;
        card.style.display = coincide ? 'flex' : 'none';
      });
    });
  });

  // Recalcula ítems, subtotal, propina (10%), total, abono web y saldo pendiente (RF07, RF09)
  function actualizarTotalesOrden() {
    if (!activeOrderList) return;
    const items = activeOrderList.querySelectorAll('.active-order-item');
    let totalItems = 0;
    let subtotalAcumulado = 0;

    items.forEach(item => {
      const qtySpan = item.querySelector('.qty-circle-value');
      const qty = parseInt(qtySpan ? qtySpan.textContent : '1', 10) || 1;
      
      let unitPrice = parseFloat(item.dataset.unitPrice);
      if (isNaN(unitPrice)) {
        const priceText = item.querySelector('.active-item-price')?.textContent || '$0.00';
        const parsedPrice = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
        unitPrice = qty > 0 ? (parsedPrice / qty) : parsedPrice;
        item.dataset.unitPrice = unitPrice;
      }

      totalItems += qty;
      const lineaSub = Math.round(unitPrice * qty * 100) / 100;
      subtotalAcumulado = Math.round((subtotalAcumulado + lineaSub) * 100) / 100;

      const priceSpan = item.querySelector('.active-item-price');
      if (priceSpan) {
        priceSpan.textContent = `$${lineaSub.toFixed(2)}`;
      }
    });

    const propina = Math.round(subtotalAcumulado * 0.10 * 100) / 100;
    const totalGeneral = Math.round((subtotalAcumulado + propina) * 100) / 100;
    const montoAbonado = parseFloat(activeOrderList.dataset.montoAbonado || '0') || 0;
    const saldoPendiente = Math.max(0, Math.round((totalGeneral - montoAbonado) * 100) / 100);

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
    if (filaOrderAbono && orderAbono) {
      orderAbono.textContent = `-$${montoAbonado.toFixed(2)} USD`;
      filaOrderAbono.style.display = montoAbonado > 0 ? 'flex' : 'none';
    }
    if (filaOrderSaldo && orderSaldo) {
      orderSaldo.textContent = `$${saldoPendiente.toFixed(2)} USD`;
      filaOrderSaldo.style.display = montoAbonado > 0 ? 'flex' : 'none';
    }

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
            <h4 class="active-item-title">${escaparHtmlServicio(nombre)}</h4>
            <span class="active-item-price">$${unitPrice.toFixed(2)}</span>
          </div>
          <div class="active-item-controls">
            <button class="qty-circle-btn" type="button" data-action="decrease" aria-label="Disminuir">-</button>
            <span class="qty-circle-value">1</span>
            <button class="qty-circle-btn" type="button" data-action="increase" aria-label="Aumentar">+</button>
            <button class="btn-remove-item" type="button" data-action="delete" aria-label="Eliminar producto">
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

  // Compatibilidad botón único `.btn-add-item`
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
    } else {
      const newItem = document.createElement('div');
      newItem.className = 'active-order-item';
      newItem.dataset.id = id;
      newItem.dataset.unitPrice = unitPrice;
      newItem.innerHTML = `
        <div class="active-item-info">
          <h4 class="active-item-title">${escaparHtmlServicio(nombre)}</h4>
          <span class="active-item-price">$${unitPrice.toFixed(2)}</span>
        </div>
        <div class="active-item-controls">
          <button class="qty-circle-btn" type="button" data-action="decrease" aria-label="Disminuir">-</button>
          <span class="qty-circle-value">1</span>
          <button class="qty-circle-btn" type="button" data-action="increase" aria-label="Aumentar">+</button>
          <button class="btn-remove-item" type="button" data-action="delete" aria-label="Eliminar producto">
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

  // Al iniciar `gestion_orden.php`, preservar siempre los `.active-order-item` precargados por PHP desde MySQL (sin sobrescribir con localStorage)
  if (activeOrderList) {
    actualizarTotalesOrden();
  }

  /**
   * ==========================================================================
   * PANTALLA: CIERRE DE CUENTA (cierre_cuenta.php - RNF03, RF09, RF10, RF14)
   * ==========================================================================
   */
  const btnImprimirTicket = document.getElementById('btnImprimirTicket') || document.getElementById('btnGenerarPDF');
  const ticketContainer = document.getElementById('ticketContainer');
  const btnProcesarPago = document.getElementById('btnProcesarPago');
  const inputMontoRecibido = document.getElementById('inputMontoRecibido');
  const cashChangeValue = document.getElementById('cashChangeValue');
  const cardMetodoEfectivo = document.getElementById('metodoEfectivoCard');
  const cardMetodoTarjeta = document.getElementById('metodoTarjetaCard');
  const cashDetailCard = document.getElementById('cashDetailCard') || document.querySelector('.cash-detail-card');
  const cardTerminalSimBox = document.getElementById('cardTerminalSimBox');
  const ticketMetodoPagoVal = document.getElementById('ticketMetodoPagoVal');
  const ticketEfectivoRecibidoVal = document.getElementById('ticketEfectivoRecibidoVal');
  const ticketCambioVal = document.getElementById('ticketCambioVal');

  const urlParamsCierre = new URLSearchParams(window.location.search);
  const mesaCierre = btnProcesarPago?.dataset.numeroMesa
    || urlParamsCierre.get('mesa_id')
    || urlParamsCierre.get('mesa')
    || sessionStorage.getItem('mesaActiva')
    || '1';

  // Sincroniza dinámicamente el cálculo de cambio y los valores en el ticket PDF (#ticketContainer)
  function sincronizarCobroYTicketCaja() {
    if (!btnProcesarPago && !inputMontoRecibido) return;

    const saldoBase = parseFloat(btnProcesarPago?.dataset.total || inputMontoRecibido?.dataset.total || '0') || 0;
    const esTarjeta = Boolean(cardMetodoTarjeta && cardMetodoTarjeta.classList.contains('active'));

    if (esTarjeta) {
      if (cashChangeValue) cashChangeValue.textContent = '0.00';
      if (ticketMetodoPagoVal) ticketMetodoPagoVal.textContent = 'Tarjeta';
      if (ticketEfectivoRecibidoVal) ticketEfectivoRecibidoVal.textContent = `$${saldoBase.toFixed(2)}`;
      if (ticketCambioVal) ticketCambioVal.textContent = '$0.00';
    } else {
      const recibido = parseFloat(inputMontoRecibido?.value || '0') || 0;
      const cambio = Math.max(0, Math.round((recibido - saldoBase) * 100) / 100);
      if (cashChangeValue) cashChangeValue.textContent = cambio.toFixed(2);
      if (ticketMetodoPagoVal) ticketMetodoPagoVal.textContent = 'Efectivo';
      if (ticketEfectivoRecibidoVal) ticketEfectivoRecibidoVal.textContent = `$${recibido.toFixed(2)}`;
      if (ticketCambioVal) ticketCambioVal.textContent = `$${cambio.toFixed(2)}`;
    }
  }

  // Alternar selección de método de pago (Efectivo / Tarjeta - RNF03)
  if (cardMetodoEfectivo && cardMetodoTarjeta) {
    function seleccionarMetodoCaja(metodo) {
      if (metodo === 'tarjeta') {
        cardMetodoTarjeta.classList.add('active');
        cardMetodoTarjeta.classList.remove('payment-method-card-inactive');
        cardMetodoEfectivo.classList.remove('active');
        if (cashDetailCard) cashDetailCard.style.display = 'none';
        if (cardTerminalSimBox) cardTerminalSimBox.classList.add('visible');
      } else {
        cardMetodoEfectivo.classList.add('active');
        cardMetodoEfectivo.classList.remove('payment-method-card-inactive');
        cardMetodoTarjeta.classList.remove('active');
        if (cashDetailCard) cashDetailCard.style.display = '';
        if (cardTerminalSimBox) cardTerminalSimBox.classList.remove('visible');
      }
      sincronizarCobroYTicketCaja();
    }

    cardMetodoEfectivo.addEventListener('click', () => seleccionarMetodoCaja('efectivo'));
    cardMetodoTarjeta.addEventListener('click', () => seleccionarMetodoCaja('tarjeta'));

    [cardMetodoEfectivo, cardMetodoTarjeta].forEach(cardEl => {
      cardEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          cardEl.click();
        }
      });
    });
  }

  if (inputMontoRecibido) {
    inputMontoRecibido.addEventListener('input', sincronizarCobroYTicketCaja);
    sincronizarCobroYTicketCaja();
  }

  // 1. Botón "Imprimir": exporta el comprobante de venta a PDF mediante html2pdf.js
  if (btnImprimirTicket && ticketContainer) {
    btnImprimirTicket.addEventListener('click', () => {
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
        const ticketSidebarEl = document.getElementById('ticketSidebar');
        const prevTicketStyle = {
          maxHeight: ticketContainer.style.maxHeight,
          height: ticketContainer.style.height,
          overflow: ticketContainer.style.overflow,
          overflowY: ticketContainer.style.overflowY,
          scrollTop: ticketContainer.scrollTop
        };
        const prevSidebarStyle = ticketSidebarEl ? {
          maxHeight: ticketSidebarEl.style.maxHeight,
          height: ticketSidebarEl.style.height,
          overflow: ticketSidebarEl.style.overflow
        } : null;

        // Expandir temporalmente el contenedor para capturar todo el contenido sin recortes por scroll
        ticketContainer.scrollTop = 0;
        ticketContainer.style.setProperty('max-height', 'none', 'important');
        ticketContainer.style.setProperty('height', 'auto', 'important');
        ticketContainer.style.setProperty('overflow', 'visible', 'important');
        ticketContainer.style.setProperty('overflow-y', 'visible', 'important');

        if (ticketSidebarEl) {
          ticketSidebarEl.style.setProperty('max-height', 'none', 'important');
          ticketSidebarEl.style.setProperty('height', 'auto', 'important');
          ticketSidebarEl.style.setProperty('overflow', 'visible', 'important');
        }

        const restaurarEstilosTicket = () => {
          ticketContainer.style.maxHeight = prevTicketStyle.maxHeight;
          ticketContainer.style.height = prevTicketStyle.height;
          ticketContainer.style.overflow = prevTicketStyle.overflow;
          ticketContainer.style.overflowY = prevTicketStyle.overflowY;
          ticketContainer.scrollTop = prevTicketStyle.scrollTop;

          if (ticketSidebarEl && prevSidebarStyle) {
            ticketSidebarEl.style.maxHeight = prevSidebarStyle.maxHeight;
            ticketSidebarEl.style.height = prevSidebarStyle.height;
            ticketSidebarEl.style.overflow = prevSidebarStyle.overflow;
          }
        };

        const anchoPx = Math.max(ticketContainer.scrollWidth, ticketContainer.offsetWidth, 380);
        const altoPx = Math.max(ticketContainer.scrollHeight, ticketContainer.offsetHeight);
        // Mantener el ancho estándar A4 (210 mm) y ajustar el alto dinámicamente si el ticket supera una página A4 (297 mm)
        const margenMm = 10;
        const anchoPdfMm = 210;
        const anchoContenidoMm = anchoPdfMm - (margenMm * 2);
        const altoContenidoMm = Math.ceil((altoPx * anchoContenidoMm) / anchoPx);
        const altoPdfMm = Math.max(297, altoContenidoMm + (margenMm * 2) + 6);

        const opciones = {
          margin:       [margenMm, margenMm, margenMm, margenMm],
          filename:     `Ticket_NIXGARU_Mesa_${mesaCierre}.pdf`,
          image:        { type: 'jpeg', quality: 0.98 },
          html2canvas:  {
            scale: 2,
            useCORS: true,
            letterRendering: true,
            scrollY: 0,
            windowHeight: altoPx + 200
          },
          jsPDF:        {
            unit: 'mm',
            format: [anchoPdfMm, altoPdfMm],
            orientation: 'portrait'
          },
          pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };

        html2pdf()
          .set(opciones)
          .from(ticketContainer)
          .save()
          .then(() => {
            restaurarEstilosTicket();
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
            restaurarEstilosTicket();
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

  // 2. Botón "Procesar Pago & Liberar Mesa" -> `ServicioController.php?action=cerrar_cuenta` (sin fallback local)
  if (btnProcesarPago) {
    btnProcesarPago.addEventListener('click', async () => {
      const urlParams = new URLSearchParams(window.location.search);
      const mesaActiva = btnProcesarPago.dataset.mesa || urlParams.get('mesa_id') || urlParams.get('mesa') || '1';
      const numMesaDisplay = btnProcesarPago.dataset.numeroMesa || mesaActiva;
      const idPedido = btnProcesarPago.dataset.idpedido ? parseInt(btnProcesarPago.dataset.idpedido, 10) : 0;
      const saldoPendiente = parseFloat(btnProcesarPago.dataset.total || '0') || 0;

      if (!idPedido || idPedido <= 0) {
        alert('No existe una orden activa pendiente de cobro para esta mesa.');
        return;
      }

      const esTarjeta = Boolean(cardMetodoTarjeta && cardMetodoTarjeta.classList.contains('active'));
      const metodoPago = esTarjeta ? 'Tarjeta' : 'Efectivo';
      const montoRecibido = esTarjeta
        ? saldoPendiente
        : (parseFloat(inputMontoRecibido?.value || '0') || 0);

      if (!esTarjeta && (montoRecibido + 0.0001) < saldoPendiente) {
        alert(`El monto recibido en efectivo ($${montoRecibido.toFixed(2)}) no puede ser menor al saldo pendiente a cobrar en caja ($${saldoPendiente.toFixed(2)}).`);
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

      try {
        const res = await fetch('../../controllers/ServicioController.php?action=cerrar_cuenta', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            id_mesa: (!isNaN(parseInt(mesaActiva, 10)) && !String(mesaActiva).startsWith('M')) ? parseInt(mesaActiva, 10) : mesaActiva,
            id_pedido: idPedido,
            metodo_pago: metodoPago,
            monto_recibido: montoRecibido
          })
        });

        const data = await res.json();
        if (!res.ok || !data || data.success !== true) {
          throw new Error((data && data.error) ? data.error : 'No fue posible completar el cierre de la cuenta.');
        }

        // Sincronizar el comprobante con los valores definitivos retornados por el servidor
        const cierreInfo = data.cierre || data;
        if (ticketMetodoPagoVal && cierreInfo.metodo_pago) {
          ticketMetodoPagoVal.textContent = cierreInfo.metodo_pago;
        }
        if (ticketEfectivoRecibidoVal && typeof cierreInfo.efectivo_recibido !== 'undefined') {
          ticketEfectivoRecibidoVal.textContent = `$${Number(cierreInfo.efectivo_recibido).toFixed(2)}`;
        }
        if (ticketCambioVal && typeof cierreInfo.cambio !== 'undefined') {
          ticketCambioVal.textContent = `$${Number(cierreInfo.cambio).toFixed(2)}`;
        }

        localStorage.removeItem('orden_mesa_' + mesaActiva);
        localStorage.removeItem('estado_mesa_' + mesaActiva);

        const cambioFinal = Number(cierreInfo.cambio || 0);
        const notaCambio = (!esTarjeta && cambioFinal > 0)
          ? `\nCambio a entregar al cliente: $${cambioFinal.toFixed(2)} USD.`
          : '';

        alert(`¡Pago (${metodoPago}) procesado con éxito para la Mesa ${numMesaDisplay}!${notaCambio}\nLos insumos han sido descontados del inventario y la mesa ahora está 'Disponible'.`);
        window.location.href = `mesas.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
      } catch (err) {
        console.error('Error al cerrar cuenta en MySQL:', err);
        alert('Error al procesar el pago: ' + (err.message || 'Fallo de conexión con el servidor.'));
        btnProcesarPago.innerHTML = textoOriginal;
        btnProcesarPago.disabled = false;
      }
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
      sessionStorage.setItem('mesaActiva', mesaActiva);
      window.location.href = `cierre_cuenta.php?mesa_id=${encodeURIComponent(mesaActiva)}`;
    });
  }

  /**
   * ==========================================================================
   * PANTALLA: RECEPCIÓN DE PEDIDOS WEB Y ASIGNACIÓN DE MESAS (pedidos_web.php - RF16, RF17)
   * ==========================================================================
   */
  const tbodyPedidosWeb = document.getElementById('tbodyPedidosWeb');
  if (tbodyPedidosWeb) {
    const initialJsonEl = document.getElementById('pedidosWebInitialData');
    const contadorPedidosWebBadge = document.getElementById('contadorPedidosWebBadge');
    const filterPillsWeb = document.querySelectorAll('.web-order-filter-pill');
    const inputBuscarPedidoWeb = document.getElementById('inputBuscarPedidoWeb');
    const btnRefrescarPedidosWeb = document.getElementById('btnRefrescarPedidosWeb');

    // Elementos del panel lateral (#webOrderSidebar)
    const webOrderEmptyState = document.getElementById('webOrderEmptyState');
    const webOrderDetailContent = document.getElementById('webOrderDetailContent');
    const detWebTituloFolio = document.getElementById('detWebTituloFolio');
    const detWebClienteNombre = document.getElementById('detWebClienteNombre');
    const detWebClienteTelefono = document.getElementById('detWebClienteTelefono');
    const detWebClienteCorreo = document.getElementById('detWebClienteCorreo');
    const detWebModalidad = document.getElementById('detWebModalidad');
    const detWebMesaAsignada = document.getElementById('detWebMesaAsignada');
    const detWebBloqueLogistico = document.getElementById('detWebBloqueLogistico');
    const detWebMunicipio = document.getElementById('detWebMunicipio');
    const detWebDireccion = document.getElementById('detWebDireccion');
    const detWebReferencia = document.getElementById('detWebReferencia');
    const detWebListaProductos = document.getElementById('detWebListaProductos');
    const selectEstadoPedidoWeb = document.getElementById('selectEstadoPedidoWeb');
    const btnActualizarEstadoWeb = document.getElementById('btnActualizarEstadoWeb');
    const detWebSubtotal = document.getElementById('detWebSubtotal');
    const detWebPropina = document.getElementById('detWebPropina');
    const detWebTotal = document.getElementById('detWebTotal');
    const detWebAbono = document.getElementById('detWebAbono');
    const detWebSaldo = document.getElementById('detWebSaldo');
    const btnAbrirAsignarMesaWeb = document.getElementById('btnAbrirAsignarMesaWeb');
    const bloqueAccionesMesaAsignada = document.getElementById('bloqueAccionesMesaAsignada');
    const btnIrAgregarProductosWeb = document.getElementById('btnIrAgregarProductosWeb');
    const btnIrCierreCuentaWeb = document.getElementById('btnIrCierreCuentaWeb');

    // Elementos del Modal RF17 (#modalAsignarMesaWeb)
    const modalAsignarMesaWeb = document.getElementById('modalAsignarMesaWeb');
    const modalAsignarFolio = document.getElementById('modalAsignarFolio');
    const modalAsignarCliente = document.getElementById('modalAsignarCliente');
    const alertaSinMesasDisponibles = document.getElementById('alertaSinMesasDisponibles');
    const errorAsignarMesaWeb = document.getElementById('errorAsignarMesaWeb');
    const formAsignarMesaWeb = document.getElementById('formAsignarMesaWeb');
    const inputIdPedidoAsignarWeb = document.getElementById('inputIdPedidoAsignarWeb');
    const selectMesaDisponibleWeb = document.getElementById('selectMesaDisponibleWeb');
    const btnCancelarAsignarMesaWeb = document.getElementById('btnCancelarAsignarMesaWeb');
    const btnConfirmarAsignarMesaWeb = document.getElementById('btnConfirmarAsignarMesaWeb');

    let pedidosWebCache = [];
    if (initialJsonEl && initialJsonEl.textContent.trim() !== '') {
      try {
        const parsed = JSON.parse(initialJsonEl.textContent);
        if (Array.isArray(parsed)) {
          pedidosWebCache = parsed;
        }
      } catch (err) {
        console.error('Error al hidratar JSON inicial de pedidos web:', err);
      }
    }

    let filtroModalidadWeb = 'Todos';
    let terminoBusquedaWeb = '';
    let idPedidoWebActivo = null;

    function formatearFolioWeb(idPedido) {
      return '#NIX-' + String(idPedido || 0).padStart(5, '0');
    }

    function obtenerClaseEstadoPedidoWeb(estado) {
      const st = String(estado || '').toLowerCase().trim();
      if (st.includes('prepar')) return 'st-preparacion';
      if (st.includes('listo')) return 'st-listo';
      if (st.includes('camino')) return 'st-camino';
      if (st.includes('entreg') || st.includes('pagad')) return 'st-pagado';
      return 'st-pendiente';
    }

    function formatearFechaHoraWeb(fechaStr) {
      if (!fechaStr) return '---';
      const normalizada = String(fechaStr).replace(' ', 'T');
      const d = new Date(normalizada);
      if (isNaN(d.getTime())) return String(fechaStr);
      const pad = (n) => String(n).padStart(2, '0');
      return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function renderizarTablaPedidosWeb() {
      const q = terminoBusquedaWeb.toLowerCase().trim();
      const filtrados = pedidosWebCache.filter(ped => {
        const tipo = String(ped.tipo_orden || '');
        if (filtroModalidadWeb !== 'Todos' && tipo !== filtroModalidadWeb) {
          return false;
        }
        if (q !== '') {
          const folio = formatearFolioWeb(ped.id_pedido).toLowerCase();
          const cliente = `${ped.nombre_cliente || ''} ${ped.apellido_cliente || ''} ${ped.telefono_cliente || ''} ${ped.correo_cliente || ''}`.toLowerCase();
          if (!folio.includes(q) && !String(ped.id_pedido).includes(q) && !cliente.includes(q)) {
            return false;
          }
        }
        return true;
      });

      if (contadorPedidosWebBadge) {
        contadorPedidosWebBadge.textContent = `${filtrados.length} ${filtrados.length === 1 ? 'Pedido visible' : 'Pedidos visibles'}`;
      }

      if (filtrados.length === 0) {
        tbodyPedidosWeb.innerHTML = `
          <tr id="filaSinPedidosWeb">
            <td colspan="9" style="text-align: center; padding: 42px 16px; color: var(--color-texto-muted); border: 1.5px dashed var(--color-borde-suave); border-radius: 14px;">
              No se encontraron pedidos web que coincidan con el filtro seleccionado.
            </td>
          </tr>
        `;
        return;
      }

      let html = '';
      filtrados.forEach(ped => {
        const idPed = Number(ped.id_pedido);
        const folio = formatearFolioWeb(idPed);
        const fechaTxt = formatearFechaHoraWeb(ped.fecha_hora);
        const nombreCompleto = `${ped.nombre_cliente || ''} ${ped.apellido_cliente || ''}`.trim() || 'Cliente Web';
        const tel = ped.telefono_cliente || 'Sin teléfono';
        const tipoOrd = ped.tipo_orden || 'Retiro en el local';
        const esDom = tipoOrd === 'Envío a domicilio';
        const modalidadCorta = esDom ? 'Domicilio' : 'Retiro';
        const numMesa = ped.numero_mesa ?? ped.id_mesa ?? null;
        const tieneMesaAsig = (numMesa !== null && numMesa !== '' && Number(numMesa) > 0);
        const estadoPed = ped.estado_pedido || 'Pendiente';
        const claseEst = obtenerClaseEstadoPedidoWeb(estadoPed);
        const total = Number(ped.total_pagar || 0);
        const abono = Number(ped.monto_abonado || 0);
        const saldo = Number(ped.saldo_pendiente || 0);
        const activaCls = idPedidoWebActivo === idPed ? 'active' : '';

        const chipMesa = tieneMesaAsig
          ? `<span class="mesa-assigned-chip">Mesa ${escaparHtmlServicio(numMesa)}</span>`
          : `<span class="mesa-unassigned-chip">Sin asignar</span>`;

        const metaMesaMovil = tieneMesaAsig
          ? `<span class="mobile-meta-mesa">Mesa ${escaparHtmlServicio(numMesa)}</span>`
          : '';

        html += `
          <tr class="web-order-row ${activaCls}" data-id-pedido="${idPed}" data-tipo-orden="${escaparHtmlServicio(tipoOrd)}">
            <td class="col-web-folio"><span class="web-folio-code">${escaparHtmlServicio(folio)}</span></td>
            <td class="col-web-fecha" style="white-space: nowrap; color: #52525B;">${escaparHtmlServicio(fechaTxt)}</td>
            <td class="col-web-cliente">
              <div class="web-client-cell">
                <span class="web-client-name">${escaparHtmlServicio(nombreCompleto)}</span>
                <span class="web-client-phone">${escaparHtmlServicio(tel)}</span>
                <div class="web-client-mobile-meta">
                  <span class="mobile-meta-chip ${esDom ? 'modality-domicilio' : 'modality-retiro'}">${escaparHtmlServicio(modalidadCorta)}</span>
                  ${metaMesaMovil}
                </div>
              </div>
            </td>
            <td class="col-web-modalidad">
              <span class="modality-pill ${esDom ? 'modality-domicilio' : 'modality-retiro'}">${escaparHtmlServicio(tipoOrd)}</span>
            </td>
            <td class="col-web-mesa">${chipMesa}</td>
            <td class="col-web-total" style="font-weight: 700;">$${total.toFixed(2)}</td>
            <td class="col-web-abono" style="color: #059669; font-weight: 600;">$${abono.toFixed(2)}</td>
            <td class="col-web-saldo" style="color: ${saldo > 0 ? '#C52E45' : '#059669'}; font-weight: 700;">$${saldo.toFixed(2)}</td>
            <td class="col-web-estado"><span class="order-status-pill ${claseEst}">${escaparHtmlServicio(estadoPed)}</span></td>
          </tr>
        `;
      });

      tbodyPedidosWeb.innerHTML = html;
    }

    const webOrderSidebar = document.getElementById('webOrderSidebar');
    const webOrderBackdrop = document.getElementById('webOrderBackdrop');
    const webOrderCloseBtn = document.getElementById('webOrderCloseBtn');
    const webOrdersLayout = document.getElementById('webOrdersLayout') || document.querySelector('.web-orders-layout');

    function abrirDrawerPedidoWeb() {
      if (!webOrderSidebar) return;
      webOrderSidebar.classList.add('open', 'active');
      webOrderSidebar.classList.remove('hidden');
      webOrderSidebar.setAttribute('aria-hidden', 'false');

      if (webOrdersLayout) {
        webOrdersLayout.classList.remove('detail-hidden');
      }

      if (btnToggleDetalle) {
        btnToggleDetalle.classList.add('active');
        btnToggleDetalle.classList.remove('panel-closed');
        btnToggleDetalle.setAttribute('aria-expanded', 'true');
      }

      if (window.innerWidth <= 1024) {
        if (webOrderBackdrop) {
          webOrderBackdrop.classList.add('open', 'active');
          webOrderBackdrop.setAttribute('aria-hidden', 'false');
        }
        document.body.style.overflow = 'hidden';
      }
    }

    function cerrarDrawerPedidoWeb() {
      if (!webOrderSidebar) return;
      webOrderSidebar.classList.remove('open', 'active');
      webOrderSidebar.setAttribute('aria-hidden', 'true');

      if (webOrderBackdrop) {
        webOrderBackdrop.classList.remove('open', 'active');
        webOrderBackdrop.setAttribute('aria-hidden', 'true');
      }

      if (btnToggleDetalle) {
        btnToggleDetalle.classList.remove('active');
        btnToggleDetalle.classList.add('panel-closed');
        btnToggleDetalle.setAttribute('aria-expanded', 'false');
      }

      document.body.style.overflow = '';
    }

    window.abrirDrawerPedidoWeb = abrirDrawerPedidoWeb;
    window.cerrarDrawerPedidoWeb = cerrarDrawerPedidoWeb;

    if (webOrderCloseBtn) {
      webOrderCloseBtn.addEventListener('click', (e) => {
        e.preventDefault();
        cerrarDrawerPedidoWeb();
      });
    }

    if (webOrderBackdrop) {
      webOrderBackdrop.addEventListener('click', () => {
        cerrarDrawerPedidoWeb();
      });
    }

    // Estado inicial en móvil (<= 1024px): #webOrderSidebar inicia cerrado al cargar la página
    if (window.innerWidth <= 1024) {
      cerrarDrawerPedidoWeb();
    }

    function mostrarDetallePedidoWeb(idPedido, abrirDrawerEnMovil = true) {
      const pedido = pedidosWebCache.find(p => Number(p.id_pedido) === Number(idPedido));
      if (!pedido) return;

      idPedidoWebActivo = Number(pedido.id_pedido);
      tbodyPedidosWeb.querySelectorAll('.web-order-row').forEach(tr => {
        tr.classList.toggle('active', Number(tr.dataset.idPedido) === idPedidoWebActivo);
      });

      if (webOrderEmptyState) webOrderEmptyState.style.display = 'none';
      if (webOrderDetailContent) webOrderDetailContent.style.display = 'flex';

      const folio = formatearFolioWeb(pedido.id_pedido);
      const nombreCompleto = `${pedido.nombre_cliente || ''} ${pedido.apellido_cliente || ''}`.trim() || 'Cliente Web';
      const tipoOrd = pedido.tipo_orden || 'Retiro en el local';
      const tieneMesa = pedido.id_mesa !== null && pedido.id_mesa !== undefined && Number(pedido.id_mesa) > 0;
      const etiquetaMesa = tieneMesa
        ? `Mesa ${pedido.numero_mesa ?? pedido.id_mesa} (En proceso)`
        : 'Sin asignar';

      if (detWebTituloFolio) detWebTituloFolio.textContent = folio;
      if (detWebClienteNombre) detWebClienteNombre.textContent = nombreCompleto;
      if (detWebClienteTelefono) detWebClienteTelefono.textContent = pedido.telefono_cliente || 'No registrado';
      if (detWebClienteCorreo) detWebClienteCorreo.textContent = pedido.correo_cliente || 'No registrado';
      if (detWebModalidad) detWebModalidad.textContent = tipoOrd;
      if (detWebMesaAsignada) detWebMesaAsignada.textContent = etiquetaMesa;

      if (detWebBloqueLogistico) {
        if (tipoOrd === 'Envío a domicilio') {
          detWebBloqueLogistico.style.display = 'flex';
          if (detWebMunicipio) detWebMunicipio.textContent = pedido.municipio || 'San Salvador';
          if (detWebDireccion) detWebDireccion.textContent = pedido.direccion || '---';
          if (detWebReferencia) detWebReferencia.textContent = pedido.punto_referencia || 'Sin punto de referencia';
        } else {
          detWebBloqueLogistico.style.display = 'none';
        }
      }

      if (detWebListaProductos) {
        const prods = Array.isArray(pedido.productos) ? pedido.productos : [];
        if (prods.length === 0) {
          detWebListaProductos.innerHTML = `<span style="font-size: 0.8rem; color: var(--color-texto-muted);">Sin detalle de productos.</span>`;
        } else {
          detWebListaProductos.innerHTML = prods.map(pr => {
            const cant = parseInt(pr.cantidad, 10) || 1;
            const sub = Number(pr.subtotal || 0);
            return `
              <div class="cuenta-item">
                <span class="item-qty-badge">${cant}</span>
                <span class="item-name">${escaparHtmlServicio(pr.nombre_producto || 'Producto')}</span>
                <span class="item-price">$${sub.toFixed(2)}</span>
              </div>
            `;
          }).join('');
        }
      }

      if (selectEstadoPedidoWeb) {
        const estActual = String(pedido.estado_pedido || 'Pendiente');
        const existeOpcion = Array.from(selectEstadoPedidoWeb.options).some(opt => opt.value === estActual);
        if (!existeOpcion) {
          const optExtra = document.createElement('option');
          optExtra.value = estActual;
          optExtra.textContent = estActual;
          selectEstadoPedidoWeb.appendChild(optExtra);
        }
        selectEstadoPedidoWeb.value = estActual;
      }

      const subtotal = Number(pedido.subtotal || 0);
      const porcPropina = Number(pedido.porcentaje_propina || 10);
      const propina = Math.round(subtotal * (porcPropina / 100) * 100) / 100;
      const total = Number(pedido.total_pagar ?? (subtotal + propina));
      const abono = Number(pedido.monto_abonado || 0);
      const saldo = Number(pedido.saldo_pendiente ?? Math.max(0, total - abono));

      if (detWebSubtotal) detWebSubtotal.textContent = `$${subtotal.toFixed(2)}`;
      if (detWebPropina) detWebPropina.textContent = `$${propina.toFixed(2)}`;
      if (detWebTotal) detWebTotal.textContent = `$${total.toFixed(2)}`;
      if (detWebAbono) detWebAbono.textContent = `$${abono.toFixed(2)}`;
      if (detWebSaldo) detWebSaldo.textContent = `$${saldo.toFixed(2)}`;

      // Visibilidad de acciones operativas según RF17
      const estLower = String(pedido.estado_pedido || '').toLowerCase().trim();
      const cerrado = ['pagado', 'entregado', 'cancelado', 'finalizado'].includes(estLower);

      if (tipoOrd === 'Retiro en el local' && !tieneMesa && !cerrado) {
        if (btnAbrirAsignarMesaWeb) btnAbrirAsignarMesaWeb.style.display = 'flex';
        if (bloqueAccionesMesaAsignada) bloqueAccionesMesaAsignada.style.display = 'none';
      } else if (tieneMesa && !cerrado) {
        if (btnAbrirAsignarMesaWeb) btnAbrirAsignarMesaWeb.style.display = 'none';
        if (bloqueAccionesMesaAsignada) bloqueAccionesMesaAsignada.style.display = 'flex';
        if (btnIrAgregarProductosWeb) {
          btnIrAgregarProductosWeb.href = `gestion_orden.php?mesa_id=${encodeURIComponent(pedido.id_mesa)}`;
        }
        if (btnIrCierreCuentaWeb) {
          btnIrCierreCuentaWeb.href = `cierre_cuenta.php?mesa_id=${encodeURIComponent(pedido.id_mesa)}`;
        }
      } else {
        if (btnAbrirAsignarMesaWeb) btnAbrirAsignarMesaWeb.style.display = 'none';
        if (bloqueAccionesMesaAsignada) bloqueAccionesMesaAsignada.style.display = 'none';
      }

      if (abrirDrawerEnMovil) {
        abrirDrawerPedidoWeb();
      }
    }

    async function refrescarPedidosWebDesdeServidor(conservarSeleccion = true) {
      try {
        const res = await fetch('../../controllers/ServicioController.php?action=obtener_pedidos_web', {
          method: 'GET',
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (!res.ok || !data || data.success !== true) {
          throw new Error((data && data.error) ? data.error : 'No fue posible recargar los pedidos web.');
        }

        pedidosWebCache = Array.isArray(data.pedidos) ? data.pedidos : [];
        renderizarTablaPedidosWeb();

        if (conservarSeleccion && idPedidoWebActivo !== null) {
          const siguePresente = pedidosWebCache.some(p => Number(p.id_pedido) === idPedidoWebActivo);
          if (siguePresente) {
            mostrarDetallePedidoWeb(idPedidoWebActivo, false);
          }
        }
      } catch (err) {
        console.error('Error al actualizar bandeja de pedidos web:', err);
      }
    }

    // Delegación de clic en las filas `.web-order-row`
    tbodyPedidosWeb.addEventListener('click', (e) => {
      const fila = e.target.closest('.web-order-row');
      if (!fila) return;
      const idPed = parseInt(fila.dataset.idPedido || '0', 10);
      if (idPed > 0) {
        mostrarDetallePedidoWeb(idPed, true);
      }
    });

    // Filtros píldora por modalidad
    filterPillsWeb.forEach(pill => {
      pill.addEventListener('click', () => {
        filterPillsWeb.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        filtroModalidadWeb = pill.dataset.filterTipo || 'Todos';
        renderizarTablaPedidosWeb();
      });
    });

    // Búsqueda rápida por folio o cliente
    if (inputBuscarPedidoWeb) {
      inputBuscarPedidoWeb.addEventListener('input', () => {
        terminoBusquedaWeb = inputBuscarPedidoWeb.value || '';
        renderizarTablaPedidosWeb();
      });
    }

    if (btnRefrescarPedidosWeb) {
      btnRefrescarPedidosWeb.addEventListener('click', () => {
        refrescarPedidosWebDesdeServidor(true);
      });
    }

    // Actualización de estado del pedido web (`cambiar_estado_pedido` - RF08, RF16)
    if (btnActualizarEstadoWeb && selectEstadoPedidoWeb) {
      btnActualizarEstadoWeb.addEventListener('click', async () => {
        if (!idPedidoWebActivo) return;
        const nuevoEstado = selectEstadoPedidoWeb.value;
        const txtOrig = btnActualizarEstadoWeb.textContent;
        btnActualizarEstadoWeb.disabled = true;
        btnActualizarEstadoWeb.textContent = 'Guardando...';

        try {
          const res = await fetch('../../controllers/ServicioController.php?action=cambiar_estado_pedido', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              id_pedido: idPedidoWebActivo,
              estado_pedido: nuevoEstado
            })
          });
          const data = await res.json();
          if (!res.ok || !data || data.success !== true) {
            throw new Error((data && data.error) ? data.error : 'No fue posible actualizar el estado del pedido.');
          }

          await refrescarPedidosWebDesdeServidor(true);
          btnActualizarEstadoWeb.textContent = '¡Actualizado!';
          setTimeout(() => {
            btnActualizarEstadoWeb.textContent = txtOrig;
            btnActualizarEstadoWeb.disabled = false;
          }, 1000);
        } catch (err) {
          alert('Error al cambiar el estado del pedido: ' + (err.message || 'Fallo de conexión.'));
          btnActualizarEstadoWeb.textContent = txtOrig;
          btnActualizarEstadoWeb.disabled = false;
        }
      });
    }

    // Apertura y cierre del Modal RF17 (`#modalAsignarMesaWeb`)
    function cerrarModalAsignarMesaWeb() {
      if (!modalAsignarMesaWeb) return;
      modalAsignarMesaWeb.classList.remove('open');
      modalAsignarMesaWeb.setAttribute('aria-hidden', 'true');
      const drawerAbierto = window.innerWidth <= 1024 && panelSidebar && (panelSidebar.classList.contains('open') || panelSidebar.classList.contains('active'));
      document.body.style.overflow = drawerAbierto ? 'hidden' : '';
    }

    if (btnCancelarAsignarMesaWeb) {
      btnCancelarAsignarMesaWeb.addEventListener('click', cerrarModalAsignarMesaWeb);
    }

    if (modalAsignarMesaWeb) {
      modalAsignarMesaWeb.addEventListener('click', (e) => {
        if (e.target === modalAsignarMesaWeb) {
          cerrarModalAsignarMesaWeb();
        }
      });
    }

    if (btnAbrirAsignarMesaWeb && modalAsignarMesaWeb) {
      btnAbrirAsignarMesaWeb.addEventListener('click', async () => {
        const pedido = pedidosWebCache.find(p => Number(p.id_pedido) === idPedidoWebActivo);
        if (!pedido) return;

        const folio = formatearFolioWeb(pedido.id_pedido);
        const clienteNombre = `${pedido.nombre_cliente || ''} ${pedido.apellido_cliente || ''}`.trim() || 'Cliente Web';

        if (modalAsignarFolio) modalAsignarFolio.textContent = folio;
        if (modalAsignarCliente) modalAsignarCliente.textContent = clienteNombre;
        if (inputIdPedidoAsignarWeb) inputIdPedidoAsignarWeb.value = String(pedido.id_pedido);
        if (errorAsignarMesaWeb) {
          errorAsignarMesaWeb.style.display = 'none';
          errorAsignarMesaWeb.textContent = '';
        }
        if (alertaSinMesasDisponibles) alertaSinMesasDisponibles.style.display = 'none';

        modalAsignarMesaWeb.classList.add('open');
        modalAsignarMesaWeb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        if (selectMesaDisponibleWeb) {
          selectMesaDisponibleWeb.innerHTML = `<option value="">Consultando disponibilidad en salón...</option>`;
          selectMesaDisponibleWeb.disabled = true;
        }
        if (btnConfirmarAsignarMesaWeb) {
          btnConfirmarAsignarMesaWeb.disabled = true;
        }

        // Consultar en vivo `ApiController.php?action=obtener_mesas_disponibles` (RF17)
        try {
          const res = await fetch('../../controllers/ApiController.php?action=obtener_mesas_disponibles', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
          });
          const data = await res.json();
          if (!res.ok || !data || data.success !== true) {
            throw new Error((data && data.error) ? data.error : 'No fue posible consultar las mesas disponibles.');
          }

          const mesasDisp = Array.isArray(data.mesas) ? data.mesas : [];
          if (mesasDisp.length === 0) {
            // Camino Alternativo de RF17
            if (alertaSinMesasDisponibles) {
              alertaSinMesasDisponibles.textContent = 'No hay mesas disponibles en este momento. Debe esperar a que se libere una mesa.';
              alertaSinMesasDisponibles.style.display = 'block';
            }
            if (selectMesaDisponibleWeb) {
              selectMesaDisponibleWeb.innerHTML = `<option value="">Sin mesas disponibles</option>`;
              selectMesaDisponibleWeb.disabled = true;
            }
            if (btnConfirmarAsignarMesaWeb) {
              btnConfirmarAsignarMesaWeb.disabled = true;
            }
          } else {
            if (alertaSinMesasDisponibles) alertaSinMesasDisponibles.style.display = 'none';
            if (selectMesaDisponibleWeb) {
              selectMesaDisponibleWeb.innerHTML = mesasDisp.map(m =>
                `<option value="${Number(m.id_mesa)}">Mesa ${Number(m.numero_mesa)} — Capacidad: ${Number(m.capacidad || 4)} personas (Disponible)</option>`
              ).join('');
              selectMesaDisponibleWeb.disabled = false;
            }
            if (btnConfirmarAsignarMesaWeb) {
              btnConfirmarAsignarMesaWeb.disabled = false;
            }
          }
        } catch (err) {
          if (errorAsignarMesaWeb) {
            errorAsignarMesaWeb.textContent = err.message || 'Error al consultar las mesas disponibles.';
            errorAsignarMesaWeb.style.display = 'block';
          }
        }
      });
    }

    // Envío del formulario de asignación de mesa (`ServicioController.php?action=asignar_mesa_web` - RF17)
    if (formAsignarMesaWeb) {
      formAsignarMesaWeb.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idPedido = parseInt(inputIdPedidoAsignarWeb?.value || '0', 10);
        const idMesa = parseInt(selectMesaDisponibleWeb?.value || '0', 10);

        if (!idPedido || !idMesa) {
          if (errorAsignarMesaWeb) {
            errorAsignarMesaWeb.textContent = 'Por favor selecciona una mesa disponible válida.';
            errorAsignarMesaWeb.style.display = 'block';
          }
          return;
        }

        if (errorAsignarMesaWeb) errorAsignarMesaWeb.style.display = 'none';
        const txtOrig = btnConfirmarAsignarMesaWeb ? btnConfirmarAsignarMesaWeb.textContent : 'Confirmar Asignación';
        if (btnConfirmarAsignarMesaWeb) {
          btnConfirmarAsignarMesaWeb.disabled = true;
          btnConfirmarAsignarMesaWeb.textContent = 'Asignando mesa...';
        }

        try {
          const res = await fetch('../../controllers/ServicioController.php?action=asignar_mesa_web', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              id_pedido: idPedido,
              id_mesa: idMesa
            })
          });
          const data = await res.json();
          if (!res.ok || !data || data.success !== true) {
            throw new Error((data && data.error) ? data.error : 'No fue posible asignar la mesa al pedido web.');
          }

          cerrarModalAsignarMesaWeb();
          await refrescarPedidosWebDesdeServidor(true);
        } catch (err) {
          if (errorAsignarMesaWeb) {
            errorAsignarMesaWeb.textContent = err.message || 'Error al procesar la asignación de mesa.';
            errorAsignarMesaWeb.style.display = 'block';
          }
        } finally {
          if (btnConfirmarAsignarMesaWeb) {
            btnConfirmarAsignarMesaWeb.disabled = false;
            btnConfirmarAsignarMesaWeb.textContent = txtOrig;
          }
        }
      });
    }

    // Selección automática de la primera orden disponible al abrir la bandeja (sin abrir el drawer automáticamente en móvil)
    if (pedidosWebCache.length > 0) {
      mostrarDetallePedidoWeb(pedidosWebCache[0].id_pedido, false);
    }
  }

  // Conexión de botones de "Cerrar Sesión" hacia AuthController.php?action=logout (RNF04)
  const botonesCerrarSesion = document.querySelectorAll('#btnCerrarSesion, .btn-logout');
  botonesCerrarSesion.forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      sessionStorage.clear();
      try {
        const res = await fetch('/NIXGARU/controllers/AuthController.php?action=logout', {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json().catch(() => null);
        window.location.href = (data && data.redirect) ? data.redirect : '/NIXGARU/index.php';
      } catch (err) {
        window.location.href = '/NIXGARU/index.php';
      }
    });
  });

  /**
   * ==========================================================================
   * MÓDULO ADMINISTRATIVO CONECTADO A AdminController.php (Paso 3.3)
   * Cumple con RF04, RF05, RF11, RF12, RF13, RF14, RNF04 y RNF06
   * ==========================================================================
   */
  function escaparHtmlAdmin(valor) {
    return String(valor ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatearFechaCortaAdmin(isoDate) {
    if (!isoDate || typeof isoDate !== 'string') return '';
    const partes = isoDate.trim().split('-');
    if (partes.length !== 3) return isoDate;
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }

  /**
   * ==========================================================================
   * A. PANTALLA: RESUMEN ANALÍTICO Y DEMANDA (admin_dashboard.php) — RF11, RF12
   * ==========================================================================
   */
  const btnDateFilter = document.getElementById('btnDateFilter');
  const labelRangoFechas = document.getElementById('labelRangoFechas');
  const dateFilterBar = document.getElementById('dateFilterBar');
  const inputFechaInicio = document.getElementById('inputFechaInicio');
  const inputFechaFin = document.getElementById('inputFechaFin');
  const btnConsultarFechas = document.getElementById('btnConsultarFechas');
  const btnVerTodoFechas = document.getElementById('btnVerTodoFechas');
  const kpiCantidadVentas = document.getElementById('kpiCantidadVentas');
  const kpiTotalVendido = document.getElementById('kpiTotalVendido');
  const tablaTopProductosBody = document.getElementById('tablaTopProductosBody');

  if (btnDateFilter && dateFilterBar) {
    btnDateFilter.addEventListener('click', () => {
      const estaOculto = dateFilterBar.style.display === 'none';
      dateFilterBar.style.display = estaOculto ? 'flex' : 'none';
      btnDateFilter.setAttribute('aria-expanded', estaOculto ? 'true' : 'false');
      if (estaOculto && inputFechaInicio) {
        inputFechaInicio.focus();
      }
    });
  }

  async function consultarVentasDashboard(fechaInicio = '', fechaFin = '') {
    const params = new URLSearchParams({ action: 'filtrar_ventas' });
    if (fechaInicio) params.set('fecha_inicio', fechaInicio);
    if (fechaFin) params.set('fecha_fin', fechaFin);

    if (btnConsultarFechas) {
      btnConsultarFechas.disabled = true;
    }

    try {
      const res = await fetch(`../../controllers/AdminController.php?${params.toString()}`, {
        method: 'GET',
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.error || 'No fue posible consultar las estadísticas del período seleccionado.');
      }

      const fIni = data.fecha_inicio || '';
      const fFin = data.fecha_fin || '';
      if (inputFechaInicio) inputFechaInicio.value = fIni;
      if (inputFechaFin) inputFechaFin.value = fFin;

      let textoRango = 'Histórico general';
      if (fIni && fFin) {
        textoRango = `${formatearFechaCortaAdmin(fIni)} – ${formatearFechaCortaAdmin(fFin)}`;
      } else if (fIni) {
        textoRango = `Desde ${formatearFechaCortaAdmin(fIni)}`;
      } else if (fFin) {
        textoRango = `Hasta ${formatearFechaCortaAdmin(fFin)}`;
      }

      if (labelRangoFechas) {
        labelRangoFechas.textContent = textoRango;
      } else if (btnDateFilter) {
        const spanBtn = btnDateFilter.querySelector('span');
        if (spanBtn) spanBtn.textContent = textoRango;
      }

      const stats = data.estadisticas || data.kpis || {};
      const cantidadVentas = Number(stats.cantidad_ventas || 0);
      const totalVendido = Number(stats.total_vendido || 0);

      if (kpiCantidadVentas) {
        kpiCantidadVentas.textContent = cantidadVentas.toLocaleString('en-US');
      }
      if (kpiTotalVendido) {
        kpiTotalVendido.textContent = `$${totalVendido.toFixed(2)} USD`;
      }

      if (tablaTopProductosBody) {
        const topProductos = Array.isArray(data.productos_mas_vendidos)
          ? data.productos_mas_vendidos
          : (Array.isArray(data.top_productos) ? data.top_productos : []);

        if (topProductos.length === 0) {
          tablaTopProductosBody.innerHTML = `
            <tr>
              <td colspan="3" style="text-align: center; color: #6C757D; padding: 24px;">No se encontraron ventas registradas para el período consultado.</td>
            </tr>
          `;
        } else {
          tablaTopProductosBody.innerHTML = topProductos.map((prod, idx) => {
            const posicion = idx + 1;
            let badgeClass = 'rank-plain';
            if (posicion === 1) badgeClass = 'rank-badge rank-gold';
            else if (posicion === 2) badgeClass = 'rank-badge rank-silver';
            else if (posicion === 3) badgeClass = 'rank-badge rank-bronze';

            const nombre = escaparHtmlAdmin(prod.nombre_producto || 'Producto');
            const precio = Number(prod.precio || 0).toFixed(2);
            const cantidad = parseInt(prod.total_cantidad ?? prod.cantidad_vendida ?? 0, 10) || 0;

            return `
              <tr>
                <td class="cell-rank">
                  <span class="${badgeClass}">${posicion}</span>
                </td>
                <td class="cell-product">
                  <div class="rank-product-info">
                    <span class="rank-product-name">${nombre}</span>
                    <span class="rank-product-price">$${precio} USD</span>
                  </div>
                </td>
                <td class="cell-qty">
                  <span class="rank-qty-val">${cantidad} uds.</span>
                </td>
              </tr>
            `;
          }).join('');
        }
      }
    } catch (err) {
      alert(err.message || 'Error de conexión al consultar el historial de ventas.');
    } finally {
      if (btnConsultarFechas) {
        btnConsultarFechas.disabled = false;
      }
    }
  }

  if (dateFilterBar) {
    dateFilterBar.addEventListener('submit', (e) => {
      e.preventDefault();
      const fIni = inputFechaInicio ? inputFechaInicio.value.trim() : '';
      const fFin = inputFechaFin ? inputFechaFin.value.trim() : '';
      consultarVentasDashboard(fIni, fFin);
    });
  }

  if (btnVerTodoFechas) {
    btnVerTodoFechas.addEventListener('click', (e) => {
      e.preventDefault();
      if (inputFechaInicio) inputFechaInicio.value = '';
      if (inputFechaFin) inputFechaFin.value = '';
      consultarVentasDashboard('', '');
    });
  }

  /**
   * ==========================================================================
   * B. PANTALLA: GESTIÓN DE CATÁLOGO Y MENÚ (admin_catalogo.php) — RF04
   * ==========================================================================
   */
  const catalogFilterPills = document.querySelectorAll('.catalog-filter-pill');
  const tablaCatalogoBody = document.getElementById('tablaCatalogoBody');
  const catalogCountBadge = document.getElementById('catalogCountBadge');
  const catalogFooterCountText = document.getElementById('catalogFooterCountText');
  const btnAgregarProductoCatalogo = document.getElementById('btnAgregarProductoCatalogo');
  const modalProductoCatalogo = document.getElementById('modalProductoCatalogo');
  const tituloModalProductoCatalogo = document.getElementById('tituloModalProductoCatalogo');
  const errorModalProductoCatalogo = document.getElementById('errorModalProductoCatalogo');
  const formProductoCatalogo = document.getElementById('formProductoCatalogo');
  const inputIdProductoCatalogo = document.getElementById('inputIdProductoCatalogo');
  const inputNombreProductoCatalogo = document.getElementById('inputNombreProductoCatalogo');
  const selectCategoriaProductoCatalogo = document.getElementById('selectCategoriaProductoCatalogo');
  const inputPrecioProductoCatalogo = document.getElementById('inputPrecioProductoCatalogo');
  const inputDescripcionProductoCatalogo = document.getElementById('inputDescripcionProductoCatalogo');
  const inputArchivoFotoCatalogo = document.getElementById('inputArchivoFotoCatalogo');
  const inputNombreFotoCatalogo = document.getElementById('inputNombreFotoCatalogo');
  const selectDisponibilidadProductoCatalogo = document.getElementById('selectDisponibilidadProductoCatalogo');
  const btnCancelarModalProducto = document.getElementById('btnCancelarModalProducto');
  const btnGuardarProductoCatalogo = document.getElementById('btnGuardarProductoCatalogo');

  function obtenerSlugCategoriaCatalogo(idCategoria) {
    switch (Number(idCategoria)) {
      case 1: return 'calientes';
      case 2: return 'frias';
      case 3: return 'postres';
      case 4: return 'platos';
      default: return 'otros';
    }
  }

  function sincronizarFiltroYContadorCatalogo() {
    if (!tablaCatalogoBody) return;
    const pillActiva = document.querySelector('.catalog-filter-pill.active');
    const filterCat = (pillActiva?.dataset.category || 'todos').toLowerCase();
    const filas = tablaCatalogoBody.querySelectorAll('tr');
    let visibles = 0;

    filas.forEach((fila) => {
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
    if (catalogFooterCountText) {
      catalogFooterCountText.textContent = `Mostrando ${visibles} de ${filas.length} productos registrados en el menú actual`;
    }
  }

  function construirHtmlFilaProductoCatalogo(producto) {
    const idProd = Number(producto.id_producto || 0);
    const idCat = Number(producto.id_categoria || 1);
    const catSlug = obtenerSlugCategoriaCatalogo(idCat);
    const nombreRaw = String(producto.nombre_producto || '');
    const descRaw = String(producto.descripcion || '');
    const precioNum = Number(producto.precio || 0);
    const precioStr = precioNum.toFixed(2);
    const dispRaw = String(producto.estado_disponibilidad || 'Disponible');
    const fotoRaw = String(producto.fotografia || '');
    const activo = dispRaw.trim().toLowerCase() === 'disponible';

    const nombreEsc = escaparHtmlAdmin(nombreRaw);
    const descEsc = escaparHtmlAdmin(descRaw);
    const dispEsc = escaparHtmlAdmin(dispRaw);
    const fotoEsc = escaparHtmlAdmin(fotoRaw);

    const dataAttrs = `
      data-id="${idProd}"
      data-nombre="${nombreEsc}"
      data-descripcion="${descEsc}"
      data-precio="${precioStr}"
      data-id-categoria="${idCat}"
      data-disponibilidad="${dispEsc}"
      data-fotografia="${fotoEsc}"
    `;

    const svgPlaceholder = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <circle cx="8.5" cy="8.5" r="1.5"></circle>
        <polyline points="21 15 16 10 5 21"></polyline>
      </svg>
    `;

    const miniaturaHtml = fotoRaw !== ''
      ? `<img src="../../public/img/${fotoEsc}" alt="${nombreEsc}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 9px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"><svg style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`
      : svgPlaceholder;

    const tr = document.createElement('tr');
    tr.dataset.category = catSlug;
    tr.dataset.id = String(idProd);
    tr.dataset.nombre = nombreRaw;
    tr.dataset.descripcion = descRaw;
    tr.dataset.precio = precioStr;
    tr.dataset.idCategoria = String(idCat);
    tr.dataset.disponibilidad = dispRaw;
    tr.dataset.fotografia = fotoRaw;

    tr.innerHTML = `
      <td class="cell-cat-photo">
        <div class="product-thumb-placeholder" title="Fotografía de ${nombreEsc}">
          ${miniaturaHtml}
        </div>
      </td>
      <td class="cell-name">${nombreEsc}</td>
      <td class="cell-cat-desc">${descRaw !== '' ? descEsc : 'Sin descripción'}</td>
      <td class="cell-cat-price">
        <span class="catalog-price">$${precioStr}</span>
      </td>
      <td class="cell-cat-status">
        <div class="toggle-switch-wrapper ${activo ? 'active' : ''}" data-product="${nombreEsc}" data-id="${idProd}" data-codigo="${idProd}">
          <button type="button" class="toggle-switch" aria-label="Cambiar disponibilidad" aria-checked="${activo ? 'true' : 'false'}">
            <span class="toggle-knob"></span>
          </button>
          <span class="toggle-status-label">${dispEsc}</span>
        </div>
      </td>
      <td class="cell-acciones">
        <button class="btn-action-edit" title="Editar producto" type="button" ${dataAttrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
        </button>
        <button class="btn-action-delete" title="Eliminar producto" type="button" ${dataAttrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
      </td>
    `;
    return tr;
  }

  function mostrarErrorModalCatalogo(mensaje) {
    if (!errorModalProductoCatalogo) {
      alert(mensaje);
      return;
    }
    errorModalProductoCatalogo.textContent = mensaje;
    errorModalProductoCatalogo.style.display = 'block';
  }

  function abrirModalProductoCatalogo(datos = null) {
    if (!modalProductoCatalogo) return;
    if (errorModalProductoCatalogo) {
      errorModalProductoCatalogo.style.display = 'none';
      errorModalProductoCatalogo.textContent = '';
    }
    if (formProductoCatalogo) {
      formProductoCatalogo.reset();
    }
    if (inputArchivoFotoCatalogo) {
      inputArchivoFotoCatalogo.value = '';
    }

    if (datos && datos.id) {
      if (tituloModalProductoCatalogo) tituloModalProductoCatalogo.textContent = 'Editar Producto';
      if (inputIdProductoCatalogo) inputIdProductoCatalogo.value = String(datos.id);
      if (inputNombreProductoCatalogo) inputNombreProductoCatalogo.value = datos.nombre || '';
      if (selectCategoriaProductoCatalogo && datos.idCategoria) {
        selectCategoriaProductoCatalogo.value = String(datos.idCategoria);
      }
      if (inputPrecioProductoCatalogo) {
        inputPrecioProductoCatalogo.value = datos.precio ? Number(datos.precio).toFixed(2) : '';
      }
      if (inputDescripcionProductoCatalogo) {
        inputDescripcionProductoCatalogo.value = datos.descripcion || '';
      }
      if (inputNombreFotoCatalogo) {
        inputNombreFotoCatalogo.value = datos.fotografia || '';
      }
      if (selectDisponibilidadProductoCatalogo) {
        selectDisponibilidadProductoCatalogo.value =
          (String(datos.disponibilidad || '').trim().toLowerCase() === 'inactivo') ? 'Inactivo' : 'Disponible';
      }
    } else {
      if (tituloModalProductoCatalogo) tituloModalProductoCatalogo.textContent = 'Agregar Nuevo Producto';
      if (inputIdProductoCatalogo) inputIdProductoCatalogo.value = '';
      if (selectDisponibilidadProductoCatalogo) selectDisponibilidadProductoCatalogo.value = 'Disponible';
    }

    modalProductoCatalogo.classList.add('show');
    modalProductoCatalogo.style.display = 'flex';
    modalProductoCatalogo.setAttribute('aria-hidden', 'false');
    if (inputNombreProductoCatalogo) {
      setTimeout(() => inputNombreProductoCatalogo.focus(), 50);
    }
  }

  function cerrarModalProductoCatalogo() {
    if (!modalProductoCatalogo) return;
    modalProductoCatalogo.classList.remove('show');
    modalProductoCatalogo.style.display = 'none';
    modalProductoCatalogo.setAttribute('aria-hidden', 'true');
    if (errorModalProductoCatalogo) {
      errorModalProductoCatalogo.style.display = 'none';
      errorModalProductoCatalogo.textContent = '';
    }
    if (formProductoCatalogo) {
      formProductoCatalogo.reset();
    }
  }

  catalogFilterPills.forEach((pill) => {
    pill.addEventListener('click', () => {
      catalogFilterPills.forEach((p) => p.classList.remove('active'));
      pill.classList.add('active');
      sincronizarFiltroYContadorCatalogo();
    });
  });

  if (btnAgregarProductoCatalogo) {
    btnAgregarProductoCatalogo.addEventListener('click', (e) => {
      e.preventDefault();
      abrirModalProductoCatalogo(null);
    });
  }

  if (btnCancelarModalProducto) {
    btnCancelarModalProducto.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarModalProductoCatalogo();
    });
  }

  if (modalProductoCatalogo) {
    modalProductoCatalogo.addEventListener('click', (e) => {
      if (e.target === modalProductoCatalogo) {
        cerrarModalProductoCatalogo();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalProductoCatalogo && (modalProductoCatalogo.classList.contains('show') || modalProductoCatalogo.style.display === 'flex')) {
      cerrarModalProductoCatalogo();
    }
  });

  if (formProductoCatalogo) {
    formProductoCatalogo.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (errorModalProductoCatalogo) {
        errorModalProductoCatalogo.style.display = 'none';
      }

      const idProd = inputIdProductoCatalogo ? inputIdProductoCatalogo.value.trim() : '';
      const accion = idProd ? 'actualizar_producto' : 'guardar_producto';
      const formData = new FormData(formProductoCatalogo);

      if (btnGuardarProductoCatalogo) {
        btnGuardarProductoCatalogo.disabled = true;
      }

      try {
        const res = await fetch(`../../controllers/AdminController.php?action=${accion}`, {
          method: 'POST',
          body: formData,
          headers: { Accept: 'application/json' },
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          mostrarErrorModalCatalogo(data.error || 'No fue posible guardar los datos del producto.');
          return;
        }

        if (tablaCatalogoBody && data.producto) {
          const nuevaFila = construirHtmlFilaProductoCatalogo(data.producto);
          const filaExistente = tablaCatalogoBody.querySelector(`tr[data-id="${data.producto.id_producto}"]`);
          if (filaExistente) {
            filaExistente.replaceWith(nuevaFila);
          } else {
            tablaCatalogoBody.appendChild(nuevaFila);
          }
          sincronizarFiltroYContadorCatalogo();
        } else {
          window.location.reload();
          return;
        }

        cerrarModalProductoCatalogo();
      } catch (err) {
        mostrarErrorModalCatalogo(err.message || 'Error de red al guardar el producto.');
      } finally {
        if (btnGuardarProductoCatalogo) {
          btnGuardarProductoCatalogo.disabled = false;
        }
      }
    });
  }

  if (tablaCatalogoBody) {
    tablaCatalogoBody.addEventListener('click', async (e) => {
      // 1. Interruptor de Disponibilidad (toggle_disponibilidad)
      const toggleWrapper = e.target.closest('.toggle-switch-wrapper');
      if (toggleWrapper) {
        e.preventDefault();
        if (toggleWrapper.dataset.busy === '1') return;

        const fila = toggleWrapper.closest('tr');
        const idProducto = Number(toggleWrapper.dataset.id || fila?.dataset.id || 0);
        if (idProducto <= 0) return;

        const switchBtn = toggleWrapper.querySelector('.toggle-switch');
        const label = toggleWrapper.querySelector('.toggle-status-label');
        const previoActivo = toggleWrapper.classList.contains('active');
        const textoPrevio = label ? label.textContent : (previoActivo ? 'Disponible' : 'Inactivo');
        const nuevoActivo = !previoActivo;
        const nuevoEstado = nuevoActivo ? 'Disponible' : 'Inactivo';

        toggleWrapper.dataset.busy = '1';
        toggleWrapper.classList.toggle('active', nuevoActivo);
        if (switchBtn) switchBtn.setAttribute('aria-checked', nuevoActivo ? 'true' : 'false');
        if (label) label.textContent = nuevoEstado;

        try {
          const res = await fetch('../../controllers/AdminController.php?action=toggle_disponibilidad', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
            body: JSON.stringify({
              id_producto: idProducto,
              estado: nuevoEstado,
            }),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            throw new Error(data.error || 'Error al actualizar la disponibilidad del producto.');
          }

          const estadoConfirmado = data.estado_disponibilidad || nuevoEstado;
          const esDisp = String(estadoConfirmado).trim().toLowerCase() === 'disponible';
          toggleWrapper.classList.toggle('active', esDisp);
          if (switchBtn) switchBtn.setAttribute('aria-checked', esDisp ? 'true' : 'false');
          if (label) label.textContent = estadoConfirmado;

          if (fila) {
            fila.dataset.disponibilidad = estadoConfirmado;
            fila.querySelectorAll('.btn-action-edit, .btn-action-delete').forEach((btn) => {
              btn.dataset.disponibilidad = estadoConfirmado;
            });
          }
        } catch (err) {
          // Revertir estado visual en caso de fallo
          toggleWrapper.classList.toggle('active', previoActivo);
          if (switchBtn) switchBtn.setAttribute('aria-checked', previoActivo ? 'true' : 'false');
          if (label) label.textContent = textoPrevio;
          alert(err.message || 'No se pudo cambiar la disponibilidad del producto.');
        } finally {
          delete toggleWrapper.dataset.busy;
        }
        return;
      }

      // 2. Botón Editar Producto (.btn-action-edit)
      const btnEdit = e.target.closest('.btn-action-edit');
      if (btnEdit) {
        e.preventDefault();
        const fila = btnEdit.closest('tr');
        abrirModalProductoCatalogo({
          id: btnEdit.dataset.id || fila?.dataset.id || '',
          nombre: btnEdit.dataset.nombre || fila?.dataset.nombre || '',
          descripcion: btnEdit.dataset.descripcion || fila?.dataset.descripcion || '',
          precio: btnEdit.dataset.precio || fila?.dataset.precio || '',
          idCategoria: btnEdit.dataset.idCategoria || fila?.dataset.idCategoria || '1',
          disponibilidad: btnEdit.dataset.disponibilidad || fila?.dataset.disponibilidad || 'Disponible',
          fotografia: btnEdit.dataset.fotografia || fila?.dataset.fotografia || '',
        });
        return;
      }

      // 3. Botón Eliminar Producto (.btn-action-delete -> eliminar_producto)
      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        e.preventDefault();
        const fila = btnDelete.closest('tr');
        const idProducto = Number(btnDelete.dataset.id || fila?.dataset.id || 0);
        const nombreProd = btnDelete.dataset.nombre || fila?.dataset.nombre || 'este producto';
        if (idProducto <= 0) return;

        if (!window.confirm(`¿Desea eliminar el producto "${nombreProd}" del catálogo?`)) {
          return;
        }

        btnDelete.disabled = true;
        try {
          const filasAntes = tablaCatalogoBody.querySelectorAll('tr').length;
          const res = await fetch('../../controllers/AdminController.php?action=eliminar_producto', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
            body: JSON.stringify({ id_producto: idProducto }),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            throw new Error(data.error || 'No fue posible eliminar el producto seleccionado.');
          }

          if (typeof data.total_productos === 'number' && data.total_productos === filasAntes && fila) {
            // El producto tenía historial en detalle_pedidos y fue desactivado de forma segura
            const toggle = fila.querySelector('.toggle-switch-wrapper');
            const switchBtn = fila.querySelector('.toggle-switch');
            const label = fila.querySelector('.toggle-status-label');
            if (toggle) toggle.classList.remove('active');
            if (switchBtn) switchBtn.setAttribute('aria-checked', 'false');
            if (label) label.textContent = 'Inactivo';
            fila.dataset.disponibilidad = 'Inactivo';
            fila.querySelectorAll('.btn-action-edit, .btn-action-delete').forEach((btn) => {
              btn.dataset.disponibilidad = 'Inactivo';
            });
            sincronizarFiltroYContadorCatalogo();
            alert('El producto tiene ventas históricas asociadas, por lo que se desactivó ("Inactivo") para conservar la trazabilidad de reportes.');
          } else if (fila) {
            fila.remove();
            sincronizarFiltroYContadorCatalogo();
          }
        } catch (err) {
          alert(err.message || 'Error al eliminar el producto.');
        } finally {
          btnDelete.disabled = false;
        }
      }
    });
  }

  /**
   * ==========================================================================
   * C. PANTALLA: ADMINISTRACIÓN DE MESAS (admin_mesas.php) — RF05
   * ==========================================================================
   */
  const btnAbrirModalMesa = document.getElementById('btnAbrirModalMesa');
  const modalNuevaMesa = document.getElementById('modalNuevaMesa');
  const tituloModalMesa = document.getElementById('tituloModalMesa');
  const errorModalMesa = document.getElementById('errorModalMesa');
  const btnCancelarModal = document.getElementById('btnCancelarModal');
  const formNuevaMesa = document.getElementById('formNuevaMesa');
  const inputIdMesa = document.getElementById('inputIdMesa');
  const inputNumeroMesa = document.getElementById('inputNumeroMesa');
  const inputCapacidadMesa = document.getElementById('inputCapacidadMesa');
  const selectEstadoMesa = document.getElementById('selectEstadoMesa');
  const btnGuardarMesa = document.getElementById('btnGuardarMesa');
  const tablaMesasBody = document.getElementById('tablaMesasBody');
  const statTotalMesas = document.getElementById('statTotalMesas');
  const statCapacidadMaxima = document.getElementById('statCapacidadMaxima');
  const mesasCountBadge = document.getElementById('mesasCountBadge') || document.querySelector('.table-count-badge');
  const mesasFooterCountText = document.getElementById('mesasFooterCountText');

  function obtenerClaseEstadoMesaAdmin(estado) {
    const slug = String(estado || '').trim().toLowerCase();
    if (slug.includes('ocupad')) return 'status-ocupada';
    if (slug.includes('proces') || slug.includes('reserv')) return 'status-reservada';
    if (slug.includes('sin')) return 'status-inactivo';
    return 'status-libre';
  }

  function actualizarResumenMesasDOM(totalMesas, capacidadMaxima) {
    const total = typeof totalMesas === 'number'
      ? totalMesas
      : (tablaMesasBody ? tablaMesasBody.querySelectorAll('tr').length : 0);

    let capacidad = capacidadMaxima;
    if (typeof capacidad !== 'number' && tablaMesasBody) {
      capacidad = Array.from(tablaMesasBody.querySelectorAll('tr')).reduce(
        (acc, fila) => acc + (parseInt(fila.dataset.capacidad || '0', 10) || 0),
        0
      );
    }

    if (statTotalMesas) {
      statTotalMesas.textContent = `${total} Mesas`;
    }
    if (typeof capacidad === 'number' && statCapacidadMaxima) {
      statCapacidadMaxima.textContent = `${capacidad} personas`;
    }
    if (mesasCountBadge && tablaMesasBody) {
      mesasCountBadge.textContent = `Total: ${total} mostradas`;
    }
    if (mesasFooterCountText) {
      mesasFooterCountText.textContent = `Mostrando ${total} de ${total} mesas activas registradas en el sistema`;
    }
  }

  function construirFilaMesaAdmin(mesa) {
    const idMesa = Number(mesa.id_mesa || 0);
    const numeroMesa = Number(mesa.numero_mesa || idMesa);
    const capacidad = Number(mesa.capacidad || 4);
    const estado = String(mesa.estado_mesa || 'Disponible');
    const estadoEsc = escaparHtmlAdmin(estado);
    const pillClass = obtenerClaseEstadoMesaAdmin(estado);
    const idFormateado = String(idMesa).padStart(2, '0');

    const attrs = `
      data-id="${idMesa}"
      data-numero="${numeroMesa}"
      data-capacidad="${capacidad}"
      data-estado="${estadoEsc}"
    `;

    const tr = document.createElement('tr');
    tr.dataset.id = String(idMesa);
    tr.dataset.numero = String(numeroMesa);
    tr.dataset.capacidad = String(capacidad);
    tr.dataset.estado = estado;

    tr.innerHTML = `
      <td class="cell-id">${idFormateado}</td>
      <td class="cell-name">Mesa ${escaparHtmlAdmin(numeroMesa)} <span style="font-size:12px; color:#6C757D; font-weight:normal;">(Cap. ${capacidad})</span></td>
      <td class="cell-estado">
        <span class="admin-status-pill ${pillClass}">${estadoEsc.toUpperCase()}</span>
      </td>
      <td class="cell-acciones">
        <button class="btn-action-edit" title="Editar mesa" type="button" ${attrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
        </button>
        <button class="btn-action-delete" title="Eliminar mesa" type="button" ${attrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
      </td>
    `;
    return tr;
  }

  function mostrarErrorModalMesa(mensaje) {
    if (!errorModalMesa) {
      alert(mensaje);
      return;
    }
    errorModalMesa.textContent = mensaje;
    errorModalMesa.style.display = 'block';
  }

  function abrirModalNuevaMesa(datos = null) {
    if (!modalNuevaMesa) return;
    if (errorModalMesa) {
      errorModalMesa.style.display = 'none';
      errorModalMesa.textContent = '';
    }
    if (formNuevaMesa) {
      formNuevaMesa.reset();
    }

    if (datos && datos.id) {
      if (tituloModalMesa) tituloModalMesa.textContent = 'Editar Mesa';
      if (inputIdMesa) inputIdMesa.value = String(datos.id);
      if (inputNumeroMesa) inputNumeroMesa.value = String(datos.numero || '');
      if (inputCapacidadMesa) inputCapacidadMesa.value = String(datos.capacidad || 4);
      if (selectEstadoMesa && datos.estado) {
        selectEstadoMesa.value = datos.estado;
      }
    } else {
      if (tituloModalMesa) tituloModalMesa.textContent = 'Registrar Nueva Mesa';
      if (inputIdMesa) inputIdMesa.value = '';
      if (inputCapacidadMesa) inputCapacidadMesa.value = '4';
      if (selectEstadoMesa) selectEstadoMesa.value = 'Disponible';
    }

    modalNuevaMesa.classList.add('show');
    modalNuevaMesa.style.display = 'flex';
    modalNuevaMesa.setAttribute('aria-hidden', 'false');
    if (inputNumeroMesa) {
      setTimeout(() => inputNumeroMesa.focus(), 50);
    }
  }

  function cerrarModalNuevaMesa() {
    if (!modalNuevaMesa) return;
    modalNuevaMesa.classList.remove('show');
    modalNuevaMesa.style.display = 'none';
    modalNuevaMesa.setAttribute('aria-hidden', 'true');
    if (errorModalMesa) {
      errorModalMesa.style.display = 'none';
      errorModalMesa.textContent = '';
    }
    if (formNuevaMesa) {
      formNuevaMesa.reset();
    }
  }

  if (btnAbrirModalMesa) {
    btnAbrirModalMesa.addEventListener('click', (e) => {
      e.preventDefault();
      abrirModalNuevaMesa(null);
    });
  }

  if (btnCancelarModal) {
    btnCancelarModal.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarModalNuevaMesa();
    });
  }

  if (modalNuevaMesa) {
    modalNuevaMesa.addEventListener('click', (e) => {
      if (e.target === modalNuevaMesa) {
        cerrarModalNuevaMesa();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalNuevaMesa && (modalNuevaMesa.classList.contains('show') || modalNuevaMesa.style.display === 'flex')) {
      cerrarModalNuevaMesa();
    }
  });

  if (formNuevaMesa) {
    formNuevaMesa.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (errorModalMesa) {
        errorModalMesa.style.display = 'none';
      }

      const idMesaVal = inputIdMesa ? inputIdMesa.value.trim() : '';
      const numeroVal = inputNumeroMesa ? inputNumeroMesa.value.trim() : '';
      const capacidadVal = inputCapacidadMesa ? parseInt(inputCapacidadMesa.value, 10) : 4;
      const estadoVal = selectEstadoMesa ? selectEstadoMesa.value : 'Disponible';

      if (!numeroVal || Number.isNaN(capacidadVal) || capacidadVal < 1) {
        mostrarErrorModalMesa('Ingrese un número de mesa y una capacidad válida (mínimo 1 persona).');
        return;
      }

      const accion = idMesaVal ? 'actualizar_mesa' : 'crear_mesa';
      const payload = {
        numero_mesa: numeroVal,
        capacidad: capacidadVal,
        estado_mesa: estadoVal,
      };
      if (idMesaVal) {
        payload.id_mesa = Number(idMesaVal);
      }

      if (btnGuardarMesa) btnGuardarMesa.disabled = true;

      try {
        const res = await fetch(`../../controllers/AdminController.php?action=${accion}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          // Camino Alternativo RF05: mostrar error de unicidad o validación sin cerrar el modal
          mostrarErrorModalMesa(data.error || 'No fue posible guardar la mesa.');
          return;
        }

        const mesaGuardada = data.mesa || {
          id_mesa: data.id_mesa,
          numero_mesa: data.numero_mesa ?? numeroVal,
          capacidad: data.capacidad ?? capacidadVal,
          estado_mesa: data.estado_mesa ?? estadoVal,
        };

        if (tablaMesasBody) {
          const nuevaFila = construirFilaMesaAdmin(mesaGuardada);
          const filaExistente = tablaMesasBody.querySelector(`tr[data-id="${mesaGuardada.id_mesa}"]`);
          if (filaExistente) {
            filaExistente.replaceWith(nuevaFila);
          } else {
            tablaMesasBody.appendChild(nuevaFila);
          }
        }

        actualizarResumenMesasDOM(data.total_mesas, data.capacidad_maxima);
        cerrarModalNuevaMesa();
      } catch (err) {
        mostrarErrorModalMesa(err.message || 'Error de conexión al guardar la mesa.');
      } finally {
        if (btnGuardarMesa) btnGuardarMesa.disabled = false;
      }
    });
  }

  if (tablaMesasBody) {
    tablaMesasBody.addEventListener('click', async (e) => {
      const btnEdit = e.target.closest('.btn-action-edit');
      if (btnEdit) {
        e.preventDefault();
        const fila = btnEdit.closest('tr');
        abrirModalNuevaMesa({
          id: btnEdit.dataset.id || fila?.dataset.id || '',
          numero: btnEdit.dataset.numero || fila?.dataset.numero || '',
          capacidad: btnEdit.dataset.capacidad || fila?.dataset.capacidad || '4',
          estado: btnEdit.dataset.estado || fila?.dataset.estado || 'Disponible',
        });
        return;
      }

      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        e.preventDefault();
        const fila = btnDelete.closest('tr');
        const idMesa = Number(btnDelete.dataset.id || fila?.dataset.id || 0);
        const numeroMesa = btnDelete.dataset.numero || fila?.dataset.numero || idMesa;
        if (idMesa <= 0) return;

        if (!window.confirm(`¿Está seguro de eliminar la Mesa ${numeroMesa} del salón?`)) {
          return;
        }

        btnDelete.disabled = true;
        try {
          const res = await fetch('../../controllers/AdminController.php?action=eliminar_mesa', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
            body: JSON.stringify({ id_mesa: idMesa }),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            throw new Error(data.error || 'No se pudo eliminar la mesa.');
          }

          if (fila) {
            fila.remove();
          }
          actualizarResumenMesasDOM(data.total_mesas, data.capacidad_maxima);
        } catch (err) {
          alert(err.message || 'Error al intentar eliminar la mesa.');
        } finally {
          btnDelete.disabled = false;
        }
      }
    });
  }

  /**
   * ==========================================================================
   * D. PANTALLA: CONTROL DE INVENTARIOS Y AJUSTES (admin_inventario.php) — RF14
   * ==========================================================================
   */
  const inputBuscarIngrediente = document.getElementById('inputBuscarIngrediente');
  const tablaInventarioBody = document.getElementById('tablaInventarioBody');
  const noResultadosInventario = document.getElementById('noResultadosInventario');

  if (inputBuscarIngrediente && tablaInventarioBody) {
    const filtrarTablaInventario = () => {
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

      if (noResultadosInventario) {
        noResultadosInventario.style.display = coincidencias === 0 ? 'block' : 'none';
      }
    };

    inputBuscarIngrediente.addEventListener('input', filtrarTablaInventario);
    inputBuscarIngrediente.addEventListener('keyup', filtrarTablaInventario);
  }

  if (tablaInventarioBody) {
    tablaInventarioBody.addEventListener('click', async (e) => {
      const btnGuardar = e.target.closest('.btn-adjust-save');
      if (!btnGuardar) return;

      e.preventDefault();
      const fila = btnGuardar.closest('tr');
      const idIngrediente = Number(btnGuardar.dataset.id || fila?.dataset.id || 0);
      const inputAjuste = fila ? fila.querySelector('.input-adjust') : null;
      const rawValor = inputAjuste ? inputAjuste.value.trim() : '';
      const valorAjuste = rawValor !== '' ? Number(rawValor) : NaN;

      // Camino Alternativo RF14: rechazar valores no numéricos o negativos
      if (idIngrediente <= 0 || Number.isNaN(valorAjuste) || valorAjuste < 0) {
        alert('Error: Ingrese una cantidad numérica válida mayor o igual a 0.00 para ajustar el inventario.');
        if (inputAjuste) inputAjuste.focus();
        return;
      }

      const textoOriginalBtn = btnGuardar.textContent;
      btnGuardar.disabled = true;
      btnGuardar.textContent = 'Guardando...';

      try {
        const res = await fetch('../../controllers/AdminController.php?action=ajustar_inventario', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({
            id_ingrediente: idIngrediente,
            cantidad_estimada: valorAjuste,
          }),
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          throw new Error(data.error || 'No fue posible actualizar la existencia del ingrediente.');
        }

        const nuevaCantidad = Number(data.cantidad_estimada ?? valorAjuste);
        const esBajo = nuevaCantidad <= 15.0;

        if (fila) {
          fila.classList.toggle('row-stock-alert', esBajo);

          const cellCode = fila.querySelector('.cell-code');
          if (cellCode) {
            cellCode.classList.toggle('text-danger', esBajo);
          }

          const cellQtyStrong = fila.querySelector('.cell-qty strong');
          if (cellQtyStrong) {
            cellQtyStrong.textContent = nuevaCantidad.toFixed(2);
            cellQtyStrong.className = esBajo ? 'qty-val-danger' : 'qty-val-normal';
          }

          const cellIngredient = fila.querySelector('.cell-ingredient');
          if (cellIngredient) {
            let badgeLow = cellIngredient.querySelector('.badge-stock-low');
            if (esBajo && !badgeLow) {
              badgeLow = document.createElement('span');
              badgeLow.className = 'badge-stock-low';
              badgeLow.textContent = 'STOCK BAJO';
              cellIngredient.appendChild(badgeLow);
            } else if (!esBajo && badgeLow) {
              badgeLow.remove();
            }
          }
        }

        if (inputAjuste) {
          inputAjuste.value = nuevaCantidad.toFixed(2);
        }

        btnGuardar.textContent = '¡Listo!';
        btnGuardar.style.backgroundColor = '#15803D';
        setTimeout(() => {
          btnGuardar.textContent = textoOriginalBtn || 'Guardar';
          btnGuardar.style.backgroundColor = '';
          btnGuardar.disabled = false;
        }, 850);
      } catch (err) {
        btnGuardar.textContent = textoOriginalBtn || 'Guardar';
        btnGuardar.style.backgroundColor = '';
        btnGuardar.disabled = false;
        alert(err.message || 'Error de conexión al ajustar el inventario.');
      }
    });
  }

  /**
   * ==========================================================================
   * E. PANTALLA: GESTIÓN DE CUENTAS DE PERSONAL (admin_personal.php) — RF13, RNF06
   * ==========================================================================
   */
  const btnAbrirModalEmpleado = document.getElementById('btnAbrirModalEmpleado');
  const modalNuevoEmpleado = document.getElementById('modalNuevoEmpleado');
  const tituloModalEmpleado = document.getElementById('tituloModalEmpleado');
  const subtituloModalEmpleado = document.getElementById('subtituloModalEmpleado');
  const errorModalEmpleado = document.getElementById('errorModalEmpleado');
  const btnCancelarModalEmpleado = document.getElementById('btnCancelarModalEmpleado');
  const formNuevoEmpleado = document.getElementById('formNuevoEmpleado');
  const inputIdEmpleado = document.getElementById('inputIdEmpleado');
  const inputNombreEmpleado = document.getElementById('inputNombreEmpleado');
  const inputUsuarioEmpleado = document.getElementById('inputUsuarioEmpleado');
  const inputPasswordEmpleado = document.getElementById('inputPasswordEmpleado');
  const ayudaPasswordEmpleado = document.getElementById('ayudaPasswordEmpleado');
  const selectRolEmpleado = document.getElementById('selectRolEmpleado');
  const btnGuardarEmpleado = document.getElementById('btnGuardarEmpleado');
  const tablaPersonalBody = document.getElementById('tablaPersonalBody');
  const statTotalPersonal = document.getElementById('statTotalPersonal');
  const personalCountBadge = document.getElementById('personalCountBadge');
  const personalFooterCountText = document.getElementById('personalFooterCountText');

  function actualizarContadoresPersonalDOM(totalUsuarios) {
    const total = typeof totalUsuarios === 'number'
      ? totalUsuarios
      : (tablaPersonalBody ? tablaPersonalBody.querySelectorAll('tr').length : 0);

    if (statTotalPersonal) {
      statTotalPersonal.textContent = `${total} Trabajadores`;
    }
    if (personalCountBadge) {
      personalCountBadge.textContent = `Total: ${total} mostrados`;
    }
    if (personalFooterCountText) {
      personalFooterCountText.textContent = `Mostrando ${total} de ${total} cuentas de personal activas`;
    }
  }

  function construirFilaPersonalAdmin(emp) {
    const idUsuario = Number(emp.id_usuario || 0);
    const nombreRaw = String(emp.nombre_completo || '');
    const usuarioRaw = String(emp.nombre_usuario || '');
    const rolRaw = String(emp.nombre_rol || 'Mesero');

    const nombreEsc = escaparHtmlAdmin(nombreRaw);
    const usuarioEsc = escaparHtmlAdmin(usuarioRaw);
    const rolEsc = escaparHtmlAdmin(rolRaw);

    const attrs = `
      data-id="${idUsuario}"
      data-nombre="${nombreEsc}"
      data-usuario="${usuarioEsc}"
      data-rol="${rolEsc}"
    `;

    const tr = document.createElement('tr');
    tr.dataset.id = String(idUsuario);
    tr.dataset.nombre = nombreRaw;
    tr.dataset.usuario = usuarioRaw;
    tr.dataset.rol = rolRaw;

    tr.innerHTML = `
      <td class="cell-id">${idUsuario}</td>
      <td class="cell-name">${nombreEsc}</td>
      <td class="cell-user">${usuarioEsc}</td>
      <td class="cell-role">
        <span class="role-pill">${rolEsc}</span>
      </td>
      <td class="cell-acciones">
        <button class="btn-action-edit" title="Editar empleado" type="button" ${attrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
        </button>
        <button class="btn-action-delete" title="Eliminar empleado" type="button" ${attrs}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          </svg>
        </button>
      </td>
    `;
    return tr;
  }

  function mostrarErrorModalEmpleado(mensaje) {
    if (!errorModalEmpleado) {
      alert(mensaje);
      return;
    }
    errorModalEmpleado.textContent = mensaje;
    errorModalEmpleado.style.display = 'block';
  }

  function abrirModalNuevoEmpleado(datos = null) {
    if (!modalNuevoEmpleado) return;
    if (errorModalEmpleado) {
      errorModalEmpleado.style.display = 'none';
      errorModalEmpleado.textContent = '';
    }
    if (formNuevoEmpleado) {
      formNuevoEmpleado.reset();
    }

    if (datos && datos.id) {
      if (tituloModalEmpleado) tituloModalEmpleado.textContent = 'Editar Cuenta de Personal';
      if (subtituloModalEmpleado) subtituloModalEmpleado.textContent = 'Modifica las credenciales o el rol asignado al trabajador.';
      if (inputIdEmpleado) inputIdEmpleado.value = String(datos.id);
      if (inputNombreEmpleado) inputNombreEmpleado.value = datos.nombre || '';
      if (inputUsuarioEmpleado) inputUsuarioEmpleado.value = datos.usuario || '';
      if (inputPasswordEmpleado) {
        inputPasswordEmpleado.value = '';
        inputPasswordEmpleado.required = false;
      }
      if (ayudaPasswordEmpleado) {
        ayudaPasswordEmpleado.style.display = 'block';
      }
      if (selectRolEmpleado && datos.rol) {
        const existeOpcion = Array.from(selectRolEmpleado.options).some((opt) => opt.value === datos.rol);
        if (!existeOpcion) {
          const nuevaOpt = document.createElement('option');
          nuevaOpt.value = datos.rol;
          nuevaOpt.textContent = datos.rol;
          selectRolEmpleado.appendChild(nuevaOpt);
        }
        selectRolEmpleado.value = datos.rol;
      }
    } else {
      if (tituloModalEmpleado) tituloModalEmpleado.textContent = 'Nueva Cuenta';
      if (subtituloModalEmpleado) subtituloModalEmpleado.textContent = 'Registra un nuevo empleado asignando credenciales de acceso.';
      if (inputIdEmpleado) inputIdEmpleado.value = '';
      if (inputPasswordEmpleado) {
        inputPasswordEmpleado.value = '';
        inputPasswordEmpleado.required = true;
      }
      if (ayudaPasswordEmpleado) {
        ayudaPasswordEmpleado.style.display = 'none';
      }
    }

    modalNuevoEmpleado.classList.add('show');
    modalNuevoEmpleado.style.display = 'flex';
    modalNuevoEmpleado.setAttribute('aria-hidden', 'false');
    if (inputNombreEmpleado) {
      setTimeout(() => inputNombreEmpleado.focus(), 50);
    }
  }

  function cerrarModalNuevoEmpleado() {
    if (!modalNuevoEmpleado) return;
    modalNuevoEmpleado.classList.remove('show');
    modalNuevoEmpleado.style.display = 'none';
    modalNuevoEmpleado.setAttribute('aria-hidden', 'true');
    if (errorModalEmpleado) {
      errorModalEmpleado.style.display = 'none';
      errorModalEmpleado.textContent = '';
    }
    if (formNuevoEmpleado) {
      formNuevoEmpleado.reset();
    }
  }

  if (btnAbrirModalEmpleado) {
    btnAbrirModalEmpleado.addEventListener('click', (e) => {
      e.preventDefault();
      abrirModalNuevoEmpleado(null);
    });
  }

  if (btnCancelarModalEmpleado) {
    btnCancelarModalEmpleado.addEventListener('click', (e) => {
      e.preventDefault();
      cerrarModalNuevoEmpleado();
    });
  }

  if (modalNuevoEmpleado) {
    modalNuevoEmpleado.addEventListener('click', (e) => {
      if (e.target === modalNuevoEmpleado) {
        cerrarModalNuevoEmpleado();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalNuevoEmpleado && (modalNuevoEmpleado.classList.contains('show') || modalNuevoEmpleado.style.display === 'flex')) {
      cerrarModalNuevoEmpleado();
    }
  });

  if (formNuevoEmpleado) {
    formNuevoEmpleado.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (errorModalEmpleado) {
        errorModalEmpleado.style.display = 'none';
      }

      const idUsuarioVal = inputIdEmpleado ? inputIdEmpleado.value.trim() : '';
      const nombre = inputNombreEmpleado ? inputNombreEmpleado.value.trim() : '';
      const usuario = inputUsuarioEmpleado ? inputUsuarioEmpleado.value.trim() : '';
      const password = inputPasswordEmpleado ? inputPasswordEmpleado.value : '';
      const rol = selectRolEmpleado ? selectRolEmpleado.value : '';

      if (!nombre || !usuario || !rol || (!idUsuarioVal && !password)) {
        mostrarErrorModalEmpleado('Complete todos los campos obligatorios de la cuenta.');
        return;
      }

      const accion = idUsuarioVal ? 'actualizar_usuario' : 'crear_usuario';
      const payload = {
        nombre_completo: nombre,
        nombre_usuario: usuario,
        rol,
      };
      if (idUsuarioVal) {
        payload.id_usuario = Number(idUsuarioVal);
      }
      if (password !== '') {
        payload.password = password;
      }

      if (btnGuardarEmpleado) btnGuardarEmpleado.disabled = true;

      try {
        const res = await fetch(`../../controllers/AdminController.php?action=${accion}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
          // Camino Alternativo RF13: usuario duplicado u otro error de validación sin cerrar el modal
          mostrarErrorModalEmpleado(data.error || 'No fue posible guardar la cuenta de personal.');
          return;
        }

        const empGuardado = data.usuario || {
          id_usuario: data.id_usuario,
          nombre_completo: nombre,
          nombre_usuario: usuario,
          nombre_rol: rol,
        };

        if (tablaPersonalBody) {
          const nuevaFila = construirFilaPersonalAdmin(empGuardado);
          const filaExistente = tablaPersonalBody.querySelector(`tr[data-id="${empGuardado.id_usuario}"]`);
          if (filaExistente) {
            filaExistente.replaceWith(nuevaFila);
          } else {
            tablaPersonalBody.appendChild(nuevaFila);
          }
        }

        actualizarContadoresPersonalDOM(data.total_usuarios);
        cerrarModalNuevoEmpleado();
      } catch (err) {
        mostrarErrorModalEmpleado(err.message || 'Error de conexión al guardar la cuenta de personal.');
      } finally {
        if (btnGuardarEmpleado) btnGuardarEmpleado.disabled = false;
      }
    });
  }

  if (tablaPersonalBody) {
    tablaPersonalBody.addEventListener('click', async (e) => {
      const btnEdit = e.target.closest('.btn-action-edit');
      if (btnEdit) {
        e.preventDefault();
        const fila = btnEdit.closest('tr');
        abrirModalNuevoEmpleado({
          id: btnEdit.dataset.id || fila?.dataset.id || '',
          nombre: btnEdit.dataset.nombre || fila?.dataset.nombre || '',
          usuario: btnEdit.dataset.usuario || fila?.dataset.usuario || '',
          rol: btnEdit.dataset.rol || fila?.dataset.rol || '',
        });
        return;
      }

      const btnDelete = e.target.closest('.btn-action-delete');
      if (btnDelete) {
        e.preventDefault();
        const fila = btnDelete.closest('tr');
        const idUsuario = Number(btnDelete.dataset.id || fila?.dataset.id || 0);
        const nombreEmp = btnDelete.dataset.nombre || fila?.dataset.nombre || 'este empleado';
        if (idUsuario <= 0) return;

        const idSesionActual = Number(document.body?.dataset?.currentUserId || 0);
        if (idSesionActual > 0 && idSesionActual === idUsuario) {
          alert('No puede eliminar su propia cuenta con una sesión activa en curso.');
          return;
        }

        if (!window.confirm(`¿Desea eliminar permanentemente la cuenta de "${nombreEmp}"?`)) {
          return;
        }

        btnDelete.disabled = true;
        try {
          const res = await fetch('../../controllers/AdminController.php?action=eliminar_usuario', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
            },
            body: JSON.stringify({ id_usuario: idUsuario }),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            throw new Error(data.error || 'No fue posible eliminar la cuenta de personal.');
          }

          if (fila) {
            fila.remove();
          }
          actualizarContadoresPersonalDOM(data.total_usuarios);
        } catch (err) {
          alert(err.message || 'Error al eliminar la cuenta de personal.');
        } finally {
          btnDelete.disabled = false;
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

// Nota: La identidad del empleado autenticado (`#nombreUsuario`, `#rolUsuario`, `#avatarUsuario`)
// se renderiza directamente desde `$_SESSION` en `views/layouts/header.php` (RNF04).


