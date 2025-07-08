<template>
    <div v-if="visible" class="splash-screen">
        <!-- Animated background with medical theme -->
        <div class="background-animation">
            <div class="floating-elements">
                <div v-for="i in 15" :key="i" class="floating-element" :style="getFloatingStyle(i)">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="gradient-overlay"></div>
        </div>
        
        <!-- Main content container -->
        <div class="content-wrapper">
            <!-- Header section -->
            <div class="header-section">
                <div class="brand-container">
                    <div class="logo-wrapper">
                        <div class="logo-circle">
                            <svg class="medical-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19 14C19 16.7614 16.7614 19 14 19H10C7.23858 19 5 16.7614 5 14V10C5 7.23858 7.23858 5 10 5H14C16.7614 5 19 7.23858 19 10V14Z" fill="currentColor"/>
                                <path d="M12 8V16M8 12H16" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="logo-glow"></div>
                    </div>
                    <div class="brand-text">
                        <h1 class="app-title">PharmaStock</h1>
                        <p class="app-subtitle">Advanced Pharmaceutical Inventory Management</p>
                    </div>
                </div>
            </div>

            <!-- Loading section -->
            <div class="loading-section">
                <div class="loading-container">
                    <!-- Animated loading rings -->
                    <div class="loading-rings">
                        <div class="ring ring-1"></div>
                        <div class="ring ring-2"></div>
                        <div class="ring ring-3"></div>
                        <div class="ring-center">
                            <div class="pulse-dot"></div>
                        </div>
                    </div>
                    
                    <!-- Progress indicator -->
                    <div class="progress-container">
                        <div class="progress-wrapper">
                            <div class="progress-bar">
                                <div class="progress-fill" :style="{ width: `${progress}%` }">
                                    <div class="progress-shine"></div>
                                </div>
                            </div>
                            <span class="progress-percentage">{{ Math.round(progress) }}%</span>
                        </div>
                    </div>
                    
                    <!-- Status display -->
                    <div class="status-display">
                        <div class="status-icon-wrapper">
                            <div class="status-icon" :class="currentStep">
                                <svg v-if="currentStep === 'initializing'" class="status-svg" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <svg v-else-if="currentStep === 'loading'" class="status-svg" viewBox="0 0 24 24" fill="none">
                                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                        </div>
                        <div class="status-text-container">
                            <p class="status-text">{{ loadingText }}</p>
                            <div class="status-dots">
                                <span class="dot" :class="{ active: currentStep === 'initializing' }"></span>
                                <span class="dot" :class="{ active: currentStep === 'loading' }"></span>
                                <span class="dot" :class="{ active: currentStep === 'connecting' }"></span>
                                <span class="dot" :class="{ active: currentStep === 'ready' }"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer section -->
            <div class="footer-section">
                <div class="system-info">
                    <div class="info-item">
                        <span class="info-label">Version</span>
                        <span class="info-value">2.1.0</span>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-item">
                        <span class="info-label">Environment</span>
                        <span class="info-value">{{ environment }}</span>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value status-badge" :class="currentStep">{{ getStatusText() }}</span>
                    </div>
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
        default: 3500 // 3.5 seconds
    }
});

const emit = defineEmits(['complete']);

const visible = ref(true);
const progress = ref(0);
const currentStep = ref('initializing');
const loadingText = ref('Initializing system components...');
const environment = ref('Production');

const loadingSteps = [
    { step: 'initializing', text: 'Initializing system components...', progress: 25 },
    { step: 'loading', text: 'Loading pharmaceutical inventory data...', progress: 50 },
    { step: 'connecting', text: 'Establishing secure database connections...', progress: 75 },
    { step: 'ready', text: 'System ready for operation!', progress: 100 }
];

let progressInterval;
let stepIndex = 0;

const getFloatingStyle = (index) => {
    const delay = Math.random() * 4;
    const duration = 4 + Math.random() * 3;
    const size = 12 + Math.random() * 20;
    const left = Math.random() * 100;
    const opacity = 0.1 + Math.random() * 0.2;
    
    return {
        '--delay': `${delay}s`,
        '--duration': `${duration}s`,
        '--size': `${size}px`,
        '--left': `${left}%`,
        '--opacity': opacity
    };
};

const getStatusText = () => {
    const statusMap = {
        'initializing': 'Initializing',
        'loading': 'Loading',
        'connecting': 'Connecting',
        'ready': 'Ready'
    };
    return statusMap[currentStep.value] || 'Unknown';
};

onMounted(() => {
    // Start progress animation
    const increment = 100 / (props.duration / 100);
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
            }, 800);
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
    background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 25%, #7c3aed 50%, #be185d 75%, #dc2626 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    overflow: hidden;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Background animation */
