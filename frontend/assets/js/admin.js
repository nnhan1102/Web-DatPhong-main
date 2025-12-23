// admin.js - Simple working version
const API_BASE_URL =
  "http://localhost/Web-DatPhong-main/backend/api/controllers";

// ===== BASIC NAVIGATION =====
document.addEventListener("DOMContentLoaded", function () {
  console.log("Admin page loaded");
  initNavigation();
  updateDateTime();

  // Add room button
  const addRoomBtn = document.getElementById("add-room-btn");
  if (addRoomBtn) {
    addRoomBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openRoomModal();
    });
  }

  // Add room type button - THÊM VÀO ĐÂY
  const addRoomTypeBtn = document.getElementById("add-room-type-btn");
  if (addRoomTypeBtn) {
    addRoomTypeBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openRoomTypeModal();
    });
  }

  // Room filters apply button
  const applyFiltersBtn = document.getElementById("apply-room-filters");
  if (applyFiltersBtn) {
    applyFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadRooms();
    });
  }

  // Add booking button
  const addBookingBtn = document.getElementById("add-booking-btn");
  if (addBookingBtn) {
    addBookingBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openBookingModal();
    });
  }

  // Apply filters button for bookings
  const applyBookingFiltersBtn = document.getElementById(
    "apply-booking-filters"
  );
  if (applyBookingFiltersBtn) {
    applyBookingFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadBookings();
    });
  }

  // Reset filters button
  const resetBookingFiltersBtn = document.getElementById(
    "reset-booking-filters"
  );
  if (resetBookingFiltersBtn) {
    resetBookingFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      resetBookingFilters();
    });
  }
  // Add service button
  const addServiceBtn = document.getElementById("add-service-btn");
  if (addServiceBtn) {
    addServiceBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openServiceModal();
    });
  }
  // Logout button
  const logoutBtn = document.querySelector(".logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
        window.location.href = "logout.php";
      }
    });
  }
});

function initNavigation() {
  console.log("Initializing navigation...");

  // Get all menu items
  const menuItems = document.querySelectorAll(".sidebar-menu li");
  console.log("Found menu items:", menuItems.length);

  // Add click event to each menu item
  menuItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      console.log("Menu clicked:", this.getAttribute("data-section"));

      // Remove active class from all menu items
      menuItems.forEach((i) => i.classList.remove("active"));

      // Add active class to clicked item
      this.classList.add("active");

      // Get section to show
      const section = this.getAttribute("data-section");

      // Show the corresponding section
      showSection(section);
    });
  });

  // Test: Force show rooms section for debugging
  console.log("Navigation initialized successfully");
}

function showSection(section) {
  console.log("Switching to section:", section);

  // Hide all content sections
  const allSections = document.querySelectorAll(".content-section");
  console.log("Total sections found:", allSections.length);

  allSections.forEach((section) => {
    section.classList.remove("active");
    console.log("Hiding section:", section.id);
  });

  // Show the selected section
  const targetSection = document.getElementById(section + "-section");
  if (targetSection) {
    targetSection.classList.add("active");
    console.log("Showing section:", targetSection.id);

    // Update page title
    updatePageTitle(section);

    // Load section data if needed
    loadSectionData(section);
  } else {
    console.error("Section not found:", section + "-section");
  }
}

function updatePageTitle(section) {
  const titleMap = {
    dashboard: "Dashboard",
    rooms: "Quản lý Phòng",
    "room-types": "Loại Phòng",
    bookings: "Đặt Phòng",
    customers: "Khách hàng",
    services: "Dịch vụ",
    staff: "Nhân viên",
    reports: "Báo cáo",
  };

  const titleElement = document.getElementById("page-title");
  const subtitleElement = document.getElementById("page-subtitle");

  if (titleElement && titleMap[section]) {
    titleElement.textContent = titleMap[section];

    // Update subtitle based on section
    const subtitles = {
      dashboard: "Tổng quan hệ thống khách sạn",
      rooms: "Quản lý và xem trạng thái phòng",
      "room-types": "Quản lý các loại phòng",
      bookings: "Quản lý đặt phòng và check-in/out",
      customers: "Quản lý thông tin khách hàng",
      services: "Quản lý dịch vụ khách sạn",
      staff: "Quản lý nhân viên và phân công",
      reports: "Thống kê và báo cáo doanh thu",
    };

    if (subtitleElement && subtitles[section]) {
      subtitleElement.textContent = subtitles[section];
    }
  }
}

// Update loadSectionData function
function loadSectionData(section) {
  console.log("Loading data for:", section);

  switch (section) {
    case "dashboard":
      loadDashboardData();
      break;
    case "rooms":
      loadRooms();
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
      loadReports();
      break;
    default:
      console.log("No data loader for:", section);
  }
}
// ===== ROOMS MANAGEMENT =====
async function loadRooms() {
  console.log("Loading rooms...");

  const tbody = document.getElementById("rooms-table-body");
  if (!tbody) {
    console.error("rooms-table-body not found!");
    return;
  }

  // Show loading state
  tbody.innerHTML = `
    <tr>
        <td colspan="10" style="text-align: center; padding: 40px;">
            <div class="loading">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i>
                <p style="margin-top: 15px; color: #666;">Đang tải danh sách phòng...</p>
            </div>
        </td>
    </tr>
  `;

  try {
    const response = await fetch(
      `${API_BASE_URL}/RoomController.php?action=getAll`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        credentials: "include",
      }
    );

    console.log("Response status:", response.status);
    console.log("Response headers:", response.headers);

    // Kiểm tra nếu response không phải JSON
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error("Response is not JSON:", text.substring(0, 500));
      throw new Error(`Server returned non-JSON: ${response.status}`);
    }

    const data = await response.json();
    console.log("API response:", data);

    if (!response.ok) {
      throw new Error(data.message || `HTTP error! status: ${response.status}`);
    }

    if (data.success && data.data && data.data.length > 0) {
      // Render rooms table
      renderRoomsTable(data.data);
    } else {
      // No rooms found
      tbody.innerHTML = `
        <tr>
            <td colspan="10" class="no-data">
                <i class="fas fa-bed fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                <p>${data.message || "Không có phòng nào"}</p>
                <button class="btn btn-primary" onclick="openRoomModal()" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Thêm phòng đầu tiên
                </button>
            </td>
        </tr>
      `;
    }
  } catch (error) {
    console.error("Error loading rooms:", error);

    // Show error message
    tbody.innerHTML = `
      <tr>
          <td colspan="10" class="no-data error">
              <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 15px;"></i>
              <p>Lỗi tải dữ liệu</p>
              <p style="font-size: 0.9em; color: #999;">${error.message}</p>
              <p style="font-size: 0.9em; color: #999;">URL: ${API_BASE_URL}/RoomController.php?action=getAll</p>
              <button class="btn btn-secondary" onclick="loadRooms()" style="margin-top: 15px;">
                  <i class="fas fa-redo"></i> Thử lại
              </button>
              <button class="btn btn-info" onclick="testApiConnection()" style="margin-top: 15px; margin-left: 10px;">
                  <i class="fas fa-plug"></i> Test API Connection
              </button>
          </td>
      </tr>
    `;
  }
}

