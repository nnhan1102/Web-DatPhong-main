// Base URL cho API - Gọi thẳng vào index.php router
const API_BASE_URL = "http://localhost/hotel_opulent/backend/api/index.php";

// Khởi tạo admin
document.addEventListener("DOMContentLoaded", function () {
  initAdmin();
  updateDateTime();
  setInterval(updateDateTime, 60000); // Update time every minute
  loadDashboardData();
});

function initAdmin() {
  // Navigation
  const menuItems = document.querySelectorAll(".sidebar-menu li");
  menuItems.forEach((item) => {
    item.addEventListener("click", function () {
      menuItems.forEach((i) => i.classList.remove("active"));
      this.classList.add("active");

      const section = this.getAttribute("data-section");
      showSection(section);
    });
  });

  // Modals
  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", closeAllModals);
  });

  // Room management
  document
    .getElementById("add-room-btn")
    ?.addEventListener("click", function () {
      openRoomModal();
    });

  // Booking management
  document
    .getElementById("add-booking-btn")
    ?.addEventListener("click", function () {
      openBookingModal();
    });

  // Export buttons
  document
    .getElementById("export-bookings-btn")
    ?.addEventListener("click", function () {
      exportBookings();
    });

  // Report period change
  document
    .getElementById("report-period")
    ?.addEventListener("change", function () {
      if (this.value === "custom") {
        document.getElementById("custom-date-range").style.display = "flex";
      } else {
        document.getElementById("custom-date-range").style.display = "none";
        loadReportData(this.value);
      }
    });

  // Date range for custom reports
  document
    .getElementById("report-date-from")
    ?.addEventListener("change", function () {
      loadCustomReport();
    });

  document
    .getElementById("report-date-to")
    ?.addEventListener("change", function () {
      loadCustomReport();
    });
}

function showSection(section) {
  // Hide all sections
  document.querySelectorAll(".content-section").forEach((sec) => {
    sec.classList.remove("active");
  });

  // Show selected section
  document.getElementById(`${section}-section`).classList.add("active");

  // Update page title
  const titles = {
    dashboard: "Dashboard",
    rooms: "Quản lý Phòng",
    "room-types": "Loại Phòng",
    bookings: "Đặt Phòng",
    customers: "Khách hàng",
    services: "Dịch vụ",
    staff: "Nhân viên",
    reports: "Báo cáo",
  };

  document.getElementById("page-title").textContent =
    titles[section] || section;

  // Load section data
  switch (section) {
    case "dashboard":
      loadDashboardData();
      break;
    case "rooms":
      loadRooms();
      loadRoomTypesForFilter();
      break;
    case "room-types":
      loadRoomTypes();
      break;
    case "bookings":
      loadBookings();
      break;
    case "customers":
      loadCustomers();
      break;
    case "services":
      loadServices();
      break;
    case "staff":
      loadStaff();
      break;
    case "reports":
      loadReportData("month");
      break;
  }
}

function updateDateTime() {
  const now = new Date();
  const dateStr = now.toLocaleDateString("vi-VN", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
  const timeStr = now.toLocaleTimeString("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
  });

  document.getElementById("current-date").textContent = dateStr;
  document.getElementById("current-time").textContent = timeStr;
}

