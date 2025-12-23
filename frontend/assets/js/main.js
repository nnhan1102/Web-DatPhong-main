// Opulent Travel - Main JavaScript File

// Global Variables
let currentUser = null;
let cartItems = [];
let wishlist = [];
const API_BASE_URL = "../../backend/api"; // Cập nhật đường dẫn API theo cấu trúc thư mục

// DOM Ready Function
document.addEventListener("DOMContentLoaded", async function () {
  console.log("Opulent Travel - Premium Travel Website");

  // Khởi tạo user từ localStorage hoặc API
  await initializeUser();

  // Initialize all components
  initComponents();

  // Setup event listeners
  setupEventListeners();

  // Update cart and wishlist counters
  updateCounters();

  // Cập nhật UI người dùng
  updateUserUI();

  // Bảo vệ trang booking
  protectBookingPages();

  // Khởi tạo date pickers
  initDatePickers();
});

// Khởi tạo user từ localStorage hoặc API
async function initializeUser() {
  // Thử lấy từ localStorage trước
  const savedUser = localStorage.getItem("opulent_user");
  if (savedUser) {
    try {
      currentUser = JSON.parse(savedUser);

      // Verify session với server nếu cần
      if (currentUser && currentUser.isLoggedIn) {
        await verifySession();
      }
    } catch (error) {
      console.error("Error parsing user data:", error);
      localStorage.removeItem("opulent_user");
      currentUser = null;
    }
  }

  // Load cart và wishlist
  loadUserData();
}

