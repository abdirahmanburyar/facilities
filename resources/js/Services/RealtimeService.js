import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

class RealtimeService {
    constructor() {
        this.echo = null;
        this.initializeEcho();
    }

    initializeEcho() {
        if (typeof window !== 'undefined') {
            try {
                console.log('Initializing Echo...');
                window.Pusher = Pusher;

                this.echo = new Echo({
                    broadcaster: 'reverb',
                    key: 'reverb-app-key', // Replace with your actual Reverb key
                    wsHost: window.location.hostname,
                    wsPort: 8080,
                    wssPort: 8080,
                    forceTLS: false,
                    enabledTransports: ['ws'],
                    disableStats: true,
                });
                console.log('Echo initialized successfully');
            } catch (error) {
                console.warn('Failed to initialize Echo:', error);
                this.echo = null;
            }
        }
    }

    /**
     * Listen for facility inventory updates
     * @param {number} facilityId - The facility ID to listen to
     * @param {Function} callback - Callback function to handle updates
     */
    listenToFacilityInventory(facilityId, callback) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return;
        }

        console.log(`Setting up listeners for facility ${facilityId}`);

        // Listen to specific facility channel
        this.echo.channel(`facility-inventory.${facilityId}`)
            .listen('FacilityInventoryUpdated', (e) => {
                console.log('Facility inventory updated:', e);
                callback(e);
            });

        // Listen to general facility inventory channel
        this.echo.channel('facility-inventory')
            .listen('FacilityInventoryUpdated', (e) => {
                console.log('General facility inventory updated:', e);
                callback(e);
            });
    }

    /**
     * Listen for transfer status changes
     * @param {number} transferId - The transfer ID to listen to
     * @param {Function} callback - Callback function to handle updates
     */
    listenToTransferStatus(transferId, callback) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return;
        }

        this.echo.private(`transfer.${transferId}`)
            .listen('TransferStatusChanged', (e) => {
                console.log('Transfer status changed:', e);
                callback(e);
            });
    }

    /**
     * Listen for general inventory updates
     * @param {Function} callback - Callback function to handle updates
     */
    listenToInventoryUpdates(callback) {
        if (!this.echo) {
            console.warn('Echo not initialized');
            return;
        }

        this.echo.channel('inventory')
            .listen('refresh', (e) => {
                console.log('Inventory updated:', e);
                callback(e);
            });
    }

    /**
     * Disconnect from all channels
     */
    disconnect() {
        if (this.echo) {
            this.echo.disconnect();
        }
    }

    /**
     * Leave a specific channel
     * @param {string} channelName - The channel name to leave
     */
    leaveChannel(channelName) {
        if (this.echo) {
            this.echo.leaveChannel(channelName);
        }
    }
}

// Create a singleton instance
const realtimeService = new RealtimeService();

export default realtimeService; 