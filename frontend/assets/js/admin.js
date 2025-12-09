// Opulent Admin - JavaScript File

// Global Variables
let currentAdmin = null;
let currentSection = "dashboard";
let toursData = [];
let bookingsData = [];
let customersData = [];
let hotelBookingsData = [];
let revenueChart = null;
let popularToursChart = null;

// API Configuration
const API_BASE_URL = "http://localhost:3000/api"; // Change this to your actual API URL
const IS_MOCK_API = true; // Set to false when connecting to real API

// Mock Data for development
const mockData = {
  tours: [
    {
      id: 1,
      name: "Tour Đà Lạt 4N3Đ: Thành Phố Ngàn Hoa",
      destination: "Đà Lạt",
      price_adult: 5990000,
      discount_percent: 15,
      available_slots: 12,
      featured_image:
        "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4",
      status: "active",
      created_at: "2024-01-15T08:00:00Z",
    },
    {
      id: 2,
      name: "Tour Phú Quốc 5N4Đ: Thiên Đường Biển Đảo",
      destination: "Phú Quốc",
      price_adult: 8990000,
      discount_percent: 20,
      available_slots: 8,
      featured_image:
        "https://images.unsplash.com/photo-1552465011-b4e30bf7349d",
      status: "active",
      created_at: "2024-02-10T08:00:00Z",
    },
    {
      id: 3,
      name: "Tour Bali 7N6Đ: Hòn Đảo Thần Thoại",
      destination: "Bali, Indonesia",
      price_adult: 25990000,
      discount_percent: 10,
      available_slots: 6,
      featured_image:
        "https://images.unsplash.com/photo-1537953773345-d172ccf13cf1",
      status: "active",
      created_at: "2024-01-20T08:00:00Z",
    },
    {
      id: 4,
      name: "Tour Hạ Long - Sapa 6N5Đ",
      destination: "Hạ Long - Sapa",
      price_adult: 12490000,
      discount_percent: 12,
      available_slots: 10,
      featured_image:
        "https://images.unsplash.com/photo-1528127269322-539801943592",
      status: "sold_out",
      created_at: "2024-02-15T08:00:00Z",
    },
    {
      id: 5,
      name: "Tour Nhật Bản 8N7Đ: Mùa Hoa Anh Đào",
      destination: "Tokyo, Kyoto, Osaka",
      price_adult: 32990000,
      discount_percent: 5,
      available_slots: 4,
      featured_image:
        "https://images.unsplash.com/photo-1528164344705-47542687000d",
      status: "active",
      created_at: "2024-03-01T08:00:00Z",
    },
  ],
  bookings: [
    {
      id: 1,
      booking_code: "OPL-20240315-001",
      tour: { id: 1, name: "Tour Đà Lạt 4N3Đ" },
      customer: {
        name: "Nguyễn Văn A",
        email: "a@example.com",
        phone: "0901234567",
      },
      departure_date: "2024-04-15",
      num_adults: 2,
      num_children: 1,
      total_price: 14975000,
      payment_status: "paid",
      booking_status: "confirmed",
      created_at: "2024-03-15T10:30:00Z",
    },
    {
      id: 2,
      booking_code: "OPL-20240316-002",
      tour: { id: 2, name: "Tour Phú Quốc 5N4Đ" },
      customer: {
        name: "Trần Thị B",
        email: "b@example.com",
        phone: "0912345678",
      },
      departure_date: "2024-05-20",
      num_adults: 4,
      num_children: 0,
      total_price: 28768000,
      payment_status: "pending",
      booking_status: "pending",
      created_at: "2024-03-16T14:20:00Z",
    },
    {
      id: 3,
      booking_code: "OPL-20240317-003",
      tour: { id: 3, name: "Tour Bali 7N6Đ" },
      customer: {
        name: "Lê Văn C",
        email: "c@example.com",
        phone: "0923456789",
      },
      departure_date: "2024-06-10",
      num_adults: 2,
      num_children: 2,
      total_price: 54579000,
      payment_status: "paid",
      booking_status: "confirmed",
      created_at: "2024-03-17T09:15:00Z",
    },
  ],
  customers: [
    {
      id: 1,
      full_name: "Nguyễn Văn A",
      email: "a@example.com",
      phone: "0901234567",
      address: "123 Nguyễn Văn Linh, Q.7, TP.HCM",
      bookings_count: 3,
      total_spent: 45800000,
      created_at: "2024-01-10T08:00:00Z",
    },
    {
      id: 2,
      full_name: "Trần Thị B",
      email: "b@example.com",
      phone: "0912345678",
      address: "456 Lê Lợi, Q.1, TP.HCM",
      bookings_count: 2,
      total_spent: 28768000,
      created_at: "2024-02-15T10:30:00Z",
    },
    {
      id: 3,
      full_name: "Lê Văn C",
      email: "c@example.com",
      phone: "0923456789",
      address: "789 Nguyễn Huệ, Q.1, TP.HCM",
      bookings_count: 1,
      total_spent: 54579000,
      created_at: "2024-03-01T14:20:00Z",
    },
  ],
  hotel_bookings: [
    {
      id: 1,
      booking_code: "HOTEL-20240318-001",
      hotel_name: "Khách sạn Đà Lạt Plaza",
      customer_name: "Nguyễn Văn A",
      customer_phone: "0901234567",
      customer_email: "a@example.com",
      room_type: "deluxe",
      room_price: 1500000,
      checkin_date: "2024-04-15",
      checkout_date: "2024-04-18",
      number_of_nights: 3,
      number_of_rooms: 1,
      number_of_guests: 2,
      total_amount: 4500000,
      special_requests: "Phòng view thành phố",
      payment_status: "paid",
      status: "confirmed",
      created_at: "2024-03-18T10:30:00Z",
    },
    {
      id: 2,
      booking_code: "HOTEL-20240318-002",
      hotel_name: "Vinpearl Phú Quốc",
      customer_name: "Trần Thị B",
      customer_phone: "0912345678",
      customer_email: "b@example.com",
      room_type: "suite",
      room_price: 2500000,
      checkin_date: "2024-05-20",
      checkout_date: "2024-05-25",
      number_of_nights: 5,
      number_of_rooms: 1,
      number_of_guests: 3,
      total_amount: 12500000,
      special_requests: "Giường phụ cho trẻ em",
      payment_status: "pending",
      status: "pending",
      created_at: "2024-03-18T14:20:00Z",
    },
    {
      id: 3,
      booking_code: "HOTEL-20240319-003",
      hotel_name: "InterContinental Đà Nẵng",
      customer_name: "Lê Văn C",
      customer_phone: "0923456789",
      customer_email: "c@example.com",
      room_type: "villa",
      room_price: 5000000,
      checkin_date: "2024-06-10",
      checkout_date: "2024-06-15",
      number_of_nights: 5,
      number_of_rooms: 2,
      number_of_guests: 6,
      total_amount: 50000000,
      special_requests: "Hồ bơi riêng, bữa sáng buffet",
      payment_status: "paid",
      status: "confirmed",
      created_at: "2024-03-19T09:15:00Z",
    },
    {
      id: 4,
      booking_code: "HOTEL-20240320-004",
      hotel_name: "Majestic Saigon Hotel",
      customer_name: "Phạm Thị D",
      customer_phone: "0934567890",
      customer_email: "d@example.com",
      room_type: "standard",
      room_price: 1200000,
      checkin_date: "2024-04-01",
      checkout_date: "2024-04-03",
      number_of_nights: 2,
      number_of_rooms: 2,
      number_of_guests: 4,
      total_amount: 4800000,
      special_requests: "",
      payment_status: "paid",
      status: "checked_in",
      created_at: "2024-03-20T11:45:00Z",
    },
    {
      id: 5,
      booking_code: "HOTEL-20240321-005",
      hotel_name: "Fusion Suites Vung Tau",
      customer_name: "Hoàng Văn E",
      customer_phone: "0945678901",
      customer_email: "e@example.com",
      room_type: "family",
      room_price: 1800000,
      checkin_date: "2024-05-01",
      checkout_date: "2024-05-04",
      number_of_nights: 3,
      number_of_rooms: 1,
      number_of_guests: 4,
      total_amount: 5400000,
      special_requests: "Phòng gia đình",
      payment_status: "partial",
      status: "confirmed",
      created_at: "2024-03-21T16:30:00Z",
    },
  ],
  dashboard: {
    total_tours: 25,
    today_bookings: 8,
    total_customers: 156,
    monthly_revenue: 125450000,
    charts: {
      months: [
        "Tháng 1",
        "Tháng 2",
        "Tháng 3",
        "Tháng 4",
        "Tháng 5",
        "Tháng 6",
      ],
      revenue: [12000000, 18500000, 15000000, 25000000, 22000000, 30000000],
      popular_tours_names: [
        "Tour Đà Lạt",
        "Tour Phú Quốc",
        "Tour Bali",
        "Tour Hạ Long",
        "Tour Nhật Bản",
      ],
      popular_tours_counts: [65, 59, 80, 81, 56],
    },
  },
};

