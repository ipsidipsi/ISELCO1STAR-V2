<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/dashboard"></ion-back-button>
        </ion-buttons>
        <ion-title>User Management</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="showCreateModal = true" fill="solid" color="success" class="add-user-button">
            <ion-icon slot="start" :icon="addOutline"></ion-icon>
            Add User
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      
      <!-- Search and Filter Bar -->
      <ion-toolbar>
        <ion-searchbar v-model="searchQuery" placeholder="Search users..." @ionInput="handleSearch"></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <!-- Filter Chips -->
      <div class="filter-chips">
        <ion-chip v-for="status in statuses" :key="status.value" 
                  :color="selectedStatus === status.value ? 'primary' : ''"
                  @click="selectedStatus = status.value">
          {{ status.label }}
        </ion-chip>
      </div>

      <!-- Users List -->
      <ion-list>
        <ion-item v-for="user in filteredUsers" :key="user.id" button @click="viewUser(user)">
          <ion-avatar slot="start">
            <div class="avatar-placeholder">{{ user.employee_name?.charAt(0) || 'U' }}</div>
          </ion-avatar>
          
          <ion-label>
            <h2>{{ user.employee_name || user.username }}</h2>
            <p>@{{ user.username }}</p>
            <div class="user-meta">
              <!-- Permanent Roles -->
              <ion-badge v-for="role in user.roles" :key="role.id" 
                        :color="role.slug === 'superadmin' ? 'danger' : role.slug === 'department_admin' ? 'warning' : 'medium'">
                {{ role.name }}
              </ion-badge>
              
              <!-- Temporary Roles (if loaded) -->
              <ion-badge v-for="tempRole in (user.temporaryRoles || user.temporary_roles)" :key="'temp-'+tempRole.id" 
                        color="warning"
                        class="temp-role-badge">
                {{ tempRole.name }} (Temp)
                <ion-icon :icon="timeOutline" class="temp-icon"></ion-icon>
              </ion-badge>
              
              <!-- Status Badge -->
              <ion-badge :color="getStatusColor(user.status)">
                {{ user.status }}
              </ion-badge>
            </div>
          </ion-label>

          <ion-buttons slot="end">
            <ion-button @click.stop="openUserMenu(user, $event)">
              <ion-icon slot="icon-only" :icon="ellipsisVerticalOutline"></ion-icon>
            </ion-button>
          </ion-buttons>
        </ion-item>
      </ion-list>

      <ion-infinite-scroll @ionInfinite="loadMore" :disabled="!hasMore">
        <ion-infinite-scroll-content></ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </ion-content>

    <!-- Create User Modal -->
    <ion-modal :is-open="showCreateModal" @didDismiss="showCreateModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Create User</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showCreateModal = false">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <ion-item>
          <ion-label position="stacked">Username *</ion-label>
          <ion-input v-model="newUser.username" placeholder="Enter username"></ion-input>
        </ion-item>
        
        <ion-item>
          <ion-label position="stacked">Employee Name *</ion-label>
          <ion-input v-model="newUser.employee_name" placeholder="Enter full name"></ion-input>
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Mobile Number</ion-label>
          <ion-input v-model="newUser.mobile_number" type="tel" placeholder="Enter mobile"></ion-input>
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Department</ion-label>
          <ion-select v-model="newUser.department_id" placeholder="Select department">
            <ion-select-option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </ion-select-option>
          </ion-select>
        </ion-item>


        <ion-item>
          <ion-label position="stacked">Password *</ion-label>
          <ion-input v-model="newUser.password" type="password" placeholder="Enter password"></ion-input>
          <ion-note slot="helper">Minimum 4 characters</ion-note>
        </ion-item>

        <ion-item>
          <ion-label position="stacked">Confirm Password *</ion-label>
          <ion-input v-model="newUser.confirm_password" type="password" placeholder="Re-enter password"></ion-input>
          <ion-note slot="error" v-if="passwordMismatch">Passwords do not match</ion-note>
        </ion-item>

        <ion-item>
          <ion-label>Roles *</ion-label>
        </ion-item>
        <ion-list>
          <ion-item v-for="role in roles" :key="role.id">
            <ion-label>{{ role.name }}</ion-label>
            <ion-checkbox slot="end" :checked="selectedRoles.includes(role.id)" @ionChange="toggleRole(role.id)"></ion-checkbox>
          </ion-item>
        </ion-list>

        <ion-button expand="block" @click="createUser" class="ion-margin-top">
          Create User
        </ion-button>
      </ion-content>
    </ion-modal>

    <!-- Edit User Modal -->
    <ion-modal :is-open="showEditModal" @didDismiss="showEditModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Edit User</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showEditModal = false">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <div v-if="editingUser">
          <ion-item>
            <ion-label position="stacked">Username</ion-label>
            <ion-input v-model="editingUser.username" placeholder="Enter username" disabled></ion-input>
            <ion-note slot="helper">Username cannot be changed</ion-note>
          </ion-item>
          
          <ion-item>
            <ion-label position="stacked">Employee Name *</ion-label>
            <ion-input v-model="editingUser.employee_name" placeholder="Enter full name"></ion-input>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Mobile Number</ion-label>
            <ion-input v-model="editingUser.mobile_number" type="tel" placeholder="Enter mobile"></ion-input>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Primary Department</ion-label>
            <ion-select v-model="editingUser.department_id" placeholder="Select department">
              <ion-select-option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-label>Roles</ion-label>
          </ion-item>
          <ion-list>
            <ion-item v-for="role in roles" :key="role.id">
              <ion-label>{{ role.name }}</ion-label>
              <ion-checkbox 
                slot="end" 
                :checked="editUserRoles.includes(role.id)" 
                @ionChange="toggleEditUserRole(role.id)"
              ></ion-checkbox>
            </ion-item>
          </ion-list>

          <ion-item lines="none" class="ion-margin-top">
            <ion-label>Status: <strong>{{ editingUser.status }}</strong></ion-label>
          </ion-item>

          <div class="button-group ion-margin-top">
            <ion-button expand="block" @click="updateUser" color="primary">
              Save Changes
            </ion-button>
            <ion-button expand="block" @click="showEditModal = false" fill="outline">
              Cancel
            </ion-button>
          </div>
        </div>
      </ion-content>
    </ion-modal>

    <!-- Temporary Role Assignment Modal -->
    <ion-modal :is-open="showTempRoleModal" @didDismiss="showTempRoleModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Assign Temporary Role (OIC)</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showTempRoleModal = false">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <div v-if="tempRoleUser">
          <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">
              <strong>{{ tempRoleUser.employee_name || tempRoleUser.username }}</strong>
            </p>
            <p class="text-xs text-blue-600">Officer in Charge (Temporary Role Assignment)</p>
          </div>

          <ion-item>
            <ion-label position="stacked">Role *</ion-label>
            <ion-select v-model="tempRoleForm.role_id" placeholder="Select temporary role">
              <ion-select-option v-for="role in availableTempRoles" :key="role.id" :value="role.id">
                {{ role.name }}
              </ion-select-option>
            </ion-select>
            <ion-note slot="helper">Department admins cannot assign superadmin role</ion-note>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Expiry Date & Time *</ion-label>
            <ion-datetime 
              v-model="tempRoleForm.expires_at"
              presentation="date-time"
              :min="minDate"
              display-format="MMM DD, YYYY HH:mm"
            ></ion-datetime>
          </ion-item>

          <ion-item>
            <ion-label position="stacked">Reason</ion-label>
            <ion-textarea 
              v-model="tempRoleForm.reason" 
              placeholder="e.g., OIC while manager on vacation"
              :rows="3"
            ></ion-textarea>
          </ion-item>

          <div class="button-group ion-margin-top">
            <ion-button expand="block" @click="assignTemporaryRole" color="warning">
              Assign Temporary Role
            </ion-button>
            <ion-button expand="block" @click="showTempRoleModal = false" fill="outline">
              Cancel
            </ion-button>
          </div>
        </div>
      </ion-content>
    </ion-modal>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
  IonButton, IonIcon, IonList, IonItem, IonLabel, IonAvatar, IonBadge,
  IonSearchbar, IonChip, IonRefresher, IonRefresherContent,
  IonInfiniteScroll, IonInfiniteScrollContent, IonModal, IonInput, IonSelect,
  IonSelectOption, IonCheckbox, IonNote, IonTextarea, IonDatetime, IonDatetimeButton,
  actionSheetController, toastController, loadingController
} from '@ionic/vue';
import { addOutline, ellipsisVerticalOutline, personRemove, lockClosed, checkmarkCircle, timeOutline } from 'ionicons/icons';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const searchQuery = ref('');
const selectedStatus = ref('all');
const users = ref<any[]>([]);
const departments = ref<any[]>([]);
const roles = ref<any[]>([]);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showTempRoleModal = ref(false);
const hasMore = ref(false);

