// Opulent Travel - API Client (Mock Version)

const API_BASE_URL = "https://api.opulent.vn";
const MOCK_API = true; // Set to false when real API is available

class ApiClient {
  constructor() {
    this.baseUrl = API_BASE_URL;
    this.mockData = this.getMockData();
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
    };
  }

  // Tour API Methods
  async getTours(params = {}) {
    if (MOCK_API) {
      return this.mockGetTours(params);
    }

    try {
      const queryString = new URLSearchParams(params).toString();
      const response = await fetch(`${this.baseUrl}/tours?${queryString}`);
      return await response.json();
    } catch (error) {
      console.error("Error fetching tours:", error);
      throw error;
    }
  }

  async getTourById(id) {
    if (MOCK_API) {
      return this.mockGetTourById(id);
    }

    try {
      const response = await fetch(`${this.baseUrl}/tours/${id}`);
      return await response.json();
    } catch (error) {
      console.error("Error fetching tour:", error);
      throw error;
    }
  }

  async createTour(tourData) {
    if (MOCK_API) {
      return this.mockCreateTour(tourData);
    }

    try {
      const response = await fetch(`${this.baseUrl}/tours`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(tourData),
      });
      return await response.json();
    } catch (error) {
      console.error("Error creating tour:", error);
      throw error;
    }
  }

  // Booking API Methods
  async createBooking(bookingData) {
    if (MOCK_API) {
      return this.mockCreateBooking(bookingData);
    }

    try {
      const response = await fetch(`${this.baseUrl}/bookings`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(bookingData),
      });
      return await response.json();
    } catch (error) {
      console.error("Error creating booking:", error);
      throw error;
    }
  }

  async getBookings(params = {}) {
    if (MOCK_API) {
      return this.mockGetBookings(params);
    }

    try {
      const queryString = new URLSearchParams(params).toString();
      const response = await fetch(`${this.baseUrl}/bookings?${queryString}`);
      return await response.json();
    } catch (error) {
      console.error("Error fetching bookings:", error);
      throw error;
    }
  }

  // Customer API Methods
  async createCustomer(customerData) {
    if (MOCK_API) {
      return this.mockCreateCustomer(customerData);
    }

    try {
      const response = await fetch(`${this.baseUrl}/customers`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(customerData),
      });
      return await response.json();
    } catch (error) {
      console.error("Error creating customer:", error);
      throw error;
    }
  }

  // Mock Methods
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

        if (params.category) {
          // In real implementation, this would filter by category
          // For mock, we'll just return all tours
        }

        if (params.featured) {
          tours = tours.slice(0, 6); // Return first 6 as featured
        }

        if (params.limit) {
          tours = tours.slice(0, params.limit);
        }

        resolve({
          success: true,
          data: tours,
          count: tours.length,
          message: "Tours fetched successfully",
        });
      }, 500); // Simulate network delay
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

  mockCreateBooking(bookingData) {
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

  mockGetBookings(params) {
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

  mockCreateCustomer(customerData) {
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

  // Helper Methods
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

  // Utility Methods
  formatPrice(price) {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(price);
  }

  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("vi-VN", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  generateBookingCode() {
    const timestamp = Date.now().toString().slice(-8);
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();
    return `OPL-${timestamp}-${random}`;
  }
}

// Create global instance
const apiClient = new ApiClient();

// Export for global use
window.ApiClient = apiClient;

// Convenience functions
async function fetchTours(params = {}) {
  const response = await apiClient.getTours(params);
  return response.data || [];
}

async function fetchTourById(id) {
  const response = await apiClient.getTourById(id);
  return response.data;
}

async function createBooking(bookingData) {
  const response = await apiClient.createBooking(bookingData);
  return response.data;
}

async function createCustomer(customerData) {
  const response = await apiClient.createCustomer(customerData);
  return response.data;
}

// Export convenience functions
window.fetchTours = fetchTours;
window.fetchTourById = fetchTourById;
window.createBooking = createBooking;
window.createCustomer = createCustomer;
window.formatPrice = apiClient.formatPrice.bind(apiClient);
window.formatDate = apiClient.formatDate.bind(apiClient);
