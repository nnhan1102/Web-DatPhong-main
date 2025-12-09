// hotels.js - Hotel booking functionality

class HotelManager {
  constructor() {
    this.hotels = [];
    this.filteredHotels = [];
    this.currentPage = 1;
    this.itemsPerPage = 6;
    this.filters = {
      minPrice: 0,
      maxPrice: 5000000,
      starRating: [],
      amenities: [],
      hotelType: "all",
    };
  }

  init() {
    this.loadHotels();
  }

  async loadHotels() {
    try {
      // Show loading
      document.getElementById("loadingHotels").classList.remove("d-none");
      document.getElementById("hotelsGrid").innerHTML = "";
      document.getElementById("noHotelsFound").classList.add("d-none");

      // Simulate API call
      await this.fetchHotelsFromAPI();

      this.updateHotelsList();
      this.updateResultsCount();
    } catch (error) {
      console.error("Error loading hotels:", error);
      this.showNoHotelsFound();
    } finally {
      document.getElementById("loadingHotels").classList.add("d-none");
    }
  }

  async fetchHotelsFromAPI() {
    // This is a mock API call - replace with actual API endpoint
    try {
      const response = await fetch("/api/hotels");
      const data = await response.json();

      if (data.success) {
        this.hotels = data.data;
        this.filteredHotels = [...this.hotels];
      } else {
        throw new Error(data.message || "Failed to load hotels");
      }
    } catch (error) {
      // Fallback to mock data if API fails
      console.log("Using mock hotel data");
      this.hotels = this.getMockHotels();
      this.filteredHotels = [...this.hotels];
    }
  }

  getMockHotels() {
    return [
      {
        id: 1,
        name: "Khách Sạn Sheraton Hà Nội",
        location: "Quận Tây Hồ, Hà Nội",
        description:
          "Khách sạn 5 sao với view hồ Tây tuyệt đẹp, phục vụ dịch vụ đẳng cấp quốc tế",
        rating: 4.8,
        stars: 5,
        price_per_night: 3200000,
        images: ["images/hotels/hotel1.jpg"],
        amenities: ["wifi", "parking", "pool", "spa", "gym", "breakfast"],
        rooms_available: 15,
        check_in: "14:00",
        check_out: "12:00",
        address: "11 Đ. Xuân Diệu, Quảng An, Tây Hồ, Hà Nội",
        phone: "024 3719 9000",
      },
      // Add more hotels as needed
    ];
  }

  createHotelCard(hotel) {
    const price = new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(hotel.price_per_night);

    const stars = "★".repeat(hotel.stars) + "☆".repeat(5 - hotel.stars);
    const amenitiesList = this.getAmenitiesIcons(hotel.amenities);

    return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card hotel-card h-100 border-0 shadow-sm" data-hotel-id="${
                  hotel.id
                }">
                    <div class="position-relative">
                        <img src="${
                          hotel.images[0] || "images/default-hotel.jpg"
                        }" 
                             class="card-img-top" alt="${hotel.name}" 
                             style="height: 200px; object-fit: cover;">
                        <span class="availability-badge">
                            <span class="badge bg-${
                              hotel.rooms_available > 5 ? "success" : "warning"
                            }">
                                ${hotel.rooms_available} phòng
                            </span>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">${hotel.name}</h6>
                            <div class="hotel-rating">
                                ${hotel.rating.toFixed(1)}
                                <i class="fas fa-star ms-1" style="font-size: 0.8rem;"></i>
                            </div>
                        </div>
                        <p class="card-text text-muted small mb-2">
                            <i class="fas fa-map-marker-alt text-primary me-1"></i> 
                            ${hotel.location}
                        </p>
                        <div class="mb-2">
                            <span class="star-rating">${stars}</span>
                            <small class="text-muted ms-2">${
                              hotel.stars
                            } sao</small>
                        </div>
                        <div class="amenities-icons mb-3">
                            ${amenitiesList}
                        </div>
                        <p class="card-text small mb-3 text-truncate" title="${
                          hotel.description
                        }">
                            ${hotel.description}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <div>
                                <h5 class="price-badge mb-0">${price}</h5>
                                <small class="text-muted">/đêm</small>
                            </div>
                            <button class="btn btn-sm btn-primary book-room-btn" data-hotel-id="${
                              hotel.id
                            }">
                                <i class="fas fa-calendar-check me-1"></i> Đặt phòng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  getAmenitiesIcons(amenities) {
    const icons = {
      wifi: "fa-wifi",
      parking: "fa-parking",
      pool: "fa-swimming-pool",
      spa: "fa-spa",
      gym: "fa-dumbbell",
      breakfast: "fa-utensils",
      ac: "fa-snowflake",
      tv: "fa-tv",
      minibar: "fa-wine-bottle",
    };

    return amenities
      .slice(0, 4)
      .map(
        (amenity) =>
          `<i class="fas ${
            icons[amenity] || "fa-check"
          } text-primary me-1" title="${amenity}"></i>`
      )
      .join("");
  }

  // Add other methods from the previous script...
  // (updateHotelsList, addHotelCardListeners, showHotelDetail, etc.)
}

// Initialize when page loads
document.addEventListener("DOMContentLoaded", function () {
  window.hotelManager = new HotelManager();
  hotelManager.init();
});
