// Extracted from index.html during Phase 2 frontend separation.
    function fetchData(url, selectId, optionMapper) {
      return fetch(url)
        .then(r => r.json())
        .then(data => {
          const select = document.getElementById(selectId);
          data.forEach(item => {
            select.insertAdjacentHTML('beforeend', optionMapper(item));
          });
        })
        .catch(err => console.error(`Error loading ${url}`, err));
    }

    function fetchParts() {
      return fetch(`${API}/get_parts.php`)
        .then(r => r.json())
        .then(data => { parts = data; })
        .catch(err => console.error('Error loading parts', err));
    }

    function refreshMachineOptions() {
      return fetch(API + '/get_machines.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load machines.');
          return data;
        })
        .then(machines => {
          [
            ['machine_id', '-- select --', true, true],
            ['reportMachine', 'All Machines', false, true],
            ['weeklyPlanMachine', '-- select --', false, typeof weeklyPlanInitialized !== 'undefined' && weeklyPlanInitialized]
          ].forEach(([id, firstLabel, includeOperation, enabled]) => {
            if (!enabled) return;
            const select = document.getElementById(id);
            if (!select) return;
            const previous = select.value;
            select.innerHTML = '<option value="">' + firstLabel + '</option>';
            machines.forEach(machine => {
              const operation = includeOperation ? ' data-operation="' + escapeHtml(machine.default_operation) + '"' : '';
              select.insertAdjacentHTML('beforeend', '<option value="' + Number(machine.id) + '" data-code="' + escapeHtml(machine.code) + '"' + operation + '>' + escapeHtml(machine.code) + ' — ' + escapeHtml(machine.name) + '</option>');
            });
            if ([...select.options].some(option => option.value === previous)) select.value = previous;
          });
        });
    }

    function refreshDailyCutters() {
      const select = document.getElementById('cutter_id');
      select.innerHTML = '<option value="">-- select --</option>';
      return fetchData(`${API}/get_cutters.php`, 'cutter_id', c => `<option value="${c.id}">${c.cutter_num}</option>`);
    }

    function refreshShiftOptions() {
      return fetch(API + '/get_shifts.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load shifts.');
          return data;
        })
        .then(shifts => {
          activeShifts = shifts;
          [
            ['shift', '-- select --'],
            ['handover_shift', '-- select --'],
            ['weeklyPlanShift', '-- select --'],
            ['reportShift', 'All Shifts']
          ].forEach(([id, firstLabel]) => {
            const select = document.getElementById(id);
            const previous = select.value;
            select.innerHTML = '<option value="">' + firstLabel + '</option>';
            shifts.forEach(shift => select.insertAdjacentHTML('beforeend',
              '<option value="' + escapeHtml(shift.code) + '" data-hours="' + Number(shift.shift_hours) + '">' + escapeHtml(shift.label) + '</option>'));
            if ([...select.options].some(option => option.value === previous)) select.value = previous;
          });
        });
    }