.background-animation {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.floating-elements {
    position: absolute;
    width: 100%;
    height: 100%;
}

.floating-element {
    position: absolute;
    width: var(--size);
    height: var(--size);
    color: rgba(255, 255, 255, 0.1);
    left: var(--left);
    bottom: -50px;
    animation: floatUp var(--duration) ease-in-out infinite;
    animation-delay: var(--delay);
    opacity: var(--opacity);
}

.gradient-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, transparent 0%, rgba(0, 0, 0, 0.3) 100%);
}

/* Content wrapper */
.content-wrapper {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 4rem;
    max-width: 700px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(30px);
    border-radius: 32px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 
        0 32px 64px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    animation: contentSlideUp 1s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Header section */
.header-section {
    margin-bottom: 4rem;
}

.brand-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
}

.logo-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-circle {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #4f46e5, #7c3aed, #be185d);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 
        0 20px 40px rgba(79, 70, 229, 0.4),
        0 0 0 1px rgba(255, 255, 255, 0.1);
    animation: logoPulse 3s ease-in-out infinite;
    position: relative;
    z-index: 2;
}

.logo-glow {
    position: absolute;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(79, 70, 229, 0.3) 0%, transparent 70%);
    border-radius: 50%;
    animation: glowPulse 3s ease-in-out infinite;
    z-index: 1;
}

.medical-icon {
    width: 50px;
    height: 50px;
    color: white;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.brand-text {
    text-align: center;
}

.app-title {
    font-size: 3rem;
    font-weight: 900;
    color: white;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: -0.02em;
    background: linear-gradient(135deg, #ffffff, #e0e7ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.app-subtitle {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-weight: 500;
    letter-spacing: 0.02em;
}

/* Loading section */
.loading-section {
    margin-bottom: 3rem;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2.5rem;
}

.loading-rings {
    position: relative;
    width: 80px;
    height: 80px;
}

.ring {
    position: absolute;
    border-radius: 50%;
    border: 2px solid transparent;
}

.ring-1 {
    width: 100%;
    height: 100%;
    border-top: 2px solid rgba(255, 255, 255, 0.3);
    animation: spin 2s linear infinite;
}

.ring-2 {
    width: 70%;
    height: 70%;
    top: 15%;
    left: 15%;
    border-top: 2px solid rgba(255, 255, 255, 0.5);
    animation: spin 1.5s linear infinite reverse;
}

.ring-3 {
    width: 40%;
    height: 40%;
    top: 30%;
    left: 30%;
    border-top: 2px solid rgba(255, 255, 255, 0.7);
    animation: spin 1s linear infinite;
}

.ring-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

.progress-container {
    width: 100%;
    max-width: 400px;
}

.progress-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.progress-bar {
    flex: 1;
    height: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4f46e5, #7c3aed, #be185d);
    border-radius: 5px;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.progress-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shine 2s ease-in-out infinite;
}

.progress-percentage {
    font-size: 1rem;
    font-weight: 700;
    color: white;
    min-width: 50px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.status-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.status-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}

.status-icon {
    width: 32px;
    height: 32px;
    color: rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
}

.status-icon.ready {
    color: #10b981;
}

.status-svg {
    width: 100%;
    height: 100%;
    animation: statusBounce 0.6s ease-out;
}

.status-text-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.status-text {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.status-dots {
    display: flex;
    gap: 0.5rem;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.dot.active {
    background: white;
    transform: scale(1.2);
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* Footer section */
.footer-section {
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.system-info {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1.5rem;
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
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 700;
}

.info-divider {
    width: 1px;
    height: 20px;
    background: rgba(255, 255, 255, 0.2);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-badge.initializing {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

.status-badge.loading {
    background: rgba(168, 85, 247, 0.2);
    color: #c084fc;
}

.status-badge.connecting {
    background: rgba(236, 72, 153, 0.2);
    color: #f472b6;
}

.status-badge.ready {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

/* Animations */
@keyframes floatUp {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: var(--opacity);
    }
    90% {
        opacity: var(--opacity);
    }
    100% {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
    }
}

@keyframes contentSlideUp {
    0% {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes logoPulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes glowPulse {
    0%, 100% {
        opacity: 0.5;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
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

@keyframes pulse {
    0%, 100% {
        opacity: 0.7;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.3);
    }
}

@keyframes shine {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

@keyframes statusBounce {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.1);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 3rem 2rem;
        margin: 1rem;
        border-radius: 24px;
    }
    
    .app-title {
        font-size: 2.5rem;
    }
    
    .app-subtitle {
        font-size: 1.1rem;
    }
    
    .logo-circle {
        width: 80px;
        height: 80px;
    }
    
    .medical-icon {
        width: 40px;
        height: 40px;
    }
    
    .system-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .info-divider {
        display: none;
    }
}

@media (max-width: 480px) {
    .content-wrapper {
        padding: 2rem 1.5rem;
    }
    
    .app-title {
        font-size: 2rem;
    }
    
    .loading-rings {
        width: 60px;
        height: 60px;
    }
    
    .status-display {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>
