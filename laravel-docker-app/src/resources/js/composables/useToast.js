export function useToast() {
    const success = (message) => {
        // Use Filament's notification system if available
        if (window.$wire) {
            window.$wire.dispatch('notify', {
                type: 'success',
                message: message
            });
        } else {
            // Fallback to console
            console.log('Success:', message);
        }
    };

    const error = (message) => {
        if (window.$wire) {
            window.$wire.dispatch('notify', {
                type: 'error',
                message: message
            });
        } else {
            console.error('Error:', message);
        }
    };

    const warning = (message) => {
        if (window.$wire) {
            window.$wire.dispatch('notify', {
                type: 'warning',
                message: message
            });
        } else {
            console.warn('Warning:', message);
        }
    };

    const info = (message) => {
        if (window.$wire) {
            window.$wire.dispatch('notify', {
                type: 'info',
                message: message
            });
        } else {
            console.info('Info:', message);
        }
    };

    return {
        success,
        error,
        warning,
        info
    };
}