//Edit user states
const editingUser = ref<any | null>(null);
const editUserRoles = ref<number[]>([]);

//Temporary role states
const tempRoleUser = ref<any | null>(null);
const tempRoleForm = ref({
  role_id: null as number | null,
  expires_at: '',
  reason: ''
});
const minDate = new Date().toISOString();

const statuses = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'On Leave', value: 'on_leave' },
  { label: 'Retired', value: 'retired' },
  { label: 'Terminated', value: 'terminated' }
];

const newUser = ref({
  username: '',
  employee_name: '',
  mobile_number: '',
  department_id: null,
  password: '',
  confirm_password: '',
});

const selectedRoles = ref<number[]>([]);

const passwordMismatch = computed(() => {
  if (!newUser.value.confirm_password) return false;
  return newUser.value.password !== newUser.value.confirm_password;
});

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = !searchQuery.value || 
      user.employee_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.username?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesStatus = selectedStatus.value === 'all' || user.status === selectedStatus.value;
    
    return matchesSearch && matchesStatus;
  });
});

// Filter temp roles based on current user's permissions
const availableTempRoles = computed(() => {
  const authStore = useAuthStore();
  const currentUser = authStore.user;
  
  // Superadmin can assign any role
  if (currentUser?.roles?.some((r: any) => r.slug === 'superadmin')) {
    return roles.value;
  }
  
  // Department admins cannot assign superadmin role
  return roles.value.filter((role: any) => role.slug !== 'superadmin');
});

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    active: 'success',
    suspended: 'warning',
    on_leave: 'tertiary',
    retired: 'medium',
    terminated: 'danger'
  };
  return colors[status] || 'medium';
};

