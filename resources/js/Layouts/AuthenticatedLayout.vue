<template>
    <div class="app-container">
        <!-- Font Awesome -->
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        />
        <!-- Sidebar -->
        <div :class="['sidebar', { 'sidebar-open': sidebarOpen }]">
            <div class="white-box" style="border-color: white">
                <Link :href="route('dashboard')" class="logo-container">
                    <img
                        src="/assets/images/moh.png"
                        class="moh-logo"
                        style="height: 50px"
                    />
                    <img
                        src="/assets/images/psi.jpg"
                        class="psi-logo"
                        style="height: 50px"
                    />
                </Link>
            </div>
            <button @click="toggleSidebar" class="sidebar-toggle">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    width="24"
                    height="24"
                >
                    <path
                        v-if="!sidebarOpen"
                        d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"
                        fill="currentColor"
                    />
                    <path
                        v-else
                        d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"
                        fill="currentColor"
                    />
                </svg>
            </button>

            <div class="sidebar-menu">
                <Link
                    :href="route('dashboard')"
                    class="menu-item"
                    :class="{ active: route().current('dashboard') }"
                    style="margin-top: 3.2rem"
                    @click="setCurrentPage('dashboard')"
                >
                    <div class="menu-content">
                        <div class="menu-icon">
                            <img
                                src="/assets/images/dashboard.png"
                                class="dashboard-icon"
                                style="height: 24px"
                            />
                        </div>
                        <span class="menu-text">Dashboard</span>
                    </div>
                </Link>

                <Link
                    :href="route('orders.index')"
                    class="menu-item"
                    :class="{ active: route().current('orders.*') }"
                    @click="setCurrentPage('orders')"
                >
                    <div class="menu-content">
                        <div class="menu-icon">
                            <img
                                :src="
                                    route().current('orders.*')
                                        ? '/assets/images/tracking-b.png'
                                        : '/assets/images/tracking-w.png'
                                "
                                class="order-icon"
                                style="height: 24px"
                            />
                        </div>
                        <span class="menu-text">Track</span>
                    </div>
                </Link>

                <Link
                    :href="route('pos.index')"
                    class="menu-item"
                    :class="{ active: route().current('pos.*') }"
                    @click="setCurrentPage('pos')"
                >
                    <div class="menu-content">
                        <div class="menu-icon">
                            <img
                                :src="
                                    route().current('pos.*')
                                        ? '/assets/images/pos-b.png'
                                        : '/assets/images/pos-w.png'
                                "
                                class="pos-icon"
                                style="height: 24px"
                            />
                        </div>
                        <span class="menu-text">POS</span>
                    </div>
                </Link>

                <Link
                    :href="route('inventories.index')"
                    class="menu-item"
                    :class="{ active: route().current('inventories.*') }"
                    @click="setCurrentPage('inventories')"
                >
                    <div class="menu-content">
                        <div class="menu-icon">
                            <img
                                :src="
                                    route().current('inventories.*')
                                        ? '/assets/images/inventory-b.png'
                                        : '/assets/images/inventory-w.png'
                                "
                                class="inventory-icon"
                                style="height: 24px"
                            />
                        </div>
                        <span class="menu-text">Inventory</span>
                    </div>
                </Link>

                <Link
                    :href="route('settings.index')"
                    class="menu-item"
                    :class="{ active: route().current('settings.*') }"
                    @click="setCurrentPage('settings')"
                >
                    <div class="menu-content">
                        <div class="menu-icon">
                            <img
                                :src="
                                    route().current('settings.*')
                                        ? '/assets/images/setting-b.png'
                                        : '/assets/images/setting-w.png'
                                "
                                class="setting-icon"
                                style="height: 24px"
                            />
                        </div>
                        <span class="menu-text">Settings</span>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <div
            :class="['main-content', { 'main-content-expanded': !sidebarOpen }]"
        >
            <!-- Top Navigation -->
            <div class="top-nav">
                <div class="inventory-banner">
                    <div class="flex justify-between">
                        <div class="flex flex-col">
                            <button @click="toggleSidebar" class="back-button">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    width="24"
                                    height="24"
                                >
                                    <path
                                        v-if="sidebarOpen"
                                        d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"
                                        fill="currentColor"
                                    />
                                    <path
                                        v-else
                                        d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"
                                        fill="currentColor"
                                    />
                                </svg>
                            </button>
                            <div class="inventory-text">
                                <h1>Manage Your Inventory</h1>
                                <p>"Keeping Essentials Ready, Every Time"</p>
                            </div>
                        </div>
                        <img
                            src="/assets/images/10873037.webp"
                            alt="Inventory illustration"
                            class="svg-image"
                        />
                    </div>
                    <div class="user-section">
                        <div class="flex flex-row">
                            <div class="notification-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    width="24"
                                    height="24"
                                >
                                    <path
                                        d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"
                                        fill="#FFF"
                                    />
                                </svg>
                            </div>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <span>A</span>
                                </div>
                                <div class="user-details">
                                    <span class="user-role"
                                        >Pharmaceutical Manager</span
                                    >
                                    <span class="user-name">{{
                                        $page.props.auth.user?.name
                                    }}</span>
                                    <span>{{
                                        $page.props.facility?.name
                                    }}</span>
                                </div>
                            </div>
                            <button class="logout-button" @click="logout">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    width="24"
                                    height="24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                    ></path>
                                    <polyline
                                        points="16 17 21 12 16 7"
                                    ></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                            </button>
                        </div>
                        <img
                            src="/assets/images/head_web.gif"
                            alt="Inventory illustration"
                            class="svg-image"
                        />
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <main class="relative flex flex-col pb-16">
                <div class="flex-1">
                    <slot />
                </div>
                <div
                    class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200"
                >
                    <div class="container py-2 mx-auto">
                        <div class="flex items-center justify-center gap-4">
                            <img
                                src="/assets/images/vista.png"
                                alt="Vista"
                                class="w-[80px]"
                            />
                            <span class="flex items-center text-gray-400"
                                >|</span
                            >
                            <span class="flex items-center text-gray-600"
                                >Copyright 2025 Vista. All rights
                                reserved.</span
                            >
                            <span class="flex items-center text-gray-400"
                                >|</span
                            >
                            <span
                                class="flex items-center text-gray-600 cursor-pointer hover:text-gray-800"
                                >Terms of Use</span
                            >
                            <span class="flex items-center text-gray-400"
                                >|</span
                            >
                            <span
                                class="flex items-center text-gray-600 cursor-pointer hover:text-gray-800"
                                >Privacy</span
                            >
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Toast Container -->
        <ToastContainer />
    </div>
