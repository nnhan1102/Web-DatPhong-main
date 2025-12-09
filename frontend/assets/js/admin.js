// Base URL cho API
const API_BASE_URL = "http://localhost/hotel_opulent/backend/api";

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
    // Load stats from API
    const response = await fetch(
      `${API_BASE_URL}/dashboard.php?action=getDashboardData`
    );
    const data = await response.json();

    if (data.success) {
      // Update stats cards
      document.getElementById("total-rooms").textContent =
        data.data.overview?.total_rooms || 0;
      document.getElementById("today-bookings").textContent =
        data.data.overview?.today_bookings || 0;
      document.getElementById("total-customers").textContent =
        data.data.overview?.total_customers || 0;
      document.getElementById("monthly-revenue").textContent = formatCurrency(
        data.data.overview?.monthly_revenue || 0
      );
      document.getElementById("occupancy-rate").textContent =
        (data.data.occupancy_stats?.current_occupancy_rate || 0) + "%";
      document.getElementById("service-revenue").textContent = formatCurrency(
        data.data.overview?.service_revenue || 0
      );

      // Load check-ins and check-outs
      loadTodayCheckins(data.data.recent_activities?.today_checkins || []);
      loadTodayCheckouts(data.data.recent_activities?.today_checkouts || []);

      // Render charts
      renderDashboardCharts(data.data);
    }
  } catch (error) {
    console.error("Error loading dashboard:", error);
    showToast("Lỗi tải dữ liệu dashboard", "error");
  }
}