// Initialize Admin Panel
document.addEventListener("DOMContentLoaded", function () {
  console.log("Opulent Admin Panel Initializing...");

  // Check authentication
  checkAuthentication();

  // Initialize components
  initAdmin();

  // Setup event listeners
  setupAdminEventListeners();

  // Load initial data
  loadInitialData();

  // Update date and time
  updateDateTime();
  setInterval(updateDateTime, 1000);
});

// Check if user is authenticated
function checkAuthentication() {
  // In a real application, you would check for a valid session/token
  const adminToken = localStorage.getItem("admin_token");

  if (!adminToken && !IS_MOCK_API) {
    // Redirect to login page
    window.location.href = "login.html";
    return;
  }

  // Load admin data
  const savedAdmin = localStorage.getItem("admin_data");
  if (savedAdmin) {
    currentAdmin = JSON.parse(savedAdmin);
    updateAdminUI();
  }
}

// Initialize admin components
function initAdmin() {
  // Set current date in date filters
  const today = new Date().toISOString().split("T")[0];
  document.getElementById("booking-date-filter").value = today;

  // Set default dates for hotel booking modal
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  document.getElementById("checkin-date").value = today;
  document.getElementById("checkout-date").value = tomorrow
    .toISOString()
    .split("T")[0];

  // Initialize tooltips
  initTooltips();

  // Initialize charts
  initCharts();
}

// Setup event listeners
function setupAdminEventListeners() {
  // Navigation
  const menuItems = document.querySelectorAll(".sidebar-menu li");
  menuItems.forEach((item) => {
    item.addEventListener("click", handleNavigation);
  });

  // Logout button
  document.querySelector(".logout-btn").addEventListener("click", handleLogout);

  // Notification bell
  document
    .querySelector(".notification")
    .addEventListener("click", toggleNotifications);

  // Tour management
  document
    .getElementById("add-tour-btn")
    .addEventListener("click", openTourModal);
  document
    .getElementById("apply-filters")
    .addEventListener("click", handleTourFilter);
  document
    .getElementById("tour-search")
    .addEventListener("input", debounce(handleTourSearch, 300));

  // Booking management
  document
    .getElementById("apply-booking-filters")
    .addEventListener("click", handleBookingFilter);
  document
    .getElementById("export-bookings")
    .addEventListener("click", exportBookings);

  // Hotel Booking management
  document
    .getElementById("add-hotel-booking-btn")
    .addEventListener("click", openHotelBookingModal);
  document
    .getElementById("apply-hotel-filters")
    .addEventListener("click", handleHotelBookingFilter);
  document
    .getElementById("reset-hotel-filters")
    .addEventListener("click", resetHotelBookingFilters);
  document
    .getElementById("export-hotel-bookings")
    .addEventListener("click", exportHotelBookings);

  // Modal close buttons
  document.querySelectorAll(".close-modal").forEach((btn) => {
    btn.addEventListener("click", closeAllModals);
  });

  // Tour form submission
  document
    .getElementById("tour-form")
    .addEventListener("submit", handleTourFormSubmit);

  // Hotel Booking form submission
  document
    .getElementById("hotel-booking-form")
    .addEventListener("submit", handleHotelBookingFormSubmit);

  // Auto generate slug for tour
  document.getElementById("tour-name").addEventListener("input", generateSlug);

  // Calculate booking details when dates/price change
  document
    .getElementById("checkin-date")
    .addEventListener("change", calculateBookingDetails);
  document
    .getElementById("checkout-date")
    .addEventListener("change", calculateBookingDetails);
  document
    .getElementById("room-price")
    .addEventListener("input", calculateBookingDetails);
  document
    .getElementById("number-of-rooms")
    .addEventListener("input", calculateBookingDetails);

  // Close modal on outside click
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeAllModals();
      }
    });
  });

  // Close modal on Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeAllModals();
    }
  });
}

// Load initial data
function loadInitialData() {
  showLoading();

  // Load all data in parallel
  Promise.all([
    loadDashboardData(),
    loadTours(),
    loadBookings(),
    loadCustomers(),
    loadHotelBookings(),
  ])
    .then(() => {
      hideLoading();
      showSection("dashboard");
    })
    .catch((error) => {
      console.error("Error loading data:", error);
      showToast("Lỗi tải dữ liệu. Vui lòng thử lại!", "error");
      hideLoading();
    });
}

// Navigation handler
function handleNavigation(e) {
  e.preventDefault();

  const menuItem = e.currentTarget;
  const section = menuItem.getAttribute("data-section");

  // Update active menu item
  document.querySelectorAll(".sidebar-menu li").forEach((item) => {
    item.classList.remove("active");
  });
  menuItem.classList.add("active");

  // Show selected section
  showSection(section);
}

// Show/hide sections
function showSection(section) {
  currentSection = section;

  // Hide all sections
  document.querySelectorAll(".content-section").forEach((sec) => {
    sec.classList.remove("active");
  });

  // Show selected section
  document.getElementById(`${section}-section`).classList.add("active");

  // Update page title
  const titles = {
    dashboard: "Dashboard",
    tours: "Quản lý Tour",
    bookings: "Quản lý Đặt Tour",
    customers: "Quản lý Khách hàng",
    hotels: "Quản lý Đặt Phòng",
    categories: "Danh mục Tour",
    reports: "Báo cáo & Thống kê",
  };

  document.getElementById("page-title").textContent =
    titles[section] || section;
  document.getElementById("page-subtitle").textContent =
    getSectionSubtitle(section);

  // Load section-specific data
  switch (section) {
    case "dashboard":
      loadDashboardData();
      break;
    case "tours":
      loadTours();
      break;
    case "bookings":
      loadBookings();
      break;
    case "customers":
      loadCustomers();
      break;
    case "hotels":
      loadHotelBookings();
      break;
    case "categories":
      loadCategories();
      break;
    case "reports":
      loadReports();
      break;
  }
}