</template>

<script>
import { Link, usePage } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import ToastContainer from "@/Components/ToastContainer.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

const { data } = usePage();

export default {
    components: {
        Link,
        ApplicationLogo,
        ToastContainer,
        Dropdown,
        DropdownLink,
        NavLink,
        ResponsiveNavLink,
    },
    props: {
        auth: Object,
        errors: Object,
    },
    data() {
        return {
            sidebarOpen: true,
            currentPage: "dashboard",
            userMenuOpen: false,
            windowWidth: window.innerWidth,
            hasResized: false,
        };
    },
    mounted() {
        // Add event listener for window resize
        window.addEventListener("resize", this.handleResize);
        this.handleResize(); // Initial check
    },
    beforeUnmount() {
        // Clean up event listener
        window.removeEventListener("resize", this.handleResize);
    },
    methods: {
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        setCurrentPage(page) {
            this.currentPage = page;
        },
        logout() {
            this.$inertia.post(route("logout"));
        },
        handleResize() {
            const newWidth = window.innerWidth;
            this.windowWidth = newWidth;

            // Only auto-collapse the sidebar on initial load, not on resize
            if (!this.hasResized && newWidth <= 1024) {
                this.sidebarOpen = false;
            }

            // Mark that we've done at least one resize check
            this.hasResized = true;
        },
    },
};
</script>

<style scoped>
/* Base Styles */
.app-container {
    display: flex;
    min-height: 100vh;
    background-color: #f9fafb;
}