// Hàm test kết nối API
async function testApiConnection() {
  try {
    console.log("Testing API connection...");
    const response = await fetch(
      `${API_BASE_URL}/RoomController.php?action=getAll`
    );
    console.log("Test response:", response);

    if (response.ok) {
      alert("✅ Kết nối API thành công!");
    } else {
      alert(`❌ Lỗi kết nối: ${response.status} ${response.statusText}`);
    }
  } catch (error) {
    alert(`❌ Lỗi kết nối: ${error.message}`);
  }
}
function renderRoomsTable(rooms) {
  const tbody = document.getElementById("rooms-table-body");
  if (!tbody) return;

  console.log("Rendering", rooms.length, "rooms");

  let html = "";

  rooms.forEach((room, index) => {
    // Get status text and class
    const statusInfo = getRoomStatusInfo(room.status);

    // Get view text
    const viewText = getViewText(room.view_type);

    // Format amenities
    let amenitiesText = "Không có";
    if (room.amenities) {
      try {
        const amenities = JSON.parse(room.amenities);
        if (Array.isArray(amenities) && amenities.length > 0) {
          amenitiesText = amenities.slice(0, 3).join(", ");
          if (amenities.length > 3) {
            amenitiesText += `, +${amenities.length - 3}`;
          }
        }
      } catch (e) {
        amenitiesText = room.amenities;
      }
    }

    html += `
            <tr>
                <td>${room.id || index + 1}</td>
                <td><strong>${room.room_number || "N/A"}</strong></td>
                <td>${room.type_name || "N/A"}</td>
                <td>${room.floor || "N/A"}</td>
                <td>${viewText}</td>
                <td>${formatCurrency(room.base_price || 0)}</td>
                <td>${room.capacity || 2} người</td>
                <td>
                    <span class="status-badge ${statusInfo.class}">
                        ${statusInfo.text}
                    </span>
                </td>
                <td title="${amenitiesText}">${
      amenitiesText.length > 30
        ? amenitiesText.substring(0, 30) + "..."
        : amenitiesText
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
  console.log("Rooms table rendered");
}

// ===== UTILITY FUNCTIONS =====
function getRoomStatusInfo(status) {
  const statusMap = {
    available: { text: "Có sẵn", class: "status-available" },
    occupied: { text: "Đã đặt", class: "status-occupied" },
    maintenance: { text: "Bảo trì", class: "status-maintenance" },
    cleaning: { text: "Đang dọn", class: "status-cleaning" },
  };

  return statusMap[status] || { text: status, class: "status-inactive" };
}

function getViewText(viewType) {
  const viewMap = {
    city: "Thành phố",
    sea: "Biển",
    garden: "Vườn",
    pool: "Hồ bơi",
  };

  return viewMap[viewType] || viewType || "N/A";
}

function formatCurrency(amount) {
  if (!amount) return "0đ";
  return new Intl.NumberFormat("vi-VN").format(amount) + "đ";
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
    second: "2-digit",
  });

  const dateElement = document.getElementById("current-date");
  const timeElement = document.getElementById("current-time");

  if (dateElement) dateElement.textContent = dateStr;
  if (timeElement) timeElement.textContent = timeStr;

  // Update every second
  setTimeout(updateDateTime, 1000);
}

// ===== PLACEHOLDER FUNCTIONS FOR NOW =====
function loadDashboardData() {
  console.log("Loading dashboard data...");
  // To be implemented
}

// ===== ROOM TYPES MANAGEMENT =====
async function loadRoomTypes() {
  console.log("Loading room types...");

  const container = document.getElementById("room-types-container");
  if (!container) {
    console.error("room-types-container not found!");
    return;
  }

  // Show loading state
  container.innerHTML = `
    <div class="loading" style="text-align: center; padding: 40px;">
      <i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i>
      <p style="margin-top: 15px; color: #666;">Đang tải danh sách loại phòng...</p>
    </div>
  `;

  try {
    const response = await fetch(
      `${API_BASE_URL}/RoomTypeController.php?action=getAll&limit=100`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      }
    );

    console.log("Response status:", response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log("Room types response:", data);

    if (data.success && data.data && data.data.length > 0) {
      // Render room types as cards
      renderRoomTypesCards(data.data);
    } else {
      // No room types found
      container.innerHTML = `
        <div class="no-data" style="text-align: center; padding: 40px;">
          <i class="fas fa-list fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
          <p>${data.message || "Không có loại phòng nào"}</p>
          <button class="btn btn-primary" onclick="openRoomTypeModal()" style="margin-top: 15px;">
            <i class="fas fa-plus"></i> Thêm loại phòng đầu tiên
          </button>
        </div>
      `;
    }
  } catch (error) {
    console.error("Error loading room types:", error);

    // Show error message
    container.innerHTML = `
      <div class="no-data error" style="text-align: center; padding: 40px;">
        <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 15px;"></i>
        <p>Lỗi tải dữ liệu</p>
        <p style="font-size: 0.9em; color: #999;">${error.message}</p>
        <button class="btn btn-secondary" onclick="loadRoomTypes()" style="margin-top: 15px;">
          <i class="fas fa-redo"></i> Thử lại
        </button>
      </div>
    `;
  }
}

function renderRoomTypesCards(roomTypes) {
  const container = document.getElementById("room-types-container");
  if (!container) return;

  console.log("Rendering", roomTypes.length, "room types");

  let html = '<div class="room-types-grid">';

  roomTypes.forEach((roomType) => {
    // Parse amenities
    let amenities = [];
    if (roomType.amenities) {
      if (typeof roomType.amenities === "string") {
        try {
          amenities = JSON.parse(roomType.amenities);
        } catch (e) {
          amenities = roomType.amenities.split(",").map((a) => a.trim());
        }
      } else if (Array.isArray(roomType.amenities)) {
        amenities = roomType.amenities;
      }
    }

    // Format amenities as HTML
    let amenitiesHtml = "";
    if (amenities.length > 0) {
      amenitiesHtml = '<div class="amenities-list">';
      amenities.slice(0, 3).forEach((amenity) => {
        amenitiesHtml += `<span class="amenity-tag">${amenity}</span>`;
      });
      if (amenities.length > 3) {
        amenitiesHtml += `<span class="amenity-more">+${
          amenities.length - 3
        } nữa</span>`;
      }
      amenitiesHtml += "</div>";
    }

    html += `
      <div class="room-type-card">
        <div class="room-type-header">
          <h3>${roomType.type_name}</h3>
          <div class="room-type-price">
            ${formatCurrency(parseFloat(roomType.base_price) || 0)}/đêm
          </div>
        </div>
        
        <div class="room-type-body">
          <p class="room-type-description">${
            roomType.description || "Không có mô tả"
          }</p>
          
          <div class="room-type-details">
            <div class="detail-item">
              <i class="fas fa-user-friends"></i>
              <span>${roomType.capacity} người</span>
            </div>
            <div class="detail-item">
              <i class="fas fa-bed"></i>
              <span>${getBedCount(roomType.capacity)} giường</span>
            </div>
          </div>
          
          ${amenitiesHtml}
          
          <div class="room-type-stats">
            <div class="stat">
              <span class="stat-label">Đã tạo:</span>
              <span class="stat-value">${formatDate(roomType.created_at)}</span>
            </div>
          </div>
        </div>
        
        <div class="room-type-footer">
          <button class="btn btn-sm btn-secondary" onclick="viewRoomType(${
            roomType.id
          })">
            <i class="fas fa-eye"></i> Xem
          </button>
          <button class="btn btn-sm btn-primary" onclick="editRoomType(${
            roomType.id
          })">
            <i class="fas fa-edit"></i> Sửa
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteRoomType(${
            roomType.id
          })">
            <i class="fas fa-trash"></i> Xóa
          </button>
        </div>
      </div>
    `;
  });

  html += "</div>";
  container.innerHTML = html;

  // Add CSS for the grid
  addRoomTypesStyles();
}

function getBedCount(capacity) {
  // Tính số giường dựa trên sức chứa
  if (capacity <= 2) return 1;
  if (capacity <= 4) return 2;
  return Math.ceil(capacity / 2);
}

function addRoomTypesStyles() {
  // Add dynamic styles if not already present
  if (!document.getElementById("room-types-styles")) {
    const style = document.createElement("style");
    style.id = "room-types-styles";
    style.textContent = `
      .room-types-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
      }
      
      .room-type-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
      }
      
      .room-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
      }
      
      .room-type-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        position: relative;
      }
      
      .room-type-header h3 {
        margin: 0 0 10px 0;
        font-size: 1.2rem;
      }
      
      .room-type-price {
        font-size: 1.5rem;
        font-weight: bold;
      }
      
      .room-type-body {
        padding: 20px;
      }
      
      .room-type-description {
        color: #666;
        margin-bottom: 15px;
        line-height: 1.5;
      }
      
      .room-type-details {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
      }
      
      .detail-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #555;
      }
      
      .amenities-list {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 15px;
      }
      
      .amenity-tag {
        background: #e3f2fd;
        color: #1976d2;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
      }
      
      .amenity-more {
        background: #f5f5f5;
        color: #757575;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
      }
      
      .room-type-stats {
        padding-top: 15px;
        border-top: 1px solid #eee;
      }
      
      .stat {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
      }
      
      .stat-label {
        color: #777;
      }
      
      .stat-value {
        color: #333;
        font-weight: 500;
      }
      
      .room-type-footer {
        padding: 15px 20px;
        background: #f9f9f9;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
      }
      
      .btn-sm {
        padding: 5px 12px;
        font-size: 0.85rem;
      }
    `;
    document.head.appendChild(style);
  }
}

// ===== ROOM TYPE MODAL FUNCTIONS =====
function openRoomTypeModal(typeId = null) {
  console.log("Open room type modal for:", typeId);

  // Tạo modal nếu chưa có
  if (!document.getElementById("room-type-modal")) {
    createRoomTypeModal();
  }

  const modal = document.getElementById("room-type-modal");
  const title = document.getElementById("room-type-modal-title");

  if (typeId) {
    // Edit mode
    title.textContent = "Chỉnh sửa Loại Phòng";
    loadRoomTypeData(typeId);
  } else {
    // Add mode
    title.textContent = "Thêm Loại Phòng Mới";
    resetRoomTypeForm();
  }

  modal.style.display = "block";
}

function createRoomTypeModal() {
  const modalHTML = `
    <div class="modal" id="room-type-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="room-type-modal-title">Thêm Loại Phòng Mới</h3>
          <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
          <form id="room-type-form">
            <input type="hidden" id="room-type-id" />
            
            <div class="form-group">
              <label for="room-type-name">Tên loại phòng *</label>
              <input type="text" id="room-type-name" required />
            </div>
            
            <div class="form-group">
              <label for="room-type-description">Mô tả</label>
              <textarea id="room-type-description" rows="3"></textarea>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="room-type-price">Giá cơ bản (USD) *</label>
                <input type="number" id="room-type-price" min="0" step="0.01" required />
              </div>
              <div class="form-group">
                <label for="room-type-capacity">Sức chứa (người) *</label>
                <input type="number" id="room-type-capacity" min="1" max="10" required />
              </div>
            </div>
            
            <div class="form-group">
              <label>Tiện nghi</label>
              <div class="amenities-selector">
                <div class="amenity-options">
                  <label><input type="checkbox" value="WiFi"> WiFi</label>
                  <label><input type="checkbox" value="TV"> TV</label>
                  <label><input type="checkbox" value="Minibar"> Mini Bar</label>
                  <label><input type="checkbox" value="Air Conditioning"> Điều hòa</label>
                  <label><input type="checkbox" value="Safe"> Két an toàn</label>
                  <label><input type="checkbox" value="Balcony"> Ban công</label>
                  <label><input type="checkbox" value="Jacuzzi"> Jacuzzi</label>
                  <label><input type="checkbox" value="Kitchenette"> Bếp nhỏ</label>
                </div>
                <div class="custom-amenity">
                  <input type="text" id="custom-amenity" placeholder="Thêm tiện nghi khác..." />
                  <button type="button" class="btn btn-sm" onclick="addCustomAmenity()">Thêm</button>
                </div>
                <div id="selected-amenities"></div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary close-modal">Hủy</button>
              <button type="submit" class="btn btn-primary">Lưu Loại Phòng</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add event listeners
  const modal = document.getElementById("room-type-modal");
  const closeBtn = modal.querySelector(".close-modal");
  const form = document.getElementById("room-type-form");

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  form.addEventListener("submit", handleRoomTypeFormSubmit);
}