function getSectionSubtitle(section) {
  const subtitles = {
    dashboard: "Tổng quan hệ thống",
    tours: "Quản lý danh sách tour du lịch",
    bookings: "Quản lý đơn đặt tour của khách hàng",
    customers: "Quản lý thông tin khách hàng",
    hotels: "Quản lý đặt phòng khách sạn",
    categories: "Quản lý danh mục tour",
    reports: "Báo cáo doanh thu và thống kê",
  };
  return subtitles[section] || "";
}

// ===== DASHBOARD FUNCTIONS =====
async function loadDashboardData() {
  try {
    let data;

    if (IS_MOCK_API) {
      // Use mock data
      data = mockData.dashboard;
    } else {
      // Fetch from real API
      const response = await fetch(`${API_BASE_URL}/dashboard`);
      const result = await response.json();
      data = result.data;
    }

    // Update stats
    updateDashboardStats(data);

    // Update charts
    updateCharts(data.charts);

    // Load recent activities
    loadRecentActivities();
  } catch (error) {
    console.error("Error loading dashboard data:", error);
    showToast("Lỗi tải dữ liệu dashboard", "error");
  }
}

function updateDashboardStats(data) {
  document.getElementById("total-tours").textContent = formatNumber(
    data.total_tours
  );
  document.getElementById("total-bookings").textContent = formatNumber(
    data.today_bookings
  );
  document.getElementById("total-customers").textContent = formatNumber(
    data.total_customers
  );
  document.getElementById("total-revenue").textContent = formatCurrency(
    data.monthly_revenue
  );
}