/* Sidebar Styles */
.sidebar {
    width: 0;
    min-width: 0;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    z-index: 50;
    background: linear-gradient(to bottom, #14d399, #ff8500);
    transform: translateX(-100%);
    opacity: 0;
    visibility: hidden;
}

.sidebar-open {
    width: 130px;
    min-width: 130px;
    transform: translateX(0);
    opacity: 1;
    visibility: visible;
}

.white-box {
    background-color: white;
    padding: 1.5rem 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    position: relative;
}

.moh-logo {
    height: 45px;
    width: auto;
    margin-bottom: 0.5rem;
    object-fit: contain;
}

.psi-logo {
    height: 35px;
    width: auto;
    object-fit: contain;
}

.sidebar-collapsed .white-box {
    padding: 0.5rem 0;
}

.sidebar-collapsed .moh-logo {
    height: 30px;
    margin-bottom: 0.25rem;
}

.sidebar-collapsed .psi-logo {
    height: 22px;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #333;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.3s ease;
    position: absolute;
    right: 0.25rem;
    top: 0.25rem;
}

.sidebar-toggle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.sidebar-menu {
    display: flex;
    flex-direction: column;
    padding: 0;
    margin: -12px;
    flex-grow: 1;
    width: 100%;
    align-items: center;
}

.menu-item {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    margin: 0.4rem 0;
    padding: 0;
    z-index: 1;
    width: 100%;
    height: 44px;
}

.menu-item:hover {
    background-color: rgba(255, 255, 255, 0.15);
}

.menu-item.active {
    background: white;
    color: #111827;
    position: relative;
    border-top-left-radius: 25px;
    border-bottom-left-radius: 25px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    margin-right: -25px;
    padding-right: 25px;
    z-index: 5;
    height: 44px;
    display: flex;
    align-items: center;
    width: 100%;
    left: 0;
}

/* Create the curved effect for the top-right corner */
.menu-item.active::before {
    content: "";
    position: absolute;
    top: -24px;
    right: 0;
    width: 25px;
    height: 24px;
    background-color: transparent;
    border-bottom-right-radius: 20px;
    box-shadow: 10px 10px 0 0 white;
    z-index: 2;
}

/* Create the curved effect for the bottom-right corner */
.menu-item.active::after {
    content: "";
    position: absolute;
    bottom: -24px;
    right: 0;
    width: 25px;
    height: 25px;
    background-color: transparent;
    border-top-right-radius: 20px;
    box-shadow: 10px -10px 0 0 white;
    z-index: 2;
    display: block;
}

/* Ensure icon in active menu is colored correctly */
.menu-item.active .menu-icon svg {
    fill: #111827;
}

.menu-content {
    margin-left: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0;
    position: relative;
    z-index: 10;
    height: 100%;
}

.menu-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 3px;
}

.sidebar-collapsed .menu-icon {
    margin-bottom: 0;
}

.menu-text {
    white-space: nowrap;
    transition: opacity 0.3s ease;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1;
    width: 100%;
}

/* Main Content Styles */
.main-content {
    flex-grow: 1;
    margin-left: 0;
    transition: margin-left 0.3s ease, width 0.3s ease;
    display: flex;
    flex-direction: column;
    width: 100%;
}

.main-content-expanded {
    margin-left: 0;
}

/* Top Navigation Styles */
.top-nav {
    background-color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 40;
}

.inventory-banner {
    display: flex;
    align-items: center;
    background-color: #81c4f6;
    color: white;
    padding: 0.5rem 1.5rem;
    width: 100%;
    height: 156px;
    position: relative;
    overflow: hidden;
    border-top-left-radius: 40px;
    border-right-color: white;
}

.back-button {
    background-color: white;
    color: #333;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    cursor: pointer;
}

.inventory-text {
    z-index: 10;
}

.inventory-text h1 {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.inventory-text p {
    font-size: 0.9rem;
    opacity: 0.9;
}

.inventory-image {
    position: absolute;
    right: 200px;
    height: 100%;
    display: flex;
    align-items: center;
}

.svg-image {
    height: 110px;
}

.user-section {
    display: flex;
    flex-direction: column;
    align-items: start;
    padding: 0.5rem;
    position: absolute;
    top: 0;
    right: 10px;
    height: 100%;
}

.notification-icon {
    margin-right: 1rem;
    cursor: pointer;
}

.user-info {
    display: flex;
    align-items: center;
    margin-right: 1rem;
}

.user-avatar {
    width: 36px;
    height: 36px;
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 0.5rem;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-role {
    font-size: 0.7rem;
    color: white;
    opacity: 0.8;
}

.user-name {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
}

.logout-button {
    background-color: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.logout-button:hover {
    background-color: #dc2626;
}

/* Page Content Styles */
main {
    padding: 1rem;
    flex-grow: 1;
}

/* Responsive Styles */
@media (min-width: 1025px) {
    .sidebar-open {
        transform: translateX(0);
        width: 130px;
        min-width: 130px;
        opacity: 1;
        visibility: visible;
    }

    .main-content {
        margin-left: 0;
    }

    .main-content-expanded {
        margin-left: 0;
    }
}

/* When sidebar is open, adjust the main content margin on desktop */
@media (min-width: 1025px) {
    .sidebar-open + .main-content {
        margin-left: 130px;
    }
}

@media (max-width: 640px) {
    .top-nav {
        padding: 0.75rem 1rem;
    }

    .page-content {
        padding: 1rem;
    }

    .banner-title {
        font-size: 1rem;
    }

    .banner-subtitle {
        font-size: 0.75rem;
    }
}

/* Adjust margin for Dashboard menu item specifically */
.menu-item[href="/"] {
    margin-top: 2rem;
}

/* Add helper class for SVG icons */
.menu-icon svg {
    width: 24px;
    height: 24px;
    fill: currentColor;
}

/* When sidebar is open, set appropriate margin */
.sidebar-open + .main-content {
    width: calc(100% - 130px);
}
</style>
