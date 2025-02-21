/**
 * SysParqueo - Parking Management System
 * Main JavaScript functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    initBootstrapComponents();
    
    // Initialize page-specific functionality
    initPageSpecificFunctions();
    
    // Setup global event listeners
    setupGlobalEventListeners();
    
    // Initialize data tables if present
    initDataTables();
    
    // Initialize charts if on dashboard
    if (document.querySelector('#occupancyChart')) {
        initDashboardCharts();
    }
    
    // Initialize form validation
    initFormValidation();
});

/**
 * Initialize Bootstrap tooltips, popovers, etc.
 */
function initBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Activate sidebar toggle for mobile
    const sidebarToggle = document.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
}

/**
 * Initialize page-specific functionality based on current page
 */
function initPageSpecificFunctions() {
    const currentPath = window.location.pathname;
    
    // Dashboard specific functionality
    if (currentPath.includes('dashboard.php')) {
        setupDashboardRefresh();
    } 
    // Registration page functionality
    else if (currentPath.includes('registro.php')) {
        setupRegistrationPage();
    }
    // Cliente page functionality
    else if (currentPath.includes('clientes.php')) {
        setupClientPage();
    }
    // Payments page functionality
    else if (currentPath.includes('pagos.php')) {
        setupPaymentsPage();
    }
    // Places management
    else if (currentPath.includes('lugares.php')) {
        setupPlacesManagement();
    }
}

/**
 * Setup dashboard auto-refresh and realtime updates
 */
function setupDashboardRefresh() {
    // Refresh dashboard data every 5 minutes
    setInterval(function() {
        fetchDashboardStats();
    }, 300000); // 5 minutes
    
    // Setup realtime notifications check
    checkForNotifications();
}

/**
 * Fetch updated dashboard statistics via AJAX
 */
