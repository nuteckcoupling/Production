// Extracted from index.html during Phase 2 frontend separation.
    function setReportPeriod(period) {
      currentReportPeriod = period;
      document.querySelectorAll('.report-tabs button').forEach(button => {
        button.classList.toggle('active', button.dataset.period === period);
      });
      document.getElementById('dailyReportFilter').classList.toggle('hidden', period !== 'daily');
      document.getElementById('weeklyReportFilter').classList.toggle('hidden', period !== 'weekly');
      document.getElementById('monthlyReportFilter').classList.toggle('hidden', period !== 'monthly');
      currentReportData = null;
      closeReportEntries();
      document.getElementById('downloadReportBtn').disabled = true;
      document.getElementById('printReportBtn').disabled = true;
    }

    function reportFilterValue() {
      if (currentReportPeriod === 'weekly') return document.getElementById('reportWeeklyDate').value;
      if (currentReportPeriod === 'monthly') return document.getElementById('reportMonthlyDate').value;
      return document.getElementById('reportDailyDate').value;
    }

    function populateReportParts() {
      const coupling = document.getElementById('reportCoupling').value;
      const partFilter = document.getElementById('reportPart');
      const previous = partFilter.value;
      partFilter.innerHTML = '<option value="">All Parts</option>';
      parts.filter(part => !coupling || part.coupling_type === coupling).forEach(part => {
        partFilter.insertAdjacentHTML('beforeend', `<option value="${Number(part.id)}">${escapeHtml(part.part_name)}</option>`);
      });
      if ([...partFilter.options].some(option => option.value === previous)) partFilter.value = previous;
    }

    document.getElementById('reportCoupling').addEventListener('change', populateReportParts);

    function loadProductionReport() {
      const value = reportFilterValue();
      const button = document.getElementById('loadReportBtn');
      const message = document.getElementById('reportMessage');
      if (!value) {
        message.className = 'banner error';
        message.innerText = 'Select the report date or month.';
        return;
      }
      button.disabled = true;
      button.innerText = 'Generating...';
      message.className = 'banner';

      const params = new URLSearchParams({ period: currentReportPeriod, value });
      const filters = {
        machine_id: document.getElementById('reportMachine').value,
        shift: document.getElementById('reportShift').value,
        operator_id: document.getElementById('reportOperator').value,
        coupling_type: document.getElementById('reportCoupling').value,
        part_id: document.getElementById('reportPart').value
      };
      Object.entries(filters).forEach(([key, filterValue]) => {
        if (filterValue) params.set(key, filterValue);
      });
      fetch(`${API}/get_production_report.php?${params.toString()}`)
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to generate report.');
          return data;
        })
        .then(data => {
          currentReportData = data;
          renderProductionReport(data);
          document.getElementById('downloadReportBtn').disabled = false;
          document.getElementById('printReportBtn').disabled = false;
        })
        .catch(error => {
          message.className = 'banner error';
          message.innerText = error.message;
        })
        .finally(() => {
          button.disabled = false;
          button.innerText = 'Generate Report';
        });
    }

    function renderProductionReport(data) {
      const totals = data.totals;
      document.getElementById('reportPeriodLabel').innerText = `${data.period.charAt(0).toUpperCase() + data.period.slice(1)} · ${data.period_label}`;
      document.getElementById('reportMachines').innerText = totals.machine_count;
      document.getElementById('reportPlanned').innerText = totals.planned_qty;
      document.getElementById('reportTotal').innerText = totals.total_qty;
      document.getElementById('reportOk').innerText = totals.ok_qty;
      document.getElementById('reportPending').innerText = totals.pending_qty;
      document.getElementById('reportAchievement').innerText = `${totals.achievement_percent}%`;
      document.getElementById('reportReject').innerText = totals.mc_reject_qty + totals.rm_defect_qty;
      document.getElementById('reportDowntime').innerText = `${totals.downtime_min} min`;
      document.getElementById('reportGeneratedAt').innerText = `Generated: ${formatDashboardTime(data.generated_at)}`;
      const tbody = document.getElementById('reportTableBody');
      if (!data.rows.length) {
        tbody.innerHTML = '<tr><td colspan="18" class="empty">No production records found for this period.</td></tr>';
        closeReportEntries();
        return;
      }
      tbody.innerHTML = data.rows.map(row => `<tr>
        <td><strong>${escapeHtml(row.machine_code)}</strong><br><span class="table-subtext">${escapeHtml(row.machine_name)}</span></td>
        <td>${escapeHtml(row.operators || '-')}</td><td>${escapeHtml(row.shifts || '-')}</td>
        <td>${escapeHtml(row.parts || '-')}</td><td>${escapeHtml(row.components || '-')}</td>
        <td>${Number(row.job_count)}</td><td>${Number(row.planned_qty)}</td><td>${Number(row.total_qty)}</td>
        <td>${Number(row.ok_qty)}</td><td>${Number(row.mc_reject_qty)}</td><td>${Number(row.rm_defect_qty)}</td>
        <td>${Number(row.rework_qty)}</td><td>${Number(row.pending_qty)}</td><td>${Number(row.downtime_min)} min</td>
        <td>${Number(row.achievement_percent)}%</td><td>${escapeHtml(row.statuses || '-')}</td>
        <td>${escapeHtml(row.remarks || '-')}</td>
        <td><button type="button" onclick="showReportEntries(${Number(row.machine_id)})">View Entries</button></td>
      </tr>`).join('');
    }

    function showReportEntries(machineId) {
      if (!currentReportData) return;
      const entries = (currentReportData.entries || []).filter(entry => Number(entry.machine_id) === Number(machineId));
      const machine = currentReportData.rows.find(row => Number(row.machine_id) === Number(machineId));
      document.getElementById('reportEntryTitle').innerText = `${machine?.machine_code || 'Machine'} Production Entries`;
      document.getElementById('reportEntryCount').innerText = `${entries.length} individual shift record${entries.length === 1 ? '' : 's'}`;
      document.getElementById('reportEntryTableBody').innerHTML = entries.map(entry => `<tr>
        <td>${escapeHtml(entry.shift_date)}</td>
        <td>${escapeHtml(entry.shift)}<br><span class="table-subtext">${Number(entry.shift_hours)} hrs</span></td>
        <td>${escapeHtml(entry.operator_name)}</td>
        <td>#${Number(entry.job_id)}<br><span class="table-subtext">Entry #${Number(entry.shift_id)}</span></td>
        <td>${escapeHtml(entry.part_name)}<br><span class="table-subtext">${escapeHtml(entry.component || '-')}</span></td>
        <td>${escapeHtml(entry.operation || '-')}<br><span class="table-subtext">${escapeHtml(entry.cutter_num || 'No cutter')}</span></td>
        <td>${Number(entry.planned_qty)}</td><td>${Number(entry.total_qty)}</td><td>${Number(entry.ok_qty)}</td>
        <td>${Number(entry.mc_reject_qty)}</td><td>${Number(entry.rm_defect_qty)}</td><td>${Number(entry.rework_qty)}</td>
        <td>${Number(entry.downtime_min)} min</td>
        <td>${escapeHtml(entry.machine_status || entry.job_status || '-')}</td>
        <td>${escapeHtml([entry.issue_code, entry.remarks].filter(Boolean).join(' | ') || '-')}</td>
        <td>${entry.edited_at
          ? `<strong>Edited Once</strong><br><span class="table-subtext">${escapeHtml(entry.edited_by || '')}: ${escapeHtml(entry.edit_reason || '')}</span>`
          : (entry.can_edit ? `<button type="button" onclick="openReportEntryEdit(${Number(entry.shift_id)})">Edit Once</button>` : 'Locked')}</td>
      </tr>`).join('');
      const panel = document.getElementById('reportEntryDetails');
      panel.classList.remove('hidden');
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeReportEntries() {
      document.getElementById('reportEntryDetails')?.classList.add('hidden');
      closeReportEntryEdit();
    }

    function openReportEntryEdit(shiftId) {
      const entry = (currentReportData?.entries || []).find(row => Number(row.shift_id) === Number(shiftId));
      if (!entry?.can_edit) return;
      const form = document.getElementById('reportEntryEditForm');
      form.reset();
      form.elements.shift_id.value = entry.shift_id;
      ['total_qty', 'ok_qty', 'mc_reject_qty', 'rm_defect_qty', 'rework_qty', 'downtime_min'].forEach(field => {
        form.elements[field].value = Number(entry[field] || 0);
      });
      form.elements.issue_code.value = entry.issue_code || '';
      form.elements.remarks.value = entry.shift_remarks || '';
      document.getElementById('reportEditTitle').innerText = `Correct Entry #${entry.shift_id} — ${entry.machine_code}`;
      form.classList.remove('hidden');
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeReportEntryEdit() {
      document.getElementById('reportEntryEditForm')?.classList.add('hidden');
    }

    document.getElementById('reportEntryEditForm').addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      const confirmed = await showConfirmDialog({
        title: 'Save One-Time Correction?',
        message: 'After saving, this production entry cannot be edited again.',
        confirmText: 'Save and Lock'
      });
      if (!confirmed) return;
      const button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      try {
        const response = await fetch(`${API}/update_report_entry.php`, {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(Object.fromEntries(new FormData(form)))
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to correct production entry.');
        closeReportEntryEdit();
        showToast('success', 'Production entry corrected and permanently locked.');
        loadProductionReport();
      } catch (error) {
        showToast('error', error.message);
      } finally {
        button.disabled = false;
      }
    });

    function csvCell(value) {
      return `"${String(value ?? '').replaceAll('"', '""')}"`;
    }

    function downloadReportCsv() {
      if (!currentReportData) return;
      const headers = ['Machine Number', 'Machine Name', 'Operators', 'Shifts', 'Parts', 'Components',
        'Jobs', 'Planned Qty', 'Total Qty', 'OK Qty', 'M.C. Reject', 'R.M. Defect', 'Rework',
        'Pending Qty', 'Downtime Minutes', 'Achievement %', 'Status', 'Remarks'];
      const lines = [
        [csvCell(`${systemSettings.company_name} — ${systemSettings.report_heading}`)].join(','),
        [csvCell(systemSettings.company_address || '')].join(','),
        [csvCell(currentReportData.period_label)].join(','),
        headers.map(csvCell).join(',')
      ];
      currentReportData.rows.forEach(row => {
        lines.push([
          row.machine_code, row.machine_name, row.operators, row.shifts, row.parts, row.components,
          row.job_count, row.planned_qty, row.total_qty, row.ok_qty, row.mc_reject_qty,
          row.rm_defect_qty, row.rework_qty, row.pending_qty, row.downtime_min,
          row.achievement_percent, row.statuses, row.remarks
        ].map(csvCell).join(','));
      });
      const totals = currentReportData.totals;
      lines.push([
        'TOTAL', '', '', '', '', '', totals.job_count, totals.planned_qty, totals.total_qty,
        totals.ok_qty, totals.mc_reject_qty, totals.rm_defect_qty, totals.rework_qty,
        totals.pending_qty, totals.downtime_min, totals.achievement_percent, '', ''
      ].map(csvCell).join(','));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `production-report-${currentReportData.period}-${currentReportData.start_date}-to-${currentReportData.end_date}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    }

    function printProductionReport() {
      if (!currentReportData) return;
      const oldTitle = document.title;
      document.title = `${systemSettings.company_name} - ${systemSettings.report_heading} - ${currentReportData.period_label}`;
      window.print();
      document.title = oldTitle;
    }