// Kiểm tra session với server
async function verifySession() {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/me`, {
      method: "GET",
      credentials: "include",
    });

    if (response.ok) {
      const result = await response.json();
      if (result.success) {
        // Cập nhật thông tin user
        currentUser = {
          ...result.data.user,
          isLoggedIn: true,
        };
        localStorage.setItem("opulent_user", JSON.stringify(currentUser));
        return true;
      } else {
        // Session không hợp lệ, xóa localStorage
        localStorage.removeItem("opulent_user");
        currentUser = null;
        return false;
      }
    }
  } catch (error) {
    console.log("Không thể verify session, sử dụng local data:", error);
    return false; // Trả về false nhưng không xóa local data
  }
}

// Initialize all UI components
function initComponents() {
  // Initialize tooltips
  initTooltips();

  // Initialize counters
  initCounters();

  // Initialize animations
  initAnimations();

  // Initialize form validation
  initFormValidation();

  // Initialize lazy loading
  initLazyLoading();

  // Initialize modals
  initModals();
}

// Load user data from localStorage
function loadUserData() {
  try {
    const savedCart = localStorage.getItem("opulent_cart");
    if (savedCart) {
      cartItems = JSON.parse(savedCart);
    }

    const savedWishlist = localStorage.getItem("opulent_wishlist");
    if (savedWishlist) {
      wishlist = JSON.parse(savedWishlist);
    }
  } catch (error) {
    console.error("Error loading user data:", error);
    // Reset corrupted data
    localStorage.removeItem("opulent_cart");
    localStorage.removeItem("opulent_wishlist");
    cartItems = [];
    wishlist = [];
  }
}

// Save user data to localStorage
function saveUserData() {
  try {
    if (currentUser) {
      localStorage.setItem("opulent_user", JSON.stringify(currentUser));
    }

    localStorage.setItem("opulent_cart", JSON.stringify(cartItems));
    localStorage.setItem("opulent_wishlist", JSON.stringify(wishlist));
  } catch (error) {
    console.error("Error saving user data:", error);
  }
}

// Setup all event listeners
function setupEventListeners() {
  // Search functionality
  const searchForm = document.getElementById("searchForm");
  if (searchForm) {
    searchForm.addEventListener("submit", handleSearch);
  }

  // Search tabs
  const searchTabs = document.querySelectorAll(".search-tab");
  searchTabs.forEach((tab) => {
    tab.addEventListener("click", handleSearchTabClick);
  });

  // Guest selector
  const guestButtons = document.querySelectorAll(".guest-btn");
  guestButtons.forEach((button) => {
    button.addEventListener("click", handleGuestButtonClick);
  });

  // Mobile menu toggle
  const mobileToggle = document.querySelector(".mobile-toggle");
  if (mobileToggle) {
    mobileToggle.addEventListener("click", toggleMobileMenu);
  }

  // Dropdown menus on mobile
  const dropdownLinks = document.querySelectorAll(".dropdown > .nav-link");
  dropdownLinks.forEach((link) => {
    link.addEventListener("click", handleDropdownClick);
  });

  // Back to top button
  const backToTop = document.getElementById("backToTop");
  if (backToTop) {
    window.addEventListener("scroll", handleScroll);
    backToTop.addEventListener("click", scrollToTop);
  }

  // Modal close buttons
  const closeButtons = document.querySelectorAll(
    ".close-modal, .btn-close-modal"
  );
  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  // Login form trong modal (nếu có)
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    // Xóa event listener cũ nếu có
    loginForm.removeEventListener("submit", handleModalLogin);
    // Thêm event listener mới
    loginForm.addEventListener("submit", handleModalLogin);
  }

  // Login button trong header
  const loginBtn = document.getElementById("login-btn");
  if (loginBtn && !currentUser) {
    loginBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openLoginModal();
    });
  }

  // Register link trong modal login
  const showRegister = document.getElementById("showRegister");
  if (showRegister) {
    showRegister.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = "login/register.html";
    });
  }

  // CTA form cho newsletter
  const ctaForm = document.querySelector(".cta-form");
  if (ctaForm) {
    ctaForm.addEventListener("submit", handleNewsletterSignup);
  }

  // Close modal on outside click
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  });

  // Close modal on Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal();
    }
  });

  // Thêm event listener cho nút đặt phòng
  const bookingButtons = document.querySelectorAll(
    ".book-now-btn, .btn-booking, .booking-btn, .btn-book-now"
  );
  bookingButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!currentUser) {
        e.preventDefault();
        e.stopPropagation();

        // Lưu URL đặt phòng
        const href = this.href || this.getAttribute("data-href");
        if (href) {
          localStorage.setItem("redirect_after_login", href);
        } else if (this.closest("[data-room-id]")) {
          const roomId =
            this.closest("[data-room-id]").getAttribute("data-room-id");
          localStorage.setItem(
            "redirect_after_login",
            `booking.html?room_id=${roomId}`
          );
        } else if (this.closest("[data-tour-id]")) {
          const tourId =
            this.closest("[data-tour-id]").getAttribute("data-tour-id");
          localStorage.setItem(
            "redirect_after_login",
            `booking.html?tour_id=${tourId}`
          );
        }

        openLoginModal();
        showNotification("Vui lòng đăng nhập để đặt phòng!", "warning");
      }
    });
  });

  // Wishlist buttons
  const wishlistButtons = document.querySelectorAll(".wishlist-btn");
  wishlistButtons.forEach((button) => {
    button.addEventListener("click", handleWishlistToggle);
  });

  // Cart buttons
  const cartButtons = document.querySelectorAll(".add-to-cart-btn");
  cartButtons.forEach((button) => {
    button.addEventListener("click", handleAddToCart);
  });

  // Filter toggles
  const filterToggles = document.querySelectorAll(".filter-toggle");
  filterToggles.forEach((toggle) => {
    toggle.addEventListener("click", toggleFilters);
  });

  // Sort dropdown
  const sortDropdowns = document.querySelectorAll(".sort-dropdown");
  sortDropdowns.forEach((dropdown) => {
    dropdown.addEventListener("change", handleSortChange);
  });
}

// Update cart and wishlist counters
function updateCounters() {
  // Update cart counter
  const cartCounter = document.getElementById("cartCounter");
  if (cartCounter) {
    cartCounter.textContent = cartItems.reduce(
      (total, item) => total + item.quantity,
      0
    );
    cartCounter.style.display = cartItems.length > 0 ? "flex" : "none";
  }

  // Update wishlist counter
  const wishlistCounter = document.getElementById("wishlistCounter");
  if (wishlistCounter) {
    wishlistCounter.textContent = wishlist.length;
    wishlistCounter.style.display = wishlist.length > 0 ? "flex" : "none";
  }
}

// Search form handler
function handleSearch(e) {
  e.preventDefault();

  const form = e.target;
  const destination = form.querySelector("#destination")?.value || "";
  const departureDate = form.querySelector("#departureDate")?.value || "";
  const returnDate = form.querySelector("#returnDate")?.value || "";
  const guests = form.querySelector(".guest-count")?.value || "1";

  // Build search parameters
  const params = new URLSearchParams();
  if (destination) params.append("destination", destination);
  if (departureDate) params.append("departure_date", departureDate);
  if (returnDate) params.append("return_date", returnDate);
  if (guests) params.append("guests", guests);

  // Get active tab
  const activeTab = document.querySelector(".search-tab.active");
  const tabType = activeTab ? activeTab.getAttribute("data-tab") : "tour";

  // Redirect to appropriate page
  let url = "";
  switch (tabType) {
    case "hotel":
      url = "hotels.html";
      break;
    case "flight":
      url = "flights.html";
      break;
    case "combo":
      url = "combos.html";
      break;
    case "tour":
    default:
      url = "tours.html";
      break;
  }

  if (params.toString()) {
    url += "?" + params.toString();
  }

  window.location.href = url;
}

// Search tab click handler
function handleSearchTabClick(e) {
  e.preventDefault();

  const tab = e.currentTarget;
  const tabs = document.querySelectorAll(".search-tab");

  // Remove active class from all tabs
  tabs.forEach((t) => t.classList.remove("active"));

  // Add active class to clicked tab
  tab.classList.add("active");

  // Update form based on tab type
  updateSearchForm(tab.getAttribute("data-tab"));
}

// Update search form based on tab
function updateSearchForm(tabType) {
  const destinationInput = document.getElementById("destination");
  const returnDateGroup = document.querySelector(".return-date-group");

  if (!destinationInput) return;

  switch (tabType) {
    case "hotel":
      destinationInput.placeholder = "Thành phố, khách sạn...";
      if (returnDateGroup) returnDateGroup.style.display = "none";
      break;
    case "flight":
      destinationInput.placeholder = "Điểm đến...";
      if (returnDateGroup) returnDateGroup.style.display = "flex";
      break;
    case "combo":
      destinationInput.placeholder = "Chọn combo...";
      if (returnDateGroup) returnDateGroup.style.display = "flex";
      break;
    case "tour":
    default:
      destinationInput.placeholder = "Bạn muốn đi đâu?";
      if (returnDateGroup) returnDateGroup.style.display = "flex";
      break;
  }
}

// Guest button click handler
function handleGuestButtonClick(e) {
  e.preventDefault();

  const button = e.currentTarget;
  const guestCount = button.parentElement.querySelector(".guest-count");
  if (!guestCount) return;

  let count = parseInt(guestCount.value) || 1;

  if (button.classList.contains("minus")) {
    if (count > 1) {
      count--;
    }
  } else if (button.classList.contains("plus")) {
    if (count < 10) {
      count++;
    }
  }

  guestCount.value = count;

  // Trigger change event
  const event = new Event("change");
  guestCount.dispatchEvent(event);
}

// Toggle mobile menu
function toggleMobileMenu() {
  const navMenu = document.querySelector(".nav-menu");
  const toggleButton = document.querySelector(".mobile-toggle");

  if (!navMenu || !toggleButton) return;

  navMenu.classList.toggle("active");

  // Update toggle button icon
  if (navMenu.classList.contains("active")) {
    toggleButton.innerHTML = '<i class="fas fa-times"></i>';
    document.body.style.overflow = "hidden";
  } else {
    toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.style.overflow = "";
  }
}

// Handle dropdown clicks on mobile
function handleDropdownClick(e) {
  if (window.innerWidth <= 992) {
    e.preventDefault();
    const dropdown = e.currentTarget.parentElement;
    dropdown.classList.toggle("active");
  }
}

// Scroll handler for back to top button
function handleScroll() {
  const backToTop = document.getElementById("backToTop");
  if (!backToTop) return;

  if (window.pageYOffset > 300) {
    backToTop.classList.add("visible");
  } else {
    backToTop.classList.remove("visible");
  }
}

// Scroll to top function
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}

// Open login modal
function openLoginModal(e) {
  if (e) e.preventDefault();

  const loginModal = document.getElementById("loginModal");
  if (loginModal) {
    loginModal.classList.add("active");
    document.body.style.overflow = "hidden";

    // Auto-focus on email input
    setTimeout(() => {
      const emailInput = loginModal.querySelector('input[type="email"]');
      if (emailInput) emailInput.focus();
    }, 100);
  } else {
    // Nếu không có modal, chuyển đến trang login
    window.location.href = "login/login.html";
  }
}

// Open register modal
function openRegisterModal(e) {
  if (e) e.preventDefault();

  closeModal();
  window.location.href = "login/register.html";
}

// Close modal
function closeModal() {
  const activeModal = document.querySelector(".modal.active");
  if (activeModal) {
    activeModal.classList.remove("active");
    document.body.style.overflow = "";
  }
}

// Hàm đăng nhập trong modal
async function handleModalLogin(e) {
  e.preventDefault();

  const form = e.target;
  const identifier =
    form.querySelector('input[type="email"], input[type="text"]')?.value || "";
  const password = form.querySelector('input[type="password"]')?.value || "";
  const remember =
    form.querySelector('input[type="checkbox"]')?.checked || false;

  // Validation
  if (!identifier || !password) {
    showNotification("Vui lòng điền đầy đủ thông tin!", "error");
    return;
  }

  showLoading();

  try {
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        identifier: identifier,
        password: password,
      }),
      credentials: "include",
    });

    const result = await response.json();

    if (result.success) {
      // Cập nhật currentUser
      currentUser = {
        ...result.data.user,
        isLoggedIn: true,
      };

      // Lưu vào localStorage
      localStorage.setItem("opulent_user", JSON.stringify(currentUser));

      // Lưu remember me
      if (remember) {
        localStorage.setItem("opulent_remember", "true");
      }

      // Update UI
      updateUserUI();

      // Close modal
      closeModal();

      // Show success message
      showNotification("Đăng nhập thành công!", "success");

      // Redirect nếu có lưu URL trước đó
      const redirectUrl = localStorage.getItem("redirect_after_login");
      if (redirectUrl) {
        localStorage.removeItem("redirect_after_login");
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 1000);
      } else {
        // Tự động reload sau 1 giây để cập nhật UI
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }
    } else {
      showNotification(result.message || "Đăng nhập thất bại!", "error");
    }
  } catch (error) {
    console.error("Login error:", error);
    showNotification("Lỗi kết nối máy chủ!", "error");
  } finally {
    hideLoading();
  }
}

// Hàm đăng xuất
async function logout() {
  if (confirm("Bạn có chắc chắn muốn đăng xuất?")) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/logout`, {
        method: "POST",
        credentials: "include",
      });

      // Luôn xóa dữ liệu local dù server có response hay không
      currentUser = null;
      localStorage.removeItem("opulent_user");
      localStorage.removeItem("opulent_remember");

      updateUserUI();
      showNotification("Đã đăng xuất thành công!", "success");

      // Nếu đang ở trang yêu cầu đăng nhập, chuyển về trang chủ
      const protectedPages = [
        "booking.html",
        "checkout.html",
        "payment.html",
        "profile.html",
        "my-bookings.html",
      ];
      const currentPage = window.location.pathname;
      const currentPageName = currentPage.split("/").pop();

      if (protectedPages.includes(currentPageName)) {
        setTimeout(() => {
          window.location.href = "index.html";
        }, 1000);
      }

      // Reload page để cập nhật UI
      setTimeout(() => {
        window.location.reload();
      }, 500);
    } catch (error) {
      console.log("Logout error:", error);
      // Vẫn xóa local storage dù server error
      currentUser = null;
      localStorage.removeItem("opulent_user");
      localStorage.removeItem("opulent_remember");
      updateUserUI();
      showNotification("Đã đăng xuất!", "info");
    }
  }
}

