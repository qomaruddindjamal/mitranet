<?php
/**
 * MitraNet Network OS - Modular Footer Include
 * Modern White Enterprise Design System
 */
?>
      </div><!-- /.content-body -->

      <!-- Fixed Status Footer Bar -->
      <footer class="footer-bar fixed-footer" id="footer-bar">
        <div class="footer-left">
          <span class="status-indicator-dot online"></span>
          <span class="footer-device">MitraNet Edge Router</span>
          <span class="footer-divider">•</span>
          <span class="footer-time" id="footer-live-clock">--:--:--</span>
        </div>
        <div class="footer-center">
          <span class="footer-badge badge-fastpath">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
            FastPath Wire-Speed
          </span>
          <span class="footer-badge badge-zbf">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Zero-Trust ZBF
          </span>
          <span class="footer-badge badge-system" id="footer-system-chip">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="2" width="20" height="8" rx="2"></rect><rect x="2" y="14" width="20" height="8" rx="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
            RAM: <span id="footer-ram-usage">-- MB</span>
          </span>
        </div>
        <div class="footer-right">
          <span class="footer-airgap">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Offline Air-Gapped Mode
          </span>
          <span class="footer-divider">•</span>
          <span class="footer-user">User: <strong><?= html_esc($_SESSION['user'] ?? 'admin') ?></strong></span>
        </div>
      </footer>
    </main>
  </div>

  <!-- Local Offline-Ready Vendor Scripts (jQuery 3.7.1 & Bootstrap 5.3.3 JS Bundle) -->
  <script src="<?= $webRoot ?>vendor/jquery/jquery.min.js"></script>
  <script src="<?= $webRoot ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $webRoot ?>includes/common.js"></script>
  <?php if (!empty($packageJs)): ?>
  <script src="<?= $packageJs ?>"></script>
  <?php endif; ?>
</body>

</html>