function initCharts() {
  const revenueCtx = document.getElementById("revenueChart").getContext("2d");
  const popularToursCtx = document
    .getElementById("popularToursChart")
    .getContext("2d");

  revenueChart = new Chart(revenueCtx, {
    type: "line",
    data: {
      labels: [],
      datasets: [
        {
          label: "Doanh thu (VNĐ)",
          data: [],
          borderColor: "#3498db",
          backgroundColor: "rgba(52, 152, 219, 0.1)",
          borderWidth: 2,
          fill: true,
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: "top",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return formatCurrency(context.raw);
            },
          },
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

  popularToursChart = new Chart(popularToursCtx, {
    type: "bar",
    data: {
      labels: [],
      datasets: [
        {
          label: "Số lượt đặt",
          data: [],
          backgroundColor: [
            "rgba(52, 152, 219, 0.7)",
            "rgba(46, 204, 113, 0.7)",
            "rgba(155, 89, 182, 0.7)",
            "rgba(241, 196, 15, 0.7)",
            "rgba(230, 126, 34, 0.7)",
          ],
          borderColor: [
            "rgb(52, 152, 219)",
            "rgb(46, 204, 113)",
            "rgb(155, 89, 182)",
            "rgb(241, 196, 15)",
            "rgb(230, 126, 34)",
          ],
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 10,
          },
        },
      },
    },
  });
}

function updateCharts(chartData) {
  if (!chartData) return;

  // Update revenue chart
  if (revenueChart && chartData.months && chartData.revenue) {
    revenueChart.data.labels = chartData.months;
    revenueChart.data.datasets[0].data = chartData.revenue;
    revenueChart.update();
  }

  // Update popular tours chart
  if (
    popularToursChart &&
    chartData.popular_tours_names &&
    chartData.popular_tours_counts
  ) {
    popularToursChart.data.labels = chartData.popular_tours_names;
    popularToursChart.data.datasets[0].data = chartData.popular_tours_counts;
    popularToursChart.update();
  }
}

async function loadRecentActivities() {
  try {
    let activities;

    if (IS_MOCK_API) {
      // Mock activities (including hotel bookings)
      activities = [
        {
          type: "booking",
          message: "Đặt tour Đà Lạt mới",
          time: "5 phút trước",
        },
        {
          type: "hotel",
          message: "Đặt phòng Vinpearl Phú Quốc thành công",
          time: "15 phút trước",
        },
        {
          type: "tour",
          message: "Tour Phú Quốc đã được cập nhật",
          time: "1 giờ trước",
        },
        {
          type: "customer",
          message: "Khách hàng mới đăng ký",
          time: "2 giờ trước",
        },
        {
          type: "booking",
          message: "Đơn đặt tour #OPL-20240318-005 đã thanh toán",
          time: "3 giờ trước",
        },
        {
          type: "hotel",
          message: "Khách đã nhận phòng tại Majestic Saigon Hotel",
          time: "4 giờ trước",
        },
      ];
    } else {
      // Fetch from API
      const response = await fetch(`${API_BASE_URL}/activities/recent`);
      const result = await response.json();
      activities = result.data;
    }

    renderActivities(activities);
  } catch (error) {
    console.error("Error loading activities:", error);
  }
}

function renderActivities(activities) {
  const container = document.getElementById("recent-activities");

  if (!activities || activities.length === 0) {
    container.innerHTML =
      '<div class="no-data">Không có hoạt động nào gần đây</div>';
    return;
  }

  let html = "";

  activities.forEach((activity) => {
    const iconClass = getActivityIcon(activity.type);
    const iconColor = getActivityColor(activity.type);

    html += `
            <div class="activity-item">
                <div class="activity-icon" style="background-color: ${iconColor};">
                    <i class="${iconClass}"></i>
                </div>
                <div class="activity-info">
                    <p>${activity.message}</p>
                    <span class="activity-time">${activity.time}</span>
                </div>
            </div>
        `;
  });

  container.innerHTML = html;
}

function getActivityIcon(type) {
  const icons = {
    booking: "fas fa-calendar-plus",
    tour: "fas fa-map-marked-alt",
    customer: "fas fa-user-plus",
    payment: "fas fa-credit-card",
    system: "fas fa-cog",
    hotel: "fas fa-hotel",
  };
  return icons[type] || "fas fa-info-circle";
}

function getActivityColor(type) {
  const colors = {
    booking: "#3498db",
    tour: "#2ecc71",
    customer: "#9b59b6",
    payment: "#f39c12",
    system: "#95a5a6",
    hotel: "#e74c3c",
  };
  return colors[type] || "#7f8c8d";
}

// ===== TOUR MANAGEMENT =====
async function loadTours(filters = {}) {
  try {
    showLoading();

    let tours;

    if (IS_MOCK_API) {
      // Use mock data
      tours = mockData.tours;

      // Apply filters
      if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        tours = tours.filter(
          (tour) =>
            tour.name.toLowerCase().includes(searchTerm) ||
            tour.destination.toLowerCase().includes(searchTerm)
        );
      }

      if (filters.status && filters.status !== "") {
        tours = tours.filter((tour) => tour.status === filters.status);
      }
    } else {
      // Fetch from API
      const queryParams = new URLSearchParams(filters).toString();
      const response = await fetch(`${API_BASE_URL}/tours?${queryParams}`);
      const result = await response.json();
      tours = result.data;
    }

    toursData = tours;
    renderToursTable(tours);
    hideLoading();
  } catch (error) {
    console.error("Error loading tours:", error);
    showToast("Lỗi tải danh sách tour", "error");
    hideLoading();
  }
}

function renderToursTable(tours) {
  const tbody = document.getElementById("tours-table-body");

  if (!tours || tours.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="10" class="no-data">
                    <i class="fas fa-box-open"></i>
                    <p>Không có tour nào</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";

  tours.forEach((tour) => {
    const finalPrice = tour.price_adult * (1 - tour.discount_percent / 100);
    const statusClass = `status-${tour.status}`;
    const statusText = getTourStatusText(tour.status);

    html += `
            <tr>
                <td>${tour.id}</td>
                <td>
                    <img src="${
                      tour.featured_image || "https://via.placeholder.com/50x50"
                    }" 
                         alt="${tour.name}" 
                         class="table-image"
                         onerror="this.src='https://via.placeholder.com/50x50'">
                </td>
                <td>
                    <strong>${tour.name}</strong>
                    <div class="text-muted" style="font-size: 0.85rem;">
                        ${tour.duration_days || 0} ngày ${
      tour.duration_nights || 0
    } đêm
                    </div>
                </td>
                <td>${tour.destination}</td>
                <td>
                    <div class="text-primary font-weight-bold">${formatCurrency(
                      finalPrice
                    )}</div>
                    ${
                      tour.discount_percent > 0
                        ? `<del class="text-muted" style="font-size: 0.85rem;">${formatCurrency(
                            tour.price_adult
                          )}</del>`
                        : ""
                    }
                </td>
                <td>
                    ${
                      tour.discount_percent > 0
                        ? `<span class="badge badge-warning">-${tour.discount_percent}%</span>`
                        : '<span class="text-muted">—</span>'
                    }
                </td>
                <td>
                    <span class="${
                      tour.available_slots > 5
                        ? "text-success"
                        : tour.available_slots > 0
                        ? "text-warning"
                        : "text-danger"
                    }">
                        ${tour.available_slots}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td>${formatDate(tour.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn edit-btn" onclick="editTour(${
                          tour.id
                        })" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn view-btn" onclick="viewTour(${
                          tour.id
                        })" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteTour(${
                          tour.id
                        })" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

function getTourStatusText(status) {
  const statusMap = {
    active: "Hoạt động",
    inactive: "Ngừng hoạt động",
    sold_out: "Hết chỗ",
    draft: "Bản nháp",
  };
  return statusMap[status] || status;
}

function handleTourFilter() {
  const search = document.getElementById("tour-search").value;
  const status = document.getElementById("tour-status-filter").value;

  loadTours({ search, status });
}

function handleTourSearch(e) {
  const search = e.target.value;
  loadTours({ search });
}

function openTourModal(tourId = null) {
  const modal = document.getElementById("tour-modal");
  const title = document.getElementById("modal-title");
  const form = document.getElementById("tour-form");

  if (tourId) {
    title.textContent = "Chỉnh sửa tour";
    loadTourData(tourId);
  } else {
    title.textContent = "Thêm tour mới";
    form.reset();
    document.getElementById("tour-id").value = "";
    document.getElementById("tour-status").value = "active";
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}

async function loadTourData(tourId) {
  try {
    showLoading();

    let tour;

    if (IS_MOCK_API) {
      // Find in mock data
      tour = mockData.tours.find((t) => t.id === tourId);
    } else {
      // Fetch from API
      const response = await fetch(`${API_BASE_URL}/tours/${tourId}`);
      const result = await response.json();
      tour = result.data;
    }

    if (tour) {
      document.getElementById("tour-id").value = tour.id;
      document.getElementById("tour-name").value = tour.name;
      document.getElementById("tour-slug").value = tour.slug || "";
      document.getElementById("tour-destination").value = tour.destination;
      document.getElementById("tour-departure").value =
        tour.departure_location || "";
      document.getElementById("tour-duration-days").value =
        tour.duration_days || 1;
      document.getElementById("tour-duration-nights").value =
        tour.duration_nights || 0;
      document.getElementById("tour-price-adult").value = tour.price_adult;
      document.getElementById("tour-price-child").value = tour.price_child || 0;
      document.getElementById("tour-price-infant").value =
        tour.price_infant || 0;
      document.getElementById("tour-discount").value =
        tour.discount_percent || 0;
      document.getElementById("tour-slots").value = tour.available_slots;
      document.getElementById("tour-featured-image").value =
        tour.featured_image || "";
      document.getElementById("tour-short-desc").value =
        tour.short_description || "";
      document.getElementById("tour-full-desc").value = tour.description || "";
      document.getElementById("tour-status").value = tour.status;
    }

    hideLoading();
  } catch (error) {
    console.error("Error loading tour data:", error);
    showToast("Lỗi tải dữ liệu tour", "error");
    hideLoading();
  }
}

function generateSlug() {
  const name = document.getElementById("tour-name").value;
  if (!name) return;

  const slug = name
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // Remove accents
    .replace(/[^a-z0-9\s-]/g, "") // Remove special characters
    .replace(/\s+/g, "-") // Replace spaces with hyphens
    .replace(/-+/g, "-") // Replace multiple hyphens with single
    .trim();

  document.getElementById("tour-slug").value = slug;
}

async function handleTourFormSubmit(e) {
  e.preventDefault();

  const tourId = document.getElementById("tour-id").value;
  const isEdit = !!tourId;

  const formData = {
    name: document.getElementById("tour-name").value,
    slug: document.getElementById("tour-slug").value,
    destination: document.getElementById("tour-destination").value,
    departure_location: document.getElementById("tour-departure").value,
    duration_days:
      parseInt(document.getElementById("tour-duration-days").value) || 1,
    duration_nights:
      parseInt(document.getElementById("tour-duration-nights").value) || 0,
    price_adult:
      parseFloat(document.getElementById("tour-price-adult").value) || 0,
    price_child:
      parseFloat(document.getElementById("tour-price-child").value) || 0,
    price_infant:
      parseFloat(document.getElementById("tour-price-infant").value) || 0,
    discount_percent:
      parseInt(document.getElementById("tour-discount").value) || 0,
    available_slots: parseInt(document.getElementById("tour-slots").value) || 0,
    featured_image: document.getElementById("tour-featured-image").value || "",
    short_description: document.getElementById("tour-short-desc").value,
    description: document.getElementById("tour-full-desc").value,
    status: document.getElementById("tour-status").value,
  };

  // Validate required fields
  if (
    !formData.name ||
    !formData.destination ||
    !formData.price_adult ||
    !formData.available_slots
  ) {
    showToast("Vui lòng điền đầy đủ các trường bắt buộc", "warning");
    return;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Mock API response
      await new Promise((resolve) => setTimeout(resolve, 1000));

      if (isEdit) {
        // Update existing tour in mock data
        const index = mockData.tours.findIndex(
          (t) => t.id === parseInt(tourId)
        );
        if (index !== -1) {
          mockData.tours[index] = { ...mockData.tours[index], ...formData };
        }
        showToast("Cập nhật tour thành công!", "success");
      } else {
        // Add new tour to mock data
        const newTour = {
          id: mockData.tours.length + 1,
          ...formData,
          created_at: new Date().toISOString(),
        };
        mockData.tours.unshift(newTour);
        showToast("Thêm tour mới thành công!", "success");
      }
    } else {
      // Real API call
      const url = `${API_BASE_URL}/tours${isEdit ? "/" + tourId : ""}`;
      const method = isEdit ? "PUT" : "POST";

      const response = await fetch(url, {
        method: method,
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (result.success) {
        showToast(
          isEdit ? "Cập nhật tour thành công!" : "Thêm tour mới thành công!",
          "success"
        );
      } else {
        throw new Error(result.message);
      }
    }

    // Close modal and refresh data
    closeAllModals();
    loadTours();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error saving tour:", error);
    showToast("Lỗi lưu tour: " + error.message, "error");
    hideLoading();
  }
}

function editTour(tourId) {
  openTourModal(tourId);
}

function viewTour(tourId) {
  // In a real application, this would redirect to tour detail page
  // For now, show a notification
  showToast("Chức năng xem chi tiết tour sẽ sớm có mặt!", "info");
}

async function deleteTour(tourId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa tour này? Hành động này không thể hoàn tác."
    )
  ) {
    return;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Remove from mock data
      await new Promise((resolve) => setTimeout(resolve, 500));
      mockData.tours = mockData.tours.filter((t) => t.id !== tourId);
      showToast("Xóa tour thành công!", "success");
    } else {
      // Real API call
      const response = await fetch(`${API_BASE_URL}/tours/${tourId}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
        },
      });

      const result = await response.json();

      if (result.success) {
        showToast("Xóa tour thành công!", "success");
      } else {
        throw new Error(result.message);
      }
    }

    // Refresh data
    loadTours();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error deleting tour:", error);
    showToast("Lỗi xóa tour: " + error.message, "error");
    hideLoading();
  }
}

