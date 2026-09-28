// Extracted from index.html during Phase 2 frontend separation.
    let showCouplingTrash = false;

    function showCouplingMessage(type, text) {
      const banner = document.getElementById('couplingMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadCouplings() {
      const tbody = document.getElementById('couplingsTableBody');
      tbody.innerHTML = '<tr><td colspan="3" class="empty">Loading...</td></tr>';
      fetch(`${API}/get_all_couplings.php${showCouplingTrash ? '?trash=1' : ''}`)
        .then(r => r.json())
        .then(couplings => {
          tbody.innerHTML = '';
          if (couplings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="empty">' + (showCouplingTrash ? 'Deleted list is empty.' : 'No couplings added yet.') + '</td></tr>';
            return;
          }
          couplings.forEach(coupling => {
            const row = document.createElement('tr');
            [coupling.coupling_type, coupling.part_name].forEach(value => {
              const cell = document.createElement('td');
              cell.textContent = value;
              row.appendChild(cell);
            });
            const actions = document.createElement('td');
            actions.className = 'table-actions';
            const photosButton = document.createElement('button');
            photosButton.type = 'button';
            photosButton.className = 'table-action edit';
            photosButton.textContent = 'Photos';
            photosButton.addEventListener('click', () => openPartAttachments(coupling));
            actions.appendChild(photosButton);
            if (showCouplingTrash) {
              const restoreButton = document.createElement('button');
              restoreButton.type = 'button';
              restoreButton.className = 'table-action restore';
              restoreButton.textContent = 'Restore';
              restoreButton.addEventListener('click', () => restoreCoupling(coupling));
              actions.appendChild(restoreButton);
            } else {
              const editButton = document.createElement('button');
              editButton.type = 'button';
              editButton.className = 'table-action edit';
              editButton.textContent = 'Edit';
              editButton.addEventListener('click', () => startCouplingEdit(coupling));
              const deleteButton = document.createElement('button');
              deleteButton.type = 'button';
              deleteButton.className = 'table-action delete';
              deleteButton.textContent = 'Delete';
              deleteButton.addEventListener('click', () => deleteCoupling(coupling));
              actions.append(editButton, deleteButton);
            }
            row.appendChild(actions);
            tbody.appendChild(row);
          });
        })
        .catch(() => {
          tbody.innerHTML = '<tr><td colspan="3" class="empty">Error loading couplings.</td></tr>';
        });
    }

    function openPartAttachments(coupling) {
      const panel = document.getElementById('partAttachmentPanel');
      const form = document.getElementById('partAttachmentForm');
      form.dataset.entityId = coupling.id;
      form.classList.toggle('hidden', showCouplingTrash || currentUser?.role !== 'Operator/Supervisor');
      document.getElementById('partAttachmentTitle').innerText = `${coupling.part_name} — Part Photographs`;
      panel.classList.remove('hidden');
      loadAttachmentList('part', coupling.id, 'partAttachmentList');
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closePartAttachments() {
      document.getElementById('partAttachmentPanel').classList.add('hidden');
      document.getElementById('partAttachmentForm').reset();
    }

    function toggleCouplingTrash() {
      showCouplingTrash = !showCouplingTrash;
      cancelCouplingEdit(false);
      document.getElementById('couplingListTitle').innerText = showCouplingTrash ? 'Deleted Couplings' : 'Existing Couplings';
      document.getElementById('toggleCouplingTrashBtn').innerText = showCouplingTrash ? 'Back to Active Couplings' : 'Show Deleted Couplings';
      loadCouplings();
    }

    function startCouplingEdit(coupling) {
      document.getElementById('coupling_edit_id').value = coupling.id;
      document.getElementById('new_coupling_type').value = coupling.coupling_type;
      document.getElementById('new_part_name').value = coupling.part_name;
      document.getElementById('couplingFormTitle').innerText = 'Edit Coupling';
      document.getElementById('saveCouplingBtn').innerText = 'Update Coupling';
      document.getElementById('cancelCouplingEditBtn').classList.remove('hidden');
      showCouplingMessage('success', 'Editing ' + coupling.part_name + '.');
      document.getElementById('couplingForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cancelCouplingEdit(clearMessage = true) {
      document.getElementById('couplingForm').reset();
      document.getElementById('coupling_edit_id').value = '';
      document.getElementById('couplingFormTitle').innerText = 'Add Coupling';
      document.getElementById('saveCouplingBtn').innerText = 'Save Coupling';
      document.getElementById('cancelCouplingEditBtn').classList.add('hidden');
      if (clearMessage) showCouplingMessage('', '');
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
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: coupling.id })
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Delete failed.');
          if (String(document.getElementById('coupling_edit_id').value) === String(coupling.id)) cancelCouplingEdit(false);
          showCouplingMessage('success', result.data.message);
          loadCouplings();
          fetchParts();
        })
        .catch(error => showCouplingMessage('error', error.message));
    }

    function restoreCoupling(coupling) {
      fetch(`${API}/restore_coupling.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: coupling.id })
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Restore failed.');
          showCouplingMessage('success', result.data.message);
          loadCouplings();
          fetchParts();
        })
        .catch(error => showCouplingMessage('error', error.message));
    }

    function handleCouplingSubmit(e) {
      e.preventDefault();
      const button = document.getElementById('saveCouplingBtn');
      const data = Object.fromEntries(new FormData(e.target).entries());
      const isEditing = Boolean(data.id);
      button.disabled = true;
      button.innerText = isEditing ? 'Updating...' : 'Saving...';

      fetch(`${API}/${isEditing ? 'update_coupling.php' : 'add_coupling.php'}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Save failed.');
          cancelCouplingEdit(false);
          showCouplingMessage('success', isEditing ? 'Coupling updated successfully.' : 'Coupling added successfully.');
          loadCouplings();
          fetchParts();
        })
        .catch(err => showCouplingMessage('error', err.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('coupling_edit_id').value ? 'Update Coupling' : 'Save Coupling';
        });
    }
