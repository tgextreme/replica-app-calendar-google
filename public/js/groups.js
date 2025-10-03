// Groups.js - Gestión de grupos de usuarios
class GroupsManager {
    constructor() {
        console.log('GroupsManager: Initializing...');
        this.currentGroupId = null;
        this.groups = [];
        this.isAdmin = false;
        this.initializeEventListeners();
        console.log('GroupsManager: Initialized successfully');
    }

    initializeEventListeners() {
        // Botón para abrir modal de grupos
        document.getElementById('groupsBtn').addEventListener('click', () => {
            this.openGroupsModal();
        });

        // Crear nuevo grupo
        document.getElementById('createGroupBtn').addEventListener('click', () => {
            this.openGroupForm();
        });

        // Formulario de grupo
        document.getElementById('groupForm').addEventListener('submit', (e) => {
            this.handleGroupSubmit(e);
        });

        // Añadir miembro
        document.getElementById('addMemberBtn').addEventListener('click', () => {
            this.openAddMemberModal();
        });

        // Formulario añadir miembro
        document.getElementById('addMemberForm').addEventListener('submit', (e) => {
            this.handleAddMemberSubmit(e);
        });
    }

    async openGroupsModal() {
        console.log('GroupsManager: Opening groups modal...');
        const modal = new bootstrap.Modal(document.getElementById('groupsModal'));
        modal.show();
        
        // Cargar grupos del usuario
        console.log('GroupsManager: Loading groups for modal...');
        await this.loadUserGroups();
        this.showEmptyState();
    }

    async loadUserGroups() {
        try {
            console.log('GroupsManager: Loading user groups...');
            console.log('GroupsManager: Making request to:', '/calendar/api/groups');
            console.log('GroupsManager: Axios defaults:', {
                withCredentials: axios.defaults.withCredentials,
                baseURL: axios.defaults.baseURL,
                headers: axios.defaults.headers
            });
            
            const response = await axios.get('/calendar/api/groups');
            console.log('GroupsManager: Groups API response:', response.data);
            console.log('GroupsManager: Response status:', response.status);
            
            if (response.data.success) {
                this.groups = response.data.groups;
                this.isAdmin = response.data.isAdmin || false;
                console.log('GroupsManager: Loaded groups:', this.groups);
                console.log('GroupsManager: User is admin:', this.isAdmin);
                this.renderGroupsList();
                this.updateUIForRole();
            } else {
                console.error('GroupsManager: API returned error:', response.data.message);
                this.showAlert('danger', 'Error al cargar grupos: ' + response.data.message);
            }
        } catch (error) {
            console.error('GroupsManager: Exception loading groups:', error);
            console.error('GroupsManager: Error config:', error.config);
            console.error('GroupsManager: Error request:', error.request);
            
            if (error.response) {
                console.error('GroupsManager: Error response status:', error.response.status);
                console.error('GroupsManager: Error response headers:', error.response.headers);
                console.error('GroupsManager: Error response data:', error.response.data);
                console.error('GroupsManager: Error response URL:', error.response.config?.url);
                
                const errorMsg = error.response.data?.message || error.response.statusText || 'Error desconocido';
                this.showAlert('danger', `Error al cargar grupos (${error.response.status}): ${errorMsg}`);
            } else if (error.request) {
                console.error('GroupsManager: Request was made but no response:', error.request);
                this.showAlert('danger', 'Error de red al cargar grupos: No se recibió respuesta del servidor');
            } else {
                console.error('GroupsManager: Error setting up request:', error.message);
                this.showAlert('danger', 'Error al cargar grupos: ' + error.message);
            }
        }
    }

    updateUIForRole() {
        const createBtn = document.getElementById('createGroupBtn');
        if (createBtn) {
            if (this.isAdmin) {
                createBtn.style.display = 'block';
                createBtn.title = 'Crear nuevo grupo';
            } else {
                createBtn.style.display = 'none';
            }
        }
    }

