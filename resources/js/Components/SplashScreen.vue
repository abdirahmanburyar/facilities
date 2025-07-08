<template>
    <div v-if="visible" class="splash-screen">
        <!-- Animated background particles -->
        <div class="particles">
            <div v-for="i in 20" :key="i" class="particle" :style="getParticleStyle(i)"></div>
        </div>
        
        <!-- Main content -->
        <div class="splash-content">
            <!-- Logo and branding -->
            <div class="brand-section">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg class="pill-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12Z" fill="currentColor"/>
                            <path d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM12 20C7.58172 20 4 16.4183 4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h1 class="app-name">PharmaStock</h1>
                        <p class="app-tagline">Intelligent Inventory Management</p>
                    </div>
                </div>
            </div>

            <!-- Loading animation -->
            <div class="loading-section">
                <div class="loading-spinner">
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                    <div class="spinner-ring"></div>
                </div>
                
                <div class="loading-progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: `${progress}%` }"></div>
                    </div>
                    <div class="progress-text">{{ Math.round(progress) }}%</div>
                </div>
                
                <div class="loading-status">
                    <div class="status-icon">
                        <svg v-if="currentStep === 'initializing'" class="status-svg" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg v-else-if="currentStep === 'loading'" class="status-svg" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg v-else-if="currentStep === 'connecting'" class="status-svg" viewBox="0 0 24 24" fill="none">
                            <path d="M8 12H8.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 12H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 12H16.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <svg v-else-if="currentStep === 'ready'" class="status-svg" viewBox="0 0 24 24" fill="none">
                            <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="status-text">{{ loadingText }}</p>
                </div>
            </div>

            <!-- System info -->
            <div class="system-info">
                <div class="info-item">
                    <span class="info-label">Version</span>
                    <span class="info-value">2.1.0</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Environment</span>
                    <span class="info-value">{{ environment }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    duration: {
        type: Number,
        default: 3000 // 3 seconds
    }
});

const emit = defineEmits(['complete']);

const visible = ref(true);
const progress = ref(0);
const currentStep = ref('initializing');
const loadingText = ref('Initializing system...');
const environment = ref('Production');

const loadingSteps = [
    { step: 'initializing', text: 'Initializing system...', progress: 25 },
    { step: 'loading', text: 'Loading inventory data...', progress: 50 },
    { step: 'connecting', text: 'Connecting to database...', progress: 75 },
    { step: 'ready', text: 'System ready!', progress: 100 }
];

let progressInterval;
let stepIndex = 0;

const getParticleStyle = (index) => {
    const delay = Math.random() * 3;
    const duration = 3 + Math.random() * 2;
    const size = 2 + Math.random() * 4;
    const left = Math.random() * 100;
    
    return {
        '--delay': `${delay}s`,
        '--duration': `${duration}s`,
        '--size': `${size}px`,
        '--left': `${left}%`
    };
};

onMounted(() => {
    // Start progress animation
    const increment = 100 / (props.duration / 100); // Update every 100ms
    progressInterval = setInterval(() => {
        progress.value = Math.min(progress.value + increment, 100);
        
        // Update loading step based on progress
        const currentStepData = loadingSteps[stepIndex];
        if (progress.value >= currentStepData.progress && stepIndex < loadingSteps.length - 1) {
            stepIndex++;
            currentStep.value = loadingSteps[stepIndex].step;
            loadingText.value = loadingSteps[stepIndex].text;
        }
        
        // Complete when progress reaches 100%
        if (progress.value >= 100) {
            clearInterval(progressInterval);
            setTimeout(() => {
                visible.value = false;
                emit('complete');
            }, 500);
        }
    }, 100);
});

onBeforeUnmount(() => {
    clearInterval(progressInterval);
});
</script>

<style scoped>
.splash-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Animated particles */
.particles {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.particle {
    position: absolute;
    width: var(--size);
    height: var(--size);
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    left: var(--left);
    bottom: -10px;
    animation: float var(--duration) ease-in-out infinite;
    animation-delay: var(--delay);
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
    }
}

.splash-content {
    text-align: center;
    padding: 3rem;
    max-width: 600px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
    animation: slideUp 0.8s ease-out;
}

/* Brand section */
.brand-section {
    margin-bottom: 3rem;
}

.logo-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
}

.logo-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
    animation: logoFloat 3s ease-in-out infinite;
}

.pill-icon {
    width: 40px;
    height: 40px;
    color: white;
}

.app-name {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin: 0;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    letter-spacing: -0.02em;
}

.app-tagline {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-weight: 500;
    letter-spacing: 0.02em;
}

/* Loading section */
.loading-section {
    margin-bottom: 2rem;
}

.loading-spinner {
    position: relative;
    width: 60px;
    height: 60px;
    margin: 0 auto 2rem;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    animation: spin 1.5s linear infinite;
}

.spinner-ring:nth-child(2) {
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
    animation-delay: 0.5s;
    border-top-color: rgba(255, 255, 255, 0.5);
}

.spinner-ring:nth-child(3) {
    width: 60%;
    height: 60%;
    top: 20%;
    left: 20%;
    animation-delay: 1s;
    border-top-color: rgba(255, 255, 255, 0.7);
}

.loading-progress-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4f46e5, #7c3aed);
    border-radius: 4px;
    transition: width 0.3s ease-out;
    box-shadow: 0 0 20px rgba(79, 70, 229, 0.5);
}

.progress-text {
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
    min-width: 40px;
}

.loading-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.status-icon {
    width: 24px;
    height: 24px;
    color: rgba(255, 255, 255, 0.8);
}

.status-svg {
    width: 100%;
    height: 100%;
    animation: statusPulse 2s ease-in-out infinite;
}

.status-text {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    font-weight: 500;
    margin: 0;
}

/* System info */
.system-info {
    display: flex;
    justify-content: center;
    gap: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.info-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
}

/* Animations */
@keyframes slideUp {
    0% {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes logoFloat {
    0%, 100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-10px) scale(1.05);
    }
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes statusPulse {
    0%, 100% {
        opacity: 0.7;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.1);
    }
}

/* Responsive design */
@media (max-width: 640px) {
    .splash-content {
        padding: 2rem;
        margin: 1rem;
        border-radius: 16px;
    }
    
    .app-name {
        font-size: 2rem;
    }
    
    .app-tagline {
        font-size: 1rem;
    }
    
    .logo-icon {
        width: 60px;
        height: 60px;
    }
    
    .pill-icon {
        width: 30px;
        height: 30px;
    }
    
    .system-info {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .splash-content {
        padding: 1.5rem;
    }
    
    .app-name {
        font-size: 1.75rem;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
    }
}
</style>
