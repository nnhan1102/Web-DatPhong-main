// Opulent Travel - API Client (Mock Version)

// const API_BASE_URL = "https://api.opulent.vn";
const API_BASE_URL = "../backend";  // Thay đổi này
const USE_MOCK_DATA = false; // Chuyển sang false khi muốn dùng API thật
const MOCK_FALLBACK = true; // Có dùng mock data khi API thật fail không

class ApiClient {
  constructor() {
    // Base URL - Điều chỉnh theo cấu trúc thư mục của bạn
    // this.baseUrl = window.location.origin + '/backend/api'; // hoặc 'http://localhost/opulent/backend'
     // Sử dụng đường dẫn tương đối
    this.baseUrl = API_BASE_URL;
    
    // Cấu hình
    this.config = {
      useMockData: true,          // Bật/tắt mock data
      mockFallback: true,          // Fallback về mock nếu API fail
      autoCheckConnection: true,   // Tự động kiểm tra kết nối
      requestTimeout: 10000,       // Timeout 10 giây
    };
      this.mockData = this.getMockData();
    this.isConnected = true;
    this.authToken = localStorage.getItem('auth_token') || null;
    
    // Tự động kiểm tra kết nối
    if (this.config.autoCheckConnection) {
      this.checkConnection();
    }
  }
   // ===== CONFIGURATION =====
  setConfig(config) {
    this.config = { ...this.config, ...config };
  }

  setAuthToken(token) {
    this.authToken = token;
    if (token) {
      localStorage.setItem('auth_token', token);
    } else {
      localStorage.removeItem('auth_token');
    }
  }


  // Phương thức mới để kiểm tra kết nối
  async checkConnection() {
    try {
      // Tạm thời luôn trả về true khi dùng mock data
        if (this.config.useMockData) {
            this.isConnected = true;
            return true;
        }
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 3000);
      
      const response = await fetch(`${this.baseUrl}/index.php`, {
        method: 'HEAD',
        signal: controller.signal,
        headers: this.getHeaders()
      });
      
      clearTimeout(timeoutId);
      this.isConnected = response.ok;
      return this.isConnected;
    } catch (error) {
      this.isConnected = false;
      console.warn('API connection check failed:', error.message);
      return false;
    }
  }

  // Phương thức gọi API chung
  async callApi(endpoint, options = {}) {
     const shouldUseMock = this.config.useMockData || !this.isConnected;
    
    // Nếu nên dùng mock và có fallback
    if (shouldUseMock && this.config.mockFallback) {
      console.log(`[API] Using mock data for: ${endpoint}`);
      return this.mockApiCall(endpoint, options);
    }
    
    try {
      const url = `${this.baseUrl}${endpoint}`;
      const defaultOptions = {
        headers: this.getHeaders(),
        timeout: this.config.requestTimeout,
        ...options
      };
      
      // Thêm timeout controller
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), this.config.requestTimeout);
      defaultOptions.signal = controller.signal;
      
      const response = await fetch(url, defaultOptions);
      clearTimeout(timeoutId);
      
      // Kiểm tra HTTP status
      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }
      
      // Parse JSON response
      const data = await response.json();
      
      // Kiểm tra response structure của backend PHP
      if (data.success === false) {
        throw new Error(data.message || 'API request failed');
      }
      
      return data;
      
    } catch (error) {
      console.warn('API connection check failed, using mock data:', error.message);
        this.isConnected = false;
        return false;
      }
      
  }
  getHeaders() {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };
    
    if (this.authToken) {
      headers['Authorization'] = `Bearer ${this.authToken}`;
    }
    
    return headers;
  }

  // ===== SPECIFIC API METHODS - GỌI BACKEND PHP =====
    // Dashboard
  async getDashboardData() {
    return this.callApi('/dashboard.php');
  }

  async getHotels(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/hotels.php${queryString ? '?' + queryString : ''}`);
}


  // Rooms
  async getRooms(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/rooms.php${queryString ? '?' + queryString : ''}`);
  }

  async getAvailableRooms(checkIn, checkOut) {
    return this.callApi('/rooms.php', {
      method: 'POST',
      body: JSON.stringify({ check_in: checkIn, check_out: checkOut })
    });
  }

  async getRoomById(id) {
    return this.callApi(`/rooms.php?id=${id}&action=get`);
  }

  // Room Types
  async getRoomTypes(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/room-types.php${queryString ? '?' + queryString : ''}`);
  }

  // Bookings
  async getBookings(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/bookings.php${queryString ? '?' + queryString : ''}`);
  }

  async createBooking(bookingData) {
    return this.callApi('/bookings.php?action=create', {
      method: 'POST',
      body: JSON.stringify(bookingData)
    });
  }

  async updateBooking(id, bookingData) {
    return this.callApi(`/bookings.php?id=${id}&action=update`, {
      method: 'POST',
      body: JSON.stringify(bookingData)
    });
  }

  // Customers
  async getCustomers(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/customers.php${queryString ? '?' + queryString : ''}`);
  }

  async createCustomer(customerData) {
    return this.callApi('/customers.php?action=create', {
      method: 'POST',
      body: JSON.stringify(customerData)
    });
  }

  // Services
  async getServices(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/services.php${queryString ? '?' + queryString : ''}`);
  }

  // Staff
  async getStaff(params = {}) {
    const queryString = new URLSearchParams(params).toString();
    return this.callApi(`/staff.php${queryString ? '?' + queryString : ''}`);
  }

  // Reports
  async getReports(period = 'month') {
    return this.callApi(`/reports.php?period=${period}`);
  }

  // Authentication (nếu backend có)
  async login(email, password) {
    try {
      // Gọi API login backend
      const response = await fetch(`${this.baseUrl}/auth.php`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ email, password })
      });
      
      const data = await response.json();
      
      if (data.success && data.token) {
        this.setAuthToken(data.token);
        return data;
      }
      
      throw new Error(data.message || 'Login failed');
      
    } catch (error) {
      console.error('Login error:', error);
      throw error;
    }
  }

  async logout() {
    this.setAuthToken(null);
    // Có thể gọi API logout nếu backend có
    // await this.callApi('/auth.php?action=logout');
  }
  // dưới này mock data toi chưa đụng, nhưng tour thì đụng vào rr  