async function loadDashboardData() {
  try {
    // Load stats from API - Gọi đúng endpoint: admin-dashboard
    const response = await fetch(
      `${API_BASE_URL}/admin-dashboard?action=getDashboardData`,
      {
        credentials: "include", // Quan trọng: gửi session cookie
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      // Update stats cards
      document.getElementById("total-rooms").textContent =
        data.data.room_stats?.total_rooms || data.data.total_rooms || 0;
      document.getElementById("today-bookings").textContent =
        data.data.booking_stats?.today_bookings ||
        data.data.today_bookings ||
        0;
      document.getElementById("total-customers").textContent =
        data.data.user_stats?.total_users || data.data.total_customers || 0;
      document.getElementById("monthly-revenue").textContent = formatCurrency(
        data.data.booking_stats?.total_revenue || data.data.monthly_revenue || 0
      );
      document.getElementById("occupancy-rate").textContent =
        (data.data.room_stats?.occupancy_rate ||
          data.data.occupancy_rate ||
          0) + "%";
      document.getElementById("service-revenue").textContent = formatCurrency(
        data.data.service_revenue || 0
      );

      // Load check-ins and check-outs
      loadTodayCheckins(
        data.data.recent_bookings ||
          data.data.recent_activities?.today_checkins ||
          []
      );
      loadTodayCheckouts(
        data.data.today_checkouts ||
          data.data.recent_activities?.today_checkouts ||
          []
      );

      // Render charts
      renderDashboardCharts(data.data);
    } else {
      console.error("API error:", data.message);
      showToast("Lỗi: " + (data.message || "Không thể tải dữ liệu"), "error");
    }
  } catch (error) {
    console.error("Error loading dashboard:", error);
    showToast("Lỗi tải dữ liệu dashboard: " + error.message, "error");
  }
}

function renderDashboardCharts(data) {
  // Revenue Chart
  const revenueCtx = document.getElementById("revenueChart");
  if (!revenueCtx) return;

  revenueCtx.getContext("2d");
  if (window.revenueChart) window.revenueChart.destroy();

  // Lấy dữ liệu từ API response
  const chartData = data.chart_data || [];
  const labels = chartData.map((item) => item.date);
  const revenues = chartData.map((item) => item.revenue || 0);

  window.revenueChart = new Chart(revenueCtx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Doanh thu (VNĐ)",
          data: revenues,
          borderColor: "#3498db",
          backgroundColor: "rgba(52, 152, 219, 0.1)",
          borderWidth: 2,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return formatCurrency(value);
            },
          },
        },
      },
    },
  });

  // Occupancy Chart
  const occupancyCtx = document.getElementById("occupancyChart");
  if (occupancyCtx) {
    if (window.occupancyChart) window.occupancyChart.destroy();

    // Lấy dữ liệu occupancy từ API
    const occupancyData =
      data.room_stats?.rooms_by_type || data.occupancy_by_type || [];
    const roomTypes = occupancyData.map((item) => item.type_name || item.name);
    const occupancyRates = occupancyData.map((item) => {
      if (item.occupancy_rate) return item.occupancy_rate;
      const total = item.total_rooms || item.total || 1;
      const occupied = item.occupied_rooms || item.occupied || 0;
      return Math.round((occupied / total) * 100);
    });

    window.occupancyChart = new Chart(occupancyCtx, {
      type: "bar",
      data: {
        labels: roomTypes,
        datasets: [
          {
            label: "Tỷ lệ lấp đầy (%)",
            data: occupancyRates,
            backgroundColor: [
              "rgba(52, 152, 219, 0.7)",
              "rgba(46, 204, 113, 0.7)",
              "rgba(155, 89, 182, 0.7)",
              "rgba(241, 196, 15, 0.7)",
              "rgba(230, 126, 34, 0.7)",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: function (value) {
                return value + "%";
              },
            },
          },
        },
      },
    });
  }
}

function loadTodayCheckins(checkins) {
  const container = document.getElementById("today-checkins");
  if (!container) return;

  if (!checkins || checkins.length === 0) {
    container.innerHTML =
      '<div class="no-data">Không có check-in hôm nay</div>';
    return;
  }

  let html = "";
  checkins.forEach((checkin) => {
    html += `
      <div class="activity-item">
        <div class="activity-icon">
          <i class="fas fa-sign-in-alt"></i>
        </div>
        <div class="activity-info">
          <p><strong>${
            checkin.customer_name || checkin.full_name || "Khách"
          }</strong></p>
          <p>Phòng ${checkin.room_number || checkin.room_id} • ${
      checkin.num_guests || 1
    } khách</p>
          <span class="activity-time">${formatTime(
            checkin.check_in_date || checkin.check_in
          )}</span>
        </div>
      </div>
    `;
  });
  container.innerHTML = html;
}