function renderDashboardCharts(data) {
  // Revenue Chart
  const revenueCtx = document.getElementById("revenueChart").getContext("2d");
  if (window.revenueChart) window.revenueChart.destroy();

  const revenueData = data.time_stats?.monthly_revenue || [];
  const months = revenueData.map((item) => item.month);
  const revenues = revenueData.map((item) => item.revenue || 0);

  window.revenueChart = new Chart(revenueCtx, {
    type: "line",
    data: {
      labels: months,
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
  const occupancyCtx = document
    .getElementById("occupancyChart")
    .getContext("2d");
  if (window.occupancyChart) window.occupancyChart.destroy();

  const occupancyData = data.occupancy_stats?.occupancy_by_type || [];
  const roomTypes = occupancyData.map((item) => item.type_name);
  const occupancyRates = occupancyData.map((item) => {
    const total = item.total_rooms || 1;
    const occupied = item.occupied_rooms || 0;
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

function loadTodayCheckins(checkins) {
  const container = document.getElementById("today-checkins");
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
                            <p><strong>${checkin.customer_name}</strong></p>
                            <p>Phòng ${checkin.room_number} • ${
      checkin.num_guests
    } khách</p>
                            <span class="activity-time">${formatTime(
                              checkin.check_in
                            )}</span>
                        </div>
                    </div>
                `;
  });
  container.innerHTML = html;
}

function loadTodayCheckouts(checkouts) {
  const container = document.getElementById("today-checkouts");
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
                            <p><strong>${checkout.customer_name}</strong></p>
                            <p>Phòng ${checkout.room_number} • ${formatCurrency(
      checkout.total_price
    )}</p>
                            <span class="activity-time">${formatTime(
                              checkout.check_out
                            )}</span>
                        </div>
                    </div>
                `;
  });
  container.innerHTML = html;
}

async function loadRooms() {
  try {
    const response = await fetch(`${API_BASE_URL}/rooms.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      renderRoomsTable(data.data);
    }
  } catch (error) {
    console.error("Error loading rooms:", error);
    showToast("Lỗi tải danh sách phòng", "error");
  }
}

function renderRoomsTable(rooms) {
  const tbody = document.getElementById("rooms-table-body");

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

    html += `
                    <tr>
                        <td>${room.id}</td>
                        <td><strong>${room.room_number}</strong></td>
                        <td>${room.type_name || room.room_type_id}</td>
                        <td>${room.floor || "N/A"}</td>
                        <td>${viewText}</td>
                        <td>${formatCurrency(room.base_price || 0)}</td>
                        <td>${room.capacity || 2} người</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${
                          room.amenities
                            ? JSON.parse(room.amenities).join(", ")
                            : "Không có"
                        }</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn" onclick="editRoom(${
                                  room.id
                                })">
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
      `${API_BASE_URL}/room-types.php?action=getAll`
    );
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("room-type-filter");
      let html = '<option value="">Tất cả loại phòng</option>';

      data.data.forEach((type) => {
        html += `<option value="${type.id}">${type.type_name}</option>`;
      });

      select.innerHTML = html;
    }
  } catch (error) {
    console.error("Error loading room types:", error);
  }
}

async function loadRoomTypes() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/room-types.php?action=getAll`
    );
    const data = await response.json();

    if (data.success) {
      renderRoomTypesCards(data.data);
    }
  } catch (error) {
    console.error("Error loading room types:", error);
    showToast("Lỗi tải loại phòng", "error");
  }
}

function renderRoomTypesCards(types) {
  const container = document.getElementById("room-types-container");

  if (!types || types.length === 0) {
    container.innerHTML = '<div class="no-data">Không có loại phòng nào</div>';
    return;
  }

  let html = "";
  types.forEach((type) => {
    const amenities = type.amenities ? JSON.parse(type.amenities) : [];

    html += `
                    <div class="room-type-card">
                        <div class="room-type-header">
                            <h3>${type.type_name}</h3>
                            <span class="room-type-price">${formatCurrency(
                              type.base_price
                            )}/đêm</span>
                        </div>
                        <div class="room-type-body">
                            <p class="room-type-desc">${
                              type.description || "Không có mô tả"
                            }</p>
                            <div class="room-type-features">
                                <div class="feature">
                                    <i class="fas fa-user-friends"></i>
                                    <span>Sức chứa: ${
                                      type.capacity
                                    } người</span>
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
    const response = await fetch(`${API_BASE_URL}/bookings.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      renderBookingsTable(data.data);
    }
  } catch (error) {
    console.error("Error loading bookings:", error);
    showToast("Lỗi tải danh sách đặt phòng", "error");
  }
}

function renderBookingsTable(bookings) {
  const tbody = document.getElementById("bookings-table-body");

  if (!bookings || bookings.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="12" class="no-data">Không có đặt phòng nào</td></tr>';
    return;
  }

  let html = "";
  bookings.forEach((booking) => {
    // Calculate number of nights
    const checkIn = new Date(booking.check_in);
    const checkOut = new Date(booking.check_out);
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
                        <td><strong>${booking.booking_code}</strong></td>
                        <td>${booking.customer_name || "N/A"}</td>
                        <td>${booking.room_number || "N/A"}</td>
                        <td>${formatDate(booking.check_in)}</td>
                        <td>${formatDate(booking.check_out)}</td>
                        <td>${nights} đêm</td>
                        <td>${booking.num_guests} khách</td>
                        <td>${formatCurrency(booking.total_price)}</td>
                        <td><span class="payment-badge ${paymentStatusClass}">${paymentStatusText}</span></td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                        <td>${formatDateTime(booking.created_at)}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn view-btn" onclick="viewBooking('${
                                  booking.booking_code
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
    const response = await fetch(`${API_BASE_URL}/customers.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      renderCustomersTable(data.data);
    }
  } catch (error) {
    console.error("Error loading customers:", error);
    showToast("Lỗi tải danh sách khách hàng", "error");
  }
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById("customers-table-body");

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
    const response = await fetch(`${API_BASE_URL}/services.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      renderServicesCards(data.data);
    }
  } catch (error) {
    console.error("Error loading services:", error);
    showToast("Lỗi tải danh sách dịch vụ", "error");
  }
}

function renderServicesCards(services) {
  const container = document.getElementById("services-container");

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
                            <h3>${service.service_name}</h3>
                            <span class="service-price">${formatCurrency(
                              service.price
                            )}</span>
                        </div>
                        <div class="service-body">
                            <p>${service.description || "Không có mô tả"}</p>
                            <div class="service-meta">
                                <span class="service-category ${categoryClass}">${categoryText}</span>
                                <span class="service-status ${statusClass}">
                                    ${
                                      service.status === "available"
                                        ? "Có sẵn"
                                        : "Không khả dụng"
                                    }
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
    const response = await fetch(`${API_BASE_URL}/staff.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      renderStaffTable(data.data);
    }
  } catch (error) {
    console.error("Error loading staff:", error);
    showToast("Lỗi tải danh sách nhân viên", "error");
  }
}

