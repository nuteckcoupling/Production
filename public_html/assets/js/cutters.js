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
        && (!search || [cutter.cutter_num, cutter.cutter_name, cutter.module, cutter.cutter_type, cutter.hob_pressure_angle, cutter.cutter_inner_diameter, cutter.cutter_outer_diameter, cutter.cutter_thickness, cutter.cutter_tooth_count, cutter.cutter_tooth_thickness, cutter.remarks]
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
            <div><span>Cutter Name</span><strong>${escapeHtml(cutter.cutter_name || '-')}</strong></div>
            <div><span>Type</span><strong>${escapeHtml(cutter.cutter_type || '-')}</strong></div>
            <div><span>Lead Angle</span><strong>${cutter.lead_angle === null ? '-' : `${escapeHtml(cutter.lead_angle)}°`}</strong></div>
            <div><span>Hob/Cutter Pressure Angle</span><strong>${cutter.hob_pressure_angle === null ? '-' : `${escapeHtml(cutter.hob_pressure_angle)}°`}</strong></div>
            <div><span>Cutter I.D.</span><strong>${cutter.cutter_inner_diameter === null ? '-' : `${escapeHtml(cutter.cutter_inner_diameter)} mm`}</strong></div>
            <div><span>Cutter O.D.</span><strong>${cutter.cutter_outer_diameter === null ? '-' : `${escapeHtml(cutter.cutter_outer_diameter)} mm`}</strong></div>
            <div><span>Cutter Thickness</span><strong>${cutter.cutter_thickness === null ? '-' : `${escapeHtml(cutter.cutter_thickness)} mm`}</strong></div>
            <div><span>Cutter Number of Teeth</span><strong>${escapeHtml(cutter.cutter_tooth_count || '-')}</strong></div>
            <div><span>Cutter Tooth Thickness</span><strong>${cutter.cutter_tooth_thickness === null ? '-' : `${escapeHtml(cutter.cutter_tooth_thickness)} mm`}</strong></div>
            <div><span>RPM / Stroke</span><strong>${escapeHtml(cutter.rpm_stroke || '-')}</strong></div>
          </div>
          ${cutter.remarks ? `<p class="cutter-remarks">${escapeHtml(cutter.remarks)}</p>` : ''}
          <div class="coupling-card-actions">
            ${!showCutterTrash ? '<button type="button" class="table-action tool-monitoring">Monitoring Report</button><button type="button" class="table-action tool-history">Tool History</button>' : ''}
            ${showCutterTrash
              ? '<button type="button" class="table-action restore restore-cutter">Restore</button>'
              : '<button type="button" class="table-action edit edit-cutter">Edit</button><button type="button" class="table-action delete delete-cutter">Delete</button>'}
          </div>`;
        card.querySelector('.edit-cutter')?.addEventListener('click', () => openCutterEditor(cutter));
        card.querySelector('.tool-monitoring')?.addEventListener('click', () => openCutterToolRecord(cutter, 'monitoring'));
        card.querySelector('.tool-history')?.addEventListener('click', () => openCutterToolRecord(cutter, 'history'));
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
      document.getElementById('cutter_name').value = cutter?.cutter_name || '';
      document.getElementById('cutter_module').value = cutter?.module || '';
      document.getElementById('cutter_type').value = cutter?.cutter_type || '';
      document.getElementById('lead_angle').value = cutter?.lead_angle ?? '';
      document.getElementById('hob_pressure_angle').value = cutter?.hob_pressure_angle ?? '';
      document.getElementById('cutter_inner_diameter').value = cutter?.cutter_inner_diameter ?? '';
      document.getElementById('cutter_outer_diameter').value = cutter?.cutter_outer_diameter ?? '';
      document.getElementById('cutter_thickness').value = cutter?.cutter_thickness ?? '';
      document.getElementById('cutter_tooth_count').value = cutter?.cutter_tooth_count ?? '';
      document.getElementById('cutter_tooth_thickness').value = cutter?.cutter_tooth_thickness ?? '';
      document.getElementById('cutter_purchase_date').value = cutter?.purchase_date || '';
      document.getElementById('cutter_scrap_date').value = cutter?.scrap_date || '';
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

    async function openCutterToolRecord(cutter, view, period = 'daily', value = today) {
      const dialog = document.getElementById('cutterToolDialog');
      const content = document.getElementById('cutterToolContent');
      const title = view === 'monitoring' ? 'Tool Monitoring Report' : 'Tool History Card';
      document.getElementById('cutterToolTitle').innerText = title;
      document.getElementById('cutterToolSubtitle').innerText = `${cutter.cutter_num} · ${cutter.module || 'Module not set'}`;
      content.innerHTML = '<p>Loading tool records...</p>';
      if (!dialog.open) dialog.showModal();
      try {
        const query = new URLSearchParams({cutter_id: cutter.id, period, value});
        const response = await fetch(`${API}/cutter_tool_tracking.php?${query}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to load cutter records.');
        if (view === 'monitoring') renderCutterMonitoring(data, cutter);
        else renderCutterHistory(data);
      } catch (error) {
        content.innerHTML = `<p class="error-text">${escapeHtml(error.message)}</p>`;
      }
    }

    function renderCutterMonitoring(data, selectedCutter) {
      const cutter = data.cutter;
      const { monitoring } = data;
      const rows = monitoring.rows.map(row => `<tr><td>${escapeHtml(row.machine_no)}</td><td>${escapeHtml(row.part_no)}</td><td>${escapeHtml(row.part_name)}</td><td>${escapeHtml(row.qty)}</td></tr>`).join('');
      document.getElementById('cutterToolContent').innerHTML = `
        <div class="grid management-grid cutter-tool-filters"><label>Report Period<select id="toolMonitoringPeriod"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></label><label id="toolMonitoringValueLabel">Date<input id="toolMonitoringValue" type="date" value="${escapeHtml(monitoring.value)}"></label><button id="loadToolMonitoringDate" class="secondary-button" type="button">Load Report</button></div>
        <div id="cutterMonitoringPrint" class="cutter-print-sheet"><h2>Tool Monitoring Report</h2><p><b>Cutter Name:</b> ${escapeHtml(cutter.cutter_name || cutter.cutter_type || '-')} &nbsp; <b>Period:</b> ${escapeHtml(monitoring.label)}</p>
        <p><b>Cutter No.:</b> ${escapeHtml(cutter.cutter_num)} &nbsp; <b>Module/Pitch:</b> ${escapeHtml(cutter.cutter_module || '-')} &nbsp; <b>Cutter Coated:</b> ${data.is_coated ? 'Yes' : 'No'} &nbsp; <b>Coated Date:</b> ${escapeHtml(data.latest_coating_date || '-')}</p>
        <p><b>Cutter Number of Teeth:</b> ${escapeHtml(cutter.cutter_tooth_count || '-')} &nbsp; <b>Cutter Tooth Thickness:</b> ${escapeHtml(cutter.cutter_tooth_thickness || '-')}</p>
        <div class="table-scroll"><table class="report-table"><thead><tr><th>Sr. No.</th><th>Machine No.</th><th>Part No.</th><th>Part Name</th><th>Qty.</th></tr></thead><tbody>${rows || '<tr><td colspan="5">No production entries for this cutter on the selected date.</td></tr>'}</tbody><tfoot><tr><th colspan="4">Total Qty</th><th>${escapeHtml(monitoring.total_qty)}</th></tr></tfoot></table></div>
        <label class="tool-remarks">Remarks<textarea id="toolMonitoringRemarks" maxlength="255"></textarea></label><p class="tool-signatures">Maintenance: __________________ &nbsp; Production: __________________ &nbsp; Quality: __________________</p></div>
        <div class="dialog-actions"><button class="submit" type="button" onclick="printCutterMonitoring()">Print / Save PDF</button></div>`;
      const periodSelect = document.getElementById('toolMonitoringPeriod');
      const valueInput = document.getElementById('toolMonitoringValue');
      periodSelect.value = monitoring.period;
      periodSelect.addEventListener('change', () => {
        const period = periodSelect.value;
        valueInput.type = period === 'monthly' ? 'month' : period === 'yearly' ? 'number' : 'date';
        valueInput.value = period === 'monthly' ? today.slice(0, 7) : period === 'yearly' ? today.slice(0, 4) : today;
        valueInput.min = period === 'yearly' ? '1000' : '';
        valueInput.max = period === 'yearly' ? '9999' : '';
        document.getElementById('toolMonitoringValueLabel').firstChild.textContent = period === 'weekly' ? 'Week containing date' : period === 'monthly' ? 'Month' : period === 'yearly' ? 'Year' : 'Date';
      });
      periodSelect.dispatchEvent(new Event('change'));
      valueInput.value = monitoring.value;
      document.getElementById('loadToolMonitoringDate').addEventListener('click', () => {
        const selectedValue = valueInput.value;
        if (selectedValue) openCutterToolRecord(selectedCutter, 'monitoring', periodSelect.value, selectedValue);
      });
    }

    function renderCutterHistory(data) {
      const { cutter, monitoring, current_cycle } = data;
      const events = data.events.filter(event => event.event_date >= monitoring.start_date && event.event_date <= monitoring.end_date);
      const rows = events.map(event => `<tr><td>${escapeHtml(event.event_date)}</td><td>${escapeHtml(event.event_type)}</td><td>${escapeHtml(event.qty_since_previous)}</td><td>${escapeHtml(event.remarks || '-')}</td></tr>`).join('');
      const productionRows = monitoring.rows.map(row => `<tr><td>${escapeHtml(row.machine_no)}</td><td>${escapeHtml(row.part_no)}</td><td>${escapeHtml(row.part_name)}</td><td>${escapeHtml(row.qty)}</td></tr>`).join('');
      document.getElementById('cutterToolContent').innerHTML = `
        <div class="grid management-grid cutter-tool-filters"><label>Report Period<select id="toolMonitoringPeriod"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></label><label id="toolMonitoringValueLabel">Date<input id="toolMonitoringValue" type="date" value="${escapeHtml(monitoring.value)}"></label><button id="loadToolMonitoringDate" class="secondary-button" type="button">Load Report</button></div>
        <div class="cutter-print-sheet"><h2>Tool History Card</h2><p><b>Cutter Name:</b> ${escapeHtml(cutter.cutter_name || cutter.cutter_type || '-')} &nbsp; <b>Cutter No.:</b> ${escapeHtml(cutter.cutter_num)} &nbsp; <b>Module/Pitch:</b> ${escapeHtml(cutter.cutter_module || '-')} &nbsp; <b>Period:</b> ${escapeHtml(monitoring.label)}</p>
        <p><b>Start Using Date:</b> ${escapeHtml(cutter.first_used_date || '-')} &nbsp; <b>Cutter Number of Teeth:</b> ${escapeHtml(cutter.cutter_tooth_count || '-')} &nbsp; <b>Cutter Tooth Thickness:</b> ${escapeHtml(cutter.cutter_tooth_thickness || '-')}</p>
        <p><b>Purchase Date:</b> ${escapeHtml(cutter.purchase_date || '-')} &nbsp; <b>Scrap Date:</b> ${escapeHtml(cutter.scrap_date || '-')}</p>
        <h3>Coating / Grinding Events in Selected Period</h3><div class="table-scroll"><table class="report-table"><thead><tr><th>Sr. No.</th><th>Coating / Grinding Date</th><th>Service</th><th>Qty. Since Previous Service</th><th>Remarks</th></tr></thead><tbody>${rows || '<tr><td colspan="5">No coating or grinding events in the selected period.</td></tr>'}</tbody></table></div>
        <h3>Production During Selected Period</h3><div class="table-scroll"><table class="report-table"><thead><tr><th>Machine No.</th><th>Part No.</th><th>Part Name</th><th>Qty.</th></tr></thead><tbody>${productionRows || '<tr><td colspan="4">No production entries in the selected period.</td></tr>'}</tbody><tfoot><tr><th colspan="3">Period Total</th><th>${escapeHtml(monitoring.total_qty)}</th></tr></tfoot></table></div>
        <p><b>Current service cycle since ${escapeHtml(current_cycle.from_date || 'first use')} (overall):</b> ${escapeHtml(current_cycle.qty)} pcs</p></div>
        <form id="cutterServiceEventForm" class="grid management-grid"><input type="hidden" name="cutter_id" value="${escapeHtml(cutter.id)}"><label>Service Type<select name="event_type" required><option value="Coating">Cutter Coating</option><option value="Grinding">Cutter Grinding</option></select></label><label>Service Date<input type="date" name="event_date" max="${today}" value="${today}" required></label><label>Remarks<input name="remarks" maxlength="255" placeholder="Optional"></label><div class="dialog-actions"><button class="submit" type="submit">Save Service Event</button><button class="secondary-button" type="button" onclick="printCutterHistory()">Print / Save PDF</button></div></form>`;
      const periodSelect = document.getElementById('toolMonitoringPeriod');
      const valueInput = document.getElementById('toolMonitoringValue');
      periodSelect.value = monitoring.period;
      periodSelect.addEventListener('change', () => {
        const period = periodSelect.value;
        valueInput.type = period === 'monthly' ? 'month' : period === 'yearly' ? 'number' : 'date';
        valueInput.value = period === 'monthly' ? today.slice(0, 7) : period === 'yearly' ? today.slice(0, 4) : today;
        valueInput.min = period === 'yearly' ? '1000' : '';
        valueInput.max = period === 'yearly' ? '9999' : '';
        document.getElementById('toolMonitoringValueLabel').firstChild.textContent = period === 'weekly' ? 'Week containing date' : period === 'monthly' ? 'Month' : period === 'yearly' ? 'Year' : 'Date';
      });
      periodSelect.dispatchEvent(new Event('change'));
      valueInput.value = monitoring.value;
      document.getElementById('loadToolMonitoringDate').addEventListener('click', () => {
        if (valueInput.value) openCutterToolRecord(cutter, 'history', periodSelect.value, valueInput.value);
      });
      document.getElementById('cutterServiceEventForm').addEventListener('submit', saveCutterServiceEvent);
    }

    async function saveCutterServiceEvent(event) {
      event.preventDefault();
      const form = event.currentTarget;
      const button = form.querySelector('[type="submit"]');
      button.disabled = true;
      try {
        const response = await fetch(`${API}/cutter_tool_tracking.php`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(Object.fromEntries(new FormData(form))) });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to save service event.');
        const cutter = cutterRecords.find(item => String(item.id) === form.elements.cutter_id.value);
        showToast('success', 'Cutter service event saved.');
        await openCutterToolRecord(cutter, 'history', document.getElementById('toolMonitoringPeriod')?.value || 'daily', document.getElementById('toolMonitoringValue')?.value || today);
      } catch (error) {
        showToast('error', error.message);
      } finally { button.disabled = false; }
    }

    function printCutterMonitoring() { printCutterToolSheet('cutterMonitoringPrint', 'Tool Monitoring Report'); }
    function printCutterHistory() { printCutterToolSheet('cutterToolContent', 'Tool History Card'); }
    function printCutterToolSheet(elementId, title) {
      const source = document.getElementById(elementId);
      if (!source) return;
      const printable = source.cloneNode(true);
      printable.querySelectorAll('textarea').forEach(field => { field.textContent = field.value; });
      const popup = window.open('', '_blank', 'width=1100,height=800');
      if (!popup) { showToast('error', 'Allow pop-ups to print this form.'); return; }
      popup.document.write(`<!doctype html><html><head><title>${escapeHtml(title)}</title><style>body{font:14px Arial,sans-serif;padding:24px;color:#111}h2{text-align:center}.report-table{width:100%;border-collapse:collapse;margin-top:16px}.report-table th,.report-table td{border:1px solid #555;padding:7px;text-align:left}.tool-remarks{display:block;margin-top:24px}.tool-remarks textarea{display:block;width:100%;height:60px}.tool-signatures{margin-top:48px}.dialog-actions,.cutter-tool-filters,#cutterServiceEventForm{display:none!important}@media print{@page{size:landscape;margin:12mm}}</style></head><body>${printable.innerHTML}<script>window.onload=()=>window.print()<\/script></body></html>`);
      popup.document.close();
    }
