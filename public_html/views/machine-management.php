    <section id="machineManagementModule" class="hidden">
      <header class="module-header">
        <h1>Machine Management</h1>
        <p>Admin-only machine master. Machines with history remain safe and can be deactivated.</p>
      </header>

      <form class="card" id="machineManagementForm" onsubmit="handleMachineSubmit(event)">
        <input type="hidden" name="id" id="machineManagementId" />
        <div id="machineManagementMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Machine Number / Code<input type="text" name="code" id="machineCode" maxlength="30" required placeholder="e.g. HOB/07" /></label>
          <label>Machine Name<input type="text" name="name" id="machineName" maxlength="100" required placeholder="e.g. Hobbing Machine 07" /></label>
          <label>Default Operation
            <input type="text" name="default_operation" id="machineDefaultOperation" maxlength="150" list="machineOperationSuggestions" required placeholder="e.g. Hobbing" />
            <datalist id="machineOperationSuggestions"><option value="Shaping"></option><option value="Hobbing"></option><option value="Turning"></option><option value="Drilling"></option><option value="Keyway"></option></datalist>
          </label>
          <label>Status<select name="status" id="machineStatus"><option>Active</option><option>Inactive</option></select></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveMachineBtn">Save Machine</button>
          <button type="button" class="secondary-button hidden" id="cancelMachineEditBtn" onclick="resetMachineForm()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2>Existing Machines</h2>
          <span class="table-subtext">Only active machines appear on the dashboard and production forms.</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Machine No.</th><th>Name</th><th>Default Operation</th><th>Status</th><th>Usage</th><th>Current Job</th><th>Actions</th></tr></thead>
            <tbody id="machineManagementTableBody"><tr><td colspan="7" class="empty">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>