    renderGroupsList() {
        const container = document.getElementById('groupsList');
        
        if (this.groups.length === 0) {
            const noGroupsMsg = this.isAdmin ? 
                'No hay grupos creados aún' : 
                'No perteneces a ningún grupo';
            
            container.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-people"></i><br>
                    <small>${noGroupsMsg}</small>
                </div>
            `;
            return;
        }

        container.innerHTML = this.groups.map(group => `
            <div class="list-group-item list-group-item-action" 
                 data-group-id="${group.id}" 
                 onclick="groupsManager.selectGroup(${group.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">
                            <span class="badge me-2" style="background-color: ${group.color};">&nbsp;</span>
                            ${group.name}
                        </h6>
                        <small class="text-muted">${group.member_count} miembro(s)</small>
                    </div>
                    <small class="text-muted">${this.getRoleBadge(group.role)}</small>
                </div>
                ${group.description ? `<p class="mb-1 small">${group.description}</p>` : ''}
            </div>
        `).join('');
    }

    async selectGroup(groupId) {
        this.currentGroupId = groupId;
        const group = this.groups.find(g => g.id == groupId);
        
        if (!group) return;

        // Actualizar UI
        document.querySelectorAll('#groupsList .list-group-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-group-id="${groupId}"]`).classList.add('active');

        // Mostrar detalles del grupo
        this.showGroupDetails(group);
        
        // Cargar miembros
        await this.loadGroupMembers(groupId);
    }

    showGroupDetails(group) {
        document.getElementById('groupDetailsContainer').style.display = 'block';
        document.getElementById('groupEmptyState').style.display = 'none';
        
        document.getElementById('groupDetailsTitle').textContent = `Grupo: ${group.name}`;
        document.getElementById('selectedGroupName').textContent = group.name;
        document.getElementById('selectedGroupDescription').textContent = group.description || 'Sin descripción';
        
        const colorBadge = document.getElementById('selectedGroupColor');
        colorBadge.style.backgroundColor = group.color;
        colorBadge.textContent = group.name;
        
        document.getElementById('selectedGroupMembers').textContent = `${group.member_count} miembro(s) - Creado por ${group.created_by_name}`;

        // Mostrar/ocultar botones según permisos (solo admins globales)
        const addBtn = document.getElementById('addMemberBtn');
        if (addBtn) {
            addBtn.style.display = this.isAdmin ? 'block' : 'none';
        }
    }

    showEmptyState() {
        document.getElementById('groupDetailsContainer').style.display = 'none';
        document.getElementById('groupEmptyState').style.display = 'block';
    }

    async loadGroupMembers(groupId) {
        try {
            const response = await axios.get(`/calendar/api/groups/${groupId}`);
            
            if (response.data.success) {
                this.renderGroupMembers(response.data.group.members);
            }
        } catch (error) {
            console.error('Error al cargar miembros:', error);
        }
    }

    renderGroupMembers(members) {
        const tbody = document.getElementById('groupMembersTable');
        const membersSection = document.querySelector('.members-section');
        
        if (!this.isAdmin) {
            // Los usuarios normales no ven la lista de miembros
            if (membersSection) {
                membersSection.style.display = 'none';
            }
            return;
        }
        
        // Solo los admins ven los miembros
        if (membersSection) {
            membersSection.style.display = 'block';
        }
        
        const currentGroup = this.groups.find(g => g.id == this.currentGroupId);
        
        tbody.innerHTML = members.map(member => `
            <tr>
                <td>
                    <div>
                        <strong>${member.full_name}</strong><br>
                        <small class="text-muted">@${member.username}</small>
                    </div>
                </td>
                <td>${member.email}</td>
                <td>${this.getRoleBadge(member.role)}</td>
                <td>
                    <small class="text-muted">
                        ${new Date(member.joined_at).toLocaleDateString('es-ES')}
                    </small>
                </td>
                <td>
                    ${this.renderMemberActions(member, currentGroup)}
                </td>
            </tr>
        `).join('');
    }