const loadUsers = async () => {
  const loading = await loadingController.create({ message: 'Loading users...' });
  await loading.present();

  try {
    const response = await api.get('/users');
    users.value = response.data.data || response.data;
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to load users',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    await loading.dismiss();
  }
};

const loadDepartments = async () => {
  try {
    const response = await api.get('/departments');
    departments.value = response.data;
  } catch (error) {
    console.error('Failed to load departments', error);
  }
};

const loadRoles = async () => {
  try {
    const response = await api.get('/roles');
    roles.value = response.data;
  } catch (error) {
    console.error('Failed to load roles', error);
  }
};

const createUser = async () => {
  // Validate passwords
  if (!newUser.value.password || newUser.value.password.length < 4) {
    const toast = await toastController.create({
      message: 'Password must be at least 4 characters',
      duration: 3000,
      color: 'warning'
    });
    await toast.present();
    return;
  }

  if (newUser.value.password !== newUser.value.confirm_password) {
    const toast = await toastController.create({
      message: 'Passwords do not match',
      duration: 3000,
      color: 'warning'
    });
    await toast.present();
    return;
  }

  const loading = await loadingController.create({ message: 'Creating user...' });
  await loading.present();

  try {
    // Don't send confirm_password to API
    const { confirm_password, ...userData } = newUser.value;
    
    await api.post('/users', {
      ...userData,
      role_ids: selectedRoles.value
    });

    const toast = await toastController.create({
      message: 'User created successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();

    showCreateModal.value = false;
    newUser.value = { 
      username: '', 
      employee_name: '', 
      mobile_number: '', 
      department_id: null, 
      password: '',
      confirm_password: ''
    };
    selectedRoles.value = [];
    await loadUsers();
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to create user',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    await loading.dismiss();
  }
};

const openUserMenu = async (user: any, event: Event) => {
  // Check if user has temporary roles
  const hasTempRoles = (user.temporaryRoles || user.temporary_roles || []).length > 0;
  
  // Build buttons array conditionally
  const buttons = [
    {
      text: 'Edit',
      icon: 'create-outline',
      handler: () => viewUser(user)
    },
    {
      text: 'Reset Password',
      icon: 'key-outline',
      handler: () => resetUserPassword(user.id)
    }
  ];

  // Add either Assign or Revoke based on temp role status
  if (hasTempRoles) {
    buttons.push({
      text: 'Revoke Temporary Role',
      icon: 'close-circle-outline',
      handler: () => revokeTemporaryRole(user)
    });
  } else {
    buttons.push({
      text: 'Assign Temporary Role',
      icon: 'time-outline',
      handler: () => openTempRoleModal(user)
    });
  }

  // Add remaining buttons
  buttons.push(
    {
      text: 'Suspend',
      icon: lockClosed,
      handler: () => suspendUser(user.id)
    },
    {
      text: 'Retire',
      icon: personRemove,
      handler: () => retireUser(user.id)
    },
    {
      text: 'Reactivate',
      icon: checkmarkCircle,
      handler: () => reactivateUser(user.id)
    },
    {
      text: 'Cancel',
      role: 'cancel'
    }
  );

  const actionSheet = await actionSheetController.create({
    header: user.employee_name || user.username,
    buttons: buttons
  });

  await actionSheet.present();
};

const openTempRoleModal = (user: any) => {
  tempRoleUser.value = user;
  tempRoleForm.value = {
    role_id: null,
    expires_at: '',
    reason: ''
  };
  showTempRoleModal.value = true;
};

const assignTemporaryRole = async () => {
  if (!tempRoleForm.value.role_id || !tempRoleForm.value.expires_at) {
    const toast = await toastController.create({
      message: 'Please select role and expiry date',
      duration: 2000,
      color: 'warning'
    });
    await toast.present();
    return;
  }

  const loading = await loadingController.create({ message: 'Assigning temporary role...' });
  await loading.present();

  try {
    // Format the datetime to MySQL format in LOCAL timezone (not UTC)
    const expiryDate = new Date(tempRoleForm.value.expires_at);
    
    // Get local date/time components
    const year = expiryDate.getFullYear();
    const month = String(expiryDate.getMonth() + 1).padStart(2, '0');
    const day = String(expiryDate.getDate()).padStart(2, '0');
    const hours = String(expiryDate.getHours()).padStart(2, '0');
    const minutes = String(expiryDate.getMinutes()).padStart(2, '0');
    const seconds = String(expiryDate.getSeconds()).padStart(2, '0');
    
    const formattedExpiry = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

    await api.post(`/users/${tempRoleUser.value.id}/assign-temporary-role`, {
      role_id: tempRoleForm.value.role_id,
      expires_at: formattedExpiry,
      reason: tempRoleForm.value.reason || null
    });

    const toast = await toastController.create({
      message: 'Temporary role assigned successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();

    showTempRoleModal.value = false;
    await loadUsers();
  } catch (error: any) {
    console.error('Assign temporary role error:', error.response?.data);
    const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Failed to assign temporary role';
    const toast = await toastController.create({
      message: errorMsg,
      duration: 5000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    await loading.dismiss();
  }
};

const revokeTemporaryRole = async (user: any) => {
  // Check if user has temporary roles first
  const tempRoles = user.temporaryRoles || user.temporary_roles || [];
  
  if (tempRoles.length === 0) {
    const toast = await toastController.create({
      message: 'This user has no temporary roles to revoke',
      duration: 2000,
      color: 'warning'
    });
    await toast.present();
    return;
  }

  const loading = await loadingController.create({ message: 'Revoking temporary roles...' });
  await loading.present();

  try {
    // Revoke all temporary roles for this user
    for (const tempRole of tempRoles) {
      await api.delete(`/users/${user.id}/temporary-roles/${tempRole.id}`);
    }

    const toast = await toastController.create({
      message: `Temporary role${tempRoles.length > 1 ? 's' : ''} revoked successfully`,
      duration: 2000,
      color: 'success'
    });
    await toast.present();

    await loadUsers();
  } catch (error: any) {
    console.error('Revoke temporary role error:', error.response?.data);
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to revoke temporary role',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    await loading.dismiss();
  }
};

const resetUserPassword = async (userId: number) => {
  try {
    await api.post(`/users/${userId}/reset-password`);
    const toast = await toastController.create({
      message: 'Password reset to 1234. User must change password on next login.',
      duration: 3000,
      color: 'success'
    });
    await toast.present();
    await loadUsers();
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to reset password',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  }
};

const suspendUser = async (userId: number) => {
  try {
    await api.post(`/users/${userId}/suspend`, { reason: 'Admin action' });
    const toast = await toastController.create({
      message: 'User suspended',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    await loadUsers();
  } catch (error) {
    console.error('Failed to suspend user', error);
  }
};

const retireUser = async (userId: number) => {
  try {
    await api.post(`/users/${userId}/retire`, { reason: 'Employee retired' });
    const toast = await toastController.create({
      message: 'User retired',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    await loadUsers();
  } catch (error) {
    console.error('Failed to retire user', error);
  }
};

const reactivateUser = async (userId: number) => {
  try {
    await api.post(`/users/${userId}/reactivate`, { reason: 'Admin reactivation' });
    const toast = await toastController.create({
      message: 'User reactivated',
      duration: 2000,
      color: 'success'
    });
    await toast.present();
    await loadUsers();
  } catch (error) {
    console.error('Failed to reactivate user', error);
  }
};

const handleSearch = () => {
  // Filtering handled by computed property
};

const handleRefresh = async (event: any) => {
  await loadUsers();
  event.target.complete();
};

const loadMore = (event: any) => {
  // Implement pagination if needed
  event.target.complete();
};

const viewUser = (user: any) => {
  // Open edit modal with user data
  editingUser.value = { ...user };
  editUserRoles.value = user.roles?.map((r: any) => r.id) || [];
  showEditModal.value = true;
};

const toggleEditUserRole = (roleId: number) => {
  const index = editUserRoles.value.indexOf(roleId);
  if (index > -1) {
    editUserRoles.value.splice(index, 1);
  } else {
    editUserRoles.value.push(roleId);
  }
};

const updateUser = async () => {
  const loading = await loadingController.create({ message: 'Updating user...' });
  await loading.present();

  try {
    // Update user basic info
    await api.put(`/users/${editingUser.value.id}`, {
      employee_name: editingUser.value.employee_name,
      mobile_number: editingUser.value.mobile_number,
      department_id: editingUser.value.department_id,
    });

    // Update roles
    await api.post(`/users/${editingUser.value.id}/assign-roles`, {
      role_ids: editUserRoles.value
    });

    const toast = await toastController.create({
      message: 'User updated successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();

    showEditModal.value = false;
    await loadUsers();
  } catch (error: any) {
    const toast = await toastController.create({
      message: error.response?.data?.message || 'Failed to update user',
      duration: 3000,
      color: 'danger'
    });
    await toast.present();
  } finally {
    await loading.dismiss();
  }
};

const toggleRole = (roleId: number) => {
  const index = selectedRoles.value.indexOf(roleId);
  if (index > -1) {
    selectedRoles.value.splice(index, 1);
  } else {
    selectedRoles.value.push(roleId);
  }
};

onMounted(async () => {
  await Promise.all([loadUsers(), loadDepartments(), loadRoles()]);
});
</script>

<style scoped>
.filter-chips {
  padding: 12px 16px;
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ion-color-primary);
  color: white;
  font-weight: bold;
  font-size: 18px;
}

.user-meta {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.add-user-button {
  font-weight: 600;
  --padding-start: 16px;
  --padding-end: 16px;
  text-transform: none;
  letter-spacing: 0.3px;
}

.temp-role-badge {
  background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%) !important;
  animation: pulseGlow 2s ease-in-out infinite;
}

.temp-icon {
  font-size: 14px;
  margin-left: 4px;
  vertical-align: middle;
}

@keyframes pulseGlow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}
</style>