// Newsletter signup handler
function handleNewsletterSignup(e) {
  e.preventDefault();

  const form = e.target;
  const emailInput = form.querySelector('input[type="email"]');
  const email = emailInput?.value || "";

  if (!email) {
    showNotification("Vui lòng nhập email của bạn!", "error");
    return;
  }

  // Email validation regex
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showNotification("Email không hợp lệ!", "error");
    return;
  }

  showLoading();

  // Giả lập API call
  setTimeout(() => {
    // Save to localStorage
    let subscribers = JSON.parse(
      localStorage.getItem("opulent_subscribers") || "[]"
    );
    if (!subscribers.includes(email)) {
      subscribers.push(email);
      localStorage.setItem("opulent_subscribers", JSON.stringify(subscribers));
    }

    // Reset form
    if (form) form.reset();

    // Show success message
    showNotification("Đăng ký nhận tin thành công! Cảm ơn bạn.", "success");

    hideLoading();
  }, 1000);
}

// Cập nhật UI người dùng
function updateUserUI() {
  const loginBtn = document.getElementById("login-btn");
  const userMenu = document.getElementById("userMenu");

  if (currentUser && currentUser.isLoggedIn && loginBtn) {
    const displayName =
      currentUser.full_name?.split(" ")[0] ||
      currentUser.username?.split(" ")[0] ||
      currentUser.name?.split(" ")[0] ||
      "Người dùng";

    loginBtn.innerHTML = `
      <i class="fas fa-user-circle"></i>
      <span>${displayName}</span>
      <i class="fas fa-chevron-down"></i>
    `;

    loginBtn.href = "#";
    loginBtn.onclick = function (e) {
      e.preventDefault();
      const menu = document.getElementById("userMenu");
      if (menu) {
        menu.classList.toggle("show");
      }
    };

    // Tạo dropdown menu cho user nếu chưa có
    if (!userMenu) {
      const menu = document.createElement("div");
      menu.id = "userMenu";
      menu.className = "user-dropdown";
      menu.innerHTML = `
        <div class="user-info">
          <strong>${
            currentUser.full_name ||
            currentUser.username ||
            currentUser.name ||
            "User"
          }</strong>
          <small>${currentUser.email || ""}</small>
          <small>${
            currentUser.user_type === "admin" ? "Quản trị viên" : "Khách hàng"
          }</small>
        </div>
        <a href="profile.html" class="dropdown-item">
          <i class="fas fa-user"></i> Hồ sơ của tôi
        </a>
        <a href="my-bookings.html" class="dropdown-item">
          <i class="fas fa-calendar-alt"></i> Đơn đặt của tôi
        </a>
        <a href="wishlist.html" class="dropdown-item">
          <i class="fas fa-heart"></i> Yêu thích (${wishlist.length})
        </a>
        <hr>
        ${
          currentUser.user_type === "admin"
            ? `
        <a href="../admin/index.html" class="dropdown-item" target="_blank">
          <i class="fas fa-cog"></i> Quản trị
        </a>
        <hr>
        `
            : ""
        }
        <a href="javascript:void(0)" onclick="logout()" class="dropdown-item logout-btn">
          <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </a>
      `;

      loginBtn.parentNode.appendChild(menu);

      // Đóng menu khi click ra ngoài
      document.addEventListener("click", function (e) {
        if (!loginBtn.contains(e.target) && !menu.contains(e.target)) {
          menu.classList.remove("show");
        }
      });
    }
  } else if (loginBtn) {
    // Hiển thị nút đăng nhập
    loginBtn.innerHTML = `
      <i class="fas fa-sign-in-alt"></i>
      <span>Đăng nhập</span>
    `;
    loginBtn.href = "#";
    loginBtn.onclick = openLoginModal;

    // Xóa menu nếu có
    const existingMenu = document.getElementById("userMenu");
    if (existingMenu) {
      existingMenu.remove();
    }
  }
}