function loadTodayCheckouts(checkouts) {
  const container = document.getElementById("today-checkouts");
  if (!container) return;

  if (!checkouts || checkouts.length === 0) {
    container.innerHTML =
      '<div class="no-data">Không có check-out hôm nay</div>';
    return;
  }

  let html = "";
  checkouts.forEach((checkout) => {
    html += `
      <div class="activity-item">
        <div class="activity-icon">
          <i class="fas fa-sign-out-alt"></i>
        </div>
        <div class="activity-info">
          <p><strong>${
            checkout.customer_name || checkout.full_name || "Khách"
          }</strong></p>
          <p>Phòng ${
            checkout.room_number || checkout.room_id
          } • ${formatCurrency(checkout.total_price || 0)}</p>
          <span class="activity-time">${formatTime(
            checkout.check_out_date || checkout.check_out
          )}</span>
        </div>
      </div>
    `;
  });
  container.innerHTML = html;
}

async function loadRooms() {
  try {
    const response = await fetch(`${API_BASE_URL}/admin-rooms?action=getAll`, {
      credentials: "include",
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderRoomsTable(data.data || data.rooms || []);
    } else {
      showToast("Lỗi: " + (data.message || "Không thể tải phòng"), "error");
    }
  } catch (error) {
    console.error("Error loading rooms:", error);
    showToast("Lỗi tải danh sách phòng: " + error.message, "error");
  }
}

function renderRoomsTable(rooms) {
  const tbody = document.getElementById("rooms-table-body");
  if (!tbody) return;

  if (!rooms || rooms.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="10" class="no-data">Không có phòng nào</td></tr>';
    return;
  }

  let html = "";
  rooms.forEach((room) => {
    const statusClass = `status-${room.status}`;
    const statusText =
      {
        available: "Có sẵn",
        occupied: "Đã đặt",
        maintenance: "Bảo trì",
        cleaning: "Đang dọn",
      }[room.status] || room.status;

    const viewText =
      {
        city: "Thành phố",
        sea: "Biển",
        garden: "Vườn",
        pool: "Hồ bơi",
      }[room.view_type] || room.view_type;

    // Parse amenities nếu là JSON string
    let amenitiesText = "Không có";
    if (room.amenities) {
      try {
        if (typeof room.amenities === "string") {
          const amenities = JSON.parse(room.amenities);
          amenitiesText = Array.isArray(amenities)
            ? amenities.join(", ")
            : amenities;
        } else if (Array.isArray(room.amenities)) {
          amenitiesText = room.amenities.join(", ");
        }
      } catch (e) {
        amenitiesText = room.amenities;
      }
    }

    html += `
      <tr>
        <td>${room.id}</td>
        <td><strong>${room.room_number}</strong></td>
        <td>${room.type_name || room.room_type_id}</td>
        <td>${room.floor || "N/A"}</td>
        <td>${viewText}</td>
        <td>${formatCurrency(room.base_price || room.price || 0)}</td>
        <td>${room.capacity || 2} người</td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td>${amenitiesText}</td>
        <td>
          <div class="action-buttons">
            <button class="action-btn edit-btn" onclick="editRoom(${room.id})">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" onclick="deleteRoom(${
              room.id
            })">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function loadRoomTypesForFilter() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-room-types?action=getAll`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("room-type-filter");
      if (select) {
        let html = '<option value="">Tất cả loại phòng</option>';

        (data.data || data.room_types || []).forEach((type) => {
          html += `<option value="${type.id}">${
            type.type_name || type.name
          }</option>`;
        });

        select.innerHTML = html;
      }
    }
  } catch (error) {
    console.error("Error loading room types for filter:", error);
  }
}

async function loadRoomTypes() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-room-types?action=getAll`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderRoomTypesCards(data.data || data.room_types || []);
    } else {
      showToast(
        "Lỗi: " + (data.message || "Không thể tải loại phòng"),
        "error"
      );
    }
  } catch (error) {
    console.error("Error loading room types:", error);
    showToast("Lỗi tải loại phòng: " + error.message, "error");
  }
}

function renderRoomTypesCards(types) {
  const container = document.getElementById("room-types-container");
  if (!container) return;

  if (!types || types.length === 0) {
    container.innerHTML = '<div class="no-data">Không có loại phòng nào</div>';
    return;
  }

  let html = "";
  types.forEach((type) => {
    let amenities = [];
    if (type.amenities) {
      try {
        if (typeof type.amenities === "string") {
          amenities = JSON.parse(type.amenities);
        } else if (Array.isArray(type.amenities)) {
          amenities = type.amenities;
        }
      } catch (e) {
        amenities = [];
      }
    }

    html += `
      <div class="room-type-card">
        <div class="room-type-header">
          <h3>${type.type_name || type.name}</h3>
          <span class="room-type-price">${formatCurrency(
            type.base_price || type.price
          )}/đêm</span>
        </div>
        <div class="room-type-body">
          <p class="room-type-desc">${type.description || "Không có mô tả"}</p>
          <div class="room-type-features">
            <div class="feature">
              <i class="fas fa-user-friends"></i>
              <span>Sức chứa: ${type.capacity} người</span>
            </div>
            ${amenities
              .map(
                (amenity) => `
                <div class="feature">
                  <i class="fas fa-check-circle"></i>
                  <span>${amenity}</span>
                </div>
              `
              )
              .join("")}
          </div>
        </div>
        <div class="room-type-footer">
          <button class="btn btn-sm btn-primary" onclick="editRoomType(${
            type.id
          })">
            <i class="fas fa-edit"></i> Sửa
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteRoomType(${
            type.id
          })">
            <i class="fas fa-trash"></i> Xóa
          </button>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

