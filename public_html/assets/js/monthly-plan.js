    let monthlyPlanInitialized = false;
    let monthlyPlanData = null;

    function initializeMonthlyPlan() {
      if (!monthlyPlanInitialized) {
        document.getElementById('monthlyPlanMonth').value = today.slice(0, 7);
        monthlyPlanInitialized = true;
      }
      loadMonthlyPlan();
    }

    function showMonthlyPlanMessage(type, text) {
      const banner = document.getElementById('monthlyPlanMessage');
      banner.className = text ? 'banner ' + type : 'banner';
      banner.innerText = text;
    }

    function loadMonthlyPlan() {
      const month = document.getElementById('monthlyPlanMonth').value;
      if (!month) {
        showMonthlyPlanMessage('error', 'Select a plan month.');
        return;
      }
      const tbody = document.getElementById('monthlyPlanTableBody');
      tbody.innerHTML = '<tr><td colspan="6" class="empty">Loading...</td></tr>';
      showMonthlyPlanMessage('', '');
      fetch(API + '/get_monthly_plan.php?month=' + encodeURIComponent(month))
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load Monthly Plan.');
          return data;
        })
        .then(data => {
          monthlyPlanData = data;
          renderMonthlyPlan();
        })
        .catch(error => {
          monthlyPlanData = null;
          tbody.innerHTML = '<tr><td colspan="6" class="empty error-text">' + escapeHtml(error.message) + '</td></tr>';
          showMonthlyPlanMessage('error', error.message);
        });
    }

    function renderMonthlyPlan() {
      const data = monthlyPlanData;
      const items = data.items || [];
      const exists = data.exists;
      const locked = exists && data.plan.status === 'Locked';
      const totalQty = items.reduce((sum, item) => sum + Number(item.planned_qty), 0);
      const snapshotLines = items.reduce((sum, item) => sum + Number(item.weekly_line_count), 0);
      document.getElementById('monthlyPlanStatus').innerText = exists ? data.plan.status : 'Not Generated';
      document.getElementById('monthlyPlanSourceCount').innerText = exists ? snapshotLines : Number(data.source.line_count);
      document.getElementById('monthlyPlanLineCount').innerText = items.length;
      document.getElementById('monthlyPlanTotalQty').innerText = totalQty;
      document.getElementById('monthlyPlanPeriodLabel').innerText = data.period_label;
      document.getElementById('downloadMonthlyPlanBtn').disabled = items.length === 0;
      document.getElementById('printMonthlyPlanBtn').disabled = items.length === 0;
      document.getElementById('generateMonthlyPlanBtn').disabled = locked;
      document.getElementById('lockMonthlyPlanBtn').disabled = !exists || locked || items.length === 0;
      const audit = document.getElementById('monthlyPlanAuditLabel');
      if (!exists) {
        audit.innerText = Number(data.source.line_count) + ' Weekly Plan line(s) available';
      } else if (locked) {
        audit.innerText = 'Locked by ' + (data.plan.locked_by || '-') + ' · ' + (data.plan.locked_at || '');
      } else {
        audit.innerText = 'Draft prepared by ' + (data.plan.created_by || '-') + ' · refresh allowed';
      }
      const tbody = document.getElementById('monthlyPlanTableBody');
      const foot = document.getElementById('monthlyPlanTableFoot');
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty">' +
          (Number(data.source.line_count) ? 'Click Generate / Refresh to convert the Weekly Plans.' : 'No Weekly Plans found for this month.') +
          '</td></tr>';
        foot.innerHTML = '';
      } else {
        tbody.innerHTML = items.map(item => '<tr>' +
          '<td><strong>' + escapeHtml(item.machine_code) + '</strong><br><span class="table-subtext">' + escapeHtml(item.machine_name) + '</span></td>' +
          '<td>' + escapeHtml(item.shift) + '</td><td>' + escapeHtml(item.part_name) + '</td>' +
          '<td>' + escapeHtml(item.operation) + '</td><td>' + Number(item.weekly_line_count) + '</td>' +
          '<td>' + Number(item.planned_qty) + '</td></tr>').join('');
        foot.innerHTML = '<tr><th colspan="4">TOTAL</th><th>' + snapshotLines + '</th><th>' + totalQty + '</th></tr>';
      }
      document.getElementById('monthlyPlanGeneratedAt').innerText = exists
        ? 'Monthly ISO snapshot · Last updated ' + data.plan.updated_at
        : 'Monthly ISO snapshot not generated';
    }

    function generateMonthlyPlan() {
      const month = document.getElementById('monthlyPlanMonth').value;
      if (!month) {
        showMonthlyPlanMessage('error', 'Select a plan month.');
        return;
      }
      const button = document.getElementById('generateMonthlyPlanBtn');
      button.disabled = true;
      button.innerText = 'Generating...';
      fetch(API + '/generate_monthly_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ month })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to generate Monthly Plan.');
      }).then(() => {
        showMonthlyPlanMessage('success', 'Monthly Plan generated from Weekly Plans.');
        loadMonthlyPlan();
      }).catch(error => showMonthlyPlanMessage('error', error.message))
        .finally(() => {
          button.innerText = 'Generate / Refresh from Weekly Plans';
          if (!monthlyPlanData || monthlyPlanData.plan?.status !== 'Locked') button.disabled = false;
        });
    }

    function lockMonthlyPlan() {
      const month = document.getElementById('monthlyPlanMonth').value;
      if (!window.confirm('Lock this Monthly Plan? After locking, it cannot be refreshed or changed.')) return;
      const button = document.getElementById('lockMonthlyPlanBtn');
      button.disabled = true;
      fetch(API + '/lock_monthly_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ month })
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to lock Monthly Plan.');
      }).then(() => {
        showMonthlyPlanMessage('success', 'Monthly Plan locked successfully.');
        loadMonthlyPlan();
      }).catch(error => {
        showMonthlyPlanMessage('error', error.message);
        button.disabled = false;
      });
    }

    function downloadMonthlyPlanCsv() {
      if (!monthlyPlanData?.items?.length) return;
      const lines = [
        [csvCell('NU-TECK Monthly Plan - ISO Document')].join(','),
        [csvCell(monthlyPlanData.period_label), csvCell(monthlyPlanData.plan.status)].join(','),
        ['Machine Number', 'Machine Name', 'Shift', 'Part Name', 'Operation', 'Weekly Lines', 'Planned Qty'].map(csvCell).join(',')
      ];
      monthlyPlanData.items.forEach(item => {
        lines.push([item.machine_code, item.machine_name, item.shift, item.part_name, item.operation, item.weekly_line_count, item.planned_qty].map(csvCell).join(','));
      });
      const totalQty = monthlyPlanData.items.reduce((sum, item) => sum + Number(item.planned_qty), 0);
      const sourceLines = monthlyPlanData.items.reduce((sum, item) => sum + Number(item.weekly_line_count), 0);
      lines.push(['TOTAL', '', '', '', '', sourceLines, totalQty].map(csvCell).join(','));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'monthly-iso-plan-' + monthlyPlanData.month + '.csv';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    }

    function printMonthlyPlan() {
      if (!monthlyPlanData?.items?.length) return;
      const oldTitle = document.title;
      document.title = 'Monthly ISO Plan - ' + monthlyPlanData.period_label;
      document.body.classList.add('monthly-plan-print');
      window.addEventListener('afterprint', () => {
        document.body.classList.remove('monthly-plan-print');
        document.title = oldTitle;
      }, { once: true });
      window.print();
    }