function handleRoomTypeFormSubmit(e) {
  e.preventDefault();

  const id = document.getElementById("room-type-id").value;
  const data = {
    type_name: document.getElementById("room-type-name").value,
    description: document.getElementById("room-type-description").value,
    base_price: document.getElementById("room-type-price").value,
    capacity: document.getElementById("room-type-capacity").value,
    amenities: getSelectedAmenities(),
  };

  saveRoomType(id, data);
}

function getSelectedAmenities() {
  const checkboxes = document.querySelectorAll(
    '.amenity-options input[type="checkbox"]:checked'
  );
  const customAmenities = document.querySelectorAll(".custom-amenity-tag");

  let amenities = [];
  checkboxes.forEach((cb) => amenities.push(cb.value));
  customAmenities.forEach((tag) =>
    amenities.push(tag.textContent.replace(" ×", ""))
  );

  return amenities;
}

async function saveRoomType(id, data) {
  try {
    const url = `${API_BASE_URL}/RoomTypeController.php?action=${
      id ? "update&id=" + id : "create"
    }`;
    const method = id ? "PUT" : "POST";

    const response = await fetch(url, {
      method: id ? "PUT" : "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();

    if (result.success) {
      showToast("Lưu loại phòng thành công!", "success");
      document.getElementById("room-type-modal").style.display = "none";
      loadRoomTypes(); // Reload the list
    } else {
      showToast(result.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving room type:", error);
    showToast("Lỗi kết nối server", "error");
  }
}

function loadRoomTypeData(id) {
  fetch(`${API_BASE_URL}/RoomTypeController.php?action=getById&id=${id}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const roomType = data.data;
        document.getElementById("room-type-id").value = roomType.id;
        document.getElementById("room-type-name").value = roomType.type_name;
        document.getElementById("room-type-description").value =
          roomType.description || "";
        document.getElementById("room-type-price").value = roomType.base_price;
        document.getElementById("room-type-capacity").value = roomType.capacity;

        // Set amenities checkboxes
        const amenities = Array.isArray(roomType.amenities)
          ? roomType.amenities
          : roomType.amenities
          ? JSON.parse(roomType.amenities)
          : [];

        const checkboxes = document.querySelectorAll(
          '.amenity-options input[type="checkbox"]'
        );
        checkboxes.forEach((cb) => {
          cb.checked = amenities.includes(cb.value);
        });

        // Clear and add custom amenities
        const selectedContainer = document.getElementById("selected-amenities");
        selectedContainer.innerHTML = "";
        amenities.forEach((amenity) => {
          if (!Array.from(checkboxes).some((cb) => cb.value === amenity)) {
            addAmenityTag(amenity);
          }
        });
      }
    })
    .catch((error) => {
      console.error("Error loading room type data:", error);
      showToast("Không thể tải dữ liệu", "error");
    });
}

function resetRoomTypeForm() {
  document.getElementById("room-type-form").reset();
  document.getElementById("room-type-id").value = "";
  document.getElementById("selected-amenities").innerHTML = "";
}

function addCustomAmenity() {
  const input = document.getElementById("custom-amenity");
  const amenity = input.value.trim();

  if (amenity) {
    addAmenityTag(amenity);
    input.value = "";
  }
}

function addAmenityTag(amenity) {
  const container = document.getElementById("selected-amenities");
  const tag = document.createElement("span");
  tag.className = "custom-amenity-tag";
  tag.innerHTML = `${amenity} <span onclick="removeAmenityTag(this)">×</span>`;
  container.appendChild(tag);
}

function removeAmenityTag(element) {
  element.parentElement.remove();
}

// ===== ROOM TYPE ACTION FUNCTIONS =====
function viewRoomType(id) {
  console.log("View room type:", id);
  // Tạo modal xem chi tiết hoặc chuyển sang trang chi tiết
  openRoomTypeModal(id);
}

function editRoomType(id) {
  console.log("Edit room type:", id);
  openRoomTypeModal(id);
}

function deleteRoomType(id) {
  if (
    confirm(
      "Bạn có chắc chắn muốn xóa loại phòng này? Các phòng thuộc loại này sẽ không thể sử dụng."
    )
  ) {
    fetch(`${API_BASE_URL}/RoomTypeController.php?action=delete&id=${id}`, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Xóa loại phòng thành công", "success");
          loadRoomTypes(); // Reload the list
        } else {
          showToast(data.message || "Không thể xóa loại phòng", "error");
        }
      })
      .catch((error) => {
        console.error("Error deleting room type:", error);
        showToast("Lỗi kết nối server", "error");
      });
  }
}

// ===== UTILITY FUNCTIONS =====
function showToast(message, type = "info") {
  const toast = document.getElementById("toast") || createToastElement();

  toast.textContent = message;
  toast.className = `toast ${type}`;
  toast.style.display = "block";

  setTimeout(() => {
    toast.style.display = "none";
  }, 3000);
}

function createToastElement() {
  const toast = document.createElement("div");
  toast.id = "toast";
  toast.className = "toast";
  document.body.appendChild(toast);
  return toast;
}

// ===== BOOKINGS MANAGEMENT =====

async function loadBookings() {
  console.log("Loading bookings...");

  const tbody = document.getElementById("bookings-table-body");
  if (!tbody) {
    console.error("bookings-table-body not found!");
    return;
  }

  // Show loading state
  tbody.innerHTML = `
        <tr>
            <td colspan="12" style="text-align: center; padding: 40px;">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i>
                    <p style="margin-top: 15px; color: #666;">Đang tải danh sách đặt phòng...</p>
                </div>
            </td>
        </tr>
    `;

  try {
    // Build query string from filters
    let query = `?action=getAll`;

    const search = document.getElementById("booking-search")?.value;
    const status = document.getElementById("booking-status-filter")?.value;
    const dateFrom = document.getElementById("booking-date-from")?.value;
    const dateTo = document.getElementById("booking-date-to")?.value;

    if (search) query += `&search=${encodeURIComponent(search)}`;
    if (status) query += `&status=${status}`;
    if (dateFrom) query += `&date_from=${dateFrom}`;
    if (dateTo) query += `&date_to=${dateTo}`;

    console.log(
      "Calling API:",
      `${API_BASE_URL}/BookingController.php${query}`
    );

    const response = await fetch(
      `${API_BASE_URL}/BookingController.php${query}`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      }
    );

    console.log("Response status:", response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log("API response:", data);

    if (data.success && data.data && data.data.length > 0) {
      // Render bookings table
      renderBookingsTable(data.data);

      // Update pagination if exists
      if (data.pagination) {
        updateBookingsPagination(data.pagination);
      }
    } else {
      // No bookings found
      tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="no-data">
                        <i class="fas fa-calendar fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                        <p>${data.message || "Không có đặt phòng nào"}</p>
                        <button class="btn btn-primary" onclick="openBookingModal()" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Tạo đặt phòng đầu tiên
                        </button>
                    </td>
                </tr>
            `;
    }
  } catch (error) {
    console.error("Error loading bookings:", error);

    // Show error message
    tbody.innerHTML = `
            <tr>
                <td colspan="12" class="no-data error">
                    <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 15px;"></i>
                    <p>Lỗi tải dữ liệu</p>
                    <p style="font-size: 0.9em; color: #999;">${error.message}</p>
                    <button class="btn btn-secondary" onclick="loadBookings()" style="margin-top: 15px;">
                        <i class="fas fa-redo"></i> Thử lại
                    </button>
                </td>
            </tr>
        `;
  }
}

function renderBookingsTable(bookings) {
  const tbody = document.getElementById("bookings-table-body");
  if (!tbody) return;

  console.log("Rendering", bookings.length, "bookings");

  let html = "";

  bookings.forEach((booking, index) => {
    // Get status info
    const statusInfo = getBookingStatusInfo(booking.status);
    const paymentStatusInfo = getPaymentStatusInfo(booking.payment_status);

    // Format dates
    const checkInDate = formatDate(booking.check_in);
    const checkOutDate = formatDate(booking.check_out);
    const createdDate = formatDateTime(booking.created_at);

    html += `
            <tr>
                <td><strong>${
                  booking.booking_code || `BK${booking.id}`
                }</strong></td>
                <td>
                    <div>${booking.customer_name || "N/A"}</div>
                    <small class="text-muted">${
                      booking.customer_phone || ""
                    }</small>
                </td>
                <td>
                    <div>Phòng ${booking.room_number || "N/A"}</div>
                    <small class="text-muted">${booking.room_type || ""}</small>
                </td>
                <td>${checkInDate}</td>
                <td>${checkOutDate}</td>
                <td>${booking.nights || 1} đêm</td>
                <td>${booking.num_guests || 1} khách</td>
                <td><strong>${formatCurrency(
                  booking.total_price || 0
                )}</strong></td>
                <td>
                    <span class="payment-badge ${paymentStatusInfo.class}">
                        ${paymentStatusInfo.text}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${statusInfo.class}">
                        ${statusInfo.text}
                    </span>
                </td>
                <td>${createdDate}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view-btn" onclick="viewBooking(${
                          booking.id
                        })">
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
  console.log("Bookings table rendered");
}

function updateBookingsPagination(pagination) {
  const paginationElement = document.getElementById("bookings-pagination");
  if (!paginationElement) return;

  const totalPages = pagination.total_pages;
  const currentPage = pagination.page;

  if (totalPages <= 1) {
    paginationElement.innerHTML = "";
    return;
  }

  let html = '<ul class="pagination">';

  // Previous button
  if (currentPage > 1) {
    html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changeBookingsPage(${
                  currentPage - 1
                })">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
  }

  // Page numbers
  for (let i = 1; i <= totalPages; i++) {
    if (i === currentPage) {
      html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
    } else if (
      i === 1 ||
      i === totalPages ||
      (i >= currentPage - 1 && i <= currentPage + 1)
    ) {
      html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changeBookingsPage(${i})">${i}</a>
                </li>
            `;
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
    }
  }

  // Next button
  if (currentPage < totalPages) {
    html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changeBookingsPage(${
                  currentPage + 1
                })">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
  }

  html += "</ul>";
  paginationElement.innerHTML = html;
}

function changeBookingsPage(page) {
  // Update URL or store page in variable
  // For now, reload with page parameter
  loadBookings();
}

function getBookingStatusInfo(status) {
  const statusMap = {
    pending: { text: "Chờ xác nhận", class: "status-pending" },
    confirmed: { text: "Đã xác nhận", class: "status-confirmed" },
    checked_in: { text: "Đã nhận phòng", class: "status-checked_in" },
    checked_out: { text: "Đã trả phòng", class: "status-checked_out" },
    cancelled: { text: "Đã hủy", class: "status-cancelled" },
  };

  return statusMap[status] || { text: status, class: "status-inactive" };
}

function getPaymentStatusInfo(status) {
  const statusMap = {
    pending: { text: "Chưa thanh toán", class: "payment-pending" },
    paid: { text: "Đã thanh toán", class: "payment-paid" },
    refunded: { text: "Đã hoàn tiền", class: "payment-refunded" },
    failed: { text: "Thất bại", class: "payment-failed" },
  };

  return statusMap[status] || { text: status, class: "payment-pending" };
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("vi-VN");
  } catch (e) {
    return dateString;
  }
}

function formatDateTime(dateTimeString) {
  if (!dateTimeString) return "N/A";
  try {
    const date = new Date(dateTimeString);
    return date.toLocaleString("vi-VN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch (e) {
    return dateTimeString;
  }
}

// Add button event listeners for bookings
document.addEventListener("DOMContentLoaded", function () {
  // Add booking button
  const addBookingBtn = document.getElementById("add-booking-btn");
  if (addBookingBtn) {
    addBookingBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openBookingModal();
    });
  }

  // Apply filters button for bookings
  const applyBookingFiltersBtn = document.getElementById(
    "apply-booking-filters"
  );
  if (applyBookingFiltersBtn) {
    applyBookingFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadBookings();
    });
  }

  // Reset filters button
  const resetBookingFiltersBtn = document.getElementById(
    "reset-booking-filters"
  );
  if (resetBookingFiltersBtn) {
    resetBookingFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      resetBookingFilters();
    });
  }
});

