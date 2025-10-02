// Calendar.js - Funcionalidades del calendario web
class CalendarApp {
    constructor() {
        // Configurar axios para enviar cookies automáticamente
        axios.defaults.withCredentials = true;
        
        this.calendar = null;
        this.currentUser = null;
        this.calendars = [];
        this.events = [];
        this.init();
    }

    async init() {
        // Verificar autenticación
        await this.checkAuth();
        
        // Inicializar componentes
        this.initializeCalendar();
        this.setupEventListeners();
        this.loadCalendars();
        this.loadEvents();
    }

    async checkAuth() {
        try {
            const response = await axios.get('/calendar/api/auth/me');
            if (response.data.success) {
                this.currentUser = response.data.user;
                document.getElementById('userName').textContent = this.currentUser.full_name;
            } else {
                window.location.href = 'index.html';
            }
        } catch (error) {
            console.error('Error de autenticación:', error);
            window.location.href = 'index.html';
        }
    }

    initializeCalendar() {
        const calendarEl = document.getElementById('calendar');
        
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: false, // Usaremos controles personalizados
            height: 'auto',
            firstDay: 1, // Lunes como primer día
            
            // Configuración de tiempo
            slotMinTime: '07:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: true,
            
            // Eventos
            events: (fetchInfo, successCallback, failureCallback) => {
                this.fetchEvents(fetchInfo, successCallback, failureCallback);
            },
            
            // Interacciones
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,
            weekends: true,
            editable: true,
            droppable: true,
            
            // Event handlers
            select: (selectInfo) => {
                this.handleDateSelect(selectInfo);
            },
            
            eventClick: (clickInfo) => {
                this.handleEventClick(clickInfo);
            },
            
            eventDrop: (dropInfo) => {
                this.handleEventDrop(dropInfo);
            },
            
            eventResize: (resizeInfo) => {
                this.handleEventResize(resizeInfo);
            },
            
            // Personalización de vistas
            views: {
                dayGridMonth: {
                    dayMaxEventRows: 3
                },
                timeGridWeek: {
                    allDaySlot: true,
                    slotDuration: '00:30:00'
                },
                timeGridDay: {
                    allDaySlot: true,
                    slotDuration: '00:15:00'
                }
            }
        });
        
        this.calendar.render();
        this.updateCurrentDate();
    }

    async fetchEvents(fetchInfo, successCallback, failureCallback) {
        try {
            const start = fetchInfo.start.toISOString().split('T')[0];
            const end = fetchInfo.end.toISOString().split('T')[0];
            
            const response = await axios.get(`/calendar/api/event?start=${start}&end=${end}`);
            
            if (response.data.success) {
                successCallback(response.data.events);
            } else {
                failureCallback(response.data.message);
            }
        } catch (error) {
            console.error('Error al cargar eventos:', error);
            failureCallback('Error al cargar eventos');
        }
    }

    setupEventListeners() {
        // Controles de navegación
        document.getElementById('prevBtn').addEventListener('click', () => {
            this.calendar.prev();
            this.updateCurrentDate();
        });
        
        document.getElementById('nextBtn').addEventListener('click', () => {
            this.calendar.next();
            this.updateCurrentDate();
        });
        
        document.getElementById('todayBtn').addEventListener('click', () => {
            this.calendar.today();
            this.updateCurrentDate();
        });

        // Cambios de vista
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.target.getAttribute('data-view');
                this.calendar.changeView(view);
                this.updateViewButtons(e.target);
            });
        });

        // Botón crear evento
        document.getElementById('createEventBtn').addEventListener('click', () => {
            this.openEventModal();
        });

        // Form de evento
        document.getElementById('eventForm').addEventListener('submit', (e) => {
            this.handleEventSubmit(e);
        });

        // Botón eliminar evento
        document.getElementById('deleteEventBtn').addEventListener('click', () => {
            this.handleEventDelete();
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', () => {
            this.logout();
        });

        // Checkbox de calendarios
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox' && e.target.closest('#groupCalendars')) {
                this.toggleCalendarVisibility(e.target);
            }
        });

        // All day checkbox
        document.getElementById('allDay').addEventListener('change', (e) => {
            this.toggleAllDayFields(e.target.checked);
        });
    }

    handleDateSelect(selectInfo) {
        this.openEventModal(null, selectInfo.start, selectInfo.end);
        this.calendar.unselect();
    }

    handleEventClick(clickInfo) {
        this.openEventModal(clickInfo.event);
    }

    async handleEventDrop(dropInfo) {
        await this.updateEventDates(dropInfo.event);
    }

    async handleEventResize(resizeInfo) {
        await this.updateEventDates(resizeInfo.event);
    }

    async updateEventDates(event) {
        try {
            const eventData = {
                id: event.id,
                start: event.start.toISOString().replace('T', ' ').replace('Z', ''),
                end: event.end ? event.end.toISOString().replace('T', ' ').replace('Z', '') : null,
                allDay: event.allDay
            };

            const response = await axios.put(`/calendar/api/event/${event.id}`, eventData);
            
            if (!response.data.success) {
                // Revertir cambios si hay error
                event.revert();
                this.showAlert('danger', 'Error al actualizar evento');
            }
        } catch (error) {
            event.revert();
            this.showAlert('danger', 'Error al actualizar evento');
        }
    }

    openEventModal(event = null, startDate = null, endDate = null) {
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        const form = document.getElementById('eventForm');
        const title = document.getElementById('eventModalTitle');
        const deleteBtn = document.getElementById('deleteEventBtn');

        // Reset form
        form.reset();
        deleteBtn.style.display = 'none';

        if (event) {
            // Modo edición
            title.textContent = 'Editar Evento';
            deleteBtn.style.display = 'inline-block';
            
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventDescription').value = event.extendedProps.description || '';
            document.getElementById('eventLocation').value = event.extendedProps.location || '';
            
            if (event.allDay) {
                document.getElementById('allDay').checked = true;
                document.getElementById('startDate').value = this.formatDateForInput(event.start, true);
                document.getElementById('endDate').value = event.end ? this.formatDateForInput(event.end, true) : '';
            } else {
                document.getElementById('startDate').value = this.formatDateForInput(event.start);
                document.getElementById('endDate').value = event.end ? this.formatDateForInput(event.end) : '';
            }
            
            this.toggleAllDayFields(event.allDay);
        } else {
            // Modo creación
            title.textContent = 'Nuevo Evento';
            
            if (startDate) {
                document.getElementById('startDate').value = this.formatDateForInput(startDate);
                if (endDate) {
                    document.getElementById('endDate').value = this.formatDateForInput(endDate);
                }
            }
        }

        modal.show();
    }

    async handleEventSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const eventId = formData.get('eventId') || document.getElementById('eventId').value;
        
        // Get selected calendar/group from dropdown
        const selectedUniqueId = document.getElementById('eventCalendar').value;
        
        if (!selectedUniqueId) {
            this.showAlert('warning', 'Por favor selecciona un calendario o grupo');
            return;
        }
        
        // Parse unique_id to get type and real ID
        const [type, realId] = selectedUniqueId.split('_');
        
        const eventData = {
            calendar_id: type === 'calendar' ? parseInt(realId) : null,
            group_id: type === 'group' ? parseInt(realId) : null,
            container_type: type, // 'calendar' or 'group'
            title: formData.get('title'),
            description: formData.get('description'),
            start_datetime: formData.get('start_datetime'),
            end_datetime: formData.get('end_datetime'),
            is_all_day: document.getElementById('allDay').checked,
            location: formData.get('location')
        };

        try {
            let response;
            if (eventId) {
                response = await axios.put(`/calendar/api/event/${eventId}`, eventData);
            } else {
                response = await axios.post('/calendar/api/event', eventData);
            }

            if (response.data.success) {
                this.showAlert('success', eventId ? 'Evento actualizado' : 'Evento creado');
                this.calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            } else {
                this.showAlert('danger', response.data.message);
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message;
            this.showAlert('danger', 'Error al guardar evento: ' + errorMessage);
        }
    }

    async handleEventDelete() {
        const eventId = document.getElementById('eventId').value;
        
        if (!eventId) return;
        
        if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
            try {
                const response = await axios.delete(`/calendar/api/event/${eventId}`);
                
                if (response.data.success) {
                    this.showAlert('success', 'Evento eliminado');
                    this.calendar.refetchEvents();
                    bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
                } else {
                    this.showAlert('danger', response.data.message);
                }
            } catch (error) {
                this.showAlert('danger', 'Error al eliminar evento');
            }
        }
    }

    updateCurrentDate() {
        const currentDate = this.calendar.getDate();
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        document.getElementById('currentDate').textContent = 
            currentDate.toLocaleDateString('es-ES', options);
    }

    updateViewButtons(activeBtn) {
        document.querySelectorAll('[data-view]').forEach(btn => {
            btn.classList.remove('active');
        });
        activeBtn.classList.add('active');
    }

    toggleAllDayFields(isAllDay) {
        const startInput = document.getElementById('startDate');
        const endInput = document.getElementById('endDate');
        
        if (isAllDay) {
            startInput.type = 'date';
            endInput.type = 'date';
        } else {
            startInput.type = 'datetime-local';
            endInput.type = 'datetime-local';
        }
    }

    formatDateForInput(date, dateOnly = false) {
        if (!date) return '';
        
        const d = new Date(date);
        if (dateOnly) {
            return d.toISOString().split('T')[0];
        } else {
            return d.toISOString().slice(0, 16);
        }
    }

    toggleCalendarVisibility(checkbox) {
        // Implementar filtrado de eventos por calendario
        this.calendar.refetchEvents();
    }

    async loadCalendars() {
        // Cargar lista de calendarios y grupos disponibles para el usuario
        try {
            const response = await axios.get('/calendar/api/calendars');
            
            if (response.data.success) {
                this.calendars = response.data.calendars;
                this.populateCalendarSelect(this.calendars);
                this.renderCalendarSidebar(this.calendars);
            } else {
                console.error('Error al cargar calendarios:', response.data.message);
                this.showAlert('warning', 'No se pudieron cargar los calendarios');
            }
        } catch (error) {
            console.error('Error al cargar calendarios:', error);
            this.showAlert('danger', 'Error de conexión al cargar calendarios');
        }
    }

    populateCalendarSelect(calendars) {
        const select = document.getElementById('eventCalendar');
        if (!select) return;
        
        select.innerHTML = '<option value="">Seleccionar calendario</option>';
        
        calendars.forEach(item => {
            const option = document.createElement('option');
            option.value = item.unique_id; // Use unique_id (calendar_1, group_2, etc.)
            
            // Show type and permission in the dropdown
            const typeLabel = item.type === 'calendar' ? '📅' : '👥';
            const permissionLabel = item.permission_level === 'owner' ? ' (Propietario)' : ' (Acceso)';
            option.textContent = `${typeLabel} ${item.name}${permissionLabel}`;
            
            // Apply color if available
            if (item.color) {
                option.style.color = item.color;
            }
            
            select.appendChild(option);
        });
    }
    
    renderCalendarSidebar(items) {
        const myCalendarsContainer = document.getElementById('myCalendars');
        const sharedCalendarsContainer = document.getElementById('sharedCalendars');
        const groupCalendarsContainer = document.getElementById('groupCalendars');
        
        if (!myCalendarsContainer || !sharedCalendarsContainer || !groupCalendarsContainer) return;
        
        // Clear containers
        myCalendarsContainer.innerHTML = '';
        sharedCalendarsContainer.innerHTML = '';
        groupCalendarsContainer.innerHTML = '';
        
        items.forEach(item => {
            const calendarItem = this.createSidebarItem(item);
            
            if (item.type === 'calendar' && item.permission_level === 'owner') {
                myCalendarsContainer.appendChild(calendarItem);
            } else if (item.type === 'calendar' && item.permission_level === 'shared') {
                sharedCalendarsContainer.appendChild(calendarItem);
            } else if (item.type === 'group') {
                groupCalendarsContainer.appendChild(calendarItem);
            }
        });
    }
    
    createSidebarItem(item) {
        const calendarItem = document.createElement('div');
        calendarItem.className = 'calendar-item';
        
        const itemColor = item.color || '#007bff';
        
        calendarItem.innerHTML = `
            <div class="form-check">
                <input class="form-check-input calendar-checkbox" 
                       type="checkbox" 
                       id="item_${item.unique_id}" 
                       data-item-id="${item.unique_id}"
                       data-item-type="${item.type}"
                       checked>
                <label class="form-check-label d-flex align-items-center justify-content-between" 
                       for="item_${item.unique_id}">
                    <div class="d-flex align-items-center">
                        <span class="calendar-color me-2" style="background-color: ${itemColor};"></span>
                        <span class="calendar-name">${item.name}</span>
                    </div>
                    <small class="text-muted">${item.type === 'calendar' ? '📅' : '👥'}</small>
                </label>
            </div>
        `;
        
        return calendarItem;
    }

    async loadEvents() {
        // Los eventos se cargan automáticamente por FullCalendar
    }

    async logout() {
        try {
            await axios.post('/calendar/api/auth/logout');
            window.location.href = 'index.html';
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            window.location.href = 'index.html';
        }
    }

    showAlert(type, message) {
        // Crear y mostrar alerta temporal
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.calendarApp = new CalendarApp();
});
