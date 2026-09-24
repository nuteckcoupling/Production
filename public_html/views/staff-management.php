    <section id="staffManagementModule" class="hidden">
      <header class="module-header">
        <h1>Staff Management</h1>
        <p>Admin-only staff master. Production history and running work remain protected.</p>
      </header>

      <form class="card" id="staffManagementForm" onsubmit="handleStaffSubmit(event)">
        <input type="hidden" name="id" id="staffManagementId" />
        <div id="staffManagementMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Staff ID<input type="text" name="staff_code" id="staffCode" maxlength="30" required placeholder="e.g. OP-005" /></label>
          <label>Staff Name<input type="text" name="name" id="staffName" maxlength="100" required placeholder="Full name" /></label>
          <label>Designation
            <input type="text" name="designation" id="staffDesignation" maxlength="100" list="staffDesignationSuggestions" required value="Operator" />
            <datalist id="staffDesignationSuggestions"><option value="Operator"></option><option value="Supervisor"></option><option value="Helper"></option><option value="QC Inspector"></option></datalist>
          </label>
          <label>Department
            <input type="text" name="department" id="staffDepartment" maxlength="100" list="staffDepartmentSuggestions" required value="Production" />
            <datalist id="staffDepartmentSuggestions"><option value="Production"></option><option value="Quality"></option><option value="Maintenance"></option><option value="Stores"></option></datalist>
          </label>
          <label>Phone<input type="tel" name="phone" id="staffPhone" maxlength="20" placeholder="Optional" /></label>
          <label>Status<select name="status" id="staffStatus"><option>Active</option><option>Inactive</option></select></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveStaffBtn">Save Staff</button>
          <button type="button" class="secondary-button hidden" id="cancelStaffEditBtn" onclick="resetStaffForm()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2>Existing Staff</h2>
          <span class="table-subtext">Only active staff appear in production and report dropdowns.</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Staff ID</th><th>Name</th><th>Designation</th><th>Department</th><th>Phone</th><th>Status</th><th>Usage</th><th>Login</th><th>Active Work</th><th>Actions</th></tr></thead>
            <tbody id="staffManagementTableBody"><tr><td colspan="10" class="empty">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>