function fetchDashboardStats() {
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            // Update availability stats
            document.querySelector('#availablePlaces').textContent = 
                `${data.disponibles}/${data.total_lugares}`;
            
            // Update current vehicles
            document.querySelector('#currentVehicles').textContent = 
                data.vehiculos_actuales;
            
            // Update today's income
            document.querySelector('#todayIncome').textContent = 
                `$${parseFloat(data.ingresos_hoy).toFixed(2)}`;
            
            // Update occupation percentage
            const occupationPercentage = data.total_lugares > 0 
                ? Math.round((data.ocupados / data.total_lugares) * 100) 
                : 0;
            document.querySelector('#occupationPercentage').textContent = 
                `${occupationPercentage}%`;
                
            // Update charts if they exist
            if (window.occupancyChart) {
                updateOccupancyChart(data);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

/**
 * Initialize and render dashboard charts
 */
function initDashboardCharts() {
    // Occupancy trend chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    window.occupancyChart = new Chart(occupancyCtx, {
        type: 'line',
        data: {
            labels: ['6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'],
            datasets: [{
                label: 'Ocupación %',
                data: [30, 45, 65, 80, 75, 85, 90, 85, 70],
                borderColor: '#2980b9',
                backgroundColor: 'rgba(41, 128, 185, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
    
    // Vehicle types pie chart
    const vehicleTypesCtx = document.getElementById('vehicleTypesChart')?.getContext('2d');
    if (vehicleTypesCtx) {
        new Chart(vehicleTypesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Automóvil', 'Motocicleta', 'Camioneta', 'Otro'],
                datasets: [{
                    data: [65, 20, 12, 3],
                    backgroundColor: [
                        '#3498db',
                        '#9b59b6',
                        '#2ecc71',
                        '#e74c3c'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Revenue chart (weekly)
    const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Ingresos',
                    data: [1200, 1900, 1500, 1700, 2100, 2800, 1800],
                    backgroundColor: '#27ae60'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }
}

/**
 * Update occupancy chart with new data
 */
function updateOccupancyChart(data) {
    if (window.occupancyChart && data.hourly_occupancy) {
        window.occupancyChart.data.datasets[0].data = data.hourly_occupancy;
        window.occupancyChart.update();
    }
}

/**
 * Setup functionality for the vehicle registration page
 */
function setupRegistrationPage() {
    // Client search functionality
    const clientSearchInput = document.getElementById('clientSearch');
    if (clientSearchInput) {
        clientSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length >= 3) {
                searchClients(searchTerm);
            } else {
                document.getElementById('clientSearchResults').innerHTML = '';
            }
        });
    }
    
    // Vehicle type change handler
    const vehicleTypeSelect = document.getElementById('vehicleType');
    if (vehicleTypeSelect) {
        vehicleTypeSelect.addEventListener('change', function() {
            updateTariffOptions(this.value);
        });
    }
    
    // Initialize date and time pickers
    initDateTimePickers();
    
    // License plate validation
    const plateInput = document.getElementById('vehiclePlate');
    if (plateInput) {
        plateInput.addEventListener('blur', function() {
            validateLicensePlate(this.value);
        });
    }
    
    // Check for available spaces
    updateAvailableSpaces();
}

/**
 * Search clients by name or document number
 */
function searchClients(term) {
    fetch(`api/search-clients.php?term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(clients => {
            const resultsContainer = document.getElementById('clientSearchResults');
            resultsContainer.innerHTML = '';
            
            if (clients.length === 0) {
                resultsContainer.innerHTML = '<div class="p-2 text-muted">No se encontraron resultados</div>';
                return;
            }
            
            clients.forEach(client => {
                const clientEl = document.createElement('div');
                clientEl.className = 'p-2 border-bottom client-result';
                clientEl.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${client.nombre}</strong><br>
                            <small>Doc: ${client.documento}</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary select-client" 
                                data-id="${client.id}" data-name="${client.nombre}">
                            Seleccionar
                        </button>
                    </div>
                `;
                
                clientEl.querySelector('.select-client').addEventListener('click', function() {
                    selectClient(this.dataset.id, this.dataset.name);
                });
                
                resultsContainer.appendChild(clientEl);
            });
        })
        .catch(error => {
            console.error('Error searching clients:', error);
        });
}

/**
 * Select a client from search results
 */
function selectClient(id, name) {
    document.getElementById('clientId').value = id;
    document.getElementById('clientName').value = name;
    document.getElementById('clientSearchResults').innerHTML = '';
    document.getElementById('clientSearch').value = '';
    
    // Fetch client vehicles
    fetchClientVehicles(id);
}

/**
 * Fetch vehicles belonging to a specific client
 */
function fetchClientVehicles(clientId) {
    fetch(`api/client-vehicles.php?client_id=${encodeURIComponent(clientId)}`)
        .then(response => response.json())
        .then(vehicles => {
            const vehicleSelect = document.getElementById('existingVehicle');
            vehicleSelect.innerHTML = '<option value="">Seleccionar vehículo existente...</option>';
            
            vehicles.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.placa} - ${vehicle.marca} ${vehicle.modelo} (${vehicle.tipo})`;
                option.dataset.type = vehicle.tipo;
                option.dataset.plate = vehicle.placa;
                vehicleSelect.appendChild(option);
            });
            
            // Show existing vehicles section if there are vehicles
            const existingVehiclesSection = document.getElementById('existingVehiclesSection');
            if (existingVehiclesSection) {
                existingVehiclesSection.style.display = vehicles.length > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching client vehicles:', error);
        });
}

/**
 * Handle selection of existing vehicle
 */
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'existingVehicle') {
        const selectedOption = e.target.options[e.target.selectedIndex];
        if (selectedOption.value) {
            document.getElementById('vehiclePlate').value = selectedOption.dataset.plate;
            document.getElementById('vehicleType').value = selectedOption.dataset.type;
            
            // Update tariff options based on selected vehicle type
            updateTariffOptions(selectedOption.dataset.type);
            
            // Disable vehicle inputs
            document.getElementById('vehiclePlate').disabled = true;
            document.getElementById('vehicleType').disabled = true;
        } else {
            // Enable vehicle inputs
            document.getElementById('vehiclePlate').disabled = false;
            document.getElementById('vehicleType').disabled = false;
            
            // Clear values
            document.getElementById('vehiclePlate').value = '';
        }
    }
});

/**
 * Update tariff options based on vehicle type
 */
function updateTariffOptions(vehicleType) {
    fetch(`api/get-tariffs.php?vehicle_type=${encodeURIComponent(vehicleType)}`)
        .then(response => response.json())
        .then(tariffs => {
            const tariffSelect = document.getElementById('tariffId');
            tariffSelect.innerHTML = '';
            
            tariffs.forEach(tariff => {
                const option = document.createElement('option');
                option.value = tariff.id;
                option.textContent = `${tariff.tipo} - $${tariff.valor} (${tariff.descripcion})`;
                tariffSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching tariffs:', error);
        });
}

/**
 * Update available parking spaces
 */
function updateAvailableSpaces() {
    fetch('api/available-spaces.php')
        .then(response => response.json())
        .then(data => {
            const spaceSelect = document.getElementById('parkingSpace');
            if (!spaceSelect) return;
            
            spaceSelect.innerHTML = '<option value="">Seleccionar lugar...</option>';
            
            data.available_spaces.forEach(space => {
                const option = document.createElement('option');
                option.value = space.id;
                option.textContent = `${space.numero_lugar} - ${space.zona}`;
                spaceSelect.appendChild(option);
            });
            
            // Update available count
            const availableCount = document.getElementById('availableSpacesCount');
            if (availableCount) {
                availableCount.textContent = data.available_spaces.length;
            }
        })
        .catch(error => {
            console.error('Error fetching available spaces:', error);
        });
}

/**
 * Validate license plate format
 */
function validateLicensePlate(plate) {
    const plateInput = document.getElementById('vehiclePlate');
    const feedbackElement = document.getElementById('plateFeedback');
    
    // Colombian license plate validation
    const platePattern = /^[A-Z]{3}\d{3}$|^[A-Z]{3}\d{2}[A-Z]$/;
    
    if (!platePattern.test(plate)) {
        plateInput.classList.add('is-invalid');
        feedbackElement.textContent = 'Formato de placa inválido. Use el formato ABC123 o ABC12D';
        return false;
    } else {
        // Check if plate already exists in active records
        fetch(`api/check-plate.php?plate=${encodeURIComponent(plate)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    plateInput.classList.add('is-invalid');
                    feedbackElement.textContent = 'Este vehículo ya se encuentra en el parqueadero';
                } else {
                    plateInput.classList.remove('is-invalid');
                    plateInput.classList.add('is-valid');
                }
            })
            .catch(error => {
                console.error('Error checking plate:', error);
            });
    }
}

/**
 * Initialize date and time pickers
 */
function initDateTimePickers() {
    const dateInputs = document.querySelectorAll('.datepicker');
    dateInputs.forEach(input => {
        new Datepicker(input, {
            format: 'dd/mm/yyyy',
            autohide: true,
            language: 'es'
        });
    });
    
    const timeInputs = document.querySelectorAll('.timepicker');
    timeInputs.forEach(input => {
        new Timepicker(input, {
            format: 'HH:mm'
        });
    });
}

/**
 * Setup functionality for client management page
 */
function setupClientPage() {
    // Client search
    const clientSearchInput = document.getElementById('clientQuickSearch');
    if (clientSearchInput) {
        clientSearchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length >= 2) {
                filterClientTable(searchTerm);
            } else {
                resetClientTable();
            }
        }, 300));
    }
    
    // Initialize client form validation
    const clientForm = document.getElementById('clientForm');
    if (clientForm) {
        clientForm.addEventListener('submit', function(e) {
            if (!validateClientForm()) {
                e.preventDefault();
            }
        });
        
        // Setup document type change handler
        const docTypeSelect = document.getElementById('documentType');
        if (docTypeSelect) {
            docTypeSelect.addEventListener('change', function() {
                updateDocumentValidation(this.value);
            });
        }
    }
}

