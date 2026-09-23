// Extracted from index.html during Phase 2 frontend separation.
    function setStartJobFieldsEnabled(enabled) {
      startJobAvailable = enabled;
      ['entry_date', 'shift', 'coupling_type', 'drg_no', 'operation_select', 'operation', 'cutter_id'].forEach(id => {
        document.getElementById(id).disabled = !enabled;
      });
      document.getElementById('operator_id').disabled = !enabled || currentUser?.role === 'Operator/Supervisor';
      partSelect.disabled = !enabled || !couplingTypeSelect.value;
      componentSelect.disabled = !enabled || !partSelect.value;
      submitBtn.disabled = !enabled;
    }

    function checkMachineAvailability() {
      const machineId = machineSelect.value;
      const requestSequence = ++machineCheckSequence;
      setStartJobFieldsEnabled(false);
      plannedQtyInput.value = 0;

      if (!machineId) {
        machineAvailabilityBanner.className = 'banner error';
        machineAvailabilityBanner.innerText = 'Select a machine to check availability.';
        return;
      }

      machineAvailabilityBanner.className = 'banner success';
      machineAvailabilityBanner.innerText = 'Checking machine availability...';
      fetch(`${API}/get_machine_status.php?machine_id=${encodeURIComponent(machineId)}`)
        .then(async response => {
          const result = await response.json();
          if (!response.ok) throw new Error(result.error || 'Unable to check machine');
          return result;
        })
        .then(result => {
          if (requestSequence !== machineCheckSequence || machineSelect.value !== machineId) return;
          if (result.runtime_status === 'Available') {
            setStartJobFieldsEnabled(true);
            applyOperatorAccess();
            machineAvailabilityBanner.className = 'banner success';
            machineAvailabilityBanner.innerText = `${result.code} is Available. Fill the job details.`;
          } else {
            machineAvailabilityBanner.className = 'banner error';
            machineAvailabilityBanner.innerText = `${result.code} already has a ${result.runtime_status} job${result.operator_name ? ` with ${result.operator_name}` : ''}.`;
          }
        })
        .catch(error => {
          if (requestSequence !== machineCheckSequence) return;
          machineAvailabilityBanner.className = 'banner error';
          machineAvailabilityBanner.innerText = error.message;
        });
    }

    function updateShiftHours() {
      const option = shiftSelect.selectedOptions[0];
      shiftHoursInput.value = option?.dataset.hours || '';
    }

    shiftSelect.addEventListener('change', updateShiftHours);

    function updateHandoverShiftHours() {
      const select = document.getElementById('handover_shift');
      const option = select.selectedOptions[0];
      document.getElementById('handover_shift_hours').value = option?.dataset.hours || '';
    }

    document.getElementById('handover_shift').addEventListener('change', updateHandoverShiftHours);

    function updatePlannedQty() {
      const machineId = machineSelect.value;
      const partId = partSelect.value;
      plannedQtyInput.value = 0;
      if (!machineId || !partId) return;

      fetch(`${API}/get_planned_qty.php?machine_id=${encodeURIComponent(machineId)}&part_id=${encodeURIComponent(partId)}`)
        .then(r => r.json())
        .then(result => {
          if (machineSelect.value === machineId && partSelect.value === partId) {
            plannedQtyInput.value = Number(result.planned_qty || 0);
          }
        })
        .catch(err => console.error('Error loading planned quantity', err));
    }

    function operationForMachineCode(machineCode) {
      const code = String(machineCode || '').trim().toUpperCase();
      if (code.startsWith('HOB')) return 'Hobbing';
      if (code.startsWith('SHP')) return 'Shaping';
      if (code.startsWith('VTL')) return 'Turning';
      if (code.startsWith('DRILL')) return 'Drilling';
      if (code.startsWith('KEYWAY')) return 'Keyway';
      return '';
    }

    function configureOperationControl(selectId, inputId, machineCode) {
      const select = document.getElementById(selectId);
      const input = document.getElementById(inputId);
      const automaticOperation = operationForMachineCode(machineCode);
      select.innerHTML = automaticOperation
        ? '<option value="' + automaticOperation + '">' + automaticOperation + '</option><option value="Other">Other</option>'
        : '<option value="">-- no automatic operation --</option><option value="Other">Other</option>';
      select.value = automaticOperation;
      input.value = automaticOperation;
      input.required = false;
      input.classList.add('hidden');
    }

    function handleOperationSelection(selectId, inputId) {
      const select = document.getElementById(selectId);
      const input = document.getElementById(inputId);
      if (select.value === 'Other') {
        input.value = '';
        input.required = true;
        input.classList.remove('hidden');
        input.focus();
      } else {
        input.value = select.value;
        input.required = false;
        input.classList.add('hidden');
      }
    }

    function updateStartOperationForMachine() {
      const selectedOption = machineSelect.options[machineSelect.selectedIndex];
      configureOperationControl('operation_select', 'operation', selectedOption?.dataset.code || '');
    }

    document.getElementById('operation_select').addEventListener('change', () => {
      handleOperationSelection('operation_select', 'operation');
    });
    document.getElementById('setting_new_operation_select').addEventListener('change', () => {
      handleOperationSelection('setting_new_operation_select', 'setting_new_operation');
    });


    machineSelect.addEventListener('change', () => {
      updateStartOperationForMachine();
      updatePlannedQty();
      checkMachineAvailability();
    });

    function setActiveJobValue(id, value) {
      document.getElementById(id).innerText = value ?? '-';
    }

    function loadActiveJob(jobId) {
      switchModule('active');
      const message = document.getElementById('activeJobMessage');
      const endPanel = document.getElementById('endShiftPanel');
      const handoverSummary = document.getElementById('handoverSummary');
      const acceptPanel = document.getElementById('acceptHandoverPanel');
      const machineActionPanel = document.getElementById('machineActionPanel');
      const machineActionSelect = document.getElementById('machineActionSelect');
      const cutterChangePanel = document.getElementById('cutterChangePanel');
      const cutterChangeProgress = document.getElementById('cutterChangeProgress');
      const settingChangePanel = document.getElementById('settingChangePanel');
      const settingChangeProgress = document.getElementById('settingChangeProgress');
      const readOnly = document.getElementById('endShiftReadOnlyMessage');
      message.className = 'banner success';
      message.innerText = 'Loading active job...';
      endPanel.classList.add('hidden');
      handoverSummary.classList.add('hidden');
      acceptPanel.classList.add('hidden');
      machineActionPanel.classList.add('hidden');
      machineActionSelect.value = '';
      cutterChangePanel.classList.add('hidden');
      cutterChangeProgress.classList.add('hidden');
      settingChangePanel.classList.add('hidden');
      settingChangeProgress.classList.add('hidden');
      readOnly.className = 'banner';

      fetch(`${API}/get_active_job.php?job_id=${encodeURIComponent(jobId)}`)
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load job details.');
          return data;
        })
        .then(job => {
          message.className = 'banner';
          setActiveJobValue('activeMachine', `${job.machine_code} — ${job.machine_name}`);
          const displayedStatus = job.setting_change_id !== null
            ? 'Setting Change'
            : (job.cutter_change_id !== null ? 'Cutter Change' : job.status);
          setActiveJobValue('activeStatus', displayedStatus);
          document.getElementById('activeStatus').className = `machine-status job-status-${statusClass(displayedStatus)}`;
          setActiveJobValue('activeOperator', job.operator_name || '-');
          const rawShift = job.shift || job.current_shift;
          const shiftLabel = ['1', '2', '3'].includes(String(rawShift)) ? `Shift ${rawShift}` : rawShift;
          setActiveJobValue('activeShift', rawShift ? `${shiftLabel}${job.shift_hours ? ` (${job.shift_hours} hours)` : ''}` : '-');
          setActiveJobValue('activePart', `${job.coupling_type} — ${job.part_name}`);
          setActiveJobValue('activeComponent', job.component || '-');
          setActiveJobValue('activeOperation', job.operation || '-');
          setActiveJobValue('activeCutter', job.cutter_num || '-');
          setActiveJobValue('activePlanned', Number(job.planned_qty || 0));
          setActiveJobValue('activeCumulative', Number(job.cumulative_ok_qty || 0));
          setActiveJobValue('activePending', Number(job.pending_qty || 0));
          setActiveJobValue('activeStarted', formatDashboardTime(job.started_at));
          setActiveJobValue('activeDuration', formatRunningTime(job.started_at));

          if (job.cutter_change_id !== null) {
            cutterChangeProgress.dataset.jobId = job.id;
            setActiveJobValue('changingOldCutter', job.old_cutter_num || 'No cutter assigned');
            setActiveJobValue('changingNewCutter', job.new_cutter_num || '-');
            setActiveJobValue('changingReason', job.cutter_change_reason || '-');
            setActiveJobValue('changingStarted', formatDashboardTime(job.cutter_change_started_at));
            setActiveJobValue('changingDowntime', formatAlertDuration(job.cutter_change_elapsed_minutes));
            setActiveJobValue('changingRemarks', job.cutter_change_remarks || '-');
            document.getElementById('completeCutterChangeButton').classList.toggle('hidden', !job.can_complete_cutter_change);
            cutterChangeProgress.classList.remove('hidden');
          }

          if (job.setting_change_id !== null) {
            settingChangeProgress.dataset.jobId = job.id;
            setActiveJobValue('settingChangingPart', `${job.setting_new_coupling_type} — ${job.setting_new_part_name}`);
            setActiveJobValue('settingChangingComponent', job.setting_new_component || '-');
            setActiveJobValue('settingChangingOperation', job.setting_new_operation || '-');
            setActiveJobValue('settingChangingCutter', job.setting_new_cutter_num || 'No cutter assigned');
            setActiveJobValue('settingChangingReason', job.setting_change_reason || '-');
            setActiveJobValue('settingChangingStarted', formatDashboardTime(job.setting_change_started_at));
            setActiveJobValue('settingChangingDowntime', formatAlertDuration(job.setting_change_elapsed_minutes));
            setActiveJobValue('settingChangingRemarks', job.setting_change_remarks || '-');
            document.getElementById('completeSettingChangeButton').classList.toggle('hidden', !job.can_complete_setting_change);
            settingChangeProgress.classList.remove('hidden');
          }
          if (job.last_shift_id !== null) {
            setActiveJobValue('handoverPreviousOperator', job.last_operator_name || '-');
            setActiveJobValue('handoverEndedAt', formatDashboardTime(job.last_shift_ended_at));
            setActiveJobValue('handoverTotal', Number(job.last_total_qty || 0));
            setActiveJobValue('handoverOk', Number(job.last_ok_qty || 0));
            setActiveJobValue('handoverReject', Number(job.last_mc_reject_qty || 0));
            setActiveJobValue('handoverDefect', Number(job.last_rm_defect_qty || 0));
            setActiveJobValue('handoverRework', Number(job.last_rework_qty || 0));
            setActiveJobValue('handoverDowntime', `${Number(job.last_downtime_min || 0)} min`);
            setActiveJobValue('handoverIssue', job.last_issue_code || '-');
            setActiveJobValue('handoverRemarks', job.last_remarks || '-');
            handoverSummary.classList.remove('hidden');
          }

          document.getElementById('machineActionCutter').disabled = !job.can_start_cutter_change;
          document.getElementById('machineActionSetting').disabled = !job.can_start_setting_change;
          if (job.can_start_cutter_change) {
            cutterChangePanel.reset();
            document.getElementById('cutter_change_job_id').value = job.id;
            document.getElementById('old_cutter_num').value = job.cutter_num || 'No cutter assigned';
            loadCutterChangeOptions(job.cutter_id);
          }

          if (job.can_start_setting_change) {
            initializeSettingChangeForm(job);
          }

          if (job.can_start_cutter_change || job.can_start_setting_change) {
            machineActionPanel.classList.remove('hidden');
          }

          if (job.can_end_shift) {
            document.getElementById('endShiftPanel').reset();
            document.getElementById('end_job_id').value = job.id;
            endPanel.classList.remove('hidden');
            readOnly.className = 'banner';
          } else if (job.can_accept_handover) {
            acceptPanel.reset();
            document.getElementById('handover_job_id').value = job.id;
            document.getElementById('handover_operator').value = currentUser.operator_name || currentUser.username;
            document.getElementById('handover_shift_date').value = today;
            updateHandoverShiftHours();
            acceptPanel.classList.remove('hidden');
            readOnly.className = 'banner';
          } else {
            readOnly.className = 'banner success';
            if (currentUser?.role === 'Admin') {
              readOnly.innerText = 'Admin / Director monitoring view. Production changes are disabled.';
            } else if (job.setting_change_id !== null) {
              readOnly.innerText = 'Complete the setting change to close this job and start the new job.';
            } else if (job.cutter_change_id !== null) {
              readOnly.innerText = 'Complete the cutter change before ending this shift.';
            } else if (job.status === 'Handover Pending') {
              readOnly.innerText = 'Shift ended. The next operator must log in and accept this handover.';
            } else if (job.status !== 'Running') {
              readOnly.innerText = `This job is ${job.status}. End Shift is not available.`;
            } else {
              readOnly.innerText = `Only ${job.operator_name || 'the current operator'} can end this shift.`;
            }
          }
        })
        .catch(error => {
          message.className = 'banner error';
          message.innerText = error.message;
        });
    }

    function handleMachineActionChange() {
      const action = document.getElementById('machineActionSelect').value;
      const cutterPanel = document.getElementById('cutterChangePanel');
      const settingPanel = document.getElementById('settingChangePanel');
      cutterPanel.classList.toggle('hidden', action !== 'cutter');
      settingPanel.classList.toggle('hidden', action !== 'setting');
    }


    function handleAcceptHandover(event) {
      event.preventDefault();
      const form = event.target;
      const button = document.getElementById('acceptHandoverButton');
      const data = Object.fromEntries(new FormData(form).entries());
      button.disabled = true;
      button.innerText = 'Accepting Handover...';

      fetch(`${API}/accept_handover.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to accept handover.');
          dashboardFlashMessage = `Handover accepted. ${result.data.operator_name} started ${['1', '2', '3'].includes(String(result.data.shift)) ? `Shift ${result.data.shift}` : result.data.shift}.`;
          switchModule('dashboard');
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Accept Handover & Start Shift';
        });
    }

    function handleEndShift(event) {
      event.preventDefault();
      const form = event.target;
      const data = Object.fromEntries(new FormData(form).entries());
      const quantityIds = ['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty', 'downtime_min'];
      quantityIds.forEach(field => { data[field] = Number(data[field] || 0); });
      const accounted = data.ok_qty + data.mc_reject_qty + data.rm_defect_qty + data.rework_qty;
      const qtyError = document.getElementById('endShiftQtyError');
      if (accounted > data.total_qty) {
        qtyError.className = 'banner error';
        qtyError.innerText = 'OK + Reject + Defect + Rework cannot exceed Total Qty.';
        return;
      }
      qtyError.className = 'banner';
      const button = document.getElementById('endShiftButton');
      button.disabled = true;
      button.innerText = 'Ending Shift...';

      fetch(`${API}/end_shift.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
        .then(async response => ({ ok: response.ok, data: await response.json() }))
        .then(result => {
          if (!result.ok) throw new Error(result.data.error || 'Unable to end shift.');
          form.reset();
          dashboardFlashMessage = `Shift ended successfully. Job status: ${result.data.status}.`;
          switchModule('dashboard');
        })
        .catch(error => {
          const message = document.getElementById('activeJobMessage');
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'End Shift';
        });
    }

    couplingTypeSelect.addEventListener('change', () => {
      const selectedType = couplingTypeSelect.value;
      partSelect.innerHTML = '<option value="">-- select --</option>';
      parts
        .filter(part => part.coupling_type === selectedType)
        .forEach(part => {
          const option = document.createElement('option');
          option.value = part.id;
          option.textContent = part.part_name;
          partSelect.appendChild(option);
        });
      partSelect.disabled = !startJobAvailable || !selectedType;
      componentSelect.innerHTML = '<option value="">-- select part first --</option>';
      componentSelect.disabled = true;
      updatePlannedQty();
    });

    partSelect.addEventListener('change', () => {
      componentSelect.innerHTML = '<option value="">-- select --</option>';
      componentSelect.disabled = true;
      updatePlannedQty();
      if (!partSelect.value) return;

      fetch(`${API}/get_part_components.php?part_id=${encodeURIComponent(partSelect.value)}`)
        .then(r => r.json())
        .then(components => {
          components.forEach(component => {
            const option = document.createElement('option');
            option.value = component.component_name;
            option.textContent = component.component_name;
            componentSelect.appendChild(option);
          });
          componentSelect.disabled = !startJobAvailable || components.length === 0;
        })
        .catch(err => console.error('Error loading components', err));
    });


    function showMessage(type, text) {
      msgBanner.className = `banner ${type}`;
      msgBanner.innerText = text;
    }

    function hideMessage() {
      msgBanner.className = 'banner';
      qtyErrBanner.style.display = 'none';
    }

    function handleSubmit(e) {
      e.preventDefault();
      hideMessage();

      if (!startJobAvailable) {
        showMessage('error', 'Select an Available machine before starting a job.');
        return;
      }

      // Collect data
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData.entries());
      if (currentUser?.role === 'Operator/Supervisor') {
        data.operator_id = String(currentUser.operator_id || '');
      }

      // Basic validation
      let isValid = true;
      ['machine_id', 'operator_id', 'coupling_type', 'part_id', 'component'].forEach(field => {
        const errSpan = document.getElementById(`err_${field}`);
        if (!data[field]) {
          errSpan.style.display = 'inline';
          isValid = false;
        } else {
          errSpan.style.display = 'none';
        }
      });

      if (!isValid) return;

      submitBtn.disabled = true;
      submitBtn.innerText = "Starting Job...";

      fetch(`${API}/start_job.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      })
      .then(async response => ({ ok: response.ok, data: await response.json() }))
      .then(result => {
        if (result.ok && result.data.success) {
          e.target.reset();
          document.getElementById('entry_date').value = today;
          updateShiftHours();
          partSelect.innerHTML = '<option value="">-- select coupling type first --</option>';
          partSelect.disabled = true;
          componentSelect.innerHTML = '<option value="">-- select part first --</option>';
          componentSelect.disabled = true;
          plannedQtyInput.value = 0;
          applyOperatorAccess();
          machineAvailabilityBanner.className = 'banner error';
          machineAvailabilityBanner.innerText = 'Select a machine to check availability.';
          setStartJobFieldsEnabled(false);
          switchModule('dashboard');
        } else {
          showMessage('error', result.data.error || 'Unable to start job.');
          checkMachineAvailability();
        }
      })
      .catch(err => {
        showMessage('error', 'Network error while starting job.');
        checkMachineAvailability();
      })
      .finally(() => {
        submitBtn.innerText = 'Start New Job';
        if (startJobAvailable) submitBtn.disabled = false;
      });
    }
