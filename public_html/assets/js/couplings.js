// Extracted from index.html during Phase 2 frontend separation.
    function showCouplingMessage(type, text) {
      const banner = document.getElementById('couplingMessage');
      banner.className = `banner ${type}`;
      banner.innerText = text;
      if (text && (type === 'success' || type === 'error')) showToast(type, text);
    }

    function loadCouplings() {
      const tbody = document.getElementById('couplingsTableBody');
      tbody.innerHTML = '<tr><td colspan="2" class="empty">Loading...</td></tr>';
      fetch(`${API}/get_all_couplings.php`)
        .then(r => r.json())
        .then(couplings => {
          tbody.innerHTML = '';
          if (couplings.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="empty">No couplings added yet.</td></tr>';
            return;
          }
          couplings.forEach(coupling => {
            const row = document.createElement('tr');
            [coupling.coupling_type, coupling.part_name].forEach(value => {
              const cell = document.createElement('td');
              cell.textContent = value;
              row.appendChild(cell);
            });
            tbody.appendChild(row);
          });
        })
        .catch(() => {
          tbody.innerHTML = '<tr><td colspan="2" class="empty">Error loading couplings.</td></tr>';
        });
    }

    function handleCouplingSubmit(e) {
      e.preventDefault();
      const button = document.getElementById('saveCouplingBtn');
      const data = Object.fromEntries(new FormData(e.target).entries());
      button.disabled = true;
      button.innerText = 'Saving...';

      fetch(`${API}/add_coupling.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
        .then(async r => ({ ok: r.ok, data: await r.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Save failed.');
          showCouplingMessage('success', 'Coupling added successfully.');
          e.target.reset();
          loadCouplings();
          fetchParts();
        })
        .catch(err => showCouplingMessage('error', err.message))
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Save Coupling';
        });
    }
