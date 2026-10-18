// Cutter catalogue, filters and editing.
    let cutterRecords = [];
    let cutterStatusView = 'Active';
    let cutterServiceData = {vendors: [], challans: [], items: [], available_cutter_ids: []};
    let cutterAvailableIds = new Set();
    let cutterServiceLoaded = false;
    let cutterVendorManagement = [];

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
          loadCutterServiceData();
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
            <span class="cutter-status ${cutterAvailableIds.has(Number(cutter.id)) ? 'active' : 'inactive'}">${!cutterServiceLoaded ? 'Checking service status' : cutter.status === 'Active' && !cutter.deleted_at && !cutter.scrap_date && !cutterAvailableIds.has(Number(cutter.id)) ? 'Out for Service' : escapeHtml(cutter.status)}</span>
          </div>
          <div class="cutter-details">
            <div><span>Cutter Name</span><strong>${escapeHtml(cutter.cutter_name || '-')}</strong></div>
            <div><span>Type</span><strong>${escapeHtml(cutter.cutter_type || '-')}</strong></div>
            <div><span>Lead Angle</span><strong>${cutter.lead_angle === null ? '-' : `${escapeHtml(cutter.lead_angle)}°`}</strong></div>
            <div><span>Hob/Cutter Pressure Angle</span><strong>${cutter.hob_pressure_angle === null ? '-' : `${escapeHtml(cutter.hob_pressure_angle)}°`}</strong></div>
            <div><span>Cutter I.D.</span><strong>${cutter.cutter_inner_diameter === null ? '-' : `${escapeHtml(cutter.cutter_inner_diameter)} mm`}</strong></div>
            <div><span>Cutter O.D.</span><strong>${cutter.cutter_outer_diameter === null ? '-' : `${escapeHtml(cutter.cutter_outer_diameter)} mm`}</strong></div>
            <div><span>Cutter Thickness / Length</span><strong>${cutter.cutter_thickness === null ? '-' : `${escapeHtml(cutter.cutter_thickness)} mm`}</strong></div>
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

    async function loadCutterServiceData() {
      const list = document.getElementById('cutterChallanList');
      if (!list) return;
      try {
        const response = await fetch(`${API}/cutter_service_challans.php`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to load cutter service challans. Apply the database migration first.');
        cutterServiceData = data;
        cutterAvailableIds = new Set(data.available_cutter_ids || []);
        cutterServiceLoaded = true;
        renderCutters();
        renderCutterChallans();
        populateChallanVendors();
      } catch (error) {
        cutterServiceLoaded = false;
        renderCutters();
        list.innerHTML = `<p class="error-text">${escapeHtml(error.message)}</p>`;
      }
    }

    function populateChallanVendors() {
      const select = document.getElementById('challanVendor');
      if (!select) return;
      const selected = select.value;
      select.innerHTML = '<option value="">Select vendor</option>' + cutterServiceData.vendors.map(v =>
        `<option value="${escapeHtml(v.id)}">${escapeHtml(v.name)} — ${escapeHtml(v.address)}</option>`).join('');
      if (cutterServiceData.vendors.some(v => String(v.id) === selected)) select.value = selected;
    }

    function renderCutterChallans() {
      const container = document.getElementById('cutterChallanList');
      const {challans, items} = cutterServiceData;
      if (!challans?.length) { container.innerHTML = '<p>No cutter service challans yet.</p>'; return; }
      container.innerHTML = `<table class="report-table"><thead><tr><th>Challan</th><th>Vendor</th><th>Dispatch</th><th>Expected Return</th><th>Status</th><th>Cutters</th><th>Actions</th></tr></thead><tbody>${challans.map(ch => {
        const lines = items.filter(item => Number(item.challan_id) === Number(ch.id));
        return `<tr><td>${escapeHtml(ch.challan_no)}</td><td>${escapeHtml(ch.vendor_name)}</td><td>${escapeHtml(ch.dispatch_date)}</td><td>${escapeHtml(ch.expected_return_date || '-')}</td><td>${escapeHtml(ch.status)}</td><td>${lines.length}</td><td><button class="table-action" type="button" onclick="printCutterChallan(${Number(ch.id)})">Print / PDF</button></td></tr>
          <tr class="cutter-challan-detail"><td colspan="7"><div class="table-scroll"><table class="report-table"><thead><tr><th>Cutter</th><th>Service</th><th>Dispatch Remarks</th><th>Return</th><th>Service Date</th><th>Condition / Remarks</th><th>Action</th></tr></thead><tbody>${lines.map(item => `<tr><td>${escapeHtml(item.cutter_num)}${item.cutter_module ? ` — ${escapeHtml(item.cutter_module)}` : ''}</td><td>${escapeHtml(item.service_type)}</td><td>${escapeHtml(item.sent_remarks || '-')}</td><td>${item.received_at ? 'Received' : 'Out for Service'}</td><td>${escapeHtml(item.service_date || '-')}</td><td>${escapeHtml([item.return_condition, item.return_remarks].filter(Boolean).join(' — ') || '-')}</td><td>${item.received_at ? '-' : `<button class="table-action edit" type="button" onclick="openCutterReceiveDialog(${Number(item.id)})">Receive</button>`}</td></tr>`).join('')}</tbody></table></div></td></tr>`;
      }).join('')}</tbody></table>`;
    }

    function openCutterVendorDialog() {
      openCutterVendorEditor(null);
    }

    function openCutterVendorEditor(vendorId) {
      const form = document.getElementById('cutterVendorForm');
      form.reset();
      const vendor = vendorId == null ? null : cutterVendorManagement.find(item => Number(item.id) === Number(vendorId));
      form.elements.id.value = vendor ? vendor.id : '';
      document.getElementById('cutterVendorTitle').textContent = vendor ? 'Edit Service Vendor' : 'Add Service Vendor';
      document.getElementById('cutterVendorSubmit').textContent = vendor ? 'Save Changes' : 'Save Vendor';
      if (vendor) {
        form.elements.name.value = vendor.name;
        form.elements.address.value = vendor.address;
        form.elements.phone.value = vendor.phone || '';
      }
      document.getElementById('cutterVendorDialog').showModal();
    }

    async function openCutterVendorManager() {
      document.getElementById('cutterVendorManagerDialog').showModal();
      await loadCutterVendorManagement();
    }

    async function loadCutterVendorManagement() {
      const list = document.getElementById('cutterVendorManagerList');
      list.innerHTML = '<p>Loading vendors...</p>';
      try {
        const response = await fetch(`${API}/cutter_service_vendors.php?manage=1`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to load vendors.');
        cutterVendorManagement = data;
        renderCutterVendorManagement();
      } catch (error) {
        list.innerHTML = `<p class="error-text">${escapeHtml(error.message)}</p>`;
      }
    }

    function renderCutterVendorManagement() {
      const list = document.getElementById('cutterVendorManagerList');
      if (!cutterVendorManagement.length) {
        list.innerHTML = '<p>No service vendors added yet. Use “Add Vendor” to create one.</p>';
        return;
      }
      list.innerHTML = `<table class="report-table"><thead><tr><th>Vendor</th><th>Phone</th><th>Address</th><th>Status</th><th>Challans</th><th>Actions</th></tr></thead><tbody>${cutterVendorManagement.map(vendor => {
        const id = Number(vendor.id);
        const used = Number(vendor.challan_count) > 0;
        const nextStatus = vendor.status === 'Active' ? 'Inactive' : 'Active';
        return `<tr><td>${escapeHtml(vendor.name)}</td><td>${escapeHtml(vendor.phone || '-')}</td><td>${escapeHtml(vendor.address)}</td><td>${escapeHtml(vendor.status)}</td><td>${Number(vendor.challan_count)}</td><td class="table-actions"><button class="table-action edit" type="button" onclick="openCutterVendorEditor(${id})">Edit</button><button class="table-action ${nextStatus === 'Active' ? 'restore' : 'delete'}" type="button" onclick="setCutterVendorStatus(${id}, '${nextStatus}')">${nextStatus === 'Active' ? 'Activate' : 'Deactivate'}</button>${used ? '<span class="muted">Challan history — cannot delete</span>' : `<button class="table-action delete" type="button" onclick="deleteCutterVendor(${id})">Delete</button>`}</td></tr>`;
      }).join('')}</tbody></table>`;
    }

    async function postCutterVendorAction(payload) {
      const response = await fetch(`${API}/cutter_service_vendors.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Vendor action failed.');
      return data;
    }

    async function setCutterVendorStatus(vendorId, status) {
      try {
        await postCutterVendorAction({action:'status', id:vendorId, status});
        await Promise.all([loadCutterVendorManagement(), loadCutterServiceData()]);
        showToast('success', `Vendor ${status.toLowerCase()}.`);
      } catch (error) { showToast('error', error.message); }
    }

    async function deleteCutterVendor(vendorId) {
      const vendor = cutterVendorManagement.find(item => Number(item.id) === Number(vendorId));
      if (!vendor) return;
      const confirmed = await showConfirmDialog({title:'Delete Vendor', message:`Permanently delete “${vendor.name}”? This is allowed only because it has no challan history.`, confirmText:'Delete Vendor', tone:'danger'});
      if (!confirmed) return;
      try {
        await postCutterVendorAction({action:'delete', id:vendorId});
        await loadCutterVendorManagement();
        await loadCutterServiceData();
        showToast('success', 'Vendor deleted.');
      } catch (error) { showToast('error', error.message); }
    }

    async function saveCutterVendor(event) {
      event.preventDefault();
      const form = event.currentTarget;
      const button = form.querySelector('[type="submit"]');
      const isEditing = Boolean(form.elements.id.value);
      button.disabled = true;
      try {
        const payload = Object.fromEntries(new FormData(form));
        payload.action = isEditing ? 'update' : 'create';
        const data = await postCutterVendorAction(payload);
        document.getElementById('cutterVendorDialog').close();
        await Promise.all([loadCutterServiceData(), document.getElementById('cutterVendorManagerDialog').open ? loadCutterVendorManagement() : Promise.resolve()]);
        if (!isEditing && document.getElementById('challanVendor')) document.getElementById('challanVendor').value = String(data.id);
        showToast('success', isEditing ? 'Vendor details updated.' : 'Service vendor added.');
      } catch (error) { showToast('error', error.message); }
      finally { button.disabled = false; }
    }

    async function openCutterChallanDialog() {
      await loadCutterServiceData();
      populateChallanVendors();
      document.getElementById('cutterChallanForm').reset();
      document.querySelector('#cutterChallanForm [name="dispatch_date"]').value = today;
      renderChallanCutterChoices();
      document.getElementById('cutterChallanDialog').showModal();
    }

    function renderChallanCutterChoices() {
      const choices = document.getElementById('challanCutterChoices');
      const available = cutterRecords.filter(c => c.status === 'Active' && !c.deleted_at && !c.scrap_date && cutterAvailableIds.has(Number(c.id)));
      document.getElementById('challanCutterEmpty').textContent = available.length ? 'Choose the cutters being sent. Each physical cutter can be selected only once.' : 'No available active cutters. Add/activate a cutter or receive an outstanding challan first.';
      choices.innerHTML = available.map(c => `<div class="cutter-challan-choice"><label class="cutter-challan-check"><input type="checkbox" class="challan-cutter-check" value="${Number(c.id)}"><span><b>${escapeHtml(c.cutter_num)}</b><small>${escapeHtml(c.module || 'Module not set')}</small></span></label><label>Service<select class="challan-service-type" disabled><option value="Grinding">Grinding</option><option value="Coating">Coating</option><option value="Both">Both</option></select></label><label>Dispatch remarks<input class="challan-sent-remarks" maxlength="255" placeholder="Optional" disabled></label></div>`).join('');
      choices.querySelectorAll('.challan-cutter-check').forEach(check => check.addEventListener('change', () => {
        const row = check.closest('.cutter-challan-choice');
        row.querySelector('.challan-service-type').disabled = !check.checked;
        row.querySelector('.challan-sent-remarks').disabled = !check.checked;
      }));
    }

    async function createCutterChallan(event) {
      event.preventDefault();
      const form = event.currentTarget;
      const selected = [...form.querySelectorAll('.challan-cutter-check:checked')];
      if (!selected.length) { showToast('error', 'Select at least one cutter.'); return; }
      const data = Object.fromEntries(new FormData(form).entries());
      data.items = selected.map(check => {
        const row = check.closest('.cutter-challan-choice');
        return {cutter_id:Number(check.value), service_type:row.querySelector('.challan-service-type').value, sent_remarks:row.querySelector('.challan-sent-remarks').value};
      });
      const button = form.querySelector('[type="submit"]'); button.disabled = true;
      try {
        const response = await fetch(`${API}/cutter_service_challans.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to create challan.');
        document.getElementById('cutterChallanDialog').close();
        await loadCutterServiceData();
        showToast('success', `${result.challan_no} created. Selected cutters are now marked Out for Service.`);
        printCutterChallan(result.challan_id);
        refreshDailyCutters();
      } catch (error) { showToast('error', error.message); }
      finally { button.disabled = false; }
    }

    function openCutterReceiveDialog(itemId) {
      const item = cutterServiceData.items.find(row => Number(row.id) === Number(itemId));
      if (!item) return;
      const challan = cutterServiceData.challans.find(row => Number(row.id) === Number(item.challan_id));
      document.getElementById('cutterReceiveForm').reset();
      document.querySelector('#cutterReceiveForm [name="item_id"]').value = item.id;
      document.querySelector('#cutterReceiveForm [name="service_date"]').value = today;
      document.querySelector('#cutterReceiveForm [name="received_date"]').value = today;
      document.getElementById('cutterReceiveSubtitle').textContent = `${item.cutter_num} · ${challan?.challan_no || ''} · ${item.service_type}`;
      document.getElementById('cutterReceiveDialog').showModal();
    }

    async function receiveCutterService(event) {
      event.preventDefault();
      const form = event.currentTarget, button = form.querySelector('[type="submit"]'); button.disabled = true;
      try {
        const response = await fetch(`${API}/receive_cutter_service_item.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(Object.fromEntries(new FormData(form)))});
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'Unable to record return.');
        document.getElementById('cutterReceiveDialog').close();
        await Promise.all([loadCutterServiceData(), new Promise(resolve => { refreshDailyCutters(); resolve(); })]);
        loadCutters();
        showToast('success', data.service_event_id ? 'Cutter received; service added to Tool History.' : 'Cutter received without service history entry.');
      } catch (error) { showToast('error', error.message); }
      finally { button.disabled = false; }
    }

    function printCutterChallan(challanId) {
      const challan = cutterServiceData.challans.find(row => Number(row.id) === Number(challanId));
      if (!challan) return;
      const lines = cutterServiceData.items.filter(item => Number(item.challan_id) === Number(challanId));
      const popup = window.open('', '_blank', 'width=1000,height=800');
      if (!popup) { showToast('error', 'Allow pop-ups to print the challan.'); return; }
      const copies = ['COMPANY COPY', 'SECURITY COPY', 'VENDOR COPY'];
      const esc = value => escapeHtml(value == null || value === '' ? '-' : value);
      const detailMarkup = () => `<div class="details">
        <div><b>Challan No.:</b> ${esc(challan.challan_no)}</div><div><b>Challan Date:</b> ${esc(challan.dispatch_date)}</div>
        <div><b>Vendor:</b> ${esc(challan.vendor_name)}</div><div><b>Phone:</b> ${esc(challan.vendor_phone)}</div>
        <div class="address"><b>Address:</b> ${esc(challan.vendor_address)}</div><div><b>Expected Return:</b> ${esc(challan.expected_return_date)}</div>
      </div>`;
      const serviceLabel = value => value === 'Both' ? 'Coating, Grinding' : value;
      const rowsMarkup = subset => subset.map(row => `<tr><td>${row.serial}</td><td>${esc(row.item.cutter_num)}</td><td>${esc(row.item.cutter_module)}</td><td>${esc(serviceLabel(row.item.service_type))}</td><td>${esc(row.item.sent_remarks)}</td></tr>`).join('');
      const tableMarkup = subset => `<table class="challan-table"><colgroup><col class="sr"><col class="cutter"><col class="module"><col class="service"><col class="remarks"></colgroup><thead><tr><th>Sr.</th><th>Cutter Number</th><th>Module</th><th>Service Required</th><th>Remarks</th></tr></thead><tbody>${rowsMarkup(subset)}</tbody></table>`;
      const footerMarkup = () => `<div class="challan-footer"><p class="challan-remarks"><b>Challan Remarks:</b> ${esc(challan.remarks)}</p><div class="signatures"><span>Issued By</span><span>Vendor Received By</span><span>Authorized Signatory</span></div></div>`;
      const compactCopyMarkup = copy => `<section class="compact-copy"><header class="copy-heading"><h1>NU-TECK COUPLINGS PVT. LTD.</h1><h2>CUTTER SERVICE CHALLAN <span>- ${copy}</span></h2></header>${detailMarkup()}${tableMarkup(lines.map((item, index) => ({item, serial:index + 1})))}${footerMarkup()}</section>`;
      const pagedSheetMarkup = (copy, subset, pageNo, pageCount, lastPage) => `<section class="sheet paged-sheet"><header class="copy-heading"><h1>NU-TECK COUPLINGS PVT. LTD.</h1><h2>CUTTER SERVICE CHALLAN <span>- ${copy}${pageNo > 1 ? ' (CONTINUED)' : ''}</span></h2></header>${detailMarkup()}${tableMarkup(subset)}${lastPage ? footerMarkup() : ''}<div class="page-number">${copy} - Page ${pageNo} of ${pageCount}</div></section>`;
      const printCss = `
        *{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#000;font:10pt Arial,Helvetica,sans-serif}
        #printRoot{width:198mm;margin:0 auto}.sheet{position:relative;width:198mm;height:285mm;overflow:hidden;background:#fff;page-break-after:always;break-after:page}
        .sheet:last-child{page-break-after:auto;break-after:auto}.copy-heading{text-align:center;border-bottom:1px solid #000;padding:0 0 2mm;margin:0 0 2mm}
        .copy-heading h1{font-size:13pt;line-height:1.15;margin:0 0 1mm;font-weight:700}.copy-heading h2{font-size:11pt;line-height:1.1;margin:0;font-weight:700}.copy-heading h2 span{font-size:10pt;font-weight:900;text-transform:uppercase;letter-spacing:.2px}
        .details{display:grid;grid-template-columns:1fr 1fr;gap:1mm 4mm;margin:0 0 2mm;font-size:9pt;line-height:1.25}.details>div{min-width:0;overflow-wrap:anywhere}.details .address{grid-column:1/-1}
        .challan-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:9pt}.challan-table th,.challan-table td{border:.25mm solid #000;padding:1.2mm 1.5mm;text-align:left;vertical-align:top;overflow-wrap:anywhere;word-break:normal}.challan-table th{font-weight:700}.challan-table .sr{width:7%}.challan-table .cutter{width:21%}.challan-table .module{width:16%}.challan-table .service{width:20%}.challan-table .remarks{width:36%}.challan-table tr{break-inside:avoid;page-break-inside:avoid}
        .challan-footer{margin-top:3mm}.challan-remarks{margin:0 0 3mm;overflow-wrap:anywhere}.signatures{display:flex;justify-content:space-between;gap:5mm;margin-top:12mm}.signatures span{width:31%;border-top:.25mm solid #000;padding-top:1.5mm;text-align:center;font-size:8pt}
        .compact-sheet{height:285mm;display:flex;flex-direction:column}.compact-copy{height:95mm;flex:0 0 95mm;overflow:hidden;padding:2mm 1mm 1mm;display:flex;flex-direction:column;border-bottom:.35mm dashed #555}.compact-copy:last-child{border-bottom:0}.compact-copy .copy-heading{padding-bottom:1mm;margin-bottom:1mm}.compact-copy .copy-heading h1{font-size:10pt;margin-bottom:.5mm}.compact-copy .copy-heading h2{font-size:8.5pt}.compact-copy .copy-heading h2 span{font-size:8pt}.compact-copy .details{font-size:7pt;gap:.5mm 2mm;margin-bottom:1mm;line-height:1.15}.compact-copy .challan-table{font-size:7pt}.compact-copy .challan-table th,.compact-copy .challan-table td{padding:.65mm .9mm;line-height:1.1}.compact-copy .challan-footer{margin-top:auto}.compact-copy .challan-remarks{font-size:7pt;margin-bottom:1mm}.compact-copy .signatures{gap:2mm;margin-top:5mm}.compact-copy .signatures span{font-size:6.5pt;padding-top:.8mm}
        .compact-sheet.roomy .copy-heading h1{font-size:11pt}.compact-sheet.roomy .copy-heading h2{font-size:9.5pt}.compact-sheet.roomy .copy-heading h2 span{font-size:9pt}.compact-sheet.roomy .details,.compact-sheet.roomy .challan-table,.compact-sheet.roomy .challan-remarks{font-size:8pt}.compact-sheet.roomy .challan-table th,.compact-sheet.roomy .challan-table td{padding:.9mm 1.2mm}.compact-sheet.roomy .signatures{margin-top:7mm}.compact-sheet.roomy .signatures span{font-size:7.5pt}
        .paged-sheet{display:flex;flex-direction:column}.paged-sheet .challan-footer{margin-top:auto;padding-top:4mm}.page-number{margin-top:auto;padding-top:2mm;text-align:right;font-size:8pt}
        #measureHost{position:absolute;left:-10000px;top:0;width:198mm;visibility:hidden;pointer-events:none}
        @page{size:A4 portrait;margin:6mm}
        @media print{html,body{width:198mm;margin:0!important;padding:0!important;background:#fff!important}.no-print{display:none!important}.sheet{margin:0!important}.sheet:not(:last-child){page-break-after:always;break-after:page}.challan-table tr,.signatures{break-inside:avoid;page-break-inside:avoid}}
      `;
      popup.document.open();
      popup.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${esc(challan.challan_no)} - Cutter Service Challan</title><style>${printCss}</style></head><body><main id="printRoot"></main><div id="measureHost"></div></body></html>`);
      popup.document.close();
      const printRows = lines.map((item, index) => ({item, serial:index + 1}));
      const print = async () => {
        if (lines.length <= 8) {
          const compactSize = lines.length <= 5 ? 'roomy' : 'dense';
          popup.document.getElementById('printRoot').innerHTML = `<section class="sheet compact-sheet ${compactSize}">${copies.map(compactCopyMarkup).join('')}</section>`;
          await new Promise(resolve => popup.requestAnimationFrame(() => popup.requestAnimationFrame(resolve)));
          const compactCopies = [...popup.document.querySelectorAll('.compact-copy')];
          const allFit = compactCopies.every(copy => copy.scrollHeight <= copy.clientHeight + 2);
          if (allFit) {
            popup.document.getElementById('measureHost').remove();
            popup.focus();
            popup.print();
            return;
          }
        }

        const measureHost = popup.document.getElementById('measureHost');
        const paginateCopy = copy => {
          const pages = [];
          let current = [];
          const fits = subset => {
            measureHost.innerHTML = pagedSheetMarkup(copy, subset, 1, 1, true);
            const sheet = measureHost.firstElementChild;
            return sheet.scrollHeight <= sheet.clientHeight + 2;
          };
          for (const row of printRows) {
            const candidate = [...current, row];
            if (current.length && !fits(candidate)) {
              pages.push(current);
              current = [row];
            } else {
              current = candidate;
            }
          }
          if (current.length || !pages.length) pages.push(current);
          return pages;
        };
        const sheets = [];
        for (const copy of copies) {
          const pages = paginateCopy(copy);
          pages.forEach((pageRows, index) => sheets.push(pagedSheetMarkup(copy, pageRows, index + 1, pages.length, index === pages.length - 1)));
        }
        measureHost.remove();
        popup.document.getElementById('printRoot').innerHTML = sheets.join('');
        await new Promise(resolve => popup.requestAnimationFrame(() => popup.requestAnimationFrame(resolve)));
        popup.focus();
        popup.print();
      };
      const fontsReady = popup.document.fonts?.ready || Promise.resolve();
      fontsReady.then(print).catch(() => print());
    }

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
      const rows = events.map(event => `<tr><td>${escapeHtml(event.event_date)}</td><td>${escapeHtml(event.event_type)}</td><td>${escapeHtml(event.qty_since_previous)}</td><td>${escapeHtml(event.challan_no || '-')}</td><td>${escapeHtml(event.vendor_name || '-')}</td><td>${escapeHtml(event.remarks || '-')}</td></tr>`).join('');
      const productionRows = monitoring.rows.map(row => `<tr><td>${escapeHtml(row.machine_no)}</td><td>${escapeHtml(row.part_no)}</td><td>${escapeHtml(row.part_name)}</td><td>${escapeHtml(row.qty)}</td></tr>`).join('');
      document.getElementById('cutterToolContent').innerHTML = `
        <div class="grid management-grid cutter-tool-filters"><label>Report Period<select id="toolMonitoringPeriod"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="yearly">Yearly</option></select></label><label id="toolMonitoringValueLabel">Date<input id="toolMonitoringValue" type="date" value="${escapeHtml(monitoring.value)}"></label><button id="loadToolMonitoringDate" class="secondary-button" type="button">Load Report</button></div>
        <div class="cutter-print-sheet"><h2>Tool History Card</h2><p><b>Cutter Name:</b> ${escapeHtml(cutter.cutter_name || cutter.cutter_type || '-')} &nbsp; <b>Cutter No.:</b> ${escapeHtml(cutter.cutter_num)} &nbsp; <b>Module/Pitch:</b> ${escapeHtml(cutter.cutter_module || '-')} &nbsp; <b>Period:</b> ${escapeHtml(monitoring.label)}</p>
        <p><b>Start Using Date:</b> ${escapeHtml(cutter.first_used_date || '-')} &nbsp; <b>Cutter Number of Teeth:</b> ${escapeHtml(cutter.cutter_tooth_count || '-')} &nbsp; <b>Cutter Tooth Thickness:</b> ${escapeHtml(cutter.cutter_tooth_thickness || '-')}</p>
        <p><b>Purchase Date:</b> ${escapeHtml(cutter.purchase_date || '-')} &nbsp; <b>Scrap Date:</b> ${escapeHtml(cutter.scrap_date || '-')}</p>
        <h3>Coating / Grinding Events in Selected Period</h3><div class="table-scroll"><table class="report-table"><thead><tr><th>Date</th><th>Service</th><th>Qty. Since Previous Service</th><th>Challan No.</th><th>Vendor</th><th>Remarks</th></tr></thead><tbody>${rows || '<tr><td colspan="6">No coating or grinding events in the selected period.</td></tr>'}</tbody></table></div>
        <h3>Production During Selected Period</h3><div class="table-scroll"><table class="report-table"><thead><tr><th>Machine No.</th><th>Part No.</th><th>Part Name</th><th>Qty.</th></tr></thead><tbody>${productionRows || '<tr><td colspan="4">No production entries in the selected period.</td></tr>'}</tbody><tfoot><tr><th colspan="3">Period Total</th><th>${escapeHtml(monitoring.total_qty)}</th></tr></tfoot></table></div>
        <p><b>Current service cycle since ${escapeHtml(current_cycle.from_date || 'first use')} (overall):</b> ${escapeHtml(current_cycle.qty)} pcs</p></div>
        <p class="muted">Service events are added automatically when a cutter is received from an issued challan and service is confirmed.</p><div class="dialog-actions"><button class="secondary-button" type="button" onclick="printCutterHistory()">Print / Save PDF</button></div>`;
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
