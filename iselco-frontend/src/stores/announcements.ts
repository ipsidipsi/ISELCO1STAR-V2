import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAnnouncementStore = defineStore('announcement', {
    state: () => ({
        activeAnnouncements: [] as any[],
        history: [] as any[],
        loading: false,
        error: null as string | null
    }),

    getters: {
        unreadCount: (state) => state.activeAnnouncements.filter(a => !a.is_read).length,
        unreadAnnouncements: (state) => state.activeAnnouncements.filter(a => !a.is_read)
    },

    actions: {
        async fetchActive() {
            this.loading = true;
            try {
                const response = await api.get('/announcements');
                this.activeAnnouncements = response.data;
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Failed to fetch announcements';
            } finally {
                this.loading = false;
            }
        },

        async fetchHistory() {
            this.loading = true;
            try {
                const response = await api.get('/announcements/history');
                this.history = response.data.data;
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Failed to fetch history';
            } finally {
                this.loading = false;
            }
        },

        async createAnnouncement(payload: FormData) {
            this.loading = true;
            try {
                // payload is FormData to support file upload
                await api.post('/announcements', payload);
                await this.fetchHistory(); // Refresh history
            } catch (err: any) {
                throw err; // Let component handle error toast
            } finally {
                this.loading = false;
            }
        },

        async markAsRead(id: number) {
            try {
                await api.post(`/announcements/${id}/read`);

                // Update local state
                const index = this.activeAnnouncements.findIndex(a => a.id === id);
                if (index !== -1) {
                    this.activeAnnouncements[index].is_read = true;
                }
            } catch (err) {
                console.error('Failed to mark announcement as read', err);
            }
        }
    }
});
