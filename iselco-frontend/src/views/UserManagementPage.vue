<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/dashboard"></ion-back-button>
        </ion-buttons>
        <ion-title>User Management</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="showCreateModal = true">
            <ion-icon slot="icon-only" :icon="addOutline"></ion-icon>
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
              <ion-badge v-for="role in user.roles" :key="role.id" 
                        :color="role.slug === 'superadmin' ? 'danger' : role.slug === 'department_admin' ? 'warning' : 'medium'">
                {{ role.name }}
              </ion-badge>
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
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton,
  IonButton, IonIcon, IonList, IonItem, IonLabel, IonAvatar, IonBadge,
  IonSearchbar, IonChip, IonRefresher, IonRefresherContent,
  IonInfiniteScroll, IonInfiniteScrollContent, IonModal, IonInput, IonSelect,
  IonSelectOption, IonCheckbox, actionSheetController, toastController, loadingController
} from '@ionic/vue';
import { addOutline, ellipsisVerticalOutline, personRemove, lockClosed, checkmarkCircle } from 'ionicons/icons';
import api from '@/services/api';

const searchQuery = ref('');
const selectedStatus = ref('all');
const users = ref<any[]>([]);
const departments = ref<any[]>([]);
const roles = ref<any[]>([]);
const showCreateModal = ref(false);
const hasMore = ref(false);

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
});

const selectedRoles = ref<number[]>([]);

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = !searchQuery.value || 
      user.employee_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.username?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesStatus = selectedStatus.value === 'all' || user.status === selectedStatus.value;
    
    return matchesSearch && matchesStatus;
  });
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
  const loading = await loadingController.create({ message: 'Creating user...' });
  await loading.present();

  try {
    await api.post('/users', {
      ...newUser.value,
      role_ids: selectedRoles.value
    });

    const toast = await toastController.create({
      message: 'User created successfully',
      duration: 2000,
      color: 'success'
    });
    await toast.present();

    showCreateModal.value = false;
    newUser.value = { username: '', employee_name: '', mobile_number: '', department_id: null, password: '' };
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
  const actionSheet = await actionSheetController.create({
    header: user.employee_name || user.username,
    buttons: [
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
    ]
  });

  await actionSheet.present();
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
  // Navigate to user detail page
  console.log('View user:', user);
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
</style>
