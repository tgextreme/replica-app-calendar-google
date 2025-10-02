// Calendar.js - Funcionalidades del calendario web
class CalendarApp {
    constructor() {
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
            const response = await axios.get('/calendar/public/api/auth/me');
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
            
            const response = await axios.get(`/calendar/api/events?start=${start}&end=${end}`);
            
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
                start_datetime: event.start.toISOString().replace('T', ' ').replace('Z', ''),
                end_datetime: event.end ? event.end.toISOString().replace('T', ' ').replace('Z', '') : null,
                is_all_day: event.allDay
            };

            const response = await axios.put(`/calendar/api/events/${event.id}`, eventData);
            
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
        
        const eventData = {
            calendar_id: formData.get('calendar_id') || 1, // Default calendar
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
                response = await axios.put(`/calendar/api/events/${eventId}`, eventData);
            } else {
                response = await axios.post('/calendar/api/events', eventData);
            }

            if (response.data.success) {
                this.showAlert('success', eventId ? 'Evento actualizado' : 'Evento creado');
                this.calendar.refetchEvents();
                bootstrap.Modal.getInstance(document.getElementById('eventModal')).hide();
            } else {
                this.showAlert('danger', response.data.message);
            }
        } catch (error) {
            this.showAlert('danger', 'Error al guardar evento');
        }
    }

    async handleEventDelete() {
        const eventId = document.getElementById('eventId').value;
        
        if (!eventId) return;
        
        if (confirm('¿Estás seguro de que quieres eliminar este evento?')) {
            try {
                const response = await axios.delete(`/calendar/api/events/${eventId}`);
                
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
        // Cargar lista de calendarios disponibles para el usuario
        try {
            // Por ahora usar calendarios estáticos, implementar endpoint después
            const calendars = [
                { id: 1, name: 'Mi Calendario', color: '#007bff' },
                { id: 2, name: 'Cumpleaños', color: '#e74c3c' },
                { id: 3, name: 'Tasks', color: '#f39c12' },
                { id: 4, name: 'Festivos', color: '#27ae60' }
            ];
            
            this.populateCalendarSelect(calendars);
        } catch (error) {
            console.error('Error al cargar calendarios:', error);
        }
    }

    populateCalendarSelect(calendars) {
        const select = document.getElementById('eventCalendar');
        select.innerHTML = '<option value="">Seleccionar calendario</option>';
        
        calendars.forEach(calendar => {
            const option = document.createElement('option');
            option.value = calendar.id;
            option.textContent = calendar.name;
            option.style.color = calendar.color;
            select.appendChild(option);
        });
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
    new CalendarApp();
});