// Reset booking filters
function resetBookingFilters() {
  document.getElementById("booking-search").value = "";
  document.getElementById("booking-status-filter").value = "";
  document.getElementById("booking-date-from").value = "";
  document.getElementById("booking-date-to").value = "";
  loadBookings();
}

// ===== BOOKING MODAL FUNCTIONS =====
function openBookingModal(bookingId = null) {
  console.log("Open booking modal for:", bookingId);

  // Tạo modal nếu chưa có
  if (!document.getElementById("booking-modal")) {
    createBookingModal();
  }

  const modal = document.getElementById("booking-modal");
  const title = document.getElementById("booking-modal-title");

  if (bookingId) {
    // Edit mode
    title.textContent = "Chỉnh sửa Đặt Phòng";
    loadBookingData(bookingId);
  } else {
    // Add mode
    title.textContent = "Thêm Đặt Phòng Mới";
    resetBookingForm();
  }

  modal.style.display = "block";
}

function createBookingModal() {
  const modalHTML = `
    <div class="modal" id="booking-modal">
      <div class="modal-content large">
        <div class="modal-header">
          <h3 id="booking-modal-title">Thêm Đặt Phòng Mới</h3>
          <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
          <form id="booking-form">
            <input type="hidden" id="booking-id" />
            
            <div class="form-row">
              <div class="form-group">
                <label for="booking-customer">Khách hàng *</label>
                <select id="booking-customer" required>
                  <option value="">Chọn khách hàng</option>
                  <option value="3">Trần Thị B (customer1)</option>
                  <!-- Load more customers from API -->
                </select>
              </div>
              <div class="form-group">
                <label for="booking-room">Phòng *</label>
                <select id="booking-room" required>
                  <option value="">Chọn phòng</option>
                  <option value="3">201 - Superior Room</option>
                  <option value="4">301 - Family Suite</option>
                  <option value="5">401 - Executive Suite</option>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="check-in">Ngày nhận phòng *</label>
                <input type="date" id="check-in" required />
              </div>
              <div class="form-group">
                <label for="check-out">Ngày trả phòng *</label>
                <input type="date" id="check-out" required />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="num-guests">Số khách *</label>
                <input type="number" id="num-guests" min="1" max="10" required value="2" />
              </div>
              <div class="form-group">
                <label for="booking-status">Trạng thái</label>
                <select id="booking-status">
                  <option value="pending">Chờ xác nhận</option>
                  <option value="confirmed">Đã xác nhận</option>
                  <option value="checked_in">Đã nhận phòng</option>
                  <option value="checked_out">Đã trả phòng</option>
                  <option value="cancelled">Đã hủy</option>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="payment-method">Phương thức thanh toán</label>
                <select id="payment-method">
                  <option value="cash">Tiền mặt</option>
                  <option value="credit_card">Thẻ tín dụng</option>
                  <option value="momo">Momo</option>
                  <option value="vnpay">VNPay</option>
                  <option value="zalopay">ZaloPay</option>
                </select>
              </div>
              <div class="form-group">
                <label for="payment-status">Trạng thái thanh toán</label>
                <select id="payment-status">
                  <option value="pending">Chưa thanh toán</option>
                  <option value="paid">Đã thanh toán</option>
                  <option value="refunded">Đã hoàn tiền</option>
                  <option value="failed">Thanh toán thất bại</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="special-requests">Yêu cầu đặc biệt</label>
              <textarea id="special-requests" rows="3" placeholder="Yêu cầu về giường, dịch vụ..."></textarea>
            </div>
            
            <div class="price-summary">
              <h4>Tổng cộng: <span id="total-price">0</span> VNĐ</h4>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary close-modal">Hủy</button>
              <button type="submit" class="btn btn-primary">Lưu Đặt Phòng</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add event listeners
  const modal = document.getElementById("booking-modal");
  const closeBtn = modal.querySelector(".close-modal");
  const form = document.getElementById("booking-form");
  const checkInInput = document.getElementById("check-in");
  const checkOutInput = document.getElementById("check-out");

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  // Set min date to today
  const today = new Date().toISOString().split("T")[0];
  checkInInput.min = today;

  // Update check-out min date when check-in changes
  checkInInput.addEventListener("change", function () {
    checkOutInput.min = this.value;
    calculatePrice();
  });

  checkOutInput.addEventListener("change", calculatePrice);

  form.addEventListener("submit", handleBookingFormSubmit);
}

function handleBookingFormSubmit(e) {
  e.preventDefault();

  const id = document.getElementById("booking-id").value;
  const data = {
    customer_id: document.getElementById("booking-customer").value,
    room_id: document.getElementById("booking-room").value,
    check_in: document.getElementById("check-in").value,
    check_out: document.getElementById("check-out").value,
    num_guests: document.getElementById("num-guests").value,
    status: document.getElementById("booking-status").value,
    payment_method: document.getElementById("payment-method").value,
    payment_status: document.getElementById("payment-status").value,
    special_requests: document.getElementById("special-requests").value,
  };

  saveBooking(id, data);
}

async function saveBooking(id, data) {
  try {
    const url = `${API_BASE_URL}/BookingController.php?action=${
      id ? "update&id=" + id : "create"
    }`;

    const response = await fetch(url, {
      method: id ? "PUT" : "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();

    if (result.success) {
      showToast(
        id ? "Cập nhật đặt phòng thành công!" : "Tạo đặt phòng thành công!",
        "success"
      );
      document.getElementById("booking-modal").style.display = "none";
      loadBookings(); // Reload the list
    } else {
      showToast(result.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving booking:", error);
    showToast("Lỗi kết nối server", "error");
  }
}

function loadBookingData(id) {
  fetch(`${API_BASE_URL}/BookingController.php?action=getById&id=${id}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const booking = data.data;
        document.getElementById("booking-id").value = booking.id;
        document.getElementById("booking-customer").value = booking.customer_id;
        document.getElementById("booking-room").value = booking.room_id;
        document.getElementById("check-in").value = booking.check_in;
        document.getElementById("check-out").value = booking.check_out;
        document.getElementById("num-guests").value = booking.num_guests;
        document.getElementById("booking-status").value = booking.status;
        document.getElementById("payment-method").value =
          booking.payment_method;
        document.getElementById("payment-status").value =
          booking.payment_status;
        document.getElementById("special-requests").value =
          booking.special_requests || "";

        calculatePrice();
      }
    })
    .catch((error) => {
      console.error("Error loading booking data:", error);
      showToast("Không thể tải dữ liệu", "error");
    });
}

