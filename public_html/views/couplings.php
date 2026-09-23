    <section id="couplingModule" class="hidden">
      <header class="module-header">
        <div>
          <h1>Coupling Add</h1>
          <p>Add a unique coupling code for use in Daily Entry.</p>
        </div>
      </header>

      <form class="card" id="couplingForm" onsubmit="handleCouplingSubmit(event)">
        <div id="couplingMessage" class="banner"></div>
        <div class="grid management-grid compact-grid">
          <label>
            Coupling Range
            <select name="coupling_type" id="new_coupling_type" required>
              <option value="">-- select --</option>
              <option value="GC Gear Coupling">GC Gear Coupling</option>
              <option value="NA Gear Coupling">NA Gear Coupling</option>
              <option value="Roller Chain Coupling">Roller Chain Coupling</option>
              <option value="Gear">Gear</option>
              <option value="Sprocket">Sprocket</option>
            </select>
          </label>
          <label>
            Coupling Code / Part Name
            <input type="text" name="part_name" id="new_part_name" required placeholder="e.g. GC-119" />
          </label>
        </div>
        <button type="submit" class="submit" id="saveCouplingBtn">Save Coupling</button>
      </form>

      <div class="card list-card">
        <h2>Existing Couplings</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Coupling Range</th><th>Coupling Code / Part Name</th></tr></thead>
            <tbody id="couplingsTableBody">
              <tr><td colspan="2" class="empty">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