// ===== MOCK METHODS FOR TOURS =====
  mockGetTours(params) {
    return new Promise((resolve) => {
      setTimeout(() => {
        let tours = [...this.mockData.tours];

        // Apply filters
        if (params.destination) {
          tours = tours.filter((tour) =>
            tour.destination
              .toLowerCase()
              .includes(params.destination.toLowerCase())
          );
        }

        if (params.featured) {
          tours = tours.slice(0, 6); // Return first 6 as featured
        }

        if (params.limit) {
          tours = tours.slice(0, parseInt(params.limit));
        }

        resolve({
          success: true,
          data: tours,
          count: tours.length,
          message: "Tours fetched successfully",
        });
      }, 500);
    });
  }

  mockGetTourById(id) {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const tour = this.mockData.tours.find((t) => t.id === parseInt(id));

        if (tour) {
          resolve({
            success: true,
            data: tour,
            message: "Tour fetched successfully",
          });
        } else {
          reject({
            success: false,
            message: "Tour not found",
          });
        }
      }, 300);
    });
  }

  mockCreateTour(tourData) {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newTour = {
          id: this.mockData.tours.length + 1,
          ...tourData,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
          status: "active",
          rating: 0,
          reviews_count: 0,
        };

        this.mockData.tours.push(newTour);

        resolve({
          success: true,
          data: newTour,
          message: "Tour created successfully",
        });
      }, 500);
    });
  }

  mockCreateTourBooking(bookingData) {
    return new Promise((resolve) => {
      setTimeout(() => {
        const bookingCode = "OPL-" + Date.now().toString().slice(-8);
        const tour = this.mockData.tours.find(
          (t) => t.id === bookingData.tour_id
        );

        if (!tour) {
          resolve({
            success: false,
            message: "Tour not found",
          });
          return;
        }

        const newBooking = {
          id: this.mockData.bookings.length + 1,
          booking_code: bookingCode,
          ...bookingData,
          tour: tour,
          total_price: this.calculateBookingPrice(tour, bookingData),
          payment_status: "pending",
          booking_status: "pending",
          created_at: new Date().toISOString(),
        };

        this.mockData.bookings.push(newBooking);

        resolve({
          success: true,
          data: newBooking,
          message: "Booking created successfully",
        });
      }, 800);
    });
  }

  mockGetTourBookings(params) {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve({
          success: true,
          data: this.mockData.bookings,
          count: this.mockData.bookings.length,
          message: "Bookings fetched successfully",
        });
      }, 300);
    });
  }

  mockCreateTourCustomer(customerData) {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newCustomer = {
          id: this.mockData.customers.length + 1,
          ...customerData,
          created_at: new Date().toISOString(),
        };

        this.mockData.customers.push(newCustomer);

        resolve({
          success: true,
          data: newCustomer,
          message: "Customer created successfully",
        });
      }, 500);
    });
  }


  getMockData() {
    return {
      tours: [
        {
          id: 1,
          name: "Tour Đà Lạt 4N3Đ: Thành Phố Ngàn Hoa",
          slug: "tour-da-lat-4n3d",
          description:
            "Khám phá thành phố mộng mơ với vườn hoa, đồi chè và không khí se lạnh. Tour bao gồm các điểm tham quan nổi tiếng như Hồ Xuân Hương, Thung lũng Tình Yêu, Dinh Bảo Đại, và nhiều điểm đến hấp dẫn khác.",
          short_description:
            "Khám phá thành phố mộng mơ với vườn hoa, đồi chè và không khí se lạnh",
          duration_days: 4,
          duration_nights: 3,
          departure_location: "TP.HCM",
          destination: "Đà Lạt",
          price_adult: 5990000,
          price_child: 4990000,
          price_infant: 2000000,
          discount_percent: 15,
          available_slots: 12,
          featured_image:
            "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3",
          gallery: [
            "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3",
            "https://images.unsplash.com/photo-1574007557239-acf6863bc375?ixlib=rb-4.0.3",
            "https://images.unsplash.com/photo-1513326738677-b964603b136d?ixlib=rb-4.0.3",
          ],
          itinerary: [
            {
              day: 1,
              title: "Khởi hành - Đến Đà Lạt",
              description:
                "Di chuyển từ TP.HCM đến Đà Lạt, nhận phòng khách sạn",
            },
            {
              day: 2,
              title: "Tham quan Đà Lạt",
              description: "Tham quan các điểm du lịch nổi tiếng",
            },
            {
              day: 3,
              title: "Khám phá văn hóa",
              description: "Tìm hiểu văn hóa và ẩm thực địa phương",
            },
            {
              day: 4,
              title: "Tự do - Khởi hành về",
              description: "Thời gian tự do, mua sắm và về lại TP.HCM",
            },
          ],
          included_services: [
            "Khách sạn 4 sao",
            "Xe đưa đón",
            "Hướng dẫn viên",
            "Bữa ăn theo chương trình",
          ],
          excluded_services: ["Chi phí cá nhân", "Bảo hiểm", "Thuế VAT"],
          status: "active",
          rating: 4.8,
          reviews_count: 124,
          created_at: "2024-01-15T08:00:00Z",
          updated_at: "2024-03-20T10:30:00Z",
        },
        {
          id: 2,
          name: "Tour Phú Quốc 5N4Đ: Thiên Đường Biển Đảo",
          slug: "tour-phu-quoc-5n4d",
          description:
            "Trải nghiệm thiên đường biển đảo với bãi cát trắng, nước biển trong xanh. Tour bao gồm tham quan các bãi biển đẹp nhất, làng chài, vườn tiêu, và các hoạt động thể thao dưới nước.",
          short_description:
            "Trải nghiệm thiên đường biển đảo với bãi cát trắng, nước biển trong xanh",
          duration_days: 5,
          duration_nights: 4,
          departure_location: "Hà Nội",
          destination: "Phú Quốc",
          price_adult: 8990000,
          price_child: 7490000,
          price_infant: 3000000,
          discount_percent: 20,
          available_slots: 8,
          featured_image:
            "https://images.unsplash.com/photo-1552465011-b4e30bf7349d?ixlib=rb-4.0.3",
          gallery: [
            "https://images.unsplash.com/photo-1552465011-b4e30bf7349d?ixlib=rb-4.0.3",
            "https://images.unsplash.com/photo-1513326738677-b964603b136d?ixlib=rb-4.0.3",
          ],
          itinerary: [
            {
              day: 1,
              title: "Khởi hành - Đến Phú Quốc",
              description: "Bay từ Hà Nội đến Phú Quốc, nhận phòng resort",
            },
            {
              day: 2,
              title: "Khám phá biển Bãi Sao",
              description: "Tham quan và tắm biển tại Bãi Sao",
            },
            {
              day: 3,
              title: "Tham quan đảo ngọc",
              description: "Tham quan các điểm du lịch nổi tiếng",
            },
            {
              day: 4,
              title: "Thể thao dưới nước",
              description: "Lặn biển, chèo thuyền kayak",
            },
            {
              day: 5,
              title: "Tự do - Khởi hành về",
              description: "Thời gian tự do, mua sắm và về lại Hà Nội",
            },
          ],
          included_services: [
            "Resort 5 sao",
            "Vé máy bay",
            "Xe đưa đón",
            "Bữa ăn",
            "Bảo hiểm du lịch",
          ],
          excluded_services: [
            "Chi phí cá nhân",
            "Thể thao dưới nước",
            "Thuế VAT",
          ],
          status: "active",
          rating: 4.9,
          reviews_count: 89,
          created_at: "2024-02-10T08:00:00Z",
          updated_at: "2024-03-25T14:20:00Z",
        },
        {
          id: 3,
          name: "Tour Bali 7N6Đ: Hòn Đảo Thần Thoại",
          slug: "tour-bali-7n6d",
          description:
            "Khám phá văn hóa độc đáo và cảnh quan tuyệt đẹp của hòn đảo thần thoại Bali. Tour bao gồm tham quan các đền thờ cổ, bãi biển tuyệt đẹp, và trải nghiệm văn hóa địa phương.",
          short_description:
            "Khám phá văn hóa độc đáo và cảnh quan tuyệt đẹp của Bali",
          duration_days: 7,
          duration_nights: 6,
          departure_location: "TP.HCM",
          destination: "Bali, Indonesia",
          price_adult: 25990000,
          price_child: 19990000,
          price_infant: 8000000,
          discount_percent: 10,
          available_slots: 6,
          featured_image:
            "https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?ixlib=rb-4.0.3",
          gallery: [
            "https://images.unsplash.com/photo-1537953773345-d172ccf13cf1?ixlib=rb-4.0.3",
            "https://images.unsplash.com/photo-1513326738677-b964603b136d?ixlib=rb-4.0.3",
          ],
          itinerary: [
            {
              day: 1,
              title: "Khởi hành - Đến Bali",
              description: "Bay từ TP.HCM đến Bali, nhận phòng khách sạn",
            },
            {
              day: 2,
              title: "Tham quan Ubud",
              description: "Khám phá trung tâm văn hóa của Bali",
            },
            {
              day: 3,
              title: "Đền thờ và biển",
              description: "Tham quan các đền thờ và bãi biển đẹp",
            },
            {
              day: 4,
              title: "Trải nghiệm văn hóa",
              description: "Học làm đồ thủ công và nấu ăn địa phương",
            },
            {
              day: 5,
              title: "Tham quan tự chọn",
              description: "Tự do khám phá hoặc tham gia tour tùy chọn",
            },
            {
              day: 6,
              title: "Nghỉ dưỡng",
              description: "Thư giãn tại resort và spa",
            },
            {
              day: 7,
              title: "Mua sắm - Khởi hành về",
              description: "Mua sắm quà lưu niệm và về lại TP.HCM",
            },
          ],
          included_services: [
            "Khách sạn 5 sao",
            "Vé máy bay",
            "Xe đưa đón",
            "Bữa ăn",
            "Bảo hiểm du lịch",
            "Visa",
          ],
          excluded_services: [
            "Chi phí cá nhân",
            "Tour tùy chọn",
            "Thuế sân bay",
          ],
          status: "active",
          rating: 4.7,
          reviews_count: 156,
          created_at: "2024-01-20T08:00:00Z",
          updated_at: "2024-03-28T09:15:00Z",
        },
        {
          id: 4,
          name: "Tour Hạ Long - Sapa 6N5Đ",
          slug: "tour-ha-long-sapa-6n5d",
          description:
            "Kết hợp kỳ quan thiên nhiên với văn hóa vùng cao Tây Bắc. Tour bao gồm tham quan Vịnh Hạ Long - Di sản thiên nhiên thế giới và khám phá văn hóa các dân tộc vùng cao tại Sapa.",
          short_description:
            "Kết hợp kỳ quan thiên nhiên với văn hóa vùng cao Tây Bắc",
          duration_days: 6,
          duration_nights: 5,
          departure_location: "Hà Nội",
          destination: "Hạ Long - Sapa",
          price_adult: 12490000,
          price_child: 9990000,
          price_infant: 4000000,
          discount_percent: 12,
          available_slots: 10,
          featured_image:
            "https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-4.0.3",
          gallery: [
            "https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-4.0.3",
            "https://images.unsplash.com/photo-1513326738677-b964603b136d?ixlib=rb-4.0.3",
          ],
          itinerary: [
            {
              day: 1,
              title: "Khởi hành - Đến Hạ Long",
              description: "Di chuyển từ Hà Nội đến Hạ Long",
            },
            {
              day: 2,
              title: "Tham quan Vịnh Hạ Long",
              description: "Du thuyền tham quan Vịnh Hạ Long",
            },
            {
              day: 3,
              title: "Di chuyển đến Sapa",
              description: "Di chuyển từ Hạ Long đến Sapa",
            },
            {
              day: 4,
              title: "Khám phá Sapa",
              description: "Tham quan bản làng và ruộng bậc thang",
            },
            {
              day: 5,
              title: "Trải nghiệm văn hóa",
              description: "Tìm hiểu văn hóa các dân tộc vùng cao",
            },
            {
              day: 6,
              title: "Tự do - Khởi hành về",
              description: "Thời gian tự do và về lại Hà Nội",
            },
          ],
          included_services: [
            "Khách sạn 4 sao",
            "Xe đưa đón",
            "Du thuyền",
            "Bữa ăn",
            "Hướng dẫn viên",
          ],
          excluded_services: [
            "Cáp treo Fansipan",
            "Chi phí cá nhân",
            "Thuế VAT",
          ],
          status: "active",
          rating: 4.8,
          reviews_count: 203,
          created_at: "2024-02-15T08:00:00Z",
          updated_at: "2024-03-30T11:45:00Z",
        },
      ],
      categories: [
        {
          id: 1,
          name: "Tour Cao Cấp",
          slug: "cao-cap",
          icon: "gem",
          tour_count: 45,
        },
        {
          id: 2,
          name: "Tour Gia Đình",
          slug: "gia-dinh",
          icon: "users",
          tour_count: 67,
        },
        {
          id: 3,
          name: "Tour Hành Hương",
          slug: "hanh-huong",
          icon: "place-of-worship",
          tour_count: 23,
        },
        {
          id: 4,
          name: "Tour Phượt",
          slug: "phuot",
          icon: "hiking",
          tour_count: 38,
        },
        {
          id: 5,
          name: "Tour Trong Nước",
          slug: "trong-nuoc",
          icon: "map",
          tour_count: 156,
        },
        {
          id: 6,
          name: "Tour Quốc Tế",
          slug: "quoc-te",
          icon: "globe-asia",
          tour_count: 89,
        },
      ],
      bookings: [],
      customers: [],
      hotels: [
    {
        id: 1,
        name: "Khách Sạn Sheraton Hà Nội",
        address: "Quận Tây Hồ, Hà Nội",
        city: "Hà Nội",
        rating: 4.8,
        stars: 5,
        min_price: 3200000,
        rooms_available: 15,
        images: ["images/hotels/hotel1-1.jpg"],
        amenities: ["wifi", "parking", "pool", "spa", "gym", "breakfast"]
    },

    // ======= PHÚ QUỐC =======
    {
        id: 2,
        name: "Vinpearl Resort & Golf Phú Quốc",
        address: "Gành Dầu, Phú Quốc",
        city: "Phú Quốc",
        rating: 4.7,
        stars: 5,
        min_price: 2800000,
        rooms_available: 22,
        images: ["images/hotels/phuquoc1.jpg"],
        amenities: ["wifi", "pool", "spa", "gym", "breakfast", "airport_shuttle"]
    },

    // ======= ĐÀ LẠT =======
    {
        id: 3,
        name: "Ana Mandara Villas Đà Lạt Resort & Spa",
        address: "Phường 5, Đà Lạt",
        city: "Đà Lạt",
        rating: 4.6,
        stars: 5,
        min_price: 2400000,
        rooms_available: 18,
        images: ["images/hotels/dalat1.jpg"],
        amenities: ["wifi", "parking", "spa", "garden", "breakfast"]
    },

    // ======= VŨNG TÀU =======
    {
        id: 4,
        name: "The Imperial Hotel Vũng Tàu",
        address: "Thùy Vân, Vũng Tàu",
        city: "Vũng Tàu",
        rating: 4.5,
        stars: 5,
        min_price: 2600000,
        rooms_available: 20,
        images: ["images/hotels/vungtau1.jpg"],
        amenities: ["wifi", "pool", "spa", "gym", "beachfront", "breakfast"]
    },

    // ======= QUY NHƠN =======
    {
        id: 5,
        name: "Fleur de Lys Hotel Quy Nhơn",
        address: "Nguyễn Huệ, Quy Nhơn",
        city: "Quy Nhơn",
        rating: 4.4,
        stars: 4,
        min_price: 1500000,
        rooms_available: 25,
        images: ["images/hotels/quynhon1.jpg"],
        amenities: ["wifi", "parking", "pool", "gym", "breakfast"]
    },

    // ======= NHA TRANG =======
    {
        id: 6,
        name: "InterContinental Nha Trang",
        address: "Trần Phú, Nha Trang",
        city: "Nha Trang",
        rating: 4.7,
        stars: 5,
        min_price: 2700000,
        rooms_available: 30,
        images: ["images/hotels/nhatrang1.jpg"],
        amenities: ["wifi", "spa", "pool", "gym", "beachfront", "breakfast"]
    },

    // ======= PHAN THIẾT =======
    {
        id: 7,
        name: "The Cliff Resort & Residences Phan Thiết",
        address: "Mũi Né, Phan Thiết",
        city: "Phan Thiết",
        rating: 4.5,
        stars: 4,
        min_price: 1900000,
        rooms_available: 28,
        images: ["images/hotels/phanthiet1.jpg"],
        amenities: ["wifi", "pool", "spa", "gym", "breakfast", "parking"]
    }
    ],

    };
  }

 // ===== TOUR API METHODS =====
  async getTours(params = {}) {
    if (this.config.useMockData) {
      return this.mockGetTours(params);
    }

    try {
      const queryString = new URLSearchParams(params).toString();
      return this.callApi(`/tours${queryString ? '?' + queryString : ''}`);
    } catch (error) {
      console.error("Error fetching tours:", error);
      throw error;
    }
  }

  async getTourById(id) {
    if (this.config.useMockData) {
      return this.mockGetTourById(id);
    }

    try {
      return this.callApi(`/tours/${id}`);
    } catch (error) {
      console.error("Error fetching tour:", error);
      throw error;
    }
  }

  async createTour(tourData) {
    if (this.config.useMockData) {
      return this.mockCreateTour(tourData);
    }

    try {
      return this.callApi('/tours', {
        method: 'POST',
        body: JSON.stringify(tourData)
      });
    } catch (error) {
      console.error("Error creating tour:", error);
      throw error;
    }
  }

  async createTourBooking(bookingData) {
    if (this.config.useMockData) {
      return this.mockCreateTourBooking(bookingData);
    }

    try {
      return this.callApi('/tour-bookings', {
        method: 'POST',
        body: JSON.stringify(bookingData)
      });
    } catch (error) {
      console.error("Error creating tour booking:", error);
      throw error;
    }
  }

  async getTourBookings(params = {}) {
    if (this.config.useMockData) {
      return this.mockGetTourBookings(params);
    }

    try {
      const queryString = new URLSearchParams(params).toString();
      return this.callApi(`/tour-bookings${queryString ? '?' + queryString : ''}`);
    } catch (error) {
      console.error("Error fetching tour bookings:", error);
      throw error;
    }
  }

  async createTourCustomer(customerData) {
    if (this.config.useMockData) {
      return this.mockCreateTourCustomer(customerData);
    }

    try {
      return this.callApi('/tour-customers', {
        method: 'POST',
        body: JSON.stringify(customerData)
      });
    } catch (error) {
      console.error("Error creating tour customer:", error);
      throw error;
    }
  }

 // ===== HELPER METHODS =====
  calculateBookingPrice(tour, bookingData) {
    const adultPrice = tour.price_adult * (1 - tour.discount_percent / 100);
    const childPrice = tour.price_child * (1 - tour.discount_percent / 100);
    const infantPrice = tour.price_infant * (1 - tour.discount_percent / 100);

    return (
      adultPrice * bookingData.num_adults +
      childPrice * bookingData.num_children +
      infantPrice * bookingData.num_infants
    );
  }

  // ===== UTILITY METHODS =====
  formatPrice(price) {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(price);
  }

  formatDate(dateString, includeTime = false) {
    const date = new Date(dateString);
    const options = {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    };
    
    if (includeTime) {
      options.hour = '2-digit';
      options.minute = '2-digit';
    }
    
    return date.toLocaleDateString("vi-VN", options);
  }

  calculateStayDays(checkIn, checkOut) {
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    const timeDiff = checkOutDate - checkInDate;
    return Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
  }

  generateBookingCode() {
    const timestamp = Date.now().toString().slice(-8);
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();
    return `OPL-${timestamp}-${random}`;
  }
}

