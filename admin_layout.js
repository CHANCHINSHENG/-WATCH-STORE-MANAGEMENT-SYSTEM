document.addEventListener('DOMContentLoaded', function () {
  lucide.createIcons();
  let isDropdownOpen = false;

 const hash = window.location.hash;
if (hash.startsWith('#order')) {
    const row = document.querySelector(hash);
    if (row) {
        row.classList.add('highlight-order');
        row.scrollIntoView({ behavior: "smooth", block: "center" });

        // ✅ 2 秒後自動移除高亮
        setTimeout(() => {
            row.classList.remove('highlight-order');
        }, 2000);
    }
}


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

  function updateNotificationUI(data) {
    const bell = document.querySelector('.notifications');
    const list = document.getElementById('notificationList');
    const notifCount = document.getElementById('notifCount');

    if (data.newOrders.length > 0) {
      bell.classList.add('has-new');
      notifCount.style.display = 'inline-block';
      notifCount.textContent = data.newOrders.length;
      list.innerHTML = '';

      data.newOrders.forEach(order => {
        const li = document.createElement('li');
        const dateTime = order.OrderDateTime ?? 'Just now';
        li.className = 'notification-item';
        li.innerHTML = ` 
          <div class="notif-title">🛒 <strong>${order.Cust_Username}</strong> placed an order of ${order.Total_Price}</div>
          <div class="notif-time">${dateTime}</div>
          <a href="admin_viewnoti_tocorrecorder.php?cid=${order.CustomerID}" class="notif-view">View</a>
        `;
        list.appendChild(li);
      });
    } else {
      bell.classList.remove('has-new');
      list.innerHTML = '<li class="notification-item">No new orders</li>';
      notifCount.style.display = 'none';
    }
  }

 const savedData = sessionStorage.getItem('notifData');
if (savedData) {
  const parsed = JSON.parse(savedData);
  updateNotificationUI(parsed);
}

fetch('admin_check_new_orders.php')
  .then(res => res.json())
  .then(data => {
    sessionStorage.setItem('notifData', JSON.stringify(data));
    updateNotificationUI(data);
  });

  function pollNewOrders() {
    setInterval(() => {
      fetch('admin_check_new_orders.php')
        .then(res => res.json())
        .then(data => {
          sessionStorage.setItem('notifData', JSON.stringify(data));
          updateNotificationUI(data);
        });
    }, 10000);
  }

  pollNewOrders();

  const bellBtn = document.querySelector('.notifications');
  const dropdown = document.getElementById('notificationDropdown');

  bellBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    isDropdownOpen = !isDropdownOpen;
    dropdown.style.display = isDropdownOpen ? 'block' : 'none';
  });

  document.addEventListener('click', function () {
    dropdown.style.display = 'none';
    isDropdownOpen = false;
  });

  document.addEventListener('change', function (e) {
    if (e.target.matches('.order-dropdown')) {
      const select = e.target;
      const orderId = select.dataset.orderId;
      const newStatus = select.value;

      fetch('admin_update_orderstatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&order_status=${newStatus}`
      })
      .then(res => res.text())
      .then(() => {
        showToast("✅ Order status updated!");
        const badge = select.closest('tr').querySelector('.status-badge');
        if (badge) {
          badge.textContent = newStatus;
          badge.className = `status-badge ${newStatus.toLowerCase().replace(/\s+/g, '-')}`;
        }

        // 🆕 Refresh notification immediately
        fetch('check_new_orders.php')
          .then(res => res.json())
          .then(data => {
            sessionStorage.setItem('notifData', JSON.stringify(data));
            updateNotificationUI(data);
          });
      })
      .catch(() => showToast("❌ Failed to update order.", 'error'));
    }
  });



  function setupAdvancedOrderFilter() {
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const deliveryStatusFilter = document.getElementById('deliveryStatusFilter');
  const methodFilter = document.getElementById('methodFilter');
  const orderLimitFilter = document.getElementById('orderlimits');
  const startDate = document.getElementById('startDate');
  const endDate = document.getElementById('endDate');
  const table = document.getElementById('ordertable');

  if (!table) return;

  function filterRows() {
    const query = searchInput.value.toLowerCase();
    const status = statusFilter.value;
    const delivery = deliveryStatusFilter.value;
    const method = methodFilter.value.toLowerCase();
    const limit = orderLimitFilter.value;
    const start = startDate.value ? new Date(startDate.value) : null;
    const end = endDate.value ? new Date(endDate.value) : null;
    const today = new Date();

    table.querySelectorAll('tbody tr').forEach(row => {
      const cells = row.children;
  const orderDate = new Date(cells[0].innerText);                  // Date
  const name = cells[1].innerText.toLowerCase();                   // Customer Name
  const orderStatusText = cells[2].innerText.trim();               // Order Status
  const deliveryStatusText = cells[5].innerText.trim();            // Delivery Status
  const methodText = cells[7].innerText.trim().toLowerCase();      // Payment Method

let show = true;

  if (query && !name.includes(query)) show = false;
  if (status && orderStatusText !== status) show = false;
  if (delivery && deliveryStatusText !== delivery) show = false;
  if (method && !methodText.includes(method)) show = false;
  if (start && orderDate < start) show = false;
  if (end && orderDate > end) show = false;

  if (limit) {
    const days = parseInt(limit.replace('days', ''));
    const limitDate = new Date(today);
    limitDate.setDate(today.getDate() - days);
    if (orderDate < limitDate) show = false;
  }

  row.style.display = show ? '' : 'none';
});
  }

  document.getElementById('filterButton').addEventListener('click', filterRows);
  document.getElementById('resetButton').addEventListener('click', () => {
    searchInput.value = '';
    statusFilter.value = '';
    deliveryStatusFilter.value = '';
    methodFilter.value = '';
    orderLimitFilter.value = '';
    startDate.value = '';
    endDate.value = '';
    table.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
  });
}



  function setupStatusDropdowns() {
   document.addEventListener('change', function (e) {
    if (e.target.matches('.order-dropdown')) {
      const select = e.target;
      const orderId = select.dataset.orderId;
      const newStatus = select.value;

      fetch('admin_update_orderstatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `order_id=${orderId}&order_status=${newStatus}`
      })
      .then(res => res.text())
      .then(() => {
        showToast("✅ Order status updated!");
        const badge = select.closest('tr').querySelector('.status-badge');
        if (badge) {
          badge.textContent = newStatus;
          badge.className = `status-badge ${newStatus.toLowerCase().replace(/\s+/g, '-')}`;
        }
      })
      .catch(() => showToast("❌ Failed to update order.", 'error'));
    }
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
            } else if (itemType === 'orders') {
              deleteUrl = `admin_delete_orders.php?id=${itemId}`;
            }else if (itemType === 'customer') {
  deleteUrl = `admin_delete_customer.php?id=${itemId}`;
}else if (itemType === 'staff') {
  deleteUrl = `admin_delete_staff.php?id=${itemId}`;
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
    if (document.getElementById('productTable')) setupTableSearch('productTable', [1, 2, 3, 4]);
    if (document.getElementById('customerTable')) setupTableSearch('customerTable', [1, 2, 3]);
    if (document.getElementById('brandTable')) setupTableSearch('brandTable', [1]);
    if (document.getElementById('categoryTable')) setupTableSearch('categoryTable', [0]);
        if (document.getElementById('staffTable')) setupTableSearch('staffTable', [1,2]);
    if (document.getElementById('ordertable')) setupAdvancedOrderFilter();
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

  const deleteResult = new URLSearchParams(window.location.search).get('delete');
if (deleteResult) {
  if (deleteResult === 'success') {
    showToast("✅ Staff deleted successfully!");
  } else if (deleteResult === 'self') {
    Swal.fire({
      icon: 'warning',
      title: 'You cannot delete yourself!',
      text: 'This action is not allowed to prevent account lockout.',
    });
  } else if (deleteResult === 'fail') {
    Swal.fire({
      icon: 'error',
      title: 'Something went wrong',
      text: 'Failed to delete the staff. Please try again.',
    });
  }

  // 🔄 Remove ?delete=xxx from URL after showing message
  const cleanUrl = window.location.pathname + window.location.search.replace(/([?&])delete=([^&]*)/, '');
  window.history.replaceState({}, document.title, cleanUrl);
}

// ✅ Show delete result feedback for product
const deleteproduct = new URLSearchParams(window.location.search).get('deleteproduct');
if (deleteproduct === 'success') {
  Swal.fire({
    toast: true,
    icon: 'success',
    title: '✅ Product deleted successfully!',
    position: 'top',
    timer: 2000,
    showConfirmButton: false,
    customClass: {
      popup: 'swal2-toast-custom'
    }
  });
} else if (deleteproduct === 'fail') {
  Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: '❌ Failed to delete the product. Please try again.',
  });
}

const deleteCustomer = new URLSearchParams(window.location.search).get('deletecustomer');
if (deleteCustomer === 'success') {
  Swal.fire({
    toast: true,
    icon: 'success',
    title: '✅ Customer deleted successfully!',
    position: 'top',
    timer: 2000,
    showConfirmButton: false,
    customClass: { popup: 'swal2-toast-custom' }
  });
} else if (deleteCustomer === 'fail') {
  Swal.fire({
    icon: 'error',
    title: 'Oops!',
    text: '❌ Failed to delete the customer. Please try again.',
  });
}


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
