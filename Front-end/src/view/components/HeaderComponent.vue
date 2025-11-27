<script setup>
import { ref, onMounted, defineEmits } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import NavigationComponent from './NavigationComponent.vue'; // Retained as requested

// ---------------- Props & Emits ----------------
// const props = defineProps({ isLoggedIn: Boolean });
const emit = defineEmits(['update:isLoggedIn']);

const token = localStorage.getItem("token");
const userString = localStorage.getItem("user");
const defaultUser = 'Guest';

// ---------------- Refs ----------------
const route = useRoute();
const router = useRouter();
const userName = ref(defaultUser);
const isOpen = ref(false);
const isProfileDropdownOpen = ref(false); // RESTORED: State for the profile dropdown

// ---------------- Initialization: Get user name from storage ----------------
if (userString) {
    try {
        const user = JSON.parse(userString);
        // Prioritize name from localStorage for fast display
        userName.value = user?.name || defaultUser;
    } catch (e) {
        console.error("Failed to parse user data from localStorage:", e);
    }
}


// ---------------- Menu Handlers ----------------
const toggleMenu = () => {
    isOpen.value = !isOpen.value;
    // Close profile dropdown when main menu closes
    if (!isOpen.value) {
        isProfileDropdownOpen.value = false;
    }
};
const closeMenu = () => {
    isOpen.value = false;
    isProfileDropdownOpen.value = false; // Close profile dropdown
};
const onTransitionEnd = () => { /* Hook for potential future transitions/animations */ };

// RESTORED: Toggle profile dropdown visibility
const toggleProfileDropdown = () => {
    isProfileDropdownOpen.value = !isProfileDropdownOpen.value;
};

// ---------------- Logout ----------------
const logout = async () => {
 if (!token) return router.push("/");

  // Using window.confirm() instead of alert()
  if (!window.confirm("Are you sure you want to log out?")) {
    return;
  }

try {
 const res = await fetch("http://127.0.0.1:8000/api/logout", {
 method: "POST",
 headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" },
 });
 if (!res.ok) console.warn("Backend logout failed, but clearing session anyway.");

} catch (err) {
 console.error("Logout failed:", err);
} finally {
 localStorage.removeItem("token");
  localStorage.removeItem("user");
 emit('update:isLoggedIn', false);
 router.push("/");
}
};

const logoutAndClose = () => {
logout();
closeMenu();
};

// ---------------- Fetch Protected User ----------------
onMounted(async () => {
if (!router.currentRoute.value.meta.requiresAuth) return;
if (!token) return router.push("/signin");

try {
 const res = await fetch("http://127.0.0.1:8000/api/protected-route", {
 headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" },
 });

 if (!res.ok) throw new Error("Unauthorized");

 const data = await res.json();
 userName.value = data?.user?.name || userName.value;
} catch (err) {
 console.error("Failed to fetch user:", err);
 localStorage.removeItem("token");
  localStorage.removeItem("user");
 router.push("/signin");
}
});