function resetBookingForm() {
  document.getElementById("booking-form").reset();
  document.getElementById("booking-id").value = "";
  document.getElementById("total-price").textContent = "0";

  // Set default dates
  const today = new Date().toISOString().split("T")[0];
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split("T")[0];

  document.getElementById("check-in").value = today;
  document.getElementById("check-out").value = tomorrowStr;
  document.getElementById("check-out").min = today;

  calculatePrice();
}

function calculatePrice() {
  const checkIn = document.getElementById("check-in").value;
  const checkOut = document.getElementById("check-out").value;
  const roomId = document.getElementById("booking-room").value;

  if (!checkIn || !checkOut || !roomId) {
    document.getElementById("total-price").textContent = "0";
    return;
  }

  // Calculate number of nights
  const start = new Date(checkIn);
  const end = new Date(checkOut);
  const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

  if (nights <= 0) {
    document.getElementById("total-price").textContent = "0";
    return;
  }

  // Get room price (hardcoded for now - should fetch from API)
  const roomPrices = {
    1: 1500000, // Deluxe Room
    2: 1200000, // Superior Room
    3: 2500000, // Family Suite
    4: 3500000, // Executive Suite
    5: 3500000, // Executive Suite
  };

  const roomPrice = roomPrices[roomId] || 1000000;
  const totalPrice = roomPrice * nights;

  document.getElementById("total-price").textContent =
    formatCurrency(totalPrice);
}
// Cập nhật các hàm booking
function viewBooking(id) {
  console.log("View booking:", id);
  openBookingModal(id);
}

function editBooking(id) {
  console.log("Edit booking:", id);
  openBookingModal(id);
}

function deleteBooking(id) {
  if (confirm("Bạn có chắc chắn muốn xóa đặt phòng này?")) {
    fetch(`${API_BASE_URL}/BookingController.php?action=delete&id=${id}`, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Xóa đặt phòng thành công", "success");
          loadBookings(); // Reload the list
        } else {
          showToast(data.message || "Không thể xóa đặt phòng", "error");
        }
      })
      .catch((error) => {
        console.error("Error deleting booking:", error);
        showToast("Lỗi kết nối server", "error");
      });
  }
}

// Cập nhật hàm openBookingModal đã tồn tại
function openBookingModal() {
  openBookingModal();
}

// ===== CUSTOMERS MANAGEMENT =====
async function loadCustomers() {
  console.log("Loading customers...");

  const tbody = document.getElementById("customers-table-body");
  if (!tbody) {
    console.error("customers-table-body not found!");
    return;
  }

  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="9" style="text-align: center; padding: 40px;">
        <div class="loading">
          <i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i>
          <p style="margin-top: 15px; color: #666;">Đang tải danh sách khách hàng...</p>
        </div>
      </td>
    </tr>
  `;

  try {
    // Build query string from filters
    let query = `?action=getAll`;

    const search = document.getElementById("customer-search")?.value;
    const status = document.getElementById("customer-status-filter")?.value;
    const page = document.getElementById("customer-page")?.value || 1;

    if (search) query += `&search=${encodeURIComponent(search)}`;
    if (status) query += `&status=${status}`;
    if (page) query += `&page=${page}`;

    console.log(
      "Calling API:",
      `${API_BASE_URL}/CustomerController.php${query}`
    );

    const response = await fetch(
      `${API_BASE_URL}/CustomerController.php${query}`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
        },
      }
    );

    console.log("Response status:", response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log("API response:", data);

    if (data.success && data.data && data.data.length > 0) {
      // Render customers table
      renderCustomersTable(data.data);

      // Update pagination if exists
      if (data.pagination) {
        updateCustomersPagination(data.pagination);
      }
    } else {
      // No customers found
      tbody.innerHTML = `
        <tr>
          <td colspan="9" class="no-data">
            <i class="fas fa-users fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
            <p>${data.message || "Không có khách hàng nào"}</p>
            <button class="btn btn-primary" onclick="openCustomerModal()" style="margin-top: 15px;">
              <i class="fas fa-plus"></i> Thêm khách hàng đầu tiên
            </button>
          </td>
        </tr>
      `;
    }
  } catch (error) {
    console.error("Error loading customers:", error);

    // Show error message
    tbody.innerHTML = `
      <tr>
        <td colspan="9" class="no-data error">
          <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 15px;"></i>
          <p>Lỗi tải dữ liệu</p>
          <p style="font-size: 0.9em; color: #999;">${error.message}</p>
          <button class="btn btn-secondary" onclick="loadCustomers()" style="margin-top: 15px;">
            <i class="fas fa-redo"></i> Thử lại
          </button>
        </td>
      </tr>
    `;
  }
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById("customers-table-body");
  if (!tbody) return;

  console.log("Rendering", customers.length, "customers");

  let html = "";

  customers.forEach((customer) => {
    // Get status info
    const statusInfo = getCustomerStatusInfo(customer.status);

    // Format dates
    const createdDate = formatDateTime(customer.created_at);
    const updatedDate = customer.updated_at
      ? formatDateTime(customer.updated_at)
      : "N/A";

    html += `
      <tr>
        <td><strong>${customer.id}</strong></td>
        <td>
          <div><strong>${customer.full_name}</strong></div>
          <small class="text-muted">${customer.username}</small>
        </td>
        <td>
          <div>${customer.email}</div>
          <small class="text-muted">${customer.phone || "Chưa có"}</small>
        </td>
        <td>${customer.address || "Chưa có"}</td>
        <td>
          <span class="status-badge ${statusInfo.class}">
            ${statusInfo.text}
          </span>
        </td>
        <td>${createdDate}</td>
        <td>${updatedDate}</td>
        <td>
          <button class="action-btn view-btn" onclick="viewCustomerBookings(${
            customer.id
          })" title="Lịch sử đặt phòng">
            <i class="fas fa-history"></i>
          </button>
        </td>
        <td>
          <div class="action-buttons">
            <button class="action-btn edit-btn" onclick="editCustomer(${
              customer.id
            })">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn status-btn ${
              customer.status === "active" ? "inactive-btn" : "active-btn"
            }" 
                    onclick="toggleCustomerStatus(${customer.id}, '${
      customer.status
    }')"
                    title="${
                      customer.status === "active"
                        ? "Khóa tài khoản"
                        : "Kích hoạt tài khoản"
                    }">
              ${
                customer.status === "active"
                  ? '<i class="fas fa-lock"></i>'
                  : '<i class="fas fa-unlock"></i>'
              }
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

function getCustomerStatusInfo(status) {
  const statusMap = {
    active: { text: "Hoạt động", class: "status-available" },
    inactive: { text: "Đã khóa", class: "status-inactive" },
  };

  return statusMap[status] || { text: status, class: "status-inactive" };
}

function updateCustomersPagination(pagination) {
  const paginationElement = document.getElementById("customers-pagination");
  if (!paginationElement) return;

  const totalPages = pagination.total_pages;
  const currentPage = pagination.page;

  if (totalPages <= 1) {
    paginationElement.innerHTML = "";
    return;
  }

  let html = '<ul class="pagination">';

  // Previous button
  if (currentPage > 1) {
    html += `
      <li class="page-item">
        <a class="page-link" href="#" onclick="changeCustomersPage(${
          currentPage - 1
        })">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `;
  }

  // Page numbers
  for (let i = 1; i <= totalPages; i++) {
    if (i === currentPage) {
      html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
    } else if (
      i === 1 ||
      i === totalPages ||
      (i >= currentPage - 1 && i <= currentPage + 1)
    ) {
      html += `
        <li class="page-item">
          <a class="page-link" href="#" onclick="changeCustomersPage(${i})">${i}</a>
        </li>
      `;
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
    }
  }

  // Next button
  if (currentPage < totalPages) {
    html += `
      <li class="page-item">
        <a class="page-link" href="#" onclick="changeCustomersPage(${
          currentPage + 1
        })">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `;
  }

  html += "</ul>";
  paginationElement.innerHTML = html;
}

function changeCustomersPage(page) {
  document.getElementById("customer-page").value = page;
  loadCustomers();
}