// Hàm bảo vệ các trang yêu cầu đăng nhập
function protectBookingPages() {
  const currentPage = window.location.pathname;
  const bookingPages = [
    "/booking.html",
    "/checkout.html",
    "/payment.html",
    "/profile.html",
    "/my-bookings.html",
  ];

  const currentPageName = currentPage.split("/").pop();

  if (bookingPages.includes(currentPageName) && !currentUser) {
    // Lưu URL hiện tại để quay lại sau khi login
    localStorage.setItem("redirect_after_login", window.location.href);

    // Hiển thị thông báo và chuyển hướng
    showNotification("Vui lòng đăng nhập để tiếp tục!", "warning");
    setTimeout(() => {
      window.location.href = "login/login.html";
    }, 1500);
    return false;
  }

  return true;
}

// Show notification
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(
    ".notification:not(.permanent)"
  );
  existingNotifications.forEach((notification) => {
    notification.remove();
  });

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;

  // Set icon based on type
  let icon = "info-circle";
  if (type === "success") icon = "check-circle";
  if (type === "error") icon = "exclamation-circle";
  if (type === "warning") icon = "exclamation-triangle";

  notification.innerHTML = `
    <i class="fas fa-${icon}"></i>
    <span>${message}</span>
    <button class="notification-close"><i class="fas fa-times"></i></button>
  `;

  // Add to body
  document.body.appendChild(notification);

  // Trigger animation
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Close button event
  const closeBtn = notification.querySelector(".notification-close");
  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      notification.classList.remove("show");
      setTimeout(() => {
        if (notification.parentNode) {
          notification.remove();
        }
      }, 300);
    });
  }

  // Auto remove after 5 seconds (trừ khi là error)
  if (type !== "error") {
    setTimeout(() => {
      if (notification.parentNode) {
        notification.classList.remove("show");
        setTimeout(() => {
          if (notification.parentNode) {
            notification.remove();
          }
        }, 300);
      }
    }, 5000);
  }
}

