document.addEventListener('DOMContentLoaded', function () {
  lucide.createIcons();

  function showToast(message, type = 'success') {
    Swal.fire({
      toast: true,
      position: 'top',
      icon: type,
      title: message,
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
      customClass: {
        popup: 'swal2-toast-custom'
      },
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });
  }

  function setupStatusDropdowns() {
    document.querySelectorAll('.order-dropdown').forEach(select => {
      select.addEventListener('change', function () {
        const orderId = this.dataset.orderId;
        const newStatus = this.value;

        fetch('admin_update_orderstatus.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `order_id=${orderId}&order_status=${newStatus}`
        })
        .then(res => res.text())
        .then(() => {
          showToast("✅ Order status updated!");
          const badge = this.closest('tr').querySelector('.status-badge');
          badge.textContent = newStatus;
          badge.className = `status-badge ${newStatus.toLowerCase().replace(/\s+/g, '-')}`;
        })
        .catch(() => showToast("❌ Failed to update order.", 'error'));
      });
    });

    document.querySelectorAll('.delivery-dropdown').forEach(select => {
      select.addEventListener('change', function () {
        const orderId = this.dataset.orderId;
        const newStatus = this.value;

        fetch('admin_update_deliverystatus.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `order_id=${orderId}&delivery_status=${newStatus}`
        })
        .then(res => res.text())
        .then(() => {
          showToast("✅ Delivery status updated!");
          const badge = this.closest('tr').querySelectorAll('.status-badge')[1];
          badge.textContent = newStatus;
          badge.className = `status-badge ${newStatus.toLowerCase().replace(/\s+/g, '-')}`;
        })
        .catch(() => showToast("❌ Failed to update delivery.", 'error'));
      });
    });
  }

  function setupEditSidebar() {
    window.openEditSidebar = function (id, name, description, price, stock, status) {
      const sidebar = document.getElementById('editSidebar');
      sidebar.classList.remove('translate-x-full');
      document.getElementById('editProductId').value = id;
      document.getElementById('editProductName').value = name;
      document.getElementById('editProductDescription').value = description;
      document.getElementById('editProductPrice').value = price;
      document.getElementById('editProductStock').value = stock;
      document.getElementById('editProductStatus').value = status;
    };

    window.closeEditSidebar = function () {
      document.getElementById('editSidebar').classList.add('translate-x-full');
    };
  }

  function setupTableSearch(tableId, columnIndexes) {
    const searchInput = document.getElementById('searchInput');
    const filterButton = document.getElementById('filterButton');
    const resetButton = document.getElementById('resetButton');
    const table = document.getElementById(tableId);

    if (!searchInput || !table || !filterButton || !resetButton) return;

    function filterTable() {
      const query = searchInput.value.toLowerCase();
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const match = columnIndexes.some(index => {
          const cell = row.children[index];
          return cell && cell.innerText.toLowerCase().includes(query);
        });
        row.style.display = match ? '' : 'none';
      });
    }

    function resetTable() {
      searchInput.value = '';
      table.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
    }

    filterButton.addEventListener('click', filterTable);
    resetButton.addEventListener('click', resetTable);
    searchInput.addEventListener('keyup', e => {
      if (e.key === 'Enter') filterTable();
    });
  }

  function previewImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.innerHTML = `<img src="${e.target.result}" class="upload-preview-image">`;
        };
        reader.readAsDataURL(file);
      } else {
        preview.innerHTML = '';
      }
    });
  }

  function setupDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const itemId = this.dataset.id;
        const itemName = this.dataset.name;
        const itemType = this.dataset.type;

        Swal.fire({
          title: '🗑️ Are you sure?',
          html: `Delete <strong style="color:#dc3545;">${itemName}</strong>?<br><small>You can't recover this after!</small>`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'No, cancel',
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
            let deleteUrl = '';
            if (itemType === 'brand') {
              deleteUrl = `admin_delete_brand.php?id=${itemId}`;
            } else if (itemType === 'product') {
              deleteUrl = `admin_delete_product.php?id=${itemId}`;
            } else if (itemType === 'category') {
              deleteUrl = `admin_delete_category.php?id=${itemId}`;
            } else if (itemType === 'order') {
              deleteUrl = `admin_delete_order.php?id=${itemId}`;
            }

            if (deleteUrl) {
              window.location.href = deleteUrl;
            }
          }
        });
      });
    });
  }

  const userProfile = document.getElementById('userProfile');
  if (userProfile) {
    userProfile.addEventListener('click', function (e) {
      e.stopPropagation();
      this.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
      if (!userProfile.contains(e.target)) {
        userProfile.classList.remove('active');
      }
    });
  }

  const sidebarToggle = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      const icon = sidebarToggle.querySelector('i');
      icon.setAttribute('data-lucide', sidebar.classList.contains('collapsed') ? 'chevron-right' : 'chevron-left');
      lucide.createIcons();
    });
  }

  document.querySelectorAll('.has-submenu').forEach(parent => {
    parent.addEventListener('click', () => {
      parent.classList.toggle('open');
      const icon = parent.querySelector('.submenu-icon');
      icon.setAttribute('data-lucide', parent.classList.contains('open') ? 'chevron-down' : 'chevron-right');
      lucide.createIcons();
    });
  });

  // 🟣 Initialize all
  function initializePageFeatures() {
    if (document.getElementById('productTable')) setupTableSearch('productTable', [0, 1, 2, 3]);
    if (document.getElementById('customerTable')) setupTableSearch('customerTable', [1, 2, 3]);
    if (document.getElementById('brandTable')) setupTableSearch('brandTable', [1]);
    if (document.getElementById('categoryTable')) setupTableSearch('categoryTable', [1]);
    if (document.getElementById('ordertable')) setupTableSearch('ordertable', [0,1,2,3,5,6]);
    if (document.getElementById('editSidebar')) setupEditSidebar();
    if (document.getElementById('product_image')) {
      previewImage('product_image', 'preview1');
      previewImage('product_image2', 'preview2');
      previewImage('product_image3', 'preview3');
    }
    setupDeleteButtons();
    setupStatusDropdowns();
  }

  initializePageFeatures();

  // ✅ Show login success toast
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('login') === 'success') {
    showToast("✅ Login successfully!");
    urlParams.delete('login');
    const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    window.history.replaceState({}, document.title, newUrl);
  }
});

window.addEventListener('pageshow', function (event) {
  if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
    window.location.reload();
  }
});
