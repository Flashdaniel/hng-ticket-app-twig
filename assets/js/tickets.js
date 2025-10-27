// Toast notification function
function showToast(message, type = "success") {
  const toast = document.createElement("div");
  toast.className = `toast ${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("show");
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }, 100);
}

// Form validation
function validateTicketForm(formData) {
  const errors = {};
  if (!formData.title?.trim()) {
    errors.title = "Title is required";
  }
  if (!formData.status) {
    errors.status = "Status is required";
  } else if (!["open", "in_progress", "closed"].includes(formData.status)) {
    errors.status = "Invalid status value";
  }
  return errors;
}

// Clear form errors
function clearFormErrors() {
  document.querySelectorAll(".error-message").forEach((el) => el.remove());
}

// Display form errors
function displayFormErrors(errors) {
  clearFormErrors();
  Object.entries(errors).forEach(([field, message]) => {
    const input = document.querySelector(`[name="${field}"]`);
    if (input) {
      const errorDiv = document.createElement("div");
      errorDiv.className = "error-message";
      errorDiv.textContent = message;
      input.parentNode.appendChild(errorDiv);
    }
  });
}

// Create ticket
async function createTicket(formData) {
  try {
    const response = await fetch("/api/tickets", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (!response.ok) {
      if (data.errors) {
        displayFormErrors(data.errors);
      } else {
        showToast(data.error || "Failed to create ticket", "error");
      }
      return null;
    }

    showToast("Ticket created successfully");
    return data;
  } catch (error) {
    showToast("Failed to create ticket", "error");
    return null;
  }
}

// Update ticket
async function updateTicket(id, formData) {
  try {
    const response = await fetch(`/api/tickets/${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(formData),
    });

    const data = await response.json();

    if (!response.ok) {
      if (data.errors) {
        displayFormErrors(data.errors);
      } else {
        showToast(data.error || "Failed to update ticket", "error");
      }
      return null;
    }

    showToast("Ticket updated successfully");
    return data;
  } catch (error) {
    showToast("Failed to update ticket", "error");
    return null;
  }
}

// Delete ticket
async function deleteTicket(id) {
  if (!confirm("Are you sure you want to delete this ticket?")) {
    return false;
  }

  try {
    const response = await fetch(`/api/tickets/${id}`, {
      method: "DELETE",
    });

    const data = await response.json();

    if (!response.ok) {
      showToast(data.error || "Failed to delete ticket", "error");
      return false;
    }

    showToast("Ticket deleted successfully");
    return true;
  } catch (error) {
    showToast("Failed to delete ticket", "error");
    return false;
  }
}

// Load tickets with filters
async function loadTickets(filters = {}) {
  try {
    const queryParams = new URLSearchParams(filters);
    const response = await fetch(`/api/tickets?${queryParams}`);
    const tickets = await response.json();

    if (!response.ok) {
      showToast(tickets.error || "Failed to load tickets", "error");
      return;
    }

    const tbody = document.querySelector("#tickets-table tbody");
    tbody.innerHTML = tickets
      .map(
        (ticket) => `
            <tr>
                <td>#${ticket.id}</td>
                <td>${ticket.title}</td>
                <td>${ticket.description || "-"}</td>
                <td><span class="status-tag ${ticket.status}">${
          ticket.status
        }</span></td>
                <td>${ticket.created}</td>
                <td>
                    <button onclick="openEditTicketModal(${JSON.stringify(
                      ticket
                    ).replace(/"/g, "&quot;")})" 
                            class="action-button edit">Edit</button>
                    <button onclick="handleDeleteTicket(${ticket.id})" 
                            class="action-button delete">Delete</button>
                </td>
            </tr>
        `
      )
      .join("");
  } catch (error) {
    showToast("Failed to load tickets", "error");
  }
}

// Event handlers
async function handleCreateTicket(event) {
  event.preventDefault();
  const form = event.target;
  const formData = Object.fromEntries(new FormData(form));

  clearFormErrors();
  const errors = validateTicketForm(formData);
  if (Object.keys(errors).length > 0) {
    displayFormErrors(errors);
    return;
  }

  const ticket = await createTicket(formData);
  if (ticket) {
    closeModal("createTicketModal");
    form.reset();
    loadTickets();
  }
}

async function handleUpdateTicket(event) {
  event.preventDefault();
  const form = event.target;
  const formData = Object.fromEntries(new FormData(form));
  const ticketId = form.dataset.ticketId;

  clearFormErrors();
  const errors = validateTicketForm(formData);
  if (Object.keys(errors).length > 0) {
    displayFormErrors(errors);
    return;
  }

  const ticket = await updateTicket(ticketId, formData);
  if (ticket) {
    closeModal("editTicketModal");
    form.reset();
    loadTickets();
  }
}

async function handleDeleteTicket(id) {
  if (await deleteTicket(id)) {
    loadTickets();
  }
}

// Modal functions
function openCreateTicketModal() {
  const modal = document.getElementById("createTicketModal");
  modal.style.display = "block";
}

function openEditTicketModal(ticket) {
  const modal = document.getElementById("editTicketModal");
  const form = modal.querySelector("form");

  form.dataset.ticketId = ticket.id;
  form.querySelector('[name="title"]').value = ticket.title;
  form.querySelector('[name="description"]').value = ticket.description || "";
  form.querySelector('[name="status"]').value = ticket.status;

  modal.style.display = "block";
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.style.display = "none";
  clearFormErrors();
  modal.querySelector("form")?.reset();
}

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  loadTickets();

  // Setup filter form
  const filterForm = document.getElementById("filter-form");
  filterForm?.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(filterForm);
    loadTickets(Object.fromEntries(formData));
  });

  // Setup modals
  window.onclick = function (event) {
    if (event.target.classList.contains("modal")) {
      event.target.style.display = "none";
    }
  };
});