// Customer action functions
function viewCustomerBookings(customerId) {
  console.log("View bookings for customer:", customerId);
  // TODO: Implement view customer bookings modal
  alert(`Xem lịch sử đặt phòng của khách hàng ID: ${customerId}`);
}

function editCustomer(customerId) {
  console.log("Edit customer:", customerId);
  openCustomerModal(customerId);
}

async function toggleCustomerStatus(customerId, currentStatus) {
  const newStatus = currentStatus === "active" ? "inactive" : "active";
  const confirmMessage =
    newStatus === "inactive"
      ? "Bạn có chắc muốn khóa tài khoản khách hàng này?"
      : "Bạn có chắc muốn kích hoạt tài khoản khách hàng này?";

  if (!confirm(confirmMessage)) return;

  try {
    const response = await fetch(
      `${API_BASE_URL}/CustomerController.php?action=updateStatus&id=${customerId}`,
      {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ status: newStatus }),
      }
    );

    const data = await response.json();

    if (data.success) {
      showToast(`Cập nhật trạng thái thành công`, "success");
      loadCustomers();
    } else {
      showToast(data.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error updating customer status:", error);
    showToast("Lỗi kết nối server", "error");
  }
}

async function deleteCustomer(customerId) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa khách hàng này?\nLưu ý: Tất cả đặt phòng liên quan sẽ bị ảnh hưởng."
    )
  ) {
    return;
  }

  try {
    const response = await fetch(
      `${API_BASE_URL}/CustomerController.php?action=delete&id=${customerId}`,
      {
        method: "DELETE",
      }
    );

    const data = await response.json();

    if (data.success) {
      showToast("Xóa khách hàng thành công", "success");
      loadCustomers();
    } else {
      showToast(data.message || "Không thể xóa khách hàng", "error");
    }
  } catch (error) {
    console.error("Error deleting customer:", error);
    showToast("Lỗi kết nối server", "error");
  }
}

// Customer modal functions
function openCustomerModal(customerId = null) {
  console.log("Open customer modal for:", customerId);

  // Create modal if not exists
  if (!document.getElementById("customer-modal")) {
    createCustomerModal();
  }

  const modal = document.getElementById("customer-modal");
  const title = document.getElementById("customer-modal-title");

  if (customerId) {
    // Edit mode
    title.textContent = "Chỉnh sửa Khách Hàng";
    loadCustomerData(customerId);
  } else {
    // Add mode
    title.textContent = "Thêm Khách Hàng Mới";
    resetCustomerForm();
  }

  modal.style.display = "block";
}

function createCustomerModal() {
  const modalHTML = `
    <div class="modal" id="customer-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="customer-modal-title">Thêm Khách Hàng Mới</h3>
          <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
          <form id="customer-form">
            <input type="hidden" id="customer-id" />
            
            <div class="form-row">
              <div class="form-group">
                <label for="customer-username">Tên đăng nhập *</label>
                <input type="text" id="customer-username" required />
              </div>
              <div class="form-group">
                <label for="customer-email">Email *</label>
                <input type="email" id="customer-email" required />
              </div>
            </div>
            
            <div class="form-group">
              <label for="customer-fullname">Họ tên *</label>
              <input type="text" id="customer-fullname" required />
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="customer-phone">Số điện thoại</label>
                <input type="tel" id="customer-phone" />
              </div>
              <div class="form-group">
                <label for="customer-status">Trạng thái</label>
                <select id="customer-status">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Đã khóa</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="customer-address">Địa chỉ</label>
              <textarea id="customer-address" rows="3"></textarea>
            </div>
            
            <div id="customer-password-section">
              <div class="form-group">
                <label for="customer-password">Mật khẩu</label>
                <input type="password" id="customer-password" placeholder="Để trống nếu không đổi" />
                <small class="form-text">Chỉ nhập nếu muốn đổi mật khẩu</small>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary close-modal">Hủy</button>
              <button type="submit" class="btn btn-primary">Lưu Khách Hàng</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add event listeners
  const modal = document.getElementById("customer-modal");
  const closeBtn = modal.querySelector(".close-modal");
  const form = document.getElementById("customer-form");

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  form.addEventListener("submit", handleCustomerFormSubmit);
}

function handleCustomerFormSubmit(e) {
  e.preventDefault();

  const id = document.getElementById("customer-id").value;
  const data = {
    username: document.getElementById("customer-username").value,
    email: document.getElementById("customer-email").value,
    full_name: document.getElementById("customer-fullname").value,
    phone: document.getElementById("customer-phone").value || null,
    address: document.getElementById("customer-address").value || null,
    status: document.getElementById("customer-status").value,
  };

  // Add password if provided
  const password = document.getElementById("customer-password").value;
  if (password) {
    data.password = password;
  }

  saveCustomer(id, data);
}

async function saveCustomer(id, data) {
  try {
    const url = `${API_BASE_URL}/CustomerController.php?action=${
      id ? "update&id=" + id : "create"
    }`;

    const response = await fetch(url, {
      method: id ? "PUT" : "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();

    if (result.success) {
      showToast(
        id ? "Cập nhật khách hàng thành công!" : "Tạo khách hàng thành công!",
        "success"
      );
      document.getElementById("customer-modal").style.display = "none";
      loadCustomers(); // Reload the list
    } else {
      showToast(result.message || "Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving customer:", error);
    showToast("Lỗi kết nối server", "error");
  }
}

async function loadCustomerData(customerId) {
  try {
    const response = await fetch(
      `${API_BASE_URL}/CustomerController.php?action=getById&id=${customerId}`
    );
    const data = await response.json();

    if (data.success) {
      const customer = data.data;

      document.getElementById("customer-id").value = customer.id;
      document.getElementById("customer-username").value = customer.username;
      document.getElementById("customer-email").value = customer.email;
      document.getElementById("customer-fullname").value = customer.full_name;
      document.getElementById("customer-phone").value = customer.phone || "";
      document.getElementById("customer-address").value =
        customer.address || "";
      document.getElementById("customer-status").value = customer.status;

      // Hide password field for editing (optional)
      document.getElementById("customer-password-section").style.display =
        "none";
    }
  } catch (error) {
    console.error("Error loading customer data:", error);
    showToast("Không thể tải dữ liệu", "error");
  }
}

function resetCustomerForm() {
  document.getElementById("customer-form").reset();
  document.getElementById("customer-id").value = "";
  document.getElementById("customer-status").value = "active";
  document.getElementById("customer-password-section").style.display = "block";
}

// Add event listeners for customer section
document.addEventListener("DOMContentLoaded", function () {
  // Add customer button
  const addCustomerBtn = document.getElementById("add-customer-btn");
  if (addCustomerBtn) {
    addCustomerBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openCustomerModal();
    });
  }

  // Apply filters button for customers
  const applyCustomerFiltersBtn = document.getElementById(
    "apply-customer-filters"
  );
  if (applyCustomerFiltersBtn) {
    applyCustomerFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadCustomers();
    });
  }

  // Reset filters button for customers
  const resetCustomerFiltersBtn = document.getElementById(
    "reset-customer-filters"
  );
  if (resetCustomerFiltersBtn) {
    resetCustomerFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      resetCustomerFilters();
    });
  }
});

// Reset customer filters
function resetCustomerFilters() {
  document.getElementById("customer-search").value = "";
  document.getElementById("customer-status-filter").value = "";
  document.getElementById("customer-page").value = "1";
  loadCustomers();
}

// ===== SERVICES MANAGEMENT =====
async function loadServices() {
  console.log("=== LOADING SERVICES ===");

  const servicesSection = document.getElementById("services-section");
  if (!servicesSection || !servicesSection.classList.contains("active")) {
    console.log("Services section is not active, skipping load");
    return;
  }

  // Tìm hoặc tạo container
  let container = document.getElementById("services-container");
  if (!container) {
    createServicesContainer();
    container = document.getElementById("services-container");
    if (!container) {
      console.error("❌ Cannot create services container");
      return;
    }
  }

  console.log("✅ Found services container");

  // Hiển thị loading
  container.innerHTML = `
    <div class="loading" style="text-align: center; padding: 40px;">
      <i class="fas fa-spinner fa-spin fa-2x" style="color: #3498db;"></i>
      <p style="margin-top: 15px; color: #666;">Đang tải danh sách dịch vụ...</p>
    </div>
  `;

  try {
    const response = await fetch(
      `${API_BASE_URL}/ServiceController.php?action=getAll&limit=50`,
      {
        method: "GET",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        credentials: "include",
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log("✅ API response:", data);

    let servicesArray = [];

    if (data.success) {
      if (data.data?.data && Array.isArray(data.data.data)) {
        servicesArray = data.data.data;
      } else if (data.data && Array.isArray(data.data)) {
        servicesArray = data.data;
      } else if (Array.isArray(data)) {
        servicesArray = data;
      }
    }

    if (servicesArray.length > 0) {
      renderServicesCards(servicesArray);
    } else {
      container.innerHTML = `
        <div class="no-data" style="text-align: center; padding: 40px;">
          <i class="fas fa-concierge-bell fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
          <p>${data.message || "Không có dịch vụ nào"}</p>
          <button class="btn btn-primary" onclick="openServiceModal()" style="margin-top: 15px;">
            <i class="fas fa-plus"></i> Thêm dịch vụ đầu tiên
          </button>
        </div>
      `;
    }
  } catch (error) {
    console.error("❌ Error loading services:", error);
    container.innerHTML = `
      <div class="no-data error" style="text-align: center; padding: 40px;">
        <i class="fas fa-exclamation-triangle fa-3x" style="color: #e74c3c; margin-bottom: 15px;"></i>
        <p>Lỗi tải dữ liệu</p>
        <p style="font-size: 0.9em; color: #999;">${error.message}</p>
        <button class="btn btn-secondary" onclick="loadServices()" style="margin-top: 15px;">
          <i class="fas fa-redo"></i> Thử lại
        </button>
        <button class="btn btn-info" onclick="testServiceApi()" style="margin-top: 15px; margin-left: 10px;">
          <i class="fas fa-plug"></i> Test API
        </button>
      </div>
    `;
  }
}

function createServicesContainer() {
  const servicesSection = document.getElementById("services-section");
  if (!servicesSection) return;

  servicesSection.innerHTML = `
    <div class="section-header">
      <h2>Quản lý Dịch vụ</h2>
      <button class="btn btn-primary" id="add-service-btn">
        <i class="fas fa-plus"></i> Thêm dịch vụ
      </button>
    </div>
    
    <div class="filters" style="margin-bottom: 20px;">
      <div class="filter-group">
        <input type="text" id="service-search" placeholder="Tìm theo tên dịch vụ..." />
      </div>
      <div class="filter-group">
        <select id="service-category-filter">
          <option value="">Tất cả danh mục</option>
          <option value="transport">Vận chuyển</option>
          <option value="food">Ẩm thực</option>
          <option value="spa">Spa & Massage</option>
          <option value="other">Khác</option>
        </select>
      </div>
      <div class="filter-group">
        <select id="service-status-filter">
          <option value="">Tất cả trạng thái</option>
          <option value="available">Có sẵn</option>
          <option value="unavailable">Ngừng cung cấp</option>
        </select>
      </div>
      <button class="btn btn-secondary" id="apply-service-filters">
        Lọc
      </button>
      <button class="btn btn-outline" id="reset-service-filters">
        Xóa lọc
      </button>
    </div>
    
    <div class="cards-container">
      <div id="services-container">
        <div class="loading">Đang tải dịch vụ...</div>
      </div>
    </div>
    
    <div class="pagination" id="services-pagination">
      <!-- Pagination will be loaded here -->
    </div>
  `;

  // Thêm lại event listeners
  const addServiceBtn = document.getElementById("add-service-btn");
  if (addServiceBtn) {
    addServiceBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openServiceModal();
    });
  }

  // Apply filters button
  const applyServiceFiltersBtn = document.getElementById(
    "apply-service-filters"
  );
  if (applyServiceFiltersBtn) {
    applyServiceFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadServices();
    });
  }

  // Reset filters button
  const resetServiceFiltersBtn = document.getElementById(
    "reset-service-filters"
  );
  if (resetServiceFiltersBtn) {
    resetServiceFiltersBtn.addEventListener("click", function (e) {
      e.preventDefault();
      resetServiceFilters();
    });
  }
}

function renderServicesCards(services) {
  const container = document.getElementById("services-container");
  if (!container) return;

  console.log("✅ Rendering services cards...");

  let html = '<div class="services-grid">';

  services.forEach((service) => {
    // Get category and status info
    const categoryInfo = getServiceCategoryInfo(service.category);
    const statusInfo = getServiceStatusInfo(service.status);
    const price = formatCurrency(service.price || 0);
    const createdDate = formatDate(service.created_at);
    const unit = service.unit || "lần";

    html += `
      <div class="service-card">
        <div class="service-header ${categoryInfo.class}">
          <div class="service-title">
            <h3>${service.service_name || service.name || "Chưa có tên"}</h3>
            <span class="service-category">${categoryInfo.text}</span>
          </div>
          <div class="service-price">${price}/${unit}</div>
        </div>
        
        <div class="service-body">
          <p class="service-description">${
            service.description || "Không có mô tả"
          }</p>
          
          <div class="service-details">
            <div class="detail-item">
              <span class="detail-label">Trạng thái:</span>
              <span class="status-badge ${statusInfo.class}">${
      statusInfo.text
    }</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Ngày tạo:</span>
              <span class="detail-value">${createdDate}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Đơn vị:</span>
              <span class="detail-value">${unit}</span>
            </div>
          </div>
        </div>
        
        <div class="service-footer">
          <button class="btn btn-sm btn-info" onclick="viewServiceDetails(${
            service.id
          })" title="Xem chi tiết">
            <i class="fas fa-eye"></i>
          </button>
          <button class="btn btn-sm btn-primary" onclick="editService(${
            service.id
          })" title="Chỉnh sửa">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteService(${
            service.id
          })" title="Xóa">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    `;
  });

  html += "</div>";
  container.innerHTML = html;

  // Add CSS for the grid
  addServicesStyles();

  console.log("✅ Services cards rendered successfully");
}

