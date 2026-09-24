<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NU-TECK Couplings — Daily Production Entry</title>
  <link rel="stylesheet" href="style.css?v=20260924-4" />
</head>
<body>
  <?php require __DIR__ . '/views/login.php'; ?>
  <?php require __DIR__ . '/views/notifications.php'; ?>

  <div id="appShell" class="app-shell hidden">
  <?php require __DIR__ . '/views/sidebar.php'; ?>

    <main class="main-content">
      <div class="wrap">
    <?php require __DIR__ . '/views/dashboard.php'; ?>
    <?php require __DIR__ . '/views/production-plan.php'; ?>
    <?php require __DIR__ . '/views/shift-management.php'; ?>
    <?php require __DIR__ . '/views/machine-management.php'; ?>
    <?php require __DIR__ . '/views/staff-management.php'; ?>
    <?php require __DIR__ . '/views/user-management.php'; ?>
    <?php require __DIR__ . '/views/change-password.php'; ?>
    <?php require __DIR__ . '/views/audit-log.php'; ?>
    <?php require __DIR__ . '/views/system-settings.php'; ?>
    <?php require __DIR__ . '/views/backup-management.php'; ?>
    <?php require __DIR__ . '/views/daily-entry.php'; ?>
    <?php require __DIR__ . '/views/active-job.php'; ?>
    <?php require __DIR__ . '/views/reports.php'; ?>
    <?php require __DIR__ . '/views/admin-analysis.php'; ?>
    <?php require __DIR__ . '/views/cutters.php'; ?>
    <?php require __DIR__ . '/views/couplings.php'; ?>
      </div>
    </main>
  </div>

  <script src="assets/js/notifications.js?v=20260924-1"></script>
  <script src="assets/js/core.js?v=20260924-7"></script>
  <script src="assets/js/api.js?v=20260924-1"></script>
  <script src="assets/js/auth.js?v=20260924-4"></script>
  <script src="assets/js/weekly-plan.js?v=20260924-2"></script>
  <script src="assets/js/monthly-plan.js?v=20260924-2"></script>
  <script src="assets/js/shift-management.js?v=20260924-1"></script>
  <script src="assets/js/machine-management.js?v=20260924-1"></script>
  <script src="assets/js/staff-management.js?v=20260924-1"></script>
  <script src="assets/js/user-management.js?v=20260924-2"></script>
  <script src="assets/js/audit-log.js?v=20260924-1"></script>
  <script src="assets/js/system-settings.js?v=20260924-1"></script>
  <script src="assets/js/backup-management.js?v=20260924-2"></script>
  <script src="assets/js/jobs.js?v=20260923-4"></script>
  <script src="assets/js/changes.js?v=20260923-3"></script>
  <script src="assets/js/reports.js?v=20260924-3"></script>
  <script src="assets/js/admin-analysis.js?v=20260924-2"></script>
  <script src="assets/js/dashboard.js?v=20260924-3"></script>
  <script src="assets/js/cutters.js?v=20260924-1"></script>
  <script src="assets/js/couplings.js?v=20260924-1"></script>
  <script src="assets/js/app.js?v=20260923-2"></script>
</body>
</html>
