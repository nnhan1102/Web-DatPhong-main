// Opulent Travel - Main JavaScript File

// Global Variables
let currentUser = null;
let cartItems = [];
let wishlist = [];

// DOM Ready Function
document.addEventListener("DOMContentLoaded", function () {
  console.log("Opulent Travel - Premium Travel Website");

  // Initialize all components
  initComponents();

  // Load user data from localStorage
  loadUserData();

  // Setup event listeners
  setupEventListeners();

  // Update cart and wishlist counters
  updateCounters();
});

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
}

// Load user data from localStorage
function loadUserData() {
  try {
    const savedUser = localStorage.getItem("opulent_user");
    if (savedUser) {
      currentUser = JSON.parse(savedUser);
    }

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
    localStorage.removeItem("opulent_user");
    localStorage.removeItem("opulent_cart");
    localStorage.removeItem("opulent_wishlist");
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
  const closeButtons = document.querySelectorAll(".close-modal");
  closeButtons.forEach((button) => {
    button.addEventListener("click", closeModal);
  });

  // Login form
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  // Login button
  const loginBtn = document.getElementById("login-btn");
  if (loginBtn) {
    loginBtn.addEventListener("click", openLoginModal);
  }

  // Register link
  const showRegister = document.getElementById("showRegister");
  if (showRegister) {
    showRegister.addEventListener("click", openRegisterModal);
  }

  // CTA form
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
}

// Update cart and wishlist counters
function updateCounters() {
  // Update cart counter
  const cartCounter = document.getElementById("cartCounter");
  if (cartCounter) {
    cartCounter.textContent = cartItems.length;
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
  const destination = form.querySelector("#destination").value;
  const departureDate = form.querySelector("#departureDate").value;
  const returnDate = form.querySelector("#returnDate").value;
  const guests = form.querySelector(".guest-count").value;

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
  const returnDateGroup = document.querySelector(".form-group:nth-child(3)");

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
  let count = parseInt(guestCount.value);

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
  }
}

// Open register modal
function openRegisterModal(e) {
  if (e) e.preventDefault();

  closeModal();

  // For now, just show a notification
  showNotification("Tính năng đăng ký sẽ sớm có mặt!", "info");

  // In a real implementation, you would show a registration modal
  // setTimeout(() => {
  //     const registerModal = document.getElementById('registerModal');
  //     if (registerModal) {
  //         registerModal.classList.add('active');
  //     }
  // }, 300);
}

// Close modal
function closeModal() {
  const activeModal = document.querySelector(".modal.active");
  if (activeModal) {
    activeModal.classList.remove("active");
    document.body.style.overflow = "";
  }
}

// Login form handler
function handleLogin(e) {
  e.preventDefault();

  const form = e.target;
  const email = form.querySelector('input[type="email"]').value;
  const password = form.querySelector('input[type="password"]').value;
  const remember = form.querySelector('input[type="checkbox"]').checked;

  // Simple validation
  if (!email || !password) {
    showNotification("Vui lòng điền đầy đủ thông tin!", "error");
    return;
  }

  // Simulate API call
  showLoading();

  setTimeout(() => {
    // Mock successful login
    currentUser = {
      id: 1,
      name: "Demo User",
      email: email,
      avatar: "https://randomuser.me/api/portraits/men/32.jpg",
    };

    saveUserData();

    // Update UI
    updateUserUI();

    // Close modal
    closeModal();

    // Show success message
    showNotification("Đăng nhập thành công!", "success");

    hideLoading();
  }, 1500);
}

// Newsletter signup handler
function handleNewsletterSignup(e) {
  e.preventDefault();

  const form = e.target;
  const email = form.querySelector('input[type="email"]').value;

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

  // Simulate API call
  showLoading();

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
    form.reset();

    // Show success message
    showNotification("Đăng ký nhận tin thành công!", "success");

    hideLoading();
  }, 1000);
}

// Update UI based on user state
function updateUserUI() {
  const loginBtn = document.getElementById("login-btn");

  if (currentUser && loginBtn) {
    loginBtn.innerHTML = `
            <i class="fas fa-user-circle"></i>
            <span>${currentUser.name.split(" ")[0]}</span>
        `;
    loginBtn.href = "#";
    loginBtn.classList.remove("btn-outline");
    loginBtn.classList.add("btn-primary");

    // Remove old event listener
    const newLoginBtn = loginBtn.cloneNode(true);
    loginBtn.parentNode.replaceChild(newLoginBtn, loginBtn);

    // Add new event listener for user menu
    newLoginBtn.addEventListener("click", function (e) {
      e.preventDefault();
      toggleUserMenu();
    });
  }
}

// Toggle user menu (for logged in users)
function toggleUserMenu() {
  // Implementation for user dropdown menu
  showNotification("Menu người dùng sẽ sớm có mặt!", "info");
}

// Show notification
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification");
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

  notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;

  // Add to body
  document.body.appendChild(notification);

  // Trigger animation
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 300);
  }, 5000);
}