function getServiceCategoryInfo(category) {
  const categoryMap = {
    transport: { text: "Vận chuyển", class: "category-transport" },
    food: { text: "Ẩm thực", class: "category-food" },
    spa: { text: "Spa & Massage", class: "category-spa" },
    other: { text: "Khác", class: "category-other" },
  };
  return (
    categoryMap[category] || {
      text: category || "Khác",
      class: "category-other",
    }
  );
}

function getServiceStatusInfo(status) {
  const statusMap = {
    available: { text: "Có sẵn", class: "status-available" },
    unavailable: { text: "Ngừng cung cấp", class: "status-inactive" },
    active: { text: "Hoạt động", class: "status-available" },
    inactive: { text: "Ngừng hoạt động", class: "status-inactive" },
  };
  return (
    statusMap[status] || {
      text: status || "Không xác định",
      class: "status-inactive",
    }
  );
}

function addServicesStyles() {
  // Add dynamic styles if not already present
  if (!document.getElementById("services-styles")) {
    const style = document.createElement("style");
    style.id = "services-styles";
    style.textContent = `
      .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
      }
      
      .service-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      
      .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
      }
      
      .service-header {
        padding: 20px;
        color: white;
        position: relative;
      }
      
      .service-header.category-transport { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      }
      .service-header.category-food { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
      }
      .service-header.category-spa { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
      }
      .service-header.category-other { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
      }
      
      .service-title {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
      }
      
      .service-title h3 {
        margin: 0;
        font-size: 1.2rem;
        flex: 1;
        font-weight: 600;
      }
      
      .service-category {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-left: 10px;
      }
      
      .service-price {
        font-size: 1.3rem;
        font-weight: bold;
        text-align: right;
        margin-top: 5px;
      }
      
      .service-body {
        padding: 20px;
      }
      
      .service-description {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.5;
        min-height: 60px;
        font-size: 0.95rem;
      }
      
      .service-details {
        border-top: 1px solid #eee;
        padding-top: 15px;
      }
      
      .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.9rem;
      }
      
      .detail-label {
        color: #777;
      }
      
      .detail-value {
        color: #333;
        font-weight: 500;
      }
      
      .service-footer {
        padding: 15px 20px;
        background: #f9f9f9;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
      }
      
      .service-footer .btn-sm {
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 5px;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
      }
      
      .service-footer .btn-sm:hover {
        transform: translateY(-2px);
      }
      
      .service-footer .btn-info {
        background: #17a2b8;
        color: white;
      }
      
      .service-footer .btn-primary {
        background: #007bff;
        color: white;
      }
      
      .service-footer .btn-danger {
        background: #dc3545;
        color: white;
      }
    `;
    document.head.appendChild(style);
  }
}

// ===== SERVICE MODAL FUNCTIONS =====
function openServiceModal(serviceId = null) {
  console.log("Open service modal for:", serviceId);

  // Tạo modal nếu chưa có
  if (!document.getElementById("service-modal")) {
    createServiceModal();
  }

  const modal = document.getElementById("service-modal");
  const title = document.getElementById("service-modal-title");

  if (serviceId) {
    // Edit mode
    title.textContent = "Chỉnh sửa Dịch vụ";
    loadServiceData(serviceId);
  } else {
    // Add mode
    title.textContent = "Thêm Dịch vụ Mới";
    resetServiceForm();
  }

  modal.style.display = "block";
}

