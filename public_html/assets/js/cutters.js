// Cutter catalogue, filters and editing.
    let cutterRecords = [];
    let cutterStatusView = 'Active';

    function showCutterMessage(type, text) {
      const banner = document.getElementById('cutterMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function setCutterView(view) {
      cutterStatusView = view;
      showCutterTrash = view === 'Trash';
      document.getElementById('activeCutterTab').classList.toggle('active', view === 'Active');
      document.getElementById('inactiveCutterTab').classList.toggle('active', view === 'Not Active');
      document.getElementById('deletedCutterTab').classList.toggle('active', view === 'Trash');
      loadCutters();
    }

    function loadCutters() {
      const list = document.getElementById('cutterCardList');
      list.innerHTML = '<div class="card empty">Loading cutters...</div>';
      fetch(`${API}/get_all_cutters.php${showCutterTrash ? '?trash=1' : ''}`)
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to load cutters.');
          cutterRecords = result.data;
          populateCutterModuleFilter();
          renderCutters();
        })
        .catch(error => {
          list.innerHTML = `<div class="card empty error-text">${escapeHtml(error.message)}</div>`;
        });
    }

    function populateCutterModuleFilter() {
      const select = document.getElementById('cutterModuleFilter');
      const selected = select.value;
      const modules = [...new Set(cutterRecords.map(cutter => cutter.module).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
      select.innerHTML = '<option value="">All Modules</option>' + modules
        .map(module => `<option value="${escapeHtml(module)}">${escapeHtml(module)}</option>`).join('');
      if (modules.includes(selected)) select.value = selected;
    }

    function renderCutters() {
      const list = document.getElementById('cutterCardList');
      const search = document.getElementById('cutterSearch').value.trim().toLowerCase();
      const module = document.getElementById('cutterModuleFilter').value;
      const filtered = cutterRecords.filter(cutter =>
        (showCutterTrash || cutter.status === cutterStatusView)
        && (!module || cutter.module === module)
        && (!search || [cutter.cutter_num, cutter.module, cutter.cutter_type, cutter.remarks]
          .some(value => String(value || '').toLowerCase().includes(search))));
      const label = showCutterTrash ? 'deleted' : cutterStatusView.toLowerCase();
      document.getElementById('cutterResultCount').innerText = `${filtered.length} ${label} cutter${filtered.length === 1 ? '' : 's'}`;
      list.innerHTML = '';
      if (!filtered.length) {
        list.innerHTML = '<div class="card empty">No matching cutters found.</div>';
        return;
      }

      filtered.forEach(cutter => {
        const card = document.createElement('article');
        card.className = 'coupling-card cutter-card';
        card.innerHTML = `
          <div class="coupling-card-heading">
            <div><span class="coupling-range-badge">${escapeHtml(cutter.module || 'Module not set')}</span><h2>${escapeHtml(cutter.cutter_num)}</h2></div>
            <span class="cutter-status ${cutter.status === 'Active' ? 'active' : 'inactive'}">${escapeHtml(cutter.status)}</span>
          </div>
          <div class="cutter-details">
            <div><span>Type</span><strong>${escapeHtml(cutter.cutter_type || '-')}</strong></div>
            <div><span>Lead Angle</span><strong>${cutter.lead_angle === null ? '-' : `${escapeHtml(cutter.lead_angle)}°`}</strong></div>
            <div><span>RPM / Stroke</span><strong>${escapeHtml(cutter.rpm_stroke || '-')}</strong></div>
          </div>
          ${cutter.remarks ? `<p class="cutter-remarks">${escapeHtml(cutter.remarks)}</p>` : ''}
          <div class="coupling-card-actions">
            ${showCutterTrash
              ? '<button type="button" class="table-action restore restore-cutter">Restore</button>'
              : '<button type="button" class="table-action edit edit-cutter">Edit</button><button type="button" class="table-action delete delete-cutter">Delete</button>'}
          </div>`;
        card.querySelector('.edit-cutter')?.addEventListener('click', () => openCutterEditor(cutter));
        card.querySelector('.delete-cutter')?.addEventListener('click', () => deleteCutter(cutter));
        card.querySelector('.restore-cutter')?.addEventListener('click', () => restoreCutter(cutter));
        list.appendChild(card);
      });
    }

    function openCutterEditor(cutter = null) {
      const form = document.getElementById('cutterForm');
      form.reset();
      document.getElementById('cutter_edit_id').value = cutter?.id || '';
      document.getElementById('cutter_num').value = cutter?.cutter_num || '';
      document.getElementById('cutter_module').value = cutter?.module || '';
      document.getElementById('cutter_type').value = cutter?.cutter_type || '';
      document.getElementById('lead_angle').value = cutter?.lead_angle ?? '';
      document.getElementById('rpm_stroke').value = cutter?.rpm_stroke || '';
      document.getElementById('cutter_status').value = cutter?.status || 'Active';
      document.getElementById('cutter_remarks').value = cutter?.remarks || '';
      document.getElementById('cutterFormTitle').innerText = cutter ? 'Edit Cutter' : 'Add Cutter';
      document.getElementById('saveCutterBtn').innerText = cutter ? 'Update Cutter' : 'Save Cutter';
      const dialog = document.getElementById('cutterEditorDialog');
      if (!dialog.open) dialog.showModal();
      document.getElementById('cutter_num').focus();
    }

    function cancelCutterEdit(clearMessage = false) {
      document.getElementById('cutterForm').reset();
      document.getElementById('cutter_edit_id').value = '';
      const dialog = document.getElementById('cutterEditorDialog');
      if (dialog.open) dialog.close();
      if (clearMessage) showCutterMessage('', '');
    }

    function restoreCutter(cutter) {
      fetch(`${API}/restore_cutter.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: cutter.id })
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Restore failed.');
          showCutterMessage('success', result.data.message);
          loadCutters();
          refreshDailyCutters();
        }).catch(error => showCutterMessage('error', error.message));
    }

    async function deleteCutter(cutter) {
      const confirmed = await showConfirmDialog({
        title: 'Move Cutter to Trash?',
        message: cutter.cutter_num + ' will be removed from active cutter dropdowns and can be restored later.',
        confirmText: 'Move to Trash', tone: 'danger'
      });
      if (!confirmed) return;
      fetch(`${API}/delete_cutter.php`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: cutter.id })
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Delete failed.');
          showCutterMessage('success', result.data.message);
          loadCutters();
          refreshDailyCutters();
        }).catch(error => showCutterMessage('error', error.message));
    }

    function handleCutterSubmit(event) {
      event.preventDefault();
      const button = document.getElementById('saveCutterBtn');
      const data = Object.fromEntries(new FormData(event.target).entries());
      const isEditing = Boolean(data.id);
      button.disabled = true;
      button.innerText = isEditing ? 'Updating...' : 'Saving...';
      fetch(`${API}/${isEditing ? 'update_cutter.php' : 'add_cutter.php'}`, {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
      }).then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Save failed.');
          cancelCutterEdit();
          showCutterMessage('success', isEditing ? 'Cutter updated successfully.' : 'Cutter added successfully.');
          loadCutters();
          refreshDailyCutters();
        }).catch(error => showCutterMessage('error', error.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('cutter_edit_id').value ? 'Update Cutter' : 'Save Cutter';
        });
    }

    document.getElementById('cutterSearch').addEventListener('input', renderCutters);
    document.getElementById('cutterModuleFilter').addEventListener('change', renderCutters);