// ===== BOOKING MANAGEMENT =====
async function loadBookings(filters = {}) {
  try {
    showLoading();

    let bookings;

    if (IS_MOCK_API) {
      // Use mock data
      bookings = mockData.bookings;

      // Apply filters
      if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        bookings = bookings.filter(
          (booking) =>
            booking.booking_code.toLowerCase().includes(searchTerm) ||
            booking.customer.name.toLowerCase().includes(searchTerm)
        );
      }

      if (filters.status && filters.status !== "") {
        bookings = bookings.filter(
          (booking) => booking.booking_status === filters.status
        );
      }

      if (filters.date) {
        bookings = bookings.filter((booking) =>
          booking.created_at.startsWith(filters.date)
        );
      }
    } else {
      // Fetch from API
      const queryParams = new URLSearchParams(filters).toString();
      const response = await fetch(`${API_BASE_URL}/bookings?${queryParams}`);
      const result = await response.json();
      bookings = result.data;
    }

    bookingsData = bookings;
    renderBookingsTable(bookings);
    hideLoading();
  } catch (error) {
    console.error("Error loading bookings:", error);
    showToast("Lỗi tải danh sách đặt tour", "error");
    hideLoading();
  }
}

function renderBookingsTable(bookings) {
  const tbody = document.getElementById("bookings-table-body");

  if (!bookings || bookings.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="10" class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>Không có đơn đặt nào</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";

  bookings.forEach((booking) => {
    const statusClass = `status-${booking.booking_status}`;
    const statusText = getBookingStatusText(booking.booking_status);
    const paymentStatusClass =
      booking.payment_status === "paid" ? "text-success" : "text-warning";
    const paymentStatusText =
      booking.payment_status === "paid" ? "Đã thanh toán" : "Chờ thanh toán";

    html += `
            <tr>
                <td>
                    <strong class="text-primary">${
                      booking.booking_code
                    }</strong>
                </td>
                <td>
                    <div class="font-weight-bold">${booking.tour.name}</div>
                    <div class="text-muted" style="font-size: 0.85rem;">
                        ID: ${booking.tour.id}
                    </div>
                </td>
                <td>
                    <div class="font-weight-bold">${booking.customer.name}</div>
                    <div class="text-muted" style="font-size: 0.85rem;">
                        ${booking.customer.phone}<br>
                        ${booking.customer.email}
                    </div>
                </td>
                <td>${formatDate(booking.departure_date)}</td>
                <td>
                    <div>Người lớn: <strong>${booking.num_adults}</strong></div>
                    <div>Trẻ em: <strong>${
                      booking.num_children || 0
                    }</strong></div>
                    <div>Em bé: <strong>${
                      booking.num_infants || 0
                    }</strong></div>
                </td>
                <td class="font-weight-bold text-primary">${formatCurrency(
                  booking.total_price
                )}</td>
                <td>
                    <span class="${paymentStatusClass}">
                        <i class="fas ${
                          booking.payment_status === "paid"
                            ? "fa-check-circle"
                            : "fa-clock"
                        }"></i>
                        ${paymentStatusText}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td>${formatDate(booking.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view-btn" onclick="viewBooking(${
                          booking.id
                        })" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit-btn" onclick="editBooking(${
                          booking.id
                        })" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteBooking(${
                          booking.id
                        })" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

function getBookingStatusText(status) {
  const statusMap = {
    pending: "Chờ xác nhận",
    confirmed: "Đã xác nhận",
    cancelled: "Đã hủy",
    completed: "Hoàn thành",
    refunded: "Đã hoàn tiền",
  };
  return statusMap[status] || status;
}

function handleBookingFilter() {
  const search = document.getElementById("booking-search").value;
  const status = document.getElementById("booking-status-filter").value;
  const date = document.getElementById("booking-date-filter").value;

  loadBookings({ search, status, date });
}

function viewBooking(bookingId) {
  // In a real application, this would open a booking detail modal
  // For now, show a notification
  showToast("Chức năng xem chi tiết đơn đặt sẽ sớm có mặt!", "info");
}

function editBooking(bookingId) {
  // In a real application, this would open a booking edit modal
  // For now, show a notification
  showToast("Chức năng chỉnh sửa đơn đặt sẽ sớm có mặt!", "info");
}

async function deleteBooking(bookingId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa đơn đặt này? Hành động này không thể hoàn tác."
    )
  ) {
    return;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Remove from mock data
      await new Promise((resolve) => setTimeout(resolve, 500));
      mockData.bookings = mockData.bookings.filter((b) => b.id !== bookingId);
      showToast("Xóa đơn đặt thành công!", "success");
    } else {
      // Real API call
      const response = await fetch(`${API_BASE_URL}/bookings/${bookingId}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
        },
      });

      const result = await response.json();

      if (result.success) {
        showToast("Xóa đơn đặt thành công!", "success");
      } else {
        throw new Error(result.message);
      }
    }

    // Refresh data
    loadBookings();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error deleting booking:", error);
    showToast("Lỗi xóa đơn đặt: " + error.message, "error");
    hideLoading();
  }
}

function exportBookings() {
  // In a real application, this would generate and download an Excel file
  // For now, show a notification
  showToast("Chức năng xuất Excel sẽ sớm có mặt!", "info");
}