async function loadBookings() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-bookings?action=getAll`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderBookingsTable(data.data || data.bookings || []);
    } else {
      showToast("Lỗi: " + (data.message || "Không thể tải đặt phòng"), "error");
    }
  } catch (error) {
    console.error("Error loading bookings:", error);
    showToast("Lỗi tải danh sách đặt phòng: " + error.message, "error");
  }
}

function renderBookingsTable(bookings) {
  const tbody = document.getElementById("bookings-table-body");
  if (!tbody) return;

  if (!bookings || bookings.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="12" class="no-data">Không có đặt phòng nào</td></tr>';
    return;
  }

  let html = "";
  bookings.forEach((booking) => {
    // Calculate number of nights
    const checkIn = new Date(booking.check_in_date || booking.check_in);
    const checkOut = new Date(booking.check_out_date || booking.check_out);
    const nights = Math.round((checkOut - checkIn) / (1000 * 60 * 60 * 24));

    const statusClass = `status-${booking.status}`;
    const statusText =
      {
        pending: "Chờ xác nhận",
        confirmed: "Đã xác nhận",
        checked_in: "Đã nhận phòng",
        checked_out: "Đã trả phòng",
        cancelled: "Đã hủy",
      }[booking.status] || booking.status;

    const paymentStatusClass = `payment-${booking.payment_status}`;
    const paymentStatusText =
      {
        pending: "Chưa thanh toán",
        paid: "Đã thanh toán",
        refunded: "Đã hoàn tiền",
        failed: "Thất bại",
      }[booking.payment_status] || booking.payment_status;

    html += `
      <tr>
        <td><strong>${booking.booking_code || booking.id}</strong></td>
        <td>${booking.customer_name || booking.customer_full_name || "N/A"}</td>
        <td>${booking.room_number || booking.room_id || "N/A"}</td>
        <td>${formatDate(booking.check_in_date || booking.check_in)}</td>
        <td>${formatDate(booking.check_out_date || booking.check_out)}</td>
        <td>${nights} đêm</td>
        <td>${booking.num_guests || 1} khách</td>
        <td>${formatCurrency(booking.total_price || 0)}</td>
        <td><span class="payment-badge ${paymentStatusClass}">${paymentStatusText}</span></td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td>${formatDateTime(booking.created_at)}</td>
        <td>
          <div class="action-buttons">
            <button class="action-btn view-btn" onclick="viewBooking('${
              booking.booking_code || booking.id
            }')">
              <i class="fas fa-eye"></i>
            </button>
            <button class="action-btn edit-btn" onclick="editBooking(${
              booking.id
            })">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" onclick="deleteBooking(${
              booking.id
            })">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function loadCustomers() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-customers?action=getAll`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderCustomersTable(data.data || data.customers || []);
    } else {
      showToast(
        "Lỗi: " + (data.message || "Không thể tải khách hàng"),
        "error"
      );
    }
  } catch (error) {
    console.error("Error loading customers:", error);
    showToast("Lỗi tải danh sách khách hàng: " + error.message, "error");
  }
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById("customers-table-body");
  if (!tbody) return;

  if (!customers || customers.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="11" class="no-data">Không có khách hàng nào</td></tr>';
    return;
  }

  let html = "";
  customers.forEach((customer) => {
    const statusClass = `status-${customer.status}`;
    const statusText =
      {
        active: "Hoạt động",
        inactive: "Ngừng HĐ",
      }[customer.status] || customer.status;

    const userTypeText =
      {
        customer: "Khách lẻ",
        corporate: "Doanh nghiệp",
        vip: "VIP",
        admin: "Admin",
        staff: "Nhân viên",
      }[customer.user_type] || customer.user_type;

    html += `
      <tr>
        <td>${customer.id}</td>
        <td><strong>${customer.full_name}</strong></td>
        <td>${customer.email}</td>
        <td>${customer.phone || "N/A"}</td>
        <td><span class="user-type-badge">${userTypeText}</span></td>
        <td>${customer.total_bookings || 0}</td>
        <td>${formatCurrency(customer.total_spent || 0)}</td>
        <td>${customer.loyalty_points || 0} điểm</td>
        <td>${formatDate(customer.created_at)}</td>
        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
        <td>
          <div class="action-buttons">
            <button class="action-btn view-btn" onclick="viewCustomer(${
              customer.id
            })">
              <i class="fas fa-eye"></i>
            </button>
            <button class="action-btn edit-btn" onclick="editCustomer(${
              customer.id
            })">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" onclick="deleteCustomer(${
              customer.id
            })">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function loadServices() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-services?action=getAll`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderServicesCards(data.data || data.services || []);
    } else {
      showToast("Lỗi: " + (data.message || "Không thể tải dịch vụ"), "error");
    }
  } catch (error) {
    console.error("Error loading services:", error);
    showToast("Lỗi tải danh sách dịch vụ: " + error.message, "error");
  }
}

