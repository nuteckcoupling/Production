  <section id="loginView" class="login-page">
    <form class="login-card" onsubmit="handleLogin(event)">
      <div class="login-brand">NU-TECK</div>
      <h1>Production System Login</h1>
      <p>Sign in with your Operator/Supervisor or Admin/Director account.</p>
      <div id="loginMessage" class="banner"></div>
      <label>
        Username
        <input type="text" name="username" id="loginUsername" autocomplete="username" required />
      </label>
      <label>
        Password / PIN
        <input type="password" name="password" id="loginPassword" autocomplete="current-password" required />
      </label>
      <button type="submit" id="loginButton">Login</button>
    </form>
  </section>