// ===== CUSTOMER MANAGEMENT =====
async function loadCustomers() {
  try {
    showLoading();

    let customers;

    if (IS_MOCK_API) {
      // Use mock data
      customers = mockData.customers;
    } else {
      // Fetch from API
      const response = await fetch(`${API_BASE_URL}/customers`);
      const result = await response.json();
      customers = result.data;
    }

    customersData = customers;
    renderCustomersTable(customers);
    hideLoading();
  } catch (error) {
    console.error("Error loading customers:", error);
    showToast("Lỗi tải danh sách khách hàng", "error");
    hideLoading();
  }
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById("customers-table-body");

  if (!customers || customers.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="9" class="no-data">
                    <i class="fas fa-users-slash"></i>
                    <p>Không có khách hàng nào</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";

  customers.forEach((customer) => {
    html += `
            <tr>
                <td>${customer.id}</td>
                <td>
                    <div class="font-weight-bold">${customer.full_name}</div>
                </td>
                <td>
                    <div>${customer.email}</div>
                </td>
                <td>${customer.phone}</td>
                <td>
                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                        ${customer.address || "—"}
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge badge-info">${
                      customer.bookings_count || 0
                    }</span>
                </td>
                <td class="font-weight-bold text-primary">${formatCurrency(
                  customer.total_spent || 0
                )}</td>
                <td>${formatDate(customer.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view-btn" onclick="viewCustomer(${
                          customer.id
                        })" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit-btn" onclick="editCustomer(${
                          customer.id
                        })" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteCustomer(${
                          customer.id
                        })" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

function viewCustomer(customerId) {
  // In a real application, this would open a customer detail modal
  // For now, show a notification
  showToast("Chức năng xem chi tiết khách hàng sẽ sớm có mặt!", "info");
}

function editCustomer(customerId) {
  // In a real application, this would open a customer edit modal
  // For now, show a notification
  showToast("Chức năng chỉnh sửa khách hàng sẽ sớm có mặt!", "info");
}

async function deleteCustomer(customerId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa khách hàng này? Hành động này không thể hoàn tác."
    )
  ) {
    return;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Remove from mock data
      await new Promise((resolve) => setTimeout(resolve, 500));
      mockData.customers = mockData.customers.filter(
        (c) => c.id !== customerId
      );
      showToast("Xóa khách hàng thành công!", "success");
    } else {
      // Real API call
      const response = await fetch(`${API_BASE_URL}/customers/${customerId}`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
        },
      });

      const result = await response.json();

      if (result.success) {
        showToast("Xóa khách hàng thành công!", "success");
      } else {
        throw new Error(result.message);
      }
    }

    // Refresh data
    loadCustomers();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error deleting customer:", error);
    showToast("Lỗi xóa khách hàng: " + error.message, "error");
    hideLoading();
  }
}

// ===== HOTEL BOOKING MANAGEMENT =====
async function loadHotelBookings(filters = {}) {
  try {
    showLoading();

    let bookings;

    if (IS_MOCK_API) {
      // Use mock data
      bookings = mockData.hotel_bookings;

      // Apply filters
      if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        bookings = bookings.filter(
          (booking) =>
            booking.booking_code.toLowerCase().includes(searchTerm) ||
            booking.customer_name.toLowerCase().includes(searchTerm) ||
            booking.hotel_name.toLowerCase().includes(searchTerm)
        );
      }

      if (filters.status && filters.status !== "") {
        bookings = bookings.filter(
          (booking) => booking.status === filters.status
        );
      }

      if (filters.date) {
        bookings = bookings.filter((booking) =>
          booking.created_at.startsWith(filters.date)
        );
      }

      if (filters.checkin_date) {
        bookings = bookings.filter(
          (booking) => booking.checkin_date >= filters.checkin_date
        );
      }
    } else {
      // Fetch from API
      const queryParams = new URLSearchParams(filters).toString();
      const response = await fetch(
        `${API_BASE_URL}/hotel-bookings?${queryParams}`
      );
      const result = await response.json();
      bookings = result.data;
    }

    hotelBookingsData = bookings;
    renderHotelBookingsTable(bookings);
    hideLoading();
  } catch (error) {
    console.error("Error loading hotel bookings:", error);
    showToast("Lỗi tải danh sách đặt phòng", "error");
    hideLoading();
  }
}

function renderHotelBookingsTable(bookings) {
  const tbody = document.getElementById("hotel-bookings-table-body");

  if (!bookings || bookings.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="13" class="no-data">
                    <i class="fas fa-bed"></i>
                    <p>Không có đặt phòng nào</p>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";

  bookings.forEach((booking) => {
    const statusClass = `status-${booking.status}`;
    const statusText = getHotelBookingStatusText(booking.status);
    const paymentStatusClass = getPaymentStatusClass(booking.payment_status);
    const paymentStatusText = getPaymentStatusText(booking.payment_status);
    const roomTypeText = getRoomTypeText(booking.room_type);

    html += `
            <tr>
                <td>
                    <strong class="text-primary">${
                      booking.booking_code
                    }</strong>
                </td>
                <td>
                    <div class="font-weight-bold">${booking.hotel_name}</div>
                </td>
                <td>
                    <div class="font-weight-bold">${booking.customer_name}</div>
                    <div class="text-muted" style="font-size: 0.85rem;">
                        ${booking.customer_phone}<br>
                        ${booking.customer_email}
                    </div>
                </td>
                <td>
                    <span class="badge badge-info">${roomTypeText}</span>
                </td>
                <td>${formatDate(booking.checkin_date)}</td>
                <td>${formatDate(booking.checkout_date)}</td>
                <td class="text-center">${booking.number_of_nights}</td>
                <td class="text-center">${booking.number_of_rooms}</td>
                <td class="text-center">${booking.number_of_guests}</td>
                <td class="font-weight-bold text-primary">${formatCurrency(
                  booking.total_amount
                )}</td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span><br>
                    <small class="${paymentStatusClass}">${paymentStatusText}</small>
                </td>
                <td>${formatDate(booking.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view-btn" onclick="viewHotelBooking(${
                          booking.id
                        })" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn edit-btn" onclick="editHotelBooking(${
                          booking.id
                        })" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteHotelBooking(${
                          booking.id
                        })" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

function getHotelBookingStatusText(status) {
  const statusMap = {
    pending: "Chờ xác nhận",
    confirmed: "Đã xác nhận",
    checked_in: "Đã nhận phòng",
    checked_out: "Đã trả phòng",
    cancelled: "Đã hủy",
  };
  return statusMap[status] || status;
}

function getPaymentStatusClass(status) {
  const classMap = {
    pending: "text-danger",
    partial: "text-warning",
    paid: "text-success",
    refunded: "text-secondary",
  };
  return classMap[status] || "text-muted";
}

function getPaymentStatusText(status) {
  const textMap = {
    pending: "Chưa thanh toán",
    partial: "Thanh toán một phần",
    paid: "Đã thanh toán",
    refunded: "Đã hoàn tiền",
  };
  return textMap[status] || status;
}

function getRoomTypeText(type) {
  const typeMap = {
    standard: "Standard",
    deluxe: "Deluxe",
    suite: "Suite",
    family: "Gia đình",
    villa: "Villa",
  };
  return typeMap[type] || type;
}

function handleHotelBookingFilter() {
  const search = document.getElementById("hotel-booking-search").value;
  const status = document.getElementById("hotel-booking-status-filter").value;
  const checkin = document.getElementById("hotel-checkin-filter").value;

  loadHotelBookings({ search, status, checkin_date: checkin });
}

function resetHotelBookingFilters() {
  document.getElementById("hotel-booking-search").value = "";
  document.getElementById("hotel-booking-status-filter").value = "";
  document.getElementById("hotel-checkin-filter").value = "";

  loadHotelBookings();
}

function openHotelBookingModal(bookingId = null) {
  const modal = document.getElementById("hotel-booking-modal");
  const title = document.getElementById("hotel-modal-title");
  const form = document.getElementById("hotel-booking-form");

  if (bookingId) {
    title.textContent = "Chỉnh sửa đặt phòng";
    loadHotelBookingData(bookingId);
  } else {
    title.textContent = "Thêm đặt phòng mới";
    form.reset();
    document.getElementById("hotel-booking-id").value = "";
    document.getElementById("booking-status").value = "pending";
    document.getElementById("payment-status").value = "pending";
    document.getElementById("room-type").value = "standard";
    document.getElementById("number-of-rooms").value = 1;
    document.getElementById("number-of-guests").value = 2;

    // Set default dates
    const today = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById("checkin-date").value = today
      .toISOString()
      .split("T")[0];
    document.getElementById("checkout-date").value = tomorrow
      .toISOString()
      .split("T")[0];

    calculateBookingDetails();
  }

  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}

async function loadHotelBookingData(bookingId) {
  try {
    showLoading();

    let booking;

    if (IS_MOCK_API) {
      // Find in mock data
      booking = mockData.hotel_bookings.find((b) => b.id === bookingId);
    } else {
      // Fetch from API
      const response = await fetch(
        `${API_BASE_URL}/hotel-bookings/${bookingId}`
      );
      const result = await response.json();
      booking = result.data;
    }

    if (booking) {
      document.getElementById("hotel-booking-id").value = booking.id;
      document.getElementById("hotel-name").value = booking.hotel_name;
      document.getElementById("customer-name").value = booking.customer_name;
      document.getElementById("customer-phone").value = booking.customer_phone;
      document.getElementById("customer-email").value =
        booking.customer_email || "";
      document.getElementById("room-type").value = booking.room_type;
      document.getElementById("room-price").value = booking.room_price;
      document.getElementById("checkin-date").value =
        booking.checkin_date.split("T")[0];
      document.getElementById("checkout-date").value =
        booking.checkout_date.split("T")[0];
      document.getElementById("number-of-rooms").value =
        booking.number_of_rooms;
      document.getElementById("number-of-guests").value =
        booking.number_of_guests;
      document.getElementById("number-of-nights").value =
        booking.number_of_nights;
      document.getElementById("total-amount").value = formatCurrency(
        booking.total_amount
      );
      document.getElementById("special-requests").value =
        booking.special_requests || "";
      document.getElementById("payment-status").value = booking.payment_status;
      document.getElementById("booking-status").value = booking.status;
    }

    hideLoading();
  } catch (error) {
    console.error("Error loading hotel booking data:", error);
    showToast("Lỗi tải dữ liệu đặt phòng", "error");
    hideLoading();
  }
}

function calculateBookingDetails() {
  const checkin = document.getElementById("checkin-date").value;
  const checkout = document.getElementById("checkout-date").value;
  const roomPrice =
    parseFloat(document.getElementById("room-price").value) || 0;
  const numberOfRooms =
    parseInt(document.getElementById("number-of-rooms").value) || 1;

  if (checkin && checkout) {
    const checkinDate = new Date(checkin);
    const checkoutDate = new Date(checkout);

    // Calculate number of nights
    const diffTime = Math.abs(checkoutDate - checkinDate);
    const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    document.getElementById("number-of-nights").value = nights;

    // Calculate total amount
    if (roomPrice > 0) {
      const totalAmount = roomPrice * nights * numberOfRooms;
      document.getElementById("total-amount").value =
        formatCurrency(totalAmount);
    }
  }
}

async function handleHotelBookingFormSubmit(e) {
  e.preventDefault();

  const bookingId = document.getElementById("hotel-booking-id").value;
  const isEdit = !!bookingId;

  const formData = {
    hotel_name: document.getElementById("hotel-name").value,
    customer_name: document.getElementById("customer-name").value,
    customer_phone: document.getElementById("customer-phone").value,
    customer_email: document.getElementById("customer-email").value,
    room_type: document.getElementById("room-type").value,
    room_price: parseFloat(document.getElementById("room-price").value) || 0,
    checkin_date: document.getElementById("checkin-date").value,
    checkout_date: document.getElementById("checkout-date").value,
    number_of_rooms:
      parseInt(document.getElementById("number-of-rooms").value) || 1,
    number_of_guests:
      parseInt(document.getElementById("number-of-guests").value) || 1,
    number_of_nights:
      parseInt(document.getElementById("number-of-nights").value) || 1,
    total_amount:
      parseFloat(
        document.getElementById("total-amount").value.replace(/[^0-9]/g, "")
      ) || 0,
    special_requests: document.getElementById("special-requests").value,
    payment_status: document.getElementById("payment-status").value,
    status: document.getElementById("booking-status").value,
  };

  // Validate required fields
  if (
    !formData.hotel_name ||
    !formData.customer_name ||
    !formData.customer_phone ||
    !formData.checkin_date ||
    !formData.checkout_date ||
    !formData.room_price
  ) {
    showToast("Vui lòng điền đầy đủ các trường bắt buộc", "warning");
    return;
  }

  // Generate booking code if new booking
  if (!isEdit) {
    const now = new Date();
    const dateStr = now.toISOString().slice(0, 10).replace(/-/g, "");
    const randomNum = Math.floor(Math.random() * 1000)
      .toString()
      .padStart(3, "0");
    formData.booking_code = `HOTEL-${dateStr}-${randomNum}`;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Mock API response
      await new Promise((resolve) => setTimeout(resolve, 1000));

      if (isEdit) {
        // Update existing booking in mock data
        const index = mockData.hotel_bookings.findIndex(
          (b) => b.id === parseInt(bookingId)
        );
        if (index !== -1) {
          mockData.hotel_bookings[index] = {
            ...mockData.hotel_bookings[index],
            ...formData,
          };
        }
        showToast("Cập nhật đặt phòng thành công!", "success");
      } else {
        // Add new booking to mock data
        const newBooking = {
          id: mockData.hotel_bookings.length + 1,
          ...formData,
          created_at: new Date().toISOString(),
        };
        mockData.hotel_bookings.unshift(newBooking);
        showToast("Thêm đặt phòng mới thành công!", "success");
      }
    } else {
      // Real API call
      const url = `${API_BASE_URL}/hotel-bookings${
        isEdit ? "/" + bookingId : ""
      }`;
      const method = isEdit ? "PUT" : "POST";

      const response = await fetch(url, {
        method: method,
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();

      if (result.success) {
        showToast(
          isEdit
            ? "Cập nhật đặt phòng thành công!"
            : "Thêm đặt phòng mới thành công!",
          "success"
        );
      } else {
        throw new Error(result.message);
      }
    }

    // Close modal and refresh data
    closeAllModals();
    loadHotelBookings();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error saving hotel booking:", error);
    showToast("Lỗi lưu đặt phòng: " + error.message, "error");
    hideLoading();
  }
}

function viewHotelBooking(bookingId) {
  // In a real application, this would open a hotel booking detail modal
  // For now, show a notification
  showToast("Chức năng xem chi tiết đặt phòng sẽ sớm có mặt!", "info");
}

function editHotelBooking(bookingId) {
  openHotelBookingModal(bookingId);
}

async function deleteHotelBooking(bookingId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa đặt phòng này? Hành động này không thể hoàn tác."
    )
  ) {
    return;
  }

  try {
    showLoading();

    if (IS_MOCK_API) {
      // Remove from mock data
      await new Promise((resolve) => setTimeout(resolve, 500));
      mockData.hotel_bookings = mockData.hotel_bookings.filter(
        (b) => b.id !== bookingId
      );
      showToast("Xóa đặt phòng thành công!", "success");
    } else {
      // Real API call
      const response = await fetch(
        `${API_BASE_URL}/hotel-bookings/${bookingId}`,
        {
          method: "DELETE",
          headers: {
            Authorization: `Bearer ${localStorage.getItem("admin_token")}`,
          },
        }
      );

      const result = await response.json();

      if (result.success) {
        showToast("Xóa đặt phòng thành công!", "success");
      } else {
        throw new Error(result.message);
      }
    }

    // Refresh data
    loadHotelBookings();
    loadDashboardData();
    hideLoading();
  } catch (error) {
    console.error("Error deleting hotel booking:", error);
    showToast("Lỗi xóa đặt phòng: " + error.message, "error");
    hideLoading();
  }
}

function exportHotelBookings() {
  // In a real application, this would generate and download an Excel file
  // For now, show a notification
  showToast("Chức năng xuất Excel sẽ sớm có mặt!", "info");
}

// ===== CATEGORY MANAGEMENT =====
async function loadCategories() {
  try {
    // This would load categories from API
    showToast("Chức năng quản lý danh mục sẽ sớm có mặt!", "info");
  } catch (error) {
    console.error("Error loading categories:", error);
  }
}

// ===== REPORTS =====
async function loadReports() {
  try {
    // This would load reports from API
    showToast("Chức năng báo cáo sẽ sớm có mặt!", "info");
  } catch (error) {
    console.error("Error loading reports:", error);
  }
}

// ===== UTILITY FUNCTIONS =====
function updateDateTime() {
  const now = new Date();

  // Format date: Thứ, ngày tháng năm
  const dateOptions = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  };
  const formattedDate = now.toLocaleDateString("vi-VN", dateOptions);
  document.getElementById("current-date").textContent = formattedDate;

  // Format time: HH:MM:SS
  const timeOptions = { hour: "2-digit", minute: "2-digit", second: "2-digit" };
  const formattedTime = now.toLocaleTimeString("vi-VN", timeOptions);
  document.getElementById("current-time").textContent = formattedTime;
}

