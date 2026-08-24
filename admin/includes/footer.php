</main>
</div>

<footer style="background: var(--color-primary); color: #64748b; text-align: center; padding: 1.25rem 1rem; font-size: 0.8rem; border-top: 1px solid var(--color-primary-light);">
  <span>Tiranda Jogja Control Panel &bull; &copy; <?= date('Y') ?> <strong>Sem Adler</strong>. All rights reserved.</span>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('adminSidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('adminSidebarOverlay');
  const closeBtn = document.getElementById('adminSidebarClose');

  function openSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.add('is-open');
      overlay.classList.add('is-open');
    }
  }

  function closeSidebar() {
    if (sidebar && overlay) {
      sidebar.classList.remove('is-open');
      overlay.classList.remove('is-open');
    }
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
});
</script>

</body>
</html>