// Show loading state
function showLoading(loadingText = "Đang xử lý...") {
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "loading-overlay";
  loadingOverlay.innerHTML = `
    <div class="loading-spinner">
      <div class="spinner"></div>
      <p>${loadingText}</p>
    </div>
  `;

  document.body.appendChild(loadingOverlay);

  // Add styles if not already present
  if (!document.querySelector("#loading-styles")) {
    const style = document.createElement("style");
    style.id = "loading-styles";
    style.textContent = `
      .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
      }
      
      .loading-spinner {
        text-align: center;
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      }
      
      .loading-spinner .spinner {
        width: 50px;
        height: 50px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #007bff;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
      }
      
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      
      .loading-spinner p {
        color: #333;
        font-weight: 500;
        margin: 0;
      }
    `;
    document.head.appendChild(style);
  }
}

// Hide loading state
function hideLoading() {
  const loadingOverlay = document.querySelector(".loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.remove();
  }
}

// Initialize tooltips
function initTooltips() {
  // Implementation remains the same as your original
  const tooltipElements = document.querySelectorAll("[data-tooltip]");

  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", function (e) {
      const tooltipText = this.getAttribute("data-tooltip");
      if (!tooltipText) return;

      const tooltip = document.createElement("div");
      tooltip.className = "tooltip";
      tooltip.textContent = tooltipText;

      document.body.appendChild(tooltip);

      const rect = this.getBoundingClientRect();
      const scrollTop =
        window.pageYOffset || document.documentElement.scrollTop;
      const scrollLeft =
        window.pageXOffset || document.documentElement.scrollLeft;

      tooltip.style.position = "absolute";
      tooltip.style.top =
        rect.top + scrollTop - tooltip.offsetHeight - 10 + "px";
      tooltip.style.left =
        rect.left + scrollLeft + (rect.width - tooltip.offsetWidth) / 2 + "px";
      tooltip.style.opacity = "0";

      setTimeout(() => {
        tooltip.style.opacity = "1";
        tooltip.style.transform = "translateY(0)";
      }, 10);

      this._tooltip = tooltip;
    });

    element.addEventListener("mouseleave", function () {
      if (this._tooltip) {
        this._tooltip.remove();
        this._tooltip = null;
      }
    });
  });
}

// Initialize animations
function initAnimations() {
  if (!document.querySelector("#animation-styles")) {
    const style = document.createElement("style");
    style.id = "animation-styles";
    style.textContent = `
      .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.6s ease, transform 0.6s ease;
      }
      
      .animate-on-scroll.animate-in {
        opacity: 1;
        transform: translateY(0);
      }
      
      .fade-in {
        animation: fadeIn 0.5s ease;
      }
      
      .slide-up {
        animation: slideUp 0.5s ease;
      }
      
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      
      @keyframes slideUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    `;
    document.head.appendChild(style);
  }

  // Initialize Intersection Observer
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("animate-in");
          }
        });
      },
      {
        threshold: 0.1,
      }
    );

    document.querySelectorAll(".animate-on-scroll").forEach((el) => {
      observer.observe(el);
    });
  }
}

