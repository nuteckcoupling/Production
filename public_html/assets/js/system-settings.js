    function applySystemSettingsBranding() {
      const reportTitle = `${systemSettings.company_name} — ${systemSettings.report_heading}`;
      document.title = `${systemSettings.company_name} — Production System`;
      const values = {
        sidebarCompanyName: systemSettings.company_name,
        productionReportHeading: reportTitle,
        productionReportAddress: systemSettings.company_address || '',
        adminAnalysisHeading: `${systemSettings.company_name} — Admin Production Analysis`,
        adminAnalysisAddress: systemSettings.company_address || '',
        weeklyPlanBrandHeading: `${systemSettings.company_name} — Weekly Plan`,
        weeklyPlanBrandAddress: systemSettings.company_address || '',
        monthlyPlanBrandHeading: `${systemSettings.company_name} — Monthly Plan`,
        monthlyPlanBrandAddress: systemSettings.company_address || ''
      };
      Object.entries(values).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (!element) return;
        element.innerText = value;
        if (id.endsWith('Address')) element.classList.toggle('hidden', !value);
      });
    }

    function populateSystemSettingsForm() {
      if (currentUser?.role !== 'Admin') return;
      document.getElementById('settingsCompanyName').value = systemSettings.company_name;
      document.getElementById('settingsReportHeading').value = systemSettings.report_heading;
      document.getElementById('settingsCompanyAddress').value = systemSettings.company_address || '';
      document.getElementById('settingsIdleMinutes').value = systemSettings.idle_alert_minutes;
      document.getElementById('settingsHandoverMinutes').value = systemSettings.handover_overdue_minutes;
      document.getElementById('settingsShiftGrace').value = systemSettings.shift_end_grace_minutes;
    }

    function loadSystemSettings(fillForm = false) {
      return fetch(API + '/get_system_settings.php')
        .then(async response => {
          const data = await response.json();
          if (!response.ok) throw new Error(data.error || 'Unable to load system settings.');
          return data;
        })
        .then(data => {
          systemSettings = data;
          applySystemSettingsBranding();
          if (fillForm) populateSystemSettingsForm();
          return data;
        })
        .catch(error => {
          if (fillForm) {
            const banner = document.getElementById('systemSettingsMessage');
            banner.className = 'banner error';
            banner.innerText = error.message;
          }
          return systemSettings;
        });
    }

    function handleSystemSettingsSubmit(event) {
      event.preventDefault();
      const button = document.getElementById('saveSystemSettingsBtn');
      const banner = document.getElementById('systemSettingsMessage');
      const data = Object.fromEntries(new FormData(event.target).entries());
      button.disabled = true;
      button.innerText = 'Saving...';
      fetch(API + '/save_system_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(async response => {
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Unable to save system settings.');
        return result;
      }).then(result => {
        systemSettings = result.settings;
        applySystemSettingsBranding();
        populateSystemSettingsForm();
        banner.className = 'banner success';
        banner.innerText = 'System settings saved. Dashboard alerts and reports updated.';
        showToast('success', banner.innerText);
        loadAlerts();
      }).catch(error => {
        banner.className = 'banner error';
        banner.innerText = error.message;
        showToast('error', error.message);
      }).finally(() => {
        button.disabled = false;
        button.innerText = 'Save Settings';
      });
    }
