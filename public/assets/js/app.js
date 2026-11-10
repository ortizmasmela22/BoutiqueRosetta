(() => {
  const loader = document.getElementById('pageLoader');
  const loaderStartedAt = Date.now();
  let pageHasLoaded = document.readyState === 'complete';
  let loaderWasHidden = false;
  const hideLoader = () => {
    if (!loader || loaderWasHidden || !pageHasLoaded) return;
    loaderWasHidden = true;
    loader.classList.add('is-hidden');
    window.setTimeout(() => loader.remove(), 800);
  };
  const finishLoaderAfterMinimum = () => {
    const remaining = Math.max(0, 3200 - (Date.now() - loaderStartedAt));
    window.setTimeout(hideLoader, remaining);
  };
  if (pageHasLoaded) finishLoaderAfterMinimum();
  else window.addEventListener('load', () => {
    pageHasLoaded = true;
    finishLoaderAfterMinimum();
  }, { once: true });
  // Respaldo: no bloquear la navegación si algún recurso tarda demasiado.
  window.setTimeout(() => { pageHasLoaded = true; hideLoader(); }, 7000);

  // Vista previa del subtotal al cambiar cantidades; el servidor recalcula al guardar.
  const cartRows = Array.from(document.querySelectorAll('[data-cart-row]'));
  if (cartRows.length) {
    const formatCOP = value => '$' + new Intl.NumberFormat('es-CO', {maximumFractionDigits: 0}).format(value) + ' COP';
    const recalculate = () => {
      let total = 0;
      cartRows.forEach(row => {
        const unitPrice = Number(row.dataset.unitPrice);
        const input = row.querySelector('input[name="quantity"]');
        const quantity = Math.max(1, Number.parseInt(input?.value || '1', 10) || 1);
        const line = unitPrice * quantity;
        total += line;
        const lineElement = row.querySelector('[data-line-total]');
        if (lineElement) lineElement.textContent = formatCOP(line);
      });
      document.querySelectorAll('[data-cart-total]').forEach(el => el.textContent = formatCOP(total));
    };
    cartRows.forEach(row => row.querySelector('input[name="quantity"]')?.addEventListener('input', recalculate));
    recalculate();
  }

  const toggle = document.getElementById('menuToggle');
  const nav = document.getElementById('mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
      toggle.textContent = open ? '×' : '☰';
    });
  }

  const search = document.getElementById('productSearch');
  const sort = document.getElementById('productSort');
  const grid = document.getElementById('productGrid');
  if (grid) {
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    const apply = () => {
      const term = (search?.value || '').trim().toLocaleLowerCase('es');
      cards.forEach(card => {
        card.hidden = !card.dataset.name.includes(term);
      });
      const visible = cards.filter(card => !card.hidden);
      if (sort?.value === 'price-asc') visible.sort((a,b) => Number(a.dataset.price)-Number(b.dataset.price));
      if (sort?.value === 'price-desc') visible.sort((a,b) => Number(b.dataset.price)-Number(a.dataset.price));
      if (sort?.value === 'featured') visible.sort((a,b) => cards.indexOf(a)-cards.indexOf(b));
      visible.forEach(card => grid.appendChild(card));
    };
    search?.addEventListener('input', apply);
    sort?.addEventListener('change', apply);
  }
})();