/**
 * Filter client table based on search term
 */
function filterClientTable(term) {
    const rows = document.querySelectorAll('#clientTable tbody tr');
    term = term.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

/**
 * Reset client table to show all rows
 */
function resetClientTable() {
    const rows = document.querySelectorAll('#clientTable tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
}

/**
 * Validate client form inputs
 */
function validateClientForm() {
    let isValid = true;
    const requiredFields = ['nombre', 'documento', 'telefono', 'email'];
    
    // Check required fields
    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    // Validate email format
    const emailInput = document.getElementById('email');
    if (emailInput.value.trim() && !validateEmail(emailInput.value)) {
        emailInput.classList.add('is-invalid');
        document.getElementById('emailFeedback').textContent = 'Formato de correo electrónico inválido';
        isValid = false;
    }
    
    // Validate document number
    const documentInput = document.getElementById('documento');
    const documentType = document.getElementById('documentType').value;
    if (documentInput.value.trim() && !validateDocument(documentInput.value, documentType)) {
        documentInput.classList.add('is-invalid');
        document.getElementById('documentFeedback').textContent = 'Número de documento inválido';
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate email format
 */
function validateEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}

/**
 * Validate document number based on type
 */
function validateDocument(document, type) {
    // Colombian document validation
    switch (type) {
        case 'CC':
            // Cédula de Ciudadanía (8-10 digits)
            return /^\d{8,10}$/.test(document);
        case 'TI':
            // Tarjeta de Identidad (10-11 digits)
            return /^\d{10,11}$/.test(document);
        case 'CE':
            // Cédula de Extranjería (6-7 digits)
            return /^[A-Z0-9]{6,7}$/.test(document);
        case 'NIT':
            // NIT (9 digits + verification digit)
            return /^\d{9}-\d{1}$/.test(document);
        default:
            return true;
    }
}

/**
 * Update document field validation based on document type
 */
function updateDocumentValidation(documentType) {
    const documentInput = document.getElementById('documento');
    const documentFeedback = document.getElementById('documentFeedback');
    
    switch (documentType) {
        case 'CC':
            documentInput.placeholder = 'Ej: 1020304050';
            documentFeedback.textContent = 'Debe contener entre 8 y 10 dígitos';
            break;
        case 'TI':
            documentInput.placeholder = 'Ej: 98765432109';
            documentFeedback.textContent = 'Debe contener entre 10 y 11 dígitos';
            break;
        case 'CE':
            documentInput.placeholder = 'Ej: A123456';
            documentFeedback.textContent = 'Debe contener entre 6 y 7 caracteres';
            break;
        case 'NIT':
            documentInput.placeholder = 'Ej: 900123456-7';
            documentFeedback.textContent = 'Formato: 9 dígitos + guion + dígito de verificación';
            break;
    }
}

/**
 * Setup functionality for payments management page
 */
function setupPaymentsPage() {
    // Date range picker initialization
    const dateRangePicker = document.getElementById('dateRange');
    if (dateRangePicker) {
        new DateRangePicker(dateRangePicker, {
            format: 'dd/mm/yyyy'
        });
    }
    
    // Payment method change handler
    const paymentMethodSelect = document.getElementById('paymentMethod');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            toggleAdditionalFields(this.value);
        });
    }
    
    // Payment calculation
    setupPaymentCalculation();
}