function updateAdminUI() {
  if (currentAdmin) {
    const avatar = document.querySelector(".avatar");
    const userName = document.querySelector(".user-info h4");

    if (avatar && currentAdmin.avatar) {
      avatar.innerHTML = `<img src="${currentAdmin.avatar}" alt="${currentAdmin.name}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
    }

    if (userName) {
      userName.textContent = currentAdmin.name;
    }
  }
}

function handleLogout() {
  if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
    // Clear admin data
    localStorage.removeItem("admin_token");
    localStorage.removeItem("admin_data");

    // Redirect to login page
    window.location.href = "login.html";
  }
}

function toggleNotifications() {
  // In a real application, this would show a dropdown with notifications
  // For now, show a notification
  showToast("Chức năng thông báo sẽ sớm có mặt!", "info");
}

function closeAllModals() {
  document.querySelectorAll(".modal").forEach((modal) => {
    modal.classList.remove("active");
  });
  document.body.style.overflow = "";
}

function showLoading() {
  let overlay = document.querySelector(".loading-overlay");

  if (!overlay) {
    overlay = document.createElement("div");
    overlay.className = "loading-overlay";
    overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Đang xử lý...</p>
            </div>
        `;
    document.body.appendChild(overlay);
  }

  overlay.classList.add("active");
}

function hideLoading() {
  const overlay = document.querySelector(".loading-overlay");
  if (overlay) {
    overlay.classList.remove("active");
  }
}

function showToast(message, type = "info") {
  // Remove existing toasts
  const existingToasts = document.querySelectorAll(".toast");
  existingToasts.forEach((toast) => {
    toast.remove();
  });

  // Create new toast
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.textContent = message;

  document.body.appendChild(toast);

  // Show toast
  setTimeout(() => {
    toast.classList.add("show");
  }, 10);

  // Auto remove after 5 seconds
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => {
      if (toast.parentNode) {
        toast.remove();
      }
    }, 300);
  }, 5000);
}