function renderStaffTable(staffList) {
  const tbody = document.getElementById("staff-table-body");

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
                        <td><strong>${staff.staff_code}</strong></td>
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
      `${API_BASE_URL}/reports.php?action=getReport&period=${period}`
    );
    const data = await response.json();

    if (data.success) {
      updateReportCards(data.data);
      renderReportCharts(data.data);
    }
  } catch (error) {
    console.error("Error loading report:", error);
    showToast("Lỗi tải báo cáo", "error");
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
  document.getElementById("revenue-change").textContent = `${
    reportData.revenue_change >= 0 ? "+" : ""
  }${reportData.revenue_change || 0}% so với kỳ trước`;
  document.getElementById("bookings-change").textContent = `${
    reportData.bookings_change >= 0 ? "+" : ""
  }${reportData.bookings_change || 0}% so với kỳ trước`;
  document.getElementById("customers-change").textContent = `${
    reportData.customers_change >= 0 ? "+" : ""
  }${reportData.customers_change || 0}% so với kỳ trước`;
  document.getElementById("occupancy-change").textContent = `${
    reportData.occupancy_change >= 0 ? "+" : ""
  }${reportData.occupancy_change || 0}% so với kỳ trước`;
}

function renderReportCharts(reportData) {
  // Revenue by room type chart
  const revenueByTypeCtx = document
    .getElementById("revenueByRoomTypeChart")
    .getContext("2d");
  if (window.revenueByTypeChart) window.revenueByTypeChart.destroy();

  window.revenueByTypeChart = new Chart(revenueByTypeCtx, {
    type: "doughnut",
    data: {
      labels: reportData.revenue_by_type?.map((item) => item.type_name) || [],
      datasets: [
        {
          data: reportData.revenue_by_type?.map((item) => item.revenue) || [],
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

  // Payment method chart
  const paymentMethodCtx = document
    .getElementById("paymentMethodChart")
    .getContext("2d");
  if (window.paymentMethodChart) window.paymentMethodChart.destroy();

  window.paymentMethodChart = new Chart(paymentMethodCtx, {
    type: "pie",
    data: {
      labels: reportData.payment_methods?.map((item) => item.method) || [],
      datasets: [
        {
          data: reportData.payment_methods?.map((item) => item.count) || [],
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

  // Yearly revenue chart
  const yearlyRevenueCtx = document
    .getElementById("yearlyRevenueChart")
    .getContext("2d");
  if (window.yearlyRevenueChart) window.yearlyRevenueChart.destroy();

  window.yearlyRevenueChart = new Chart(yearlyRevenueCtx, {
    type: "bar",
    data: {
      labels: reportData.yearly_revenue?.map((item) => item.month) || [],
      datasets: [
        {
          label: "Doanh thu",
          data: reportData.yearly_revenue?.map((item) => item.revenue) || [],
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
      `${API_BASE_URL}/room-types.php?action=getAll`
    );
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("room-type-id");
      let html = '<option value="">Chọn loại phòng</option>';

      data.data.forEach((type) => {
        html += `<option value="${type.id}">${type.type_name} (${formatCurrency(
          type.base_price
        )}/đêm)</option>`;
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
      `${API_BASE_URL}/rooms.php?action=get&id=${roomId}`
    );
    const data = await response.json();

    if (data.success) {
      const room = data.data;
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
    const url = `${API_BASE_URL}/rooms.php?action=${
      isEdit ? "update" : "create"
    }`;
    const method = "POST";

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
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

  fetch(`${API_BASE_URL}/rooms.php?action=delete&id=${roomId}`, {
    method: "DELETE",
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

function openBookingModal(bookingId = null) {
  const modal = document.getElementById("booking-modal");
  const title = document.getElementById("booking-modal-title");
  const form = document.getElementById("booking-form");

  if (bookingId) {
    title.textContent = "Chỉnh sửa đặt phòng";
    loadBookingData(bookingId);
  } else {
    title.textContent = "Thêm đặt phòng mới";
    form.reset();
    document.getElementById("booking-id").value = "";
    loadCustomersForSelect();
    loadAvailableRooms();
    loadServicesForSelect();
    setupDateCalculations();
  }

  modal.classList.add("active");
}

async function loadCustomersForSelect() {
  try {
    const response = await fetch(`${API_BASE_URL}/customers.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("booking-customer-id");
      let html = '<option value="">Chọn khách hàng</option>';

      data.data.forEach((customer) => {
        html += `<option value="${customer.id}">${customer.full_name} (${customer.email})</option>`;
      });

      select.innerHTML = html;
    }
  } catch (error) {
    console.error("Error loading customers for select:", error);
  }
}

async function loadAvailableRooms() {
  try {
    const response = await fetch(
      `${API_BASE_URL}/rooms.php?action=getAvailable`
    );
    const data = await response.json();

    if (data.success) {
      const select = document.getElementById("booking-room-id");
      let html = '<option value="">Chọn phòng</option>';

      data.data.forEach((room) => {
        html += `<option value="${room.id}" data-price="${room.base_price}">
                            Phòng ${room.room_number} - ${
          room.type_name
        } (${formatCurrency(room.base_price)}/đêm)
                        </option>`;
      });

      select.innerHTML = html;

      // Add event listener for room selection
      select.addEventListener("change", calculateTotalPrice);
    }
  } catch (error) {
    console.error("Error loading available rooms:", error);
  }
}

async function loadServicesForSelect() {
  try {
    const response = await fetch(`${API_BASE_URL}/services.php?action=getAll`);
    const data = await response.json();

    if (data.success) {
      const container = document.getElementById("services-selection");
      let html = "";

      data.data.forEach((service) => {
        if (service.status === "available") {
          html += `
                                <div class="service-checkbox">
                                    <input type="checkbox" id="service-${
                                      service.id
                                    }" value="${service.id}" data-price="${
            service.price
          }">
                                    <label for="service-${service.id}">
                                        ${
                                          service.service_name
                                        } - ${formatCurrency(service.price)}
                                        <small>${service.description}</small>
                                    </label>
                                </div>
                            `;
        }
      });

      container.innerHTML = html || "<p>Không có dịch vụ nào khả dụng</p>";

      // Add event listeners for service selection
      document
        .querySelectorAll('#services-selection input[type="checkbox"]')
        .forEach((checkbox) => {
          checkbox.addEventListener("change", calculateTotalPrice);
        });
    }
  } catch (error) {
    console.error("Error loading services for select:", error);
  }
}

function setupDateCalculations() {
  const checkInInput = document.getElementById("check-in");
  const checkOutInput = document.getElementById("check-out");
  const numNightsInput = document.getElementById("num-nights");

  // Set minimum date to today
  const today = new Date().toISOString().split("T")[0];
  checkInInput.min = today;

  checkInInput.addEventListener("change", function () {
    checkOutInput.min = this.value;
    calculateNights();
    calculateTotalPrice();
  });

  checkOutInput.addEventListener("change", function () {
    calculateNights();
    calculateTotalPrice();
  });
}

function calculateNights() {
  const checkIn = document.getElementById("check-in").value;
  const checkOut = document.getElementById("check-out").value;

  if (checkIn && checkOut) {
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const nights = Math.round((end - start) / (1000 * 60 * 60 * 24));
    document.getElementById("num-nights").value = nights > 0 ? nights : 0;
  }
}

function calculateTotalPrice() {
  const roomSelect = document.getElementById("booking-room-id");
  const selectedOption = roomSelect.options[roomSelect.selectedIndex];
  const roomPrice = selectedOption
    ? parseFloat(selectedOption.getAttribute("data-price"))
    : 0;

  const numNights = parseInt(document.getElementById("num-nights").value) || 0;
  const numGuests = parseInt(document.getElementById("num-guests").value) || 2;

  // Calculate room total
  let total = roomPrice * numNights;

  // Add service costs
  const selectedServices = document.querySelectorAll(
    '#services-selection input[type="checkbox"]:checked'
  );
  selectedServices.forEach((checkbox) => {
    const price = parseFloat(checkbox.getAttribute("data-price")) || 0;
    total += price;
  });

  // Update total display
  document.getElementById("total-price").textContent = formatCurrency(total);
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
  toast.textContent = message;
  toast.className = `toast ${type}`;
  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

function closeAllModals() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.classList.remove("active");
  });
}

// Form submission handlers
document.getElementById("room-form")?.addEventListener("submit", function (e) {
  e.preventDefault();
  saveRoom();
});

document
  .getElementById("booking-form")
  ?.addEventListener("submit", function (e) {
    e.preventDefault();
    saveBooking();
  });

async function saveBooking() {
  const bookingId = document.getElementById("booking-id").value;
  const isEdit = !!bookingId;

  const bookingData = {
    customer_id: document.getElementById("booking-customer-id").value,
    room_id: document.getElementById("booking-room-id").value,
    check_in: document.getElementById("check-in").value,
    check_out: document.getElementById("check-out").value,
    num_guests: document.getElementById("num-guests").value,
    special_requests: document.getElementById("special-requests").value,
    payment_method: document.getElementById("payment-method").value,
    payment_status: document.getElementById("payment-status").value,
    status: document.getElementById("booking-status").value,
    promo_code: document.getElementById("booking-promo").value || null,
    total_price: parseFloat(
      document.getElementById("total-price").textContent.replace(/[^0-9]/g, "")
    ),
  };

  // Get selected services
  const selectedServices = [];
  document
    .querySelectorAll('#services-selection input[type="checkbox"]:checked')
    .forEach((checkbox) => {
      selectedServices.push({
        service_id: checkbox.value,
        price: parseFloat(checkbox.getAttribute("data-price")),
      });
    });

  try {
    const url = `${API_BASE_URL}/bookings.php?action=${
      isEdit ? "update" : "create"
    }`;
    const method = "POST";

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        ...bookingData,
        id: isEdit ? bookingId : undefined,
        services: selectedServices,
      }),
    });

    const result = await response.json();

    if (result.success) {
      showToast(
        isEdit ? "Cập nhật đặt phòng thành công" : "Thêm đặt phòng thành công",
        "success"
      );
      closeAllModals();
      loadBookings();
      loadDashboardData();
    } else {
      showToast(result.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving booking:", error);
    showToast("Lỗi lưu đặt phòng", "error");
  }
}

function exportBookings() {
  const startDate = document.getElementById("booking-date-from").value;
  const endDate = document.getElementById("booking-date-to").value;
  const status = document.getElementById("booking-status-filter").value;

  let url = `${API_BASE_URL}/export.php?type=bookings`;
  if (startDate) url += `&start_date=${startDate}`;
  if (endDate) url += `&end_date=${endDate}`;
  if (status) url += `&status=${status}`;

  window.open(url, "_blank");
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
  openBookingModal(id);
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