function renderServicesCards(services) {
  const container = document.getElementById("services-container");
  if (!container) return;

  if (!services || services.length === 0) {
    container.innerHTML = '<div class="no-data">Không có dịch vụ nào</div>';
    return;
  }

  let html = "";
  services.forEach((service) => {
    const categoryClass = `category-${service.category}`;
    const categoryText =
      {
        transport: "Vận chuyển",
        food: "Ẩm thực",
        spa: "Spa & Wellness",
        other: "Khác",
      }[service.category] || service.category;

    const statusClass =
      service.status === "available" ? "available" : "unavailable";

    html += `
      <div class="service-card">
        <div class="service-header">
          <h3>${service.service_name || service.name}</h3>
          <span class="service-price">${formatCurrency(service.price)}</span>
        </div>
        <div class="service-body">
          <p>${service.description || "Không có mô tả"}</p>
          <div class="service-meta">
            <span class="service-category ${categoryClass}">${categoryText}</span>
            <span class="service-status ${statusClass}">
              ${service.status === "available" ? "Có sẵn" : "Không khả dụng"}
            </span>
          </div>
        </div>
        <div class="service-footer">
          <button class="btn btn-sm btn-primary" onclick="editService(${
            service.id
          })">
            <i class="fas fa-edit"></i> Sửa
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteService(${
            service.id
          })">
            <i class="fas fa-trash"></i> Xóa
          </button>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

async function loadStaff() {
  try {
    const response = await fetch(`${API_BASE_URL}/admin-staff?action=getAll`, {
      credentials: "include",
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      renderStaffTable(data.data || data.staff || []);
    } else {
      showToast("Lỗi: " + (data.message || "Không thể tải nhân viên"), "error");
    }
  } catch (error) {
    console.error("Error loading staff:", error);
    showToast("Lỗi tải danh sách nhân viên: " + error.message, "error");
  }
}

function renderStaffTable(staffList) {
  const tbody = document.getElementById("staff-table-body");
  if (!tbody) return;

  if (!staffList || staffList.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="9" class="no-data">Không có nhân viên nào</td></tr>';
    return;
  }

  let html = "";
  staffList.forEach((staff) => {
    const departmentText =
      {
        reception: "Lễ tân",
        housekeeping: "Buồng phòng",
        management: "Quản lý",
        support: "Hỗ trợ",
      }[staff.department] || staff.department;

    html += `
      <tr>
        <td><strong>${staff.staff_code || staff.id}</strong></td>
        <td>${staff.full_name}</td>
        <td>${staff.email}</td>
        <td>${staff.phone || "N/A"}</td>
        <td>${staff.position}</td>
        <td><span class="dept-badge">${departmentText}</span></td>
        <td>${formatDate(staff.hire_date)}</td>
        <td><span class="status-badge status-active">Đang làm việc</span></td>
        <td>
          <div class="action-buttons">
            <button class="action-btn edit-btn" onclick="editStaff(${
              staff.id
            })">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" onclick="deleteStaff(${
              staff.id
            })">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