// ===== GLOBAL INSTANCE & EXPORTS =====
// Create global instance
const apiClient = new ApiClient();

// Export for global use
window.ApiClient = apiClient;

// Convenience functions for tours
async function fetchTours(params = {}) {
  const response = await apiClient.getTours(params);
  return response.data || [];
}

async function fetchTourById(id) {
  const response = await apiClient.getTourById(id);
  return response.data;
}

async function createTourBooking(bookingData) {
  const response = await apiClient.createTourBooking(bookingData);
  return response.data;
}

async function createTourCustomer(customerData) {
  const response = await apiClient.createTourCustomer(customerData);
  return response.data;
}

// Convenience functions for hotel
async function fetchRooms(filters = {}) {
  const response = await apiClient.getRooms(filters);
  return response.data || [];
}

// Thêm convenience function
async function fetchHotels(params = {}) {
    const response = await apiClient.getHotels(params);
    return response.data || [];
}

async function createNewBooking(bookingData) {
  const response = await apiClient.createBooking(bookingData);
  return response.data;
}

async function getDashboardStats() {
  const response = await apiClient.getDashboardData();
  return response.data;
}

// Export convenience functions
window.fetchTours = fetchTours;
window.fetchTourById = fetchTourById;
window.createTourBooking = createTourBooking;
window.createTourCustomer = createTourCustomer;
window.fetchRooms = fetchRooms;
window.fetchHotels = fetchHotels;
window.createNewBooking = createNewBooking;
window.getDashboardStats = getDashboardStats;
window.formatPrice = apiClient.formatPrice.bind(apiClient);
window.formatDate = apiClient.formatDate.bind(apiClient);

// Cấu hình động từ URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('mock')) {
  apiClient.setConfig({ useMockData: urlParams.get('mock') === 'true' });
}

console.log('[API Client] Initialized. Using backend:', apiClient.baseUrl);