/**
 * Toggle additional fields based on payment method
 */
function toggleAdditionalFields(paymentMethod) {
    const cardFields = document.getElementById('cardFields');
    const transferFields = document.getElementById('transferFields');
    
    if (cardFields && transferFields) {
        cardFields.style.display = paymentMethod === 'tarjeta' ? 'block' : 'none';
        transferFields.style.display = paymentMethod === 'transferencia' ? 'block' : 'none';
    }
}

/**
 * Setup payment calculation
 */
function setupPaymentCalculation() {
    const registroSelect = document.getElementById('registroId');
    if (registroSelect) {
        registroSelect.addEventListener('change', function() {
            if (this.value) {
                calculatePayment(this.value);
            } else {
                document.getElementById('calculationResult').innerHTML = '';
            }
        });
    }
}

/**
 * Calculate payment amount based on selected registration
 */
function calculatePayment(registroId) {
    fetch(`api/calculate-payment.php?registro_id=${encodeURIComponent(registroId)}`)
        .then(response => response.json())
        .then(data => {
            const resultContainer = document.getElementById('calculationResult');
            
            if (data.error) {
                resultContainer.innerHTML = `
                    <div class="alert alert-danger">
                        ${data.error}
                    </div>
                `;
                return;
            }
            
            resultContainer.innerHTML = `
                <div class="card border-primary mt-3">
                    <div class="card-header bg-primary text-white">
                        Detalles del Pago
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Vehículo:</strong> ${data.placa}</p>
                                <p><strong>Cliente:</strong> ${data.cliente}</p>
                                <p><strong>Hora de Entrada:</strong> ${data.hora_entrada}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tarifa:</strong> ${data.tarifa_tipo}</p>
                                <p><strong>Tiempo:</strong> ${data.tiempo_transcurrido}</p>
                                <p><strong>Lugar:</strong> ${data.lugar}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 text-center">
                                <h4 class="text-primary">Total a Pagar</h4>
                                <h2>$${parseFloat(data.monto).toFixed(2)}</h2>
                                <input type="hidden" name="monto" value="${data.monto}">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error calculating payment:', error);
        });
}

/**
 * Setup parking spaces management
 */
function setupPlacesManagement() {
    // Interactive parking map
    initParkingMap();
    
    // Place form validation
    const placeForm = document.getElementById('placeForm');
    if (placeForm) {
        placeForm.addEventListener('submit', function(e) {
            if (!validatePlaceForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Add zone functionality
    const addZoneBtn = document.getElementById('addZoneBtn');
    if (addZoneBtn) {
        addZoneBtn.addEventListener('click', function() {
            const zonesContainer = document.getElementById('zonesContainer');
            const zoneCount = zonesContainer.children.length;
            
            const zoneRow = document.createElement('div');
            zoneRow.className = 'row mb-3 zone-row';
            zoneRow.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" name="zones[${zoneCount}][name]" placeholder="Nombre de Zona" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" name="zones[${zoneCount}][capacity]" placeholder="Capacidad" min="1" required>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="zones[${zoneCount}][type]">
                        <option value="general">General</option>
                        <option value="disabled">Discapacitados</option>
                        <option value="reserved">Reservados</option>
                        <option value="motorcycle">Motocicletas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger remove-zone">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            zoneRow.querySelector('.remove-zone').addEventListener('click', function() {
                zonesContainer.removeChild(zoneRow);
            });
            
            zonesContainer.appendChild(zoneRow);
        });
    }
}

/**
 * Initialize interactive parking map
 */
function initParkingMap() {
    const parkingMap = document.getElementById('parkingMap');
    if (!parkingMap) return;
    
    fetch('api/parking-status.php')
        .then(response => response.json())
        .then(data => {
            const zones = data.zones;
            
            // Create zones
            zones.forEach(zone => {
                const zoneEl = document.createElement('div');
                zoneEl.className = 'parking-zone mb-4';
                zoneEl.innerHTML = `
                    <h5>${zone.name} <span class="badge bg-${getZoneBadgeColor(zone.type)}">${getZoneTypeName(zone.type)}</span></h5>
                    <div class="parking-spaces d-flex flex-wrap" id="zone-${zone.id}"></div>
                `;
                
                parkingMap.appendChild(zoneEl);
                
                // Create spaces within zone
                const spacesContainer = document.getElementById(`zone-${zone.id}`);
                zone.spaces.forEach(space => {
                    const spaceEl = document.createElement('div');
                    spaceEl.className = `parking-space ${space.status} ${space.type}`;
                    spaceEl.dataset.id = space.id;
                    spaceEl.dataset.number = space.number;
                    spaceEl.innerHTML = `
                        <span class="space-number">${space.number}</span>
                        ${space.status === 'occupied' ? `<span class="vehicle-info">${space.vehicle}</span>` : ''}
                    `;
                    
                    spaceEl.addEventListener('click', function() {
                        showSpaceDetails(space);
                    });
                    
                    spacesContainer.appendChild(spaceEl);
                });
            });
        })
        .catch(error => {
            console.error('Error loading parking map:', error);
            parkingMap.innerHTML = '<div class="alert alert-danger">Error al cargar el mapa de estacionamiento</div>';
        });
}

/**
 * Show parking space details in modal
 */
function showSpaceDetails(space) {
    const modal = new bootstrap.Modal(document.getElementById('spaceDetailModal'));
    const modalBody = document.getElementById('spaceDetailBody');
    
    if (space.status === 'occupied') {
        // Fetch detailed info
        fetch(`api/space-details.php?space_id=${encodeURIComponent(space.id)}`)
            .then(response => response.json())
            .then(details => {
                modalBody.innerHTML = `
                    <div class="space-info-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5>Espacio #${space.number}</h5>
                                <span class="badge bg-danger">Ocupado</span>
                            </div>
                            <div class="vehicle-tag">
                                <i class="fas fa-car"></i> ${details.placa}
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Cliente:</strong> ${details.cliente}</p> 
                                <p><strong>Hora de Entrada:</strong> ${details.hora_entrada}</p>
                            </div>  
                            <div class="col-md-6">
                                <p><strong>Tarifa:</strong> ${details.tarifa_tipo}</p>
                                <p><strong>Costo:</strong> ${details.costo}</p>
                            </div>
                        </div>
                    </div>                      
                `;                
            })
            .catch(error => {
                console.error('Error fetching space details:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Error al obtener detalles del espacio</div>';
            });
    } else {
        modalBody.innerHTML = `            
            <div class="space-info-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5>Espacio #${space.number}</h5>
                        <span class="badge bg-success">Libre</span>
                    </div>
                </div>
            </div>
        `;
    }    
    modal.show();
}