</script>
<template>
 <nav class="navbar fixed-top">
 <div class="container-fluid px-4">
  <!-- Logo/Brand Link -->
  <router-link
      @click="closeMenu"
      class="navbar-brand py-2 fw-bold text-primary"
      :to="route.name === 'Homepage' || route.name === 'Auth/Signin' || route.name === 'Auth/Signup' ? '/' : 'dashboard'">
      <img src="/image/logo.png" alt="Logo" class="logo">
     </router-link>

     <!-- Toggler Button -->
  <button @click="toggleMenu" class="navbar-toggler" type="button" aria-label="Toggle navigation">
   <i class="ri-menu-line text-primary fs-4"></i>
   </button>

     <!-- Offcanvas Menu -->
  <div :class="['offcanvas-menu', { show: isOpen }]" @transitionend="onTransitionEnd">
      <!-- Offcanvas Header -->
  <div class="offcanvas-header">
   <h5 class="text-black text-capitalize fw-bold" id="offcanvasNavbarLabel">Menu</h5>
   <button type="button" class="btn-close p-2 rounded-circle" @click="toggleMenu" aria-label="Close"></button>
  </div>

      <!-- Offcanvas Body -->
  <div class="offcanvas-body">
       <!-- Public Links (Homepage/Auth Views) -->
   <ul class="navbar-nav public-nav" v-if="route.name === 'Homepage' || route.name === 'Auth/Signin' || route.name === 'Auth/Signup'">
   <li class="nav-item">
    <router-link @click="closeMenu" class="nav-link link" to="signin"><i class="ri-login-box-line"></i> Sign In</router-link>
   </li>
   <li class="nav-item">
    <router-link @click="closeMenu" class="nav-link link" to="signup"><i class="ri-user-add-line"></i> Sign Up</router-link>
   </li>
   </ul>

       <!-- Authenticated Links -->
   <ul class="navbar-nav auth-nav" v-else>
      <NavigationComponent @link-click="closeMenu" />
            <!-- Profile Dropdown Container -->
            <li class="nav-item user-dropdown-container">
                <!-- User Info and Dropdown Trigger -->
                <a @click="toggleProfileDropdown" class="nav-link user-info-trigger" role="button" aria-expanded="isProfileDropdownOpen">
                    <i class="ri-user-6-line me-2"></i>
                    <span class="fw-bold text-truncate">{{userName}}</span>
                    <i :class="['ri-arrow-down-s-line ms-auto', { 'rotated': isProfileDropdownOpen }]"></i>
                </a>

                <!-- Dropdown Content (Contains Logout) -->
                <div v-if="isProfileDropdownOpen" class="profile-dropdown">

                    <a @click.prevent="logoutAndClose" class="dropdown-item btn-logout-link">
                        <i class="ri-logout-circle-line me-2"></i> Log out
                    </a>
                </div>
            </li>

       <!-- Main Navigation Links -->


   </ul>
  </div>

  </div>
    <!-- Overlay to close menu on outside click -->
    <div v-if="isOpen" class="menu-backdrop" @click="closeMenu"></div>
 </div>
 </nav>
</template>

<style scoped>
/* Define a Primary Color for consistency */
:root {
    --primary-color: #3498db;
    --primary-dark: #2980b9;
    --logout-red: #e74c3c;
    --link-hover-border: 1px solid var(--primary-color);
}

.navbar {
    background-color: white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    padding: 0.5rem 0;
}
.fixed-top {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
}

.logo{
 width: 100px;
}

/* Toggler Button */
.navbar-toggler {
    border: none;
    padding: 0.5rem;
    line-height: 1;
}

/* Offcanvas Menu */
.offcanvas-menu {
    position: fixed;
    top: 0;
    right: 0;
    width: 300px; /* Reduced width for better mobile experience */
    max-width: 80vw;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 15px rgba(0, 0, 0, 0.2);
    transform: translateX(100%);
    transition: transform 0.4s ease, opacity 0.4s ease;
    z-index: 1050;
    display: flex;
    flex-direction: column;
}

.offcanvas-menu.show {
    transform: translateX(0);
}

.offcanvas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.offcanvas-body {
    padding: 1rem;
    overflow-y: auto;
    flex-grow: 1;
}

.auth-nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Links */
.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #333;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: background-color 0.3s, color 0.3s;
}

.nav-link i {
    font-size: 1.1em;
}

/* Specific styling for the user name display */
.user-info .nav-link {
    color: var(--primary-dark);
    font-weight: 700;
    background-color: #f8f9fa;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary-color);
}

/* Hover effect for router links */
.link {
    color: #555;
}
.link:hover {
    color: var(--primary-dark);
    background-color: #eef7ff;
}

/* Logout button style */
.btn-logout {
    background-color: var(--logout-red);
    color: white;
    font-weight: 600;
    padding: 0.75rem;
    margin-top: 1rem;
}
.btn-logout:hover {
    background-color: #c0392b;
    color: white;
}

/* Menu Backdrop */
.menu-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
</style>