function createServiceModal() {
  const modalHTML = `
    <div class="modal" id="service-modal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="service-modal-title">Thêm Dịch vụ Mới</h3>
          <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
          <form id="service-form">
            <input type="hidden" id="service-id" />
            
            <div class="form-group">
              <label for="service-name">Tên dịch vụ *</label>
              <input type="text" id="service-name" required 
                     placeholder="Ví dụ: Đưa đón sân bay, Massage body, Buffet sáng..." />
            </div>
            
            <div class="form-group">
              <label for="service-description">Mô tả dịch vụ</label>
              <textarea id="service-description" rows="3" 
                        placeholder="Mô tả chi tiết về dịch vụ..."></textarea>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="service-price">Giá (VNĐ) *</label>
                <input type="number" id="service-price" min="0" required 
                       placeholder="0" />
              </div>
              <div class="form-group">
                <label for="service-unit">Đơn vị tính</label>
                <input type="text" id="service-unit" 
                       placeholder="Ví dụ: lần, người, giờ, bữa..." />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="service-category">Danh mục *</label>
                <select id="service-category" required>
                  <option value="">Chọn danh mục</option>
                  <option value="transport">Vận chuyển</option>
                  <option value="food">Ẩm thực</option>
                  <option value="spa">Spa & Massage</option>
                  <option value="entertainment">Giải trí</option>
                  <option value="business">Dịch vụ kinh doanh</option>
                  <option value="other">Khác</option>
                </select>
              </div>
              <div class="form-group">
                <label for="service-status">Trạng thái</label>
                <select id="service-status">
                  <option value="available">Có sẵn</option>
                  <option value="unavailable">Ngừng cung cấp</option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="service-image">URL hình ảnh (tùy chọn)</label>
              <input type="text" id="service-image" 
                     placeholder="https://example.com/service-image.jpg" />
              <small class="form-text">Để trống nếu không có hình ảnh</small>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary close-modal">Hủy</button>
              <button type="submit" class="btn btn-primary">Lưu Dịch vụ</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add event listeners
  const modal = document.getElementById("service-modal");
  const closeBtn = modal.querySelector(".close-modal");
  const form = document.getElementById("service-form");

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  form.addEventListener("submit", handleServiceFormSubmit);

  // Thêm CSS cho modal nếu cần
  addServiceModalStyles();
}

function addServiceModalStyles() {
  if (!document.getElementById("service-modal-styles")) {
    const style = document.createElement("style");
    style.id = "service-modal-styles";
    style.textContent = `
      #service-modal .modal-content {
        max-width: 600px;
      }
      
      #service-form .form-text {
        color: #6c757d;
        font-size: 0.85rem;
        margin-top: 5px;
        display: block;
      }
      
      #service-form input[type="number"] {
        padding: 10px;
        font-size: 1rem;
      }
      
      #service-form textarea {
        resize: vertical;
        min-height: 80px;
      }
    `;
    document.head.appendChild(style);
  }
}

async function handleServiceFormSubmit(e) {
  e.preventDefault();

  // Show loading
  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
  submitBtn.disabled = true;

  const id = document.getElementById("service-id").value;
  const data = {
    service_name: document.getElementById("service-name").value,
    description: document.getElementById("service-description").value || null,
    price: document.getElementById("service-price").value,
    category: document.getElementById("service-category").value,
    unit: document.getElementById("service-unit").value || null,
    status: document.getElementById("service-status").value,
    image_url: document.getElementById("service-image").value || null,
  };

  try {
    await saveService(id, data);
  } catch (error) {
    console.error("Error in form submission:", error);
  } finally {
    // Restore button
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

async function saveService(id, data) {
  try {
    const url = `${API_BASE_URL}/ServiceController.php?action=${
      id ? "update&id=" + id : "create"
    }`;

    console.log("Saving service to:", url);
    console.log("Data:", data);

    const response = await fetch(url, {
      method: id ? "PUT" : "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    console.log("Save response:", result);

    if (result.success) {
      showToast(
        id ? "✅ Cập nhật dịch vụ thành công!" : "✅ Tạo dịch vụ thành công!",
        "success"
      );
      document.getElementById("service-modal").style.display = "none";
      loadServices(); // Reload the list
    } else {
      showToast(result.message || "❌ Có lỗi xảy ra", "error");
    }
  } catch (error) {
    console.error("Error saving service:", error);
    showToast("❌ Lỗi kết nối server", "error");
  }
}

async function loadServiceData(serviceId) {
  try {
    console.log("Loading service data for ID:", serviceId);

    const response = await fetch(
      `${API_BASE_URL}/ServiceController.php?action=getById&id=${serviceId}`
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    console.log("Service data response:", data);

    if (data.success && data.data) {
      const service = data.data;

      document.getElementById("service-id").value = service.id;
      document.getElementById("service-name").value =
        service.service_name || service.name || "";
      document.getElementById("service-description").value =
        service.description || "";
      document.getElementById("service-price").value = service.price || 0;
      document.getElementById("service-unit").value = service.unit || "";
      document.getElementById("service-category").value =
        service.category || "other";
      document.getElementById("service-status").value =
        service.status || "available";
      document.getElementById("service-image").value = service.image_url || "";
    } else {
      showToast("Không tìm thấy thông tin dịch vụ", "error");
    }
  } catch (error) {
    console.error("Error loading service data:", error);
    showToast("Không thể tải dữ liệu", "error");
  }
}

function resetServiceForm() {
  const form = document.getElementById("service-form");
  if (form) {
    form.reset();
    document.getElementById("service-id").value = "";
    document.getElementById("service-status").value = "available";
    document.getElementById("service-category").value = "";
    document.getElementById("service-unit").value = "lần";
  }
}

// ===== SERVICE ACTION FUNCTIONS =====
function viewServiceDetails(id) {
  console.log("View service details:", id);
  // Mở modal xem chi tiết hoặc chuyển hướng
  openServiceModal(id);
}

function editService(id) {
  console.log("Edit service:", id);
  openServiceModal(id);
}

async function deleteService(id) {
  if (
    !confirm(
      "Bạn có chắc chắn muốn xóa dịch vụ này?\nThao tác này không thể hoàn tác."
    )
  ) {
    return;
  }

  try {
    const response = await fetch(
      `${API_BASE_URL}/ServiceController.php?action=delete&id=${id}`,
      {
        method: "DELETE",
        headers: {
          Accept: "application/json",
        },
      }
    );

    const data = await response.json();

    if (data.success) {
      showToast("✅ Xóa dịch vụ thành công", "success");
      loadServices(); // Reload the list
    } else {
      showToast(data.message || "❌ Không thể xóa dịch vụ", "error");
    }
  } catch (error) {
    console.error("Error deleting service:", error);
    showToast("❌ Lỗi kết nối server", "error");
  }
}

// ===== SERVICE FILTER FUNCTIONS =====
function resetServiceFilters() {
  document.getElementById("service-search").value = "";
  document.getElementById("service-category-filter").value = "";
  document.getElementById("service-status-filter").value = "";
  loadServices();
}

// ===== TEST FUNCTION =====
async function testServiceApi() {
  try {
    console.log("Testing Service API connection...");

    const testUrls = [
      `${API_BASE_URL}/ServiceController.php?action=getAll&limit=5`,
      `${API_BASE_URL}/ServiceController.php?action=getAll`,
    ];

    let result = "";

    for (const url of testUrls) {
      console.log("Testing URL:", url);
      const response = await fetch(url);
      const data = await response.json();

      result += `URL: ${url}\n`;
      result += `Status: ${response.status}\n`;
      result += `Success: ${data.success}\n`;
      result += `Message: ${data.message || "N/A"}\n`;
      result += `Data count: ${
        data.data
          ? Array.isArray(data.data)
            ? data.data.length
            : "Not array"
          : "No data"
      }\n`;
      result += `---\n`;
    }

    alert("Kết quả test API:\n\n" + result);
    console.log("Test result:", result);
  } catch (error) {
    alert(`❌ Lỗi test API: ${error.message}`);
    console.error("Test error:", error);
  }
}

// ===== UTILITY FUNCTIONS (thêm nếu chưa có) =====
function formatDate(dateString) {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("vi-VN", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    });
  } catch (e) {
    return dateString;
  }
}

function showToast(message, type = "info") {
  // Remove existing toast
  const existingToast = document.getElementById("toast");
  if (existingToast) {
    existingToast.remove();
  }

  // Create new toast
  const toast = document.createElement("div");
  toast.id = "toast";
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <div class="toast-content">
      <i class="fas fa-${
        type === "success"
          ? "check-circle"
          : type === "error"
          ? "exclamation-circle"
          : "info-circle"
      }"></i>
      <span>${message}</span>
    </div>
  `;

  document.body.appendChild(toast);

  // Show toast
  setTimeout(() => {
    toast.classList.add("show");
  }, 10);

  // Hide after 3 seconds
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 300);
  }, 3000);
}

// Thêm CSS cho toast
if (!document.getElementById("toast-styles")) {
  const style = document.createElement("style");
  style.id = "toast-styles";
  style.textContent = `
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: white;
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      z-index: 9999;
      transform: translateX(100%);
      transition: transform 0.3s ease;
      min-width: 300px;
      max-width: 400px;
    }
    
    .toast.show {
      transform: translateX(0);
    }
    
    .toast-success {
      border-left: 4px solid #28a745;
    }
    
    .toast-error {
      border-left: 4px solid #dc3545;
    }
    
    .toast-info {
      border-left: 4px solid #17a2b8;
    }
    
    .toast-content {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .toast-content i {
      font-size: 1.2rem;
    }
    
    .toast-success .toast-content i {
      color: #28a745;
    }
    
    .toast-error .toast-content i {
      color: #dc3545;
    }
    
    .toast-info .toast-content i {
      color: #17a2b8;
    }
  `;
  document.head.appendChild(style);
}

// ===== CẬP NHẬT HÀM loadSectionData =====
// Thêm case 'services' vào hàm loadSectionData (nếu chưa có)
function loadSectionData(section) {
  console.log("Loading data for:", section);

  switch (section) {
    case "dashboard":
      loadDashboardData();
      break;
    case "rooms":
      loadRooms();
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
      loadServices(); // <-- ĐÃ THÊM VÀO
      break;
    case "staff":
      loadStaff();
      break;
    case "reports":
      loadReports();
      break;
    default:
      console.log("No data loader for:", section);
  }
}
