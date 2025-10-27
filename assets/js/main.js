// Constants
const TOKEN_KEY = "ticket_app_token";
const API_BASE_URL = "http://localhost:3000"; // Replace with your actual API URL

// Authentication Functions
const isAuthenticated = () => {
  const token = localStorage.getItem(TOKEN_KEY);
  return !!token;
};

const getToken = () => {
  return localStorage.getItem(TOKEN_KEY);
};

const setToken = (token) => {
  localStorage.setItem(TOKEN_KEY, token);
};

const removeToken = () => {
  localStorage.removeItem(TOKEN_KEY);
};

// Form Handling
const handleFormSubmit = async (event, endpoint, redirectUrl) => {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        ...(isAuthenticated() && { Authorization: `Bearer ${getToken()}` }),
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error("Request failed");
    }

    const result = await response.json();

    if (endpoint === "/auth/login" || endpoint === "/auth/signup") {
      setToken(result.token);
    }

    window.location.href = redirectUrl;
  } catch (error) {
    console.error("Error:", error);
    showError(form, error.message || "An error occurred. Please try again.");
  }
};

// Error Display
const showError = (form, message) => {
  const errorDiv = form.querySelector(".error-message");
  if (errorDiv) {
    errorDiv.textContent = message;
    errorDiv.style.display = "block";
  } else {
    const div = document.createElement("div");
    div.className = "error-message error-text";
    div.textContent = message;
    form.insertBefore(div, form.firstChild);
  }
};

// Ticket Management Functions
const createTicket = async (data) => {
  try {
    const response = await fetch(`${API_BASE_URL}/api/tickets`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${getToken()}`,
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error("Failed to create ticket");
    }

    return await response.json();
  } catch (error) {
    console.error("Error creating ticket:", error);
    throw error;
  }
};

const updateTicket = async (ticketId, data) => {
  try {
    const response = await fetch(`${API_BASE_URL}/api/tickets/${ticketId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${getToken()}`,
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error("Failed to update ticket");
    }

    return await response.json();
  } catch (error) {
    console.error("Error updating ticket:", error);
    throw error;
  }
};

const deleteTicket = async (ticketId) => {
  try {
    const response = await fetch(`${API_BASE_URL}/api/tickets/${ticketId}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${getToken()}`,
      },
    });

    if (response.status !== 204 && !response.ok) {
      throw new Error("Failed to delete ticket");
    }
  } catch (error) {
    console.error("Error deleting ticket:", error);
    throw error;
  }
};

// Logout Function
const logout = () => {
  removeToken();
  // redirect to auth login route
  window.location.href = "/auth/login";
};

// Event Listeners
document.addEventListener("DOMContentLoaded", () => {
  // Login Form
  const loginForm = document.querySelector("#login-form");
  if (loginForm) {
    loginForm.addEventListener("submit", (e) =>
      handleFormSubmit(e, "/auth/login", "/dashboard")
    );
  }

  // Signup Form
  const signupForm = document.querySelector("#signup-form");
  if (signupForm) {
    signupForm.addEventListener("submit", (e) =>
      handleFormSubmit(e, "/auth/signup", "/dashboard")
    );
  }

  // Ticket Form
  const ticketForm = document.querySelector("#ticket-form");
  if (ticketForm) {
    ticketForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(ticketForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await createTicket(data);
        window.location.reload();
      } catch (error) {
        showError(ticketForm, error.message);
      }
    });
  }

  // Logout Button
  const logoutButton = document.querySelector("#logout-button");
  if (logoutButton) {
    logoutButton.addEventListener("click", logout);
  }

  // Check Authentication Status
  const protectedPages = ["/dashboard", "/tickets"];
  const currentPath = window.location.pathname;

  if (
    protectedPages.some((page) => currentPath.includes(page)) &&
    !isAuthenticated()
  ) {
    window.location.href = "/auth/login";
  }
});

// Status Badge Colors
const getStatusColor = (status) => {
  const statusColors = {
    open: "var(--status-open)",
    "in-progress": "var(--status-in-progress)",
    closed: "var(--status-closed)",
  };
  return statusColors[status.toLowerCase()] || "var(--color-text-light)";
};

// Dynamic Status Badge Update
const updateStatusBadges = () => {
  const statusBadges = document.querySelectorAll(".status-badge");
  statusBadges.forEach((badge) => {
    const status = badge.dataset.status;
    badge.style.backgroundColor = getStatusColor(status);
  });
};

// Call status badge update when DOM is loaded
document.addEventListener("DOMContentLoaded", updateStatusBadges);
