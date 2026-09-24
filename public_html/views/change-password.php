    <section id="changePasswordModule" class="hidden">
      <header class="module-header">
        <h1>Change My Password</h1>
        <p>Enter your current password before choosing a new password.</p>
      </header>
      <form class="card" id="changePasswordForm" onsubmit="handleChangePassword(event)">
        <div id="changePasswordMessage" class="banner"></div>
        <div class="grid management-grid">
          <label>Current Password<input type="password" name="current_password" id="currentPassword" autocomplete="current-password" required /></label>
          <label>New Password<input type="password" name="new_password" id="newPassword" minlength="8" maxlength="128" autocomplete="new-password" required /></label>
          <label>Confirm New Password<input type="password" name="confirm_password" id="confirmNewPassword" minlength="8" maxlength="128" autocomplete="new-password" required /></label>
        </div>
        <div class="management-form-actions">
          <button type="submit" class="submit" id="changePasswordBtn">Change Password</button>
        </div>
      </form>
    </section>