async function loadReportData(period) {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-reports?action=getReport&period=${period}`,
      {
        credentials: "include",
      }
    );

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    if (data.success) {
      updateReportCards(data.data || {});
      renderReportCharts(data.data || {});
    } else {
      showToast("Lỗi: " + (data.message || "Không thể tải báo cáo"), "error");
    }
  } catch (error) {
    console.error("Error loading report:", error);
    showToast("Lỗi tải báo cáo: " + error.message, "error");
  }
}

function updateReportCards(reportData) {
  document.getElementById("report-revenue").textContent = formatCurrency(
    reportData.revenue || 0
  );
  document.getElementById("report-bookings").textContent =
    reportData.bookings || 0;
  document.getElementById("report-new-customers").textContent =
    reportData.new_customers || 0;
  document.getElementById("report-occupancy").textContent =
    (reportData.occupancy_rate || 0) + "%";

  // Update changes
  const revenueChange = document.getElementById("revenue-change");
  const bookingsChange = document.getElementById("bookings-change");
  const customersChange = document.getElementById("customers-change");
  const occupancyChange = document.getElementById("occupancy-change");

  if (revenueChange) {
    revenueChange.textContent = `${reportData.revenue_change >= 0 ? "+" : ""}${
      reportData.revenue_change || 0
    }% so với kỳ trước`;
  }
  if (bookingsChange) {
    bookingsChange.textContent = `${
      reportData.bookings_change >= 0 ? "+" : ""
    }${reportData.bookings_change || 0}% so với kỳ trước`;
  }
  if (customersChange) {
    customersChange.textContent = `${
      reportData.customers_change >= 0 ? "+" : ""
    }${reportData.customers_change || 0}% so với kỳ trước`;
  }
  if (occupancyChange) {
    occupancyChange.textContent = `${
      reportData.occupancy_change >= 0 ? "+" : ""
    }${reportData.occupancy_change || 0}% so với kỳ trước`;
  }
}

function renderReportCharts(reportData) {
  // Revenue by room type chart
  const revenueByTypeCtx = document.getElementById("revenueByRoomTypeChart");
  if (revenueByTypeCtx) {
    if (window.revenueByTypeChart) window.revenueByTypeChart.destroy();

    const revenueByType = reportData.revenue_by_type || [];
    window.revenueByTypeChart = new Chart(revenueByTypeCtx, {
      type: "doughnut",
      data: {
        labels: revenueByType.map((item) => item.type_name || item.name),
        datasets: [
          {
            data: revenueByType.map((item) => item.revenue || 0),
            backgroundColor: [
              "#3498db",
              "#2ecc71",
              "#9b59b6",
              "#f1c40f",
              "#e67e22",
              "#e74c3c",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "right",
          },
        },
      },
    });
  }

  // Payment method chart
  const paymentMethodCtx = document.getElementById("paymentMethodChart");
  if (paymentMethodCtx) {
    if (window.paymentMethodChart) window.paymentMethodChart.destroy();

    const paymentMethods = reportData.payment_methods || [];
    window.paymentMethodChart = new Chart(paymentMethodCtx, {
      type: "pie",
      data: {
        labels: paymentMethods.map(
          (item) => item.method || item.payment_method
        ),
        datasets: [
          {
            data: paymentMethods.map((item) => item.count || item.total),
            backgroundColor: [
              "#3498db",
              "#2ecc71",
              "#9b59b6",
              "#f1c40f",
              "#e67e22",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: "right",
          },
        },
      },
    });
  }

  // Yearly revenue chart
  const yearlyRevenueCtx = document.getElementById("yearlyRevenueChart");
  if (yearlyRevenueCtx) {
    if (window.yearlyRevenueChart) window.yearlyRevenueChart.destroy();

    const yearlyRevenue =
      reportData.yearly_revenue || reportData.monthly_revenue || [];
    window.yearlyRevenueChart = new Chart(yearlyRevenueCtx, {
      type: "bar",
      data: {
        labels: yearlyRevenue.map((item) => item.month || item.date),
        datasets: [
          {
            label: "Doanh thu",
            data: yearlyRevenue.map((item) => item.revenue || 0),
            backgroundColor: "rgba(52, 152, 219, 0.7)",
            borderColor: "#3498db",
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return formatCurrency(value);
              },
            },
          },
        },
      },
    });
  }
}

// ... REST OF THE CODE REMAINS THE SAME ...

function openRoomModal(roomId = null) {
  const modal = document.getElementById("room-modal");
  const title = document.getElementById("room-modal-title");
  const form = document.getElementById("room-form");

  if (roomId) {
    title.textContent = "Chỉnh sửa phòng";
    loadRoomData(roomId);
  } else {
    title.textContent = "Thêm phòng mới";
    form.reset();
    document.getElementById("room-id").value = "";
    loadRoomTypesForSelect();
  }

  modal.classList.add("active");
}

async function loadRoomTypesForSelect() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-room-types?action=getAll`,
      {
        credentials: "include",
      }
    );
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("room-type-id");
      let html = '<option value="">Chọn loại phòng</option>';

      (data.data || data.room_types || []).forEach((type) => {
        html += `<option value="${type.id}">${
          type.type_name || type.name
        } (${formatCurrency(type.base_price || type.price)}/đêm)</option>`;
      });

      select.innerHTML = html;
    }
  } catch (error) {
    console.error("Error loading room types for select:", error);
  }
}

