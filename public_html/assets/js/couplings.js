// Coupling catalogue, editing and drawing management.
    let showCouplingTrash = false;
    let couplingRecords = [];

    function showCouplingMessage(type, text) {
      const banner = document.getElementById('couplingMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function setCouplingView(showTrash) {
      showCouplingTrash = showTrash;
      document.getElementById('activeCouplingTab').classList.toggle('active', !showTrash);
      document.getElementById('deletedCouplingTab').classList.toggle('active', showTrash);
      closePartAttachments(false);
      loadCouplings();
    }

    function loadCouplings() {
      const list = document.getElementById('couplingCardList');
      list.innerHTML = '<div class="card empty">Loading couplings...</div>';
      fetch(`${API}/get_all_couplings.php${showCouplingTrash ? '?trash=1' : ''}`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load couplings.');
          couplingRecords = result.data;
          renderCouplings();
        })
        .catch(error => {
          list.innerHTML = `<div class="card empty error-text">${escapeHtml(error.message)}</div>`;
        });
    }

    function renderCouplings() {
      const list = document.getElementById('couplingCardList');
      const search = document.getElementById('couplingSearch').value.trim().toLowerCase();
      const range = document.getElementById('couplingRangeFilter').value;
      const filtered = couplingRecords.filter(coupling =>
        (!range || coupling.coupling_type === range)
        && (!search || coupling.part_name.toLowerCase().includes(search) || coupling.coupling_type.toLowerCase().includes(search)));
      document.getElementById('couplingResultCount').innerText = `${filtered.length} ${showCouplingTrash ? 'deleted' : 'active'} coupling${filtered.length === 1 ? '' : 's'}`;
      list.innerHTML = '';
      if (!filtered.length) {
        list.innerHTML = '<div class="card empty">No matching couplings found.</div>';
        return;
      }

      filtered.forEach(coupling => {
        const card = document.createElement('article');
        card.className = 'coupling-card';
        const components = Array.isArray(coupling.components) && coupling.components.length
          ? coupling.components.map(component => `<span>${escapeHtml(component)}</span>`).join('')
          : '<span>No component</span>';
        card.innerHTML = `
          <div class="coupling-card-heading">
            <div><span class="coupling-range-badge">${escapeHtml(coupling.coupling_type)}</span><h2>${escapeHtml(coupling.part_name)}</h2></div>
            <span class="drawing-count">${Number(coupling.drawing_count)} drawing${Number(coupling.drawing_count) === 1 ? '' : 's'}</span>
          </div>
          <div class="component-badges">${components}</div>
          <div class="coupling-card-actions">
            <button type="button" class="secondary-button manage-drawings">Drawings</button>
            ${showCouplingTrash
              ? '<button type="button" class="table-action restore restore-coupling">Restore</button>'
              : '<button type="button" class="table-action edit edit-coupling">Edit</button><button type="button" class="table-action delete delete-coupling">Delete</button>'}
          </div>`;
        card.querySelector('.manage-drawings').addEventListener('click', () => openPartAttachments(coupling));
        card.querySelector('.edit-coupling')?.addEventListener('click', () => startCouplingEdit(coupling));
        card.querySelector('.delete-coupling')?.addEventListener('click', () => deleteCoupling(coupling));
        card.querySelector('.restore-coupling')?.addEventListener('click', () => restoreCoupling(coupling));
        list.appendChild(card);
      });
    }

    function openCouplingEditor(coupling = null) {
      const dialog = document.getElementById('couplingEditorDialog');
      const form = document.getElementById('couplingForm');
      form.reset();
      document.getElementById('coupling_edit_id').value = coupling?.id || '';
      document.getElementById('new_coupling_type').value = coupling?.coupling_type || '';
      document.getElementById('new_part_name').value = coupling?.part_name || '';
      document.getElementById('couplingFormTitle').innerText = coupling ? 'Edit Coupling' : 'Add Coupling';
      document.getElementById('saveCouplingBtn').innerText = coupling ? 'Update Coupling' : 'Save Coupling';
      if (!dialog.open) dialog.showModal();
      document.getElementById(coupling ? 'new_part_name' : 'new_coupling_type').focus();
    }

    function startCouplingEdit(coupling) {
      openCouplingEditor(coupling);
    }

    function cancelCouplingEdit(clearMessage = false) {
      document.getElementById('couplingForm').reset();
      document.getElementById('coupling_edit_id').value = '';
      const dialog = document.getElementById('couplingEditorDialog');
      if (dialog.open) dialog.close();
      if (clearMessage) showCouplingMessage('', '');
    }

    async function openPartAttachments(coupling) {
      const dialog = document.getElementById('partAttachmentDialog');
      const form = document.getElementById('partAttachmentForm');
      const component = document.getElementById('partDrawingComponent');
      form.reset();
      form.dataset.entityId = coupling.id;
      form.classList.toggle('hidden', showCouplingTrash || currentUser?.role !== 'Operator/Supervisor');
      document.getElementById('partAttachmentTitle').innerText = `${coupling.part_name} — Drawings`;
      component.innerHTML = '<option value="">Loading...</option>';
      if (!dialog.open) dialog.showModal();
      loadAttachmentList('part', coupling.id, 'partAttachmentList', { category: 'Coupling Drawing' }, {
        allowDelete: !showCouplingTrash && currentUser?.role === 'Operator/Supervisor'
      });
      try {
        const response = await fetch(`${API}/get_part_components.php?part_id=${encodeURIComponent(coupling.id)}`);
        const components = await response.json();
        if (!response.ok) throw new Error(components.error || 'Unable to load components.');
        component.innerHTML = '<option value="">-- select --</option>' + components
          .map(item => `<option value="${escapeHtml(item.component_name)}">${escapeHtml(item.component_name)}</option>`).join('');
      } catch (error) {
        component.innerHTML = '<option value="">Unable to load</option>';
        showToast('error', error.message);
      }
    }

    function closePartAttachments(refresh = true) {
      const dialog = document.getElementById('partAttachmentDialog');
      if (dialog.open) dialog.close();
      document.getElementById('partAttachmentForm').reset();
      if (refresh && !document.getElementById('couplingModule').classList.contains('hidden')) loadCouplings();
    }

    async function deleteCoupling(coupling) {
      const confirmed = await showConfirmDialog({
        title: 'Delete Coupling?',
        message: coupling.part_name + ' will be removed from production dropdowns. Its old production history will remain safe.',
        confirmText: 'Delete Coupling',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(`${API}/delete_coupling.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: coupling.id })
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Delete failed.');
          showCouplingMessage('success', result.data.message);
          loadCouplings();
          fetchParts();
        }).catch(error => showCouplingMessage('error', error.message));
    }

    function restoreCoupling(coupling) {
      fetch(`${API}/restore_coupling.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: coupling.id })
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Restore failed.');
          showCouplingMessage('success', result.data.message);
          loadCouplings();
          fetchParts();
        }).catch(error => showCouplingMessage('error', error.message));
    }

    function handleCouplingSubmit(event) {
      event.preventDefault();
      const button = document.getElementById('saveCouplingBtn');
      const data = Object.fromEntries(new FormData(event.target).entries());
      const isEditing = Boolean(data.id);
      button.disabled = true;
      button.innerText = isEditing ? 'Updating...' : 'Saving...';
      fetch(`${API}/${isEditing ? 'update_coupling.php' : 'add_coupling.php'}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Save failed.');
          cancelCouplingEdit();
          showCouplingMessage('success', isEditing ? 'Coupling updated successfully.' : 'Coupling added successfully.');
          loadCouplings();
          fetchParts();
        }).catch(error => showCouplingMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('coupling_edit_id').value ? 'Update Coupling' : 'Save Coupling';
        });
    }

    document.getElementById('couplingSearch').addEventListener('input', renderCouplings);
    document.getElementById('couplingRangeFilter').addEventListener('change', renderCouplings);
