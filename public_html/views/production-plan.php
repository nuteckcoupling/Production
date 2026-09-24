    <section id="productionPlanModule" class="hidden">
      <div class="card report-tabs plan-module-tabs" role="tablist" aria-label="Production Plan type">
        <button type="button" id="weeklyPlanTab" class="active" onclick="switchPlanTab('weekly')">Weekly Plan</button>
        <button type="button" id="monthlyPlanTab" onclick="switchPlanTab('monthly')">Monthly Plan</button>
      </div>
      <?php require __DIR__ . '/weekly-plan.php'; ?>
      <?php require __DIR__ . '/monthly-plan.php'; ?>
    </section>