// Show loading state
function showLoading() {
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "loading-overlay";
  loadingOverlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Đang xử lý...</p>
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
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(5px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            
            .loading-spinner {
                text-align: center;
            }
            
            .spinner-border {
                width: 3rem;
                height: 3rem;
                border-width: 0.25em;
                border-color: var(--primary-color);
                border-right-color: transparent;
                animation: spinner-border 0.75s linear infinite;
                margin-bottom: 1rem;
            }
            
            @keyframes spinner-border {
                to { transform: rotate(360deg); }
            }
            
            .loading-spinner p {
                color: var(--dark-color);
                font-weight: 500;
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
      tooltip.style.position = "fixed";
      tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + "px";
      tooltip.style.left =
        rect.left + (rect.width - tooltip.offsetWidth) / 2 + "px";
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

  // Add tooltip styles
  if (!document.querySelector("#tooltip-styles")) {
    const style = document.createElement("style");
    style.id = "tooltip-styles";
    style.textContent = `
            .tooltip {
                position: fixed;
                background: var(--dark-color);
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: var(--border-radius);
                font-size: 0.875rem;
                z-index: 9999;
                pointer-events: none;
                transform: translateY(-10px);
                transition: all 0.2s ease;
                max-width: 200px;
                text-align: center;
                box-shadow: var(--shadow-md);
            }
            
            .tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                transform: translateX(-50%);
                border-width: 5px;
                border-style: solid;
                border-color: var(--dark-color) transparent transparent transparent;
            }
        `;
    document.head.appendChild(style);
  }
}

// Initialize counters
function initCounters() {
  // You can add counter animations here
  // Example: Animate numbers when they come into view
}

// Initialize animations
function initAnimations() {
  // Initialize Intersection Observer for scroll animations
  const observerOptions = {
    root: null,
    rootMargin: "0px",
    threshold: 0.1,
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("animate-in");
      }
    });
  }, observerOptions);

  // Observe elements with animation classes
  const animatedElements = document.querySelectorAll(".animate-on-scroll");
  animatedElements.forEach((element) => {
    observer.observe(element);
  });
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

    // Add real-time validation for inputs
    const inputs = form.querySelectorAll("input, textarea, select");
    inputs.forEach((input) => {
      input.addEventListener("blur", function () {
        validateInput(this);
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

  // Remove existing error state
  input.classList.remove("error");
  const existingError = input.parentElement.querySelector(".error-message");
  if (existingError) {
    existingError.remove();
  }

  // Check required fields
  if (input.hasAttribute("required") && !value) {
    isValid = false;
    errorMessage = "Trường này là bắt buộc";
  }

  // Check email format
  if (input.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      isValid = false;
      errorMessage = "Email không hợp lệ";
    }
  }

  // Check phone number format
  if (input.type === "tel" && value) {
    const phoneRegex = /^[0-9]{10,11}$/;
    if (!phoneRegex.test(value.replace(/\s+/g, ""))) {
      isValid = false;
      errorMessage = "Số điện thoại không hợp lệ";
    }
  }

  // Check minimum length
  if (input.hasAttribute("minlength") && value) {
    const minLength = parseInt(input.getAttribute("minlength"));
    if (value.length < minLength) {
      isValid = false;
      errorMessage = `Tối thiểu ${minLength} ký tự`;
    }
  }

  // Show error if invalid
  if (!isValid) {
    input.classList.add("error");
    const errorElement = document.createElement("div");
    errorElement.className = "error-message";
    errorElement.textContent = errorMessage;
    errorElement.style.color = "var(--danger-color)";
    errorElement.style.fontSize = "0.875rem";
    errorElement.style.marginTop = "0.25rem";
    input.parentElement.appendChild(errorElement);
  }

  return isValid;
}

// Initialize lazy loading for images
function initLazyLoading() {
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.add("loaded");
          observer.unobserve(img);
        }
      });
    });

    const lazyImages = document.querySelectorAll("img[data-src]");
    lazyImages.forEach((img) => imageObserver.observe(img));
  } else {
    // Fallback for browsers without IntersectionObserver
    const lazyImages = document.querySelectorAll("img[data-src]");
    lazyImages.forEach((img) => {
      img.src = img.dataset.src;
    });
  }
}

// Add item to cart
function addToCart(tourId, tourData) {
  const existingItem = cartItems.find((item) => item.id === tourId);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cartItems.push({
      id: tourId,
      ...tourData,
      quantity: 1,
      addedAt: new Date().toISOString(),
    });
  }

  saveUserData();
  updateCounters();
  showNotification("Đã thêm vào giỏ hàng!", "success");
}

// Remove item from cart
function removeFromCart(tourId) {
  cartItems = cartItems.filter((item) => item.id !== tourId);
  saveUserData();
  updateCounters();
  showNotification("Đã xóa khỏi giỏ hàng!", "info");
}

// Add item to wishlist
function addToWishlist(tourId, tourData) {
  if (!wishlist.find((item) => item.id === tourId)) {
    wishlist.push({
      id: tourId,
      ...tourData,
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
function removeFromWishlist(tourId) {
  wishlist = wishlist.filter((item) => item.id !== tourId);
  saveUserData();
  updateCounters();
  showNotification("Đã xóa khỏi yêu thích!", "info");
  return true;
}

// Check if item is in wishlist
function isInWishlist(tourId) {
  return wishlist.some((item) => item.id === tourId);
}

// Check if item is in cart
function isInCart(tourId) {
  return cartItems.some((item) => item.id === tourId);
}

// Format currency
function formatCurrency(amount, currency = "VND") {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: currency,
  }).format(amount);
}

// Format date
function formatDate(dateString, format = "vi-VN") {
  const date = new Date(dateString);
  return date.toLocaleDateString(format, {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
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
  currentUser,
  cartItems,
  wishlist,
};

// Make functions available globally
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
window.formatCurrency = formatCurrency;
window.formatDate = formatDate;
window.showNotification = showNotification;