    renderMemberActions(member, currentGroup) {
        // Solo admins pueden gestionar miembros
        if (currentGroup.role !== 'admin') {
            return '<small class="text-muted">Sin permisos</small>';
        }

        // No permitir acciones sobre el creador
        if (member.role === 'admin' && currentGroup.created_by == member.id) {
            return '<small class="text-muted">Creador</small>';
        }

        return `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary btn-sm" 
                        onclick="groupsManager.changeRole(${member.id}, '${member.role}')"
                        title="Cambiar rol">
                    <i class="bi bi-gear"></i>
                </button>
                <button class="btn btn-outline-danger btn-sm" 
                        onclick="groupsManager.removeMember(${member.id}, '${member.full_name}')"
                        title="Eliminar del grupo">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
    }

    getRoleBadge(role) {
        const badges = {
            'admin': '<span class="badge bg-danger">Admin</span>',
            'moderator': '<span class="badge bg-warning">Moderador</span>',
            'member': '<span class="badge bg-secondary">Miembro</span>'
        };
        return badges[role] || badges.member;
    }

    openGroupForm(group = null) {
        const modal = new bootstrap.Modal(document.getElementById('groupFormModal'));
        const form = document.getElementById('groupForm');
        
        // Reset form
        form.reset();
        document.getElementById('groupColor').value = '#007bff';
        
        if (group) {
            document.getElementById('groupFormTitle').textContent = 'Editar Grupo';
            document.getElementById('groupName').value = group.name;
            document.getElementById('groupDescription').value = group.description || '';
            document.getElementById('groupColor').value = group.color;
        } else {
            document.getElementById('groupFormTitle').textContent = 'Nuevo Grupo';
        }
        
        modal.show();
    }

    async handleGroupSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const groupData = {
            name: document.getElementById('groupName').value.trim(),
            description: document.getElementById('groupDescription').value.trim(),
            color: document.getElementById('groupColor').value
        };

        if (!groupData.name) {
            this.showAlert('warning', 'El nombre del grupo es requerido');
            return;
        }

        try {
            const response = await axios.post('/calendar/api/groups', groupData);
            
            if (response.data.success) {
                this.showAlert('success', 'Grupo creado correctamente');
                bootstrap.Modal.getInstance(document.getElementById('groupFormModal')).hide();
                await this.loadUserGroups();
            } else {
                this.showAlert('danger', response.data.error || 'Error al crear grupo');
            }
        } catch (error) {
            console.error('Error al crear grupo:', error);
            this.showAlert('danger', 'Error al crear grupo');
        }
    }

    openAddMemberModal() {
        if (!this.currentGroupId) return;
        
        const modal = new bootstrap.Modal(document.getElementById('addMemberModal'));
        document.getElementById('addMemberForm').reset();
        document.getElementById('selectedUserId').value = '';
        this.clearUserSearchResults();
        this.initializeUserSearch();
        modal.show();
    }

    async handleAddMemberSubmit(e) {
        e.preventDefault();
        
        const selectedUserId = document.getElementById('selectedUserId').value;
        const searchValue = document.getElementById('memberSearch').value.trim();

        if (!selectedUserId) {
            this.showAlert('warning', 'Selecciona un usuario de la lista');
            return;
        }

        const memberData = {
            group_id: this.currentGroupId,
            user_id: selectedUserId,
            role: document.getElementById('memberRole').value
        };

        try {
            const response = await axios.post(`/calendar/api/groups/${this.currentGroupId}/members`, memberData);
            
            if (response.data.success) {
                this.showAlert('success', 'Miembro añadido correctamente');
                bootstrap.Modal.getInstance(document.getElementById('addMemberModal')).hide();
                await this.loadGroupMembers(this.currentGroupId);
            } else {
                this.showAlert('danger', response.data.error || 'Error al añadir miembro');
            }
        } catch (error) {
            console.error('Error al añadir miembro:', error);
            const message = error.response?.data?.error || 'Error al añadir miembro';
            this.showAlert('danger', message);
        }
    }

    async changeRole(memberId, currentRole) {
        const roles = ['member', 'moderator', 'admin'];
        const currentIndex = roles.indexOf(currentRole);
        const nextRole = roles[(currentIndex + 1) % roles.length];
        
        if (confirm(`¿Cambiar rol a "${nextRole}"?`)) {
            try {
                const response = await axios.put(`/calendar/api/groups/${this.currentGroupId}/members/${memberId}`, {
                    role: nextRole
                });
                
                if (response.data.success) {
                    this.showAlert('success', 'Rol actualizado');
                    await this.loadGroupMembers(this.currentGroupId);
                }
            } catch (error) {
                this.showAlert('danger', 'Error al cambiar rol');
            }
        }
    }

    async removeMember(memberId, memberName) {
        if (confirm(`¿Eliminar a "${memberName}" del grupo?`)) {
            try {
                const response = await axios.delete(`/calendar/api/groups/${this.currentGroupId}/members/${memberId}`);
                
                if (response.data.success) {
                    this.showAlert('success', 'Miembro eliminado del grupo');
                    await this.loadGroupMembers(this.currentGroupId);
                    await this.loadUserGroups(); // Actualizar conteo
                }
            } catch (error) {
                this.showAlert('danger', 'Error al eliminar miembro');
            }
        }
    }

    showAlert(type, message) {
        // Reutilizar la función de alertas del calendario principal
        if (typeof window.calendarApp !== 'undefined' && window.calendarApp.showAlert) {
            window.calendarApp.showAlert(type, message);
        } else {
            // Fallback si no está disponible
            alert(message);
        }
    }

    // Métodos para autocompletado de usuarios
    initializeUserSearch() {
        const searchInput = document.getElementById('memberSearch');
        const resultsContainer = document.getElementById('userSearchResults');
        let searchTimeout;

        // Evento de entrada de texto
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                this.clearUserSearchResults();
                return;
            }

            // Debounce para evitar demasiadas búsquedas
            searchTimeout = setTimeout(() => {
                this.searchUsers(query);
            }, 300);
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                this.clearUserSearchResults();
            }
        });

        // Navegación con teclado
        searchInput.addEventListener('keydown', (e) => {
            const items = resultsContainer.querySelectorAll('.user-search-item');
            const activeItem = resultsContainer.querySelector('.user-search-item.active');
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (items.length > 0) {
                        if (!activeItem) {
                            items[0].classList.add('active');
                        } else {
                            activeItem.classList.remove('active');
                            const next = activeItem.nextElementSibling || items[0];
                            next.classList.add('active');
                        }
                    }
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    if (items.length > 0) {
                        if (!activeItem) {
                            items[items.length - 1].classList.add('active');
                        } else {
                            activeItem.classList.remove('active');
                            const prev = activeItem.previousElementSibling || items[items.length - 1];
                            prev.classList.add('active');
                        }
                    }
                    break;
                    
                case 'Enter':
                    e.preventDefault();
                    if (activeItem) {
                        this.selectUser(
                            activeItem.dataset.userId,
                            activeItem.dataset.userName,
                            activeItem.dataset.userEmail
                        );
                    }
                    break;
                    
                case 'Escape':
                    this.clearUserSearchResults();
                    break;
            }
        });
    }

    async searchUsers(query) {
        if (!this.currentGroupId) {
            console.log('GroupsManager: No currentGroupId set for search');
            return;
        }

        try {
            const url = `/calendar/api/groups/search?q=${encodeURIComponent(query)}&group_id=${this.currentGroupId}`;
            console.log('GroupsManager: Searching users with URL:', url);
            
            const response = await axios.get(url);
            console.log('GroupsManager: User search response:', response.data);
            
            if (response.data.success) {
                console.log('GroupsManager: Found users:', response.data.users);
                this.displayUserSearchResults(response.data.users);
            } else {
                console.error('GroupsManager: User search failed:', response.data.message);
            }
        } catch (error) {
            console.error('GroupsManager: Error searching users:', error);
            if (error.response) {
                console.error('GroupsManager: Search error response:', error.response.data);
            }
        }
    }

    displayUserSearchResults(users) {
        console.log('GroupsManager: Displaying user search results:', users);
        const resultsContainer = document.getElementById('userSearchResults');
        
        if (!resultsContainer) {
            console.error('GroupsManager: userSearchResults container not found');
            return;
        }
        
        resultsContainer.innerHTML = '';

        if (users.length === 0) {
            console.log('GroupsManager: No users found');
            resultsContainer.innerHTML = '<div class="p-3 text-muted">No se encontraron usuarios</div>';
            resultsContainer.classList.add('show');
            return;
        }

        console.log('GroupsManager: Creating user items for', users.length, 'users');
        users.forEach(user => {
            const initials = this.getInitials(user.full_name);
            const item = document.createElement('div');
            item.className = 'user-search-item d-flex align-items-center';
            item.dataset.userId = user.id;
            item.dataset.userName = user.full_name;
            item.dataset.userEmail = user.email;
            
            item.innerHTML = `
                <div class="user-initials">${initials}</div>
                <div class="flex-grow-1">
                    <div class="user-name">${user.full_name}</div>
                    <div class="user-email">${user.email}</div>
                </div>
            `;

            item.addEventListener('click', () => {
                this.selectUser(user.id, user.full_name, user.email);
            });

            resultsContainer.appendChild(item);
        });

        resultsContainer.classList.add('show');
    }

    selectUser(userId, userName, userEmail) {
        document.getElementById('memberSearch').value = `${userName} <${userEmail}>`;
        document.getElementById('selectedUserId').value = userId;
        this.clearUserSearchResults();
    }

    clearUserSearchResults() {
        const resultsContainer = document.getElementById('userSearchResults');
        resultsContainer.innerHTML = '';
        resultsContainer.classList.remove('show');
    }

    getInitials(fullName) {
        if (!fullName) return '??';
        return fullName
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.groupsManager = new GroupsManager();
});