// Initialize form validation
function initFormValidation() {
  const forms = document.querySelectorAll("form[data-validate]");

  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault();
      }
    });

    // Real-time validation
    const inputs = form.querySelectorAll("input, textarea, select");
    inputs.forEach((input) => {
      input.addEventListener("blur", function () {
        validateInput(this);
      });

      input.addEventListener("input", function () {
        clearInputError(this);
      });
    });
  });
}

// Validate form
function validateForm(form) {
  let isValid = true;
  const inputs = form.querySelectorAll(
    "input[required], textarea[required], select[required]"
  );

  inputs.forEach((input) => {
    if (!validateInput(input)) {
      isValid = false;
    }
  });

  return isValid;
}

// Validate input field
function validateInput(input) {
  const value = input.value.trim();
  let isValid = true;
  let errorMessage = "";

  // Remove existing error
  clearInputError(input);

  // Check required
  if (input.hasAttribute("required") && !value) {
    isValid = false;
    errorMessage =
      input.getAttribute("data-error-required") || "Trường này là bắt buộc";
  }

  // Email validation
  if (input.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      isValid = false;
      errorMessage =
        input.getAttribute("data-error-email") || "Email không hợp lệ";
    }
  }

  // Phone validation
  if (input.type === "tel" && value) {
    const phoneRegex =
      /^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-9])[0-9]{7}$/;
    if (!phoneRegex.test(value.replace(/\s+/g, ""))) {
      isValid = false;
      errorMessage =
        input.getAttribute("data-error-phone") || "Số điện thoại không hợp lệ";
    }
  }

  // Min length
  if (input.hasAttribute("minlength") && value) {
    const minLength = parseInt(input.getAttribute("minlength"));
    if (value.length < minLength) {
      isValid = false;
      errorMessage =
        input.getAttribute("data-error-minlength") ||
        `Tối thiểu ${minLength} ký tự`;
    }
  }

  // Pattern validation
  if (input.hasAttribute("pattern") && value) {
    const pattern = new RegExp(input.getAttribute("pattern"));
    if (!pattern.test(value)) {
      isValid = false;
      errorMessage =
        input.getAttribute("data-error-pattern") || "Giá trị không hợp lệ";
    }
  }

  // Show error if invalid
  if (!isValid) {
    showInputError(input, errorMessage);
  }

  return isValid;
}

// Show input error
function showInputError(input, message) {
  input.classList.add("error");

  const errorElement = document.createElement("div");
  errorElement.className = "error-message";
  errorElement.textContent = message;
  errorElement.style.cssText = `
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
  `;

  // Insert after input
  input.parentNode.insertBefore(errorElement, input.nextSibling);
}

// Clear input error
function clearInputError(input) {
  input.classList.remove("error");

  const errorElement = input.parentNode.querySelector(".error-message");
  if (errorElement) {
    errorElement.remove();
  }
}

// Initialize lazy loading
function initLazyLoading() {
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          if (img.dataset.src) {
            img.src = img.dataset.src;
          }
          if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset;
          }
          img.classList.add("loaded");
          observer.unobserve(img);
        }
      });
    });

    const lazyImages = document.querySelectorAll(
      "img[data-src], img[data-srcset]"
    );
    lazyImages.forEach((img) => imageObserver.observe(img));
  }
}

// Initialize modals
function initModals() {
  // Add modal styles if not present
  if (!document.querySelector("#modal-styles")) {
    const style = document.createElement("style");
    style.id = "modal-styles";
    style.textContent = `
      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        align-items: center;
        justify-content: center;
      }
      
      .modal.active {
        display: flex;
      }
      
      .modal-content {
        background: white;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        animation: modalSlideUp 0.3s ease;
      }
      
      @keyframes modalSlideUp {
        from {
          opacity: 0;
          transform: translateY(50px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      .modal-title {
        margin: 0;
        font-size: 1.25rem;
        color: #333;
      }
      
      .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
      }
      
      .close-modal:hover {
        background: #f8f9fa;
        color: #333;
      }
      
      .modal-body {
        padding: 1.5rem;
      }
    `;
    document.head.appendChild(style);
  }
}