function initTooltips() {
  // Initialize tooltips for buttons with title attribute
  const tooltipElements = document.querySelectorAll("[title]");

  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", function (e) {
      const tooltipText = this.getAttribute("title");
      if (!tooltipText) return;

      const tooltip = document.createElement("div");
      tooltip.className = "tooltip";
      tooltip.textContent = tooltipText;
      tooltip.style.position = "fixed";
      tooltip.style.zIndex = "9999";
      tooltip.style.backgroundColor = "var(--admin-primary)";
      tooltip.style.color = "white";
      tooltip.style.padding = "6px 12px";
      tooltip.style.borderRadius = "4px";
      tooltip.style.fontSize = "0.85rem";
      tooltip.style.boxShadow = "0 2px 8px rgba(0,0,0,0.2)";

      document.body.appendChild(tooltip);

      const rect = this.getBoundingClientRect();
      tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px";
      tooltip.style.left =
        rect.left + (rect.width - tooltip.offsetWidth) / 2 + "px";

      this._tooltip = tooltip;

      // Remove title attribute to prevent default browser tooltip
      this.removeAttribute("title");
    });

    element.addEventListener("mouseleave", function () {
      if (this._tooltip) {
        this._tooltip.remove();
        this._tooltip = null;
      }
      // Restore title attribute
      const tooltipText = this.getAttribute("data-original-title");
      if (tooltipText) {
        this.setAttribute("title", tooltipText);
      }
    });
  });
}

// Format currency (Vietnamese Dong)
function formatCurrency(amount) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    minimumFractionDigits: 0,
  }).format(amount);
}

// Format date
function formatDate(dateString) {
  if (!dateString) return "—";

  const date = new Date(dateString);
  return date.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

// Format number with thousands separator
function formatNumber(number) {
  return new Intl.NumberFormat("vi-VN").format(number);
}

// Debounce function for performance
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Export functions for global use
window.editTour = editTour;
window.viewTour = viewTour;
window.deleteTour = deleteTour;
window.viewBooking = viewBooking;
window.editBooking = editBooking;
window.deleteBooking = deleteBooking;
window.viewCustomer = viewCustomer;
window.editCustomer = editCustomer;
window.deleteCustomer = deleteCustomer;
window.viewHotelBooking = viewHotelBooking;
window.editHotelBooking = editHotelBooking;
window.deleteHotelBooking = deleteHotelBooking;
