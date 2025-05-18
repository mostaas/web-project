document.addEventListener('DOMContentLoaded', function() {
    // --- Element Selectors ---
    const addNewHotelBtn = document.getElementById('addNewHotelBtn');
    const hotelFormSection = document.getElementById('hotelFormSection');
    const hotelForm = document.getElementById('hotelForm');
    const hotelFormTitle = document.getElementById('hotelFormTitle');
    const saveHotelBtn = document.getElementById('saveHotelBtn');
    const cancelFormBtn = document.getElementById('cancelFormBtn');

    const hotelIdInput = document.getElementById('hotel_id');
    const formActionInput = document.getElementById('form_action');
    const hotelNameInput = document.getElementById('hotel_name');
    const hotelLocationInput = document.getElementById('hotel_location');
    const hotelAmenitiesInput = document.getElementById('hotel_amenities');

    const hotelsTable = document.getElementById('hotelsTable');
    const hotelsTableBody = hotelsTable ? hotelsTable.getElementsByTagName('tbody')[0] : null;
    const noHotelsMessage = document.getElementById('noHotelsMessage');
    const adminMessagesDiv = document.getElementById('adminMessages');
    const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : null;


    // --- Helper: Display Messages ---
    function displayMessage(message, type = 'success') {
        if (!adminMessagesDiv) return;
        // Sanitize message before inserting into HTML to prevent XSS if message comes from user input
        const p = document.createElement('p');
        p.className = `admin-message ${type}`;
        p.textContent = message; // Use textContent for safety

        adminMessagesDiv.innerHTML = ''; // Clear previous messages
        adminMessagesDiv.appendChild(p);
        adminMessagesDiv.style.display = 'block';

        setTimeout(() => {
            adminMessagesDiv.style.display = 'none';
            adminMessagesDiv.innerHTML = '';
        }, 5000);
    }

    // --- Helper: Clear Form ---
    function clearForm() {
        if (hotelForm) hotelForm.reset();
        if (hotelIdInput) hotelIdInput.value = '';
        // Ensure hidden action field is reset if necessary, or handled by showForm
    }

    // --- Helper: Show/Hide Form and Set Mode ---
    function showForm(mode = 'add', hotelData = null) {
        if (!hotelFormSection || !hotelFormTitle || !saveHotelBtn || !formActionInput) {
            console.error("Essential form elements for showForm are missing from the DOM.");
            return;
        }
        clearForm(); // Clear form before populating or setting mode

        if (mode === 'add') {
            hotelFormTitle.textContent = 'Add New Hotel';
            saveHotelBtn.textContent = 'Save Hotel';
            formActionInput.value = 'add_hotel';
        } else if (mode === 'edit' && hotelData) {
            hotelFormTitle.textContent = 'Update Hotel';
            saveHotelBtn.textContent = 'Update Hotel';
            formActionInput.value = 'update_hotel';

            if (hotelIdInput) hotelIdInput.value = hotelData.hotelId;
            if (hotelNameInput) hotelNameInput.value = hotelData.name;
            if (hotelLocationInput) hotelLocationInput.value = hotelData.location;
            if (hotelAmenitiesInput) hotelAmenitiesInput.value = hotelData.amenities;
        } else if (mode === 'edit' && !hotelData) {
            console.error("ShowForm called in 'edit' mode but no hotelData provided.");
            displayMessage("Could not load hotel data for editing.", "error");
            return; // Don't show form if edit data is missing
        }

        hotelFormSection.style.display = 'block';
        // Scroll to the form for better UX
        hotelFormSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function hideForm() {
        if (hotelFormSection) {
            hotelFormSection.style.display = 'none';
            clearForm();
        }
    }

    // --- Helper: Escape HTML for rendering dynamic content ---
function escapeHTML(str) {
    if (str === null || typeof str === 'undefined') return '';
    return String(str).replace(/[&<>"']/g, function (match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'  // Corrected to '&#39;' for single quote
        }[match];
    });
}



    // --- Helper: Render a single hotel row for the table ---
    function renderHotelRow(hotel) {
        return `
            <tr data-hotel-id="${hotel.hotelId}">
                <td>${hotel.hotelId}</td>
                <td class="hotel-name">${escapeHTML(hotel.name)}</td>
                <td class="hotel-location">${escapeHTML(hotel.location)}</td>
                <td class="hotel-amenities amenities-cell">${escapeHTML(hotel.amenities).replace(/\n/g, '<br>')}</td>
                <td class="actions-cell">
                    <button type="button" class="btn-action edit editHotelBtn" data-id="${hotel.hotelId}">
                        ✏️ <span class="action-text">Update</span>
                    </button>
                    <button type="button" class="btn-action delete deleteHotelBtn" data-id="${hotel.hotelId}" data-name="${escapeHTML(hotel.name)}">
                        🗑️ <span class="action-text">Delete</span>
                    </button>
                </td>
            </tr>
        `;
    }

    // --- Helper: Update table visibility based on rows ---
    function updateTableVisibility() {
        if (!hotelsTableBody || !noHotelsMessage || !hotelsTable) return;
        if (hotelsTableBody.rows.length > 0) {
            hotelsTable.style.display = ''; // Default table display
            noHotelsMessage.style.display = 'none';
        } else {
            hotelsTable.style.display = 'none';
            noHotelsMessage.style.display = 'block';
        }
    }


    // --- Event Listener: "Add New Hotel" Button ---
    if (addNewHotelBtn) {
        addNewHotelBtn.addEventListener('click', () => {
            showForm('add'); // This should now work
        });
    } else {
        console.warn("#addNewHotelBtn not found in the DOM.");
    }

    // --- Event Listener: "Cancel Form" Button ---
    if (cancelFormBtn) {
        cancelFormBtn.addEventListener('click', () => {
            hideForm();
        });
    } else {
        console.warn("#cancelFormBtn not found in the DOM.");
    }

    // --- Event Listener: Hotel Form Submission (Add/Update) ---
    if (hotelForm) {
        hotelForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!csrfToken) {
                displayMessage("CSRF token missing. Cannot process form.", "error");
                return;
            }

            const formData = new FormData(hotelForm);
            // The 'action' (add_hotel or update_hotel) is already set in the hidden input by showForm()
            // The CSRF token is also already in a hidden input.

            const originalButtonText = saveHotelBtn.textContent;
            saveHotelBtn.disabled = true;
            saveHotelBtn.textContent = formData.get('action') === 'update_hotel' ? 'Updating...' : 'Saving...';

            fetch('manage_hotels.php', { // URL to your PHP script
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayMessage(data.message, 'success');
                    hideForm();
                    if (data.hotel && hotelsTableBody) {
                        if (formData.get('action') === 'add_hotel') {
                            hotelsTableBody.insertAdjacentHTML('beforeend', renderHotelRow(data.hotel));
                        } else if (formData.get('action') === 'update_hotel') {
                            const rowToUpdate = hotelsTableBody.querySelector(`tr[data-hotel-id="${data.hotel.hotelId}"]`);
                            if (rowToUpdate) {
                                rowToUpdate.cells[1].textContent = data.hotel.name;
                                rowToUpdate.cells[2].textContent = data.hotel.location;
                                rowToUpdate.cells[3].innerHTML = escapeHTML(data.hotel.amenities).replace(/\n/g, '<br>');
                                const deleteBtn = rowToUpdate.querySelector('.deleteHotelBtn');
                                if(deleteBtn) deleteBtn.dataset.name = data.hotel.name; // Update for confirm message
                            } else {
                                // If row wasn't found (e.g., after a filter not yet implemented), append as new.
                                // Or simply rely on a full page refresh / re-fetch of table data in more complex scenarios.
                                console.warn("Updated row not found in table, appending new one. This might indicate a mismatch or need for table refresh.");
                                hotelsTableBody.insertAdjacentHTML('beforeend', renderHotelRow(data.hotel));
                            }
                        }
                    }
                    updateTableVisibility();
                } else {
                    displayMessage(data.message || 'An error occurred while saving the hotel.', 'error');
                }
            })
            .catch(error => {
                console.error('Form submission error:', error);
                displayMessage(`An error occurred: ${error.message}. Please try again.`, 'error');
            })
            .finally(() => {
                saveHotelBtn.disabled = false;
                saveHotelBtn.textContent = originalButtonText;
            });
        });
    } else {
        console.warn("#hotelForm not found in the DOM.");
    }

    // --- Event Delegation for Table Actions (Edit/Delete) ---
    if (hotelsTable) {
        hotelsTable.addEventListener('click', function(event) {
            const target = event.target;
            const actionButton = target.closest('.btn-action'); // Get the button itself or its child icon/text

            if (!actionButton) return; // Click was not on an action button or its child

            const hotelId = actionButton.dataset.id;
            if (!hotelId) return;

            if (actionButton.classList.contains('editHotelBtn')) {
                fetch(`manage_hotels.php?action=get_hotel&hotel_id=${hotelId}`) // Ensure this PHP endpoint exists
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.hotel) {
                        showForm('edit', data.hotel);
                    } else {
                        displayMessage(data.message || 'Could not fetch hotel details for editing.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching hotel for edit:', error);
                    displayMessage(`Error fetching hotel details: ${error.message}.`, 'error');
                });
            } else if (actionButton.classList.contains('deleteHotelBtn')) {
                const hotelName = actionButton.dataset.name || 'this hotel';
                if (confirm(`Are you sure you want to delete "${escapeHTML(hotelName)}"? This action cannot be undone.`)) {
                    if (!csrfToken) {
                        displayMessage("CSRF token missing. Cannot process deletion.", "error");
                        return;
                    }
                    const formData = new FormData();
                    formData.append('action', 'delete_hotel');
                    formData.append('hotel_id', hotelId);
                    formData.append('csrf_token', csrfToken);

                    fetch('manage_hotels.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            displayMessage(data.message, 'success');
                            const rowToRemove = hotelsTableBody ? hotelsTableBody.querySelector(`tr[data-hotel-id="${hotelId}"]`) : null;
                            if (rowToRemove) {
                                rowToRemove.remove();
                            }
                            updateTableVisibility();
                        } else {
                            displayMessage(data.message || 'Could not delete the hotel.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting hotel:', error);
                        displayMessage(`Error deleting hotel: ${error.message}.`, 'error');
                    });
                }
            }
        });
    } else {
        console.warn("#hotelsTable not found in the DOM.");
    }

    // --- Initial Table Visibility Check ---
    updateTableVisibility();

    console.log("Manage Hotels specific logic initialized.");
});