// Initialize date pickers
function initDatePickers() {
  const dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach((input) => {
    // Set min date to today
    const today = new Date().toISOString().split("T")[0];
    input.min = today;

    // Set default value for departure date (tomorrow)
    if (input.id === "departureDate" && !input.value) {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      input.value = tomorrow.toISOString().split("T")[0];
    }

    // Set default value for return date (3 days after departure)
    if (input.id === "returnDate" && !input.value) {
      const departureInput = document.getElementById("departureDate");
      if (departureInput && departureInput.value) {
        const returnDate = new Date(departureInput.value);
        returnDate.setDate(returnDate.getDate() + 3);
        input.value = returnDate.toISOString().split("T")[0];
      }
    }

    // Update return date min when departure date changes
    if (input.id === "departureDate") {
      input.addEventListener("change", function () {
        const returnInput = document.getElementById("returnDate");
        if (returnInput) {
          const departureDate = new Date(this.value);
          const minReturnDate = new Date(departureDate);
          minReturnDate.setDate(minReturnDate.getDate() + 1);
          returnInput.min = minReturnDate.toISOString().split("T")[0];

          // Auto-update return date if it's before new min date
          if (
            returnInput.value &&
            new Date(returnInput.value) < minReturnDate
          ) {
            const defaultReturn = new Date(departureDate);
            defaultReturn.setDate(defaultReturn.getDate() + 3);
            returnInput.value = defaultReturn.toISOString().split("T")[0];
          }
        }
      });
    }
  });
}

// Wishlist toggle handler
function handleWishlistToggle(e) {
  e.preventDefault();

  const button = e.currentTarget;
  const itemId =
    button.getAttribute("data-id") ||
    button.closest("[data-tour-id]")?.getAttribute("data-tour-id") ||
    button.closest("[data-room-id]")?.getAttribute("data-room-id");

  if (!itemId) return;

  // Get item data
  const itemElement = button.closest(".card, .room-card, .tour-item");
  const itemData = {
    id: itemId,
    title:
      itemElement?.querySelector(".card-title, .room-title, .tour-title")
        ?.textContent || "Item",
    price: itemElement?.querySelector(".price")?.textContent || "0",
    image: itemElement?.querySelector("img")?.src || "",
    type: button.closest("[data-tour-id]") ? "tour" : "room",
  };

  if (isInWishlist(itemId)) {
    removeFromWishlist(itemId);
    button.innerHTML = '<i class="far fa-heart"></i>';
    button.classList.remove("active");
  } else {
    addToWishlist(itemId, itemData);
    button.innerHTML = '<i class="fas fa-heart"></i>';
    button.classList.add("active");
  }
}

// Add to cart handler
function handleAddToCart(e) {
  e.preventDefault();

  const button = e.currentTarget;
  const itemId =
    button.getAttribute("data-id") ||
    button.closest("[data-tour-id]")?.getAttribute("data-tour-id") ||
    button.closest("[data-room-id]")?.getAttribute("data-room-id");

  if (!itemId) return;

  // Get item data
  const itemElement = button.closest(".card, .room-card, .tour-item");
  const itemData = {
    id: itemId,
    title:
      itemElement?.querySelector(".card-title, .room-title, .tour-title")
        ?.textContent || "Item",
    price: itemElement?.querySelector(".price")?.textContent || "0",
    image: itemElement?.querySelector("img")?.src || "",
    type: button.closest("[data-tour-id]") ? "tour" : "room",
  };

  addToCart(itemId, itemData);
}

// Add item to cart
function addToCart(itemId, itemData) {
  const existingItem = cartItems.find((item) => item.id === itemId);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cartItems.push({
      ...itemData,
      id: itemId,
      quantity: 1,
      addedAt: new Date().toISOString(),
    });
  }

  saveUserData();
  updateCounters();
  showNotification("Đã thêm vào giỏ hàng!", "success");

  // Update cart UI nếu có
  updateCartUI();
}

// Remove item from cart
function removeFromCart(itemId) {
  cartItems = cartItems.filter((item) => item.id !== itemId);
  saveUserData();
  updateCounters();
  showNotification("Đã xóa khỏi giỏ hàng!", "info");

  // Update cart UI nếu có
  updateCartUI();
}

// Add item to wishlist
function addToWishlist(itemId, itemData) {
  if (!wishlist.find((item) => item.id === itemId)) {
    wishlist.push({
      ...itemData,
      id: itemId,
      addedAt: new Date().toISOString(),
    });

    saveUserData();
    updateCounters();
    showNotification("Đã thêm vào yêu thích!", "success");
    return true;
  }
  return false;
}

// Remove item from wishlist
function removeFromWishlist(itemId) {
  wishlist = wishlist.filter((item) => item.id !== itemId);
  saveUserData();
  updateCounters();
  showNotification("Đã xóa khỏi yêu thích!", "info");
  return true;
}

// Check if item is in wishlist
function isInWishlist(itemId) {
  return wishlist.some((item) => item.id === itemId);
}

// Check if item is in cart
function isInCart(itemId) {
  return cartItems.some((item) => item.id === itemId);
}

// Update cart UI (nếu có trang cart)
function updateCartUI() {
  const cartPage = document.querySelector(".cart-page, #cartPage");
  if (cartPage) {
    // Reload cart items display
    // This should be implemented in cart-specific JS
    console.log("Cart updated, should refresh cart display");
  }
}

// Format currency
function formatCurrency(amount, currency = "VND") {
  if (!amount) return "0 ₫";

  // Convert string to number if needed
  const numAmount =
    typeof amount === "string"
      ? parseFloat(amount.replace(/[^0-9.-]+/g, ""))
      : Number(amount);

  if (isNaN(numAmount)) return "0 ₫";

  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(numAmount);
}

