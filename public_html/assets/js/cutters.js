// Extracted from index.html during Phase 2 frontend separation.
    function showCutterMessage(type, text) {
      const banner = document.getElementById('cutterMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadCutters() {
      const tbody = document.getElementById('cuttersTableBody');
      tbody.innerHTML = '<tr><td colspan="7" class="empty">Loading...</td></tr>';
      fetch(`${API}/get_all_cutters.php${showCutterTrash ? '?trash=1' : ''}`)
        .then(r => r.json())
        .then(cutters => {
          tbody.innerHTML = '';
          if (cutters.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty">' + (showCutterTrash ? 'Trash is empty.' : 'No cutters added yet.') + '</td></tr>';
            return;
          }
          cutters.forEach(cutter => {
            const row = document.createElement('tr');
            [cutter.cutter_num, cutter.cutter_type || '-', cutter.lead_angle === null ? '-' : `${cutter.lead_angle}°`, cutter.rpm_stroke || '-', cutter.status, cutter.remarks || '-'].forEach(value => {
              const cell = document.createElement('td');
              cell.textContent = value;
              row.appendChild(cell);
            });

            const actions = document.createElement('td');
            actions.className = 'table-actions';
            if (showCutterTrash) {
              const restoreButton = document.createElement('button');
              restoreButton.type = 'button';
              restoreButton.className = 'table-action restore';
              restoreButton.textContent = 'Restore';
              restoreButton.addEventListener('click', () => restoreCutter(cutter));
              actions.appendChild(restoreButton);
            } else {
              const editButton = document.createElement('button');
              editButton.type = 'button';
              editButton.className = 'table-action edit';
              editButton.textContent = 'Edit';
              editButton.addEventListener('click', () => startCutterEdit(cutter));
              const deleteButton = document.createElement('button');
              deleteButton.type = 'button';
              deleteButton.className = 'table-action delete';
              deleteButton.textContent = 'Delete';
              deleteButton.addEventListener('click', () => deleteCutter(cutter));
              actions.append(editButton, deleteButton);
            }
            row.appendChild(actions);
            tbody.appendChild(row);
          });
        })
        .catch(() => {
          tbody.innerHTML = '<tr><td colspan="7" class="empty">Error loading cutters.</td></tr>';
        });
    }

    function toggleCutterTrash() {
      showCutterTrash = !showCutterTrash;
      cancelCutterEdit(false);
      document.getElementById('cutterListTitle').innerText = showCutterTrash ? 'Deleted Cutters' : 'Existing Cutters';
      document.getElementById('toggleCutterTrashBtn').innerText = showCutterTrash ? 'Back to Active Cutters' : 'Show Deleted Cutters';
      loadCutters();
    }

    function restoreCutter(cutter) {
      fetch(`${API}/restore_cutter.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: cutter.id })
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Restore failed.');
          showCutterMessage('success', result.data.message);
          loadCutters();
          refreshDailyCutters();
        })
        .catch(error => showCutterMessage('error', error.message));
    }


    function startCutterEdit(cutter) {
      document.getElementById('cutter_edit_id').value = cutter.id;
      document.getElementById('cutter_num').value = cutter.cutter_num || '';
      document.getElementById('cutter_type').value = cutter.cutter_type || '';
      document.getElementById('lead_angle').value = cutter.lead_angle ?? '';
      document.getElementById('rpm_stroke').value = cutter.rpm_stroke || '';
      document.getElementById('cutter_status').value = cutter.status;
      document.getElementById('cutter_remarks').value = cutter.remarks || '';
      document.getElementById('cutterFormTitle').innerText = 'Edit Cutter';
      document.getElementById('saveCutterBtn').innerText = 'Update Cutter';
      document.getElementById('cancelCutterEditBtn').classList.remove('hidden');
      showCutterMessage('success', 'Editing ' + cutter.cutter_num + '.');
      document.getElementById('cutterForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cancelCutterEdit(clearMessage = true) {
      document.getElementById('cutterForm').reset();
      document.getElementById('cutter_edit_id').value = '';
      document.getElementById('cutterFormTitle').innerText = 'Add Cutter';
      document.getElementById('saveCutterBtn').innerText = 'Save Cutter';
      document.getElementById('cancelCutterEditBtn').classList.add('hidden');
      if (clearMessage) {
        const banner = document.getElementById('cutterMessage');
        banner.className = 'banner';
        banner.innerText = '';
      }
    }

    async function deleteCutter(cutter) {
      const confirmed = await showConfirmDialog({
        title: 'Move Cutter to Trash?',
        message: cutter.cutter_num + ' will be removed from active cutter dropdowns and can be restored later.',
        confirmText: 'Move to Trash',
        tone: 'danger'
      });
      if (!confirmed) return;
      fetch(`${API}/delete_cutter.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: cutter.id })
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Delete failed.');
          if (String(document.getElementById('cutter_edit_id').value) === String(cutter.id)) {
            cancelCutterEdit(false);
          }
          showCutterMessage('success', result.data.message);
          loadCutters();
          refreshDailyCutters();
        })
        .catch(error => showCutterMessage('error', error.message));
    }

    function handleCutterSubmit(e) {
      e.preventDefault();
      const button = document.getElementById('saveCutterBtn');
      const data = Object.fromEntries(new FormData(e.target).entries());
      const isEditing = Boolean(data.id);
      button.disabled = true;
      button.innerText = isEditing ? 'Updating...' : 'Saving...';

      fetch(`${API}/${isEditing ? 'update_cutter.php' : 'add_cutter.php'}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Save failed.');
          cancelCutterEdit(false);
          showCutterMessage('success', isEditing ? 'Cutter updated successfully.' : 'Cutter added successfully.');
          loadCutters();
          refreshDailyCutters();
        })
        .catch(err => showCutterMessage('error', err.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = document.getElementById('cutter_edit_id').value ? 'Update Cutter' : 'Save Cutter';
        });
    }
