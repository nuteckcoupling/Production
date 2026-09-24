// Extracted from jobs.js during Phase 2 frontend separation.
    function loadCutterChangeOptions(currentCutterId) {
      const select = document.getElementById('new_cutter_id');
      select.innerHTML = '<option value="">-- select new cutter --</option>';
      fetch(`${API}/get_cutters.php`)
        .then(response => response.json())
        .then(cutters => {
          cutters.forEach(cutter => {
            if (Number(cutter.id) === Number(currentCutterId)) return;
            const option = document.createElement('option');
            option.value = cutter.id;
            option.textContent = cutter.cutter_num;
            select.appendChild(option);
          });
        })
        .catch(() => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = 'Unable to load active cutters.';
        });
    }

    function handleStartCutterChange(event) {
      event.preventDefault();
      const form = event.target;
      const button = document.getElementById('startCutterChangeButton');
      const data = Object.fromEntries(new FormData(form).entries());
      button.disabled = true;
      button.innerText = 'Starting Cutter Change...';
      fetch(`${API}/start_cutter_change.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to start cutter change.');
          loadActiveJob(data.job_id);
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Start Cutter Change';
        });
    }

    function handleCompleteCutterChange() {
      const progress = document.getElementById('cutterChangeProgress');
      const jobId = progress.dataset.jobId;
      const button = document.getElementById('completeCutterChangeButton');
      if (!jobId) return;
      button.disabled = true;
      button.innerText = 'Completing Cutter Change...';
      fetch(`${API}/complete_cutter_change.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId })
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to complete cutter change.');
          loadActiveJob(jobId);
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Complete Cutter Change & Resume Job';
        });
    }

    function initializeSettingChangeForm(job) {
      const form = document.getElementById('settingChangePanel');
      form.reset();
      document.getElementById('setting_change_job_id').value = job.id;
      document.getElementById('setting_change_machine_id').value = job.machine_id;
      document.getElementById('setting_new_part_id').innerHTML = '<option value="">-- select coupling range first --</option>';
      document.getElementById('setting_new_part_id').disabled = true;
      document.getElementById('setting_new_component').innerHTML = '<option value="">-- select part first --</option>';
      document.getElementById('setting_new_component').disabled = true;
      document.getElementById('setting_new_planned_qty').value = 0;
      document.getElementById('settingChangeQtyError').className = 'banner';
      configureOperationControl('setting_new_operation_select', 'setting_new_operation', job.machine_code, job.machine_default_operation);
      loadSettingCutterOptions();
    }

    function loadSettingCutterOptions() {
      const select = document.getElementById('setting_new_cutter_id');
      select.innerHTML = '<option value="">-- no cutter --</option>';
      fetch(API + '/get_cutters.php')
        .then(response => response.json())
        .then(cutters => {
          cutters.forEach(cutter => {
            const option = document.createElement('option');
            option.value = cutter.id;
            option.textContent = cutter.cutter_num;
            select.appendChild(option);
          });
        })
        .catch(() => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = 'Unable to load active cutters.';
        });
    }

    function populateSettingParts() {
      const selectedType = document.getElementById('setting_new_coupling_type').value;
      const partSelect = document.getElementById('setting_new_part_id');
      const componentSelect = document.getElementById('setting_new_component');
      partSelect.innerHTML = '<option value="">-- select part --</option>';
      parts.filter(part => part.coupling_type === selectedType).forEach(part => {
        const option = document.createElement('option');
        option.value = part.id;
        option.textContent = part.part_name;
        partSelect.appendChild(option);
      });
      partSelect.disabled = !selectedType;
      componentSelect.innerHTML = '<option value="">-- select part first --</option>';
      componentSelect.disabled = true;
      document.getElementById('setting_new_planned_qty').value = 0;
    }

    function loadSettingComponentsAndPlan() {
      const partId = document.getElementById('setting_new_part_id').value;
      const machineId = document.getElementById('setting_change_machine_id').value;
      const componentSelect = document.getElementById('setting_new_component');
      componentSelect.innerHTML = '<option value="">-- select --</option>';
      componentSelect.disabled = true;
      document.getElementById('setting_new_planned_qty').value = 0;
      if (!partId) return;

      fetch(API + '/get_part_components.php?part_id=' + encodeURIComponent(partId))
        .then(response => response.json())
        .then(components => {
          if (document.getElementById('setting_new_part_id').value !== partId) return;
          components.forEach(component => {
            const option = document.createElement('option');
            option.value = component.component_name;
            option.textContent = component.component_name;
            componentSelect.appendChild(option);
          });
          componentSelect.disabled = components.length === 0;
        });

      fetch(API + '/get_planned_qty.php?machine_id=' + encodeURIComponent(machineId) + '&part_id=' + encodeURIComponent(partId))
        .then(response => response.json())
        .then(result => {
          if (document.getElementById('setting_new_part_id').value === partId) {
            document.getElementById('setting_new_planned_qty').value = Number(result.planned_qty || 0);
          }
        });
    }

    document.getElementById('setting_new_coupling_type').addEventListener('change', populateSettingParts);
    document.getElementById('setting_new_part_id').addEventListener('change', loadSettingComponentsAndPlan);

    function handleStartSettingChange(event) {
      event.preventDefault();
      const form = event.target;
      const data = Object.fromEntries(new FormData(form).entries());
      ['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty'].forEach(field => {
        data[field] = Number(data[field] || 0);
      });
      const accounted = data.ok_qty + data.mc_reject_qty + data.rm_defect_qty + data.rework_qty;
      const qtyError = document.getElementById('settingChangeQtyError');
      if (accounted > data.total_qty) {
        qtyError.className = 'banner error';
        qtyError.innerText = 'OK + Reject + Defect + Rework cannot exceed Total Qty.';
        return;
      }
      qtyError.className = 'banner';

      const button = document.getElementById('startSettingChangeButton');
      button.disabled = true;
      button.innerText = 'Starting Setting Change...';
      fetch(API + '/start_setting_change.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to start setting change.');
          loadActiveJob(data.job_id);
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Start Setting Change';
        });
    }

    function handleCompleteSettingChange() {
      const progress = document.getElementById('settingChangeProgress');
      const jobId = progress.dataset.jobId;
      const button = document.getElementById('completeSettingChangeButton');
      if (!jobId) return;
      button.disabled = true;
      button.innerText = 'Completing Setting...';
      fetch(API + '/complete_setting_change.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId })
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to complete setting change.');
          dashboardFlashMessage = 'Setting completed. New job started successfully. Downtime: '
            + Number(result.data.downtime_min || 0) + ' min.';
          switchModule('dashboard');
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Complete Setting & Start New Job';
        });
    }
