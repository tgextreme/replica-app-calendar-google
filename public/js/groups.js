// Groups.js - Gestión de grupos de usuarios
class GroupsManager {
    constructor() {
        this.currentGroupId = null;
        this.groups = [];
        this.initializeEventListeners();
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
        const modal = new bootstrap.Modal(document.getElementById('groupsModal'));
        modal.show();
        
        // Cargar grupos del usuario
        await this.loadUserGroups();
        this.showEmptyState();
    }

    async loadUserGroups() {
        try {
            const response = await axios.get('/calendar/api/group');
            
            if (response.data.success) {
                this.groups = response.data.groups;
                this.renderGroupsList();
            } else {
                this.showAlert('danger', 'Error al cargar grupos');
            }
        } catch (error) {
            console.error('Error al cargar grupos:', error);
            this.showAlert('danger', 'Error al cargar grupos');
        }
    }

    renderGroupsList() {
        const container = document.getElementById('groupsList');
        
        if (this.groups.length === 0) {
            container.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-people"></i><br>
                    <small>No tienes grupos aún</small>
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

        // Mostrar/ocultar botón añadir miembro según permisos
        const addBtn = document.getElementById('addMemberBtn');
        addBtn.style.display = ['admin', 'moderator'].includes(group.role) ? 'block' : 'none';
    }

    showEmptyState() {
        document.getElementById('groupDetailsContainer').style.display = 'none';
        document.getElementById('groupEmptyState').style.display = 'block';
    }

    async loadGroupMembers(groupId) {
        try {
            const response = await axios.get(`/calendar/api/group/members?group_id=${groupId}`);
            
            if (response.data.success) {
                this.renderGroupMembers(response.data.members);
            }
        } catch (error) {
            console.error('Error al cargar miembros:', error);
        }
    }

    renderGroupMembers(members) {
        const tbody = document.getElementById('groupMembersTable');
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
            const response = await axios.post('/calendar/api/group', groupData);
            
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
            const response = await axios.post('/calendar/api/group/members', memberData);
            
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
                const response = await axios.put('/calendar/api/group/members', {
                    group_id: this.currentGroupId,
                    member_id: memberId,
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
                const response = await axios.delete('/calendar/api/group/members', {
                    data: {
                        group_id: this.currentGroupId,
                        member_id: memberId
                    }
                });
                
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
        if (!this.currentGroupId) return;

        try {
            const response = await axios.get(`/calendar/api/group/search?q=${encodeURIComponent(query)}&group_id=${this.currentGroupId}`);
            
            if (response.data.success) {
                this.displayUserSearchResults(response.data.users);
            }
        } catch (error) {
            console.error('Error searching users:', error);
        }
    }

    displayUserSearchResults(users) {
        const resultsContainer = document.getElementById('userSearchResults');
        resultsContainer.innerHTML = '';

        if (users.length === 0) {
            resultsContainer.innerHTML = '<div class="p-3 text-muted">No se encontraron usuarios</div>';
            resultsContainer.classList.add('show');
            return;
        }

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