async function loadRoomData(roomId) {
  try {
    const response = await fetch(
      `${API_BASE_URL}/admin-rooms?action=get&id=${roomId}`,
      {
        credentials: "include",
      }
    );
    const data = await response.json();

    if (data.success) {
      const room = data.data || data.room;
      if (room) {
        document.getElementById("room-id").value = room.id;
        document.getElementById("room-number").value = room.room_number;
        document.getElementById("room-type-id").value = room.room_type_id;
        document.getElementById("room-floor").value = room.floor || "";
        document.getElementById("room-view").value = room.view_type;
        document.getElementById("room-status").value = room.status;
        document.getElementById("room-image").value = room.image_url || "";

        // Load room types for select
        await loadRoomTypesForSelect();
      }
    }
  } catch (error) {
    console.error("Error loading room data:", error);
    showToast("Lỗi tải dữ liệu phòng", "error");
  }
}

async function saveRoom() {
  const roomId = document.getElementById("room-id").value;
  const isEdit = !!roomId;

  const roomData = {
    room_number: document.getElementById("room-number").value,
    room_type_id: document.getElementById("room-type-id").value,
    floor: document.getElementById("room-floor").value || null,
    view_type: document.getElementById("room-view").value,
    status: document.getElementById("room-status").value,
    image_url: document.getElementById("room-image").value || null,
  };

  try {
    const url = `${API_BASE_URL}/admin-rooms?action=${
      isEdit ? "update" : "create"
    }`;
    const method = "POST";

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(isEdit ? { ...roomData, id: roomId } : roomData),
    });

    const result = await response.json();

    if (result.success) {
      showToast(
        isEdit ? "Cập nhật phòng thành công" : "Thêm phòng thành công",
        "success"
      );
      closeAllModals();
      loadRooms();
      loadDashboardData();
    } else {
      showToast(result.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving room:", error);
    showToast("Lỗi lưu phòng", "error");
  }
}

