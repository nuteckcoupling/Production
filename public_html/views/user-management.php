    <section id="userManagementModule" class="hidden">
      <header class="module-header">
        <h1>User Management</h1>
        <p>Admin-only login account setup, access control and password reset.</p>
      </header>

      <form class="card" id="userManagementForm" onsubmit="handleUserSubmit(event)">
        <input type="hidden" name="id" id="userManagementId" />
        <div id="userManagementMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Username<input type="text" name="username" id="userUsername" maxlength="50" autocomplete="off" required placeholder="e.g. operator05" /></label>
          <label>Role<select name="role" id="userRole" onchange="updateUserRoleFields()"><option>Operator/Supervisor</option><option>Admin</option></select></label>
          <label id="userStaffField">Linked Staff<select name="operator_id" id="userOperatorId"><option value="">-- select staff --</option></select></label>
          <label>Status<select name="status" id="userStatus"><option>Active</option><option>Inactive</option></select></label>
          <label>Password / Reset Password
            <input type="password" name="password" id="userPassword" minlength="8" maxlength="128" autocomplete="new-password" placeholder="Required for new user" />
            <span class="table-subtext">Editing: leave blank to keep the current password.</span>
          </label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="saveUserBtn">Save User</button>
          <button type="button" class="secondary-button hidden" id="cancelUserEditBtn" onclick="resetUserForm()">Cancel Edit</button>
        </div>
      </form>

      <div class="card list-card">
        <div class="list-heading">
          <h2>Login Accounts</h2>
          <span class="table-subtext">Inactive users are immediately blocked from login and future API requests.</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Username</th><th>Role</th><th>Linked Staff</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
            <tbody id="userManagementTableBody"><tr><td colspan="6" class="empty">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>
    </section>