// Format date
function formatDate(dateString, format = "vi-VN") {
  if (!dateString) return "";

  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;

  return date.toLocaleDateString(format, {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Toggle filters
function toggleFilters() {
  const filters = document.querySelector(".filters");
  if (filters) {
    filters.classList.toggle("show");
  }
}

// Handle sort change
function handleSortChange(e) {
  const sortBy = e.target.value;
  // Implement sorting logic here
  console.log("Sort by:", sortBy);
}

// Get URL parameters
function getUrlParams() {
  const params = {};
  const queryString = window.location.search.slice(1);
  const pairs = queryString.split("&");

  pairs.forEach((pair) => {
    const [key, value] = pair.split("=");
    if (key) {
      params[decodeURIComponent(key)] = decodeURIComponent(value || "");
    }
  });

  return params;
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

// Throttle function for performance
function throttle(func, limit) {
  let inThrottle;
  return function () {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => (inThrottle = false), limit);
    }
  };
}

// Cleanup function - xóa các event listeners khi cần
function cleanup() {
  // Remove all custom tooltips
  document.querySelectorAll(".tooltip").forEach((tooltip) => tooltip.remove());

  // Remove loading overlay if exists
  hideLoading();

  // Remove notifications
  document
    .querySelectorAll(".notification")
    .forEach((notification) => notification.remove());
}

// Window unload cleanup
window.addEventListener("beforeunload", cleanup);

// Export functions for global use
window.Opulent = {
  addToCart,
  removeFromCart,
  addToWishlist,
  removeFromWishlist,
  isInWishlist,
  isInCart,
  formatCurrency,
  formatDate,
  showNotification,
  showLoading,
  hideLoading,
  currentUser: () => currentUser,
  cartItems: () => cartItems,
  wishlist: () => wishlist,
  logout,
  verifySession,
  getUrlParams,
  debounce,
  throttle,
};

// Make essential functions available globally
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showNotification = showNotification;
window.logout = logout;
window.handleModalLogin = handleModalLogin;

// Add user dropdown CSS
const userDropdownCSS = `
.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
  min-width: 280px;
  display: none;
  z-index: 1001;
  margin-top: 10px;
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.user-dropdown.show {
  display: block;
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.user-dropdown .user-info {
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.user-dropdown .user-info strong {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.user-dropdown .user-info small {
  display: block;
  font-size: 0.875rem;
  opacity: 0.9;
  margin-bottom: 0.125rem;
}

.user-dropdown .dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  color: #495057;
  text-decoration: none;
  transition: all 0.2s;
  border-bottom: 1px solid #f8f9fa;
  font-size: 0.9375rem;
}

.user-dropdown .dropdown-item:last-child {
  border-bottom: none;
}

.user-dropdown .dropdown-item:hover {
  background: #f8f9fa;
  color: #007bff;
  padding-left: 2rem;
}

.user-dropdown .dropdown-item i {
  width: 20px;
  margin-right: 10px;
  text-align: center;
  color: #6c757d;
}

.user-dropdown .dropdown-item:hover i {
  color: #007bff;
}

.user-dropdown .logout-btn {
  color: #dc3545;
}

.user-dropdown .logout-btn i {
  color: #dc3545;
}

.user-dropdown .logout-btn:hover {
  background: #fff5f5;
  color: #c82333;
}

.user-dropdown hr {
  margin: 0;
  border: none;
  border-top: 1px solid #e9ecef;
}

/* Notification styles */
.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  z-index: 9999;
  transform: translateX(150%);
  transition: transform 0.3s ease;
  border-left: 4px solid #007bff;
  min-width: 300px;
  max-width: 400px;
}

.notification.show {
  transform: translateX(0);
}

.notification.success {
  border-left-color: #28a745;
}

.notification.error {
  border-left-color: #dc3545;
}

.notification.warning {
  border-left-color: #ffc107;
}

.notification i {
  font-size: 1.25rem;
}

.notification.success i {
  color: #28a745;
}

.notification.error i {
  color: #dc3545;
}

.notification.warning i {
  color: #ffc107;
}

.notification span {
  flex: 1;
  font-size: 0.9375rem;
}

.notification-close {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background 0.2s;
}

.notification-close:hover {
  background: #f8f9fa;
}

/* Form error styles */
input.error, textarea.error, select.error {
  border-color: #dc3545 !important;
  background-color: #fff8f8;
}

input.error:focus, textarea.error:focus, select.error:focus {
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.error-message {
  color: #dc3545 !important;
  font-size: 0.875rem !important;
  margin-top: 0.25rem !important;
  display: block !important;
}
`;

// Add CSS to head
if (!document.querySelector("#user-dropdown-styles")) {
  const style = document.createElement("style");
  style.id = "user-dropdown-styles";
  style.textContent = userDropdownCSS;
  document.head.appendChild(style);
}

console.log("Opulent main.js loaded successfully!");
