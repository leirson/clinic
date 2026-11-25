// Pesquisa local: não envia nomes nem dados clínicos a serviços externos.
// O select original mantém nome, required, valor, eventos e envio do formulário.
const selector = 'select[name="paciente_id"], select[data-patient-search], select[name="paciente"]';
let nextId = 0;
const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR').replace(/\s+/g, ' ').trim();
const matches = (text, query) => query.split(' ').every(word => text.includes(word));

function makeSearch(target, onSearch) {
  const wrapper = document.createElement('div');
  wrapper.className = 'clinic-patient-search';
  const id = 'clinic-patient-search-' + (++nextId);
  const label = document.createElement('label');
  label.htmlFor = id;
  label.className = 'clinic-patient-search-label';
  label.textContent = 'Pesquisar paciente';
  const row = document.createElement('div');
  row.className = 'clinic-patient-search-row';
  const input = document.createElement('input');
  input.id = id;
  input.type = 'search';
  input.className = 'form-input';
  input.placeholder = 'Digite o nome do paciente...';
  input.autocomplete = 'off';
  input.setAttribute('aria-controls', target.id || (target.id = id + '-target'));
  const clear = document.createElement('button');
  clear.type = 'button';
  clear.className = 'clinic-patient-search-clear';
  clear.textContent = 'Limpar';
  clear.setAttribute('aria-label', 'Limpar pesquisa de paciente');
  const status = document.createElement('p');
  status.id = id + '-status';
  status.className = 'clinic-patient-search-status';
  status.setAttribute('role', 'status');
  status.setAttribute('aria-live', 'polite');
  input.setAttribute('aria-describedby', status.id);
  row.append(input, clear);
  wrapper.append(label, row, status);
  target.before(wrapper);
  const update = () => onSearch(normalize(input.value), status);
  input.addEventListener('input', update);
  // Enter pesquisa, mas nunca envia o formulário nem escolhe outro paciente.
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); update(); }
    if (event.key === 'Escape') { input.value = ''; update(); }
  });
  clear.addEventListener('click', () => { input.value = ''; update(); input.focus(); });
  return { input, clear, status, update };
}

function bindSelect(select) {
  if (select.dataset.patientSearchBound) return;
  select.dataset.patientSearchBound = '1';
  // Guardar cópias evita destruir as opções originais ao alternar a pesquisa.
  const originals = Array.from(select.options, option => option.cloneNode(true));
  const indexed = originals.map(option => ({ option, text: normalize(option.textContent) }));
  const empty = option => option.value === '' || option.value === '0';
  const originalDescription = select.getAttribute('aria-describedby') || '';
  const ui = makeSearch(select, (query, status) => {
    const selected = new Set(Array.from(select.selectedOptions, option => option.value));
    let count = 0;
    let retained = false;
    const fragment = document.createDocumentFragment();
    const outside = document.createElement('optgroup');
    outside.label = 'Paciente selecionado (fora da pesquisa)';
    indexed.forEach(({ option, text }) => {
      const placeholder = empty(option);
      const found = !placeholder && matches(text, query);
      if (found) count++;
      if (placeholder || found) {
        const clone = option.cloneNode(true);
        clone.selected = selected.has(option.value);
        fragment.append(clone);
      } else if (selected.has(option.value)) {
        const clone = option.cloneNode(true);
        clone.selected = true;
        outside.append(clone);
        retained = true;
      }
    });
    if (outside.children.length) fragment.append(outside);
    select.replaceChildren(fragment);
    // Inclusive select sem placeholder: digitar não pode selecionar o 1º resultado.
    if (!selected.size) select.selectedIndex = -1;
    status.textContent = !query
      ? count + ' paciente(s) disponível(is).'
      : count === 0 ? 'Nenhum paciente encontrado.' : count + ' paciente(s) encontrado(s).';
    if (retained) status.textContent += ' O paciente selecionado foi mantido.';
  });
  select.setAttribute('aria-describedby', (originalDescription + ' ' + ui.status.id).trim());
  ui.input.disabled = select.disabled;
  ui.clear.disabled = select.disabled;
  const disabledObserver = new MutationObserver(() => {
    ui.input.disabled = select.disabled;
    ui.clear.disabled = select.disabled;
  });
  disabledObserver.observe(select, { attributes: true, attributeFilter: ['disabled'] });
  select.addEventListener('change', ui.update);
  if (select.form) select.form.addEventListener('reset', () => {
    // Restaurar o conjunto completo ANTES de aplicar os defaultSelected originais.
    select.replaceChildren(...originals.map(option => option.cloneNode(true)));
    setTimeout(() => { ui.input.value = ''; ui.update(); }, 0);
  });
  ui.update();
}

function bindCards(container) {
  if (container.dataset.patientSearchBound) return;
  container.dataset.patientSearchBound = '1';
  const cards = Array.from(container.querySelectorAll('[data-patient-option]'));
  const ui = makeSearch(container, (query, status) => {
    let count = 0;
    cards.forEach(card => {
      const found = matches(normalize(card.textContent), query);
      card.hidden = !found;
      if (found) count++;
    });
    status.textContent = count ? count + ' paciente(s) encontrado(s).' : 'Nenhum paciente encontrado.';
  });
  ui.update();
}

export function initPatientSearch(root = document) {
  if (root.matches?.(selector)) bindSelect(root);
  root.querySelectorAll?.(selector).forEach(bindSelect);
  if (root.matches?.('[data-patient-list]')) bindCards(root);
  root.querySelectorAll?.('[data-patient-list]').forEach(bindCards);
}

initPatientSearch();
// Novos seletores inseridos em modais/formulários recebem o mesmo comportamento.
new MutationObserver(records => {
  records.forEach(record => record.addedNodes.forEach(node => {
    if (node.nodeType === 1) initPatientSearch(node);
  }));
}).observe(document.body, { childList: true, subtree: true });