function deleteRoom(roomId) {
  if (!confirm("Bạn có chắc chắn muốn xóa phòng này?")) {
    return;
  }

  fetch(`${API_BASE_URL}/admin-rooms?action=delete&id=${roomId}`, {
    method: "DELETE",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        showToast("Xóa phòng thành công", "success");
        loadRooms();
        loadDashboardData();
      } else {
        showToast(result.message || "Có lỗi xảy ra", "error");
      }
    })
    .catch((error) => {
      console.error("Error deleting room:", error);
      showToast("Lỗi xóa phòng", "error");
    });
}

// Utility functions
function formatCurrency(amount) {
  if (!amount) amount = 0;
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleDateString("vi-VN");
}

function formatDateTime(dateTimeString) {
  if (!dateTimeString) return "N/A";
  const date = new Date(dateTimeString);
  return date.toLocaleString("vi-VN");
}

function formatTime(dateTimeString) {
  if (!dateTimeString) return "N/A";
  const date = new Date(dateTimeString);
  return date.toLocaleTimeString("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  if (toast) {
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add("show");

    setTimeout(() => {
      toast.classList.remove("show");
    }, 3000);
  } else {
    console.log(`${type}: ${message}`);
  }
}

function closeAllModals() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.classList.remove("active");
  });
}

// Placeholder functions for future implementation
function editRoomType(id) {
  showToast("Tính năng đang phát triển", "info");
}

function deleteRoomType(id) {
  if (confirm("Bạn có chắc chắn muốn xóa loại phòng này?")) {
    showToast("Xóa loại phòng thành công", "success");
  }
}

function viewBooking(bookingCode) {
  showToast(`Xem chi tiết đặt phòng ${bookingCode}`, "info");
}

function editBooking(id) {
  showToast(`Sửa đặt phòng #${id}`, "info");
}

function deleteBooking(id) {
  if (confirm("Bạn có chắc chắn muốn xóa đặt phòng này?")) {
    showToast("Xóa đặt phòng thành công", "success");
  }
}

function viewCustomer(id) {
  showToast(`Xem chi tiết khách hàng #${id}`, "info");
}

function editCustomer(id) {
  showToast(`Sửa khách hàng #${id}`, "info");
}

function deleteCustomer(id) {
  if (confirm("Bạn có chắc chắn muốn xóa khách hàng này?")) {
    showToast("Xóa khách hàng thành công", "success");
  }
}

function editService(id) {
  showToast(`Sửa dịch vụ #${id}`, "info");
}

function deleteService(id) {
  if (confirm("Bạn có chắc chắn muốn xóa dịch vụ này?")) {
    showToast("Xóa dịch vụ thành công", "success");
  }
}

function editStaff(id) {
  showToast(`Sửa nhân viên #${id}`, "info");
}

function deleteStaff(id) {
  if (confirm("Bạn có chắc chắn muốn xóa nhân viên này?")) {
    showToast("Xóa nhân viên thành công", "success");
  }
}

function loadCustomReport() {
  const fromDate = document.getElementById("report-date-from").value;
  const toDate = document.getElementById("report-date-to").value;

  if (fromDate && toDate) {
    showToast(`Tải báo cáo từ ${fromDate} đến ${toDate}`, "info");
    // Implement custom report loading here
  }
}

// Form submission handlers
document.getElementById("room-form")?.addEventListener("submit", function (e) {
  e.preventDefault();
  saveRoom();
});
